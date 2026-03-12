<?php
/**
 * Template: Image Detail View
 *
 * Variables available:
 *   $post   WP_Post
 *
 * NOTE: No <meta> tags, no og: tags — SEO plugins handle all meta.
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id     = $post->ID;
$title       = get_the_title( $post );
$file_4k     = get_post_meta( $post_id, '_wpg_file_4k',        true );
$resolution  = get_post_meta( $post_id, '_wpg_resolution',     true );
$author_name = get_post_meta( $post_id, '_wpg_author_name',    true );
$views       = (int) get_post_meta( $post_id, '_wpg_views',    true );
$permalink   = get_permalink( $post_id );

$cats      = wp_get_post_terms( $post_id, 'wpg_category' );
$tags      = wp_get_post_terms( $post_id, 'wpg_tag' );
$first_cat = ( ! empty( $cats ) && ! is_wp_error( $cats ) ) ? $cats[0] : null;

// Resolve attachment — prefer stored ID, fall back to featured image.
$attach_id_src = (int) get_post_meta( $post_id, '_wpg_attach_id', true );
if ( ! $attach_id_src ) {
	$attach_id_src = (int) get_post_thumbnail_id( $post_id );
}

// Display image — prefer _wpg_attach_id (the wallpaper's own attachment, always accurate).
// _thumbnail_id can drift if posts are merged/edited; _wpg_attach_id is set by our upload flow.
if ( $attach_id_src ) {
	$thumb_url = wp_get_attachment_url( $attach_id_src );
} else {
	$thumb_url = get_the_post_thumbnail_url( $post_id, 'full' );
}

// Download URL — use the true original (pre-scaling) file, not the WP-scaled copy.
// wp_get_original_image_url() returns the pre-scaling file for images WP downscaled;
// for new uploads (no scaling) it returns false, so we fall back to wp_get_attachment_url().
$file_orig = '';
if ( $attach_id_src ) {
	$file_orig = wp_get_original_image_url( $attach_id_src ) ?: wp_get_attachment_url( $attach_id_src );
}

// Get TRUE original dimensions (from the pre-scaling file, not WP attachment metadata).
$orig_w = 0;
$orig_h = 0;
if ( $attach_id_src ) {
	$orig_file_path = wp_get_original_image_path( $attach_id_src ) ?: get_attached_file( $attach_id_src );
	if ( $orig_file_path ) {
		$orig_size = wp_getimagesize( $orig_file_path );
		if ( $orig_size ) {
			$orig_w = (int) $orig_size[0];
			$orig_h = (int) $orig_size[1];
		}
	}
}
$is_4k_source = ( $orig_w >= 3840 || $orig_h >= 2160 );

// Resolution label — prefer stored meta, fall back to live dimensions
if ( ! $resolution && $orig_w && $orig_h ) {
	$resolution = $orig_w . '×' . $orig_h;
}

// Auto description
$res_label = $resolution ?: '4K';
$cat_label = $first_cat ? $first_cat->name : 'HD';
$auto_desc = sprintf(
	/* translators: 1: title, 2: resolution, 3: category */
	__( 'Get this %1$s wallpaper in %2$s resolution from %3$s category. You can also download it for computer, mobile phones, iPhone and iPad.', 'wallpress-gallery' ),
	esc_html( $title ),
	esc_html( $res_label ),
	esc_html( $cat_label )
);

// Social share URLs
$enc_url   = rawurlencode( $permalink );
$enc_title = rawurlencode( $title );
$enc_img   = rawurlencode( $thumb_url );
?>
<div class="wpg-detail-wrap">

	<!-- ── Breadcrumb ────────────────────────────────────── -->
	<nav class="wpg-detail-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'wallpress-gallery' ); ?>">
		<?php if ( $first_cat ) :
			$cat_url = get_term_link( $first_cat );
			if ( ! is_wp_error( $cat_url ) ) : ?>
				<a href="<?php echo esc_url( $cat_url ); ?>" class="wpg-bc-cat">
					<?php echo esc_html( $first_cat->name ); ?>
				</a>
				<span class="wpg-bc-sep">/</span>
			<?php endif;
		endif; ?>
		<span class="wpg-bc-title"><?php echo esc_html( $title ); ?></span>
	</nav>

	<!-- ── Title ─────────────────────────────────────────── -->
	<h1 class="wpg-detail-title"><?php echo esc_html( $title ); ?></h1>

	<!-- ── Description ───────────────────────────────────── -->
	<p class="wpg-detail-desc"><?php echo esc_html( $auto_desc ); ?></p>

	<!-- ── Main Image ────────────────────────────────────── -->
	<div class="wpg-detail-img-box">
		<?php if ( $thumb_url ) : ?>
		<img
			class="wpg-detail-image"
			src="<?php echo esc_url( $thumb_url ); ?>"
			alt="<?php echo esc_attr( $title ); ?>"
			loading="eager"
		/>
		<?php endif; ?>

		<!-- Report link -->
		<a class="wpg-report-link"
			href="mailto:report@example.com?subject=<?php echo rawurlencode( 'Report: ' . $title ); ?>"
			rel="nofollow">
			<?php esc_html_e( 'Report Image', 'wallpress-gallery' ); ?>
		</a>
	</div>

	<!-- ── Stats Bar (resolution + views) ───────────────── -->
	<div class="wpg-detail-stats">
		<?php if ( $resolution ) : ?>
		<span class="wpg-detail-stat wpg-detail-stat--res">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="2" y="4" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 20h8M12 18v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			<?php echo esc_html( $resolution ); ?>
		</span>
		<?php endif; ?>
		<span class="wpg-detail-stat wpg-detail-stat--views" data-id="<?php echo esc_attr( $post_id ); ?>">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/></svg>
			<span class="wpg-views-num"><?php echo number_format( $views ); ?></span>
			<?php esc_html_e( 'views', 'wallpress-gallery' ); ?>
		</span>
	</div>

	<!-- ── Download Buttons ──────────────────────────────── -->
	<div class="wpg-download-btns">

		<?php
		$svg_dl = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 3v13m0 0l-4-4m4 4l4-4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 21h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>';
		$svg_wand = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M15 4l5 5L8 21l-5-1 -1-5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 8l-2-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
		?>

		<?php if ( $file_4k ) : ?>
			<!-- 4K file already generated — show download -->
			<a class="wpg-dl-btn wpg-dl-primary"
				href="<?php echo esc_url( $file_4k ); ?>"
				download rel="nofollow"
				data-id="<?php echo esc_attr( $post_id ); ?>"
				data-type="4k">
				<?php echo $svg_dl; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php esc_html_e( 'Download in 4K (3840×2160)', 'wallpress-gallery' ); ?>
			</a>

		<?php elseif ( $is_4k_source ) : ?>
			<!-- Source is 4K-capable — show generate button -->
			<button class="wpg-dl-btn wpg-dl-primary wpg-generate-4k-btn"
				data-id="<?php echo esc_attr( $post_id ); ?>"
				type="button">
				<?php echo $svg_wand; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php esc_html_e( 'Generate & Download 4K', 'wallpress-gallery' ); ?>
			</button>

		<?php else : ?>
			<!-- Source smaller than 4K — inform user -->
			<span class="wpg-4k-optimal-note">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
				<?php esc_html_e( 'Original is already optimally sized', 'wallpress-gallery' ); ?>
			</span>

		<?php endif; ?>

		<?php if ( $file_orig ) : ?>
			<a class="wpg-dl-btn wpg-dl-secondary"
				href="<?php echo esc_url( $file_orig ); ?>"
				download rel="nofollow"
				data-id="<?php echo esc_attr( $post_id ); ?>"
				data-type="original">
				<?php echo $svg_dl; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php
				printf(
					/* translators: %s: resolution string */
					esc_html__( 'Download Original (%s)', 'wallpress-gallery' ),
					esc_html( $resolution ?: '—' )
				);
				?>
			</a>
		<?php endif; ?>

	</div>

	<!-- ── Meta: Resolution / Views / Author / Categories / Tags ── -->
	<div class="wpg-detail-meta">

		<?php if ( $resolution ) : ?>
		<div class="wpg-detail-meta-row">
			<span class="wpg-detail-meta-label"><?php esc_html_e( 'Resolution', 'wallpress-gallery' ); ?></span>
			<span class="wpg-detail-meta-value wpg-detail-meta-res"><?php echo esc_html( $resolution ); ?></span>
		</div>
		<?php endif; ?>

		<div class="wpg-detail-meta-row">
			<span class="wpg-detail-meta-label"><?php esc_html_e( 'Views', 'wallpress-gallery' ); ?></span>
			<span class="wpg-detail-meta-value"><?php echo number_format( $views ); ?></span>
		</div>

		<?php if ( $author_name ) : ?>
		<div class="wpg-detail-meta-row">
			<span class="wpg-detail-meta-label"><?php esc_html_e( 'Author', 'wallpress-gallery' ); ?></span>
			<span class="wpg-detail-meta-value wpg-detail-meta-author"><?php echo esc_html( $author_name ); ?></span>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) : ?>
		<div class="wpg-detail-meta-row">
			<span class="wpg-detail-meta-label"><?php esc_html_e( 'Categories', 'wallpress-gallery' ); ?></span>
			<span class="wpg-detail-meta-pills">
				<?php foreach ( $cats as $cat ) :
					$cat_link = get_term_link( $cat );
					if ( is_wp_error( $cat_link ) ) { continue; }
					?>
					<a class="wpg-detail-badge" href="<?php echo esc_url( $cat_link ); ?>"><?php echo esc_html( $cat->name ); ?></a>
				<?php endforeach; ?>
			</span>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) : ?>
		<div class="wpg-detail-meta-row">
			<span class="wpg-detail-meta-label"><?php esc_html_e( 'Tags', 'wallpress-gallery' ); ?></span>
			<span class="wpg-detail-meta-pills">
				<?php foreach ( $tags as $tag ) :
					$tag_link = get_term_link( $tag );
					if ( is_wp_error( $tag_link ) ) { continue; }
					?>
					<a class="wpg-detail-badge" href="<?php echo esc_url( $tag_link ); ?>"><?php echo esc_html( $tag->name ); ?></a>
				<?php endforeach; ?>
			</span>
		</div>
		<?php endif; ?>


	</div>

	<!-- ── Social Share ──────────────────────────────────── -->
	<div class="wpg-social-share">
		<a class="wpg-social-btn wpg-social-pinterest"
			href="https://pinterest.com/pin/create/button/?url=<?php echo $enc_url; ?>&media=<?php echo $enc_img; ?>&description=<?php echo $enc_title; ?>"
			target="_blank" rel="noopener nofollow" aria-label="Pinterest">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
			Pinterest
		</a>
		<a class="wpg-social-btn wpg-social-facebook"
			href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $enc_url; ?>"
			target="_blank" rel="noopener nofollow" aria-label="Facebook">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
			Facebook
		</a>
		<a class="wpg-social-btn wpg-social-twitter"
			href="https://twitter.com/intent/tweet?url=<?php echo $enc_url; ?>&text=<?php echo $enc_title; ?>"
			target="_blank" rel="noopener nofollow" aria-label="Twitter / X">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
			Twitter / X
		</a>
		<a class="wpg-social-btn wpg-social-reddit"
			href="https://reddit.com/submit?url=<?php echo $enc_url; ?>&title=<?php echo $enc_title; ?>"
			target="_blank" rel="noopener nofollow" aria-label="Reddit">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/></svg>
			Reddit
		</a>
	</div>

	<!-- resolution section removed -->
	<?php if ( false ) :
		$dl_link = '';
		$all_res = wpg_get_resolutions();
		$device_tabs = [
			'desktop' => [ 'label' => __( 'Desktop', 'wallpress-gallery' ), 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="2" y="3" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 21h8M12 17v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>' ],
			'mobile'  => [ 'label' => __( 'Mobiles', 'wallpress-gallery' ), 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="5" y="2" width="14" height="20" rx="3" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="18" r="1" fill="currentColor"/></svg>' ],
			'tablet'  => [ 'label' => __( 'Tablets', 'wallpress-gallery' ), 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="2" width="18" height="20" rx="3" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="18" r="1" fill="currentColor"/></svg>' ],
			'iphone'  => [ 'label' => __( 'iPhone',  'wallpress-gallery' ), 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="6" y="1" width="12" height="22" rx="4" stroke="currentColor" stroke-width="1.8"/><path d="M10 4h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>' ],
			'ipad'    => [ 'label' => __( 'iPad',    'wallpress-gallery' ), 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="2" y="3" width="20" height="18" rx="3" stroke="currentColor" stroke-width="1.8"/><circle cx="19" cy="12" r="1" fill="currentColor"/></svg>' ],
		];
		$svg_ok = [ 'svg' => [ 'width' => [], 'height' => [], 'viewBox' => [], 'fill' => [] ], 'rect' => [ 'x' => [], 'y' => [], 'width' => [], 'height' => [], 'rx' => [], 'stroke' => [], 'stroke-width' => [], 'fill' => [] ], 'circle' => [ 'cx' => [], 'cy' => [], 'r' => [], 'fill' => [], 'stroke' => [], 'stroke-width' => [] ], 'path' => [ 'd' => [], 'stroke' => [], 'stroke-width' => [], 'stroke-linecap' => [], 'fill' => [] ] ];
		// Check there's at least one device with rows
		$has_any = false;
		foreach ( $device_tabs as $key => $tab ) { if ( ! empty( $all_res[ $key ] ) ) { $has_any = true; break; } }
	?>
	<?php if ( $has_any ) : ?>
	<div class="wpg-resolutions-section">

		<!-- Tab bar -->
		<div class="wpg-res-tab-bar">
			<span class="wpg-res-section-label"><?php esc_html_e( 'More Resolutions:', 'wallpress-gallery' ); ?></span>
			<?php $first_tab = true;
			foreach ( $device_tabs as $key => $tab ) :
				if ( empty( $all_res[ $key ] ) ) { continue; } ?>
			<button class="wpg-res-tab-btn<?php echo $first_tab ? ' active' : ''; ?>"
				data-panel="wpg-respanel-<?php echo esc_attr( $key ); ?>">
				<?php echo wp_kses( $tab['icon'], $svg_ok ); ?>
				<span><?php echo esc_html( $tab['label'] ); ?></span>
				<?php if ( $first_tab ) : ?><svg class="wpg-res-chevron" width="10" height="10" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg><?php endif; ?>
			</button>
			<?php $first_tab = false; endforeach; ?>
		</div>

		<!-- Resolution rows per device -->
		<?php $first_panel = true;
		foreach ( $device_tabs as $key => $tab ) :
			$rows = $all_res[ $key ] ?? [];
			if ( empty( $rows ) ) { continue; }
		?>
		<div class="wpg-res-panel<?php echo $first_panel ? ' active' : ''; ?>" id="wpg-respanel-<?php echo esc_attr( $key ); ?>">
			<?php foreach ( $rows as $r ) :
				$parts = [];
				if ( ! empty( $r['label'] ) )  { $parts[] = '(' . esc_html( $r['label'] ) . ')'; }
				if ( ! empty( $r['compat'] ) ) { $parts[] = 'Compatible Resolutions ' . esc_html( $r['compat'] ); }
			?>
			<a class="wpg-res-row" href="<?php echo esc_url( $dl_link ); ?>" download rel="nofollow"
				data-id="<?php echo esc_attr( $post_id ); ?>" data-type="resolution">
				<span class="wpg-res-row-res"><?php echo esc_html( $r['res'] ); ?></span>
				<?php if ( $parts ) : ?>
				<span class="wpg-res-row-desc"><?php echo implode( ' | ', $parts ); ?></span>
				<?php endif; ?>
			</a>
			<?php endforeach; ?>
		</div>
		<?php $first_panel = false; endforeach; ?>

	</div>
	<?php endif; ?>
	<?php
		// old $resolutions array kept for compat — we skip it now
		$skip_old_block = true;
		if ( false ) :
		$resolutions = [
			[
				'label' => __( 'Desktop',  'wallpress-gallery' ),
				'res'   => '1920×1080',
				'icon'  => '',
			],
			[
				'label' => __( 'Widescreen', 'wallpress-gallery' ),
				'res'   => '2560×1440',
				'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="1" y="4" width="22" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 22h10M12 18v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
			],
			[
				'label' => __( 'Mobiles',  'wallpress-gallery' ),
				'res'   => '1080×1920',
				'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="5" y="2" width="14" height="20" rx="3" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="18" r="1" fill="currentColor"/></svg>',
			],
			[
				'label' => __( 'Tablets',  'wallpress-gallery' ),
				'res'   => '2048×2732',
				'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="2" width="18" height="20" rx="3" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="18" r="1" fill="currentColor"/></svg>',
			],
			[
				'label' => __( 'iPhone',   'wallpress-gallery' ),
				'res'   => '1170×2532',
				'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="6" y="1" width="12" height="22" rx="4" stroke="currentColor" stroke-width="1.8"/><path d="M10 4h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
			],
			[
				'label' => __( 'iPad',     'wallpress-gallery' ),
				'res'   => '2048×1536',
				'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="2" y="3" width="20" height="18" rx="3" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="19" r="1" fill="currentColor"/></svg>',
			],
		];
		endif;
	endif; ?>

	<!-- ── Related Images ───────────────────────────────── -->
	<?php
	// Try category-based first
	$related_args = [
		'post_type'      => 'wpg_image',
		'post_status'    => 'publish',
		'posts_per_page' => 8,
		'post__not_in'   => [ $post_id ],
		'orderby'        => 'rand',
	];
	if ( $first_cat && ! is_wp_error( $first_cat ) ) {
		$related_args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery
			[
				'taxonomy' => 'wpg_category',
				'field'    => 'term_id',
				'terms'    => $first_cat->term_id,
			],
		];
	}
	$related = new WP_Query( $related_args );
	// Fallback: if no category results, fetch any random images
	if ( ! $related->have_posts() ) {
		unset( $related_args['tax_query'] );
		$related = new WP_Query( $related_args );
	}
	if ( $related->have_posts() ) :
	?>
	<div class="wpg-related-section">
		<div class="wpg-related-header">
			<h2 class="wpg-related-title"><?php esc_html_e( 'Related Images', 'wallpress-gallery' ); ?></h2>
		</div>
		<div class="wpg-related-grid">
			<?php while ( $related->have_posts() ) : $related->the_post();
				$post    = get_post();
				$style   = 'uniform';
				$columns = 4;
				include WPG_PATH . 'templates/card.php';
			endwhile; wp_reset_postdata(); ?>
		</div>
	</div>
	<?php endif; ?>

</div><!-- .wpg-detail-wrap -->
