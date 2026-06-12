<?php
/**
 * Cleanup database records when the plugin is uninstalled.
 *
 * @package Cerber_Lockout_Cloudflare_Sync
 */

// If uninstall is not called by WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options.
delete_option( 'cerber_cf_sync_settings' );

// Delete transients from options table.
global $wpdb;
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_cf_blocked_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_cf_blocked_' ) . '%'
	)
);

// Delete error email rate limit transient.
delete_transient( 'cf_sync_err_email_sent' );
