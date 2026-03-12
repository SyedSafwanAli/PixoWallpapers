<?php
/**
 * Admin View: Categories
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle add/edit/delete form submissions
$notice = '';

if ( isset( $_POST['wpg_cat_action'] ) ) {
	check_admin_referer( 'wpg_category_action' );

	$action      = sanitize_key( $_POST['wpg_cat_action'] );
	$cat_name    = sanitize_text_field( wp_unslash( $_POST['cat_name']   ?? '' ) );
	$cat_slug    = sanitize_title( wp_unslash( $_POST['cat_slug']        ?? '' ) );
	$cat_parent  = absint( $_POST['cat_parent']                          ?? 0 );
	$cat_desc    = sanitize_textarea_field( wp_unslash( $_POST['cat_desc'] ?? '' ) );
	$cat_thumb      = absint( $_POST['cat_thumbnail_id']                 ?? 0 );
	$cat_in_carousel = isset( $_POST['cat_in_carousel'] ) ? 1 : 0;
	$edit_id        = absint( $_POST['edit_term_id']                     ?? 0 );

	if ( $action === 'add' && $cat_name ) {
		$result = wp_insert_term( $cat_name, 'wpg_category', [
			'slug'        => $cat_slug ?: sanitize_title( $cat_name ),
			'parent'      => $cat_parent,
			'description' => $cat_desc,
		] );
		if ( ! is_wp_error( $result ) ) {
			if ( $cat_thumb ) {
				update_term_meta( $result['term_id'], 'wpg_cat_thumbnail', $cat_thumb );
			}
			update_term_meta( $result['term_id'], 'wpg_cat_in_carousel', $cat_in_carousel );
			$notice = '<div class="wpg-notice wpg-notice-success">' . esc_html__( 'Category added.', 'wallpress-gallery' ) . '</div>';
		} else {
			$notice = '<div class="wpg-notice wpg-notice-error">' . esc_html( $result->get_error_message() ) . '</div>';
		}
	} elseif ( $action === 'edit' && $edit_id ) {
		$result = wp_update_term( $edit_id, 'wpg_category', [
			'name'        => $cat_name,
			'slug'        => $cat_slug,
			'parent'      => $cat_parent,
			'description' => $cat_desc,
		] );
		if ( ! is_wp_error( $result ) ) {
			update_term_meta( $edit_id, 'wpg_cat_thumbnail', $cat_thumb );
			update_term_meta( $edit_id, 'wpg_cat_in_carousel', $cat_in_carousel );
			$notice = '<div class="wpg-notice wpg-notice-success">' . esc_html__( 'Category updated.', 'wallpress-gallery' ) . '</div>';
		} else {
			$notice = '<div class="wpg-notice wpg-notice-error">' . esc_html( $result->get_error_message() ) . '</div>';
		}
	} elseif ( $action === 'delete' && $edit_id ) {
		wp_delete_term( $edit_id, 'wpg_category' );
		delete_term_meta( $edit_id, 'wpg_cat_thumbnail' );
		$notice = '<div class="wpg-notice wpg-notice-success">' . esc_html__( 'Category deleted.', 'wallpress-gallery' ) . '</div>';
	}
}

$all_cats   = get_terms( [ 'taxonomy' => 'wpg_category', 'hide_empty' => false, 'orderby' => 'name' ] );
$parent_opt = is_wp_error( $all_cats ) ? [] : $all_cats;
?>
<div class="wpg-admin-wrap">

	<div class="wpg-admin-header">
		<h1 class="wpg-admin-title"><?php esc_html_e( 'Categories', 'wallpress-gallery' ); ?></h1>
		<div class="wpg-admin-header-actions">
			<button id="wpg-seed-cats-btn" class="wpg-btn wpg-btn-secondary">
				<svg width="14" height="14" fill="none" viewBox="0 0 24 24" style="margin-right:5px;vertical-align:middle;"><path d="M12 4v16m-8-8h16" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
				<?php esc_html_e( 'Add Default Categories', 'wallpress-gallery' ); ?>
			</button>
		</div>
	</div>

	<?php echo wp_kses_post( $notice ); ?>

	<div class="wpg-cat-layout">

		<!-- LEFT: Add New Form -->
		<div class="wpg-cat-form-col">
			<div class="wpg-card-panel" id="wpg-cat-form-wrap">
				<h2 class="wpg-section-title" id="wpg-form-heading">
					<?php esc_html_e( 'Add New Category', 'wallpress-gallery' ); ?>
				</h2>
				<form method="post" id="wpg-cat-form">
					<?php wp_nonce_field( 'wpg_category_action' ); ?>
					<input type="hidden" name="wpg_cat_action" id="wpg-cat-action" value="add" />
					<input type="hidden" name="edit_term_id" id="wpg-edit-term-id" value="0" />

					<div class="wpg-field-group">
						<label for="cat_name"><?php esc_html_e( 'Name', 'wallpress-gallery' ); ?> <span style="color:#f66;">*</span></label>
						<input type="text" id="cat_name" name="cat_name" class="wpg-input" required />
					</div>

					<div class="wpg-field-group">
						<label for="cat_slug"><?php esc_html_e( 'Slug', 'wallpress-gallery' ); ?></label>
						<input type="text" id="cat_slug" name="cat_slug" class="wpg-input"
							placeholder="<?php esc_attr_e( 'auto-generated', 'wallpress-gallery' ); ?>" />
					</div>

					<div class="wpg-field-group">
						<label for="cat_parent"><?php esc_html_e( 'Parent', 'wallpress-gallery' ); ?></label>
						<select id="cat_parent" name="cat_parent" class="wpg-select">
							<option value="0"><?php esc_html_e( '— None —', 'wallpress-gallery' ); ?></option>
							<?php foreach ( $parent_opt as $t ) : ?>
								<option value="<?php echo esc_attr( $t->term_id ); ?>">
									<?php echo esc_html( $t->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="wpg-field-group">
						<label for="cat_desc"><?php esc_html_e( 'Description', 'wallpress-gallery' ); ?></label>
						<textarea id="cat_desc" name="cat_desc" class="wpg-input" rows="3"></textarea>
					</div>

					<div class="wpg-field-group">
						<label><?php esc_html_e( 'Thumbnail', 'wallpress-gallery' ); ?></label>
						<div style="display:flex;gap:10px;align-items:center;">
							<button type="button" id="wpg-cat-thumb-btn" class="wpg-btn wpg-btn-secondary">
								<?php esc_html_e( 'Select Image', 'wallpress-gallery' ); ?>
							</button>
							<input type="hidden" id="cat_thumbnail_id" name="cat_thumbnail_id" value="0" />
							<div id="wpg-cat-thumb-preview"></div>
						</div>
					</div>

					<div class="wpg-field-group">
						<label style="display:flex;align-items:center;gap:8px;cursor:pointer;user-select:none;">
							<input type="checkbox" id="cat_in_carousel" name="cat_in_carousel" value="1"
								style="width:16px;height:16px;accent-color:#7fff00;cursor:pointer;flex-shrink:0;" />
							<span style="font-weight:500;"><?php esc_html_e( 'Show in Carousel', 'wallpress-gallery' ); ?></span>
							<span style="font-size:11px;color:var(--wpg-admin-muted);font-weight:400;"><?php esc_html_e( '[wpg_category_carousel]', 'wallpress-gallery' ); ?></span>
						</label>
					</div>

					<div style="display:flex;gap:10px;margin-top:20px;">
						<button type="submit" class="wpg-btn wpg-btn-primary" id="wpg-form-submit">
							<?php esc_html_e( 'Add Category', 'wallpress-gallery' ); ?>
						</button>
						<button type="button" id="wpg-form-reset" class="wpg-btn wpg-btn-secondary"
							style="display:none;">
							<?php esc_html_e( 'Cancel Edit', 'wallpress-gallery' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>

		<!-- RIGHT: Categories Table -->
		<div class="wpg-cat-table-col">
			<div class="wpg-card-panel">
				<h2 class="wpg-section-title"><?php esc_html_e( 'All Categories', 'wallpress-gallery' ); ?></h2>

				<?php if ( ! empty( $all_cats ) && ! is_wp_error( $all_cats ) ) : ?>
					<div class="wpg-table-wrap">
						<table class="wpg-admin-table" id="wpg-cat-table">
							<thead>
								<tr>
									<th style="width:70px;"><?php esc_html_e( 'Thumb', 'wallpress-gallery' ); ?></th>
									<th><?php esc_html_e( 'Name', 'wallpress-gallery' ); ?></th>
									<th><?php esc_html_e( 'Parent', 'wallpress-gallery' ); ?></th>
									<th style="width:60px;"><?php esc_html_e( 'Count', 'wallpress-gallery' ); ?></th>
									<th style="width:70px;text-align:center;"><?php esc_html_e( 'Carousel', 'wallpress-gallery' ); ?></th>
								<th style="width:140px;"><?php esc_html_e( 'Actions', 'wallpress-gallery' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $all_cats as $term ) :
									$thumb_id    = get_term_meta( $term->term_id, 'wpg_cat_thumbnail', true );
									$thumb_url   = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '';
									$in_carousel = get_term_meta( $term->term_id, 'wpg_cat_in_carousel', true );
									$parent      = $term->parent ? get_term( $term->parent, 'wpg_category' ) : null;
									$desc        = $term->description;
									?>
									<tr data-id="<?php echo esc_attr( $term->term_id ); ?>"
										data-name="<?php echo esc_attr( $term->name ); ?>"
										data-slug="<?php echo esc_attr( $term->slug ); ?>"
										data-parent="<?php echo esc_attr( $term->parent ); ?>"
										data-desc="<?php echo esc_attr( $desc ); ?>"
										data-thumb="<?php echo esc_attr( $thumb_id ); ?>"
										data-thumb-url="<?php echo esc_attr( $thumb_url ); ?>"
										data-in-carousel="<?php echo esc_attr( $in_carousel ? '1' : '0' ); ?>">
										<td>
											<?php if ( $thumb_url ) : ?>
												<img src="<?php echo esc_url( $thumb_url ); ?>" alt=""
													style="width:60px;height:40px;object-fit:cover;border-radius:5px;" />
											<?php else : ?>
												<div style="width:60px;height:40px;background:var(--wpg-admin-surface2);border-radius:5px;"></div>
											<?php endif; ?>
										</td>
										<td style="font-weight:500;color:var(--wpg-admin-text);">
											<?php echo esc_html( $term->name ); ?>
										</td>
										<td style="color:var(--wpg-admin-muted);font-size:12px;">
											<?php echo ( $parent && ! is_wp_error( $parent ) ) ? esc_html( $parent->name ) : '—'; ?>
										</td>
										<td><?php echo esc_html( number_format_i18n( $term->count ) ); ?></td>
										<td style="text-align:center;">
											<?php if ( $in_carousel ) : ?>
												<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#7fff00;box-shadow:0 0 6px rgba(127,255,0,0.7);" title="<?php esc_attr_e( 'Shown in carousel', 'wallpress-gallery' ); ?>"></span>
											<?php else : ?>
												<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:rgba(255,255,255,0.15);" title="<?php esc_attr_e( 'Not in carousel', 'wallpress-gallery' ); ?>"></span>
											<?php endif; ?>
										</td>
										<td>
											<button class="wpg-cat-edit-btn wpg-btn wpg-btn-secondary"
												style="font-size:11px;padding:4px 10px;">
												<?php esc_html_e( 'Edit', 'wallpress-gallery' ); ?>
											</button>
											<form method="post" style="display:inline-block;margin-left:4px;">
												<?php wp_nonce_field( 'wpg_category_action' ); ?>
												<input type="hidden" name="wpg_cat_action" value="delete" />
												<input type="hidden" name="edit_term_id" value="<?php echo esc_attr( $term->term_id ); ?>" />
												<button type="submit" class="wpg-btn wpg-btn-danger"
													style="font-size:11px;padding:4px 10px;"
													onclick="return confirm('<?php echo esc_js( __( 'Delete this category? Images will not be deleted.', 'wallpress-gallery' ) ); ?>')">
													<?php esc_html_e( 'Delete', 'wallpress-gallery' ); ?>
												</button>
											</form>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<p style="color:var(--wpg-admin-muted);">
						<?php esc_html_e( 'No categories yet. Add your first category.', 'wallpress-gallery' ); ?>
					</p>
				<?php endif; ?>
			</div>
		</div>

	</div><!-- .wpg-cat-layout -->
</div><!-- .wpg-admin-wrap -->

<script>
(function(){
	'use strict';

	// Slug auto-generate
	var nameInput = document.getElementById('cat_name');
	var slugInput = document.getElementById('cat_slug');
	if (nameInput && slugInput) {
		nameInput.addEventListener('input', function(){
			if (!slugInput.dataset.manual) {
				slugInput.value = nameInput.value.toLowerCase()
					.replace(/[^a-z0-9\s-]/g, '')
					.trim().replace(/\s+/g, '-');
			}
		});
		slugInput.addEventListener('input', function(){
			slugInput.dataset.manual = '1';
		});
	}

	// Edit row click
	document.querySelectorAll('.wpg-cat-edit-btn').forEach(function(btn){
		btn.addEventListener('click', function(){
			var row      = btn.closest('tr');
			var id       = row.dataset.id;
			var name     = row.dataset.name;
			var slug     = row.dataset.slug;
			var parent   = row.dataset.parent;
			var desc     = row.dataset.desc;
			var thumbId  = row.dataset.thumb;
			var thumbUrl = row.dataset.thumbUrl;

			document.getElementById('wpg-cat-action').value    = 'edit';
			document.getElementById('wpg-edit-term-id').value  = id;
			document.getElementById('cat_name').value          = name;
			document.getElementById('cat_slug').value          = slug;
			document.getElementById('cat_parent').value        = parent;
			document.getElementById('cat_desc').value          = desc;
			document.getElementById('cat_thumbnail_id').value  = thumbId;
			document.getElementById('cat_in_carousel').checked = (row.dataset.inCarousel === '1');

			var preview = document.getElementById('wpg-cat-thumb-preview');
			if (thumbUrl) {
				preview.innerHTML = '<img src="' + thumbUrl + '" style="width:60px;height:40px;object-fit:cover;border-radius:5px;" />';
			} else {
				preview.innerHTML = '';
			}

			document.getElementById('wpg-form-heading').textContent = '<?php echo esc_js( __( 'Edit Category', 'wallpress-gallery' ) ); ?>';
			document.getElementById('wpg-form-submit').textContent  = '<?php echo esc_js( __( 'Update Category', 'wallpress-gallery' ) ); ?>';
			document.getElementById('wpg-form-reset').style.display = 'inline-block';

			document.getElementById('wpg-cat-form-wrap').scrollIntoView({ behavior: 'smooth' });
		});
	});

	// Reset form
	var resetBtn = document.getElementById('wpg-form-reset');
	if (resetBtn) {
		resetBtn.addEventListener('click', function(){
			document.getElementById('wpg-cat-form').reset();
			document.getElementById('wpg-cat-action').value   = 'add';
			document.getElementById('wpg-edit-term-id').value = '0';
			document.getElementById('wpg-form-heading').textContent = '<?php echo esc_js( __( 'Add New Category', 'wallpress-gallery' ) ); ?>';
			document.getElementById('wpg-form-submit').textContent  = '<?php echo esc_js( __( 'Add Category', 'wallpress-gallery' ) ); ?>';
			resetBtn.style.display = 'none';
			document.getElementById('wpg-cat-thumb-preview').innerHTML = '';
			document.getElementById('cat_thumbnail_id').value = '0';
			document.getElementById('cat_in_carousel').checked = false;
			if (slugInput) { delete slugInput.dataset.manual; }
		});
	}

	// Seed default categories button
	var seedBtn = document.getElementById('wpg-seed-cats-btn');
	if (seedBtn) {
		seedBtn.addEventListener('click', function(){
			if (!confirm('<?php echo esc_js( __( 'Add all 30 default wallpaper categories? Existing categories will not be duplicated.', 'wallpress-gallery' ) ); ?>')) { return; }
			seedBtn.disabled = true;
			seedBtn.textContent = '<?php echo esc_js( __( 'Adding…', 'wallpress-gallery' ) ); ?>';
			var fd = new FormData();
			fd.append('action', 'wpg_seed_categories');
			fd.append('nonce', '<?php echo esc_js( wp_create_nonce( 'wpg_nonce' ) ); ?>');
			fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', { method: 'POST', body: fd })
				.then(function(r){ return r.json(); })
				.then(function(res){
					if (res.success) {
						window.location.reload();
					} else {
						alert('Error: ' + (res.data || 'Unknown error'));
						seedBtn.disabled = false;
						seedBtn.textContent = '<?php echo esc_js( __( 'Add Default Categories', 'wallpress-gallery' ) ); ?>';
					}
				});
		});
	}

	// wp.media thumbnail uploader
	var thumbBtn = document.getElementById('wpg-cat-thumb-btn');
	if (thumbBtn) {
		thumbBtn.addEventListener('click', function(){
			if (typeof wp === 'undefined' || !wp.media) {
				alert('WordPress media library not loaded. Please refresh the page.');
				return;
			}
			var frame = wp.media({
				title: '<?php echo esc_js( __( 'Select Category Thumbnail', 'wallpress-gallery' ) ); ?>',
				button: { text: '<?php echo esc_js( __( 'Use this image', 'wallpress-gallery' ) ); ?>' },
				multiple: false
			});
			frame.on('select', function(){
				var att = frame.state().get('selection').first().toJSON();
				document.getElementById('cat_thumbnail_id').value = att.id;
				document.getElementById('wpg-cat-thumb-preview').innerHTML =
					'<img src="' + att.url + '" style="width:60px;height:40px;object-fit:cover;border-radius:5px;margin-left:8px;" />';
			});
			frame.open();
		});
	}
})();
</script>
