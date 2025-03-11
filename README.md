<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://8realms.net/wp-content/uploads/2024/06/8realmswhite.png">
  <source media="(prefers-color-scheme: light)" srcset="https://8realms.net/wp-content/uploads/2025/03/8realms.png">
  <img alt="8Realms Logo" src="https://8realms.net/wp-content/uploads/2024/06/8realmswhite.png" width="50%" align="middle">
</picture>

# 8Realms Events

**8Realms Events** is a suite of functionality that allows the user to ingest, manage, and display event data for Age of Sigmar on a WordPress site. Designed for use at [8realms.net](https://8realms.net), it automatically scrapes event details from external sources, enables admin management of event records, and presents upcoming or past events via custom Gutenberg blocks.

The plugin is developed primarily in PHP and JavaScript with supporting SQL, HTML, and CSS.

## Features

**Data Ingestion & Scraping**
  - Automatically scrape event details (title, date, time, location, thumbnail image, etc.) from external sources.
  - Parse and convert dates for MySQL compatibility.
  - Insert new events into the `events_data` table while checking for duplicates.

**Event Management**
  - Admin interface for viewing, editing, and deleting events.
  - Built-in search and filtering of event records.
  - Schedule or run manual scrapes to keep event data fresh.

**Gutenberg Blocks**
  - Display upcoming or past events as responsive cards.
  - Switch between upcoming and past events using a selector.
  - Integrate event listings into posts and pages with custom blocks.

**Media & Map Integration**
  - Automatically sideload event thumbnail images.
  - Integrate Google Maps for location previews and directions.

**Custom Styles**
  - Responsive design.
  - Adjust plugin styles based on the current WordPress theme.

## Installation

1. **Upload the Plugin:**  
   Upload the `8realms-events` folder to the `/wp-content/plugins/` directory of your WordPress installation.

2. **Activate the Plugin:**  
   In your WordPress admin dashboard, go to **Plugins** and activate **8Realms Events**.

3. **Configure Google Maps API Key (Optional):**  
   To enable map previews, copy `google-maps-api-key.example.php` to `google-maps-api-key.php` and enter your API key.

## Usage

### Frontend Functionality

- In the block editor, insert the **Upcoming Events** block to display events starting today or later.
- Use the **Past Events** view (via a URL parameter) to showcase historical event data.
- Customize block attributes to adjust filtering and layout to suit your site.

### Backend Functionality

- Use the **Manage Events** admin page to view, edit, or delete event records.
- Access the **Scrape Events** submenu to run or schedule scrapes, ensuring your event data is up to date.
- Review and update individual event data as needed via an intuitive admin interface.

## File Structure

    8realms-events/
    ├── 8realms-events.php                # Main plugin file; initializes core tables, entry point for hooks, and setup
    ├── blocks.js                         # Registers Gutenberg blocks for event display
    ├── editor.css                        # Editor-specific styles for Gutenberg blocks
    ├── events-display.php                # Functions for frontend display of event cards
    ├── events-ingest.php                 # Handles scraping and ingestion of event data
    ├── events-management.php             # Admin functions for managing event records (view, edit, delete, search)
    ├── google-maps-api-key.example.php   # Example file for entering a Google Maps API key
    ├── map-preview.js                    # JavaScript for previewing event locations on a map
    ├── media-upload.js                   # JavaScript for handling media uploads in event forms
    ├── README.md                         # Plugin documentation and usage instructions
    └── style.css                         # Frontend stylesheet for event cards and overall plugin styling

## Changelog

### 1.0.0
- Initial release with full functionality:
  - Data ingestion and scraping of external event data.
  - Admin area for managing event records with search and filter capabilities.
  - Custom Gutenberg blocks for displaying upcoming and past events.
  - Integration with Google Maps and media sideloading for event thumbnails.
  - Responsive design and custom styles for a seamless user experience.

## Contributing

Contributions are welcome! To contribute:

1. Fork the repository.
2. Create a new branch for your feature or bug fix.
3. Submit a pull request with detailed information about your changes.

## License

This project is licensed under the [GNU General Public Licence](https://www.gnu.org/licenses/gpl-3.0.en.html).

## Support

For support or to report issues, please open an issue on the GitHub repository.
