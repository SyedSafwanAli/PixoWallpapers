<?php
/**
 * Settings — WordPress Settings API
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPG_Settings {

	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
	}

	public static function register_settings() {
		// ── GENERAL ──────────────────────────────────────────────────────────
		add_settings_section( 'wpg_general', __( 'General', 'wallpress-gallery' ), '__return_false', 'wpg-settings' );

		self::field( 'wpg_per_page', __( 'Images per page', 'wallpress-gallery' ),
			'number', 'wpg_general', 'intval', 12 );

		self::field( 'wpg_default_grid', __( 'Default grid style', 'wallpress-gallery' ),
			'select', 'wpg_general', 'sanitize_key', 'uniform',
			[ 'uniform' => __( 'Uniform', 'wallpress-gallery' ), 'masonry' => __( 'Masonry', 'wallpress-gallery' ) ] );

		self::field( 'wpg_accent_color', __( 'Accent colour', 'wallpress-gallery' ),
			'color', 'wpg_general', 'sanitize_hex_color', '#7fff00' );

		self::field( 'wpg_google_fonts', __( 'Enable Google Fonts', 'wallpress-gallery' ),
			'checkbox', 'wpg_general', 'absint', 1 );

		// ── DOWNLOADS ────────────────────────────────────────────────────────
		add_settings_section( 'wpg_downloads', __( 'Downloads', 'wallpress-gallery' ), '__return_false', 'wpg-settings' );

		self::field( 'wpg_enable_4k_download', __( 'Enable 4K download', 'wallpress-gallery' ),
			'checkbox', 'wpg_downloads', 'absint', 1 );

		self::field( 'wpg_enable_orig_download', __( 'Enable original download', 'wallpress-gallery' ),
			'checkbox', 'wpg_downloads', 'absint', 1 );

		self::field( 'wpg_track_downloads', __( 'Track download counts', 'wallpress-gallery' ),
			'checkbox', 'wpg_downloads', 'absint', 1 );

		// ── PERFORMANCE ───────────────────────────────────────────────────────
		add_settings_section( 'wpg_performance', __( 'Performance', 'wallpress-gallery' ), '__return_false', 'wpg-settings' );

		self::field( 'wpg_infinite_scroll', __( 'Infinite scroll', 'wallpress-gallery' ),
			'checkbox', 'wpg_performance', 'absint', 0 );

		self::field( 'wpg_lazy_load', __( 'Lazy load images', 'wallpress-gallery' ),
			'checkbox', 'wpg_performance', 'absint', 1 );

		self::field( 'wpg_images_per_ajax', __( 'Images per AJAX load', 'wallpress-gallery' ),
			'number', 'wpg_performance', 'intval', 12 );

		// ── SEO ───────────────────────────────────────────────────────────────
		add_settings_section( 'wpg_seo', __( 'SEO', 'wallpress-gallery' ), '__return_false', 'wpg-settings' );

		self::field( 'wpg_enable_schema', __( 'Output ImageObject schema', 'wallpress-gallery' ),
			'checkbox', 'wpg_seo', 'absint', 0,
			[],
			'<span style="color:#e65c00;font-weight:500;">' .
			__( 'Disable this if you are using Yoast SEO, Rank Math, or All in One SEO — they handle schema automatically.', 'wallpress-gallery' ) .
			'</span>'
		);

		// ── CATEGORY ARCHIVE TEMPLATE ─────────────────────────────────────────
		add_settings_section( 'wpg_template', __( 'Category Archive Template', 'wallpress-gallery' ), '__return_false', 'wpg-settings' );

		self::field( 'wpg_cat_archive_page_id', __( 'Category archive page', 'wallpress-gallery' ),
			'page_select', 'wpg_template', 'absint', 0,
			[],
			'<span>' .
			__( 'Select a page you designed in Divi Builder. Add the <code>[wpg_category_page]</code> shortcode to it. This page will be used as the template for <strong>all</strong> category archive pages — the correct category is detected automatically.', 'wallpress-gallery' ) .
			'</span>'
		);
	}

	/**
	 * Helper to register a setting + add_settings_field in one call.
	 *
	 * @param string   $key         Option key.
	 * @param string   $label       Field label.
	 * @param string   $type        'text'|'number'|'checkbox'|'select'|'color'|'page_select'.
	 * @param string   $section     Section id.
	 * @param callable $sanitize    Sanitize callback.
	 * @param mixed    $default     Default value.
	 * @param array    $options     For select fields: ['value' => 'Label'].
	 * @param string   $description Additional description HTML.
	 */
	private static function field( $key, $label, $type, $section, $sanitize, $default = '', $options = [], $description = '' ) {
		register_setting( 'wpg_settings_group', $key, [
			'sanitize_callback' => $sanitize,
			'default'           => $default,
		] );

		add_settings_field(
			$key,
			$label,
			function () use ( $key, $type, $default, $options, $description ) {
				$value = get_option( $key, $default );
				switch ( $type ) {
					case 'number':
						echo '<input type="number" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="small-text" />';
						break;
					case 'checkbox':
						echo '<input type="checkbox" name="' . esc_attr( $key ) . '" value="1" ' . checked( 1, $value, false ) . ' />';
						break;
					case 'color':
						echo '<input type="color" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
						break;
					case 'select':
						echo '<select name="' . esc_attr( $key ) . '">';
						foreach ( $options as $val => $opt_label ) {
							echo '<option value="' . esc_attr( $val ) . '" ' . selected( $value, $val, false ) . '>' . esc_html( $opt_label ) . '</option>';
						}
						echo '</select>';
						break;
					case 'page_select':
						wp_dropdown_pages( [
							'name'              => esc_attr( $key ),
							'selected'          => absint( $value ),
							'show_option_none'  => __( '— Select a page —', 'wallpress-gallery' ),
							'option_none_value' => '0',
						] );
						break;
					default:
						echo '<input type="text" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
				}
				if ( $description ) {
					echo '<p class="description">' . wp_kses_post( $description ) . '</p>';
				}
			},
			'wpg-settings',
			$section
		);
	}
}

WPG_Settings::init();
