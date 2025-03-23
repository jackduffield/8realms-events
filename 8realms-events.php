<?php

/**
 * Plugin Name: 8Realms Events
 * Description: A plugin that creates 8Realms Events. Incorporates three suites of functionality: Events Ingest, Events Management, and Events Display.
 * Version: 1.0
 * Author: Jack Duffield
 */

/******************************************************************************/
/* Core Tables Creation on Activation                                         */
/******************************************************************************/

/**
 * Create the events_data table.
 *
 * Creates the 'events_data' table used to store events data.
 *
 * @return void
 */
function events_create_table() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'events_data';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        thumbnail varchar(255) DEFAULT '',                        -- URL for thumbnail image
        event_title varchar(255) NOT NULL,                        -- * Required event title
        start_date date NOT NULL,                                 -- * Required start date
        start_time time DEFAULT NULL,                             -- Optional start time
        event_type varchar(10) NOT NULL,                          -- * Required, either 'RTT' or 'GT'
        location varchar(255) NOT NULL,                           -- * Required location
        entry_cost varchar(50) DEFAULT '',                        -- Entry cost (as text for flexibility)
        description text NOT NULL,                                -- * Required description (up to 200 words)
        website_link varchar(255) DEFAULT '',                     -- Website link for the event
        dankhold_link varchar(255) DEFAULT '',                     -- Bluesky link for the event
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'events_create_table');

/******************************************************************************/
/* Enqueue Plugin Styles                                                      */
/******************************************************************************/

/**
 * Enqueue the plugin stylesheet.
 *
 * @return void
 */
function events_enqueue_styles() {
    wp_enqueue_style( 'events-styles', plugins_url( 'style.css', __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'events_enqueue_styles' );

/******************************************************************************/
// Enqueue Block Editor Assets
/******************************************************************************/

/**
 * Enqueue block editor assets.
 *
 * @return void
 */
function events_enqueue_block_editor_assets() {
    wp_enqueue_script(
        'events-editor', // Handle.
        plugins_url('blocks.js', __FILE__), // Block editor script.
        array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor'), // Dependencies.
        filemtime(plugin_dir_path(__FILE__) . 'blocks.js') // Version: file modification time.
    );

    wp_enqueue_style(
        'events-editor', // Handle.
        plugins_url('editor.css', __FILE__), // Block editor styles.
        array('wp-edit-blocks'), // Dependencies.
        filemtime(plugin_dir_path(__FILE__) . 'editor.css') // Version: file modification time.
    );
}
add_action('enqueue_block_editor_assets', 'events_enqueue_block_editor_assets');

/******************************************************************************/
/* Include Main Plugin Functionality                                          */
/******************************************************************************/

// Include the Events Management functionality.
require_once plugin_dir_path(__FILE__) . 'events-management.php';

// Include the Events Display functionality.
require_once plugin_dir_path(__FILE__) . 'events-display.php';

// Include the Events Ingest functionality.
require_once plugin_dir_path(__FILE__) . 'events-ingest.php';

/******************************************************************************/
/* On Deactivation                                                            */
/******************************************************************************/

/**
 * Clear scheduled scrape on deactivation.
 *
 * @return void
 */
function events_clear_scheduled_scrape() {
    wp_clear_scheduled_hook( 'events_weekly_scrape' );
}
register_deactivation_hook( __FILE__, 'events_Clear_scheduled_scrape' );

/******************************************************************************/
/* On Uninstall                                                               */
/******************************************************************************/

// TODO

// EOF
