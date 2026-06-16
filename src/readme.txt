=== Cerber Lockout Cloudflare Sync ===
Contributors: manojmohangit
Tags: wp-cerber, cloudflare, security, sync, ip-list, firewall
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPL-2.0
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Synchronizes IP lockout events triggered by WP Cerber Security to a specified Cloudflare Account IP List for edge-level block mitigation.

== Description ==

Cerber Lockout Cloudflare Sync is a lightweight, secure bridge between WP Cerber Security and Cloudflare Account IP Lists. When WP Cerber blocks a malicious IP at the application level, this plugin automatically pushes that IP to your Cloudflare Account IP List. This allows Cloudflare to drop or challenge traffic from the offending IP at the edge—reducing server load and mitigating brute force attacks before they reach your hosting environment.

= Features =
* **Automated IP Synchronization:** Intercepts WP Cerber lockout events in real-time.
* **Support for IPv4 & IPv6:** Full validation and support for both address formats.
* **Duplicate Prevention Cache:** Uses transient-based caching to prevent redundant API queries.
* **Flexible Credentials Resolution:** Resolve API tokens from Settings or secure `wp-config.php` constants.
* **Capacity Safeguards:** In-dashboard indicators and warnings for the Cloudflare 10,000 IP list cap.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/cerber-lockout-cloudflare-sync` directory, or upload the ZIP file via **Plugins > Add New > Upload Plugin**.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **Settings > Cerber CF Sync** and configure your Account ID, List ID, and API Token.

== Frequently Asked Questions ==

= Does this require a paid Cloudflare account? =
No. Account IP Lists are available on all Cloudflare plans, including the Free plan.

= How do I find my Account ID and List ID? =
Your Account ID is displayed on the main Overview tab of any domain in your Cloudflare dashboard. The List ID is found under **Configurations > Lists** after creating a custom IP List.

= What API Token permissions are required? =
The token must be scoped to **Account > Account Filter Lists > Edit**.

== Changelog ==

= 1.1.0 =
* Feature: Added automated IP list purging logic based on age and capacity thresholds.
* Improvement: Added a WAI-ARIA accessible tabbed layout for settings configuration.
* Dev: Integrated NPM build tooling, esbuild asset minification, and packaging scripts.
* Improvement: Revamped admin dashboard UI with dynamic progress indicators, toggle switches, and native dashicons.

= 1.0.0 =
* Initial release.
* Automated IP lockout syncing, Diagnostics control panel, and capacity monitoring notices.
