<?php
/**
 * WordPress Admin Settings Panel and AJAX Controller.
 *
 * @package Cerber_Lockout_Cloudflare_Sync
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cerber_CF_Sync_Admin_UI
 */
class Cerber_CF_Sync_Admin_UI {

	/**
	 * Singleton instance.
	 *
	 * @var Cerber_CF_Sync_Admin_UI|null
	 */
	private static $instance = null;

	/**
	 * Settings page hook suffix.
	 *
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Get the singleton instance.
	 *
	 * @return Cerber_CF_Sync_Admin_UI
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
		// Register settings page and section/fields.
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_plugin_settings' ) );
		add_action( 'admin_notices', array( $this, 'capacity_warning_notice' ) );

		// Register AJAX actions.
		add_action( 'wp_ajax_cf_sync_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_cf_sync_manual_ip', array( $this, 'ajax_manual_ip' ) );
		add_action( 'wp_ajax_cf_sync_clear_cache', array( $this, 'ajax_clear_cache' ) );
		add_action( 'wp_ajax_cf_sync_refresh_count', array( $this, 'ajax_refresh_count' ) );
		add_action( 'wp_ajax_cf_sync_run_purge', array( $this, 'ajax_run_purge' ) );
	}

	/**
	 * Register settings menu page under Settings.
	 */
	public function add_settings_page() {
		$this->page_hook = add_options_page(
			__( 'Cerber Lockout Cloudflare Sync Settings', 'cerber-lockout-cloudflare-sync' ),
			__( 'Cerber CF Sync', 'cerber-lockout-cloudflare-sync' ),
			'manage_options',
			'cerber-cf-sync',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings group, sections, and fields.
	 */
	public function register_plugin_settings() {
		register_setting(
			'cerber_cf_sync_group',
			'cerber_cf_sync_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);

		// 1. API Credentials Section
		add_settings_section(
			'cerber_cf_sync_section_api',
			__( 'Cloudflare API Settings', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_section_description' ),
			'cerber-cf-sync-api'
		);

		add_settings_field(
			'account_id',
			__( 'Cloudflare Account ID', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_account_id_field' ),
			'cerber-cf-sync-api',
			'cerber_cf_sync_section_api'
		);

		add_settings_field(
			'list_id',
			__( 'Cloudflare List ID', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_list_id_field' ),
			'cerber-cf-sync-api',
			'cerber_cf_sync_section_api'
		);

		add_settings_field(
			'api_token',
			__( 'Cloudflare API Token', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_api_token_field' ),
			'cerber-cf-sync-api',
			'cerber_cf_sync_section_api'
		);

		// 2. Alerts & Notifications Section
		add_settings_section(
			'cerber_cf_sync_section_alerts',
			__( 'Alerts & Notifications Settings', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_section_description' ),
			'cerber-cf-sync-alerts'
		);

		add_settings_field(
			'email',
			__( 'Notification Email', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_email_field' ),
			'cerber-cf-sync-alerts',
			'cerber_cf_sync_section_alerts'
		);

		add_settings_field(
			'enable_success_emails',
			__( 'Enable Success Notifications', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_enable_success_emails_field' ),
			'cerber-cf-sync-alerts',
			'cerber_cf_sync_section_alerts'
		);

		add_settings_field(
			'warning_threshold',
			__( 'Capacity Warning Threshold', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_warning_threshold_field' ),
			'cerber-cf-sync-alerts',
			'cerber_cf_sync_section_alerts'
		);

		// 3. Purging Configuration Section
		add_settings_section(
			'cerber_cf_sync_section_purging',
			__( 'Auto-Purging Settings', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_section_description' ),
			'cerber-cf-sync-purging'
		);

		add_settings_field(
			'enable_age_purging',
			__( 'Enable Age-Based Purging', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_enable_age_purging_field' ),
			'cerber-cf-sync-purging',
			'cerber_cf_sync_section_purging'
		);

		add_settings_field(
			'expiration_days',
			__( 'IP Expiration Period (Days)', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_expiration_days_field' ),
			'cerber-cf-sync-purging',
			'cerber_cf_sync_section_purging'
		);

		add_settings_field(
			'enable_capacity_purging',
			__( 'Enable Capacity-Based Purging', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_enable_capacity_purging_field' ),
			'cerber-cf-sync-purging',
			'cerber_cf_sync_section_purging'
		);

		add_settings_field(
			'max_list_size',
			__( 'Maximum IP List Size', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_max_list_size_field' ),
			'cerber-cf-sync-purging',
			'cerber_cf_sync_section_purging'
		);

		add_settings_field(
			'purge_quantity',
			__( 'Purge Quantity', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_purge_quantity_field' ),
			'cerber-cf-sync-purging',
			'cerber_cf_sync_section_purging'
		);
	}

	/**
	 * Sanitize form input settings.
	 *
	 * @param array $input Raw setting values.
	 * @return array Sanitized values.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		if ( isset( $input['account_id'] ) ) {
			$sanitized['account_id'] = sanitize_text_field( trim( $input['account_id'] ) );
		}

		if ( isset( $input['list_id'] ) ) {
			$sanitized['list_id'] = sanitize_text_field( trim( $input['list_id'] ) );
		}

		if ( isset( $input['api_token'] ) ) {
			$sanitized['api_token'] = sanitize_text_field( trim( $input['api_token'] ) );
		}

		if ( isset( $input['email'] ) ) {
			$sanitized['email'] = sanitize_email( trim( $input['email'] ) );
		}

		$sanitized['enable_success_emails'] = ! empty( $input['enable_success_emails'] ) ? '1' : '0';

		if ( isset( $input['warning_threshold'] ) ) {
			$val = (int) $input['warning_threshold'];
			$sanitized['warning_threshold'] = ( $val >= 1000 && $val <= 10000 ) ? $val : 9000;
		} else {
			$sanitized['warning_threshold'] = 9000;
		}

		if ( isset( $input['expiration_days'] ) ) {
			$sanitized['expiration_days'] = intval( $input['expiration_days'] );
		} else {
			$sanitized['expiration_days'] = 30;
		}

		if ( isset( $input['max_list_size'] ) ) {
			$val = intval( $input['max_list_size'] );
			$sanitized['max_list_size'] = ( $val >= 1000 && $val <= 10000 ) ? $val : 9500;
		} else {
			$sanitized['max_list_size'] = 9500;
		}

		if ( isset( $input['purge_quantity'] ) ) {
			$val = intval( $input['purge_quantity'] );
			$sanitized['purge_quantity'] = in_array( $val, array( 100, 200, 500, 1000 ) ) ? $val : 500;
		} else {
			$sanitized['purge_quantity'] = 500;
		}

		$sanitized['enable_age_purging']      = ! empty( $input['enable_age_purging'] ) ? '1' : '0';
		$sanitized['enable_capacity_purging'] = ! empty( $input['enable_capacity_purging'] ) ? '1' : '0';

		return $sanitized;
	}

	/**
	 * Render Section Header.
	 */
	public function render_section_description() {
		// No description text inside tab headers to keep tab views clean.
	}

	/**
	 * Render Account ID field.
	 */
	public function render_account_id_field() {
		$settings = get_option( 'cerber_cf_sync_settings', array() );
		$val      = isset( $settings['account_id'] ) ? $settings['account_id'] : '';
		
		$placeholder = '';
		if ( empty( $val ) && defined( 'CLOUDFLARE_ACCOUNT_ID' ) ) {
			$placeholder = __( 'Defined by constant', 'cerber-lockout-cloudflare-sync' );
		}

		echo '<input type="password" class="regular-text" name="cerber_cf_sync_settings[account_id]" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( $placeholder ) . '" autocomplete="off" />';
		echo '<p class="description">' . esc_html__( 'Your Cloudflare Account ID (located on the overview page of your domain panel).', 'cerber-lockout-cloudflare-sync' ) . '</p>';
	}

	/**
	 * Render List ID field.
	 */
	public function render_list_id_field() {
		$settings = get_option( 'cerber_cf_sync_settings', array() );
		$val      = isset( $settings['list_id'] ) ? $settings['list_id'] : '';

		$placeholder = '';
		if ( empty( $val ) && defined( 'CLOUDFLARE_LIST_ID' ) ) {
			$placeholder = __( 'Defined by constant', 'cerber-lockout-cloudflare-sync' );
		}

		echo '<input type="text" class="regular-text" name="cerber_cf_sync_settings[list_id]" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( $placeholder ) . '" autocomplete="off" />';
		echo '<p class="description">';
		echo wp_kses(
			sprintf(
				__( 'The ID of the custom IP List created in Cloudflare (Configurations > Lists). For details, see <a href="%s" target="_blank">Cloudflare Lists Documentation ↗</a>.', 'cerber-lockout-cloudflare-sync' ),
				'https://developers.cloudflare.com/waf/tools/lists/'
			),
			array( 'a' => array( 'href' => true, 'target' => true ) )
		);
		echo '</p>';
	}

	/**
	 * Render API Token field.
	 */
	public function render_api_token_field() {
		$settings = get_option( 'cerber_cf_sync_settings', array() );
		$val      = isset( $settings['api_token'] ) ? $settings['api_token'] : '';

		$api_client = new Cerber_CF_Sync_API_Client();
		$resolved   = $api_client->get_credentials();
		
		$placeholder = '';
		$status_note = '';

		if ( empty( $val ) ) {
			if ( ! empty( $resolved['token'] ) ) {
				$placeholder = __( 'Auto-detected fallback token active', 'cerber-lockout-cloudflare-sync' );
				$status_note = '<span style="color: hsl(140, 50%, 40%); font-weight: 500;">✓ ' . esc_html__( 'Credentials resolved from constants or Cloudflare official plugin.', 'cerber-lockout-cloudflare-sync' ) . '</span>';
			} else {
				$placeholder = __( 'Enter API Token', 'cerber-lockout-cloudflare-sync' );
			}
		}

		echo '<div style="display: flex; gap: 8px; align-items: center; max-width: 500px; flex-wrap: wrap;">';
		echo '<input type="password" class="regular-text" name="cerber_cf_sync_settings[api_token]" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( $placeholder ) . '" autocomplete="off" style="flex: 1;" />';
		echo '<a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" class="button button-secondary" style="height: 30px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">' . esc_html__( 'Create Token ↗', 'cerber-lockout-cloudflare-sync' ) . '</a>';
		echo '</div>';

		if ( $status_note ) {
			echo '<p class="description" style="margin-top: 4px;">' . wp_kses( $status_note, array( 'span' => array( 'style' => true ) ) ) . '</p>';
		}
		echo '<p class="description">';
		echo wp_kses(
			sprintf(
				__( 'Required permission: Account > Account Filter Lists > Edit. For instructions, see <a href="%s" target="_blank">Cloudflare API Token Documentation ↗</a>.', 'cerber-lockout-cloudflare-sync' ),
				'https://developers.cloudflare.com/fundamentals/api/get-started/create-token/'
			),
			array( 'a' => array( 'href' => true, 'target' => true ) )
		);
		echo '</p>';
	}

	/**
	 * Render Email Field.
	 */
	public function render_email_field() {
		$settings = get_option( 'cerber_cf_sync_settings', array() );
		$val      = isset( $settings['email'] ) ? $settings['email'] : '';
		$default  = get_option( 'admin_email' );

		echo '<input type="email" class="regular-text" name="cerber_cf_sync_settings[email]" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( $default ) . '" />';
		echo '<p class="description">' . esc_html__( 'The email address that receives sync error reports and notices. Defaults to administrator email.', 'cerber-lockout-cloudflare-sync' ) . '</p>';
	}

	/**
	 * Render Checkbox for success notifications.
	 */
	public function render_enable_success_emails_field() {
		$settings = get_option( 'cerber_cf_sync_settings', array() );
		$checked  = ! empty( $settings['enable_success_emails'] ) ? '1' : '0';

		echo '<label>';
		echo '<input type="checkbox" name="cerber_cf_sync_settings[enable_success_emails]" value="1" ' . checked( '1', $checked, false ) . ' />';
		echo ' ' . esc_html__( 'Send confirmation email alerts immediately when an IP lockout successfully syncs to Cloudflare.', 'cerber-lockout-cloudflare-sync' );
		echo '</label>';
	}

	/**
	 * Render Warning Threshold Field.
	 */
	public function render_warning_threshold_field() {
		$settings = get_option( 'cerber_cf_sync_settings', array() );
		$val      = isset( $settings['warning_threshold'] ) ? (int) $settings['warning_threshold'] : 9000;

		echo '<input type="number" class="small-text" name="cerber_cf_sync_settings[warning_threshold]" value="' . esc_attr( $val ) . '" min="1000" max="10000" step="100" />';
		echo '<p class="description">' . esc_html__( 'Trigger a warning notice when the Cloudflare list item count reaches or exceeds this value. Must be between 1,000 and 10,000 (default is 9,000).', 'cerber-lockout-cloudflare-sync' ) . '</p>';
	}

	/**
	 * Render Enable Age-Based Purging checkbox field.
	 */
	public function render_enable_age_purging_field() {
		$settings = get_option( 'cerber_cf_sync_settings', array() );
		$checked  = ! empty( $settings['enable_age_purging'] ) ? '1' : '0';

		echo '<label>';
		echo '<input type="checkbox" name="cerber_cf_sync_settings[enable_age_purging]" value="1" ' . checked( '1', $checked, false ) . ' />';
		echo ' ' . esc_html__( 'Enable daily automatic removal of expired IP addresses based on the expiration period below.', 'cerber-lockout-cloudflare-sync' );
		echo '</label>';
	}

	/**
	 * Render Enable Capacity-Based Purging checkbox field.
	 */
	public function render_enable_capacity_purging_field() {
		$settings = get_option( 'cerber_cf_sync_settings', array() );
		$checked  = ! empty( $settings['enable_capacity_purging'] ) ? '1' : '0';

		echo '<label>';
		echo '<input type="checkbox" name="cerber_cf_sync_settings[enable_capacity_purging]" value="1" ' . checked( '1', $checked, false ) . ' />';
		echo ' ' . esc_html__( 'Enable background capacity-based purging when the list approaches the maximum size limit.', 'cerber-lockout-cloudflare-sync' );
		echo '</label>';
	}

	/**
	 * Render Expiration Days field.
	 */
	public function render_expiration_days_field() {
		$settings = get_option( 'cerber_cf_sync_settings', array() );
		$val      = isset( $settings['expiration_days'] ) ? (int) $settings['expiration_days'] : 30;

		$options = array(
			0  => __( 'Never (Disable Purging)', 'cerber-lockout-cloudflare-sync' ),
			1  => __( '1 Day', 'cerber-lockout-cloudflare-sync' ),
			3  => __( '3 Days', 'cerber-lockout-cloudflare-sync' ),
			7  => __( '7 Days', 'cerber-lockout-cloudflare-sync' ),
			14 => __( '14 Days', 'cerber-lockout-cloudflare-sync' ),
			30 => __( '30 Days', 'cerber-lockout-cloudflare-sync' ),
			60 => __( '60 Days', 'cerber-lockout-cloudflare-sync' ),
			90 => __( '90 Days', 'cerber-lockout-cloudflare-sync' ),
		);

		echo '<select name="cerber_cf_sync_settings[expiration_days]">';
		foreach ( $options as $days => $label ) {
			printf(
				'<option value="%d" %s>%s</option>',
				intval( $days ),
				selected( $val, $days, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Automatically delete IP blocks older than the selected period during daily cron job runs.', 'cerber-lockout-cloudflare-sync' ) . '</p>';
	}

	/**
	 * Render Max List Size field.
	 */
	public function render_max_list_size_field() {
		$settings = get_option( 'cerber_cf_sync_settings', array() );
		$val      = isset( $settings['max_list_size'] ) ? (int) $settings['max_list_size'] : 9500;

		echo '<input type="number" class="small-text" name="cerber_cf_sync_settings[max_list_size]" value="' . esc_attr( $val ) . '" min="1000" max="10000" step="100" />';
		echo '<p class="description">' . esc_html__( 'Automatically trigger capacity purging if the Cloudflare list size exceeds this limit (Maximum allowed is 10,000).', 'cerber-lockout-cloudflare-sync' ) . '</p>';
	}

	/**
	 * Render Purge Quantity field.
	 */
	public function render_purge_quantity_field() {
		$settings = get_option( 'cerber_cf_sync_settings', array() );
		$val      = isset( $settings['purge_quantity'] ) ? (int) $settings['purge_quantity'] : 500;

		$options = array( 100, 200, 500, 1000 );

		echo '<select name="cerber_cf_sync_settings[purge_quantity]">';
		foreach ( $options as $qty ) {
			printf(
				'<option value="%d" %s>%d</option>',
				intval( $qty ),
				selected( $val, $qty, false ),
				intval( $qty )
			);
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Number of oldest IPs to remove when capacity purging is triggered.', 'cerber-lockout-cloudflare-sync' ) . '</p>';
	}

	/**
	 * Show an admin notice if the Cloudflare List is nearing capacity.
	 */
	public function capacity_warning_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen && 'settings_page_cerber-cf-sync' === $screen->id ) {
			return;
		}

		$list_count = get_transient( 'cerber_cf_sync_list_count' );

		if ( false === $list_count || is_wp_error( $list_count ) ) {
			return;
		}

		$settings  = get_option( 'cerber_cf_sync_settings', array() );
		$threshold = isset( $settings['warning_threshold'] ) ? (int) $settings['warning_threshold'] : 9000;

		if ( $list_count >= $threshold ) {
			$class = $list_count >= 10000 ? 'notice-error' : 'notice-warning';
			printf(
				'<div class="notice %s"><p><strong>%s</strong> %s</p></div>',
				esc_attr( $class ),
				esc_html__( 'Cerber Lockout Cloudflare Sync Capacity Alert:', 'cerber-lockout-cloudflare-sync' ),
				sprintf(
					esc_html__( 'The Cloudflare Account IP List is nearing capacity. Current size: %s / 10,000 items. Please log in to your Cloudflare dashboard and purge old items to ensure uninterrupted lockout synchronization.', 'cerber-lockout-cloudflare-sync' ),
					esc_html( number_format_i18n( $list_count ) )
				)
			);
		}
	}

	/**
	 * Render Settings Page HTML.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'cerber-lockout-cloudflare-sync' ) );
		}

		$api_client = new Cerber_CF_Sync_API_Client();
		$list_count = get_transient( 'cerber_cf_sync_list_count' );

		if ( false === $list_count ) {
			$list_count = $api_client->get_list_item_count();
			if ( ! is_wp_error( $list_count ) ) {
				set_transient( 'cerber_cf_sync_list_count', $list_count, HOUR_IN_SECONDS );
			}
		}

		// Load modular tabbed settings view.
		include CERBER_CF_SYNC_PATH . 'includes/admin/views/settings-page.php';
	}

	/**
	 * AJAX Handler: Test API connection credentials.
	 */
	public function ajax_test_connection() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security error: Insufficient permissions.', 'cerber-lockout-cloudflare-sync' ) ), 403 );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cerber_cf_sync_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed. Invalid nonce.', 'cerber-lockout-cloudflare-sync' ) ), 400 );
		}

		$api_client = new Cerber_CF_Sync_API_Client();
		$test       = $api_client->test_connection();

		if ( is_wp_error( $test ) ) {
			wp_send_json_error( array( 'message' => $test->get_error_message() ) );
		}

		$count = $api_client->get_list_item_count();
		if ( ! is_wp_error( $count ) ) {
			set_transient( 'cerber_cf_sync_list_count', $count, HOUR_IN_SECONDS );
			wp_send_json_success( array(
				'message' => sprintf(
					__( 'Success! Successfully established connection with Cloudflare Account List API. The list currently contains %d items.', 'cerber-lockout-cloudflare-sync' ),
					$count
				)
			) );
		}

		wp_send_json_success( array( 'message' => __( 'Success! Successfully established connection with Cloudflare Account List API.', 'cerber-lockout-cloudflare-sync' ) ) );
	}

	/**
	 * AJAX Handler: Manually trigger an IP Block synchronization.
	 */
	public function ajax_manual_ip() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security error: Insufficient permissions.', 'cerber-lockout-cloudflare-sync' ) ), 403 );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cerber_cf_sync_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed. Invalid nonce.', 'cerber-lockout-cloudflare-sync' ) ), 400 );
		}

		if ( empty( $_POST['ip'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Error: IP address parameter is missing.', 'cerber-lockout-cloudflare-sync' ) ) );
		}

		$ip = sanitize_text_field( wp_unslash( $_POST['ip'] ) );

		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			wp_send_json_error( array( 'message' => __( 'Error: Invalid IP address format.', 'cerber-lockout-cloudflare-sync' ) ) );
		}

		$handler = Cerber_CF_Sync_Handler::get_instance();
		$result  = $handler->sync_ip( $ip, __( 'Manual Block via Control Panel', 'cerber-lockout-cloudflare-sync' ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => sprintf( __( 'IP %s has been successfully synchronized and blocked in Cloudflare.', 'cerber-lockout-cloudflare-sync' ), $ip ) ) );
	}

	/**
	 * AJAX Handler: Flush transient cache entries for blocked IPs.
	 */
	public function ajax_clear_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security error: Insufficient permissions.', 'cerber-lockout-cloudflare-sync' ) ), 403 );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cerber_cf_sync_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed. Invalid nonce.', 'cerber-lockout-cloudflare-sync' ) ), 400 );
		}

		global $wpdb;

		$deleted_count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_cf_blocked_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_cf_blocked_' ) . '%'
			)
		);

		delete_transient( 'cf_sync_err_email_sent' );
		delete_transient( 'cerber_cf_sync_list_count' );

		wp_send_json_success( array( 'message' => sprintf( __( 'Success! Local transient cache successfully cleared. Removed %d cache entries.', 'cerber-lockout-cloudflare-sync' ), intval( $deleted_count / 2 ) ) ) );
	}

	/**
	 * AJAX Handler: Refresh and return the current list item count.
	 */
	public function ajax_refresh_count() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security error: Insufficient permissions.', 'cerber-lockout-cloudflare-sync' ) ), 403 );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cerber_cf_sync_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed. Invalid nonce.', 'cerber-lockout-cloudflare-sync' ) ), 400 );
		}

		$api_client = new Cerber_CF_Sync_API_Client();
		$count      = $api_client->get_list_item_count();

		if ( is_wp_error( $count ) ) {
			wp_send_json_error( array( 'message' => $count->get_error_message() ) );
		}

		set_transient( 'cerber_cf_sync_list_count', $count, HOUR_IN_SECONDS );

		$settings  = get_option( 'cerber_cf_sync_settings', array() );
		$threshold = isset( $settings['warning_threshold'] ) ? (int) $settings['warning_threshold'] : 9000;
		$percent   = round( ( $count / 10000 ) * 100, 1 );

		wp_send_json_success( array(
			'count'   => $count,
			'percent' => $percent,
			'message' => sprintf(
				__( 'Success! Current size: %s / 10,000 items (%s%% capacity).', 'cerber-lockout-cloudflare-sync' ),
				number_format_i18n( $count ),
				$percent
			)
		) );
	}

	/**
	 * AJAX Handler: Manually trigger IP list purging.
	 */
	public function ajax_run_purge() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security error: Insufficient permissions.', 'cerber-lockout-cloudflare-sync' ) ), 403 );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cerber_cf_sync_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed. Invalid nonce.', 'cerber-lockout-cloudflare-sync' ) ), 400 );
		}

		$handler = Cerber_CF_Sync_Handler::get_instance();
		$result  = $handler->purge_expired_and_overflowing_ips( true );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => $result ) );
	}
}
