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

	<div class="nav-tab-wrapper" role="tablist" aria-label="<?php esc_attr_e( 'Plugin Settings Tabs', 'cerber-lockout-cloudflare-sync' ); ?>">
		<a href="#tab-api" id="tab-link-api" class="nav-tab nav-tab-active" data-tab="tab-api" role="tab" aria-selected="true" aria-controls="tab-api" tabindex="0"><?php esc_html_e( 'API Credentials', 'cerber-lockout-cloudflare-sync' ); ?></a>
		<a href="#tab-alerts" id="tab-link-alerts" class="nav-tab" data-tab="tab-alerts" role="tab" aria-selected="false" aria-controls="tab-alerts" tabindex="-1"><?php esc_html_e( 'Alerts & Warnings', 'cerber-lockout-cloudflare-sync' ); ?></a>
		<a href="#tab-purging" id="tab-link-purging" class="nav-tab" data-tab="tab-purging" role="tab" aria-selected="false" aria-controls="tab-purging" tabindex="-1"><?php esc_html_e( 'Purging Configuration', 'cerber-lockout-cloudflare-sync' ); ?></a>
		<a href="#tab-diagnostics" id="tab-link-diagnostics" class="nav-tab" data-tab="tab-diagnostics" role="tab" aria-selected="false" aria-controls="tab-diagnostics" tabindex="-1"><?php esc_html_e( 'Diagnostics & Tools', 'cerber-lockout-cloudflare-sync' ); ?></a>
	</div>

	<div class="cerber-cf-sync-layout">
		<!-- Settings Form tabs -->
		<form method="post" action="options.php" id="cerber-cf-settings-form">
			<?php settings_fields( 'cerber_cf_sync_group' ); ?>

			<div id="tab-api" class="tab-content active" role="tabpanel" aria-labelledby="tab-link-api">
				<?php include CERBER_CF_SYNC_PATH . 'includes/admin/views/tab-api.php'; ?>
			</div>

			<div id="tab-alerts" class="tab-content" role="tabpanel" aria-labelledby="tab-link-alerts">
				<?php include CERBER_CF_SYNC_PATH . 'includes/admin/views/tab-alerts.php'; ?>
			</div>

			<div id="tab-purging" class="tab-content" role="tabpanel" aria-labelledby="tab-link-purging">
				<?php include CERBER_CF_SYNC_PATH . 'includes/admin/views/tab-purging.php'; ?>
			</div>

			<div class="submit-wrapper">
				<?php submit_button( __( 'Save Integration Settings', 'cerber-lockout-cloudflare-sync' ) ); ?>
			</div>
		</form>

		<!-- Diagnostics tab (Outside the settings form) -->
		<div id="tab-diagnostics" class="tab-content" role="tabpanel" aria-labelledby="tab-link-diagnostics">
			<?php include CERBER_CF_SYNC_PATH . 'includes/admin/views/tab-diagnostics.php'; ?>
		</div>
	</div>
</div>

<script type="text/javascript">
	jQuery(document).ready(function($) {
		var ajaxNonce = '<?php echo esc_js( wp_create_nonce( "cerber_cf_sync_ajax_nonce" ) ); ?>';

		// Handle tabs switching
		$('.nav-tab-wrapper a').on('click', function(e) {
			e.preventDefault();
			var targetTab = $(this).data('tab');

			// Update active tab header class and accessibility properties
			$('.nav-tab-wrapper a')
				.removeClass('nav-tab-active')
				.attr('aria-selected', 'false')
				.attr('tabindex', '-1');

			$(this)
				.addClass('nav-tab-active')
				.attr('aria-selected', 'true')
				.attr('tabindex', '0');

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

		// Handle keyboard navigation between tabs (WAI-ARIA Pattern)
		$('.nav-tab-wrapper').on('keydown', '[role="tab"]', function(e) {
			var $tabs = $('.nav-tab-wrapper [role="tab"]');
			var index = $tabs.index(this);
			var newIndex;

			if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
				newIndex = (index + 1) % $tabs.length;
			} else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
				newIndex = (index - 1 + $tabs.length) % $tabs.length;
			} else if (e.key === 'Home') {
				newIndex = 0;
			} else if (e.key === 'End') {
				newIndex = $tabs.length - 1;
			} else {
				return;
			}

			e.preventDefault();
			var $newTab = $tabs.eq(newIndex);
			$newTab.trigger('click').focus();
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
