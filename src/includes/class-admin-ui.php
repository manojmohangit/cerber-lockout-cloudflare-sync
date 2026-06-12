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

		// Register AJAX actions.
		add_action( 'wp_ajax_cf_sync_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_cf_sync_manual_ip', array( $this, 'ajax_manual_ip' ) );
		add_action( 'wp_ajax_cf_sync_clear_cache', array( $this, 'ajax_clear_cache' ) );
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

		// Enqueue custom styling and scripts only on this specific settings page.
		if ( $this->page_hook ) {
			add_action( "admin_print_styles-{$this->page_hook}", array( $this, 'print_custom_styles' ) );
			add_action( "admin_footer-{$this->page_hook}", array( $this, 'print_custom_scripts' ) );
		}
	}

	/**
	 * Register settings group, section, and fields.
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

		add_settings_section(
			'cerber_cf_sync_section_credentials',
			__( 'Cloudflare Configuration Settings', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_section_description' ),
			'cerber-cf-sync'
		);

		add_settings_field(
			'account_id',
			__( 'Cloudflare Account ID', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_account_id_field' ),
			'cerber-cf-sync',
			'cerber_cf_sync_section_credentials'
		);

		add_settings_field(
			'list_id',
			__( 'Cloudflare List ID', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_list_id_field' ),
			'cerber-cf-sync',
			'cerber_cf_sync_section_credentials'
		);

		add_settings_field(
			'api_token',
			__( 'Cloudflare API Token', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_api_token_field' ),
			'cerber-cf-sync',
			'cerber_cf_sync_section_credentials'
		);

		add_settings_field(
			'email',
			__( 'Notification Email', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_email_field' ),
			'cerber-cf-sync',
			'cerber_cf_sync_section_credentials'
		);

		add_settings_field(
			'enable_success_emails',
			__( 'Enable Success Notifications', 'cerber-lockout-cloudflare-sync' ),
			array( $this, 'render_enable_success_emails_field' ),
			'cerber-cf-sync',
			'cerber_cf_sync_section_credentials'
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

		return $sanitized;
	}

	/**
	 * Render Section Header.
	 */
	public function render_section_description() {
		echo '<p class="description">' . esc_html__( 'Configure the credentials required to sync IP address blocks directly to your Cloudflare Account IP list.', 'cerber-lockout-cloudflare-sync' ) . '</p>';
	}

	/**
	 * Render Account ID field.
	 */
	public function render_account_id_field() {
		$settings = get_option( 'cerber_cf_sync_settings', array() );
		$val      = isset( $settings['account_id'] ) ? $settings['account_id'] : '';
		
		// Fallback check
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
	 * Render Settings Page HTML.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'cerber-lockout-cloudflare-sync' ) );
		}
		?>
		<div class="wrap cerber-cf-sync-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="cerber-cf-sync-layout">
				<!-- Settings Card -->
				<div class="cerber-cf-card config-card">
					<form method="post" action="options.php">
						<?php
						settings_fields( 'cerber_cf_sync_group' );
						do_settings_sections( 'cerber-cf-sync' );
						submit_button( __( 'Save Integration Settings', 'cerber-lockout-cloudflare-sync' ) );
						?>
					</form>
				</div>

				<!-- Diagnostic Control Panel -->
				<div class="cerber-cf-card diagnostic-card">
					<h2><?php esc_html_e( 'Diagnostics & Control Center', 'cerber-lockout-cloudflare-sync' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Use these tools to manually trigger events and manage local caching states.', 'cerber-lockout-cloudflare-sync' ); ?></p>
					
					<div class="diagnostic-actions">
						<!-- Test Connection -->
						<div class="action-row">
							<div class="action-info">
								<h3><?php esc_html_e( 'API Connection Check', 'cerber-lockout-cloudflare-sync' ); ?></h3>
								<p><?php esc_html_e( 'Tests API authorization and validates Account/List parameters.', 'cerber-lockout-cloudflare-sync' ); ?></p>
							</div>
							<div class="action-trigger">
								<button type="button" id="btn-test-connection" class="button button-secondary"><?php esc_html_e( 'Test Connection', 'cerber-lockout-cloudflare-sync' ); ?></button>
							</div>
						</div>
						<div id="test-connection-output" class="output-log hidden"></div>

						<!-- Manual Block -->
						<div class="action-row">
							<div class="action-info">
								<h3><?php esc_html_e( 'Manual IP Synchronization', 'cerber-lockout-cloudflare-sync' ); ?></h3>
								<p><?php esc_html_e( 'Manually blocklist an IP in the Cloudflare List and update the cache.', 'cerber-lockout-cloudflare-sync' ); ?></p>
							</div>
							<div class="action-trigger">
								<div class="manual-block-form">
									<input type="text" id="manual-ip-input" placeholder="e.g. 192.0.2.1" class="regular-text" style="max-width: 180px; margin-right: 8px;" />
									<button type="button" id="btn-manual-block" class="button button-secondary"><?php esc_html_e( 'Block IP', 'cerber-lockout-cloudflare-sync' ); ?></button>
								</div>
							</div>
						</div>
						<div id="manual-block-output" class="output-log hidden"></div>

						<!-- Clear Transient Cache -->
						<div class="action-row">
							<div class="action-info">
								<h3><?php esc_html_e( 'Clear Local Lockout Cache', 'cerber-lockout-cloudflare-sync' ); ?></h3>
								<p><?php esc_html_e( 'Flushes transient caching to force complete API queries on subsequent lockouts.', 'cerber-lockout-cloudflare-sync' ); ?></p>
							</div>
							<div class="action-trigger">
								<button type="button" id="btn-clear-cache" class="button button-link-delete" style="color: hsl(0, 75%, 50%);"><?php esc_html_e( 'Flush Cache', 'cerber-lockout-cloudflare-sync' ); ?></button>
							</div>
						</div>
						<div id="clear-cache-output" class="output-log hidden"></div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Print Custom Styles on settings page wrapper.
	 */
	public function print_custom_styles() {
		?>
		<style>
			.cerber-cf-sync-wrap {
				max-width: 900px;
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			}
			.cerber-cf-sync-layout {
				display: grid;
				grid-template-columns: 1fr;
				gap: 24px;
				margin-top: 20px;
			}
			.cerber-cf-card {
				background: #fff;
				border: 1px solid hsl(210, 14%, 89%);
				border-radius: 12px;
				box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
				padding: 24px 32px;
				box-sizing: border-box;
			}
			.cerber-cf-card h2 {
				margin-top: 0;
				font-size: 20px;
				font-weight: 600;
				color: hsl(215, 25%, 27%);
				border-bottom: 1px solid hsl(210, 14%, 93%);
				padding-bottom: 12px;
			}
			.form-table th {
				font-weight: 500;
				color: hsl(215, 20%, 30%);
				width: 220px;
			}
			.form-table td input[type="text"],
			.form-table td input[type="password"],
			.form-table td input[type="email"] {
				border-radius: 6px;
				border: 1px solid hsl(210, 14%, 80%);
				padding: 6px 12px;
				transition: border-color 0.2s ease, box-shadow 0.2s ease;
			}
			.form-table td input:focus {
				border-color: hsl(220, 90%, 56%);
				box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.15);
				outline: none;
			}
			.diagnostic-actions {
				margin-top: 20px;
			}
			.action-row {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 16px 0;
				border-bottom: 1px solid hsl(210, 14%, 95%);
			}
			.action-row:last-child {
				border-bottom: none;
			}
			.action-info h3 {
				margin: 0 0 4px 0;
				font-size: 15px;
				font-weight: 500;
				color: hsl(215, 25%, 27%);
			}
			.action-info p {
				margin: 0;
				font-size: 13px;
				color: hsl(210, 10%, 45%);
			}
			.action-trigger {
				min-width: 160px;
				text-align: right;
			}
			.manual-block-form {
				display: inline-flex;
				align-items: center;
				justify-content: flex-end;
			}
			.manual-block-form input {
				border-radius: 6px;
				border: 1px solid hsl(210, 14%, 80%);
				padding: 5px 8px;
			}
			.output-log {
				margin: 12px 0;
				padding: 12px 16px;
				border-radius: 8px;
				font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, Courier, monospace;
				font-size: 13px;
				line-height: 1.5;
			}
			.output-log.success {
				background-color: hsl(140, 60%, 96%);
				border-left: 4px solid hsl(140, 50%, 40%);
				color: hsl(140, 50%, 25%);
			}
			.output-log.error {
				background-color: hsl(0, 75%, 97%);
				border-left: 4px solid hsl(0, 75%, 50%);
				color: hsl(0, 75%, 30%);
			}
			.hidden {
				display: none;
			}
		</style>
		<?php
	}

	/**
	 * Print Interactive Script assets in footer.
	 */
	public function print_custom_scripts() {
		// Output JS for Ajax controls.
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				var ajaxNonce = '<?php echo esc_js( wp_create_nonce( "cerber_cf_sync_ajax_nonce" ) ); ?>';

				// Helper to update log views
				function showLog(container, message, type) {
					container.removeClass('hidden success error').addClass(type).html(message).show();
				}

				// Connection Test AJAX
				$('#btn-test-connection').on('click', function(e) {
					e.preventDefault();
					var $btn = $(this);
					var $log = $('#test-connection-output');
					
					$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Testing...', 'cerber-lockout-cloudflare-sync' ) ); ?>');
					$log.addClass('hidden');

					$.post(ajaxurl, {
						action: 'cf_sync_test_connection',
						nonce: ajaxNonce
					}, function(response) {
						if (response.success) {
							showLog($log, response.data.message, 'success');
						} else {
							showLog($log, response.data.message, 'error');
						}
					}).fail(function() {
						showLog($log, '<?php echo esc_js( __( 'Server communication failed. Please check network.', 'cerber-lockout-cloudflare-sync' ) ); ?>', 'error');
					}).always(function() {
						$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Test Connection', 'cerber-lockout-cloudflare-sync' ) ); ?>');
					});
				});

				// Manual Block IP AJAX
				$('#btn-manual-block').on('click', function(e) {
					e.preventDefault();
					var $btn = $(this);
					var $input = $('#manual-ip-input');
					var ip = $input.val().trim();
					var $log = $('#manual-block-output');

					if (!ip) {
						alert('<?php echo esc_js( __( 'Please enter a valid IP address.', 'cerber-lockout-cloudflare-sync' ) ); ?>');
						return;
					}

					$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Syncing...', 'cerber-lockout-cloudflare-sync' ) ); ?>');
					$log.addClass('hidden');

					$.post(ajaxurl, {
						action: 'cf_sync_manual_ip',
						ip: ip,
						nonce: ajaxNonce
					}, function(response) {
						if (response.success) {
							showLog($log, response.data.message, 'success');
							$input.val('');
						} else {
							showLog($log, response.data.message, 'error');
						}
					}).fail(function() {
						showLog($log, '<?php echo esc_js( __( 'Communication failed.', 'cerber-lockout-cloudflare-sync' ) ); ?>', 'error');
					}).always(function() {
						$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Block IP', 'cerber-lockout-cloudflare-sync' ) ); ?>');
					});
				});

				// Flush Cache AJAX
				$('#btn-clear-cache').on('click', function(e) {
					e.preventDefault();
					var $btn = $(this);
					var $log = $('#clear-cache-output');

					if (!confirm('<?php echo esc_js( __( 'Are you sure you want to flush all synchronized IP cache transients? This will trigger fresh lookup requests on repeating events.', 'cerber-lockout-cloudflare-sync' ) ); ?>')) {
						return;
					}

					$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Flushing...', 'cerber-lockout-cloudflare-sync' ) ); ?>');
					$log.addClass('hidden');

					$.post(ajaxurl, {
						action: 'cf_sync_clear_cache',
						nonce: ajaxNonce
					}, function(response) {
						if (response.success) {
							showLog($log, response.data.message, 'success');
						} else {
							showLog($log, response.data.message, 'error');
						}
					}).fail(function() {
						showLog($log, '<?php echo esc_js( __( 'Failure flushing transients.', 'cerber-lockout-cloudflare-sync' ) ); ?>', 'error');
					}).always(function() {
						$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Flush Cache', 'cerber-lockout-cloudflare-sync' ) ); ?>');
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * AJAX Handler: Test API connection credentials.
	 */
	public function ajax_test_connection() {
		// Capability check.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security error: Insufficient permissions.', 'cerber-lockout-cloudflare-sync' ) ), 403 );
		}

		// Nonce check.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cerber_cf_sync_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed. Invalid nonce.', 'cerber-lockout-cloudflare-sync' ) ), 400 );
		}

		$api_client = new Cerber_CF_Sync_API_Client();
		$test       = $api_client->test_connection();

		if ( is_wp_error( $test ) ) {
			wp_send_json_error( array( 'message' => $test->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Success! Successfully established connection with Cloudflare Account List API.', 'cerber-lockout-cloudflare-sync' ) ) );
	}

	/**
	 * AJAX Handler: Manually trigger an IP Block synchronization.
	 */
	public function ajax_manual_ip() {
		// Capability check.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security error: Insufficient permissions.', 'cerber-lockout-cloudflare-sync' ) ), 403 );
		}

		// Nonce check.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cerber_cf_sync_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed. Invalid nonce.', 'cerber-lockout-cloudflare-sync' ) ), 400 );
		}

		// IP check.
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
		// Capability check.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security error: Insufficient permissions.', 'cerber-lockout-cloudflare-sync' ) ), 403 );
		}

		// Nonce check.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cerber_cf_sync_ajax_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed. Invalid nonce.', 'cerber-lockout-cloudflare-sync' ) ), 400 );
		}

		global $wpdb;

		// Delete cached lockout transients from wp_options database table.
		$deleted_count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_cf_blocked_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_cf_blocked_' ) . '%'
			)
		);

		// Reset error notification rate limiter.
		delete_transient( 'cf_sync_err_email_sent' );

		wp_send_json_success( array( 'message' => sprintf( __( 'Success! Local transient cache successfully cleared. Removed %d cache entries.', 'cerber-lockout-cloudflare-sync' ), intval( $deleted_count / 2 ) ) ) );
	}
}
