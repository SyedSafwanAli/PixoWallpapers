<?php
/**
 * Bulk Upload Handler
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPG_Bulk_Upload {

	public static function init() {
		add_action( 'wp_ajax_wpg_bulk_upload', [ __CLASS__, 'handle_upload' ] );
	}

	/**
	 * Handle a single file upload via AJAX.
	 * Each file is sent in a separate request to allow per-file progress tracking.
	 */
	public static function handle_upload() {
		check_ajax_referer( 'wpg_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wallpress-gallery' ) ] );
		}

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No file received.', 'wallpress-gallery' ) ] );
		}

		// Include WP upload handling
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$file     = $_FILES['file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$overrides = [
			'test_form' => false,
			'test_type' => true,
			'mimes'     => [
				'jpg|jpeg|jpe' => 'image/jpeg',
				'gif'          => 'image/gif',
				'png'          => 'image/png',
				'webp'         => 'image/webp',
				'avif'         => 'image/avif',
			],
		];

		$uploaded = wp_handle_upload( $file, $overrides );

		if ( isset( $uploaded['error'] ) ) {
			wp_send_json_error( [ 'message' => $uploaded['error'] ] );
		}

		// Create attachment
		$filename    = $uploaded['file'];
		$filetype    = wp_check_filetype( basename( $filename ), null );
		$upload_dir  = wp_upload_dir();

		$attachment = [
			'guid'           => $upload_dir['url'] . '/' . basename( $filename ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => self::title_from_filename( basename( $filename ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attach_id = wp_insert_attachment( $attachment, $filename );

		if ( is_wp_error( $attach_id ) ) {
			wp_send_json_error( [ 'message' => $attach_id->get_error_message() ] );
		}

		// Scaling/compression is already prevented globally via wallpress-gallery.php filters.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		// Create wpg_image post
		$post_title = self::title_from_filename( basename( $filename ) );
		$post_id    = wp_insert_post( [
			'post_title'   => $post_title,
			'post_type'    => 'wpg_image',
			'post_status'  => 'publish',
			'post_content' => '',
		] );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( [ 'message' => $post_id->get_error_message() ] );
		}

		// Set featured image
		set_post_thumbnail( $post_id, $attach_id );

		// Auto-set original file URL, attachment ID, and resolution
		// wp_get_original_image_url() returns the true pre-scaling original (WP 5.3+)
		$orig_url = wp_get_original_image_url( $attach_id ) ?: $uploaded['url'];
		update_post_meta( $post_id, '_wpg_file_original', esc_url_raw( $orig_url ) );
		update_post_meta( $post_id, '_wpg_attach_id',     $attach_id );
		// Use original image dimensions (before WP scaling) for resolution label
		$orig_path = wp_get_original_image_path( $attach_id );
		$orig_size = $orig_path ? wp_getimagesize( $orig_path ) : null;
		if ( $orig_size ) {
			update_post_meta( $post_id, '_wpg_resolution', $orig_size[0] . '×' . $orig_size[1] );
		} elseif ( ! empty( $attach_data['width'] ) && ! empty( $attach_data['height'] ) ) {
			update_post_meta( $post_id, '_wpg_resolution', $attach_data['width'] . '×' . $attach_data['height'] );
		}

		// Initialize meta counters
		update_post_meta( $post_id, '_wpg_views',          0 );
		update_post_meta( $post_id, '_wpg_downloads_4k',   0 );
		update_post_meta( $post_id, '_wpg_downloads_orig', 0 );
		update_post_meta( $post_id, '_wpg_is_featured',    '0' );
		update_post_meta( $post_id, '_wpg_is_trending',    '0' );

		$thumb_url = get_the_post_thumbnail_url( $post_id, 'thumbnail' );

		wp_send_json_success( [
			'post_id'       => $post_id,
			'title'         => $post_title,
			'edit_url'      => get_edit_post_link( $post_id, 'raw' ),
			'thumbnail_url' => $thumb_url ?: '',
		] );
	}

	/** Returns 100 — used to set lossless JPEG quality during upload. */
	public static function return_full_quality() {
		return 100;
	}

	/**
	 * Generate a human-readable title from a filename.
	 *
	 * @param  string $filename  e.g. "dark-forest_4k.jpg"
	 * @return string            e.g. "Dark Forest 4k"
	 */
	private static function title_from_filename( $filename ) {
		$name = pathinfo( $filename, PATHINFO_FILENAME );
		$name = str_replace( [ '-', '_' ], ' ', $name );
		return ucwords( $name );
	}
}

WPG_Bulk_Upload::init();
