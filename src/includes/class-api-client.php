<?php
/**
 * Cloudflare API Client Handler.
 *
 * @package Cerber_Lockout_Cloudflare_Sync
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cerber_CF_Sync_API_Client
 */
class Cerber_CF_Sync_API_Client {

	/**
	 * Endpoint base URL.
	 */
	const API_BASE = 'https://api.cloudflare.com/client/v4';

	/**
	 * Get resolved Cloudflare credentials.
	 *
	 * Resolves settings with priority:
	 * 1. Plugin Options (cerber_cf_sync_settings)
	 * 2. PHP Constants (CLOUDFLARE_API_TOKEN, CLOUDFLARE_API_KEY, CLOUDFLARE_EMAIL, CLOUDFLARE_ACCOUNT_ID, CLOUDFLARE_LIST_ID)
	 * 3. Official Cloudflare plugin options database entries.
	 *
	 * @return array Credentials array containing token, email, auth_type, account_id, list_id.
	 */
	public function get_credentials() {
		$settings = get_option( 'cerber_cf_sync_settings', array() );

		$token      = ! empty( $settings['api_token'] ) ? trim( $settings['api_token'] ) : '';
		$account_id = ! empty( $settings['account_id'] ) ? trim( $settings['account_id'] ) : '';
		$list_id    = ! empty( $settings['list_id'] ) ? trim( $settings['list_id'] ) : '';
		$email      = '';
		$auth_type  = 'token';

		// Constant fallbacks.
		if ( empty( $token ) ) {
			if ( defined( 'CLOUDFLARE_API_TOKEN' ) && CLOUDFLARE_API_TOKEN ) {
				$token     = CLOUDFLARE_API_TOKEN;
				$auth_type = 'token';
			} elseif (defined( 'CLOUDFLARE_API_KEY' ) && CLOUDFLARE_API_KEY ) {
				$token     = CLOUDFLARE_API_KEY;
				$auth_type = 'key';
				if ( defined( 'CLOUDFLARE_EMAIL' ) && CLOUDFLARE_EMAIL ) {
					$email = CLOUDFLARE_EMAIL;
				}
			}
		}

		if ( empty( $account_id ) && defined( 'CLOUDFLARE_ACCOUNT_ID' ) && CLOUDFLARE_ACCOUNT_ID ) {
			$account_id = CLOUDFLARE_ACCOUNT_ID;
		}

		if ( empty( $list_id ) && defined( 'CLOUDFLARE_LIST_ID' ) && CLOUDFLARE_LIST_ID ) {
			$list_id = CLOUDFLARE_LIST_ID;
		}

		// Fallback to official Cloudflare plugin options.
		if ( empty( $token ) ) {
			$cf_opts = get_option( 'cloudflare' );
			if ( is_array( $cf_opts ) ) {
				if ( ! empty( $cf_opts['api_key'] ) ) {
					$token     = $cf_opts['api_key'];
					$auth_type = 'key';
					if ( ! empty( $cf_opts['email'] ) ) {
						$email = $cf_opts['email'];
					}
				}
			}

			// Try standalone token option if still empty.
			if ( empty( $token ) ) {
				$cf_token = get_option( 'cloudflare_api_token' );
				if ( $cf_token ) {
					$token     = $cf_token;
					$auth_type = 'token';
				}
			}
		}

		return array(
			'token'      => $token,
			'email'      => $email,
			'auth_type'  => $auth_type,
			'account_id' => $account_id,
			'list_id'    => $list_id,
		);
	}

	/**
	 * Build HTTP request headers.
	 *
	 * @param array $creds Resolved credentials.
	 * @return array HTTP headers.
	 */
	private function build_headers( $creds ) {
		$headers = array(
			'Content-Type' => 'application/json',
		);

		if ( 'key' === $creds['auth_type'] ) {
			$headers['X-Auth-Key']   = $creds['token'];
			$headers['X-Auth-Email'] = $creds['email'];
		} else {
			$headers['Authorization'] = 'Bearer ' . $creds['token'];
		}

		return $headers;
	}

	/**
	 * Validate that resolved credentials are present and correctly formatted.
	 *
	 * Cloudflare Account IDs and List IDs are 32-character hexadecimal strings.
	 *
	 * @param array $creds Resolved credentials.
	 * @return true|WP_Error True if valid, WP_Error if not.
	 */
	private function validate_credentials( $creds ) {
		if ( empty( $creds['token'] ) ) {
			return new WP_Error(
				'cf_missing_credentials',
				__( 'Cloudflare API Token is not configured.', 'cerber-lockout-cloudflare-sync' )
			);
		}

		if ( empty( $creds['account_id'] ) || ! preg_match( '/^[a-f0-9]{32}$/i', $creds['account_id'] ) ) {
			return new WP_Error(
				'cf_invalid_account_id',
				__( 'Cloudflare Account ID is missing or invalid. Expected a 32-character hexadecimal string.', 'cerber-lockout-cloudflare-sync' )
			);
		}

		if ( empty( $creds['list_id'] ) || ! preg_match( '/^[a-f0-9]{32}$/i', $creds['list_id'] ) ) {
			return new WP_Error(
				'cf_invalid_list_id',
				__( 'Cloudflare List ID is missing or invalid. Expected a 32-character hexadecimal string.', 'cerber-lockout-cloudflare-sync' )
			);
		}

		return true;
	}

	/**
	 * Perform remote request and inspect for authorization errors.
	 *
	 * @param string $url Request URL.
	 * @param array  $args Request arguments.
	 * @return array|WP_Error Response or WP_Error on failure.
	 */
	private function send_request( $url, $args ) {
		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 401 === $status_code || 403 === $status_code ) {
			return new WP_Error(
				'cf_api_permission_denied',
				__( 'Cloudflare API authentication failed. Verify API token is valid and has "Account Filter Lists: Edit" permissions enabled.', 'cerber-lockout-cloudflare-sync' ),
				array( 'status' => $status_code )
			);
		}

		if ( $status_code < 200 || $status_code >= 300 ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			$msg  = ! empty( $data['errors'][0]['message'] ) ? $data['errors'][0]['message'] : __( 'Unknown API Error', 'cerber-lockout-cloudflare-sync' );
			return new WP_Error(
				'cf_api_error',
				sprintf( __( 'Cloudflare API Error (Status %d): %s', 'cerber-lockout-cloudflare-sync' ), $status_code, $msg ),
				array( 'status' => $status_code )
			);
		}

		return $response;
	}

	/**
	 * Test the connection credentials.
	 *
	 * Checks if the list can be retrieved successfully.
	 *
	 * @return true|WP_Error True if success, WP_Error if failure.
	 */
	public function test_connection() {
		$creds = $this->get_credentials();

		$valid = $this->validate_credentials( $creds );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$url = sprintf(
			'%s/accounts/%s/rules/lists/%s',
			self::API_BASE,
			urlencode( $creds['account_id'] ),
			urlencode( $creds['list_id'] )
		);

		$args = array(
			'method'  => 'GET',
			'headers' => $this->build_headers( $creds ),
			'timeout' => 15,
		);

		$response = $this->send_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Check if an IP address exists in the Cloudflare List.
	 *
	 * @param string $ip IPv4 or IPv6 address.
	 * @return bool|WP_Error True if present, false if not, WP_Error if request fails.
	 */
	public function is_ip_in_list( $ip ) {
		$creds = $this->get_credentials();

		$valid = $this->validate_credentials( $creds );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$url = sprintf(
			'%s/accounts/%s/rules/lists/%s/items?search=%s',
			self::API_BASE,
			urlencode( $creds['account_id'] ),
			urlencode( $creds['list_id'] ),
			urlencode( $ip )
		);

		$args = array(
			'method'  => 'GET',
			'headers' => $this->build_headers( $creds ),
			'timeout' => 15,
		);

		$response = $this->send_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! empty( $data['result'] ) && is_array( $data['result'] ) ) {
			foreach ( $data['result'] as $item ) {
				if ( isset( $item['ip'] ) && $item['ip'] === $ip ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Append an IP address to the Cloudflare List.
	 *
	 * @param string $ip      IPv4 or IPv6 address.
	 * @param string $comment Description comment for the IP item.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function add_ip_to_list( $ip, $comment ) {
		$creds = $this->get_credentials();

		$valid = $this->validate_credentials( $creds );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$url = sprintf(
			'%s/accounts/%s/rules/lists/%s/items',
			self::API_BASE,
			urlencode( $creds['account_id'] ),
			urlencode( $creds['list_id'] )
		);

		$payload = array(
			array(
				'ip'      => $ip,
				'comment' => substr( $comment, 0, 100 ), // Max 100 chars for list comments
			),
		);

		$args = array(
			'method'  => 'POST',
			'headers' => $this->build_headers( $creds ),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 15,
		);

		$response = $this->send_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}
}
