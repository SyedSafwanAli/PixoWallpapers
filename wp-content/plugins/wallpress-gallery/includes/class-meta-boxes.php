<?php
/**
 * Meta Boxes for wpg_image post type
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPG_Meta_Boxes {

	public static function init() {
		add_action( 'add_meta_boxes',        [ __CLASS__, 'register_meta_boxes' ] );
		add_action( 'save_post_wpg_image',   [ __CLASS__, 'save_meta' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_media_script' ] );
	}

	// ── Register meta boxes ───────────────────────────────────────────────────
	public static function register_meta_boxes() {
		add_meta_box(
			'wpg_image_details',
			__( 'Image Details', 'wallpress-gallery' ),
			[ __CLASS__, 'render_details_box' ],
			'wpg_image',
			'normal',
			'high'
		);

		add_meta_box(
			'wpg_image_taxonomy',
			__( 'Image Categories & Tags', 'wallpress-gallery' ),
			[ __CLASS__, 'render_taxonomy_box' ],
			'wpg_image',
			'side',
			'default'
		);
	}

	// ── Enqueue wp.media only on wpg_image post edit screens ─────────────────
	public static function enqueue_media_script( $hook ) {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}
		global $post;
		if ( ! isset( $post->post_type ) || $post->post_type !== 'wpg_image' ) {
			return;
		}
		wp_enqueue_media();
	}

	// ── Render: Image Details meta box ────────────────────────────────────────
	public static function render_details_box( $post ) {
		wp_nonce_field( 'wpg_save_meta', 'wpg_meta_nonce' );

		$author      = get_post_meta( $post->ID, '_wpg_author_name',   true );
		$resolution  = get_post_meta( $post->ID, '_wpg_resolution',    true );
		$file_4k     = get_post_meta( $post->ID, '_wpg_file_4k',       true );
		$file_orig   = get_post_meta( $post->ID, '_wpg_file_original', true );
		$is_featured = get_post_meta( $post->ID, '_wpg_is_featured',   true );
		$is_trending = get_post_meta( $post->ID, '_wpg_is_trending',   true );
		$views       = (int) get_post_meta( $post->ID, '_wpg_views',          true );
		$dl_4k       = (int) get_post_meta( $post->ID, '_wpg_downloads_4k',   true );
		$dl_orig     = (int) get_post_meta( $post->ID, '_wpg_downloads_orig', true );

		// Determine 4K capability
		$attach_id_src = (int) get_post_meta( $post->ID, '_wpg_attach_id', true );
		if ( ! $attach_id_src ) {
			$attach_id_src = (int) get_post_thumbnail_id( $post->ID );
		}
		$orig_meta    = $attach_id_src ? wp_get_attachment_metadata( $attach_id_src ) : null;
		$orig_w       = $orig_meta ? (int) $orig_meta['width']  : 0;
		$orig_h       = $orig_meta ? (int) $orig_meta['height'] : 0;
		$is_4k_source = ( $orig_w >= 3840 || $orig_h >= 2160 );
		?>
		<style>
			.wpg-meta-table { width:100%; border-collapse:collapse; }
			.wpg-meta-table th { text-align:left; padding:8px 4px; font-weight:600; width:160px; vertical-align:top; padding-top:10px; }
			.wpg-meta-table td { padding:6px 4px; }
			.wpg-meta-table input[type="text"] { width:100%; }
			.wpg-readonly { background:#f6f7f7; color:#555; padding:4px 8px; border:1px solid #ddd; border-radius:3px; display:inline-block; font-size:12px; word-break:break-all; }
			.wpg-4k-status { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
			.wpg-badge-ok  { background:#d4edda; color:#155724; padding:2px 8px; border-radius:3px; font-size:11px; font-weight:600; }
			.wpg-badge-no  { background:#fff3cd; color:#856404; padding:2px 8px; border-radius:3px; font-size:11px; font-weight:600; }
			.wpg-badge-small { background:#d1ecf1; color:#0c5460; padding:2px 8px; border-radius:3px; font-size:11px; font-weight:600; }
			#wpg-admin-gen4k-msg { font-size:12px; margin-top:4px; }
			.wpg-upload-preview { display:block; max-width:260px; max-height:160px; object-fit:cover; border-radius:4px; border:1px solid #ddd; margin-bottom:8px; }
			.wpg-upload-placeholder { display:flex; align-items:center; justify-content:center; width:260px; height:120px; border:2px dashed #ccc; border-radius:4px; color:#aaa; font-size:13px; margin-bottom:8px; }
		</style>

		<?php
		// Current preview image
		$preview_url = $attach_id_src ? wp_get_attachment_image_url( $attach_id_src, 'medium' ) : '';
		?>

		<table class="wpg-meta-table">
			<tr>
				<th><?php esc_html_e( 'Wallpaper Image', 'wallpress-gallery' ); ?></th>
				<td>
					<?php if ( $preview_url ) : ?>
						<img id="wpg-upload-preview-img" class="wpg-upload-preview"
							src="<?php echo esc_url( $preview_url ); ?>" alt="" />
					<?php else : ?>
						<div id="wpg-upload-placeholder" class="wpg-upload-placeholder">
							<?php esc_html_e( 'No image selected', 'wallpress-gallery' ); ?>
						</div>
						<img id="wpg-upload-preview-img" class="wpg-upload-preview"
							src="" alt="" style="display:none;" />
					<?php endif; ?>

					<input type="hidden" name="wpg_new_attach_id" id="wpg_new_attach_id" value="" />

					<button type="button" id="wpg-upload-image-btn" class="button button-primary">
						<?php echo $preview_url ? esc_html__( 'Change Image', 'wallpress-gallery' ) : esc_html__( 'Upload Image', 'wallpress-gallery' ); ?>
					</button>
					<?php if ( $preview_url ) : ?>
					<button type="button" id="wpg-remove-image-btn" class="button button-secondary" style="margin-left:6px;">
						<?php esc_html_e( 'Remove', 'wallpress-gallery' ); ?>
					</button>
					<?php endif; ?>
					<p class="description" style="margin-top:6px;">
						<?php esc_html_e( 'This sets the wallpaper image and featured thumbnail.', 'wallpress-gallery' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="wpg_author_name"><?php esc_html_e( 'Artist/Photographer', 'wallpress-gallery' ); ?></label></th>
				<td><input type="text" id="wpg_author_name" name="wpg_author_name"
					value="<?php echo esc_attr( $author ); ?>" class="widefat" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Resolution', 'wallpress-gallery' ); ?></th>
				<td>
					<span class="wpg-readonly"><?php echo esc_html( $resolution ?: '—' ); ?></span>
					<input type="hidden" name="wpg_resolution" value="<?php echo esc_attr( $resolution ); ?>" />
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Original File', 'wallpress-gallery' ); ?></th>
				<td>
					<?php if ( $file_orig ) : ?>
						<span class="wpg-readonly"><?php echo esc_html( basename( $file_orig ) ); ?></span>
					<?php else : ?>
						<span class="wpg-readonly" style="color:#999;"><?php esc_html_e( 'Not set — upload via Bulk Upload', 'wallpress-gallery' ); ?></span>
					<?php endif; ?>
					<input type="hidden" name="wpg_file_original" value="<?php echo esc_url( $file_orig ); ?>" />
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( '4K Version', 'wallpress-gallery' ); ?></th>
				<td>
					<div class="wpg-4k-status" id="wpg-admin-4k-status">
						<?php if ( $file_4k ) : ?>
							<span class="wpg-badge-ok">✓ Generated</span>
							<span class="wpg-readonly"><?php echo esc_html( basename( $file_4k ) ); ?></span>
							<a href="<?php echo esc_url( $file_4k ); ?>" target="_blank" style="font-size:12px;">Preview</a>
						<?php elseif ( ! $is_4k_source ) : ?>
							<span class="wpg-badge-small">✓ Original is optimal (below 4K)</span>
						<?php else : ?>
							<span class="wpg-badge-no">Not generated yet</span>
						<?php endif; ?>
					</div>
					<input type="hidden" name="wpg_file_4k" id="wpg_file_4k_hidden" value="<?php echo esc_url( $file_4k ); ?>" />

					<?php if ( $is_4k_source ) : ?>
					<div style="margin-top:8px;">
						<button type="button" id="wpg-admin-gen4k-btn" class="button button-secondary"
							data-id="<?php echo esc_attr( $post->ID ); ?>"
							data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpg_nonce' ) ); ?>">
							<?php echo $file_4k ? esc_html__( 'Regenerate 4K', 'wallpress-gallery' ) : esc_html__( 'Generate 4K', 'wallpress-gallery' ); ?>
						</button>
						<span id="wpg-admin-gen4k-msg"></span>
					</div>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Flags', 'wallpress-gallery' ); ?></th>
				<td>
					<label style="margin-right:16px;">
						<input type="checkbox" name="wpg_is_featured" value="1"
							<?php checked( $is_featured, '1' ); ?> />
						<?php esc_html_e( 'Mark as Featured', 'wallpress-gallery' ); ?>
					</label>
					<label>
						<input type="checkbox" name="wpg_is_trending" value="1"
							<?php checked( $is_trending, '1' ); ?> />
						<?php esc_html_e( 'Mark as Trending', 'wallpress-gallery' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Views', 'wallpress-gallery' ); ?></th>
				<td><span class="wpg-readonly"><?php echo esc_html( number_format_i18n( $views ) ); ?></span></td>
			</tr>
			<tr>
				<th><?php esc_html_e( '4K Downloads', 'wallpress-gallery' ); ?></th>
				<td><span class="wpg-readonly"><?php echo esc_html( number_format_i18n( $dl_4k ) ); ?></span></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Original Downloads', 'wallpress-gallery' ); ?></th>
				<td><span class="wpg-readonly"><?php echo esc_html( number_format_i18n( $dl_orig ) ); ?></span></td>
			</tr>
		</table>

		<script>
		(function(){
			// ── Upload Image via wp.media ──────────────────────────────────────
			var uploadBtn   = document.getElementById('wpg-upload-image-btn');
			var removeBtn   = document.getElementById('wpg-remove-image-btn');
			var previewImg  = document.getElementById('wpg-upload-preview-img');
			var placeholder = document.getElementById('wpg-upload-placeholder');
			var attachInput = document.getElementById('wpg_new_attach_id');
			var wpgFrame;

			if ( uploadBtn ) {
				uploadBtn.addEventListener('click', function() {
					if ( wpgFrame ) { wpgFrame.open(); return; }
					wpgFrame = wp.media({
						title:    '<?php echo esc_js( __( 'Select Wallpaper Image', 'wallpress-gallery' ) ); ?>',
						button:   { text: '<?php echo esc_js( __( 'Use this image', 'wallpress-gallery' ) ); ?>' },
						multiple: false,
						library:  { type: 'image' },
					});
					wpgFrame.on('select', function() {
						var attachment = wpgFrame.state().get('selection').first().toJSON();
						attachInput.value = attachment.id;
						var imgUrl = (attachment.sizes && attachment.sizes.medium)
							? attachment.sizes.medium.url
							: attachment.url;
						previewImg.src = imgUrl;
						previewImg.style.display = 'block';
						if ( placeholder ) { placeholder.style.display = 'none'; }
						uploadBtn.textContent = '<?php echo esc_js( __( 'Change Image', 'wallpress-gallery' ) ); ?>';
						if ( removeBtn ) { removeBtn.style.display = 'inline-block'; }
					});
					wpgFrame.open();
				});
			}

			if ( removeBtn ) {
				removeBtn.addEventListener('click', function() {
					attachInput.value = '-1'; // signal to clear
					previewImg.src = '';
					previewImg.style.display = 'none';
					if ( placeholder ) { placeholder.style.display = 'flex'; }
					uploadBtn.textContent = '<?php echo esc_js( __( 'Upload Image', 'wallpress-gallery' ) ); ?>';
					removeBtn.style.display = 'none';
				});
			}

			// ── Generate 4K ───────────────────────────────────────────────────
			var btn = document.getElementById('wpg-admin-gen4k-btn');
			if (!btn) { return; }
			btn.addEventListener('click', function(){
				btn.disabled = true;
				btn.textContent = 'Generating…';
				var msg = document.getElementById('wpg-admin-gen4k-msg');
				msg.textContent = '';
				var fd = new FormData();
				fd.append('action',  'wpg_generate_4k');
				fd.append('nonce',   btn.getAttribute('data-nonce'));
				fd.append('post_id', btn.getAttribute('data-id'));
				fetch(ajaxurl, { method: 'POST', body: fd })
					.then(function(r){ return r.json(); })
					.then(function(res){
						btn.disabled = false;
						if (res.success) {
							btn.textContent = 'Regenerate 4K';
							document.getElementById('wpg_file_4k_hidden').value = res.data.url;
							document.getElementById('wpg-admin-4k-status').innerHTML =
								'<span class="wpg-badge-ok">✓ Generated</span> ' +
								'<span class="wpg-readonly">' + res.data.url.split('/').pop() + '</span>' +
								' <a href="' + res.data.url + '" target="_blank" style="font-size:12px;">Preview</a>';
							msg.style.color = '#155724';
							msg.textContent = 'Done! Resolution: ' + res.data.res_label;
						} else {
							btn.textContent = btn.getAttribute('data-id') ? 'Generate 4K' : 'Regenerate 4K';
							msg.style.color = '#721c24';
							msg.textContent = (res.data && res.data.message) ? res.data.message : 'Failed.';
						}
					})
					.catch(function(){ btn.disabled = false; btn.textContent = 'Generate 4K'; msg.textContent = 'Network error.'; });
			});
		})();
		</script>
		<?php
	}

	// ── Render: Categories & Tags meta box ────────────────────────────────────
	public static function render_taxonomy_box( $post ) {
		// Categories
		$categories   = get_terms( [ 'taxonomy' => 'wpg_category', 'hide_empty' => false ] );
		$selected_cats = wp_get_post_terms( $post->ID, 'wpg_category', [ 'fields' => 'ids' ] );

		// Tags — stored as comma-separated
		$selected_tags = wp_get_post_terms( $post->ID, 'wpg_tag', [ 'fields' => 'names' ] );
		$tags_string   = implode( ', ', $selected_tags );
		?>
		<div style="margin-bottom:14px;">
			<strong><?php esc_html_e( 'Categories', 'wallpress-gallery' ); ?></strong>
			<div style="max-height:160px;overflow-y:auto;margin-top:6px;border:1px solid #ddd;padding:6px;border-radius:3px;">
				<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
					<?php foreach ( $categories as $cat ) : ?>
						<label style="display:block;padding:2px 0;">
							<input type="checkbox" name="wpg_categories[]"
								value="<?php echo esc_attr( $cat->term_id ); ?>"
								<?php checked( in_array( $cat->term_id, $selected_cats, true ) ); ?> />
							<?php echo esc_html( $cat->name ); ?>
						</label>
					<?php endforeach; ?>
				<?php else : ?>
					<em style="color:#999;"><?php esc_html_e( 'No categories yet.', 'wallpress-gallery' ); ?></em>
				<?php endif; ?>
			</div>
		</div>

		<div>
			<strong><?php esc_html_e( 'Tags', 'wallpress-gallery' ); ?></strong>
			<input type="text" name="wpg_tags_input"
				value="<?php echo esc_attr( $tags_string ); ?>"
				placeholder="<?php esc_attr_e( 'nature, dark, 4k …', 'wallpress-gallery' ); ?>"
				style="width:100%;margin-top:6px;" />
			<p class="description"><?php esc_html_e( 'Separate tags with commas.', 'wallpress-gallery' ); ?></p>
		</div>
		<?php
	}

	// ── Save meta ─────────────────────────────────────────────────────────────
	public static function save_meta( $post_id, $post ) {
		// Security checks
		if ( ! isset( $_POST['wpg_meta_nonce'] ) ) {
			return;
		}
		check_admin_referer( 'wpg_save_meta', 'wpg_meta_nonce' );

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// ── Text fields ───────────────────────────────────────────────────────
		$fields = [
			'wpg_author_name'  => '_wpg_author_name',
			'wpg_resolution'   => '_wpg_resolution',
		];
		foreach ( $fields as $input => $meta_key ) {
			if ( isset( $_POST[ $input ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $input ] ) ) );
			}
		}

		// ── URL fields (read-only hidden inputs — only update if value provided) ──
		if ( ! empty( $_POST['wpg_file_4k'] ) ) {
			update_post_meta( $post_id, '_wpg_file_4k', esc_url_raw( wp_unslash( $_POST['wpg_file_4k'] ) ) );
		}
		if ( ! empty( $_POST['wpg_file_original'] ) ) {
			update_post_meta( $post_id, '_wpg_file_original', esc_url_raw( wp_unslash( $_POST['wpg_file_original'] ) ) );
		}

		// ── Checkboxes ────────────────────────────────────────────────────────
		update_post_meta( $post_id, '_wpg_is_featured', isset( $_POST['wpg_is_featured'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_wpg_is_trending', isset( $_POST['wpg_is_trending'] ) ? '1' : '0' );

		// ── Wallpaper image (uploaded from meta box) ──────────────────────────
		if ( isset( $_POST['wpg_new_attach_id'] ) && '' !== $_POST['wpg_new_attach_id'] ) {
			$new_attach_id = (int) $_POST['wpg_new_attach_id'];
			if ( $new_attach_id === -1 ) {
				// Remove image
				delete_post_thumbnail( $post_id );
				delete_post_meta( $post_id, '_wpg_attach_id' );
				delete_post_meta( $post_id, '_wpg_file_original' );
				delete_post_meta( $post_id, '_wpg_resolution' );
			} elseif ( $new_attach_id > 0 ) {
				set_post_thumbnail( $post_id, $new_attach_id );
				update_post_meta( $post_id, '_wpg_attach_id', $new_attach_id );
				$orig_url = wp_get_original_image_url( $new_attach_id ) ?: wp_get_attachment_url( $new_attach_id );
				update_post_meta( $post_id, '_wpg_file_original', esc_url_raw( $orig_url ) );
				$attach_meta = wp_get_attachment_metadata( $new_attach_id );
				if ( ! empty( $attach_meta['width'] ) && ! empty( $attach_meta['height'] ) ) {
					update_post_meta( $post_id, '_wpg_resolution', $attach_meta['width'] . '×' . $attach_meta['height'] );
				}
				// Clear cached 4K since source changed
				delete_post_meta( $post_id, '_wpg_file_4k' );
			}
		}

		// ── Sync post thumbnail with _wpg_attach_id (prevents drift) ─────────
		// Even without a new image selection, ensure _thumbnail_id always matches
		// the wallpaper's own attachment so detail page always shows the right image.
		$stored_attach_id = (int) get_post_meta( $post_id, '_wpg_attach_id', true );
		if ( $stored_attach_id > 0 ) {
			$current_thumb_id = (int) get_post_thumbnail_id( $post_id );
			if ( $current_thumb_id !== $stored_attach_id ) {
				set_post_thumbnail( $post_id, $stored_attach_id );
			}
		}

		// ── Categories ────────────────────────────────────────────────────────
		$cat_ids = [];
		if ( isset( $_POST['wpg_categories'] ) && is_array( $_POST['wpg_categories'] ) ) {
			$cat_ids = array_map( 'intval', $_POST['wpg_categories'] );
		}
		wp_set_post_terms( $post_id, $cat_ids, 'wpg_category' );

		// ── Tags ──────────────────────────────────────────────────────────────
		$tags_raw = isset( $_POST['wpg_tags_input'] ) ? sanitize_text_field( wp_unslash( $_POST['wpg_tags_input'] ) ) : '';
		$tags     = array_filter( array_map( 'trim', explode( ',', $tags_raw ) ) );
		wp_set_post_terms( $post_id, $tags, 'wpg_tag' );
	}
}

WPG_Meta_Boxes::init();
