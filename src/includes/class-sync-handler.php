<?php
/**
 * WP Cerber IP Lockout Action Hook Listener.
 *
 * @package Cerber_Lockout_Cloudflare_Sync
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cerber_CF_Sync_Handler
 */
class Cerber_CF_Sync_Handler {

	/**
	 * Singleton instance.
	 *
	 * @var Cerber_CF_Sync_Handler|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Cerber_CF_Sync_Handler
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'cerber_ip_locked', array( $this, 'handle_cerber_lockout' ), 10, 3 );
	}

	/**
	 * Hook callback for WP Cerber lockout events.
	 *
	 * @param string $ip      Locked IP.
	 * @param string $reason  Lockout reason.
	 * @param array  $details Lockout details.
	 */
	public function handle_cerber_lockout( $ip, $reason = '', $details = array() ) {
		$reason_str = ! empty( $reason ) ? $reason : __( 'Automatic lockout', 'cerber-lockout-cloudflare-sync' );
		
		// Run sync and check for failures.
		$result = $this->sync_ip( $ip, $reason_str );

		if ( is_wp_error( $result ) ) {
			// Notify Administrator of API Client / Authorization failure.
			Cerber_CF_Sync_Notifier::send_error_notification( $result );
		} else {
			// Notify Administrator of successful block sync (if configured).
			Cerber_CF_Sync_Notifier::send_success_notification( $ip, $reason_str );
		}
	}

	/**
	 * Synchronize a single IP to Cloudflare (used by hooks and manual triggers).
	 *
	 * @param string $ip     IP address.
	 * @param string $reason Lockout reason.
	 * @return true|WP_Error True if already in list or successfully added, WP_Error on failure.
	 */
	public function sync_ip( $ip, $reason ) {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return new WP_Error( 'invalid_ip', __( 'Invalid IP address format.', 'cerber-lockout-cloudflare-sync' ) );
		}

		$transient_key = 'cf_blocked_' . md5( $ip );
		if ( get_transient( $transient_key ) ) {
			return true;
		}

		$api_client = new Cerber_CF_Sync_API_Client();
		$is_blocked = $api_client->is_ip_in_list( $ip );

		if ( is_wp_error( $is_blocked ) ) {
			return $is_blocked;
		}

		if ( $is_blocked ) {
			set_transient( $transient_key, '1', DAY_IN_SECONDS );
			return true;
		}

		$comment = sprintf( 'Cerber: %s', $reason );
		$result  = $api_client->add_ip_to_list( $ip, $comment );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		set_transient( $transient_key, '1', DAY_IN_SECONDS );
		return true;
	}
}
