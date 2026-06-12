# Cerber Lockout Cloudflare Sync

WordPress plugin that synchronizes IP lockout events triggered by WP Cerber Security to a specified Cloudflare Account IP List for edge-level block mitigation.

Developed by **Manoj Mohan** ([manojmohan.dev](https://manojmohan.dev)).

## Features

- **Automated IP Synchronization**: Intercepts WP Cerber lockout events (`cerber_ip_locked` hook) and updates a Cloudflare Account IP List via the Cloudflare API.
- **Support for IPv4 and IPv6**: Fully validates and handles both address formats.
- **Duplicate Prevention Cache**: Uses transient-based caching to prevent redundant API calls for recently blocked IPs.
- **Automatic Token Resolution**: Supports fallback token resolution from constants (`CLOUDFLARE_API_TOKEN`, `CLOUDFLARE_API_KEY`) and database values from the official Cloudflare plugin.
- **Admin Control Panel**: Sleek, WordPress-native card-based settings page (`Settings > Cerber CF Sync`) protected by administrator capability checks.
- **Interactive Control Center**: Features diagnostic AJAX tools to manually test the API connection, manually block IPs, or clear local transients.
- **List Capacity Monitoring**: Displays Cloudflare IP list size and percentage utilization in real-time, with persistent admin warning notifications when approaching the 10,000-item limit.
- **Notification System**: Rate-limited email alerts sent to the administrator upon synchronization success or API token failure.

## Directory Structure

```text
cerber-lockout-cloudflare-sync/
├── .gitignore                                     # Git ignore definitions
├── README.md                                      # Project Readme
├── CHANGELOG.md                                   # Keep a Changelog version history
├── src/                                           # WordPress plugin source files
│   ├── cerber-lockout-cloudflare-sync.php         # Main plugin bootstrapper
│   ├── uninstall.php                              # Complete option/transient cleanup on deletion
│   └── includes/
│       ├── class-api-client.php                   # Cloudflare API client
│       ├── class-admin-ui.php                     # Settings page, CSS, and AJAX actions
│       ├── class-sync-handler.php                 # WP Cerber action hook listener
│       └── class-notifier.php                     # Email notifications manager
├── docs/                                          # GitHub Pages static documentation
│   ├── index.html                                 # Landing page
│   ├── documentation.html                         # Setup & configuration guide
│   └── security.html                              # Security details & data-flow architecture
└── dist/                                          # Distribution/Release builds
    └── cerber-lockout-cloudflare-sync-v1.0.0.zip  # Versioned installable WordPress plugin ZIP
```

## Installation

1. Download the latest release ZIP file from the [dist/](dist/) directory.
2. In the WordPress admin dashboard, navigate to **Plugins > Add New > Upload Plugin**.
3. Select the downloaded ZIP file and click **Install Now**.
4. Activate the plugin.
5. Go to **Settings > Cerber CF Sync** to configure your Cloudflare API settings.

## License

Licensed under the [GPL-2.0 License](https://www.gnu.org/licenses/gpl-2.0.html). Developed by Manoj Mohan.

