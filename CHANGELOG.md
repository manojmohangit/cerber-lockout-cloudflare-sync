# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-06-16

### Added
- **IP Purging**: Automated IP list purging logic based on block age and list capacity thresholds.
- **Tabbed Settings**: Modular settings routing to separate API credentials, alerts, purging, and diagnostic controls.
- **Accessibility**: Improved settings tabs with proper WAI-ARIA tablist roles and keyboard arrow navigation.
- **Build Automation**: Automated build system using esbuild for asset minification and custom Node scripts to package versioned releases.

## [1.0.0] - 2026-06-11

### Added
- **Plugin Bootstrap**: Core initialization loader `cerber-lockout-cloudflare-sync.php` defining `CERBER_CF_SYNC_VERSION`.
- **Cloudflare API Client**: Communication class supporting GET list items (lookup check) and POST list items (add IP block) with specific scopes.
- **WP Cerber Sync Listener**: Hook receiver `cerber_ip_locked` capturing locked IPs, validating IPv4/IPv6, and executing the API sync.
- **Cache circuit-breaker**: Transients for blocked IPs (`cf_blocked_*`) to minimize API load on repeating attacks.
- **Token Resolution Fallbacks**: Checks plugin settings, custom constants (`CLOUDFLARE_API_TOKEN` / `CLOUDFLARE_API_KEY`), and options from the official Cloudflare plugin.
- **Admin Configuration UI**: Custom Settings Page under Settings menu using WordPress Settings API, styled with premium card layout.
- **Diagnostic Panel**: AJAX actions for Test Connection, Manual IP Lockout, and Clear Transient Cache.
- **Email Notifications**: Action success and API error alerts, with built-in rate-limiting and permission failure alerts (Account Filter Lists: Edit).
- **Uninstall Teardown**: Complete database option and transient cleanup upon plugin deletion.
- **Distribution Build**: Added versioned installable WordPress plugin ZIP file at `dist/cerber-lockout-cloudflare-sync-v1.0.0.zip`.