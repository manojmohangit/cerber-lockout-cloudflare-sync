<?php
/**
 * Email Notifications and Alerts Manager.
 *
 * @package Cerber_Lockout_Cloudflare_Sync
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cerber_CF_Sync_Notifier
 */
class Cerber_CF_Sync_Notifier {

	/**
	 * Send an email notification when an API error occurs.
	 *
	 * Rate-limits email alerts to once per hour using the `cf_sync_err_email_sent` transient.
	 *
	 * @param WP_Error $wp_error The error object.
	 */
	public static function send_error_notification( $wp_error ) {
		if ( ! is_wp_error( $wp_error ) ) {
			return;
		}

		$transient_key = 'cf_sync_err_email_sent';
		if ( get_transient( $transient_key ) ) {
			return; // Rate limit email alerts to 1 per hour.
		}

		$settings = get_option( 'cerber_cf_sync_settings', array() );
		$to       = ! empty( $settings['email'] ) ? sanitize_email( $settings['email'] ) : get_option( 'admin_email' );

		if ( empty( $to ) || ! is_email( $to ) ) {
			return;
		}

		$error_code = $wp_error->get_error_code();
		$error_msg  = $wp_error->get_error_message();

		if ( 'cf_api_permission_denied' === $error_code ) {
			$subject = __( 'Security Alert: Cloudflare API Authorization Failed', 'cerber-lockout-cloudflare-sync' );
			$message = sprintf(
				__( "Hello,\n\nWe attempted to synchronize a locked IP address to Cloudflare, but the API returned a Permission Denied error.\n\nPlease verify that your Cloudflare API Token has the required permissions enabled on your account:\n- Scope: Account\n- Permission: Account Filter Lists > Edit\n\nError details: %s\n\nThis notification is rate-limited and will not be sent again for 1 hour.\n\nRegards,\nCerber Lockout Cloudflare Sync", 'cerber-lockout-cloudflare-sync' ),
				$error_msg
			);
		} else {
			$subject = __( 'Alert: Cloudflare API Sync Error', 'cerber-lockout-cloudflare-sync' );
			$message = sprintf(
				__( "Hello,\n\nWe encountered an error while synchronizing a locked IP address to Cloudflare:\n\n%s\n\nThis notification is rate-limited and will not be sent again for 1 hour.\n\nRegards,\nCerber Lockout Cloudflare Sync", 'cerber-lockout-cloudflare-sync' ),
				$error_msg
			);
		}

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		wp_mail( $to, $subject, $message, $headers );

		// Set transient to rate-limit further error notifications for 1 hour.
		set_transient( $transient_key, '1', HOUR_IN_SECONDS );
	}

	/**
	 * Send an email notification on successful synchronization.
	 *
	 * Only triggers if enabled in the settings.
	 *
	 * @param string $ip     The blocked IP address.
	 * @param string $reason The lockout reason.
	 */
	public static function send_success_notification( $ip, $reason ) {
		$settings = get_option( 'cerber_cf_sync_settings', array() );

		// Check if success emails are explicitly enabled.
		if ( empty( $settings['enable_success_emails'] ) || '1' !== $settings['enable_success_emails'] ) {
			return;
		}

		$to = ! empty( $settings['email'] ) ? sanitize_email( $settings['email'] ) : get_option( 'admin_email' );

		if ( empty( $to ) || ! is_email( $to ) ) {
			return;
		}

		$subject = sprintf( __( 'IP Blocked: %s Synced to Cloudflare', 'cerber-lockout-cloudflare-sync' ), $ip );
		$message = sprintf(
			__( "Hello,\n\nAn IP address lockout event has been successfully synchronized to your Cloudflare Account IP List:\n\nIP Address: %s\nReason: %s\nTime: %s\n\nRegards,\nCerber Lockout Cloudflare Sync", 'cerber-lockout-cloudflare-sync' ),
			$ip,
			$reason,
			current_time( 'mysql' )
		);

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		wp_mail( $to, $subject, $message, $headers );
	}
}
