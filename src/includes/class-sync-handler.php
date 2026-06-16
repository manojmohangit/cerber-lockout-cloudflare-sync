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
		add_action( 'cerber_cf_sync_daily_purging', array( $this, 'purge_expired_and_overflowing_ips' ) );
		add_action( 'cerber_cf_sync_instant_purge', array( $this, 'purge_expired_and_overflowing_ips' ) );
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

		// Increment the cached count if it exists.
		$list_count = get_transient( 'cerber_cf_sync_list_count' );
		if ( false !== $list_count && ! is_wp_error( $list_count ) ) {
			$new_count = $list_count + 1;
			set_transient( 'cerber_cf_sync_list_count', $new_count, HOUR_IN_SECONDS );

			// If the list size exceeds our configured capacity limit, trigger instant async purging.
			$settings = get_option( 'cerber_cf_sync_settings', array() );
			if ( ! empty( $settings['enable_capacity_purging'] ) ) {
				$max_size = isset( $settings['max_list_size'] ) ? (int) $settings['max_list_size'] : 9500;
				if ( $new_count >= $max_size ) {
					if ( ! wp_next_scheduled( 'cerber_cf_sync_instant_purge' ) ) {
						wp_schedule_single_event( time(), 'cerber_cf_sync_instant_purge' );
					}
				}
			}
		}

		return true;
	}

	/**
	 * Purge expired and overflowing IPs from the Cloudflare list.
	 *
	 * Performs age-based (time expiration) and capacity-based (size overflow) purging.
	 *
	 * @param bool $force If true, bypasses automatic schedule checks and runs enabled purging methods.
	 * @return string|WP_Error Completion message or WP_Error on failure.
	 */
	public function purge_expired_and_overflowing_ips( $force = false ) {
		$settings        = get_option( 'cerber_cf_sync_settings', array() );
		$enable_age      = ! empty( $settings['enable_age_purging'] );
		$enable_capacity = ! empty( $settings['enable_capacity_purging'] );

		if ( ! $enable_age && ! $enable_capacity ) {
			if ( $force ) {
				return new WP_Error(
					'cf_purging_disabled',
					__( 'Purging failed: Neither Age-Based nor Capacity-Based purging is enabled in settings.', 'cerber-lockout-cloudflare-sync' )
				);
			}
			return __( 'Purging skipped: Auto-purging features are disabled in settings.', 'cerber-lockout-cloudflare-sync' );
		}

		$expiration_days = isset( $settings['expiration_days'] ) ? (int) $settings['expiration_days'] : 30;
		$max_size        = isset( $settings['max_list_size'] ) ? (int) $settings['max_list_size'] : 9500;
		$purge_qty       = isset( $settings['purge_quantity'] ) ? (int) $settings['purge_quantity'] : 500;

		$api_client = new Cerber_CF_Sync_API_Client();

		$purged_expired  = 0;
		$purged_overflow = 0;

		// 1. Age-Based Purging
		if ( $enable_age && $expiration_days > 0 ) {
			$cutoff_time = time() - ( $expiration_days * DAY_IN_SECONDS );

			// Limit check to 5 pages max (500 items) per execution to protect execution time
			$cursor        = '';
			$pages_checked = 0;
			$to_delete_ids = array();

			while ( $pages_checked < 5 ) {
				$res = $api_client->get_list_items( $cursor, 100 );
				if ( is_wp_error( $res ) ) {
					return $res;
				}

				if ( empty( $res['items'] ) ) {
					break;
				}

				foreach ( $res['items'] as $item ) {
					if ( isset( $item['id'] ) && isset( $item['created_on'] ) ) {
						$created_time = strtotime( $item['created_on'] );
						if ( $created_time && $created_time < $cutoff_time ) {
							$to_delete_ids[] = $item['id'];
						}
					}
				}

				$cursor = $res['cursor'];
				if ( empty( $cursor ) ) {
					break;
				}
				$pages_checked++;
			}

			if ( ! empty( $to_delete_ids ) ) {
				$chunks = array_chunk( $to_delete_ids, 100 );
				foreach ( $chunks as $chunk ) {
					$del_res = $api_client->delete_list_items( $chunk );
					if ( is_wp_error( $del_res ) ) {
						return $del_res;
					}
					$purged_expired += count( $chunk );
				}
			}
		}

		// 2. Capacity-Based Purging
		if ( $enable_capacity ) {
			$current_count = $api_client->get_list_item_count();
			if ( is_wp_error( $current_count ) ) {
				if ( $purged_expired > 0 ) {
					delete_transient( 'cerber_cf_sync_list_count' );
				}
				return sprintf(
					__( 'Age-based purging completed: removed %d expired IPs. Capacity-based check skipped due to API error.', 'cerber-lockout-cloudflare-sync' ),
					$purged_expired
				);
			}

			if ( $current_count >= $max_size ) {
				// Pull oldest items (which appear first in the Cloudflare list response)
				$cursor        = '';
				$to_delete_ids = array();
				$fetched_qty   = 0;

				while ( $fetched_qty < $purge_qty ) {
					$limit = min( 100, $purge_qty - $fetched_qty );
					$res   = $api_client->get_list_items( $cursor, $limit );
					if ( is_wp_error( $res ) ) {
						return $res;
					}

					if ( empty( $res['items'] ) ) {
						break;
					}

					foreach ( $res['items'] as $item ) {
						if ( isset( $item['id'] ) ) {
							$to_delete_ids[] = $item['id'];
						}
					}

					$fetched_qty += count( $res['items'] );
					$cursor       = $res['cursor'];
					if ( empty( $cursor ) ) {
						break;
					}
				}

				if ( ! empty( $to_delete_ids ) ) {
					$chunks = array_chunk( $to_delete_ids, 100 );
					foreach ( $chunks as $chunk ) {
						$del_res = $api_client->delete_list_items( $chunk );
						if ( is_wp_error( $del_res ) ) {
							return $del_res;
						}
						$purged_overflow += count( $chunk );
					}
				}
			}
		}

		// Update cached count transient
		$current_count = $api_client->get_list_item_count();
		if ( ! is_wp_error( $current_count ) ) {
			set_transient( 'cerber_cf_sync_list_count', $current_count, HOUR_IN_SECONDS );
		} else {
			delete_transient( 'cerber_cf_sync_list_count' );
		}

		return sprintf(
			__( 'Purging completed successfully: %d expired IPs removed, %d overflow IPs removed. Current list size: %d.', 'cerber-lockout-cloudflare-sync' ),
			$purged_expired,
			$purged_overflow,
			is_wp_error( $current_count ) ? 0 : $current_count
		);
	}
}
