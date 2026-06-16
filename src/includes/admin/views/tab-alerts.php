<?php
/**
 * Tab Content: Alerts & Warnings
 *
 * @package Cerber_Lockout_Cloudflare_Sync
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cerber-cf-card config-card">
	<h2><span class="dashicons dashicons-testimonial"></span><?php esc_html_e( 'Alerts & Warnings', 'cerber-lockout-cloudflare-sync' ); ?></h2>
	<table class="form-table">
		<?php
		do_settings_fields( 'cerber-cf-sync-alerts', 'cerber_cf_sync_section_alerts' );
		?>
	</table>
</div>
