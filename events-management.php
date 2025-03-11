<?php

// If a Google Maps API key file exists, include it.
if (file_exists(plugin_dir_path(__FILE__) . 'google-maps-api-key.php')) {
    require_once plugin_dir_path(__FILE__) . 'google-maps-api-key.php';
}

/******************************************************************************/
/* ADMIN MENU & PAGE HANDLING                                                 */
/* -------------------------------------------------------------------------- */
/* Adds an admin menu page "Manage Events" for users with the "edit_posts"     */
/* capability (Authors and above). This page routes to various subviews such    */
/* as:                                                                        */
/* - Listing all events                                                       */
/* - Creating a new event                                                     */
/* - Editing an existing event                                                */
/* - Searching events                                                         */
/******************************************************************************/

/**
 * Add the admin menu page for managing events.
 *
 * @return void
 */
function events_manage_admin_menu() {
    add_menu_page(
        'Manage Events',
        'Manage Events',
        'edit_posts',
        'events-manage',
        'events_manage_page'
    );
}
add_action('admin_menu', 'events_manage_admin_menu');

/******************************************************************************/
/* ADMIN FORMS & DISPLAY FUNCTIONS                                            */
/* -------------------------------------------------------------------------- */
/* Functions to display:                                                      */
/* - A list of events (as a table with action buttons)                        */
/* - A form to create a new event                                               */
/* - A form to edit an existing event                                          */
/* - A search form and search results                                         */
/******************************************************************************/

/**
 * List Events.
 *
 * Displays a table of events (ordered by start date and start time ascending)
 * with "Edit" and "Delete" action buttons.
 *
 * @return void
 */
function events_list() {
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'events_data';

    // Fetch events ordered by start_date and start_time.
    $events = $wpdb->get_results("SELECT * FROM $table_name ORDER BY start_date ASC, start_time ASC", ARRAY_A);

    echo '<div class="wrap">';
    echo '<h1>Manage Events</h1>';
    echo '<a href="' . esc_url(admin_url('admin.php?page=events-manage&action=new')) . '" class="button button-primary" style="margin-bottom:10px;">Add New Event</a>';
    echo '<a href="' . esc_url(admin_url('admin.php?page=events-manage&action=search')) . '" class="button button-secondary" style="margin-left:10px;">Search Events</a>';
    if (empty($events)) {
        echo '<p>No events available.</p>';
    } else {
        echo '<table class="widefat fixed events-table" cellspacing="0">';
        echo '<thead><tr>';
        echo '<th>ID</th>';
        echo '<th>Thumbnail</th>';
        echo '<th>Event Title</th>';
        echo '<th>Start Date</th>';
        echo '<th>Start Time</th>';
        echo '<th>Type (RTT/GT)</th>';
        echo '<th>Location</th>';
        echo '<th>Entry Cost</th>';
        echo '<th>Description</th>';
        echo '<th>Website</th>';
        echo '<th>Bluesky</th>';
        echo '<th>Actions</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        foreach ($events as $event) {
            echo '<tr>';
            echo '<td>' . esc_html($event['id']) . '</td>';
            $thumb = $event['thumbnail'] ? '<img src="' . esc_url($event['thumbnail']) . '" style="max-width:80px; height:auto;">' : '';
            echo '<td>' . $thumb . '</td>';
            echo '<td>' . esc_html($event['event_title']) . '</td>';
            echo '<td>' . esc_html($event['start_date']) . '</td>';
            echo '<td>' . esc_html($event['start_time']) . '</td>';
            echo '<td>' . esc_html($event['event_type']) . '</td>';
            echo '<td>' . esc_html($event['location']) . '</td>';
            echo '<td>' . esc_html($event['entry_cost']) . '</td>';
            echo '<td>' . esc_html($event['description']) . '</td>';
            echo '<td>' . esc_url($event['website_link']) . '</td>';
            echo '<td>' . esc_url($event['bluesky_link']) . '</td>';
            // Actions: Edit button (as a link) and Delete button (form with confirmation)
            $edit_url = add_query_arg(
                array(
                    'page'   => 'events-manage',
                    'action' => 'edit',
                    'id'     => $event['id']
                ),
                admin_url('admin.php')
            );
            echo '<td>';
            echo '<a href="' . esc_url($edit_url) . '" class="button button-secondary">Edit</a> ';
            echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this event?\');">';
            echo '<input type="hidden" name="delete_event" value="' . esc_attr($event['id']) . '">';
            echo '<input type="submit" value="Delete" class="button button-secondary">';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    echo '</div>';
}

/**
 * New Event Form.
 *
 * Displays a form for adding a new event with required fields.
 *
 * @return void
 */
function events_create_form() {
    ?>
    <div class="wrap">
        <h1>Add New Event</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">Thumbnail Image</th>
                    <td>
                        <input type="text" id="thumbnail-url" name="thumbnail" style="width: 80%;">
                        <input type="button" id="upload-thumbnail-button" class="button" value="Select Image">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Event Title *</th>
                    <td><input type="text" name="event_title" style="width: 100%;" required></td>
                </tr>
                <tr>
                    <th scope="row">Start Date *</th>
                    <td><input type="date" name="start_date" required></td>
                </tr>
                <tr>
                    <th scope="row">Start Time</th>
                    <td><input type="time" name="start_time"></td>
                </tr>
                <tr>
                    <th scope="row">Event Type (RTT/GT) *</th>
                    <td><input type="text" name="event_type" placeholder="RTT or GT" required></td>
                </tr>
                <tr>
                    <th scope="row">Location *</th>
                    <td>
                        <input type="text" id="location-input" name="location" style="width: 100%;" required>
                        <button type="button" id="show-map-btn" class="button">Show Map</button>
                        <div id="location-map" style="display: none; margin-top: 10px; width: 100%; height: 300px;"></div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Entry Cost</th>
                    <td><input type="text" name="entry_cost" style="width: 100%;"></td>
                </tr>
                <tr>
                    <th scope="row">Description *(max 200 words)</th>
                    <td><textarea name="description" rows="6" style="width: 100%;" required></textarea></td>
                </tr>
                <tr>
                    <th scope="row">Website Link</th>
                    <td><input type="url" name="website_link" style="width: 100%;"></td>
                </tr>
                <tr>
                    <th scope="row">Bluesky Link</th>
                    <td><input type="url" name="bluesky_link" style="width: 100%;"></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="create_event" value="Create Event" class="button button-primary">
                <a href="<?php echo admin_url('admin.php?page=events-manage'); ?>" class="button">Back</a>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Edit Event Form.
 *
 * Displays a form for editing an existing event.
 *
 * @param int $id The event ID.
 * @return void
 */
function events_edit_form($id) {
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'events_data';
    $event      = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
    if (!$event) {
        echo '<div class="wrap"><p>Event not found.</p></div>';
        return;
    }
    ?>
    <div class="wrap">
        <h1>Edit Event</h1>
        <form method="post">
            <input type="hidden" name="id" value="<?php echo esc_attr($event['id']); ?>">
            <table class="form-table">
                <tr>
                    <th scope="row">Thumbnail Image</th>
                    <td>
                        <input type="text" id="thumbnail-url" name="thumbnail" value="<?php echo isset($event['thumbnail']) ? esc_attr($event['thumbnail']) : ''; ?>" style="width: 80%;">
                        <input type="button" id="upload-thumbnail-button" class="button" value="Select Image">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Event Title *</th>
                    <td><input type="text" name="event_title" style="width: 100%;" value="<?php echo esc_attr($event['event_title']); ?>" required></td>
                </tr>
                <tr>
                    <th scope="row">Start Date *</th>
                    <td><input type="date" name="start_date" value="<?php echo esc_attr($event['start_date']); ?>" required></td>
                </tr>
                <tr>
                    <th scope="row">Start Time</th>
                    <td><input type="time" name="start_time" value="<?php echo esc_attr($event['start_time']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row">Event Type (RTT/GT) *</th>
                    <td><input type="text" name="event_type" placeholder="RTT or GT" value="<?php echo esc_attr($event['event_type']); ?>" required></td>
                </tr>
                <tr>
                    <th scope="row">Location *</th>
                    <td>
                        <input type="text" id="location-input" name="location" style="width: 100%;" value="<?php echo esc_attr($event['location']); ?>" required>
                        <button type="button" id="show-map-btn" class="button">Check Map Location</button>
                        <div id="location-map" style="display: none; margin-top: 10px; width: 100%; height: 300px;"></div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Entry Cost</th>
                    <td><input type="text" name="entry_cost" style="width: 100%;" value="<?php echo esc_attr($event['entry_cost']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row">Description *(max 200 words)</th>
                    <td><textarea name="description" rows="6" style="width: 100%;" required><?php echo esc_textarea($event['description']); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row">Website Link</th>
                    <td><input type="url" name="website_link" style="width: 100%;" value="<?php echo esc_attr($event['website_link']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row">Bluesky Link</th>
                    <td><input type="url" name="bluesky_link" style="width: 100%;" value="<?php echo esc_attr($event['bluesky_link']); ?>"></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="update_event" value="Update Event" class="button button-primary">
                <a href="<?php echo admin_url('admin.php?page=events-manage'); ?>" class="button">Back</a>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Process Create/Update/Delete submissions.
 *
 * @return void
 */
function events_process_submissions() {
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'events_data';

    // Process new event creation.
    if (isset($_POST['create_event'])) {
        $data = array(
            'thumbnail'    => sanitize_text_field($_POST['thumbnail']),
            'event_title'  => sanitize_text_field($_POST['event_title']),
            'start_date'   => sanitize_text_field($_POST['start_date']),
            'start_time'   => sanitize_text_field($_POST['start_time']),
            'event_type'   => sanitize_text_field($_POST['event_type']),
            'location'     => sanitize_text_field($_POST['location']),
            'entry_cost'   => sanitize_text_field($_POST['entry_cost']),
            'description'  => sanitize_textarea_field($_POST['description']),
            'website_link' => esc_url_raw($_POST['website_link']),
            'bluesky_link' => esc_url_raw($_POST['bluesky_link']),
        );
        $wpdb->insert($table_name, $data);
        echo '<div class="updated"><p>New event created successfully!</p></div>';
    }

    // Process event update.
    if (isset($_POST['update_event']) && isset($_POST['id'])) {
        $data = array(
            'thumbnail'    => sanitize_text_field($_POST['thumbnail']),
            'event_title'  => sanitize_text_field($_POST['event_title']),
            'start_date'   => sanitize_text_field($_POST['start_date']),
            'start_time'   => sanitize_text_field($_POST['start_time']),
            'event_type'   => sanitize_text_field($_POST['event_type']),
            'location'     => sanitize_text_field($_POST['location']),
            'entry_cost'   => sanitize_text_field($_POST['entry_cost']),
            'description'  => sanitize_textarea_field($_POST['description']),
            'website_link' => esc_url_raw($_POST['website_link']),
            'bluesky_link' => esc_url_raw($_POST['bluesky_link']),
        );
        $wpdb->update($table_name, $data, array('id' => intval($_POST['id'])));
        echo '<div class="updated"><p>Event updated successfully!</p></div>';
    }

    // Process event deletion.
    if (isset($_POST['delete_event'])) {
        $id = intval($_POST['delete_event']);
        $wpdb->delete($table_name, array('id' => $id));
        echo '<div class="updated"><p>Event deleted successfully!</p></div>';
    }
}

/**
 * Search Events Form.
 *
 * Displays a form for filtering events by start date, event type, and entry cost.
 *
 * @return void
 */
function events_search_form() {
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    ?>
    <div class="wrap">
        <h1>Search Events</h1>
        <form method="get" action="">
            <!-- Preserve the menu page parameter -->
            <input type="hidden" name="page" value="events-manage">
            <input type="hidden" name="action" value="search">
            <table class="form-table">
                <tr>
                    <th scope="row">Start Date</th>
                    <td><input type="date" name="start_date"></td>
                </tr>
                <tr>
                    <th scope="row">Event Type (RTT/GT)</th>
                    <td><input type="text" name="event_type" placeholder="RTT or GT"></td>
                </tr>
                <tr>
                    <th scope="row">Entry Cost</th>
                    <td><input type="text" name="entry_cost" placeholder="e.g., 10 or Free"></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" value="Search" class="button button-primary">
                <a href="<?php echo admin_url('admin.php?page=events-manage'); ?>" class="button">Back</a>
            </p>
        </form>
    </div>
    <?php

    // Process search results if filters are present.
    if (isset($_GET['start_date']) || isset($_GET['event_type']) || isset($_GET['entry_cost'])) {
        events_search_results();
    }
}

/**
 * Display Search Results.
 *
 * Queries the events_data table using filters from the search form and
 * displays the results.
 *
 * @return void
 */
function events_search_results() {
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'events_data';
    $query      = "SELECT * FROM $table_name WHERE 1=1";

    if (!empty($_GET['start_date'])) {
        $query .= $wpdb->prepare(" AND start_date = %s", sanitize_text_field($_GET['start_date']));
    }
    if (!empty($_GET['event_type'])) {
        $query .= $wpdb->prepare(" AND event_type LIKE %s", '%' . $wpdb->esc_like(sanitize_text_field($_GET['event_type'])) . '%');
    }
    if (!empty($_GET['entry_cost'])) {
        $query .= $wpdb->prepare(" AND entry_cost LIKE %s", '%' . $wpdb->esc_like(sanitize_text_field($_GET['entry_cost'])) . '%');
    }

    $results = $wpdb->get_results($query, ARRAY_A);
    echo '<h2>Search Results</h2>';
    if (empty($results)) {
        echo '<p>No events match your search criteria.</p>';
        return;
    }
    echo '<table class="widefat fixed events-table" cellspacing="0">';
    echo '<thead><tr>';
    foreach (array_keys($results[0]) as $key) {
        echo '<th>' . esc_html($key) . '</th>';
    }
    echo '<th>Actions</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    foreach ($results as $row) {
        echo '<tr>';
        foreach ($row as $value) {
            echo '<td>' . esc_html($value) . '</td>';
        }
        // Actions: Edit as a link, Delete as a form with confirmation.
        $edit_url = add_query_arg(
            array(
                'page'   => 'events-manage',
                'action' => 'edit',
                'id'     => $row['id']
            ),
            admin_url('admin.php')
        );
        echo '<td>
            <a href="' . esc_url($edit_url) . '" class="button button-secondary">Edit</a>
            <form method="post" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this event?\');">
                <input type="hidden" name="delete_event" value="' . esc_attr($row['id']) . '">
                <input type="submit" value="Delete" class="button button-secondary">
            </form>
        </td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<a href="' . admin_url('admin.php?page=events-manage') . '" class="button">Back</a>';
}

/******************************************************************************/
/* ADMIN PAGE ROUTING                                                         */
/* -------------------------------------------------------------------------- */
/* The main Events Management admin page routes to the appropriate view based  */
/* on the "action" query parameter (list events, new event form, edit event    */
/* form, or search form).                                                     */
/******************************************************************************/

/**
 * Main admin page for managing events.
 *
 * Routes to list events, new event form, edit event form, or search form.
 *
 * @return void
 */
function events_manage_page() {
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Process form submissions (create, update, delete) before outputting the page.
    events_process_submissions();

    // Route based on the "action" query parameter.
    if (isset($_GET['action'])) {
        $action = sanitize_text_field($_GET['action']);
        if ($action == 'new') {
            events_create_form();
        } elseif ($action == 'edit' && isset($_GET['id'])) {
            events_edit_form(intval($_GET['id']));
        } elseif ($action == 'search') {
            events_search_form();
        } else {
            events_list();
        }
    } else {
        events_list();
    }
}

/******************************************************************************/
/* ENQUEUE EVENTS FORM HELPERS                                                */
/******************************************************************************/

/**
 * Enqueue scripts for events management admin pages.
 *
 * @param string $hook The current admin page hook.
 * @return void
 */
function events_manage_enqueue_scripts($hook) {
    // Only load on our events management admin pages.
    if (strpos($hook, 'events-manage') === false) {
        return;
    }
    // Enqueue the built-in media uploader.
    wp_enqueue_media();
    // Enqueue our custom media uploader script.
    wp_enqueue_script('events-manage-media', plugins_url('media-upload.js', __FILE__), array('jquery'), '1.0', true);
    // Enqueue our custom map preview script.
    wp_enqueue_script('events-manage-map', plugins_url('map-preview.js', __FILE__), array('jquery'), '1.0', true);
    // Pass the API key to our map script.
    wp_localize_script('events-manage-map', 'EM_MapVars', array(
        'apiKey' => defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : ''
    ));
}
add_action('admin_enqueue_scripts', 'events_manage_enqueue_scripts');

// EOF
