<?php
/**
 * Tab Content: Diagnostics & Tools
 *
 * @package Cerber_Lockout_Cloudflare_Sync
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cerber-cf-card diagnostic-card">
	<h2><span class="dashicons dashicons-performance"></span><?php esc_html_e( 'Diagnostics & Control Center', 'cerber-lockout-cloudflare-sync' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Use these tools to manually trigger events, monitor IP list capacity, and manage caching states.', 'cerber-lockout-cloudflare-sync' ); ?></p>
	
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
					<input type="text" id="manual-ip-input" placeholder="e.g. 192.0.2.1" class="regular-text" />
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
				<button type="button" id="btn-clear-cache" class="button button-link-delete"><?php esc_html_e( 'Flush Cache', 'cerber-lockout-cloudflare-sync' ); ?></button>
			</div>
		</div>
		<div id="clear-cache-output" class="output-log hidden"></div>

		<!-- IP List Capacity Status -->
		<div class="action-row">
			<div class="action-info">
				<h3><?php esc_html_e( 'Cloudflare IP List Capacity', 'cerber-lockout-cloudflare-sync' ); ?></h3>
				<div class="capacity-status-text">
					<?php
					if ( is_wp_error( $list_count ) ) {
						echo '<span class="error-msg">' . esc_html__( 'Unable to retrieve capacity data. Verify API credentials.', 'cerber-lockout-cloudflare-sync' ) . '</span>';
					} else {
						$settings  = get_option( 'cerber_cf_sync_settings', array() );
						$threshold = isset( $settings['warning_threshold'] ) ? (int) $settings['warning_threshold'] : 9000;
						$percent   = round( ( $list_count / 10000 ) * 100, 1 );

						$color = 'hsl(140, 50%, 40%)';
						if ( $list_count >= 10000 ) {
							$color = 'hsl(0, 75%, 50%)';
						} elseif ( $list_count >= $threshold ) {
							$color = 'hsl(35, 90%, 50%)';
						}

						printf(
							__( 'Current size: <strong class="capacity-count" style="--capacity-text-color: %s;">%s</strong> / 10,000 items (%s%% capacity)', 'cerber-lockout-cloudflare-sync' ),
							esc_attr( $color ),
							esc_html( number_format_i18n( $list_count ) ),
							esc_html( $percent )
						);
						?>
						<div class="capacity-bar-wrapper">
							<div class="capacity-bar-progress" style="--capacity-percent: <?php echo esc_attr( $percent ); ?>%; --capacity-color: <?php echo esc_attr( $color ); ?>;"></div>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<div class="action-trigger">
				<button type="button" id="btn-refresh-count" class="button button-secondary"><?php esc_html_e( 'Refresh Capacity', 'cerber-lockout-cloudflare-sync' ); ?></button>
			</div>
		</div>
		<div id="refresh-count-output" class="output-log hidden"></div>

		<!-- Manual IP Purging -->
		<div class="action-row">
			<div class="action-info">
				<h3><?php esc_html_e( 'IP List Purging', 'cerber-lockout-cloudflare-sync' ); ?></h3>
				<p><?php esc_html_e( 'Manually execute list purging based on age expiration and maximum list size settings.', 'cerber-lockout-cloudflare-sync' ); ?></p>
			</div>
			<div class="action-trigger">
				<button type="button" id="btn-run-purge" class="button button-secondary"><?php esc_html_e( 'Purge List Now', 'cerber-lockout-cloudflare-sync' ); ?></button>
			</div>
		</div>
		<div id="run-purge-output" class="output-log hidden"></div>
	</div>
</div>
