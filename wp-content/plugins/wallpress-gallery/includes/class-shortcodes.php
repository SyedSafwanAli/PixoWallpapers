<?php
/**
 * Shortcodes
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPG_Shortcodes {

	public static function init() {
		add_shortcode( 'wpg_grid',            [ __CLASS__, 'sc_grid' ] );
		add_shortcode( 'wpg_recent',          [ __CLASS__, 'sc_recent' ] );
		add_shortcode( 'wpg_popular',         [ __CLASS__, 'sc_popular' ] );
		add_shortcode( 'wpg_featured',        [ __CLASS__, 'sc_featured' ] );
		add_shortcode( 'wpg_search_bar',      [ __CLASS__, 'sc_search_bar' ] );
		add_shortcode( 'wpg_category_filter', [ __CLASS__, 'sc_category_filter' ] );
		add_shortcode( 'wpg_filter',          [ __CLASS__, 'sc_filter' ] );
		add_shortcode( 'wpg_detail',          [ __CLASS__, 'sc_detail' ] );
		add_shortcode( 'wpg_collections',     [ __CLASS__, 'sc_collections' ] );
		add_shortcode( 'wpg_tags_cloud',        [ __CLASS__, 'sc_tags_cloud' ] );
		add_shortcode( 'wpg_category_menu',     [ __CLASS__, 'sc_category_menu' ] );
		add_shortcode( 'wpg_category_page',     [ __CLASS__, 'sc_category_page' ] );
		add_shortcode( 'wpg_tag_page',          [ __CLASS__, 'sc_tag_page' ] );
		add_shortcode( 'wpg_category_carousel', [ __CLASS__, 'sc_category_carousel' ] );
		add_shortcode( 'wpg_categories_grid',   [ __CLASS__, 'sc_categories_grid' ] );
		add_shortcode( 'wpg_sidebar',           [ __CLASS__, 'sc_sidebar' ] );
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Build default atts merged with user atts.
	 */
	private static function default_atts( $user_atts, $defaults ) {
		return shortcode_atts( $defaults, $user_atts );
	}

	/**
	 * Build WP_Query args from grid shortcode atts.
	 */
	private static function build_query_args( $atts ) {
		$per_page = absint( get_option( 'wpg_per_page', 16 ) );
		$count    = $atts['count'] !== '' ? absint( $atts['count'] ) : $per_page;

		$args = [
			'post_type'      => 'wpg_image',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'paged'          => max( 1, get_query_var( 'paged' ) ),
		];

		// Orderby
		switch ( $atts['orderby'] ) {
			case 'views':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_wpg_views';
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

		// Featured filter
		if ( $atts['featured'] === 'true' ) {
			$args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery
				[
					'key'   => '_wpg_is_featured',
					'value' => '1',
				],
			];
		}

		// Category
		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery
				[
					'taxonomy' => 'wpg_category',
					'field'    => 'slug',
					'terms'    => sanitize_key( $atts['category'] ),
				],
			];
		}

		// Tag
		if ( ! empty( $atts['tag'] ) ) {
			$tag_query = [
				'taxonomy' => 'wpg_tag',
				'field'    => 'slug',
				'terms'    => sanitize_key( $atts['tag'] ),
			];
			if ( isset( $args['tax_query'] ) ) {
				$args['tax_query'][]         = $tag_query;
				$args['tax_query']['relation'] = 'AND';
			} else {
				$args['tax_query'] = [ $tag_query ]; // phpcs:ignore WordPress.DB.SlowDBQuery
			}
		}

		return $args;
	}

	// ── [wpg_grid] ────────────────────────────────────────────────────────────
	public static function sc_grid( $user_atts ) {
		$default_grid = sanitize_key( get_option( 'wpg_default_grid', 'uniform' ) );
		$per_page     = absint( get_option( 'wpg_per_page', 12 ) );

		$atts = self::default_atts( $user_atts, [
			'style'      => $default_grid,
			'columns'    => 4,
			'count'      => $per_page,
			'category'   => '',
			'tag'        => '',
			'orderby'    => 'date',
			'featured'   => 'false',
			'pagination' => 'true',
			'text_color' => '',
		] );

		$atts['columns'] = absint( $atts['columns'] );
		$valid_styles    = [ 'uniform', 'masonry', 'portrait', 'natural', 'spotlight', 'filmstrip' ];
		$atts['style']   = in_array( $atts['style'], $valid_styles, true ) ? $atts['style'] : 'uniform';

		$query_args = self::build_query_args( $atts );
		$query      = new WP_Query( $query_args );

		ob_start();
		include WPG_PATH . 'templates/grid.php';
		return ob_get_clean();
	}

	// ── [wpg_recent] ─────────────────────────────────────────────────────────
	public static function sc_recent( $user_atts ) {
		$default_grid    = sanitize_key( get_option( 'wpg_default_grid', 'uniform' ) );
		$atts            = shortcode_atts( [ 'columns' => 4, 'count' => 16, 'style' => $default_grid ], $user_atts );
		$atts['orderby'] = 'date';
		$atts['featured']   = 'false';
		$atts['pagination'] = 'true';
		$atts['category']   = '';
		$atts['tag']        = '';
		return self::sc_grid( $atts );
	}

	// ── [wpg_popular] ────────────────────────────────────────────────────────
	public static function sc_popular( $user_atts ) {
		$default_grid    = sanitize_key( get_option( 'wpg_default_grid', 'uniform' ) );
		$atts            = shortcode_atts( [ 'columns' => 4, 'count' => 16, 'style' => $default_grid ], $user_atts );
		$atts['orderby'] = 'views';
		$atts['featured']   = 'false';
		$atts['pagination'] = 'true';
		$atts['category']   = '';
		$atts['tag']        = '';
		return self::sc_grid( $atts );
	}

	// ── [wpg_featured] ───────────────────────────────────────────────────────
	public static function sc_featured( $user_atts ) {
		$atts            = shortcode_atts( [ 'columns' => 4, 'count' => 12 ], $user_atts );
		$atts['orderby'] = 'date';
		$atts['featured']   = 'true';
		$atts['pagination'] = 'true';
		$atts['category']   = '';
		$atts['tag']        = '';
		$atts['style']      = sanitize_key( get_option( 'wpg_default_grid', 'uniform' ) );
		return self::sc_grid( $atts );
	}

	// ── [wpg_search_bar] ─────────────────────────────────────────────────────
	public static function sc_search_bar( $user_atts ) {
		$atts = shortcode_atts( [
			'placeholder'   => __( 'Search wallpapers…', 'wallpress-gallery' ),
			'results_count' => 12,
		], $user_atts );

		// Fetch all categories for the dropdown
		$cats = get_terms( [
			'taxonomy'   => 'wpg_category',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		] );

		ob_start();
		?>
		<div class="wpg-search-wrap">
			<div class="wpg-searchbar">

				<?php if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) : ?>
				<!-- Category picker -->
				<div class="wpg-sb-cat-picker">
					<button class="wpg-sb-cat-btn" type="button" aria-haspopup="listbox" aria-expanded="false">
						<span class="wpg-sb-cat-label"><?php esc_html_e( 'All', 'wallpress-gallery' ); ?></span>
						<svg class="wpg-sb-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none">
							<path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</button>
					<ul class="wpg-sb-cat-dropdown" role="listbox">
						<li class="wpg-sb-cat-opt" role="option" data-cat="" data-label="<?php esc_attr_e( 'All', 'wallpress-gallery' ); ?>">
							<?php esc_html_e( 'All', 'wallpress-gallery' ); ?>
						</li>
						<?php foreach ( $cats as $term ) : ?>
						<li class="wpg-sb-cat-opt" role="option"
							data-cat="<?php echo esc_attr( $term->slug ); ?>"
							data-label="<?php echo esc_attr( $term->name ); ?>">
							<?php echo esc_html( $term->name ); ?>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<div class="wpg-sb-divider"></div>
				<?php endif; ?>

				<!-- Search input -->
				<input type="search"
					class="wpg-search-input wpg-sb-input"
					placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
					autocomplete="off"
					data-count="<?php echo absint( $atts['results_count'] ); ?>"
					data-cat="" />

				<!-- Search icon button -->
				<button class="wpg-sb-search-btn" type="button" aria-label="<?php esc_attr_e( 'Search', 'wallpress-gallery' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none">
						<circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2.2"/>
						<path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
					</svg>
				</button>

			</div><!-- .wpg-searchbar -->
			<div class="wpg-search-results" role="listbox" aria-label="<?php esc_attr_e( 'Search results', 'wallpress-gallery' ); ?>"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	// ── [wpg_category_filter] ────────────────────────────────────────────────
	public static function sc_category_filter( $user_atts ) {
		$atts = shortcode_atts( [
			'columns'   => 4,
			'count'     => 12,
			'show_tabs' => 'true',
		], $user_atts );

		$terms = get_terms( [
			'taxonomy'   => 'wpg_category',
			'hide_empty' => true,
		] );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '<p class="wpg-no-results">' . esc_html__( 'No categories found.', 'wallpress-gallery' ) . '</p>';
		}

		// Load initial "All" grid
		$initial_atts           = $atts;
		$initial_atts['orderby']    = 'date';
		$initial_atts['featured']   = 'false';
		$initial_atts['pagination'] = 'true';
		$initial_atts['category']   = '';
		$initial_atts['tag']        = '';
		$initial_atts['style']      = sanitize_key( get_option( 'wpg_default_grid', 'uniform' ) );

		ob_start();
		?>
		<div class="wpg-category-filter-wrap"
			data-columns="<?php echo absint( $atts['columns'] ); ?>"
			data-count="<?php echo absint( $atts['count'] ); ?>"
			data-orderby="date"
			data-style="<?php echo esc_attr( $initial_atts['style'] ); ?>"
			data-pagination="load_more">
			<?php if ( $atts['show_tabs'] === 'true' ) : ?>
			<div class="wpg-tabs" role="tablist">
				<button class="wpg-tab-btn active"
					data-cat=""
					data-columns="<?php echo absint( $atts['columns'] ); ?>"
					data-count="<?php echo absint( $atts['count'] ); ?>"
					role="tab" aria-selected="true">
					<?php esc_html_e( 'All', 'wallpress-gallery' ); ?>
				</button>
				<?php foreach ( $terms as $term ) : ?>
					<button class="wpg-tab-btn"
						data-cat="<?php echo esc_attr( $term->slug ); ?>"
						data-columns="<?php echo absint( $atts['columns'] ); ?>"
						data-count="<?php echo absint( $atts['count'] ); ?>"
						role="tab" aria-selected="false">
						<?php echo esc_html( $term->name ); ?>
					</button>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<div class="wpg-grid-wrap wpg-filter-target">
				<?php echo self::sc_grid( $initial_atts ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// ── [wpg_filter] ─────────────────────────────────────────────────────────
	// Usage: [wpg_filter columns="4" count="16" style="masonry"]
	// Shows 4 tabs: RECENT | POPULAR | FEATURED | RANDOM
	public static function sc_filter( $user_atts ) {
		$default_grid = sanitize_key( get_option( 'wpg_default_grid', 'uniform' ) );
		$per_page     = absint( get_option( 'wpg_per_page', 16 ) );

		$atts = shortcode_atts( [
			'columns' => 4,
			'count'   => $per_page,
			'style'   => $default_grid,
		], $user_atts );

		$atts['columns'] = absint( $atts['columns'] );
		$atts['count']   = absint( $atts['count'] );
		$valid_styles    = [ 'uniform', 'masonry', 'portrait', 'natural', 'spotlight', 'filmstrip' ];
		$atts['style']   = in_array( $atts['style'], $valid_styles, true ) ? $atts['style'] : 'uniform';

		// Tabs definition
		$tabs = [
			[
				'key'      => 'recent',
				'label'    => __( 'RECENT', 'wallpress-gallery' ),
				'orderby'  => 'date',
				'featured' => 'false',
				'icon'     => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
			],
			[
				'key'      => 'popular',
				'label'    => __( 'POPULAR', 'wallpress-gallery' ),
				'orderby'  => 'views',
				'featured' => 'false',
				'icon'     => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 2C8 8 4 10 4 14a8 8 0 0016 0c0-4-4-6-8-12z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M12 14c-1.5 0-3 1-3 3s1.5 3 3 3 3-1 3-3-1.5-3-3-3z" stroke="currentColor" stroke-width="2"/></svg>',
			],
			[
				'key'      => 'featured',
				'label'    => __( 'FEATURED', 'wallpress-gallery' ),
				'orderby'  => 'date',
				'featured' => 'true',
				'icon'     => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 2l3 6.5L22 9.5l-5 4.9 1.18 6.88L12 18l-6.18 3.28L7 14.4 2 9.5l7-.97z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
			],
			[
				'key'      => 'random',
				'label'    => __( 'RANDOM', 'wallpress-gallery' ),
				'orderby'  => 'rand',
				'featured' => 'false',
				'icon'     => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M16 3h5v5M4 20l17-17M21 16v5h-5M15 15l6 6M4 4l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
			],
		];

		// Initial grid: RECENT (date)
		$initial_atts = [
			'columns'    => $atts['columns'],
			'count'      => $atts['count'],
			'style'      => $atts['style'],
			'orderby'    => 'date',
			'featured'   => 'false',
			'pagination' => 'true',
			'category'   => '',
			'tag'        => '',
		];

		ob_start();
		?>
		<div class="wpg-filter-module"
			data-columns="<?php echo esc_attr( $atts['columns'] ); ?>"
			data-count="<?php echo esc_attr( $atts['count'] ); ?>"
			data-style="<?php echo esc_attr( $atts['style'] ); ?>">

			<div class="wpg-filter-tabs-wrap">
				<div class="wpg-filter-tabs" role="tablist">
					<?php foreach ( $tabs as $i => $tab ) : ?>
					<button class="wpg-filter-tab<?php echo $i === 0 ? ' active' : ''; ?>"
						data-orderby="<?php echo esc_attr( $tab['orderby'] ); ?>"
						data-featured="<?php echo esc_attr( $tab['featured'] ); ?>"
						role="tab"
						aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>">
						<?php echo $tab['icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span><?php echo esc_html( $tab['label'] ); ?></span>
					</button>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="wpg-grid-wrap wpg-filter-target">
				<?php echo self::sc_grid( $initial_atts ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// ── [wpg_detail] ─────────────────────────────────────────────────────────
	// Usage:
	//   [wpg_detail]            — auto-detects from current queried wpg_image post
	//   [wpg_detail id="123"]   — by post ID
	//   [wpg_detail slug="dark-hero"] — by post slug
	public static function sc_detail( $user_atts ) {
		$atts = shortcode_atts( [ 'id' => '', 'slug' => '' ], $user_atts );

		$post_id = 0;

		if ( $atts['id'] ) {
			$post_id = absint( $atts['id'] );
		} elseif ( $atts['slug'] ) {
			$found = get_page_by_path( sanitize_title( wp_unslash( $atts['slug'] ) ), OBJECT, 'wpg_image' );
			$post_id = $found ? $found->ID : 0;
		} else {
			// Auto-detect: queried object must be a wpg_image post
			$queried = get_queried_object();
			if ( $queried instanceof WP_Post && $queried->post_type === 'wpg_image' ) {
				$post_id = $queried->ID;
			} else {
				// Fallback: check if current loop post is wpg_image
				$loop_id = get_the_ID();
				if ( $loop_id && get_post_type( $loop_id ) === 'wpg_image' ) {
					$post_id = $loop_id;
				}
			}
		}

		if ( ! $post_id ) {
			return '';
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'wpg_image' || $post->post_status !== 'publish' ) {
			return '';
		}

		// Views are tracked via AJAX (public-script.js → wpg_increment_views)
		// to avoid counting bots/crawlers and page refreshes incorrectly.

		ob_start();
		include WPG_PATH . 'templates/detail.php';
		return ob_get_clean();
	}

	// ── [wpg_collections] ────────────────────────────────────────────────────
	public static function sc_collections( $user_atts ) {
		$atts  = shortcode_atts( [ 'columns' => 5 ], $user_atts );
		$terms = get_terms( [
			'taxonomy'   => 'wpg_category',
			'hide_empty' => false,
		] );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '<p class="wpg-no-results">' . esc_html__( 'No collections found.', 'wallpress-gallery' ) . '</p>';
		}

		ob_start();
		?>
		<div class="wpg-collections-grid"
			style="--wpg-cols:<?php echo absint( $atts['columns'] ); ?>;">
			<?php foreach ( $terms as $term ) :
				$thumb_id  = get_term_meta( $term->term_id, 'wpg_cat_thumbnail', true );
				$thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';
				$term_url  = get_term_link( $term );
				if ( is_wp_error( $term_url ) ) {
					continue;
				}
				?>
				<a class="wpg-collection-card" href="<?php echo esc_url( $term_url ); ?>">
					<?php if ( $thumb_url ) : ?>
						<img src="<?php echo esc_url( $thumb_url ); ?>"
							alt="<?php echo esc_attr( $term->name ); ?>"
							loading="lazy" />
					<?php else : ?>
						<div class="wpg-collection-placeholder"></div>
					<?php endif; ?>
					<div class="wpg-collection-overlay">
						<span class="wpg-collection-name"><?php echo esc_html( $term->name ); ?></span>
						<span class="wpg-collection-count">
							<?php echo esc_html( sprintf(
								/* translators: %d: number of images */
								_n( '%d image', '%d images', $term->count, 'wallpress-gallery' ),
								$term->count
							) ); ?>
						</span>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	// ── [wpg_tags_cloud] ─────────────────────────────────────────────────────
	public static function sc_tags_cloud( $user_atts ) {
		$atts = shortcode_atts( [
			'min'      => 1,
			'max_size' => 22,
			'min_size' => 12,
			'limit'    => 30,
		], $user_atts );

		$tags = get_terms( [
			'taxonomy'   => 'wpg_tag',
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => absint( $atts['limit'] ),
		] );

		if ( is_wp_error( $tags ) || empty( $tags ) ) {
			return '<p class="wpg-no-results">' . esc_html__( 'No tags found.', 'wallpress-gallery' ) . '</p>';
		}

		$min_count = (int) $atts['min'];
		$tags      = array_filter( $tags, function( $t ) use ( $min_count ) {
			return $t->count >= $min_count;
		} );

		if ( empty( $tags ) ) {
			return '';
		}

		$counts   = array_column( (array) $tags, 'count' );
		$max_cnt  = max( $counts );
		$min_cnt  = min( $counts );
		$min_size = absint( $atts['min_size'] );
		$max_size = absint( $atts['max_size'] );
		$spread   = $max_cnt - $min_cnt;

		$total_shown = count( $tags );
		ob_start();
		?>
		<div class="wpg-tags-cloud-wrap">
			<div class="wpg-tags-cloud-heading">
				<h3 class="wpg-tags-cloud-title"><?php esc_html_e( 'Browse by Tags', 'wallpress-gallery' ); ?></h3>
				<span class="wpg-tags-cloud-badge"><?php echo absint( $total_shown ); ?> <?php esc_html_e( 'tags', 'wallpress-gallery' ); ?></span>
			</div>
			<div class="wpg-tags-cloud">
				<?php foreach ( $tags as $tag ) :
					$size = $spread > 0
						? $min_size + ( ( $tag->count - $min_cnt ) / $spread ) * ( $max_size - $min_size )
						: ( $min_size + $max_size ) / 2;
					$tag_url = get_term_link( $tag );
					if ( is_wp_error( $tag_url ) ) {
						continue;
					}
					?>
					<a class="wpg-tag-link"
						href="<?php echo esc_url( $tag_url ); ?>"
						style="font-size:<?php echo esc_attr( round( $size, 1 ) ); ?>px;">
						<?php echo esc_html( $tag->name ); ?>
						<sup><?php echo absint( $tag->count ); ?></sup>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
	// ── [wpg_category_page] ──────────────────────────────────────────────────
	// Usage:
	//   [wpg_category_page]                  — auto-detects current wpg_category archive
	//   [wpg_category_page category="cars"]  — explicit category slug
	//   [wpg_category_page columns="4" count="16" style="masonry"]
	public static function sc_category_page( $user_atts ) {
		$atts = shortcode_atts( [
			'category' => '',
			'columns'  => 4,
			'count'    => absint( get_option( 'wpg_per_page', 16 ) ),
			'style'    => 'masonry',
		], $user_atts );

		// Resolve term: explicit slug → queried object
		if ( $atts['category'] ) {
			$term = get_term_by( 'slug', sanitize_key( $atts['category'] ), 'wpg_category' );
		} else {
			$queried = get_queried_object();
			$term    = ( $queried instanceof WP_Term && $queried->taxonomy === 'wpg_category' )
				? $queried
				: null;
		}

		if ( ! $term || is_wp_error( $term ) ) {
			return '<p class="wpg-no-results">' . esc_html__( 'Category not found.', 'wallpress-gallery' ) . '</p>';
		}

		// All categories for pill navigation bar
		$all_cats = get_terms( [
			'taxonomy'   => 'wpg_category',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		] );

		// Grid atts for this category
		$valid_styles = [ 'uniform', 'masonry', 'portrait', 'natural', 'spotlight', 'filmstrip' ];
		$grid_style   = in_array( $atts['style'], $valid_styles, true ) ? $atts['style'] : 'masonry';

		$grid_atts = [
			'style'      => $grid_style,
			'columns'    => absint( $atts['columns'] ),
			'count'      => absint( $atts['count'] ),
			'category'   => $term->slug,
			'tag'        => '',
			'orderby'    => 'date',
			'featured'   => 'false',
			'pagination' => 'true',
			'text_color' => '',
		];

		ob_start();
		?>
		<div class="wpg-cat-page">

			<?php if ( ! is_wp_error( $all_cats ) && ! empty( $all_cats ) ) : ?>
			<div class="wpg-cat-pills-bar-wrap">
				<div class="wpg-cat-pills-bar">
					<?php foreach ( $all_cats as $cat ) :
						$is_active = ( $cat->term_id === $term->term_id );
						$cat_url   = get_term_link( $cat );
						if ( is_wp_error( $cat_url ) ) { continue; }
					?>
					<a href="<?php echo esc_url( $cat_url ); ?>"
					   class="wpg-cat-pill<?php echo $is_active ? ' active' : ''; ?>">
						<?php echo esc_html( $cat->name ); ?>
					</a>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<div class="wpg-cat-heading">
				<h1 class="wpg-cat-title">
					<?php echo esc_html( $term->name ); ?>
					<span class="wpg-cat-subtitle"><?php esc_html_e( 'Wallpapers', 'wallpress-gallery' ); ?></span>
				</h1>
				<span class="wpg-cat-count">
					<?php echo esc_html( sprintf(
						/* translators: %d: number of images */
						_n( '%d image', '%d images', $term->count, 'wallpress-gallery' ),
						$term->count
					) ); ?>
				</span>
			</div>

			<?php if ( ! empty( $term->description ) ) : ?>
			<p class="wpg-cat-description"><?php echo esc_html( $term->description ); ?></p>
			<?php endif; ?>

			<?php echo self::sc_grid( $grid_atts ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		</div>
		<?php
		return ob_get_clean();
	}

	// ── [wpg_tag_page] ───────────────────────────────────────────────────────
	// Usage:
	//   [wpg_tag_page]                 — auto-detects current wpg_tag archive
	//   [wpg_tag_page tag="nature"]    — explicit tag slug
	//   [wpg_tag_page columns="4" count="16" style="masonry"]
	public static function sc_tag_page( $user_atts ) {
		$atts = shortcode_atts( [
			'tag'     => '',
			'columns' => 4,
			'count'   => absint( get_option( 'wpg_per_page', 16 ) ),
			'style'   => 'masonry',
		], $user_atts );

		// Resolve term: explicit slug → queried object
		if ( $atts['tag'] ) {
			$term = get_term_by( 'slug', sanitize_key( $atts['tag'] ), 'wpg_tag' );
		} else {
			$queried = get_queried_object();
			$term    = ( $queried instanceof WP_Term && $queried->taxonomy === 'wpg_tag' )
				? $queried
				: null;
		}

		if ( ! $term || is_wp_error( $term ) ) {
			return '<p class="wpg-no-results">' . esc_html__( 'Tag not found.', 'wallpress-gallery' ) . '</p>';
		}

		$valid_styles = [ 'uniform', 'masonry', 'portrait', 'natural', 'spotlight', 'filmstrip' ];
		$grid_style   = in_array( $atts['style'], $valid_styles, true ) ? $atts['style'] : 'masonry';

		$grid_atts = [
			'style'      => $grid_style,
			'columns'    => absint( $atts['columns'] ),
			'count'      => absint( $atts['count'] ),
			'category'   => '',
			'tag'        => $term->slug,
			'orderby'    => 'date',
			'featured'   => 'false',
			'pagination' => 'true',
			'text_color' => '',
		];

		ob_start();
		?>
		<div class="wpg-cat-page wpg-tag-page">

			<div class="wpg-cat-heading">
				<h1 class="wpg-cat-title">
					<?php echo esc_html( $term->name ); ?>
					<span class="wpg-cat-subtitle"><?php esc_html_e( 'Wallpapers', 'wallpress-gallery' ); ?></span>
				</h1>
				<span class="wpg-cat-count">
					<?php echo esc_html( sprintf(
						/* translators: %d: number of images */
						_n( '%d image', '%d images', $term->count, 'wallpress-gallery' ),
						$term->count
					) ); ?>
				</span>
			</div>

			<?php echo self::sc_grid( $grid_atts ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		</div>
		<?php
		return ob_get_clean();
	}

	// ── [wpg_category_menu] ──────────────────────────────────────────────────
	public static function sc_category_menu( $user_atts ) {
		$atts = shortcode_atts( [
			'menu' => 'Category-Menu',
		], $user_atts );

		if ( ! has_nav_menu( '' ) && ! wp_get_nav_menu_object( $atts['menu'] ) ) {
			return '';
		}

		ob_start();
		wp_nav_menu( [
			'menu'            => $atts['menu'],
			'container'       => 'nav',
			'container_class' => 'wpg-cat-menu-wrap',
			'menu_class'      => 'wpg-cat-menu',
			'depth'           => 1,
			'fallback_cb'     => false,
			'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
		] );
		return ob_get_clean();
	}

	// ── [wpg_category_carousel] ──────────────────────────────────────────────
	// Usage:
	//   [wpg_category_carousel]
	//   [wpg_category_carousel limit="15" orderby="name"]
	public static function sc_category_carousel( $user_atts ) {
		$atts = shortcode_atts( [
			'limit'   => 20,
			'orderby' => 'count',
			'order'   => 'DESC',
		], $user_atts );

		$allowed_orderby = [ 'count', 'name', 'slug', 'term_id' ];
		$orderby = in_array( $atts['orderby'], $allowed_orderby, true ) ? $atts['orderby'] : 'count';

		// Only show categories marked "show in carousel"; fall back to all if none marked
		$carousel_args = [
			'taxonomy'   => 'wpg_category',
			'hide_empty' => true,
			'orderby'    => $orderby,
			'order'      => strtoupper( $atts['order'] ) === 'ASC' ? 'ASC' : 'DESC',
			'number'     => absint( $atts['limit'] ),
			'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery
				[
					'key'   => 'wpg_cat_in_carousel',
					'value' => '1',
				],
			],
		];
		$cats = get_terms( $carousel_args );

		// Fallback: if no categories are marked, show all
		if ( is_wp_error( $cats ) || empty( $cats ) ) {
			unset( $carousel_args['meta_query'] );
			$cats = get_terms( $carousel_args );
		}

		if ( is_wp_error( $cats ) || empty( $cats ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="wpg-cat-carousel-wrap">
			<div class="wpg-cat-carousel" role="list">
				<?php foreach ( $cats as $cat ) :
					$thumb_id = get_term_meta( $cat->term_id, 'wpg_cat_thumbnail', true );
					$img_url  = $thumb_id ? wp_get_attachment_image_url( absint( $thumb_id ), 'large' ) : '';
					$cat_url  = get_term_link( $cat );
					if ( is_wp_error( $cat_url ) ) { continue; }
				?>
				<a href="<?php echo esc_url( $cat_url ); ?>"
				   class="wpg-cat-card"
				   role="listitem"
				   <?php if ( $img_url ) : ?>
				   style="background-image:url('<?php echo esc_url( $img_url ); ?>')"
				   <?php endif; ?>>
					<div class="wpg-cat-card-overlay">
						<span class="wpg-cat-card-name"><?php echo esc_html( $cat->name ); ?></span>
						<span class="wpg-cat-card-count">
							<?php
							/* translators: %d = number of wallpapers */
							printf( esc_html__( '%d wallpapers', 'wallpress-gallery' ), absint( $cat->count ) );
							?>
						</span>
					</div>
				</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// ── [wpg_categories_grid] ────────────────────────────────────────────────
	// Displays ALL categories as cards in a responsive grid (like the wallpaper grid).
	// Usage:
	//   [wpg_categories_grid]
	//   [wpg_categories_grid columns="4" orderby="name" order="ASC"]
	public static function sc_categories_grid( $user_atts ) {
		$atts = shortcode_atts( [
			'columns' => 4,
			'orderby' => 'count',
			'order'   => 'DESC',
			'limit'   => 0,       // 0 = all
		], $user_atts );

		$allowed_orderby = [ 'count', 'name', 'slug', 'term_id' ];
		$orderby = in_array( $atts['orderby'], $allowed_orderby, true ) ? $atts['orderby'] : 'count';

		$query_args = [
			'taxonomy'   => 'wpg_category',
			'hide_empty' => false,
			'orderby'    => $orderby,
			'order'      => strtoupper( $atts['order'] ) === 'ASC' ? 'ASC' : 'DESC',
		];
		if ( absint( $atts['limit'] ) > 0 ) {
			$query_args['number'] = absint( $atts['limit'] );
		}

		$cats = get_terms( $query_args );

		if ( is_wp_error( $cats ) || empty( $cats ) ) {
			return '<p class="wpg-no-results">' . esc_html__( 'No categories found.', 'wallpress-gallery' ) . '</p>';
		}

		ob_start();
		?>
		<div class="wpg-cat-grid" style="--wpg-cat-cols:<?php echo absint( $atts['columns'] ); ?>;">
			<?php foreach ( $cats as $cat ) :
				$thumb_id = get_term_meta( $cat->term_id, 'wpg_cat_thumbnail', true );
				$img_url  = $thumb_id ? wp_get_attachment_image_url( absint( $thumb_id ), 'large' ) : '';
				$cat_url  = get_term_link( $cat );
				if ( is_wp_error( $cat_url ) ) { continue; }
			?>
			<a href="<?php echo esc_url( $cat_url ); ?>"
			   class="wpg-cat-grid-card"
			   <?php if ( $img_url ) : ?>
			   style="background-image:url('<?php echo esc_url( $img_url ); ?>')"
			   <?php endif; ?>>
				<div class="wpg-cat-card-overlay">
					<span class="wpg-cat-card-name"><?php echo esc_html( $cat->name ); ?></span>
					<span class="wpg-cat-card-count">
						<?php
						/* translators: %d = number of wallpapers */
						printf( esc_html__( '%d wallpapers', 'wallpress-gallery' ), absint( $cat->count ) );
						?>
					</span>
				</div>
			</a>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	// ── [wpg_sidebar] ────────────────────────────────────────────────────────
	// Slide-in navbar sidebar: logo, search, primary menu, categories, tags.
	// Usage: [wpg_sidebar]
	public static function sc_sidebar( $user_atts ) {
		$atts = shortcode_atts( [
			'cat_limit' => 10,
			'tag_limit' => 10,
			'menu'      => 'primary',   // WP menu slug or location
		], $user_atts );

		// Top categories by count
		$cats = get_terms( [
			'taxonomy'   => 'wpg_category',
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => absint( $atts['cat_limit'] ),
		] );

		// Top tags by count
		$tags = get_terms( [
			'taxonomy'   => 'wpg_tag',
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => absint( $atts['tag_limit'] ),
		] );

		$home_url = esc_url( home_url( '/' ) );

		ob_start();
		?>

		<!-- Inline trigger (shows at shortcode position) -->
		<button class="wpg-sb-inline-trigger wpg-sb-toggle" aria-label="<?php esc_attr_e( 'Open menu', 'wallpress-gallery' ); ?>" aria-expanded="false">
			<span class="wpg-sb-toggle-icon">
				<span></span><span></span><span></span>
			</span>
		</button>

		<!-- Fixed edge toggle (always visible) -->
		<button class="wpg-sb-toggle wpg-sb-toggle--fixed" aria-label="<?php esc_attr_e( 'Open menu', 'wallpress-gallery' ); ?>" aria-expanded="false" aria-hidden="true" tabindex="-1">
			<span class="wpg-sb-toggle-icon">
				<span></span><span></span><span></span>
			</span>
		</button>

		<!-- Overlay -->
		<div class="wpg-sb-overlay" aria-hidden="true"></div>

		<!-- Sidebar panel -->
		<aside class="wpg-sb-panel" aria-label="<?php esc_attr_e( 'Site navigation', 'wallpress-gallery' ); ?>">

			<!-- Logo + close -->
			<div class="wpg-sb-head">
				<a href="<?php echo $home_url; ?>" class="wpg-sb-logo">
					<div class="wpg-sb-logo-text">
						<span class="wpg-sb-logo-pixo">Pixo</span>
						<span class="wpg-sb-logo-wall">WALLPAPER</span>
					</div>
				</a>
				<button class="wpg-sb-close" aria-label="<?php esc_attr_e( 'Close', 'wallpress-gallery' ); ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
				</button>
			</div>

			<!-- Search bar -->
			<div class="wpg-sb-search-wrap wpg-search-wrap">
				<form role="search" method="get" action="<?php echo $home_url; ?>" class="wpg-sb-search-form">
					<svg class="wpg-sb-search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
					<input type="search" name="s" class="wpg-sb-search-input wpg-search-input"
						placeholder="<?php esc_attr_e( 'Search wallpapers…', 'wallpress-gallery' ); ?>"
						autocomplete="off"
						data-count="12" />
					<button type="button" class="wpg-sb-search-clear" aria-label="<?php esc_attr_e( 'Clear', 'wallpress-gallery' ); ?>" hidden>
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
					</button>
				</form>
				<div class="wpg-search-results wpg-sb-search-results" role="listbox"></div>
			</div>

			<!-- Primary navigation menu -->
			<?php
			$menu_html = wp_nav_menu( [
				'theme_location' => $atts['menu'],
				'menu'           => $atts['menu'],
				'container'      => false,
				'menu_class'     => 'wpg-sb-nav-list',
				'depth'          => 2,
				'fallback_cb'    => false,
				'echo'           => false,
			] );
			if ( $menu_html ) : ?>
			<div class="wpg-sb-section wpg-sb-section--nav">
				<h4 class="wpg-sb-section-title">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M3 12h18M3 6h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
					<?php esc_html_e( 'Menu', 'wallpress-gallery' ); ?>
				</h4>
				<?php echo $menu_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<?php endif; ?>

			<!-- Top Categories -->
			<?php if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) : ?>
			<div class="wpg-sb-section">
				<h4 class="wpg-sb-section-title">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/></svg>
					<?php esc_html_e( 'Top Categories', 'wallpress-gallery' ); ?>
				</h4>
				<ul class="wpg-sb-cat-list">
					<?php foreach ( $cats as $cat ) :
						$thumb_id  = get_term_meta( $cat->term_id, 'wpg_cat_thumbnail', true );
						$thumb_url = $thumb_id ? wp_get_attachment_image_url( absint( $thumb_id ), 'thumbnail' ) : '';
						$cat_url   = get_term_link( $cat );
						if ( is_wp_error( $cat_url ) ) { continue; }
					?>
					<li>
						<a href="<?php echo esc_url( $cat_url ); ?>" class="wpg-sb-cat-item">
							<span class="wpg-sb-cat-thumb"
								<?php if ( $thumb_url ) : ?>style="background-image:url('<?php echo esc_url( $thumb_url ); ?>')"<?php endif; ?>>
							</span>
							<span class="wpg-sb-cat-name"><?php echo esc_html( $cat->name ); ?></span>
							<span class="wpg-sb-cat-count"><?php echo absint( $cat->count ); ?></span>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>

			<!-- Trending Tags -->
			<?php if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) : ?>
			<div class="wpg-sb-section">
				<h4 class="wpg-sb-section-title">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7" cy="7" r="1.5" fill="currentColor"/></svg>
					<?php esc_html_e( 'Trending Tags', 'wallpress-gallery' ); ?>
				</h4>
				<div class="wpg-sb-tags">
					<?php foreach ( $tags as $tag ) :
						$tag_url = get_term_link( $tag );
						if ( is_wp_error( $tag_url ) ) { continue; }
					?>
					<a href="<?php echo esc_url( $tag_url ); ?>" class="wpg-sb-tag">
						<?php echo esc_html( $tag->name ); ?>
					</a>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

		</aside>
		<?php
		return ob_get_clean();
	}
}

WPG_Shortcodes::init();
