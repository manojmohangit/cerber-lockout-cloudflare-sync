<?php
/**
 * Plugin Name:       Cerber Lockout Cloudflare Sync
 * Plugin URI:        https://github.com/manojmohangit/cerber-lockout-cloudflare-sync/
 * Description:       Synchronizes IP lockout events triggered by WP Cerber Security to a specified Cloudflare Account IP List for edge-level block mitigation.
 * Version:           1.0.0
 * Author:            Manoj Mohan
 * Author URI:        https://manojmohan.dev
 * License:           GPL-2.0
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cerber-lockout-cloudflare-sync
 * Requires at least: 5.8
 * Tested up to:      6.7
 * Requires PHP:      7.4
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Plugin Constants.
define( 'CERBER_CF_SYNC_VERSION', '1.0.0' );
define( 'CERBER_CF_SYNC_FILE', __FILE__ );
define( 'CERBER_CF_SYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'CERBER_CF_SYNC_URL', plugin_dir_url( __FILE__ ) );

// Load components.
require_once CERBER_CF_SYNC_PATH . 'includes/class-api-client.php';
require_once CERBER_CF_SYNC_PATH . 'includes/class-notifier.php';
require_once CERBER_CF_SYNC_PATH . 'includes/class-sync-handler.php';

if ( is_admin() ) {
	require_once CERBER_CF_SYNC_PATH . 'includes/admin/class-admin-ui.php';
}

/**
 * Initialize the plugin classes on plugins_loaded hook.
 */
function cerber_cf_sync_init() {
	// Initialize core sync listener.
	Cerber_CF_Sync_Handler::get_instance();

	// Initialize Admin settings UI & AJAX actions.
	if ( is_admin() ) {
		Cerber_CF_Sync_Admin_UI::get_instance();
	}
}
add_action( 'plugins_loaded', 'cerber_cf_sync_init' );

/**
 * Add a "Settings" link on the Plugins listing page.
 *
 * @param array  $links Existing plugin action links.
 * @param string $file  The plugin file name.
 * @return array Modified links.
 */
function cerber_cf_sync_plugin_action_links( $links, $file ) {
	if ( basename( $file ) === 'cerber-lockout-cloudflare-sync.php' ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=cerber-cf-sync' ) ),
			esc_html__( 'Settings', 'cerber-lockout-cloudflare-sync' )
		);
		array_unshift( $links, $settings_link );
	}
	return $links;
}
add_filter( 'plugin_action_links', 'cerber_cf_sync_plugin_action_links', 10, 2 );


/**
 * Check if WP Cerber is active during plugin activation and schedule cron events.
 */
function cerber_cf_sync_activate() {
	if ( ! function_exists( 'cerber_get_options' ) && ! defined( 'CERBER_VER' ) ) {
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'Cerber Lockout Cloudflare Sync requires the WP Cerber Security plugin to be active.', 'cerber-lockout-cloudflare-sync' ),
			esc_html__( 'Dependency Required', 'cerber-lockout-cloudflare-sync' ),
			array( 'back_link' => true )
		);
	}

	// Schedule the daily purging cron event.
	if ( ! wp_next_scheduled( 'cerber_cf_sync_daily_purging' ) ) {
		wp_schedule_event( time(), 'daily', 'cerber_cf_sync_daily_purging' );
	}
}
register_activation_hook( __FILE__, 'cerber_cf_sync_activate' );

/**
 * Unschedule cron events on plugin deactivation.
 */
function cerber_cf_sync_deactivate() {
	wp_clear_scheduled_hook( 'cerber_cf_sync_daily_purging' );
	wp_clear_scheduled_hook( 'cerber_cf_sync_instant_purge' );
}
register_deactivation_hook( __FILE__, 'cerber_cf_sync_deactivate' );


/**
 * Show a persistent admin notice if WP Cerber Security is not active.
 */
function cerber_cf_sync_dependency_notice() {
	if ( ! function_exists( 'cerber_get_options' ) && ! defined( 'CERBER_VER' ) ) {
		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'Cerber Lockout Cloudflare Sync:', 'cerber-lockout-cloudflare-sync' ),
			esc_html__( 'WP Cerber Security plugin is not active. This plugin requires WP Cerber to intercept IP lockout events.', 'cerber-lockout-cloudflare-sync' )
		);
	}
}
add_action( 'admin_notices', 'cerber_cf_sync_dependency_notice' );

