<?php
/**
 * AJAX Handlers — public + admin
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPG_Ajax {

	public static function init() {
		// ── Public AJAX (logged-in + logged-out) ──────────────────────────────
		$public_actions = [
			'wpg_search',
			'wpg_load_grid',
			'wpg_filter_category',
			'wpg_increment_views',
			'wpg_track_download',
			'wpg_load_more',
			'wpg_generate_4k',
		];

		foreach ( $public_actions as $action ) {
			add_action( 'wp_ajax_' . $action,        [ __CLASS__, 'handle_' . $action ] );
			add_action( 'wp_ajax_nopriv_' . $action, [ __CLASS__, 'handle_' . $action ] );
		}

		// ── Admin-only AJAX ───────────────────────────────────────────────────
		$admin_actions = [
			'wpg_admin_search_images',
			'wpg_admin_delete_image',
			'wpg_admin_toggle_featured',
			'wpg_admin_toggle_trending',
			'wpg_admin_bulk_action',
		];

		foreach ( $admin_actions as $action ) {
			add_action( 'wp_ajax_' . $action, [ __CLASS__, 'handle_' . $action ] );
		}
	}

	// ── Nonce check helper ────────────────────────────────────────────────────
	private static function verify_nonce() {
		check_ajax_referer( 'wpg_nonce', 'nonce' );
	}

	// ── Render a card HTML string ─────────────────────────────────────────────
	private static function render_card( $post, $style = 'uniform', $columns = 4, $index = 1 ) {
		ob_start();
		include WPG_PATH . 'templates/card.php';
		return ob_get_clean();
	}

	// ── Run a grid WP_Query and return cards HTML ─────────────────────────────
	private static function query_to_html( $args, $style, $columns ) {
		$query = new WP_Query( $args );
		$html  = '';

		if ( $query->have_posts() ) {
			$index = 0;
			while ( $query->have_posts() ) {
				$query->the_post();
				$post  = get_post();
				$html .= self::render_card( $post, $style, $columns, $index );
				$index++;
			}
			wp_reset_postdata();
		}

		return [
			'html'         => $html,
			'max_pages'    => (int) $query->max_num_pages,
			'current_page' => (int) max( 1, $args['paged'] ?? 1 ),
			'found'        => (int) $query->found_posts,
			'has_more'     => ( $args['paged'] ?? 1 ) < $query->max_num_pages,
		];
	}

	// ── wpg_search ────────────────────────────────────────────────────────────
	public static function handle_wpg_search() {
		self::verify_nonce();

		$query_str = sanitize_text_field( wp_unslash( $_POST['query'] ?? '' ) );
		$count     = absint( $_POST['count'] ?? 12 );
		$category  = sanitize_text_field( wp_unslash( $_POST['category'] ?? '' ) );

		$args = [
			'post_type'      => 'wpg_image',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			's'              => $query_str,
		];

		if ( $category ) {
			$args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery
				[
					'taxonomy' => 'wpg_category',
					'field'    => 'slug',
					'terms'    => $category,
				],
			];
		}

		$query  = new WP_Query( $args );
		$items  = '';

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id   = get_the_ID();
				$title     = get_the_title();
				$thumb_url = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
				$permalink = get_permalink();
				$items    .= '<a class="wpg-search-result-item" href="' . esc_url( $permalink ) . '">';
				if ( $thumb_url ) {
					$items .= '<img class="wpg-search-thumb" src="' . esc_url( $thumb_url ) . '" alt="' . esc_attr( $title ) . '" />';
				}
				$items .= '<span>' . esc_html( $title ) . '</span>';
				$items .= '</a>';
			}
			wp_reset_postdata();
		}

		wp_send_json_success( [
			'html'  => $items,
			'count' => (int) $query->found_posts,
		] );
	}

	// ── wpg_load_grid ─────────────────────────────────────────────────────────
	public static function handle_wpg_load_grid() {
		self::verify_nonce();

		$orderby  = sanitize_key( $_POST['orderby']  ?? 'date' );
		$page     = absint( $_POST['page']     ?? 1 );
		$columns  = absint( $_POST['columns']  ?? 4 );
		$count    = absint( $_POST['count']    ?? get_option( 'wpg_per_page', 12 ) );
		$category = sanitize_key( $_POST['category'] ?? '' );
		$tag      = sanitize_key( $_POST['tag']      ?? '' );
		$style    = sanitize_key( $_POST['style']    ?? get_option( 'wpg_default_grid', 'uniform' ) );

		$args = [
			'post_type'      => 'wpg_image',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'paged'          => $page,
		];

		self::apply_orderby( $args, $orderby );
		self::apply_tax_query( $args, $category, $tag );

		wp_send_json_success( self::query_to_html( $args, $style, $columns ) );
	}

	// ── wpg_filter_category ───────────────────────────────────────────────────
	public static function handle_wpg_filter_category() {
		self::verify_nonce();

		$cat      = sanitize_key( $_POST['category'] ?? '' );
		$page     = absint( $_POST['page']     ?? 1 );
		$columns  = absint( $_POST['columns']  ?? 4 );
		$count    = absint( $_POST['count']    ?? get_option( 'wpg_per_page', 12 ) );
		$style    = sanitize_key( $_POST['style']    ?? get_option( 'wpg_default_grid', 'uniform' ) );
		$orderby  = sanitize_key( $_POST['orderby']  ?? 'date' );
		$featured = sanitize_key( $_POST['featured'] ?? '' );

		$args = [
			'post_type'      => 'wpg_image',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'paged'          => $page,
		];

		self::apply_orderby( $args, $orderby );

		if ( $featured === 'true' ) {
			$args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery
				[
					'key'   => '_wpg_is_featured',
					'value' => '1',
				],
			];
		}

		if ( $cat ) {
			$args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery
				[
					'taxonomy' => 'wpg_category',
					'field'    => 'slug',
					'terms'    => $cat,
				],
			];
		}

		wp_send_json_success( self::query_to_html( $args, $style, $columns ) );
	}

	// ── wpg_increment_views ───────────────────────────────────────────────────
	public static function handle_wpg_increment_views() {
		self::verify_nonce();

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => 'Invalid post ID.' ] );
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'wpg_image' ) {
			wp_send_json_error( [ 'message' => 'Invalid post.' ] );
		}

		$views = (int) get_post_meta( $post_id, '_wpg_views', true );
		$views++;
		update_post_meta( $post_id, '_wpg_views', $views );

		wp_send_json_success( [ 'views' => $views ] );
	}

	// ── wpg_track_download ────────────────────────────────────────────────────
	public static function handle_wpg_track_download() {
		self::verify_nonce();

		if ( ! get_option( 'wpg_track_downloads', 1 ) ) {
			wp_send_json_success( [ 'tracked' => false ] );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		$type    = sanitize_key( $_POST['type'] ?? '' );

		if ( ! $post_id || ! in_array( $type, [ '4k', 'original' ], true ) ) {
			wp_send_json_error( [ 'message' => 'Invalid request.' ] );
		}

		$meta_key = $type === '4k' ? '_wpg_downloads_4k' : '_wpg_downloads_orig';
		$count    = (int) get_post_meta( $post_id, $meta_key, true );
		update_post_meta( $post_id, $meta_key, $count + 1 );

		wp_send_json_success( [ 'success' => true ] );
	}

	// ── wpg_load_more ─────────────────────────────────────────────────────────
	public static function handle_wpg_load_more() {
		self::verify_nonce();

		$page     = absint( $_POST['page']    ?? 1 );
		$orderby  = sanitize_key( $_POST['orderby']  ?? 'date' );
		$columns  = absint( $_POST['columns'] ?? 4 );
		$count    = absint( $_POST['count']   ?? get_option( 'wpg_per_page', 12 ) );
		$category = sanitize_key( $_POST['category'] ?? '' );
		$tag      = sanitize_key( $_POST['tag']      ?? '' );
		$style    = sanitize_key( $_POST['style'] ?? get_option( 'wpg_default_grid', 'uniform' ) );

		$args = [
			'post_type'      => 'wpg_image',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'paged'          => $page,
		];

		self::apply_orderby( $args, $orderby );
		self::apply_tax_query( $args, $category, $tag );

		wp_send_json_success( self::query_to_html( $args, $style, $columns ) );
	}

	// ── Admin: wpg_admin_search_images ────────────────────────────────────────
	public static function handle_wpg_admin_search_images() {
		self::verify_nonce();
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$q = sanitize_text_field( wp_unslash( $_POST['query'] ?? '' ) );

		$query = new WP_Query( [
			'post_type'      => 'wpg_image',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			's'              => $q,
		] );

		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				self::render_admin_table_row( get_post() );
			}
			wp_reset_postdata();
		}
		$html = ob_get_clean();

		wp_send_json_success( [ 'html' => $html ] );
	}

	// ── Admin: wpg_admin_delete_image ─────────────────────────────────────────
	public static function handle_wpg_admin_delete_image() {
		self::verify_nonce();
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( ! $post_id ) {
			wp_send_json_error();
		}

		// Delete attachment (featured image)
		$thumb_id = get_post_thumbnail_id( $post_id );
		if ( $thumb_id ) {
			wp_delete_attachment( $thumb_id, true );
		}

		wp_delete_post( $post_id, true );

		wp_send_json_success( [ 'deleted' => $post_id ] );
	}

	// ── Admin: wpg_admin_toggle_featured ──────────────────────────────────────
	public static function handle_wpg_admin_toggle_featured() {
		self::verify_nonce();
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		$current = get_post_meta( $post_id, '_wpg_is_featured', true );
		$new_val = $current === '1' ? '0' : '1';
		update_post_meta( $post_id, '_wpg_is_featured', $new_val );

		wp_send_json_success( [ 'featured' => $new_val === '1' ] );
	}

	// ── Admin: wpg_admin_toggle_trending ──────────────────────────────────────
	public static function handle_wpg_admin_toggle_trending() {
		self::verify_nonce();
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		$current = get_post_meta( $post_id, '_wpg_is_trending', true );
		$new_val = $current === '1' ? '0' : '1';
		update_post_meta( $post_id, '_wpg_is_trending', $new_val );

		wp_send_json_success( [ 'trending' => $new_val === '1' ] );
	}

	// ── Admin: wpg_admin_bulk_action ──────────────────────────────────────────
	public static function handle_wpg_admin_bulk_action() {
		self::verify_nonce();
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$action  = sanitize_key( $_POST['bulk_action'] ?? '' );
		$ids_raw = isset( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] )
			? array_map( 'absint', $_POST['post_ids'] )
			: [];

		if ( empty( $ids_raw ) || ! in_array( $action, [ 'delete', 'set_featured', 'remove_featured' ], true ) ) {
			wp_send_json_error( [ 'message' => 'Invalid request.' ] );
		}

		$processed = 0;
		foreach ( $ids_raw as $post_id ) {
			if ( get_post_type( $post_id ) !== 'wpg_image' ) {
				continue;
			}
			switch ( $action ) {
				case 'delete':
					$thumb_id = get_post_thumbnail_id( $post_id );
					if ( $thumb_id ) {
						wp_delete_attachment( $thumb_id, true );
					}
					wp_delete_post( $post_id, true );
					break;
				case 'set_featured':
					update_post_meta( $post_id, '_wpg_is_featured', '1' );
					break;
				case 'remove_featured':
					update_post_meta( $post_id, '_wpg_is_featured', '0' );
					break;
			}
			$processed++;
		}

		wp_send_json_success( [ 'processed' => $processed ] );
	}

	// ── wpg_generate_4k ───────────────────────────────────────────────────────
	public static function handle_wpg_generate_4k() {
		self::verify_nonce();

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post ID.', 'wallpress-gallery' ) ] );
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'wpg_image' ) {
			wp_send_json_error( [ 'message' => __( 'Invalid image.', 'wallpress-gallery' ) ] );
		}

		// Return cached URL if already generated
		$existing = get_post_meta( $post_id, '_wpg_file_4k', true );
		if ( $existing ) {
			wp_send_json_success( [ 'url' => $existing, 'res_label' => '3840×2160', 'cached' => true ] );
		}

		// Get original file path via stored attachment ID.
		// wp_get_original_image_path() returns the pre-scaling original for images
		// that WP downscaled on upload; falls back to get_attached_file() for
		// images uploaded after big_image_size_threshold was disabled.
		$attach_id = (int) get_post_meta( $post_id, '_wpg_attach_id', true );
		if ( ! $attach_id ) {
			$attach_id = (int) get_post_thumbnail_id( $post_id );
		}
		$orig_path = '';
		if ( $attach_id ) {
			$orig_path = wp_get_original_image_path( $attach_id ) ?: get_attached_file( $attach_id );
		}

		if ( ! $orig_path || ! file_exists( $orig_path ) ) {
			wp_send_json_error( [ 'message' => __( 'Original file not found on server.', 'wallpress-gallery' ) ] );
		}

		// Check image dimensions
		$size = wp_getimagesize( $orig_path );
		if ( ! $size ) {
			wp_send_json_error( [ 'message' => __( 'Could not read image dimensions.', 'wallpress-gallery' ) ] );
		}

		$orig_w = (int) $size[0];
		$orig_h = (int) $size[1];

		// Source too small to produce a true 4K
		if ( $orig_w < 3840 && $orig_h < 2160 ) {
			wp_send_json_error( [
				'message' => __( 'Original is already optimal — no upscaling needed.', 'wallpress-gallery' ),
				'code'    => 'already_optimal',
			] );
		}

		// Generate 4K with WP_Image_Editor
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$editor = wp_get_image_editor( $orig_path );
		if ( is_wp_error( $editor ) ) {
			wp_send_json_error( [ 'message' => __( 'Image editor unavailable.', 'wallpress-gallery' ) ] );
		}

		$resize_result = $editor->resize( 3840, 2160, false );
		if ( is_wp_error( $resize_result ) ) {
			wp_send_json_error( [ 'message' => $resize_result->get_error_message() ] );
		}

		$editor->set_quality( 90 );

		$pathinfo    = pathinfo( $orig_path );
		$filename_4k = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '-4k.jpg';

		$saved = $editor->save( $filename_4k, 'image/jpeg' );
		if ( is_wp_error( $saved ) ) {
			wp_send_json_error( [ 'message' => __( 'Failed to save 4K file.', 'wallpress-gallery' ) ] );
		}

		// Build URL for saved file
		$upload_dir  = wp_upload_dir();
		$file_4k_url = str_replace(
			wp_normalize_path( $upload_dir['basedir'] ),
			$upload_dir['baseurl'],
			wp_normalize_path( $saved['path'] )
		);

		update_post_meta( $post_id, '_wpg_file_4k', esc_url_raw( $file_4k_url ) );

		$saved_size = wp_getimagesize( $saved['path'] );
		$res_label  = $saved_size ? $saved_size[0] . '×' . $saved_size[1] : '3840×2160';

		wp_send_json_success( [
			'url'       => $file_4k_url,
			'res_label' => $res_label,
		] );
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	private static function apply_orderby( &$args, $orderby ) {
		switch ( $orderby ) {
			case 'views':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_wpg_views'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['order']    = 'DESC';
				break;
			case 'rand':
				$args['orderby'] = 'rand';
				break;
			case 'title':
				$args['orderby'] = 'title';
				$args['order']   = 'ASC';
				break;
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
		}
	}

	private static function apply_tax_query( &$args, $category, $tag ) {
		$tax = [];
		if ( $category ) {
			$tax[] = [
				'taxonomy' => 'wpg_category',
				'field'    => 'slug',
				'terms'    => $category,
			];
		}
		if ( $tag ) {
			$tax[] = [
				'taxonomy' => 'wpg_tag',
				'field'    => 'slug',
				'terms'    => $tag,
			];
		}
		if ( count( $tax ) > 1 ) {
			$tax['relation'] = 'AND';
		}
		if ( ! empty( $tax ) ) {
			$args['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery
		}
	}

	/**
	 * Render a single admin table row (used by search AJAX).
	 */
	private static function render_admin_table_row( $post ) {
		$thumb       = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );
		$views       = (int) get_post_meta( $post->ID, '_wpg_views', true );
		$dl_4k       = (int) get_post_meta( $post->ID, '_wpg_downloads_4k', true );
		$dl_orig     = (int) get_post_meta( $post->ID, '_wpg_downloads_orig', true );
		$is_featured = get_post_meta( $post->ID, '_wpg_is_featured', true ) === '1';
		$is_trending = get_post_meta( $post->ID, '_wpg_is_trending', true ) === '1';
		$cats        = get_the_terms( $post->ID, 'wpg_category' );
		$tags        = get_the_terms( $post->ID, 'wpg_tag' );
		$edit_url    = get_edit_post_link( $post->ID, 'raw' );
		?>
		<tr data-id="<?php echo esc_attr( $post->ID ); ?>">
			<td><input type="checkbox" class="wpg-row-check" value="<?php echo esc_attr( $post->ID ); ?>" /></td>
			<td>
				<?php if ( $thumb ) : ?>
					<img src="<?php echo esc_url( $thumb ); ?>" alt="" style="width:80px;height:50px;object-fit:cover;border-radius:4px;" />
				<?php else : ?>
					<div style="width:80px;height:50px;background:#2a2a32;border-radius:4px;"></div>
				<?php endif; ?>
			</td>
			<td><?php echo esc_html( get_the_title( $post ) ); ?></td>
			<td>
				<?php
				if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
					echo esc_html( implode( ', ', wp_list_pluck( $cats, 'name' ) ) );
				} else {
					echo '—';
				}
				?>
			</td>
			<td>
				<?php
				if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
					echo esc_html( implode( ', ', wp_list_pluck( $tags, 'name' ) ) );
				} else {
					echo '—';
				}
				?>
			</td>
			<td><?php echo esc_html( number_format_i18n( $views ) ); ?></td>
			<td><?php echo esc_html( number_format_i18n( $dl_4k + $dl_orig ) ); ?></td>
			<td>
				<button class="wpg-toggle-featured <?php echo $is_featured ? 'active' : ''; ?>"
					data-id="<?php echo esc_attr( $post->ID ); ?>"
					title="<?php esc_attr_e( 'Toggle Featured', 'wallpress-gallery' ); ?>">
					<?php echo $is_featured ? '★' : '☆'; ?>
				</button>
			</td>
			<td><?php echo esc_html( get_the_date( '', $post ) ); ?></td>
			<td>
				<a href="<?php echo esc_url( $edit_url ); ?>" class="wpg-btn wpg-btn-secondary" style="font-size:11px;padding:4px 10px;">
					<?php esc_html_e( 'Edit', 'wallpress-gallery' ); ?>
				</a>
				<button class="wpg-btn wpg-btn-danger wpg-delete-image"
					data-id="<?php echo esc_attr( $post->ID ); ?>"
					style="font-size:11px;padding:4px 10px;margin-left:4px;">
					<?php esc_html_e( 'Delete', 'wallpress-gallery' ); ?>
				</button>
			</td>
		</tr>
		<?php
	}
}

WPG_Ajax::init();
