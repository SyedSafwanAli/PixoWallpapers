<?php
/**
 * Admin View: Dashboard
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Stats ─────────────────────────────────────────────────────────────────────
$post_counts  = wp_count_posts( 'wpg_image' );
$total_images = isset( $post_counts->publish ) ? (int) $post_counts->publish : 0;
$total_draft  = isset( $post_counts->draft )   ? (int) $post_counts->draft   : 0;

$total_cats = wp_count_terms( [ 'taxonomy' => 'wpg_category', 'hide_empty' => false ] );
$total_cats = is_wp_error( $total_cats ) ? 0 : (int) $total_cats;

$total_tags = wp_count_terms( [ 'taxonomy' => 'wpg_tag', 'hide_empty' => false ] );
$total_tags = is_wp_error( $total_tags ) ? 0 : (int) $total_tags;

global $wpdb;
$total_views = (int) $wpdb->get_var(
	"SELECT SUM(meta_value+0) FROM {$wpdb->postmeta} WHERE meta_key = '_wpg_views'"
);
$total_dl_4k = (int) $wpdb->get_var(
	"SELECT SUM(meta_value+0) FROM {$wpdb->postmeta} WHERE meta_key = '_wpg_downloads_4k'"
);
$total_dl_orig = (int) $wpdb->get_var(
	"SELECT SUM(meta_value+0) FROM {$wpdb->postmeta} WHERE meta_key = '_wpg_downloads_orig'"
);
$total_downloads = $total_dl_4k + $total_dl_orig;

// ── Recent 6 uploads ──────────────────────────────────────────────────────────
$recent_query = new WP_Query( [
	'post_type'      => 'wpg_image',
	'post_status'    => 'publish',
	'posts_per_page' => 6,
	'orderby'        => 'date',
	'order'          => 'DESC',
] );

// ── Top 5 most viewed ─────────────────────────────────────────────────────────
$top_query = new WP_Query( [
	'post_type'      => 'wpg_image',
	'post_status'    => 'publish',
	'posts_per_page' => 5,
	'orderby'        => 'meta_value_num',
	'meta_key'       => '_wpg_views',
	'order'          => 'DESC',
] );
$top_posts = $top_query->posts;
$top_max   = ! empty( $top_posts ) ? max( array_map( function( $p ) {
	return (int) get_post_meta( $p->ID, '_wpg_views', true );
}, $top_posts ) ) : 1;

// ── Photo-like gradients for layout previews ──────────────────────────────────
$pg = [
	'linear-gradient(135deg,#1a3a2a 0%,#4a7c3f 60%,#2d6a4f 100%)',  // 0 forest
	'linear-gradient(135deg,#1a1a3e 0%,#4a2fa0 60%,#2d1b69 100%)',  // 1 galaxy
	'linear-gradient(135deg,#0a2a4a 0%,#1565c0 60%,#0d47a1 100%)',  // 2 ocean
	'linear-gradient(135deg,#4a200a 0%,#c0650d 60%,#8b2500 100%)',  // 3 sunset
	'linear-gradient(135deg,#3a0a2a 0%,#a0298a 60%,#701a5e 100%)',  // 4 rose
	'linear-gradient(135deg,#0a3a3a 0%,#00897b 60%,#004d40 100%)',  // 5 teal
	'linear-gradient(135deg,#3a2a0a 0%,#c09a00 60%,#7a5f00 100%)',  // 6 gold
	'linear-gradient(135deg,#3a0a0a 0%,#c02020 60%,#8b1515 100%)',  // 7 crimson
	'linear-gradient(135deg,#0a0a3a 0%,#303f9f 60%,#1a1a8b 100%)', // 8 indigo
	'linear-gradient(135deg,#1a2a0a 0%,#5a8a00 60%,#3a6000 100%)', // 9 lime
	'linear-gradient(135deg,#2a0a3a 0%,#7b1fa2 60%,#4a0080 100%)', // 10 violet
	'linear-gradient(135deg,#0a1a2a 0%,#01579b 60%,#003c6e 100%)', // 11 navy
];

// ── Layout styles definition ──────────────────────────────────────────────────
$layouts = [
	[
		'key'       => 'uniform',
		'label'     => 'Uniform Grid',
		'desc'      => 'Fixed 16:9 ratio cards — perfect for landscape wallpapers',
		'shortcode' => '[wpg_recent style="uniform" columns="4"]',
		'color'     => '#7fff00',
	],
	[
		'key'       => 'masonry',
		'label'     => 'Masonry',
		'desc'      => 'Pinterest-style flowing columns, natural image heights',
		'shortcode' => '[wpg_recent style="masonry" columns="4"]',
		'color'     => '#00d4ff',
	],
	[
		'key'       => 'portrait',
		'label'     => 'Portrait',
		'desc'      => 'Tall 9:16 cards — ideal for mobile & phone wallpapers',
		'shortcode' => '[wpg_recent style="portrait" columns="5"]',
		'color'     => '#ff6ec7',
	],
	[
		'key'       => 'natural',
		'label'     => 'Natural',
		'desc'      => 'No cropping — images display at their full original size',
		'shortcode' => '[wpg_popular style="natural" columns="4"]',
		'color'     => '#ffa500',
	],
	[
		'key'       => 'spotlight',
		'label'     => 'Spotlight',
		'desc'      => '1 large hero image with smaller thumbnails beside it',
		'shortcode' => '[wpg_featured style="spotlight" columns="4"]',
		'color'     => '#b57bff',
	],
	[
		'key'       => 'filmstrip',
		'label'     => 'Filmstrip',
		'desc'      => 'Horizontal scrolling row — great for category sections',
		'shortcode' => '[wpg_recent style="filmstrip"]',
		'color'     => '#ff7b7b',
	],
];
?>
<div class="wpg-admin-wrap">

	<!-- ── Header ──────────────────────────────────────────────────────────── -->
	<div class="wpg-dash-header">
		<div class="wpg-dash-header-left">
			<div class="wpg-dash-logo">
				<svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
					<rect width="36" height="36" rx="10" fill="#7fff00" fill-opacity=".1"/>
					<rect x="5" y="5" width="12" height="12" rx="3" fill="#7fff00"/>
					<rect x="19" y="5" width="12" height="12" rx="3" fill="#7fff00" fill-opacity=".55"/>
					<rect x="5" y="19" width="12" height="12" rx="3" fill="#7fff00" fill-opacity=".55"/>
					<rect x="19" y="19" width="12" height="12" rx="3" fill="#7fff00" fill-opacity=".25"/>
				</svg>
			</div>
			<div>
				<h1 class="wpg-admin-title">WallPress Gallery</h1>
				<p class="wpg-dash-subtitle">v<?php echo esc_html( WPG_VERSION ); ?> &nbsp;&middot;&nbsp; Wallpaper Gallery Manager</p>
			</div>
		</div>
		<div class="wpg-dash-header-right">
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpg_image' ) ); ?>" class="wpg-btn wpg-btn-primary">
				<svg width="13" height="13" fill="none" viewBox="0 0 24 24" style="margin-right:5px;"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
				<?php esc_html_e( 'Add Image', 'wallpress-gallery' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpg-bulk-upload' ) ); ?>" class="wpg-btn wpg-btn-secondary">
				<svg width="13" height="13" fill="none" viewBox="0 0 24 24" style="margin-right:5px;"><path d="M12 16V4m0 0L8 8m4-4l4 4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 20h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
				<?php esc_html_e( 'Bulk Upload', 'wallpress-gallery' ); ?>
			</a>
		</div>
	</div>

	<!-- ── Stats ───────────────────────────────────────────────────────────── -->
	<div class="wpg-stats-grid">
		<div class="wpg-stat-card wpg-stat-accent">
			<div class="wpg-stat-icon-wrap" style="background:rgba(127,255,0,.1);">
				<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5" fill="#7fff00"/><rect x="14" y="3" width="7" height="7" rx="1.5" fill="#7fff00" opacity=".6"/><rect x="3" y="14" width="7" height="7" rx="1.5" fill="#7fff00" opacity=".6"/><rect x="14" y="14" width="7" height="7" rx="1.5" fill="#7fff00" opacity=".3"/></svg>
			</div>
			<div class="wpg-stat-number"><?php echo esc_html( number_format_i18n( $total_images ) ); ?></div>
			<div class="wpg-stat-label"><?php esc_html_e( 'Published Images', 'wallpress-gallery' ); ?></div>
		</div>
		<div class="wpg-stat-card">
			<div class="wpg-stat-icon-wrap" style="background:rgba(0,212,255,.1);">
				<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M3 7h18M3 12h18M3 17h10" stroke="#00d4ff" stroke-width="2" stroke-linecap="round"/></svg>
			</div>
			<div class="wpg-stat-number" style="color:#00d4ff;"><?php echo esc_html( number_format_i18n( $total_cats ) ); ?></div>
			<div class="wpg-stat-label"><?php esc_html_e( 'Categories', 'wallpress-gallery' ); ?></div>
		</div>
		<div class="wpg-stat-card">
			<div class="wpg-stat-icon-wrap" style="background:rgba(181,123,255,.1);">
				<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3" fill="#b57bff"/><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" stroke="#b57bff" stroke-width="1.8" fill="none"/></svg>
			</div>
			<div class="wpg-stat-number" style="color:#b57bff;"><?php echo esc_html( number_format_i18n( $total_views ) ); ?></div>
			<div class="wpg-stat-label"><?php esc_html_e( 'Total Views', 'wallpress-gallery' ); ?></div>
		</div>
		<div class="wpg-stat-card">
			<div class="wpg-stat-icon-wrap" style="background:rgba(255,165,0,.1);">
				<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M12 3v13m0 0l-4-4m4 4l4-4" stroke="#ffa500" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 21h14" stroke="#ffa500" stroke-width="2" stroke-linecap="round"/></svg>
			</div>
			<div class="wpg-stat-number" style="color:#ffa500;"><?php echo esc_html( number_format_i18n( $total_downloads ) ); ?></div>
			<div class="wpg-stat-label"><?php esc_html_e( 'Total Downloads', 'wallpress-gallery' ); ?></div>
		</div>
		<div class="wpg-stat-card">
			<div class="wpg-stat-icon-wrap" style="background:rgba(255,110,199,.1);">
				<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M7 7h10v10H7z" stroke="#ff6ec7" stroke-width="1.8" fill="none" rx="2"/><path d="M10 7V5m4 2V5M7 11h10" stroke="#ff6ec7" stroke-width="1.5" stroke-linecap="round"/></svg>
			</div>
			<div class="wpg-stat-number" style="color:#ff6ec7;"><?php echo esc_html( number_format_i18n( $total_tags ) ); ?></div>
			<div class="wpg-stat-label"><?php esc_html_e( 'Tags', 'wallpress-gallery' ); ?></div>
		</div>
		<div class="wpg-stat-card">
			<div class="wpg-stat-icon-wrap" style="background:rgba(255,123,123,.1);">
				<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="#ff7b7b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</div>
			<div class="wpg-stat-number" style="color:#ff7b7b;"><?php echo esc_html( number_format_i18n( $total_draft ) ); ?></div>
			<div class="wpg-stat-label"><?php esc_html_e( 'Drafts', 'wallpress-gallery' ); ?></div>
		</div>
	</div>

	<!-- ── Quick Nav ───────────────────────────────────────────────────────── -->
	<div class="wpg-quick-nav">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpg-manage-images' ) ); ?>" class="wpg-nav-pill">
			<svg width="13" height="13" fill="none" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1" fill="currentColor" opacity=".7"/><rect x="14" y="3" width="7" height="7" rx="1" fill="currentColor" opacity=".7"/><rect x="3" y="14" width="7" height="7" rx="1" fill="currentColor" opacity=".7"/><rect x="14" y="14" width="7" height="7" rx="1" fill="currentColor" opacity=".7"/></svg>
			<?php esc_html_e( 'All Images', 'wallpress-gallery' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpg-bulk-upload' ) ); ?>" class="wpg-nav-pill">
			<svg width="13" height="13" fill="none" viewBox="0 0 24 24"><path d="M12 16V4m0 0L8 8m4-4l4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 20h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
			<?php esc_html_e( 'Bulk Upload', 'wallpress-gallery' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpg-categories' ) ); ?>" class="wpg-nav-pill">
			<svg width="13" height="13" fill="none" viewBox="0 0 24 24"><path d="M3 7h18M3 12h18M3 17h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
			<?php esc_html_e( 'Categories', 'wallpress-gallery' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpg-settings' ) ); ?>" class="wpg-nav-pill">
			<svg width="13" height="13" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/><path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			<?php esc_html_e( 'Settings', 'wallpress-gallery' ); ?>
		</a>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="wpg-nav-pill" target="_blank" rel="noopener">
			<svg width="13" height="13" fill="none" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M15 3h6v6M10 14L21 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<?php esc_html_e( 'View Site', 'wallpress-gallery' ); ?>
		</a>
	</div>

	<!-- ── Layout Styles Section ───────────────────────────────────────────── -->
	<div class="wpg-section-header">
		<h2 class="wpg-section-title wpg-section-title--large"><?php esc_html_e( 'Gallery Layout Styles', 'wallpress-gallery' ); ?></h2>
		<p class="wpg-section-desc"><?php esc_html_e( 'Copy any shortcode and paste it into a page or Divi Code Module to use that layout.', 'wallpress-gallery' ); ?></p>
	</div>

	<div class="wpg-layouts-grid">
		<?php foreach ( $layouts as $layout ) : ?>
		<div class="wpg-layout-card" data-layout="<?php echo esc_attr( $layout['key'] ); ?>">

			<!-- Preview thumbnail -->
			<div class="wpg-layout-preview wpg-layout-preview--<?php echo esc_attr( $layout['key'] ); ?>">

				<?php if ( $layout['key'] === 'uniform' ) : ?>
					<!-- 4-column landscape grid -->
					<div class="wpg-lp-uniform-grid">
						<div class="wpg-lp-photo wpg-lp-photo--land" style="background:<?php echo esc_attr( $pg[0] ); ?>;"></div>
						<div class="wpg-lp-photo wpg-lp-photo--land" style="background:<?php echo esc_attr( $pg[1] ); ?>;"></div>
						<div class="wpg-lp-photo wpg-lp-photo--land" style="background:<?php echo esc_attr( $pg[2] ); ?>;"></div>
						<div class="wpg-lp-photo wpg-lp-photo--land" style="background:<?php echo esc_attr( $pg[3] ); ?>;"></div>
						<div class="wpg-lp-photo wpg-lp-photo--land" style="background:<?php echo esc_attr( $pg[4] ); ?>;"></div>
						<div class="wpg-lp-photo wpg-lp-photo--land" style="background:<?php echo esc_attr( $pg[5] ); ?>;"></div>
						<div class="wpg-lp-photo wpg-lp-photo--land" style="background:<?php echo esc_attr( $pg[6] ); ?>;"></div>
						<div class="wpg-lp-photo wpg-lp-photo--land" style="background:<?php echo esc_attr( $pg[7] ); ?>;"></div>
					</div>

				<?php elseif ( $layout['key'] === 'masonry' ) : ?>
					<!-- Pinterest columns with varied heights -->
					<div class="wpg-lp-masonry-grid">
						<div class="wpg-lp-col">
							<div class="wpg-lp-photo" style="height:56px;background:<?php echo esc_attr( $pg[0] ); ?>;"></div>
							<div class="wpg-lp-photo" style="height:38px;background:<?php echo esc_attr( $pg[4] ); ?>;"></div>
						</div>
						<div class="wpg-lp-col">
							<div class="wpg-lp-photo" style="height:36px;background:<?php echo esc_attr( $pg[1] ); ?>;"></div>
							<div class="wpg-lp-photo" style="height:58px;background:<?php echo esc_attr( $pg[5] ); ?>;"></div>
						</div>
						<div class="wpg-lp-col">
							<div class="wpg-lp-photo" style="height:50px;background:<?php echo esc_attr( $pg[2] ); ?>;"></div>
							<div class="wpg-lp-photo" style="height:42px;background:<?php echo esc_attr( $pg[6] ); ?>;"></div>
						</div>
						<div class="wpg-lp-col">
							<div class="wpg-lp-photo" style="height:40px;background:<?php echo esc_attr( $pg[3] ); ?>;"></div>
							<div class="wpg-lp-photo" style="height:52px;background:<?php echo esc_attr( $pg[7] ); ?>;"></div>
						</div>
					</div>

				<?php elseif ( $layout['key'] === 'portrait' ) : ?>
					<!-- 5 tall portrait cells -->
					<div class="wpg-lp-portrait-grid">
						<div class="wpg-lp-photo wpg-lp-photo--tall" style="background:<?php echo esc_attr( $pg[4] ); ?>;"></div>
						<div class="wpg-lp-photo wpg-lp-photo--tall" style="background:<?php echo esc_attr( $pg[0] ); ?>;"></div>
						<div class="wpg-lp-photo wpg-lp-photo--tall" style="background:<?php echo esc_attr( $pg[1] ); ?>;"></div>
						<div class="wpg-lp-photo wpg-lp-photo--tall" style="background:<?php echo esc_attr( $pg[2] ); ?>;"></div>
						<div class="wpg-lp-photo wpg-lp-photo--tall" style="background:<?php echo esc_attr( $pg[5] ); ?>;"></div>
					</div>

				<?php elseif ( $layout['key'] === 'natural' ) : ?>
					<!-- Natural heights — mixed organic sizes -->
					<div class="wpg-lp-natural-grid">
						<div class="wpg-lp-col">
							<div class="wpg-lp-photo" style="height:46px;background:<?php echo esc_attr( $pg[3] ); ?>;"></div>
							<div class="wpg-lp-photo" style="height:32px;background:<?php echo esc_attr( $pg[7] ); ?>;"></div>
						</div>
						<div class="wpg-lp-col">
							<div class="wpg-lp-photo" style="height:28px;background:<?php echo esc_attr( $pg[0] ); ?>;"></div>
							<div class="wpg-lp-photo" style="height:50px;background:<?php echo esc_attr( $pg[4] ); ?>;"></div>
						</div>
						<div class="wpg-lp-col">
							<div class="wpg-lp-photo" style="height:60px;background:<?php echo esc_attr( $pg[1] ); ?>;"></div>
							<div class="wpg-lp-photo" style="height:18px;background:<?php echo esc_attr( $pg[8] ); ?>;"></div>
						</div>
						<div class="wpg-lp-col">
							<div class="wpg-lp-photo" style="height:36px;background:<?php echo esc_attr( $pg[2] ); ?>;"></div>
							<div class="wpg-lp-photo" style="height:42px;background:<?php echo esc_attr( $pg[5] ); ?>;"></div>
						</div>
					</div>

				<?php elseif ( $layout['key'] === 'spotlight' ) : ?>
					<!-- Hero left + 2×2 sub-grid right -->
					<div class="wpg-lp-spotlight-grid">
						<div class="wpg-lp-photo wpg-lp-photo--hero" style="background:<?php echo esc_attr( $pg[1] ); ?>;"></div>
						<div class="wpg-lp-sub-grid">
							<div class="wpg-lp-photo" style="background:<?php echo esc_attr( $pg[4] ); ?>;"></div>
							<div class="wpg-lp-photo" style="background:<?php echo esc_attr( $pg[2] ); ?>;"></div>
							<div class="wpg-lp-photo" style="background:<?php echo esc_attr( $pg[6] ); ?>;"></div>
							<div class="wpg-lp-photo" style="background:<?php echo esc_attr( $pg[3] ); ?>;"></div>
						</div>
					</div>

				<?php elseif ( $layout['key'] === 'filmstrip' ) : ?>
					<!-- Horizontal scrolling row of 16:9 photos -->
					<div class="wpg-lp-filmstrip-row">
						<div class="wpg-lp-photo wpg-lp-photo--film" style="background:<?php echo esc_attr( $pg[7] ); ?>;"></div>
						<div class="wpg-lp-photo wpg-lp-photo--film" style="background:<?php echo esc_attr( $pg[0] ); ?>;"></div>
						<div class="wpg-lp-photo wpg-lp-photo--film" style="background:<?php echo esc_attr( $pg[3] ); ?>;"></div>
						<div class="wpg-lp-photo wpg-lp-photo--film wpg-lp-photo--peek" style="background:<?php echo esc_attr( $pg[5] ); ?>;"></div>
					</div>
				<?php endif; ?>

				<!-- Color badge -->
				<div class="wpg-layout-badge" style="background:<?php echo esc_attr( $layout['color'] ); ?>22;color:<?php echo esc_attr( $layout['color'] ); ?>;border-color:<?php echo esc_attr( $layout['color'] ); ?>44;">
					<?php echo esc_html( $layout['label'] ); ?>
				</div>
			</div>

			<!-- Info -->
			<div class="wpg-layout-info">
				<div class="wpg-layout-name">
					<span class="wpg-layout-dot" style="background:<?php echo esc_attr( $layout['color'] ); ?>;box-shadow:0 0 6px <?php echo esc_attr( $layout['color'] ); ?>88;"></span>
					<?php echo esc_html( $layout['label'] ); ?>
				</div>
				<p class="wpg-layout-desc"><?php echo esc_html( $layout['desc'] ); ?></p>
				<div class="wpg-layout-sc-wrap">
					<code class="wpg-layout-sc"><?php echo esc_html( $layout['shortcode'] ); ?></code>
					<button class="wpg-copy-btn" data-copy="<?php echo esc_attr( $layout['shortcode'] ); ?>" title="<?php esc_attr_e( 'Copy shortcode', 'wallpress-gallery' ); ?>">
						<svg width="14" height="14" fill="none" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" stroke="currentColor" stroke-width="2"/></svg>
					</button>
				</div>
			</div>
		</div>
		<?php endforeach; ?>
	</div>

	<!-- ── Shortcodes Reference ────────────────────────────────────────────── -->
	<style>
		.wpg-sc-ref-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px;margin-top:4px;}
		.wpg-sc-card{background:var(--wpg-admin-card);border:1px solid var(--wpg-admin-border);border-radius:10px;padding:18px 20px;display:flex;flex-direction:column;gap:10px;transition:box-shadow .2s;}
		.wpg-sc-card:hover{box-shadow:0 4px 18px rgba(0,0,0,.35);}
		.wpg-sc-card-header{padding-left:10px;}
		.wpg-sc-tag-badge{display:inline-block;font-family:monospace;font-size:13.5px;font-weight:700;padding:4px 10px;border-radius:6px;letter-spacing:.3px;}
		.wpg-sc-desc{font-size:12.5px;color:var(--wpg-admin-muted);margin:0;line-height:1.6;}
		.wpg-sc-params{display:flex;flex-direction:column;gap:5px;background:var(--wpg-admin-bg);border-radius:7px;padding:10px 12px;}
		.wpg-sc-param-row{display:grid;grid-template-columns:120px 130px 1fr;align-items:baseline;gap:6px;font-size:11.5px;}
		.wpg-sc-param-name{color:#7fff00;background:rgba(127,255,0,.08);padding:1px 6px;border-radius:4px;font-size:11px;white-space:nowrap;}
		.wpg-sc-param-default{color:var(--wpg-admin-muted);font-size:11px;white-space:nowrap;}
		.wpg-sc-param-default em{font-style:normal;color:#aaa;}
		.wpg-sc-param-desc{color:var(--wpg-admin-muted);font-size:11px;line-height:1.4;}
		@media(max-width:600px){.wpg-sc-param-row{grid-template-columns:1fr 1fr;}.wpg-sc-param-desc{grid-column:1/-1;}}
	</style>

	<div class="wpg-section-header" style="margin-top:36px;">
		<h2 class="wpg-section-title wpg-section-title--large"><?php esc_html_e( 'Shortcodes Reference', 'wallpress-gallery' ); ?></h2>
		<p class="wpg-section-desc"><?php esc_html_e( 'All 10 available shortcodes with parameters. Copy any example and paste into a page, post, or widget.', 'wallpress-gallery' ); ?></p>
	</div>

	<?php
	$shortcodes_ref = [
		[
			'tag'     => 'wpg_grid',
			'color'   => '#7fff00',
			'desc'    => 'Main gallery grid — the most flexible shortcode with full filtering, sorting, and layout options.',
			'example' => '[wpg_grid style="uniform" columns="4" count="12"]',
			'params'  => [
				[ 'name' => 'style',      'default' => 'uniform',    'desc' => 'uniform · masonry · portrait · natural · spotlight · filmstrip' ],
				[ 'name' => 'columns',    'default' => '4',          'desc' => 'Number of columns' ],
				[ 'name' => 'count',      'default' => '(per page)', 'desc' => 'Number of images to display' ],
				[ 'name' => 'category',   'default' => '',           'desc' => 'Filter by category slug' ],
				[ 'name' => 'tag',        'default' => '',           'desc' => 'Filter by tag slug' ],
				[ 'name' => 'orderby',    'default' => 'date',       'desc' => 'date · views · rand · title' ],
				[ 'name' => 'featured',   'default' => 'false',      'desc' => 'Show only featured images' ],
				[ 'name' => 'pagination', 'default' => 'true',       'desc' => 'Enable pagination' ],
				[ 'name' => 'text_color', 'default' => '',           'desc' => 'Custom text color (CSS value)' ],
			],
		],
		[
			'tag'     => 'wpg_recent',
			'color'   => '#00d4ff',
			'desc'    => 'Displays the most recently uploaded wallpapers sorted by publish date.',
			'example' => '[wpg_recent columns="4" count="16"]',
			'params'  => [
				[ 'name' => 'columns', 'default' => '4',          'desc' => 'Number of columns' ],
				[ 'name' => 'count',   'default' => '16',         'desc' => 'Number of images to show' ],
				[ 'name' => 'style',   'default' => '(setting)',  'desc' => 'uniform · masonry · portrait · natural · spotlight · filmstrip' ],
			],
		],
		[
			'tag'     => 'wpg_popular',
			'color'   => '#ffa500',
			'desc'    => 'Shows the most popular wallpapers sorted by total view count.',
			'example' => '[wpg_popular columns="4" count="16"]',
			'params'  => [
				[ 'name' => 'columns', 'default' => '4',         'desc' => 'Number of columns' ],
				[ 'name' => 'count',   'default' => '16',        'desc' => 'Number of images to show' ],
				[ 'name' => 'style',   'default' => '(setting)', 'desc' => 'uniform · masonry · portrait · natural · spotlight · filmstrip' ],
			],
		],
		[
			'tag'     => 'wpg_featured',
			'color'   => '#b57bff',
			'desc'    => 'Shows only images marked as Featured — great for hero or highlight sections.',
			'example' => '[wpg_featured columns="4" count="12"]',
			'params'  => [
				[ 'name' => 'columns', 'default' => '4',  'desc' => 'Number of columns' ],
				[ 'name' => 'count',   'default' => '12', 'desc' => 'Number of images to show' ],
			],
		],
		[
			'tag'     => 'wpg_search_bar',
			'color'   => '#ff6ec7',
			'desc'    => 'Renders an interactive search bar with a category dropdown for live filtering.',
			'example' => '[wpg_search_bar placeholder="Search wallpapers…" results_count="12"]',
			'params'  => [
				[ 'name' => 'placeholder',   'default' => 'Search wallpapers…', 'desc' => 'Input field placeholder text' ],
				[ 'name' => 'results_count', 'default' => '12',                 'desc' => 'Number of results per search query' ],
			],
		],
		[
			'tag'     => 'wpg_category_filter',
			'color'   => '#00c9a7',
			'desc'    => 'Tabbed interface with "All" + each category — perfect for a filterable gallery page.',
			'example' => '[wpg_category_filter columns="4" count="12" show_tabs="true"]',
			'params'  => [
				[ 'name' => 'columns',   'default' => '4',    'desc' => 'Number of grid columns' ],
				[ 'name' => 'count',     'default' => '12',   'desc' => 'Images per category tab' ],
				[ 'name' => 'show_tabs', 'default' => 'true', 'desc' => 'Show category tab buttons' ],
			],
		],
		[
			'tag'     => 'wpg_detail',
			'color'   => '#ff7b7b',
			'desc'    => 'Full detail view of a single wallpaper — download button, resolution, view count.',
			'example' => '[wpg_detail id="123"]',
			'params'  => [
				[ 'name' => 'id', 'default' => '(current post)', 'desc' => 'Image post ID — uses current post if omitted' ],
			],
		],
		[
			'tag'     => 'wpg_collections',
			'color'   => '#ffcc00',
			'desc'    => 'Displays category collection cards with cover thumbnails and image counts.',
			'example' => '[wpg_collections columns="5"]',
			'params'  => [
				[ 'name' => 'columns', 'default' => '5', 'desc' => 'Number of collection cards per row' ],
			],
		],
		[
			'tag'     => 'wpg_tags_cloud',
			'color'   => '#4fc3f7',
			'desc'    => 'Tag cloud where font size is proportional to how many images each tag has.',
			'example' => '[wpg_tags_cloud min="1" max_size="22" min_size="12"]',
			'params'  => [
				[ 'name' => 'min',      'default' => '1',  'desc' => 'Minimum image count for a tag to appear' ],
				[ 'name' => 'max_size', 'default' => '22', 'desc' => 'Maximum font size in pixels' ],
				[ 'name' => 'min_size', 'default' => '12', 'desc' => 'Minimum font size in pixels' ],
			],
		],
		[
			'tag'     => 'wpg_category_menu',
			'color'   => '#a5d6a7',
			'desc'    => 'Outputs a WordPress navigation menu — useful for category nav bars in headers.',
			'example' => '[wpg_category_menu menu="Category-Menu"]',
			'params'  => [
				[ 'name' => 'menu', 'default' => 'Category-Menu', 'desc' => 'WordPress menu name or slug to render' ],
			],
		],
	];
	?>

	<div class="wpg-sc-ref-grid">
		<?php foreach ( $shortcodes_ref as $sc ) : ?>
		<div class="wpg-sc-card">

			<div class="wpg-sc-card-header" style="border-left:3px solid <?php echo esc_attr( $sc['color'] ); ?>;">
				<span class="wpg-sc-tag-badge" style="color:<?php echo esc_attr( $sc['color'] ); ?>;background:<?php echo esc_attr( $sc['color'] ); ?>18;">
					[<?php echo esc_html( $sc['tag'] ); ?>]
				</span>
			</div>

			<p class="wpg-sc-desc"><?php echo esc_html( $sc['desc'] ); ?></p>

			<div class="wpg-sc-params">
				<?php foreach ( $sc['params'] as $param ) : ?>
				<div class="wpg-sc-param-row">
					<code class="wpg-sc-param-name"><?php echo esc_html( $param['name'] ); ?></code>
					<span class="wpg-sc-param-default"><?php esc_html_e( 'default:', 'wallpress-gallery' ); ?> <em><?php echo esc_html( $param['default'] ); ?></em></span>
					<span class="wpg-sc-param-desc"><?php echo esc_html( $param['desc'] ); ?></span>
				</div>
				<?php endforeach; ?>
			</div>

			<div class="wpg-layout-sc-wrap">
				<code class="wpg-layout-sc" style="font-size:11.5px;"><?php echo esc_html( $sc['example'] ); ?></code>
				<button class="wpg-copy-btn" data-copy="<?php echo esc_attr( $sc['example'] ); ?>" title="<?php esc_attr_e( 'Copy shortcode', 'wallpress-gallery' ); ?>">
					<svg width="14" height="14" fill="none" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" stroke="currentColor" stroke-width="2"/></svg>
				</button>
			</div>

		</div>
		<?php endforeach; ?>
	</div>

	<!-- ── Two-column: Recent uploads + Top viewed ─────────────────────────── -->
	<div class="wpg-dashboard-cols" style="margin-top:28px;">

		<!-- Recent Uploads -->
		<div class="wpg-dash-section">
			<div class="wpg-dash-section-head">
				<h2 class="wpg-section-title"><?php esc_html_e( 'Recent Uploads', 'wallpress-gallery' ); ?></h2>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpg-manage-images' ) ); ?>" class="wpg-dash-view-all"><?php esc_html_e( 'View all', 'wallpress-gallery' ); ?> &rarr;</a>
			</div>
			<div class="wpg-recent-thumbs">
				<?php if ( $recent_query->have_posts() ) : ?>
					<?php while ( $recent_query->have_posts() ) : $recent_query->the_post(); ?>
						<?php
						$pid   = get_the_ID();
						$thumb = get_the_post_thumbnail_url( $pid, 'medium' );
						$views = (int) get_post_meta( $pid, '_wpg_views', true );
						?>
						<a href="<?php echo esc_url( get_edit_post_link( $pid, 'raw' ) ); ?>" class="wpg-recent-thumb-item" title="<?php echo esc_attr( get_the_title() ); ?>">
							<?php if ( $thumb ) : ?>
								<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" />
							<?php else : ?>
								<div class="wpg-recent-thumb-empty"></div>
							<?php endif; ?>
							<div class="wpg-recent-thumb-overlay">
								<span class="wpg-recent-thumb-title"><?php echo esc_html( wp_trim_words( get_the_title(), 5, '…' ) ); ?></span>
								<span class="wpg-recent-thumb-meta"><?php echo esc_html( number_format_i18n( $views ) ); ?> views</span>
							</div>
						</a>
					<?php endwhile; wp_reset_postdata(); ?>
				<?php else : ?>
					<div class="wpg-empty-state">
						<div class="wpg-empty-icon">&#128444;</div>
						<p><?php esc_html_e( 'No images uploaded yet.', 'wallpress-gallery' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpg-bulk-upload' ) ); ?>" class="wpg-btn wpg-btn-primary">
							<?php esc_html_e( 'Upload Images', 'wallpress-gallery' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Right column: Top viewed + System info -->
		<div style="display:flex;flex-direction:column;gap:20px;">

			<!-- Top 5 most viewed -->
			<div class="wpg-dash-section">
				<div class="wpg-dash-section-head">
					<h2 class="wpg-section-title"><?php esc_html_e( 'Top Viewed', 'wallpress-gallery' ); ?></h2>
				</div>
				<?php if ( ! empty( $top_posts ) ) : ?>
					<div class="wpg-bar-chart">
						<?php foreach ( $top_posts as $i => $p ) :
							$v    = (int) get_post_meta( $p->ID, '_wpg_views', true );
							$pct  = $top_max > 0 ? round( ( $v / $top_max ) * 100 ) : 0;
							$rank = $i + 1;
							?>
							<div class="wpg-bar-row">
								<span class="wpg-bar-rank" style="<?php echo $rank === 1 ? 'color:var(--wpg-admin-accent);' : ''; ?>"><?php echo esc_html( $rank ); ?></span>
								<a href="<?php echo esc_url( get_edit_post_link( $p->ID, 'raw' ) ); ?>"
									class="wpg-bar-label" title="<?php echo esc_attr( get_the_title( $p ) ); ?>">
									<?php echo esc_html( wp_trim_words( get_the_title( $p ), 4, '…' ) ); ?>
								</a>
								<div class="wpg-bar-track">
									<div class="wpg-bar-fill" style="width:<?php echo esc_attr( $pct ); ?>%;"></div>
								</div>
								<span class="wpg-bar-value"><?php echo esc_html( number_format_i18n( $v ) ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p style="color:var(--wpg-admin-muted);font-size:13px;padding:8px 0;">
						<?php esc_html_e( 'No view data yet.', 'wallpress-gallery' ); ?>
					</p>
				<?php endif; ?>
			</div>

			<!-- System Info -->
			<div class="wpg-dash-section wpg-system-info">
				<h2 class="wpg-section-title"><?php esc_html_e( 'System Info', 'wallpress-gallery' ); ?></h2>
				<div class="wpg-sysinfo-rows">
					<div class="wpg-sysinfo-row">
						<span class="wpg-sysinfo-label"><?php esc_html_e( 'Plugin Version', 'wallpress-gallery' ); ?></span>
						<span class="wpg-sysinfo-val wpg-sysinfo-badge wpg-sysinfo-badge--green"><?php echo esc_html( WPG_VERSION ); ?></span>
					</div>
					<div class="wpg-sysinfo-row">
						<span class="wpg-sysinfo-label">WordPress</span>
						<span class="wpg-sysinfo-val"><?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
					</div>
					<div class="wpg-sysinfo-row">
						<span class="wpg-sysinfo-label">PHP</span>
						<span class="wpg-sysinfo-val"><?php echo esc_html( PHP_VERSION ); ?></span>
					</div>
					<div class="wpg-sysinfo-row">
						<span class="wpg-sysinfo-label"><?php esc_html_e( 'GD WebP', 'wallpress-gallery' ); ?></span>
						<?php $gd_webp = function_exists( 'imagetypes' ) && ( imagetypes() & IMG_WEBP ); ?>
						<span class="wpg-sysinfo-val wpg-sysinfo-badge <?php echo $gd_webp ? 'wpg-sysinfo-badge--green' : 'wpg-sysinfo-badge--red'; ?>">
							<?php echo $gd_webp ? esc_html__( 'Supported', 'wallpress-gallery' ) : esc_html__( 'Not supported', 'wallpress-gallery' ); ?>
						</span>
					</div>
					<div class="wpg-sysinfo-row">
						<span class="wpg-sysinfo-label">Imagick</span>
						<?php $has_imagick = class_exists( 'Imagick' ); ?>
						<span class="wpg-sysinfo-val wpg-sysinfo-badge <?php echo $has_imagick ? 'wpg-sysinfo-badge--green' : 'wpg-sysinfo-badge--yellow'; ?>">
							<?php echo $has_imagick ? esc_html__( 'Available', 'wallpress-gallery' ) : esc_html__( 'Not installed', 'wallpress-gallery' ); ?>
						</span>
					</div>
					<div class="wpg-sysinfo-row">
						<span class="wpg-sysinfo-label">Divi Builder</span>
						<?php $has_divi = class_exists( 'ET_Builder_Module' ); ?>
						<span class="wpg-sysinfo-val wpg-sysinfo-badge <?php echo $has_divi ? 'wpg-sysinfo-badge--green' : 'wpg-sysinfo-badge--yellow'; ?>">
							<?php echo $has_divi ? esc_html__( 'Active', 'wallpress-gallery' ) : esc_html__( 'Not detected', 'wallpress-gallery' ); ?>
						</span>
					</div>
				</div>
			</div>

		</div><!-- right col -->
	</div><!-- .wpg-dashboard-cols -->

</div><!-- .wpg-admin-wrap -->

<script>
(function(){
	document.querySelectorAll('.wpg-copy-btn').forEach(function(btn){
		btn.addEventListener('click', function(){
			var text = btn.getAttribute('data-copy');
			if(!text){ return; }
			navigator.clipboard.writeText(text).then(function(){
				btn.classList.add('wpg-copy-btn--done');
				btn.innerHTML = '<svg width="14" height="14" fill="none" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="#7fff00" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
				setTimeout(function(){
					btn.classList.remove('wpg-copy-btn--done');
					btn.innerHTML = '<svg width="14" height="14" fill="none" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" stroke="currentColor" stroke-width="2"/></svg>';
				}, 2000);
			});
		});
	});
})();
</script>
