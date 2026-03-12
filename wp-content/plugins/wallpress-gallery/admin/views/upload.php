<?php
/**
 * Admin View: Bulk Upload
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpg-admin-wrap">

	<div class="wpg-admin-header">
		<h1 class="wpg-admin-title"><?php esc_html_e( 'Bulk Upload', 'wallpress-gallery' ); ?></h1>
		<p style="color:var(--wpg-admin-muted);margin:4px 0 0;">
			<?php esc_html_e( 'Drag & drop images or click to browse. Each file will be uploaded and created as a new wallpaper.', 'wallpress-gallery' ); ?>
		</p>
	</div>

	<!-- Drop Zone -->
	<div id="wpg-dropzone" class="wpg-dropzone" role="button" tabindex="0"
		aria-label="<?php esc_attr_e( 'Drop images here or click to browse', 'wallpress-gallery' ); ?>">
		<div class="wpg-dropzone-inner">
			<div style="font-size:48px;margin-bottom:12px;">📂</div>
			<p style="font-size:16px;font-weight:600;color:var(--wpg-admin-text);margin:0 0 6px;">
				<?php esc_html_e( 'Drop images here or click to browse', 'wallpress-gallery' ); ?>
			</p>
			<p style="font-size:13px;color:var(--wpg-admin-muted);margin:0;">
				<?php esc_html_e( 'Accepts: JPG, PNG, GIF, WebP, AVIF', 'wallpress-gallery' ); ?>
			</p>
		</div>
	</div>

	<!-- Hidden file input -->
	<input type="file" id="wpg-file-input" accept="image/*" multiple style="display:none;" />

	<!-- Selected files list -->
	<div id="wpg-file-list-wrap" style="display:none;margin-top:20px;">
		<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
			<h3 style="margin:0;color:var(--wpg-admin-text);font-size:15px;">
				<?php esc_html_e( 'Selected Files', 'wallpress-gallery' ); ?>
				<span id="wpg-file-count" style="color:var(--wpg-admin-accent);margin-left:6px;"></span>
			</h3>
			<button id="wpg-clear-files" class="wpg-btn wpg-btn-secondary" style="font-size:12px;padding:6px 12px;">
				<?php esc_html_e( 'Clear All', 'wallpress-gallery' ); ?>
			</button>
		</div>

		<div id="wpg-file-list" class="wpg-file-list"></div>

		<div style="margin-top:16px;">
			<button id="wpg-start-upload" class="wpg-btn wpg-btn-primary" style="padding:12px 28px;font-size:14px;">
				⬆️ <?php esc_html_e( 'Start Upload', 'wallpress-gallery' ); ?>
			</button>
		</div>
	</div>

	<!-- Progress section (hidden until upload starts) -->
	<div id="wpg-progress-wrap" style="display:none;margin-top:24px;">
		<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
			<h3 style="margin:0;color:var(--wpg-admin-text);font-size:15px;">
				<?php esc_html_e( 'Upload Progress', 'wallpress-gallery' ); ?>
			</h3>
			<span id="wpg-progress-label" style="color:var(--wpg-admin-muted);font-size:13px;">0%</span>
		</div>

		<div class="wpg-progress">
			<div class="wpg-progress-fill" id="wpg-progress-fill" style="width:0%"></div>
		</div>

		<div id="wpg-upload-results" style="margin-top:16px;" class="wpg-file-list"></div>
	</div>

	<!-- Summary (shown after complete) -->
	<div id="wpg-upload-summary" style="display:none;margin-top:24px;">
		<div style="background:var(--wpg-admin-surface);border:1px solid var(--wpg-admin-border);border-radius:12px;padding:24px;text-align:center;">
			<div style="font-size:36px;margin-bottom:8px;">✅</div>
			<h3 style="color:var(--wpg-admin-text);margin:0 0 6px;">
				<?php esc_html_e( 'Upload Complete', 'wallpress-gallery' ); ?>
			</h3>
			<p id="wpg-summary-text" style="color:var(--wpg-admin-muted);margin:0 0 16px;"></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpg-manage-images' ) ); ?>"
				class="wpg-btn wpg-btn-primary">
				<?php esc_html_e( 'Manage Images', 'wallpress-gallery' ); ?>
			</a>
			<button id="wpg-upload-more" class="wpg-btn wpg-btn-secondary" style="margin-left:8px;">
				<?php esc_html_e( 'Upload More', 'wallpress-gallery' ); ?>
			</button>
		</div>
	</div>

</div><!-- .wpg-admin-wrap -->
