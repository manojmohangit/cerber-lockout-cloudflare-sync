<?php
/**
 * Tab Content: API Credentials
 *
 * @package Cerber_Lockout_Cloudflare_Sync
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cerber-cf-card config-card">
	<h2><span class="dashicons dashicons-admin-network"></span><?php esc_html_e( 'Cloudflare API Credentials', 'cerber-lockout-cloudflare-sync' ); ?></h2>
	<table class="form-table">
		<?php
		do_settings_fields( 'cerber-cf-sync-api', 'cerber_cf_sync_section_api' );
		?>
	</table>
</div>
