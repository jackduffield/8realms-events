<?php

/******************************************************************************/
/* BLOCK REGISTRATION                                                         */
/* -------------------------------------------------------------------------- */
/* Registers a Gutenberg block "events/upcoming-events" for displaying  */
/* upcoming events (those with a start date greater than or equal to today)   */
/* as cards.                                                                  */
/******************************************************************************/

/**
 * Register Gutenberg block for displaying upcoming events.
 *
 * @return void
 */
function events_register_blocks() {
    register_block_type('events/upcoming-events', array(
        'editor_script'   => 'events-editor',   // Assuming this editor script is enqueued globally
        'editor_style'    => 'events-editor',
        'style'           => 'events-styles',
        'render_callback' => 'events_upcoming_render_callback',
    ));
}
add_action('init', 'events_register_blocks');

/******************************************************************************/
/* UPCOMING EVENTS BLOCK RENDER CALLBACK                                      */
/* -------------------------------------------------------------------------- */
/* Renders upcoming or past events from the events_data table as cards. Each   */
/* card includes:                                                             */
/*   - Thumbnail image (if available)                                         */
/*   - Event Title (required)                                                 */
/*   - Start Date and Start Time (required)                                   */
/*   - Event Type (RTT/GT, required)                                          */
/*   - Location (required)                                                    */
/*   - Entry Cost (optional)                                                  */
/*   - Description (required, up to 200 words)                                */
/*   - Website link and Bluesky link (optional)                               */
/* Cards are displayed in a responsive flex container using the               */
/* "events-container" class.                                                  */
/******************************************************************************/

/**
 * Render upcoming or past events as cards.
 *
 * Displays events based on the display mode (upcoming or past).
 *
 * @param array $attributes Block attributes.
 * @return string HTML output.
 */
function events_upcoming_render_callback( $attributes ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'events_data';

    // Determine display mode from URL (default: upcoming).
    $display_mode = isset( $_GET['display'] ) ? sanitize_text_field( $_GET['display'] ) : 'upcoming';
    $today        = date( 'Y-m-d' );

    // Build selector HTML.
    $selector  = '<div class="event-button-row">';
    if ( $display_mode === 'past' ) {
        $selector .= '<a href="' . esc_url( add_query_arg( 'display', 'upcoming' ) ) . '" class="event-button inactive">Upcoming Events</a>';
        $selector .= '<a href="' . esc_url( add_query_arg( 'display', 'past' ) ) . '" class="event-button active">Past Events</a>';
    } else {
        $selector .= '<a href="' . esc_url( add_query_arg( 'display', 'upcoming' ) ) . '" class="event-button active">Upcoming Events</a>';
        $selector .= '<a href="' . esc_url( add_query_arg( 'display', 'past' ) ) . '" class="event-button inactive">Past Events</a>';
    }
    $selector .= '</div>';

    // Query events based on the selected mode.
    if ( $display_mode === 'past' ) {
        // Past events: those with start_date earlier than today.
        $events      = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE start_date < %s ORDER BY start_date DESC, start_time DESC",
            $today
        ), ARRAY_A );
        $noEventsMsg = '<p>No past events.</p>';
    } else {
        // Upcoming events: those starting today or later.
        $events      = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE start_date >= %s ORDER BY start_date ASC, start_time ASC",
            $today
        ), ARRAY_A );
        $noEventsMsg = '<p>No upcoming events.</p>';
    }

    if ( empty( $events ) ) {
        return $selector . $noEventsMsg;
    }

    // Build the output container.
    $output = '<div class="events-container">';
    foreach ( $events as $event ) {
        $output .= '<div class="event-card">';
        // Thumbnail image if available.
        if ( ! empty( $event['thumbnail'] ) ) {
            $output .= '<img class="event-thumbnail" src="' . esc_url( $event['thumbnail'] ) . '" alt="' . esc_attr( $event['event_title'] ) . '">';
        }
        // Event title.
        $output .= '<h2>' . esc_html( $event['event_title'] ) . '</h2>';
        // Horizontal divider after title.
        $output .= '<hr class="event-title-divider">';
        // Event start date/time and type.
        $output .= '<p><strong>Starts:</strong> ' . esc_html( $event['start_date'] ) . ' ' . esc_html( $event['start_time'] ) . '</p>';
        $output .= '<p><strong>Type:</strong> ' . esc_html( $event['event_type'] ) . '</p>';
        // Display location.
        $output .= '<p><strong>Location:</strong> ' . esc_html( $event['location'] ) . '</p>';
        // Entry cost (if provided).
        if ( ! empty( $event['entry_cost'] ) ) {
            $output .= '<p><strong>Cost:</strong> ' . esc_html( $event['entry_cost'] ) . '</p>';
        }
        // Horizontal divider before description.
        $output .= '<hr class="event-description-divider">';
        // Event description.
        $output .= '<p>' . esc_html( $event['description'] ) . '</p>';
        // Button row.
        $output .= '<div class="event-button-row">';
        // "Book Now" button: website link.
        if ( ! empty( $event['website_link'] ) ) {
            $output .= '<a href="' . esc_url( $event['website_link'] ) . '" target="_blank" class="event-button">Book Now</a>';
        } else {
            $output .= '<a class="event-button disabled">Book Now</a>';
        }
        // "Read More" button: dankhold link.
        if ( ! empty( $event['dankhold_link'] ) ) {
            $output .= '<a href="https://615cb23714593.site123.me' . esc_url( $event['dankhold_link'] ) . '" target="_blank" class="event-button">Read More</a>';
        } else {
            $output .= '<a class="event-button disabled">Read More</a>';
        }
        // "Plan Journey" button: Opens Google Maps directions.
        $planJourneyUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode( $event['location'] );
        $output        .= '<a href="' . esc_url( $planJourneyUrl ) . '" target="_blank" class="event-button">Plan Journey</a>';
        $output        .= '</div>'; // End button row.
        $output        .= '</div>'; // End event card.
    }
    $output .= '</div>'; // End container.

    // Prepend the selector row to the output.
    return $selector . $output;
}

// EOF
