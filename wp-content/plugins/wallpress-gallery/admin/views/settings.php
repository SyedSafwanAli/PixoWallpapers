<?php
/**
 * Admin View: Settings
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpg-admin-wrap">

	<div class="wpg-admin-header">
		<h1 class="wpg-admin-title"><?php esc_html_e( 'Settings', 'wallpress-gallery' ); ?></h1>
	</div>

	<?php settings_errors( 'wpg_settings_group' ); ?>

	<form method="post" action="options.php" class="wpg-settings-form">
		<?php settings_fields( 'wpg_settings_group' ); ?>

		<!-- GENERAL -->
		<div class="wpg-card-panel" style="margin-bottom:20px;">
			<h2 class="wpg-section-title">⚙️ <?php esc_html_e( 'General', 'wallpress-gallery' ); ?></h2>
			<table class="wpg-settings-table">
				<?php do_settings_fields( 'wpg-settings', 'wpg_general' ); ?>
			</table>
		</div>

		<!-- DOWNLOADS -->
		<div class="wpg-card-panel" style="margin-bottom:20px;">
			<h2 class="wpg-section-title">⬇️ <?php esc_html_e( 'Downloads', 'wallpress-gallery' ); ?></h2>
			<table class="wpg-settings-table">
				<?php do_settings_fields( 'wpg-settings', 'wpg_downloads' ); ?>
			</table>
		</div>

		<!-- PERFORMANCE -->
		<div class="wpg-card-panel" style="margin-bottom:20px;">
			<h2 class="wpg-section-title">🚀 <?php esc_html_e( 'Performance', 'wallpress-gallery' ); ?></h2>
			<table class="wpg-settings-table">
				<?php do_settings_fields( 'wpg-settings', 'wpg_performance' ); ?>
			</table>
		</div>

		<!-- SEO -->
		<div class="wpg-card-panel" style="margin-bottom:20px;">
			<h2 class="wpg-section-title">🔍 <?php esc_html_e( 'SEO', 'wallpress-gallery' ); ?></h2>
			<div style="background:rgba(230,92,0,0.1);border:1px solid rgba(230,92,0,0.3);border-radius:8px;padding:12px 16px;margin-bottom:16px;">
				<p style="margin:0;font-size:13px;color:#e65c00;">
					<strong><?php esc_html_e( 'Recommendation:', 'wallpress-gallery' ); ?></strong>
					<?php esc_html_e( 'If you have Yoast SEO, Rank Math, or All in One SEO installed, leave schema output disabled. These plugins handle all meta tags, OG tags, and schema automatically — enabling it here may cause duplicates.', 'wallpress-gallery' ); ?>
				</p>
			</div>
			<table class="wpg-settings-table">
				<?php do_settings_fields( 'wpg-settings', 'wpg_seo' ); ?>
			</table>
		</div>

		<?php submit_button( __( 'Save Settings', 'wallpress-gallery' ), 'primary wpg-btn wpg-btn-primary', 'submit', false ); ?>
	</form>

</div><!-- .wpg-admin-wrap -->
