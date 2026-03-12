<?php
/**
 * Divi Module: WPG Category Filter
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPG_Divi_CategoryFilter extends ET_Builder_Module {

	private static $count = 0;

	public function init() {
		$this->name       = esc_html__( 'WPG Category Filter', 'wallpress-gallery' );
		$this->slug       = 'wpg_divi_category_filter';
		$this->vb_support = 'on';

		if ( file_exists( WPG_PATH . 'admin/images/divi-icon-filter.svg' ) ) {
			$this->icon_path = WPG_PATH . 'admin/images/divi-icon-filter.svg';
		}

		$this->settings_modal_toggles = [
			'general' => [
				'toggles' => [
					'grid_settings'       => [ 'title' => esc_html__( 'Grid Settings',        'wallpress-gallery' ), 'priority' => 10 ],
					'category_selection'  => [ 'title' => esc_html__( 'Category Selection',   'wallpress-gallery' ), 'priority' => 20 ],
				],
			],
			'advanced' => [
				'toggles' => [
					'tab_style'      => [ 'title' => esc_html__( 'Tab Button Style',      'wallpress-gallery' ), 'priority' => 10 ],
					'card_style'     => [ 'title' => esc_html__( 'Card Style',            'wallpress-gallery' ), 'priority' => 20 ],
					'loadmore_style' => [ 'title' => esc_html__( 'Load More Button Style','wallpress-gallery' ), 'priority' => 30 ],
				],
			],
		];
	}

	// ── Field definitions ─────────────────────────────────────────────────────
	public function get_fields() {
		return [

			// ══ CONTENT TAB ═══════════════════════════════════════════════════

			// Grid Settings
			'wpg_columns' => [
				'label'       => esc_html__( 'Columns', 'wallpress-gallery' ),
				'type'        => 'select',
				'default'     => '4',
				'options'     => [ '2' => '2', '3' => '3', '4' => '4', '5' => '5' ],
				'tab_slug'    => 'general',
				'toggle_slug' => 'grid_settings',
			],
			'wpg_count' => [
				'label'          => esc_html__( 'Images Per Page', 'wallpress-gallery' ),
				'type'           => 'range',
				'default'        => '12',
				'range_settings' => [ 'min' => '4', 'max' => '48', 'step' => '4' ],
				'unitless'       => true,
				'tab_slug'       => 'general',
				'toggle_slug'    => 'grid_settings',
			],
			'wpg_grid_style' => [
				'label'       => esc_html__( 'Grid Style', 'wallpress-gallery' ),
				'type'        => 'select',
				'default'     => 'uniform',
				'options'     => [
					'uniform' => esc_html__( 'Uniform Grid', 'wallpress-gallery' ),
					'masonry' => esc_html__( 'Masonry',      'wallpress-gallery' ),
				],
				'tab_slug'    => 'general',
				'toggle_slug' => 'grid_settings',
			],
			'wpg_orderby' => [
				'label'       => esc_html__( 'Default Order', 'wallpress-gallery' ),
				'type'        => 'select',
				'default'     => 'date',
				'options'     => [
					'date'  => esc_html__( 'Latest',      'wallpress-gallery' ),
					'views' => esc_html__( 'Most Viewed', 'wallpress-gallery' ),
					'rand'  => esc_html__( 'Random',      'wallpress-gallery' ),
					'title' => esc_html__( 'Title',       'wallpress-gallery' ),
				],
				'tab_slug'    => 'general',
				'toggle_slug' => 'grid_settings',
			],
			'wpg_show_all_tab' => [
				'label'       => esc_html__( 'Show "All" Tab', 'wallpress-gallery' ),
				'type'        => 'yes_no_button',
				'default'     => 'on',
				'options'     => [ 'on' => esc_html__( 'Yes', 'wallpress-gallery' ), 'off' => esc_html__( 'No', 'wallpress-gallery' ) ],
				'tab_slug'    => 'general',
				'toggle_slug' => 'grid_settings',
			],
			'wpg_all_tab_label' => [
				'label'       => esc_html__( '"All" Tab Label', 'wallpress-gallery' ),
				'type'        => 'text',
				'default'     => esc_html__( 'All', 'wallpress-gallery' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'grid_settings',
			],
			'wpg_pagination_type' => [
				'label'       => esc_html__( 'Pagination Type', 'wallpress-gallery' ),
				'type'        => 'select',
				'default'     => 'numbered',
				'options'     => [
					'numbered'  => esc_html__( 'Numbered',         'wallpress-gallery' ),
					'load_more' => esc_html__( 'Load More Button', 'wallpress-gallery' ),
					'infinite'  => esc_html__( 'Infinite Scroll',  'wallpress-gallery' ),
				],
				'tab_slug'    => 'general',
				'toggle_slug' => 'grid_settings',
			],
			'wpg_show_caption' => [
				'label'       => esc_html__( 'Show Image Caption', 'wallpress-gallery' ),
				'type'        => 'yes_no_button',
				'default'     => 'on',
				'options'     => [ 'on' => esc_html__( 'Yes', 'wallpress-gallery' ), 'off' => esc_html__( 'No', 'wallpress-gallery' ) ],
				'tab_slug'    => 'general',
				'toggle_slug' => 'grid_settings',
			],
			'wpg_show_download_btn' => [
				'label'       => esc_html__( 'Show Download Button on Hover', 'wallpress-gallery' ),
				'type'        => 'yes_no_button',
				'default'     => 'on',
				'options'     => [ 'on' => esc_html__( 'Yes', 'wallpress-gallery' ), 'off' => esc_html__( 'No', 'wallpress-gallery' ) ],
				'tab_slug'    => 'general',
				'toggle_slug' => 'grid_settings',
			],

			// Category Selection
			'wpg_categories_include' => [
				'label'       => esc_html__( 'Include Categories (slugs)', 'wallpress-gallery' ),
				'type'        => 'text',
				'default'     => '',
				'description' => esc_html__( 'Comma-separated slugs. Leave empty to show all. E.g: nature,cars,anime', 'wallpress-gallery' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'category_selection',
			],
			'wpg_categories_exclude' => [
				'label'       => esc_html__( 'Exclude Categories (slugs)', 'wallpress-gallery' ),
				'type'        => 'text',
				'default'     => '',
				'description' => esc_html__( 'Comma-separated slugs to exclude.', 'wallpress-gallery' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'category_selection',
			],
			'wpg_max_tabs' => [
				'label'          => esc_html__( 'Max Category Tabs', 'wallpress-gallery' ),
				'type'           => 'range',
				'default'        => '10',
				'range_settings' => [ 'min' => '3', 'max' => '30', 'step' => '1' ],
				'unitless'       => true,
				'tab_slug'       => 'general',
				'toggle_slug'    => 'category_selection',
			],

			// ══ DESIGN TAB ════════════════════════════════════════════════════

			// Tab Button Style
			'wpg_tab_bg' => [
				'label'       => esc_html__( 'Tab Background', 'wallpress-gallery' ),
				'type'        => 'color-alpha',
				'default'     => 'transparent',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'tab_style',
			],
			'wpg_tab_border_color' => [
				'label'       => esc_html__( 'Tab Border Color', 'wallpress-gallery' ),
				'type'        => 'color-alpha',
				'default'     => 'rgba(255,255,255,0.1)',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'tab_style',
			],
			'wpg_tab_text_color' => [
				'label'       => esc_html__( 'Tab Text Color', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#999999',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'tab_style',
			],
			'wpg_tab_active_bg' => [
				'label'       => esc_html__( 'Active Tab Background', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#7fff00',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'tab_style',
			],
			'wpg_tab_active_text' => [
				'label'       => esc_html__( 'Active Tab Text Color', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#111111',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'tab_style',
			],
			'wpg_tab_radius' => [
				'label'          => esc_html__( 'Tab Border Radius (px)', 'wallpress-gallery' ),
				'type'           => 'range',
				'default'        => '999',
				'range_settings' => [ 'min' => '0', 'max' => '999', 'step' => '1' ],
				'fixed_unit'     => 'px',
				'tab_slug'       => 'advanced',
				'toggle_slug'    => 'tab_style',
			],
			'wpg_tab_font_size' => [
				'label'          => esc_html__( 'Tab Font Size (px)', 'wallpress-gallery' ),
				'type'           => 'range',
				'default'        => '13',
				'range_settings' => [ 'min' => '10', 'max' => '20', 'step' => '1' ],
				'fixed_unit'     => 'px',
				'tab_slug'       => 'advanced',
				'toggle_slug'    => 'tab_style',
			],
			'wpg_tab_gap' => [
				'label'          => esc_html__( 'Gap Between Tabs (px)', 'wallpress-gallery' ),
				'type'           => 'range',
				'default'        => '8',
				'range_settings' => [ 'min' => '4', 'max' => '24', 'step' => '1' ],
				'fixed_unit'     => 'px',
				'tab_slug'       => 'advanced',
				'toggle_slug'    => 'tab_style',
			],

			// Card Style
			'wpg_card_radius' => [
				'label'          => esc_html__( 'Card Border Radius (px)', 'wallpress-gallery' ),
				'type'           => 'range',
				'default'        => '10',
				'range_settings' => [ 'min' => '0', 'max' => '24', 'step' => '1' ],
				'fixed_unit'     => 'px',
				'tab_slug'       => 'advanced',
				'toggle_slug'    => 'card_style',
			],
			'wpg_card_gap' => [
				'label'          => esc_html__( 'Gap Between Cards (px)', 'wallpress-gallery' ),
				'type'           => 'range',
				'default'        => '12',
				'range_settings' => [ 'min' => '4', 'max' => '32', 'step' => '2' ],
				'fixed_unit'     => 'px',
				'tab_slug'       => 'advanced',
				'toggle_slug'    => 'card_style',
			],
			'wpg_card_overlay_color' => [
				'label'       => esc_html__( 'Hover Overlay Color', 'wallpress-gallery' ),
				'type'        => 'color-alpha',
				'default'     => 'rgba(0,0,0,0.75)',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'card_style',
			],
			'wpg_card_caption_bg' => [
				'label'       => esc_html__( 'Caption Background', 'wallpress-gallery' ),
				'type'        => 'color-alpha',
				'default'     => 'rgba(0,0,0,0.5)',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'card_style',
			],
			'wpg_card_caption_color' => [
				'label'       => esc_html__( 'Caption Text Color', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#cccccc',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'card_style',
			],
			'wpg_card_caption_size' => [
				'label'          => esc_html__( 'Caption Font Size (px)', 'wallpress-gallery' ),
				'type'           => 'range',
				'default'        => '11',
				'range_settings' => [ 'min' => '9', 'max' => '16', 'step' => '1' ],
				'fixed_unit'     => 'px',
				'tab_slug'       => 'advanced',
				'toggle_slug'    => 'card_style',
			],
			'wpg_dl_btn_bg' => [
				'label'       => esc_html__( 'Download Button Background', 'wallpress-gallery' ),
				'type'        => 'color-alpha',
				'default'     => 'rgba(0,0,0,0.6)',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'card_style',
			],
			'wpg_dl_btn_color' => [
				'label'       => esc_html__( 'Download Button Text Color', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#ffffff',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'card_style',
			],
			'wpg_dl_btn_hover_bg' => [
				'label'       => esc_html__( 'Download Button Hover Background', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#7fff00',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'card_style',
			],
			'wpg_dl_btn_hover_color' => [
				'label'       => esc_html__( 'Download Button Hover Text Color', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#111111',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'card_style',
			],

			// Load More Style
			'wpg_loadmore_bg' => [
				'label'       => esc_html__( 'Load More Background', 'wallpress-gallery' ),
				'type'        => 'color-alpha',
				'default'     => 'rgba(30,30,38,1)',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'loadmore_style',
			],
			'wpg_loadmore_color' => [
				'label'       => esc_html__( 'Load More Text Color', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#cccccc',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'loadmore_style',
			],
			'wpg_loadmore_border' => [
				'label'       => esc_html__( 'Load More Border Color', 'wallpress-gallery' ),
				'type'        => 'color-alpha',
				'default'     => 'rgba(255,255,255,0.1)',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'loadmore_style',
			],
			'wpg_loadmore_hover_border' => [
				'label'       => esc_html__( 'Load More Hover Border', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#7fff00',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'loadmore_style',
			],
		];
	}

	// ── Render ────────────────────────────────────────────────────────────────
	public function render( $attrs, $content, $render_slug ) {
		$p = $this->props;

		// ── Content props ─────────────────────────────────────────────────────
		$columns          = absint( $p['wpg_columns']          ?? 4 );
		$count            = absint( $p['wpg_count']            ?? 12 );
		$grid_style       = sanitize_key( $p['wpg_grid_style'] ?? 'uniform' );
		$orderby          = sanitize_key( $p['wpg_orderby']    ?? 'date' );
		$show_all_tab     = ( $p['wpg_show_all_tab']           ?? 'on' ) === 'on';
		$all_tab_label    = sanitize_text_field( $p['wpg_all_tab_label']   ?? __( 'All', 'wallpress-gallery' ) );
		$pagination_type  = sanitize_key( $p['wpg_pagination_type']        ?? 'numbered' );
		$show_caption     = ( $p['wpg_show_caption']           ?? 'on' ) === 'on';
		$show_dl_btn      = ( $p['wpg_show_download_btn']      ?? 'on' ) === 'on';
		$cats_include_raw = sanitize_text_field( $p['wpg_categories_include'] ?? '' );
		$cats_exclude_raw = sanitize_text_field( $p['wpg_categories_exclude'] ?? '' );
		$max_tabs         = absint( $p['wpg_max_tabs']         ?? 10 );

		// ── Design props ──────────────────────────────────────────────────────
		$tab_bg             = $p['wpg_tab_bg']              ?? 'transparent';
		$tab_border         = $p['wpg_tab_border_color']    ?? 'rgba(255,255,255,0.1)';
		$tab_text           = $p['wpg_tab_text_color']      ?? '#999999';
		$tab_active_bg      = $p['wpg_tab_active_bg']       ?? '#7fff00';
		$tab_active_text    = $p['wpg_tab_active_text']     ?? '#111111';
		$tab_radius         = absint( $p['wpg_tab_radius']  ?? 999 );
		$tab_font_size      = absint( $p['wpg_tab_font_size'] ?? 13 );
		$tab_gap            = absint( $p['wpg_tab_gap']     ?? 8 );
		$card_radius        = absint( $p['wpg_card_radius'] ?? 10 );
		$card_gap           = absint( $p['wpg_card_gap']    ?? 12 );
		$overlay_color      = $p['wpg_card_overlay_color']  ?? 'rgba(0,0,0,0.75)';
		$caption_bg         = $p['wpg_card_caption_bg']     ?? 'rgba(0,0,0,0.5)';
		$caption_color      = $p['wpg_card_caption_color']  ?? '#cccccc';
		$caption_size       = absint( $p['wpg_card_caption_size'] ?? 11 );
		$dl_btn_bg          = $p['wpg_dl_btn_bg']           ?? 'rgba(0,0,0,0.6)';
		$dl_btn_color       = $p['wpg_dl_btn_color']        ?? '#ffffff';
		$dl_hover_bg        = $p['wpg_dl_btn_hover_bg']     ?? '#7fff00';
		$dl_hover_color     = $p['wpg_dl_btn_hover_color']  ?? '#111111';
		$lm_bg              = $p['wpg_loadmore_bg']         ?? 'rgba(30,30,38,1)';
		$lm_color           = $p['wpg_loadmore_color']      ?? '#cccccc';
		$lm_border          = $p['wpg_loadmore_border']     ?? 'rgba(255,255,255,0.1)';
		$lm_hover_border    = $p['wpg_loadmore_hover_border'] ?? '#7fff00';

		// ── Unique module ID ──────────────────────────────────────────────────
		self::$count++;
		$mid = 'wpg-cf-' . self::$count;

		// ── Inline CSS scoped to module ID ────────────────────────────────────
		$css  = "<style>";
		$css .= "#{$mid} .wpg-tabs{gap:{$tab_gap}px;}";
		$css .= "#{$mid} .wpg-tab-btn{";
		$css .=   'background:' . esc_attr( $tab_bg ) . ';';
		$css .=   'border-color:' . esc_attr( $tab_border ) . ';';
		$css .=   'color:' . esc_attr( $tab_text ) . ';';
		$css .=   "border-radius:{$tab_radius}px;";
		$css .=   "font-size:{$tab_font_size}px;";
		$css .= '}';
		$css .= "#{$mid} .wpg-tab-btn.active{background:" . esc_attr( $tab_active_bg ) . ';color:' . esc_attr( $tab_active_text ) . ';border-color:' . esc_attr( $tab_active_bg ) . ';}';
		$css .= "#{$mid} .wpg-card{border-radius:{$card_radius}px;}";
		$css .= "#{$mid} .wpg-grid-uniform,#{$mid} .wpg-grid-masonry{gap:{$card_gap}px;--wpg-cols:{$columns};}";
		$css .= "#{$mid} .wpg-grid-masonry{column-gap:{$card_gap}px;}";
		$css .= "#{$mid} .wpg-grid-masonry .wpg-card{margin-bottom:{$card_gap}px;}";
		$css .= "#{$mid} .wpg-card-overlay{background:linear-gradient(to top," . esc_attr( $overlay_color ) . " 0%,transparent 60%);}";
		if ( ! $show_caption ) {
			$css .= "#{$mid} .wpg-card-caption{display:none;}";
		} else {
			$css .= "#{$mid} .wpg-card-caption{background:" . esc_attr( $caption_bg ) . ';color:' . esc_attr( $caption_color ) . ";font-size:{$caption_size}px;}";
		}
		if ( ! $show_dl_btn ) {
			$css .= "#{$mid} .wpg-card-dl{display:none;}";
		} else {
			$css .= "#{$mid} .wpg-card-dl{background:" . esc_attr( $dl_btn_bg ) . ';color:' . esc_attr( $dl_btn_color ) . ';}';
			$css .= "#{$mid} .wpg-card-dl:hover{background:" . esc_attr( $dl_hover_bg ) . ';color:' . esc_attr( $dl_hover_color ) . ';}';
		}
		$css .= "#{$mid} .wpg-load-more-btn{background:" . esc_attr( $lm_bg ) . ';color:' . esc_attr( $lm_color ) . ';border-color:' . esc_attr( $lm_border ) . ';}';
		$css .= "#{$mid} .wpg-load-more-btn:hover{border-color:" . esc_attr( $lm_hover_border ) . ';color:' . esc_attr( $lm_hover_border ) . ';}';
		$css .= '</style>';

		// ── Build category terms list ──────────────────────────────────────────
		$term_args = [
			'taxonomy'   => 'wpg_category',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'number'     => $max_tabs,
		];

		// Include specific slugs
		if ( $cats_include_raw ) {
			$include_slugs = array_filter( array_map( 'sanitize_key', explode( ',', $cats_include_raw ) ) );
			if ( ! empty( $include_slugs ) ) {
				$term_args['slug'] = $include_slugs;
			}
		}

		// Exclude slugs
		$exclude_ids = [];
		if ( $cats_exclude_raw ) {
			$exclude_slugs = array_filter( array_map( 'sanitize_key', explode( ',', $cats_exclude_raw ) ) );
			foreach ( $exclude_slugs as $ex_slug ) {
				$ex_term = get_term_by( 'slug', $ex_slug, 'wpg_category' );
				if ( $ex_term ) { $exclude_ids[] = $ex_term->term_id; }
			}
		}
		if ( ! empty( $exclude_ids ) ) {
			$term_args['exclude'] = $exclude_ids;
		}

		$terms = get_terms( $term_args );
		if ( is_wp_error( $terms ) ) { $terms = []; }

		// ── Build initial grid (All images) ───────────────────────────────────
		$query_args = [
			'post_type'      => 'wpg_image',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
		];

		switch ( $orderby ) {
			case 'views':
				$query_args['orderby']  = 'meta_value_num';
				$query_args['meta_key'] = '_wpg_views'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$query_args['order']    = 'DESC';
				break;
			case 'rand':
				$query_args['orderby'] = 'rand';
				break;
			case 'title':
				$query_args['orderby'] = 'title';
				$query_args['order']   = 'ASC';
				break;
			default:
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'DESC';
		}

		$query      = new WP_Query( $query_args );
		$cards_html = '';
		$style      = in_array( $grid_style, [ 'uniform', 'masonry' ], true ) ? $grid_style : 'uniform';

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post    = get_post();
				$columns_var = $columns; // pass to template
				ob_start();
				include WPG_PATH . 'templates/card.php';
				$cards_html .= ob_get_clean();
			}
			wp_reset_postdata();
		} else {
			$cards_html = '<p class="wpg-no-results">' . esc_html__( 'No images found.', 'wallpress-gallery' ) . '</p>';
		}

		// ── Tabs HTML ─────────────────────────────────────────────────────────
		$tabs_html = '<div class="wpg-tabs" role="tablist">';
		if ( $show_all_tab ) {
			$tabs_html .= '<button class="wpg-tab-btn active" data-cat="" data-columns="' . esc_attr( $columns ) . '" data-count="' . esc_attr( $count ) . '" role="tab" aria-selected="true">';
			$tabs_html .= esc_html( $all_tab_label );
			$tabs_html .= '</button>';
		}
		foreach ( $terms as $term ) {
			$tabs_html .= '<button class="wpg-tab-btn" data-cat="' . esc_attr( $term->slug ) . '" data-columns="' . esc_attr( $columns ) . '" data-count="' . esc_attr( $count ) . '" role="tab" aria-selected="false">';
			$tabs_html .= esc_html( $term->name );
			$tabs_html .= '<span class="wpg-tab-count" style="font-size:10px;opacity:0.6;margin-left:4px;">(' . absint( $term->count ) . ')</span>';
			$tabs_html .= '</button>';
		}
		$tabs_html .= '</div>';

		// ── Pagination / Load More ─────────────────────────────────────────────
		$pagination_html = '';
		if ( $pagination_type === 'numbered' && $query->max_num_pages > 1 ) {
			$pagination_html .= '<div class="wpg-pagination">';
			for ( $i = 1; $i <= $query->max_num_pages; $i++ ) {
				$pagination_html .= '<button class="wpg-page-btn' . ( $i === 1 ? ' active' : '' ) . '" data-page="' . esc_attr( $i ) . '">' . esc_html( $i ) . '</button>';
			}
			$pagination_html .= '</div>';
		} elseif ( in_array( $pagination_type, [ 'load_more', 'infinite' ], true ) ) {
			$hide      = $query->max_num_pages <= 1 ? ' style="display:none;"' : '';
			$auto_load = $pagination_type === 'infinite' ? ' data-infinite="1"' : '';
			$pagination_html .= '<button class="wpg-load-more-btn" data-page="1"' . $hide . $auto_load . '>';
			$pagination_html .= esc_html__( 'Load More', 'wallpress-gallery' );
			$pagination_html .= '</button>';
		}

		// ── Full HTML ─────────────────────────────────────────────────────────
		$html  = $css;
		$html .= '<div id="' . esc_attr( $mid ) . '"';
		$html .= ' class="wpg-category-filter-module"';
		$html .= ' data-columns="' . esc_attr( $columns ) . '"';
		$html .= ' data-count="' . esc_attr( $count ) . '"';
		$html .= ' data-orderby="' . esc_attr( $orderby ) . '"';
		$html .= ' data-style="' . esc_attr( $grid_style ) . '"';
		$html .= ' data-pagination="' . esc_attr( $pagination_type ) . '"';
		$html .= '>';

		$html .= $tabs_html;

		$html .= '<div class="wpg-grid-wrap">';
		$html .= '<div class="wpg-grid-' . esc_attr( $style ) . '"';
		$html .= ' style="--wpg-cols:' . esc_attr( $columns ) . ';"';
		$html .= ' data-style="' . esc_attr( $style ) . '"';
		$html .= ' data-columns="' . esc_attr( $columns ) . '"';
		$html .= ' data-orderby="' . esc_attr( $orderby ) . '"';
		$html .= ' data-category=""';
		$html .= ' data-tag="">';
		$html .= $cards_html;
		$html .= '</div>'; // .wpg-grid-*

		$html .= $pagination_html;
		$html .= '</div>'; // .wpg-grid-wrap

		$html .= '</div>'; // #module_id

		return $html;
	}
}

new WPG_Divi_CategoryFilter();
