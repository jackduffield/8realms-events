<?php

/******************************************************************************/
/* HELPER FUNCTION: Convert DD/MM/YYYY to YYYY-MM-DD                          */
/******************************************************************************/

/**
 * Convert a date from DD/MM/YYYY format to YYYY-MM-DD format.
 *
 * @param string $date Date in DD/MM/YYYY format.
 * @return string Converted date in YYYY-MM-DD format, or original date if format is incorrect.
 */
function events_convert_date_to_mysql( $date ) {
    $parts = explode( '/', $date );
    if ( count( $parts ) === 3 ) {
        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
    return $date;
}

/******************************************************************************/
/* SCRAPER FUNCTION: Fetch and process events                                 */
/* -------------------------------------------------------------------------- */
/* Scrape events from the external page, compare with existing events, update   */
/* any changes, and insert new events.                                        */
/******************************************************************************/

/**
 * Scrape events fzrom the external page, update changes, and insert new events.
 *
 * @return string A message summarizing the number of events added or an error message.
 */
function events_scrape() {
    $errors         = array();
    $newCount       = 0;
    $unchangedCount = 0;

    // Fetch the events page using the WordPress HTTP API.
    $response = wp_remote_get( 'https://615cb23714593.site123.me/events' );
    if ( is_wp_error( $response ) ) {
        return 'Error fetching the events page: ' . $response->get_error_message();
    }
    $body = wp_remote_retrieve_body( $response );
    if ( empty( $body ) ) {
        return 'The events page returned an empty response.';
    }

    // Suppress warnings from malformed HTML.
    libxml_use_internal_errors( true );
    $dom = new DOMDocument();
    $dom->loadHTML( $body );
    libxml_clear_errors();
    $xpath = new DOMXPath( $dom );

    // Find each event container.
    $eventDivs = $xpath->query( "//div[contains(@class, 'event') and contains(@class, 'clearfix') and contains(@class, 'box-primary') and contains(@class, 'preview-highlighter')]" );
    if ( ! $eventDivs || $eventDivs->length === 0 ) {
        return 'No events found on the page.';
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'events_data';

    foreach ( $eventDivs as $eventDiv ) {
        /**********************************/
        /* Event Title (required)         */
        /**********************************/
        $titleNodes = $xpath->query( ".//div[contains(@class, 'event-title')]", $eventDiv );
        if ( $titleNodes->length > 0 ) {
            $event_title = trim( $titleNodes->item(0)->nodeValue );
        } else {
            $errors[] = 'An event with missing title was skipped.';
            continue; // Skip if no title found.
        }

        // Check if event already exists using both event_title and start_date.
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE event_title = %s AND start_date = %s",
            $event_title,
            $start_date
        ) );
        if ( $exists > 0 ) {
            $unchangedCount++;
            continue; // Skip duplicate events.
        }

        /**********************************/
        /* Thumbnail: Check for existing  */
        /**********************************/
        $thumbnail_url = '';
        $thumbNodes    = $xpath->query( ".//div[contains(@class, 'event-image')]//img", $eventDiv );
        if ( $thumbNodes->length > 0 ) {
            // Prefer data-src if available; otherwise fallback to src.
            $img_src = $thumbNodes->item(0)->getAttribute( 'data-src' );
            if ( empty( $img_src ) ) {
                $img_src = $thumbNodes->item(0)->getAttribute( 'src' );
            }
            $basename = basename( $img_src );

            // Look for an existing attachment with a matching file name.
            $existing = get_posts( array(
                'post_type'   => 'attachment',
                'post_status' => 'inherit',
                'meta_query'  => array(
                    array(
                        'key'     => '_wp_attached_file',
                        'value'   => $basename,
                        'compare' => 'LIKE',
                    ),
                ),
            ) );
            if ( $existing && count( $existing ) > 0 ) {
                $thumbnail_url = wp_get_attachment_url( $existing[0]->ID );
            } else {
                // No existing image found; download and sideload.
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                $tmp = download_url( $img_src );
                if ( is_wp_error( $tmp ) ) {
                    $errors[] = "Failed to download image for event '$event_title'.";
                } else {
                    $file_array             = array();
                    $file_array['name']     = $basename;
                    $file_array['tmp_name'] = $tmp;
                    $attachment_id          = media_handle_sideload( $file_array, 0 );
                    if ( is_wp_error( $attachment_id ) ) {
                        $errors[] = "Media sideload failed for event '$event_title'.";
                    } else {
                        $thumbnail_url = wp_get_attachment_url( $attachment_id );
                    }
                }
            }
        }

        /**********************************/
        /* Meta Data: start_date, start_time, event_type */
        /**********************************/
        $start_date = '';
        $start_time = '';
        $date1      = '';
        $date2      = '';
        $metaNodes  = $xpath->query( ".//ul[contains(@class, 'event-meta')]", $eventDiv );
        if ( $metaNodes->length > 0 ) {
            $meta    = $metaNodes->item(0);
            $liNodes = $meta->getElementsByTagName( 'li' );
            $dates   = array();
            $times   = array();
            foreach ( $liNodes as $li ) {
                $text = trim( $li->nodeValue );
                // Look for date in DD/MM/YYYY format.
                if ( preg_match( '/(\d{2}\/\d{2}\/\d{4})/', $text, $matches ) ) {
                    $dates[] = $matches[1];
                }
                // Look for time in HH:MM format.
                if ( preg_match( '/(\d{1,2}:\d{2})/', $text, $matches ) ) {
                    $times[] = $matches[1];
                }
            }
            if ( count( $dates ) > 0 ) {
                $date1      = $dates[0];
                $start_date = events_convert_date_to_mysql( $date1 );
            }
            if ( count( $dates ) > 1 ) {
                $date2 = $dates[1];
            }
            if ( count( $times ) > 0 ) {
                $start_time = $times[0];
            }
        }
        $event_type = 'RTT';
        if ( $date1 && $date2 && ( $date1 !== $date2 ) ) {
            $event_type = 'GT';
        }

        /**********************************/
        /* Location: Extract from meta data */
        /**********************************/
        $location = '';
        $metaUl   = $xpath->query( ".//ul[contains(@class, 'event-meta') and contains(@class, 'clearfix')]", $eventDiv );
        if ( $metaUl->length > 0 ) {
            $lis = $metaUl->item(0)->getElementsByTagName( 'li' );
            if ( $lis->length > 0 ) {
                // Get the last li element.
                $lastLi        = $lis->item( $lis->length - 1 );
                $locationParts = array();
                foreach ( $lastLi->childNodes as $child ) {
                    // Exclude the <i> element's text.
                    if ( $child->nodeName !== 'i' ) {
                        $locationParts[] = $child->textContent;
                    }
                }
                $location = trim( implode( ' ', $locationParts ) );
                // Collapse multiple spaces.
                $location = preg_replace( '/\s+/', ' ', $location );
            }
        }
        if ( empty( $location ) ) {
            $errors[] = "Event '$event_title' has no location.";
            continue; // Skip event if location is missing.
        }

        /**********************************/
        /* Entry Cost: from span with data-rel="multiCurrency" */
        /**********************************/
        $entry_cost = '';
        $entryNodes = $xpath->query( ".//span[@data-rel='multiCurrency']", $eventDiv );
        if ( $entryNodes->length > 0 ) {
            $entry_cost = trim( $entryNodes->item(0)->nodeValue );
        }

        /**********************************/
        /* Description: from <p> inside event content */
        /**********************************/
        $description = '';
        $descNodes   = $xpath->query( ".//div[contains(@class, 'event-content') and contains(@class, 'breakable')]//p", $eventDiv );
        if ( $descNodes->length > 0 ) {
            $description = trim( $descNodes->item(0)->nodeValue );
        }

        /**********************************/
        /* Website Link: from anchor with aria-label "Buy Now" */
        /**********************************/
        $website_link = '';
        $linkNodes    = $xpath->query( ".//div[contains(@class, 'event-btns')]//a[contains(translate(@aria-label, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'buy now')]", $eventDiv );
        if ( $linkNodes->length > 0 ) {
            $href = $linkNodes->item(0)->getAttribute( 'href' );
            if ( strpos( $href, 'external_redirect.php' ) !== false && strpos( $href, '&url=' ) !== false ) {
                $parts = explode( '&url=', $href );
                if ( count( $parts ) > 1 ) {
                    $website_link = urldecode( $parts[1] );
                }
            } else {
                $website_link = $href;
            }
        }

        /**********************************/
        /* Dankhold Link: from anchor with aria-label "Read More" */
        /**********************************/
        $dankhold_link = '';
        $linkNodes    = $xpath->query( ".//div[contains(@class, 'event-btns')]//a[contains(translate(@aria-label, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'read more')]", $eventDiv );
        if ( $linkNodes->length > 0 ) {
            $href = $linkNodes->item(0)->getAttribute( 'href' );
            if ( strpos( $href, 'external_redirect.php' ) !== false && strpos( $href, '&url=' ) !== false ) {
                $parts = explode( '&url=', $href );
                if ( count( $parts ) > 1 ) {
                    $dankhold_link = urldecode( $parts[1] );
                }
            } else {
                $dankhold_link = $href;
            }
        }

        /**********************************/
        /* Insert the event               */
        /**********************************/
        $data = array(
            'thumbnail'    => $thumbnail_url,
            'event_title'  => $event_title,
            'start_date'   => $start_date,
            'start_time'   => $start_time,
            'event_type'   => $event_type,
            'location'     => $location,
            'entry_cost'   => $entry_cost,
            'description'  => $description,
            'website_link' => $website_link,
            'dankhold_link' => $dankhold_link,
        );
        $format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
        $insert = $wpdb->insert( $table_name, $data, $format );
        if ( $insert === false ) {
            $errors[] = "Failed to insert event '$event_title'.";
        } else {
            $newCount++;
        }
    }

    $message = "$newCount new events added, $unchangedCount events unchanged.";
    if ( ! empty( $errors ) ) {
        $message .= " Errors: " . implode( " | ", $errors );
    }
    return $message;
}

/******************************************************************************/
/* ADMIN PAGE: Scrape Events                                                  */
/******************************************************************************/

/**
 * Display the admin page for scraping events.
 *
 * @return void
 */
function events_scrape_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( __( 'Insufficient permissions.' ) );
    }

    echo '<div class="wrap">';
    echo '<h1>Scrape Events</h1>';

    // If the "Run Scraper Now" button was clicked.
    if ( isset( $_POST['run_scrape'] ) ) {
        $result = events_scrape();
        echo '<div class="updated"><p>' . esc_html( $result ) . '</p></div>';
    }

    // Handle scheduling the weekly scrape.
    if ( isset( $_POST['schedule_scrape'] ) ) {
        if ( ! wp_next_scheduled( 'events_weekly_scrape' ) ) {
            // Calculate next Monday at 05:00.
            $timestamp = strtotime( 'next Monday 05:00' );
            wp_schedule_event( $timestamp, 'weekly', 'events_weekly_scrape' );
            echo '<div class="updated"><p>Weekly scraping scheduled (Every Monday at 05:00).</p></div>';
        } else {
            echo '<div class="updated"><p>Weekly scraping is already scheduled.</p></div>';
        }
    }

    // Handle unscheduling the weekly scrape.
    if ( isset( $_POST['unschedule_scrape'] ) ) {
        $timestamp = wp_next_scheduled( 'events_weekly_scrape' );
        if ( $timestamp ) {
            wp_clear_scheduled_hook( 'events_weekly_scrape' );
            echo '<div class="updated"><p>Weekly scraping unscheduled.</p></div>';
        }
    }

    // Display the form.
    echo '<form method="post">';
    echo '<p><input type="submit" name="run_scrape" class="button button-primary" value="Run Scraper Now"></p>';
    echo '<p><input type="submit" name="schedule_scrape" class="button button-secondary" value="Schedule Weekly Scrape (Every Monday 05:00)"></p>';
    echo '<p><input type="submit" name="unschedule_scrape" class="button button-secondary" value="Unschedule Weekly Scrape"></p>';
    echo '</form>';
    echo '</div>';
}

/******************************************************************************/
/* ADD SUBMENU: Scrape Events                                                 */
/******************************************************************************/

/**
 * Add a submenu page under the events management menu for scraping events.
 *
 * @return void
 */
function events_add_scrape_submenu() {
    add_submenu_page(
        'events-manage',
        'Scrape Events',
        'Scrape Events',
        'edit_posts',
        'events-scrape',
        'events_scrape_page'
    );
}
add_action( 'admin_menu', 'events_add_scrape_submenu' );

/******************************************************************************/
/* CRON HOOK: Run the scraper on schedule                                     */
/******************************************************************************/

add_action( 'events_weekly_scrape', 'events_scrape' );

// EOF
