<?php
/**
 * Template: Grid wrapper
 *
 * Variables available:
 *   $query   WP_Query
 *   $atts    array — shortcode attributes
 *   $style   string — passed from shortcode via $atts['style']
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$columns      = intval( $atts['columns'] );
$valid_styles = [ 'uniform', 'masonry', 'portrait', 'natural', 'spotlight', 'filmstrip' ];
$style        = isset( $atts['style'] ) && in_array( $atts['style'], $valid_styles, true )
	? $atts['style']
	: 'uniform';

$grid_class  = 'wpg-grid-' . $style;
$text_color  = isset( $atts['text_color'] ) && $atts['text_color']
	? sanitize_hex_color( $atts['text_color'] )
	: '';
$extra_style = $text_color ? '--wpg-card-text:' . $text_color . ';' : '';
?>
<?php
$pagination    = isset( $atts['pagination'] ) ? $atts['pagination'] : 'true';
$max_pages     = (int) $query->max_num_pages;
$current_page  = max( 1, (int) ( $query->query_vars['paged'] ?? 1 ) );
?>
<div class="wpg-grid-wrap" data-pagination="<?php echo esc_attr( $pagination ); ?>">
	<div class="<?php echo esc_attr( $grid_class ); ?>"
		style="--wpg-cols:<?php echo esc_attr( $columns ); ?>;<?php echo esc_attr( $extra_style ); ?>"
		data-style="<?php echo esc_attr( $style ); ?>"
		data-columns="<?php echo esc_attr( $columns ); ?>"
		data-count="<?php echo esc_attr( (int) $query->query_vars['posts_per_page'] ); ?>"
		data-orderby="<?php echo esc_attr( $atts['orderby'] ); ?>"
		data-category="<?php echo esc_attr( $atts['category'] ); ?>"
		data-tag="<?php echo esc_attr( $atts['tag'] ); ?>">

		<?php if ( $query->have_posts() ) : ?>
			<?php $index = 0; ?>
			<?php while ( $query->have_posts() ) : $query->the_post(); ?>
				<?php
				$post = get_post();
				include WPG_PATH . 'templates/card.php';
				$index++;
				?>
			<?php endwhile; wp_reset_postdata(); ?>
		<?php else : ?>
			<p class="wpg-no-results">
				<?php esc_html_e( 'No images found.', 'wallpress-gallery' ); ?>
			</p>
		<?php endif; ?>
	</div>

	<?php if ( $pagination === 'numbered' && $max_pages > 1 ) : ?>
		<div class="wpg-pagination">
			<?php for ( $i = 1; $i <= $max_pages; $i++ ) : ?>
				<button class="wpg-page-btn<?php echo $i === $current_page ? ' active' : ''; ?>"
					data-page="<?php echo esc_attr( $i ); ?>">
					<?php echo esc_html( $i ); ?>
				</button>
			<?php endfor; ?>
		</div>
	<?php elseif ( $pagination !== 'numbered' && $pagination !== 'false' && $max_pages > 1 ) : ?>
		<div class="wpg-infinite-sentinel" aria-hidden="true">
			<span class="wpg-infinite-spinner"></span>
		</div>
	<?php endif; ?>
</div>
