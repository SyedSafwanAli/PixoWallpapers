<?php
/**
 * Admin View: Manage Images
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged    = max( 1, absint( $_GET['paged'] ?? 1 ) );
$per_page = 20;
$search   = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
$cat_filter = sanitize_key( $_GET['cat'] ?? '' );

$query_args = [
	'post_type'      => 'wpg_image',
	'post_status'    => 'publish',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'orderby'        => 'date',
	'order'          => 'DESC',
];

if ( $search ) {
	$query_args['s'] = $search;
}

if ( $cat_filter ) {
	$query_args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery
		[
			'taxonomy' => 'wpg_category',
			'field'    => 'slug',
			'terms'    => $cat_filter,
		],
	];
}

$query      = new WP_Query( $query_args );
$categories = get_terms( [ 'taxonomy' => 'wpg_category', 'hide_empty' => false ] );

$base_url = admin_url( 'admin.php?page=wpg-manage-images' );
?>
<div class="wpg-admin-wrap">

	<div class="wpg-admin-header">
		<h1 class="wpg-admin-title"><?php esc_html_e( 'All Images', 'wallpress-gallery' ); ?></h1>
	</div>

	<!-- Filters row -->
	<div class="wpg-filter-row">
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
			<input type="hidden" name="page" value="wpg-manage-images" />

			<input type="text" name="s" id="wpg-admin-search"
				class="wpg-input" placeholder="<?php esc_attr_e( 'Search images…', 'wallpress-gallery' ); ?>"
				value="<?php echo esc_attr( $search ); ?>"
				style="max-width:240px;" />

			<select name="cat" class="wpg-select" style="max-width:180px;">
				<option value=""><?php esc_html_e( 'All Categories', 'wallpress-gallery' ); ?></option>
				<?php if ( ! is_wp_error( $categories ) ) : ?>
					<?php foreach ( $categories as $cat ) : ?>
						<option value="<?php echo esc_attr( $cat->slug ); ?>"
							<?php selected( $cat_filter, $cat->slug ); ?>>
							<?php echo esc_html( $cat->name ); ?>
						</option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>

			<button type="submit" class="wpg-btn wpg-btn-secondary">
				<?php esc_html_e( 'Filter', 'wallpress-gallery' ); ?>
			</button>

			<?php if ( $search || $cat_filter ) : ?>
				<a href="<?php echo esc_url( $base_url ); ?>" class="wpg-btn wpg-btn-secondary">
					<?php esc_html_e( 'Reset', 'wallpress-gallery' ); ?>
				</a>
			<?php endif; ?>
		</form>

		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpg_image' ) ); ?>"
			class="wpg-btn wpg-btn-primary" style="margin-left:auto;">
			+ <?php esc_html_e( 'Add New', 'wallpress-gallery' ); ?>
		</a>
	</div>

	<!-- Bulk Actions row -->
	<div class="wpg-bulk-row">
		<select id="wpg-bulk-action" class="wpg-select" style="max-width:200px;">
			<option value=""><?php esc_html_e( 'Bulk Actions', 'wallpress-gallery' ); ?></option>
			<option value="delete"><?php esc_html_e( 'Delete Selected', 'wallpress-gallery' ); ?></option>
			<option value="set_featured"><?php esc_html_e( 'Set as Featured', 'wallpress-gallery' ); ?></option>
			<option value="remove_featured"><?php esc_html_e( 'Remove Featured', 'wallpress-gallery' ); ?></option>
		</select>
		<button id="wpg-bulk-apply" class="wpg-btn wpg-btn-secondary">
			<?php esc_html_e( 'Apply', 'wallpress-gallery' ); ?>
		</button>
		<span id="wpg-bulk-status" style="color:var(--wpg-admin-muted);font-size:13px;margin-left:8px;"></span>
	</div>

	<!-- Table -->
	<div class="wpg-table-wrap">
		<table class="wpg-admin-table" id="wpg-images-table">
			<thead>
				<tr>
					<th style="width:32px;">
						<input type="checkbox" id="wpg-select-all" title="<?php esc_attr_e( 'Select all', 'wallpress-gallery' ); ?>" />
					</th>
					<th style="width:96px;"><?php esc_html_e( 'Thumbnail', 'wallpress-gallery' ); ?></th>
					<th><?php esc_html_e( 'Title', 'wallpress-gallery' ); ?></th>
					<th><?php esc_html_e( 'Category', 'wallpress-gallery' ); ?></th>
					<th><?php esc_html_e( 'Tags', 'wallpress-gallery' ); ?></th>
					<th style="width:70px;"><?php esc_html_e( 'Views', 'wallpress-gallery' ); ?></th>
					<th style="width:80px;"><?php esc_html_e( 'Downloads', 'wallpress-gallery' ); ?></th>
					<th style="width:60px;"><?php esc_html_e( 'Featured', 'wallpress-gallery' ); ?></th>
					<th style="width:60px;"><?php esc_html_e( 'Trending', 'wallpress-gallery' ); ?></th>
					<th style="width:100px;"><?php esc_html_e( 'Date', 'wallpress-gallery' ); ?></th>
					<th style="width:140px;"><?php esc_html_e( 'Actions', 'wallpress-gallery' ); ?></th>
				</tr>
			</thead>
			<tbody id="wpg-table-body">
				<?php if ( $query->have_posts() ) : ?>
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<?php
						$post_id     = get_the_ID();
						$thumb       = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
						$views       = (int) get_post_meta( $post_id, '_wpg_views', true );
						$dl_4k       = (int) get_post_meta( $post_id, '_wpg_downloads_4k', true );
						$dl_orig     = (int) get_post_meta( $post_id, '_wpg_downloads_orig', true );
						$is_featured = get_post_meta( $post_id, '_wpg_is_featured', true ) === '1';
						$is_trending = get_post_meta( $post_id, '_wpg_is_trending', true ) === '1';
						$cats        = get_the_terms( $post_id, 'wpg_category' );
						$tags        = get_the_terms( $post_id, 'wpg_tag' );
						?>
						<tr data-id="<?php echo esc_attr( $post_id ); ?>">
							<td>
								<input type="checkbox" class="wpg-row-check" value="<?php echo esc_attr( $post_id ); ?>" />
							</td>
							<td>
								<?php if ( $thumb ) : ?>
									<img src="<?php echo esc_url( $thumb ); ?>" alt=""
										style="width:80px;height:50px;object-fit:cover;border-radius:6px;" />
								<?php else : ?>
									<div style="width:80px;height:50px;background:var(--wpg-admin-surface2);border-radius:6px;"></div>
								<?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link() ); ?>"
									style="color:var(--wpg-admin-text);text-decoration:none;font-weight:500;">
									<?php echo esc_html( get_the_title() ); ?>
								</a>
							</td>
							<td style="font-size:12px;color:var(--wpg-admin-muted);">
								<?php
								if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
									echo esc_html( implode( ', ', wp_list_pluck( $cats, 'name' ) ) );
								} else {
									echo '—';
								}
								?>
							</td>
							<td style="font-size:12px;color:var(--wpg-admin-muted);">
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
							<td style="text-align:center;">
								<button class="wpg-toggle-featured<?php echo $is_featured ? ' active' : ''; ?>"
									data-id="<?php echo esc_attr( $post_id ); ?>"
									title="<?php esc_attr_e( 'Toggle Featured', 'wallpress-gallery' ); ?>"
									style="background:none;border:none;cursor:pointer;font-size:18px;line-height:1;color:<?php echo $is_featured ? '#7fff00' : 'var(--wpg-admin-muted)'; ?>;">
									<?php echo $is_featured ? '★' : '☆'; ?>
								</button>
							</td>
							<td style="text-align:center;">
								<button class="wpg-toggle-trending<?php echo $is_trending ? ' active' : ''; ?>"
									data-id="<?php echo esc_attr( $post_id ); ?>"
									title="<?php esc_attr_e( 'Toggle Trending', 'wallpress-gallery' ); ?>"
									style="background:none;border:none;cursor:pointer;font-size:18px;line-height:1;color:<?php echo $is_trending ? '#ff7700' : 'var(--wpg-admin-muted)'; ?>;">
									<?php echo $is_trending ? '🔥' : '○'; ?>
								</button>
							</td>
							<td style="font-size:12px;color:var(--wpg-admin-muted);">
								<?php echo esc_html( get_the_date( 'd M Y' ) ); ?>
							</td>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link() ); ?>"
									class="wpg-btn wpg-btn-secondary"
									style="font-size:11px;padding:4px 10px;display:inline-block;margin-bottom:4px;">
									<?php esc_html_e( 'Edit', 'wallpress-gallery' ); ?>
								</a>
								<button class="wpg-delete-image wpg-btn wpg-btn-danger"
									data-id="<?php echo esc_attr( $post_id ); ?>"
									style="font-size:11px;padding:4px 10px;">
									<?php esc_html_e( 'Delete', 'wallpress-gallery' ); ?>
								</button>
							</td>
						</tr>
					<?php endwhile; wp_reset_postdata(); ?>
				<?php else : ?>
					<tr>
						<td colspan="11" style="text-align:center;padding:40px;color:var(--wpg-admin-muted);">
							<?php esc_html_e( 'No images found.', 'wallpress-gallery' ); ?>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpg_image' ) ); ?>"
								style="color:var(--wpg-admin-accent);margin-left:8px;">
								<?php esc_html_e( 'Add your first image →', 'wallpress-gallery' ); ?>
							</a>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- Pagination -->
	<?php if ( $query->max_num_pages > 1 ) : ?>
		<div style="display:flex;gap:8px;justify-content:center;margin-top:24px;flex-wrap:wrap;">
			<?php
			$page_links = paginate_links( [
				'base'      => add_query_arg( 'paged', '%#%' ),
				'format'    => '',
				'current'   => $paged,
				'total'     => $query->max_num_pages,
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
				'type'      => 'array',
			] );

			if ( $page_links ) {
				foreach ( $page_links as $link ) {
					echo '<span style="display:inline-block;">' . wp_kses_post( $link ) . '</span>';
				}
			}
			?>
		</div>
	<?php endif; ?>

</div><!-- .wpg-admin-wrap -->
