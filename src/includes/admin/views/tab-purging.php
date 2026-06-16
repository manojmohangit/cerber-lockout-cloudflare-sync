<?php
/**
 * Tab Content: IP List Purging Configuration
 *
 * @package Cerber_Lockout_Cloudflare_Sync
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cerber-cf-card config-card">
	<h2><span class="dashicons dashicons-trash"></span><?php esc_html_e( 'IP List Purging Configuration', 'cerber-lockout-cloudflare-sync' ); ?></h2>
	<table class="form-table">
		<?php
		do_settings_fields( 'cerber-cf-sync-purging', 'cerber_cf_sync_section_purging' );
		?>
	</table>
</div>
