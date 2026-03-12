<?php
/**
 * Divi Module: WPG Navbar & Search
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPG_Divi_Navbar extends ET_Builder_Module {

	/** Instance counter for unique IDs (reset per request — no caching conflicts). */
	private static $count = 0;

	public function init() {
		$this->name       = esc_html__( 'WPG Navbar & Search', 'wallpress-gallery' );
		$this->slug       = 'wpg_divi_navbar';
		$this->vb_support = 'on';

		// SVG icon for Divi module list (Divi 4.10+)
		if ( file_exists( WPG_PATH . 'admin/images/divi-icon-navbar.svg' ) ) {
			$this->icon_path = WPG_PATH . 'admin/images/divi-icon-navbar.svg';
		}

		// ── Settings modal toggles (sections within each tab) ─────────────────
		$this->settings_modal_toggles = [
			'general' => [
				'toggles' => [
					'logo_settings'       => [ 'title' => esc_html__( 'Logo Settings',        'wallpress-gallery' ), 'priority' => 10 ],
					'categories_dropdown' => [ 'title' => esc_html__( 'Categories Dropdown',  'wallpress-gallery' ), 'priority' => 20 ],
					'search_bar'          => [ 'title' => esc_html__( 'Search Bar',           'wallpress-gallery' ), 'priority' => 30 ],
					'quick_tags'          => [ 'title' => esc_html__( 'Quick Tags Bar',        'wallpress-gallery' ), 'priority' => 40 ],
				],
			],
			'advanced' => [
				'toggles' => [
					'navbar_style'     => [ 'title' => esc_html__( 'Navbar Style',     'wallpress-gallery' ), 'priority' => 10 ],
					'logo_style'       => [ 'title' => esc_html__( 'Logo Style',       'wallpress-gallery' ), 'priority' => 20 ],
					'dropdown_style'   => [ 'title' => esc_html__( 'Dropdown Style',   'wallpress-gallery' ), 'priority' => 30 ],
					'search_style'     => [ 'title' => esc_html__( 'Search Style',     'wallpress-gallery' ), 'priority' => 40 ],
					'quick_tags_style' => [ 'title' => esc_html__( 'Quick Tags Style', 'wallpress-gallery' ), 'priority' => 50 ],
				],
			],
		];
	}

	// ── Field definitions ─────────────────────────────────────────────────────
	public function get_fields() {
		return [

			// ══ CONTENT TAB (tab_slug: general) ══════════════════════════════

			// Logo Settings
			'wpg_logo_text' => [
				'label'       => esc_html__( 'Logo Text', 'wallpress-gallery' ),
				'type'        => 'text',
				'default'     => get_bloginfo( 'name' ),
				'description' => esc_html__( 'Text shown as logo in navbar.', 'wallpress-gallery' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'logo_settings',
			],
			'wpg_logo_url' => [
				'label'       => esc_html__( 'Logo URL', 'wallpress-gallery' ),
				'type'        => 'text',
				'default'     => home_url( '/' ),
				'description' => esc_html__( 'URL when logo is clicked.', 'wallpress-gallery' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'logo_settings',
			],
			'wpg_logo_accent_word' => [
				'label'       => esc_html__( 'Accent Word', 'wallpress-gallery' ),
				'type'        => 'text',
				'default'     => '',
				'description' => esc_html__( 'Which word gets the accent color. Leave empty to accent the last word.', 'wallpress-gallery' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'logo_settings',
			],

			// Categories Dropdown
			'wpg_show_categories' => [
				'label'       => esc_html__( 'Show Categories Dropdown', 'wallpress-gallery' ),
				'type'        => 'yes_no_button',
				'default'     => 'on',
				'options'     => [ 'on' => esc_html__( 'Yes', 'wallpress-gallery' ), 'off' => esc_html__( 'No', 'wallpress-gallery' ) ],
				'tab_slug'    => 'general',
				'toggle_slug' => 'categories_dropdown',
			],
			'wpg_cat_button_label' => [
				'label'       => esc_html__( 'Button Label', 'wallpress-gallery' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Categories', 'wallpress-gallery' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'categories_dropdown',
			],
			'wpg_cat_columns' => [
				'label'       => esc_html__( 'Dropdown Columns', 'wallpress-gallery' ),
				'type'        => 'select',
				'default'     => '2',
				'options'     => [ '1' => '1', '2' => '2', '3' => '3' ],
				'tab_slug'    => 'general',
				'toggle_slug' => 'categories_dropdown',
			],
			'wpg_cat_orderby' => [
				'label'       => esc_html__( 'Order Categories By', 'wallpress-gallery' ),
				'type'        => 'select',
				'default'     => 'name',
				'options'     => [
					'name'  => esc_html__( 'Name',  'wallpress-gallery' ),
					'count' => esc_html__( 'Count', 'wallpress-gallery' ),
					'slug'  => esc_html__( 'Slug',  'wallpress-gallery' ),
				],
				'tab_slug'    => 'general',
				'toggle_slug' => 'categories_dropdown',
			],

			// Search Bar
			'wpg_show_search' => [
				'label'       => esc_html__( 'Show Search Bar', 'wallpress-gallery' ),
				'type'        => 'yes_no_button',
				'default'     => 'on',
				'options'     => [ 'on' => esc_html__( 'Yes', 'wallpress-gallery' ), 'off' => esc_html__( 'No', 'wallpress-gallery' ) ],
				'tab_slug'    => 'general',
				'toggle_slug' => 'search_bar',
			],
			'wpg_search_placeholder' => [
				'label'       => esc_html__( 'Search Placeholder', 'wallpress-gallery' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Search wallpapers...', 'wallpress-gallery' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'search_bar',
			],
			'wpg_search_results_count' => [
				'label'          => esc_html__( 'Max Search Results', 'wallpress-gallery' ),
				'type'           => 'range',
				'default'        => '8',
				'range_settings' => [ 'min' => '4', 'max' => '20', 'step' => '1' ],
				'unitless'       => true,
				'tab_slug'       => 'general',
				'toggle_slug'    => 'search_bar',
			],
			'wpg_search_page_url' => [
				'label'       => esc_html__( 'Search Results Page URL', 'wallpress-gallery' ),
				'type'        => 'text',
				'default'     => home_url( '/wallpapers/' ),
				'description' => esc_html__( 'Pressing Enter will navigate here with ?s= appended.', 'wallpress-gallery' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'search_bar',
			],

			// Quick Tags Bar
			'wpg_show_quick_tags' => [
				'label'       => esc_html__( 'Show Quick Tags Bar', 'wallpress-gallery' ),
				'type'        => 'yes_no_button',
				'default'     => 'on',
				'options'     => [ 'on' => esc_html__( 'Yes', 'wallpress-gallery' ), 'off' => esc_html__( 'No', 'wallpress-gallery' ) ],
				'tab_slug'    => 'general',
				'toggle_slug' => 'quick_tags',
			],
			'wpg_quick_tags_count' => [
				'label'          => esc_html__( 'Number of Tags', 'wallpress-gallery' ),
				'type'           => 'range',
				'default'        => '10',
				'range_settings' => [ 'min' => '5', 'max' => '20', 'step' => '1' ],
				'unitless'       => true,
				'tab_slug'       => 'general',
				'toggle_slug'    => 'quick_tags',
			],

			// ══ DESIGN TAB (tab_slug: advanced) ══════════════════════════════

			// Navbar Style
			'wpg_navbar_bg' => [
				'label'       => esc_html__( 'Navbar Background', 'wallpress-gallery' ),
				'type'        => 'color-alpha',
				'default'     => 'rgba(26,26,30,0.95)',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'navbar_style',
			],
			'wpg_navbar_height' => [
				'label'          => esc_html__( 'Navbar Height (px)', 'wallpress-gallery' ),
				'type'           => 'range',
				'default'        => '58',
				'range_settings' => [ 'min' => '48', 'max' => '80', 'step' => '1' ],
				'fixed_unit'     => 'px',
				'tab_slug'       => 'advanced',
				'toggle_slug'    => 'navbar_style',
			],
			'wpg_navbar_sticky' => [
				'label'       => esc_html__( 'Sticky Navbar', 'wallpress-gallery' ),
				'type'        => 'yes_no_button',
				'default'     => 'on',
				'options'     => [ 'on' => esc_html__( 'Yes', 'wallpress-gallery' ), 'off' => esc_html__( 'No', 'wallpress-gallery' ) ],
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'navbar_style',
			],
			'wpg_navbar_blur' => [
				'label'       => esc_html__( 'Backdrop Blur', 'wallpress-gallery' ),
				'type'        => 'yes_no_button',
				'default'     => 'on',
				'options'     => [ 'on' => esc_html__( 'Yes', 'wallpress-gallery' ), 'off' => esc_html__( 'No', 'wallpress-gallery' ) ],
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'navbar_style',
			],

			// Logo Style
			'wpg_logo_size' => [
				'label'          => esc_html__( 'Logo Font Size (px)', 'wallpress-gallery' ),
				'type'           => 'range',
				'default'        => '18',
				'range_settings' => [ 'min' => '14', 'max' => '32', 'step' => '1' ],
				'fixed_unit'     => 'px',
				'tab_slug'       => 'advanced',
				'toggle_slug'    => 'logo_style',
			],
			'wpg_logo_color' => [
				'label'       => esc_html__( 'Logo Text Color', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#f0f0f0',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'logo_style',
			],
			'wpg_logo_accent_color' => [
				'label'       => esc_html__( 'Logo Accent Color', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#7fff00',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'logo_style',
			],

			// Dropdown Style
			'wpg_dropdown_bg' => [
				'label'       => esc_html__( 'Dropdown Background', 'wallpress-gallery' ),
				'type'        => 'color-alpha',
				'default'     => '#1a1a21',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'dropdown_style',
			],
			'wpg_dropdown_item_color' => [
				'label'       => esc_html__( 'Item Text Color', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#cccccc',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'dropdown_style',
			],
			'wpg_dropdown_hover_color' => [
				'label'       => esc_html__( 'Item Hover Color', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#7fff00',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'dropdown_style',
			],
			'wpg_dropdown_accent_color' => [
				'label'       => esc_html__( 'Active Border Color', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#7fff00',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'dropdown_style',
			],

			// Search Style
			'wpg_search_bg' => [
				'label'       => esc_html__( 'Search Background', 'wallpress-gallery' ),
				'type'        => 'color-alpha',
				'default'     => 'rgba(42,42,48,1)',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'search_style',
			],
			'wpg_search_text_color' => [
				'label'       => esc_html__( 'Search Text Color', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#f0f0f0',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'search_style',
			],
			'wpg_search_focus_border' => [
				'label'       => esc_html__( 'Search Focus Border Color', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#7fff00',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'search_style',
			],
			'wpg_search_width' => [
				'label'          => esc_html__( 'Search Width (px)', 'wallpress-gallery' ),
				'type'           => 'range',
				'default'        => '220',
				'range_settings' => [ 'min' => '150', 'max' => '400', 'step' => '5' ],
				'fixed_unit'     => 'px',
				'tab_slug'       => 'advanced',
				'toggle_slug'    => 'search_style',
			],

			// Quick Tags Style
			'wpg_qtag_color' => [
				'label'       => esc_html__( 'Tag Text Color', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#999999',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'quick_tags_style',
			],
			'wpg_qtag_border_color' => [
				'label'       => esc_html__( 'Tag Border Color', 'wallpress-gallery' ),
				'type'        => 'color-alpha',
				'default'     => 'rgba(255,255,255,0.08)',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'quick_tags_style',
			],
			'wpg_qtag_hover_color' => [
				'label'       => esc_html__( 'Tag Hover Color', 'wallpress-gallery' ),
				'type'        => 'color',
				'default'     => '#7fff00',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'quick_tags_style',
			],
			'wpg_qtag_bg_hover' => [
				'label'       => esc_html__( 'Tag Hover Background', 'wallpress-gallery' ),
				'type'        => 'color-alpha',
				'default'     => 'rgba(127,255,0,0.06)',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'quick_tags_style',
			],
		];
	}

	// ── Render ────────────────────────────────────────────────────────────────
	public function render( $attrs, $content, $render_slug ) {
		$p = $this->props;

		// ── Content fields ────────────────────────────────────────────────────
		$logo_text          = sanitize_text_field( $p['wpg_logo_text']          ?? get_bloginfo( 'name' ) );
		$logo_url           = esc_url( $p['wpg_logo_url']                       ?? home_url( '/' ) );
		$logo_accent_word   = sanitize_text_field( $p['wpg_logo_accent_word']   ?? '' );
		$show_categories    = $p['wpg_show_categories']                         ?? 'on';
		$cat_btn_label      = sanitize_text_field( $p['wpg_cat_button_label']   ?? __( 'Categories', 'wallpress-gallery' ) );
		$cat_columns        = max( 1, absint( $p['wpg_cat_columns']             ?? 2 ) );
		$cat_orderby        = sanitize_key( $p['wpg_cat_orderby']               ?? 'name' );
		$show_search        = $p['wpg_show_search']                             ?? 'on';
		$search_placeholder = sanitize_text_field( $p['wpg_search_placeholder'] ?? __( 'Search wallpapers...', 'wallpress-gallery' ) );
		$search_count       = absint( $p['wpg_search_results_count']            ?? 8 );
		$search_page_url    = esc_url( $p['wpg_search_page_url']                ?? home_url( '/wallpapers/' ) );
		$show_quick_tags    = $p['wpg_show_quick_tags']                         ?? 'on';
		$quick_tags_count   = absint( $p['wpg_quick_tags_count']                ?? 10 );

		// ── Design fields ─────────────────────────────────────────────────────
		$navbar_bg           = $p['wpg_navbar_bg']            ?? 'rgba(26,26,30,0.95)';
		$navbar_height       = absint( $p['wpg_navbar_height'] ?? 58 );
		$is_sticky           = ( ( $p['wpg_navbar_sticky'] ?? 'on' ) === 'on' );
		$has_blur            = ( ( $p['wpg_navbar_blur']   ?? 'on' ) === 'on' );
		$logo_size           = absint( $p['wpg_logo_size']    ?? 18 );
		$logo_color          = $p['wpg_logo_color']            ?? '#f0f0f0';
		$logo_accent_color   = $p['wpg_logo_accent_color']     ?? '#7fff00';
		$dropdown_bg         = $p['wpg_dropdown_bg']           ?? '#1a1a21';
		$dropdown_item_color = $p['wpg_dropdown_item_color']   ?? '#cccccc';
		$dropdown_hover      = $p['wpg_dropdown_hover_color']  ?? '#7fff00';
		$dropdown_accent     = $p['wpg_dropdown_accent_color'] ?? '#7fff00';
		$search_bg           = $p['wpg_search_bg']             ?? 'rgba(42,42,48,1)';
		$search_text_color   = $p['wpg_search_text_color']     ?? '#f0f0f0';
		$search_focus_border = $p['wpg_search_focus_border']   ?? '#7fff00';
		$search_width        = absint( $p['wpg_search_width']  ?? 220 );
		$qtag_color          = $p['wpg_qtag_color']            ?? '#999999';
		$qtag_border         = $p['wpg_qtag_border_color']     ?? 'rgba(255,255,255,0.08)';
		$qtag_hover          = $p['wpg_qtag_hover_color']      ?? '#7fff00';
		$qtag_bg_hover       = $p['wpg_qtag_bg_hover']         ?? 'rgba(127,255,0,0.06)';

		// ── Unique instance ID ─────────────────────────────────────────────────
		self::$count++;
		$uid = 'wpg-nb-' . self::$count;

		// ── Computed CSS values ────────────────────────────────────────────────
		$position = $is_sticky ? 'sticky' : 'relative';
		$blur_val = $has_blur   ? 'blur(14px)' : 'none';

		// ── Inline CSS ────────────────────────────────────────────────────────
		$css  = '<style>';
		$css .= "#{$uid} .wpg-navbar{";
		$css .=   'background:' . esc_attr( $navbar_bg ) . ';';
		$css .=   "min-height:{$navbar_height}px;";
		$css .=   "position:{$position};top:0;z-index:1000;";
		$css .=   "backdrop-filter:{$blur_val};-webkit-backdrop-filter:{$blur_val};";
		$css .= '}';
		$css .= "#{$uid} .wpg-navbar-logo{font-size:{$logo_size}px;color:" . esc_attr( $logo_color ) . ';}';
		$css .= "#{$uid} .wpg-navbar-logo .wpg-accent{color:" . esc_attr( $logo_accent_color ) . ';}';
		$css .= "#{$uid} .wpg-dropdown{background:" . esc_attr( $dropdown_bg ) . ';}';
		$css .= "#{$uid} .wpg-dropdown-item{color:" . esc_attr( $dropdown_item_color ) . ';}';
		$css .= "#{$uid} .wpg-dropdown-item:hover{color:" . esc_attr( $dropdown_hover ) . ';border-left-color:' . esc_attr( $dropdown_accent ) . ';}';
		$css .= "#{$uid} .wpg-nav-search-wrap{width:{$search_width}px;}";
		$css .= "#{$uid} .wpg-nav-search-wrap input{background:" . esc_attr( $search_bg ) . ';color:' . esc_attr( $search_text_color ) . ';}';
		$css .= "#{$uid} .wpg-nav-search-wrap:focus-within{border-color:" . esc_attr( $search_focus_border ) . ';}';
		$css .= "#{$uid} .wpg-qtag{color:" . esc_attr( $qtag_color ) . ';border-color:' . esc_attr( $qtag_border ) . ';}';
		$css .= "#{$uid} .wpg-qtag:hover{color:" . esc_attr( $qtag_hover ) . ';background:' . esc_attr( $qtag_bg_hover ) . ';border-color:' . esc_attr( $qtag_hover ) . ';}';
		$css .= '</style>';

		// ── Logo HTML ─────────────────────────────────────────────────────────
		$logo_html = $this->build_logo_html( $logo_text, $logo_accent_word );

		// ── Categories dropdown HTML ───────────────────────────────────────────
		$dropdown_html = '';
		if ( $show_categories === 'on' ) {
			$cats = get_terms( [
				'taxonomy'   => 'wpg_category',
				'hide_empty' => false,
				'orderby'    => in_array( $cat_orderby, [ 'name', 'count', 'slug' ], true ) ? $cat_orderby : 'name',
				'order'      => 'ASC',
			] );

			if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) {
				$chunks = array_chunk( $cats, (int) ceil( count( $cats ) / $cat_columns ) );

				$dropdown_html  = '<div class="wpg-nav-dropdown-wrap">';
				$dropdown_html .= '<button class="wpg-nav-dropdown-btn" type="button" aria-expanded="false" aria-haspopup="true">';
				$dropdown_html .= esc_html( $cat_btn_label );
				$dropdown_html .= '<svg class="wpg-dd-arrow" width="10" height="6" viewBox="0 0 10 6" fill="currentColor"><path d="M0 0l5 6 5-6z"/></svg>';
				$dropdown_html .= '</button>';
				$dropdown_html .= '<div class="wpg-dropdown" role="menu" style="--wpg-dd-cols:' . esc_attr( $cat_columns ) . ';">';
				$dropdown_html .= '<div class="wpg-dropdown-grid">';
				foreach ( $chunks as $chunk ) {
					$dropdown_html .= '<div class="wpg-dropdown-col">';
					foreach ( $chunk as $cat ) {
						$cat_url = get_term_link( $cat );
						if ( is_wp_error( $cat_url ) ) { continue; }
						$dropdown_html .= '<a class="wpg-dropdown-item" href="' . esc_url( $cat_url ) . '" role="menuitem">';
						$dropdown_html .= esc_html( $cat->name );
						$dropdown_html .= '<span class="wpg-dd-count">(' . absint( $cat->count ) . ')</span>';
						$dropdown_html .= '</a>';
					}
					$dropdown_html .= '</div>';
				}
				$dropdown_html .= '</div>';
				$dropdown_html .= '</div>'; // .wpg-dropdown
				$dropdown_html .= '</div>'; // .wpg-nav-dropdown-wrap
			}
		}

		// ── Search HTML ───────────────────────────────────────────────────────
		$search_html = '';
		if ( $show_search === 'on' ) {
			$search_html  = '<div class="wpg-nav-search-wrap">';
			$search_html .= '<svg class="wpg-nav-search-icon" width="14" height="14" viewBox="0 0 14 14" fill="currentColor">';
			$search_html .= '<path d="M9.5 8.5a5 5 0 10-1 1l3 3 1-1-3-3zm-4.5 1a3 3 0 110-6 3 3 0 010 6z"/>';
			$search_html .= '</svg>';
			$search_html .= '<input type="search"';
			$search_html .= ' class="wpg-search-input wpg-nav-search-input"';
			$search_html .= ' placeholder="' . esc_attr( $search_placeholder ) . '"';
			$search_html .= ' autocomplete="off"';
			$search_html .= ' data-count="' . esc_attr( $search_count ) . '"';
			$search_html .= ' data-search-page="' . esc_attr( $search_page_url ) . '"';
			$search_html .= ' />';
			$search_html .= '<div class="wpg-search-results wpg-nav-search-results"></div>';
			$search_html .= '</div>';
		}

		// ── Quick tags HTML ────────────────────────────────────────────────────
		$qtags_html = '';
		if ( $show_quick_tags === 'on' ) {
			$tags = get_terms( [
				'taxonomy'   => 'wpg_tag',
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => $quick_tags_count,
			] );
			if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
				$qtags_html = '<div class="wpg-qtags-bar">';
				foreach ( $tags as $tag ) {
					$tag_url = get_term_link( $tag );
					if ( is_wp_error( $tag_url ) ) { continue; }
					$qtags_html .= '<a class="wpg-qtag" href="' . esc_url( $tag_url ) . '">' . esc_html( $tag->name ) . '</a>';
				}
				$qtags_html .= '</div>';
			}
		}

		// ── Assemble full HTML ─────────────────────────────────────────────────
		$html  = $css;
		$html .= '<div id="' . esc_attr( $uid ) . '" class="wpg-navbar-module">';
		$html .= '<nav class="wpg-navbar" role="navigation" aria-label="' . esc_attr__( 'Main navigation', 'wallpress-gallery' ) . '">';
		$html .= '<div class="wpg-navbar-inner">';
		$html .= '<a class="wpg-navbar-logo" href="' . $logo_url . '">' . $logo_html . '</a>';
		$html .= $dropdown_html;
		$html .= '<div class="wpg-navbar-spacer"></div>';
		$html .= $search_html;
		$html .= '</div>'; // .wpg-navbar-inner
		if ( $qtags_html ) {
			$html .= $qtags_html;
		}
		$html .= '</nav>';
		$html .= '</div>'; // #uid

		// ── Scoped navbar JS (dropdown only — search handled by public-script.js) ──
		$uid_js = esc_js( $uid );
		$html .= <<<JS
<script>
(function(){
	var wrap = document.getElementById('{$uid_js}');
	if (!wrap) return;
	var btn  = wrap.querySelector('.wpg-nav-dropdown-btn');
	var menu = wrap.querySelector('.wpg-dropdown');
	if (!btn || !menu) return;
	btn.addEventListener('click', function(e){
		e.stopPropagation();
		var open = menu.classList.toggle('wpg-dd-open');
		btn.setAttribute('aria-expanded', open ? 'true' : 'false');
	});
	document.addEventListener('click', function(e){
		if (!wrap.contains(e.target)){
			menu.classList.remove('wpg-dd-open');
			btn.setAttribute('aria-expanded','false');
		}
	});
	document.addEventListener('keydown', function(e){
		if (e.key==='Escape'){
			menu.classList.remove('wpg-dd-open');
			btn.setAttribute('aria-expanded','false');
		}
	});
})();
</script>
JS;

		return $html;
	}

	// ── Build logo with accent word ───────────────────────────────────────────
	private function build_logo_html( $text, $accent_word ) {
		if ( ! $text ) { return ''; }
		$words  = explode( ' ', $text );
		$target = $accent_word ? strtolower( $accent_word ) : strtolower( end( $words ) );
		$out    = '';
		foreach ( $words as $word ) {
			if ( strtolower( $word ) === $target ) {
				$out .= '<span class="wpg-accent">' . esc_html( $word ) . '</span> ';
			} else {
				$out .= '<span>' . esc_html( $word ) . '</span> ';
			}
		}
		return trim( $out );
	}
}

new WPG_Divi_Navbar();
