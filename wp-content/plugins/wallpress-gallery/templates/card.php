<?php
/**
 * Template: Single Image Card (unified design — all layouts)
 *
 * Variables available:
 *   $post    WP_Post object
 *   $style   string — 'uniform'|'masonry'|'portrait'|'natural'|'spotlight'|'filmstrip'|'clean'
 *   $columns int
 *   $index   int — 0-based position in grid (used by spotlight)
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id    = $post->ID;
$thumb      = get_the_post_thumbnail_url( $post_id, 'large' );
$title      = get_the_title( $post );
$link       = get_permalink( $post );
$file_4k    = get_post_meta( $post_id, '_wpg_file_4k', true );
$lazy       = get_option( 'wpg_lazy_load', 1 ) ? 'lazy' : 'eager';
// Provide intrinsic dimensions so browser reserves correct space before image loads
// (prevents masonry span overlap on lazy-loaded images)
$thumb_id   = get_post_thumbnail_id( $post_id );
$thumb_src  = $thumb_id ? wp_get_attachment_image_src( $thumb_id, 'large' ) : null;
$img_w      = $thumb_src ? (int) $thumb_src[1] : 0;
$img_h      = $thumb_src ? (int) $thumb_src[2] : 0;
$index    = isset( $index ) ? (int) $index : 0;
$style    = isset( $style ) ? $style : 'uniform';

// Spotlight: first card gets hero class
$extra_class = '';
if ( 'spotlight' === $style && 0 === $index ) {
	$extra_class = ' wpg-card--hero';
}

// Skip if no thumbnail
if ( ! $thumb ) {
	return;
}

// Fetch first category for subtitle (all layouts)
$cats     = wp_get_post_terms( $post_id, 'wpg_category', [ 'number' => 1 ] );
$card_cat = ( ! empty( $cats ) && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';
?>
<article class="wpg-card<?php echo esc_attr( $extra_class ); ?>" data-id="<?php echo esc_attr( $post_id ); ?>">
	<a href="<?php echo esc_url( $link ); ?>" class="wpg-card-link">
		<div class="wpg-card-img-box">
			<img
				src="<?php echo esc_url( $thumb ); ?>"
				alt="<?php echo esc_attr( $title ); ?>"
				loading="<?php echo esc_attr( $lazy ); ?>"
				<?php if ( $img_w && $img_h ) : ?>
				width="<?php echo esc_attr( $img_w ); ?>"
				height="<?php echo esc_attr( $img_h ); ?>"
				<?php endif; ?>
			/>
			<?php if ( $file_4k && get_option( 'wpg_enable_4k_download', 1 ) ) : ?>
				<a class="wpg-card-dl"
					href="<?php echo esc_url( $file_4k ); ?>"
					download
					data-id="<?php echo esc_attr( $post_id ); ?>"
					data-type="4k"
					rel="nofollow"
					onclick="event.stopPropagation()">
					<svg width="14" height="14" fill="none" viewBox="0 0 24 24">
						<path d="M12 3v13m0 0l-4-4m4 4l4-4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M5 21h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
					</svg>
				</a>
			<?php endif; ?>
		</div>
		<div class="wpg-card-info">
			<h3 class="wpg-card-name"><?php echo esc_html( $title ); ?></h3>
			<?php if ( $card_cat ) : ?>
				<p class="wpg-card-cat"><?php echo esc_html( $card_cat ); ?></p>
			<?php endif; ?>
		</div>
	</a>
</article>
