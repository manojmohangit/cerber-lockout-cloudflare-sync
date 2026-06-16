<?php
/**
 * Main Settings Page wrapper template.
 *
 * @package Cerber_Lockout_Cloudflare_Sync
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap cerber-cf-sync-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
		<a href="#tab-api" class="nav-tab nav-tab-active" data-tab="tab-api"><?php esc_html_e( 'API Credentials', 'cerber-lockout-cloudflare-sync' ); ?></a>
		<a href="#tab-alerts" class="nav-tab" data-tab="tab-alerts"><?php esc_html_e( 'Alerts & Warnings', 'cerber-lockout-cloudflare-sync' ); ?></a>
		<a href="#tab-purging" class="nav-tab" data-tab="tab-purging"><?php esc_html_e( 'Purging Configuration', 'cerber-lockout-cloudflare-sync' ); ?></a>
		<a href="#tab-diagnostics" class="nav-tab" data-tab="tab-diagnostics"><?php esc_html_e( 'Diagnostics & Tools', 'cerber-lockout-cloudflare-sync' ); ?></a>
	</h2>

	<div class="cerber-cf-sync-layout">
		<!-- Settings Form tabs -->
		<form method="post" action="options.php" id="cerber-cf-settings-form">
			<?php settings_fields( 'cerber_cf_sync_group' ); ?>

			<div id="tab-api" class="tab-content active">
				<?php include CERBER_CF_SYNC_PATH . 'includes/admin/views/tab-api.php'; ?>
			</div>

			<div id="tab-alerts" class="tab-content">
				<?php include CERBER_CF_SYNC_PATH . 'includes/admin/views/tab-alerts.php'; ?>
			</div>

			<div id="tab-purging" class="tab-content">
				<?php include CERBER_CF_SYNC_PATH . 'includes/admin/views/tab-purging.php'; ?>
			</div>

			<div class="submit-wrapper">
				<?php submit_button( __( 'Save Integration Settings', 'cerber-lockout-cloudflare-sync' ) ); ?>
			</div>
		</form>

		<!-- Diagnostics tab (Outside the settings form) -->
		<div id="tab-diagnostics" class="tab-content">
			<?php include CERBER_CF_SYNC_PATH . 'includes/admin/views/tab-diagnostics.php'; ?>
		</div>
	</div>
</div>

<style>
	.cerber-cf-sync-wrap {
		max-width: 900px;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
	}
	.cerber-cf-sync-layout {
		margin-top: 15px;
	}
	.cerber-cf-card {
		background: #fff;
		border: 1px solid hsl(210, 14%, 89%);
		border-radius: 12px;
		box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
		padding: 24px 32px;
		box-sizing: border-box;
		margin-bottom: 20px;
	}
	.cerber-cf-card h2 {
		margin-top: 0;
		font-size: 20px;
		font-weight: 600;
		color: hsl(215, 25%, 27%);
		border-bottom: 1px solid hsl(210, 14%, 93%);
		padding-bottom: 12px;
		margin-bottom: 20px;
	}
	.form-table th {
		font-weight: 500;
		color: hsl(215, 20%, 30%);
		width: 220px;
	}
	.form-table td input[type="text"],
	.form-table td input[type="password"],
	.form-table td input[type="email"],
	.form-table td input[type="number"],
	.form-table td select {
		border-radius: 6px;
		border: 1px solid hsl(210, 14%, 80%);
		padding: 6px 12px;
		transition: border-color 0.2s ease, box-shadow 0.2s ease;
	}
	.form-table td input:focus,
	.form-table td select:focus {
		border-color: hsl(220, 90%, 56%);
		box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.15);
		outline: none;
	}
	.diagnostic-actions {
		margin-top: 10px;
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

	/* Tabs display rules */
	.tab-content {
		display: none;
	}
	.tab-content.active {
		display: block;
	}
	.submit-wrapper {
		margin-top: 15px;
	}
</style>

<script type="text/javascript">
	jQuery(document).ready(function($) {
		var ajaxNonce = '<?php echo esc_js( wp_create_nonce( "cerber_cf_sync_ajax_nonce" ) ); ?>';

		// Handle tabs switching
		$('.nav-tab-wrapper a').on('click', function(e) {
			e.preventDefault();
			var targetTab = $(this).data('tab');

			// Update active tab header class
			$('.nav-tab-wrapper a').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');

			// Toggle tab content visibility
			$('.tab-content').removeClass('active');
			$('#' + targetTab).addClass('active');

			// Hide or show the settings submit button (hide on diagnostics tab)
			if (targetTab === 'tab-diagnostics') {
				$('#cerber-cf-settings-form .submit-wrapper').hide();
			} else {
				$('#cerber-cf-settings-form .submit-wrapper').show();
			}

			// Store active tab in localStorage
			localStorage.setItem('cerber_cf_sync_active_tab', targetTab);
		});

		// Restore last active tab on page load
		var activeTab = localStorage.getItem('cerber_cf_sync_active_tab');
		if (activeTab && $('#' + activeTab).length) {
			$('.nav-tab-wrapper a[data-tab="' + activeTab + '"]').trigger('click');
		}

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

		// Refresh Capacity AJAX
		$('#btn-refresh-count').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var $log = $('#refresh-count-output');
			
			$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Refreshing...', 'cerber-lockout-cloudflare-sync' ) ); ?>');
			$log.addClass('hidden');

			$.post(ajaxurl, {
				action: 'cf_sync_refresh_count',
				nonce: ajaxNonce
			}, function(response) {
				if (response.success) {
					showLog($log, response.data.message, 'success');
					setTimeout(function() {
						location.reload();
					}, 1500);
				} else {
					showLog($log, response.data.message, 'error');
				}
			}).fail(function() {
				showLog($log, '<?php echo esc_js( __( 'Refresh communication failed.', 'cerber-lockout-cloudflare-sync' ) ); ?>', 'error');
			}).always(function() {
				$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Refresh Capacity', 'cerber-lockout-cloudflare-sync' ) ); ?>');
			});
		});

		// Run Purge AJAX
		$('#btn-run-purge').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var $log = $('#run-purge-output');

			if (!confirm('<?php echo esc_js( __( 'Are you sure you want to run the IP list purging process now? This will call Cloudflare APIs to remove expired or overflowing IP addresses.', 'cerber-lockout-cloudflare-sync' ) ); ?>')) {
				return;
			}

			$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Purging...', 'cerber-lockout-cloudflare-sync' ) ); ?>');
			$log.addClass('hidden');

			$.post(ajaxurl, {
				action: 'cf_sync_run_purge',
				nonce: ajaxNonce
			}, function(response) {
				if (response.success) {
					showLog($log, response.data.message, 'success');
					setTimeout(function() {
						location.reload();
					}, 2500);
				} else {
					showLog($log, response.data.message, 'error');
				}
			}).fail(function() {
				showLog($log, '<?php echo esc_js( __( 'Purging request communication failed.', 'cerber-lockout-cloudflare-sync' ) ); ?>', 'error');
			}).always(function() {
				$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Purge List Now', 'cerber-lockout-cloudflare-sync' ) ); ?>');
			});
		});
	});
</script>
