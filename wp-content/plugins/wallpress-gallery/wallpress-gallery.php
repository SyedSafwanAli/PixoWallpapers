<?php
/**
 * Plugin Name: WallPress Gallery
 * Plugin URI:  https://yoursite.com
 * Description: A complete image gallery plugin with dark dashboard, bulk upload, shortcodes, and full SEO plugin compatibility.
 * Version:     1.4.5
 * Author:      Your Name
 * Author URI:  https://yoursite.com
 * Text Domain: wallpress-gallery
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Constants ───────────────────────────────────────────────────────────────
define( 'WPG_VERSION',  '1.4.5' );
define( 'WPG_PATH',     plugin_dir_path( __FILE__ ) );
define( 'WPG_URL',      plugin_dir_url( __FILE__ ) );
define( 'WPG_BASENAME', plugin_basename( __FILE__ ) );

// ─── Load Text Domain ─────────────────────────────────────────────────────────
add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'wallpress-gallery', false, dirname( WPG_BASENAME ) . '/languages' );
} );

// ─── Include Core Files ───────────────────────────────────────────────────────
require_once WPG_PATH . 'includes/class-post-types.php';
require_once WPG_PATH . 'includes/class-meta-boxes.php';
require_once WPG_PATH . 'includes/class-admin-pages.php';
require_once WPG_PATH . 'includes/class-bulk-upload.php';
require_once WPG_PATH . 'includes/class-shortcodes.php';
require_once WPG_PATH . 'includes/class-ajax.php';
require_once WPG_PATH . 'includes/class-settings.php';

// ─── Preserve original image quality (no WP auto-scaling / compression) ──────
// Disable the 2560px big-image scale-down and force 100% JPEG quality so that
// images uploaded via the plugin's media uploader are stored losslessly.
// Priority 9999 ensures this runs after any theme/plugin that might re-enable scaling.
add_filter( 'big_image_size_threshold', '__return_false', 9999 );
add_filter( 'jpeg_quality',          function() { return 100; }, 9999 );
add_filter( 'wp_editor_set_quality', function() { return 100; }, 9999 );

// ─── Category Archive Template ────────────────────────────────────────────────
// If admin has set a Divi-designed page as the category archive template, serve
// that page's content for every wpg_category archive while keeping
// get_queried_object() returning the actual taxonomy term so [wpg_category_page]
// can auto-detect which category to display.
add_filter( 'template_include', function ( $template ) {
	if ( ! is_tax( 'wpg_category' ) ) {
		return $template;
	}

	$page_id = absint( get_option( 'wpg_cat_archive_page_id', 0 ) );

	if ( $page_id ) {
		$page = get_post( $page_id );
		if ( $page && 'publish' === $page->post_status ) {
			// Swap the main query's post list with the template page so Divi
			// renders its saved layout, but leave queried_object untouched so
			// is_tax() / get_queried_object() still return the taxonomy term.
			global $wp_query;
			$wp_query->posts      = [ $page ];
			$wp_query->post       = $page;
			$wp_query->found_posts = 1;
			$wp_query->post_count  = 1;
			$GLOBALS['post']       = $page;

			// Use Divi's page.php (or singular.php / index.php as fallback)
			$divi_tpl = locate_template( [ 'page.php', 'singular.php', 'index.php' ] );
			if ( $divi_tpl ) {
				return $divi_tpl;
			}
		}
	}

	// Fallback: plain plugin template (no Divi, but still renders the shortcode)
	$plugin_tpl = WPG_PATH . 'templates/taxonomy-wpg_category.php';
	if ( file_exists( $plugin_tpl ) ) {
		return $plugin_tpl;
	}

	return $template;
} );

// Remove sidebar + give full-width content on category archive
add_filter( 'body_class', function ( $classes ) {
	if ( is_tax( 'wpg_category' ) ) {
		$classes[] = 'et_full_width_page';
		$classes[] = 'wpg-category-archive';
	}
	return $classes;
} );

// ─── Divi Modules (lazy-loaded when Divi builder is ready) ───────────────────
add_action( 'et_builder_ready', 'wpg_load_divi_modules' );
function wpg_load_divi_modules() {
	if ( ! class_exists( 'ET_Builder_Module' ) ) {
		return;
	}
	require_once WPG_PATH . 'includes/class-divi-modules.php';
	new WPG_Divi_Modules();
}

// ─── Activation Hook ──────────────────────────────────────────────────────────
register_activation_hook( __FILE__, 'wpg_activate' );
function wpg_activate() {
	// Register post types so rewrite rules flush correctly
	WPG_Post_Types::register();
	flush_rewrite_rules();

	// Default options
	$defaults = [
		'wpg_per_page'            => 12,
		'wpg_default_grid'        => 'uniform',
		'wpg_accent_color'        => '#7fff00',
		'wpg_google_fonts'        => 1,
		'wpg_enable_4k_download'  => 1,
		'wpg_enable_orig_download'=> 1,
		'wpg_track_downloads'     => 1,
		'wpg_infinite_scroll'     => 0,
		'wpg_lazy_load'           => 1,
		'wpg_images_per_ajax'     => 12,
		'wpg_enable_schema'       => 0,
	];
	foreach ( $defaults as $key => $value ) {
		if ( false === get_option( $key ) ) {
			add_option( $key, $value );
		}
	}

	// Seed default wallpaper categories
	wpg_seed_default_categories();
}

// ─── Default Categories Seeder ────────────────────────────────────────────────
function wpg_seed_default_categories() {
	$categories = [
		'Abstract'     => 'abstract',
		'Animals'      => 'animals',
		'Anime'        => 'anime',
		'Architecture' => 'architecture',
		'Bikes'        => 'bikes',
		'Black & Dark' => 'black-dark',
		'Cars'         => 'cars',
		'Celebrations' => 'celebrations',
		'Cute'         => 'cute',
		'Fantasy'      => 'fantasy',
		'Flowers'      => 'flowers',
		'Food'         => 'food',
		'Games'        => 'games',
		'Gradients'    => 'gradients',
		'CGI'          => 'cgi',
		'Lifestyle'    => 'lifestyle',
		'Love'         => 'love',
		'Military'     => 'military',
		'Minimal'      => 'minimal',
		'Movies'       => 'movies',
		'Music'        => 'music',
		'Nature'       => 'nature',
		'People'       => 'people',
		'Photography'  => 'photography',
		'Quotes'       => 'quotes',
		'Sci-Fi'       => 'sci-fi',
		'Space'        => 'space',
		'Sports'       => 'sports',
		'Technology'   => 'technology',
		'World'        => 'world',
	];

	foreach ( $categories as $name => $slug ) {
		if ( ! term_exists( $slug, 'wpg_category' ) ) {
			wp_insert_term( $name, 'wpg_category', [ 'slug' => $slug ] );
		}
	}
}

// ─── Resolution Helpers ───────────────────────────────────────────────────────
function wpg_get_default_resolutions() {
	return [
		'desktop' => [
			[ 'res' => '1920×1080', 'label' => 'Full HD 1080p',          'compat' => '1280×720, 1600×900, 1366×768' ],
			[ 'res' => '1920×1200', 'label' => 'Widescreen HD',          'compat' => '1280×800, 1440×900, 1680×1050' ],
			[ 'res' => '2560×1080', 'label' => '21:9 UltraWide HD',      'compat' => '' ],
			[ 'res' => '2560×1440', 'label' => '2K | QHD',               'compat' => '' ],
			[ 'res' => '2880×1800', 'label' => 'Retina Widescreen',      'compat' => '2560×1600' ],
			[ 'res' => '3440×1440', 'label' => '21:9 UltraWide QHD',     'compat' => '' ],
			[ 'res' => '3840×1080', 'label' => 'Dual Monitor HD',        'compat' => '' ],
			[ 'res' => '3840×2160', 'label' => '4K UHD',                 'compat' => '' ],
			[ 'res' => '4480×2520', 'label' => '4.5K iMac 2023',         'compat' => '' ],
			[ 'res' => '5120×2880', 'label' => '5K',                     'compat' => '' ],
		],
		'mobile'  => [
			[ 'res' => '480×800',   'label' => '',                       'compat' => '' ],
			[ 'res' => '768×1024',  'label' => '',                       'compat' => '' ],
			[ 'res' => '720×1280',  'label' => 'HD Phone',               'compat' => '' ],
			[ 'res' => '1080×1920', 'label' => 'Mobile Phone Full HD',   'compat' => '' ],
			[ 'res' => '1080×2160', 'label' => 'Vertical Full HD',       'compat' => '' ],
			[ 'res' => '1080×2340', 'label' => 'Smartphone Full HD+',    'compat' => '' ],
			[ 'res' => '1080×2400', 'label' => 'Vertical Full HD+',      'compat' => '' ],
			[ 'res' => '1440×2560', 'label' => 'Mobile Phone QHD | 2K',  'compat' => '' ],
		],
		'tablet'  => [
			[ 'res' => '1024×768',  'label' => 'XGA',                    'compat' => '' ],
			[ 'res' => '1280×800',  'label' => 'WXGA',                   'compat' => '' ],
			[ 'res' => '1366×768',  'label' => 'HD Tablet',              'compat' => '' ],
			[ 'res' => '1600×900',  'label' => 'HD+ Tablet',             'compat' => '' ],
			[ 'res' => '1920×1200', 'label' => 'FHD+ Tablet',            'compat' => '' ],
			[ 'res' => '2048×1536', 'label' => 'iPad Retina',            'compat' => '' ],
			[ 'res' => '2560×1600', 'label' => 'WQXGA Tablet',          'compat' => '' ],
			[ 'res' => '2732×2048', 'label' => 'iPad Pro 12.9"',         'compat' => '' ],
		],
		'iphone'  => [
			[ 'res' => '640×1136',  'label' => 'iPhone 5, 5S',                            'compat' => '' ],
			[ 'res' => '750×1334',  'label' => 'iPhone 6, 7, 8',                          'compat' => '' ],
			[ 'res' => '1242×2208', 'label' => 'iPhone 6 Plus, 7 Plus, 8 Plus',           'compat' => '' ],
			[ 'res' => '1125×2436', 'label' => 'iPhone X, XS, 11 Pro',                    'compat' => '' ],
			[ 'res' => '1242×2688', 'label' => 'iPhone XS Max, 11 Pro Max',               'compat' => '' ],
			[ 'res' => '1170×2532', 'label' => 'iPhone 12, 12 Pro, iPhone 13, 13 Pro',    'compat' => '' ],
			[ 'res' => '1080×2340', 'label' => 'iPhone 12 Mini, iPhone 13 Mini',          'compat' => '' ],
			[ 'res' => '1284×2778', 'label' => 'iPhone 12 Pro Max, 13 Pro Max, 14 Plus',  'compat' => '' ],
			[ 'res' => '1179×2556', 'label' => 'iPhone 14, 14 Pro, 15 Pro, iPhone 16',    'compat' => '' ],
			[ 'res' => '1290×2796', 'label' => 'iPhone 14 Pro Max, 15 Pro Max, 16 Plus',  'compat' => '' ],
			[ 'res' => '1206×2622', 'label' => 'iPhone 16 Pro, iPhone 17, 17 Pro',        'compat' => '' ],
			[ 'res' => '1320×2868', 'label' => 'iPhone 16 Pro Max, iPhone 17 Pro Max',    'compat' => '' ],
			[ 'res' => '1260×2736', 'label' => 'iPhone Air',                              'compat' => '' ],
		],
		'ipad'    => [
			[ 'res' => '768×1024',  'label' => 'iPad Mini (1st-4th gen)',  'compat' => '' ],
			[ 'res' => '1536×2048', 'label' => 'iPad Mini Retina',         'compat' => '' ],
			[ 'res' => '1668×2224', 'label' => 'iPad Pro 10.5", iPad Air', 'compat' => '' ],
			[ 'res' => '1668×2388', 'label' => 'iPad Pro 11"',             'compat' => '' ],
			[ 'res' => '2048×2732', 'label' => 'iPad Pro 12.9"',           'compat' => '' ],
			[ 'res' => '2360×1640', 'label' => 'iPad Air 10.9"',           'compat' => '' ],
			[ 'res' => '2388×1668', 'label' => 'iPad Pro 11" M4',          'compat' => '' ],
			[ 'res' => '2752×2064', 'label' => 'iPad Pro 13" M4',          'compat' => '' ],
		],
	];
}

function wpg_get_resolutions() {
	$saved = get_option( 'wpg_resolutions', null );
	if ( $saved === null ) {
		return wpg_get_default_resolutions();
	}
	return $saved;
}

// AJAX: save resolutions (admin only)
add_action( 'wp_ajax_wpg_save_resolutions', 'wpg_ajax_save_resolutions' );
function wpg_ajax_save_resolutions() {
	check_ajax_referer( 'wpg_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'Unauthorized' ); }

	$device  = sanitize_key( $_POST['device']  ?? '' );
	$res     = sanitize_text_field( wp_unslash( $_POST['res']     ?? '' ) );
	$label   = sanitize_text_field( wp_unslash( $_POST['label']   ?? '' ) );
	$compat  = sanitize_text_field( wp_unslash( $_POST['compat']  ?? '' ) );

	$valid_devices = [ 'desktop', 'mobile', 'tablet', 'iphone', 'ipad' ];
	if ( ! $device || ! in_array( $device, $valid_devices, true ) || ! $res ) {
		wp_send_json_error( 'Invalid data' );
	}

	$all = wpg_get_resolutions();
	if ( ! isset( $all[ $device ] ) ) { $all[ $device ] = []; }
	$all[ $device ][] = [ 'res' => $res, 'label' => $label, 'compat' => $compat ];
	update_option( 'wpg_resolutions', $all );
	wp_send_json_success( [ 'message' => 'Saved.' ] );
}

// AJAX: delete resolution
add_action( 'wp_ajax_wpg_delete_resolution', 'wpg_ajax_delete_resolution' );
function wpg_ajax_delete_resolution() {
	check_ajax_referer( 'wpg_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'Unauthorized' ); }

	$device = sanitize_key( $_POST['device'] ?? '' );
	$index  = (int) ( $_POST['index'] ?? -1 );

	$all = wpg_get_resolutions();
	if ( isset( $all[ $device ][ $index ] ) ) {
		array_splice( $all[ $device ], $index, 1 );
		update_option( 'wpg_resolutions', $all );
		wp_send_json_success();
	}
	wp_send_json_error( 'Not found' );
}

// AJAX: reset resolutions to default
add_action( 'wp_ajax_wpg_reset_resolutions', 'wpg_ajax_reset_resolutions' );
function wpg_ajax_reset_resolutions() {
	check_ajax_referer( 'wpg_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'Unauthorized' ); }
	update_option( 'wpg_resolutions', wpg_get_default_resolutions() );
	wp_send_json_success();
}

// AJAX: seed categories (admin only)
add_action( 'wp_ajax_wpg_seed_categories', 'wpg_ajax_seed_categories' );
function wpg_ajax_seed_categories() {
	check_ajax_referer( 'wpg_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized' );
	}
	wpg_seed_default_categories();
	wp_send_json_success( [ 'message' => 'Default categories added.' ] );
}

// ─── Deactivation Hook ────────────────────────────────────────────────────────
register_deactivation_hook( __FILE__, 'wpg_deactivate' );
function wpg_deactivate() {
	flush_rewrite_rules();
}

// ─── Admin Assets ─────────────────────────────────────────────────────────────
add_action( 'admin_enqueue_scripts', 'wpg_admin_enqueue_scripts' );
function wpg_admin_enqueue_scripts( $hook ) {
	// Only load on our own plugin pages + wpg_image post edit screen
	$wpg_pages = [
		'toplevel_page_wallpress-gallery',
		'wallpress_page_wpg-manage-images',
		'wallpress_page_wpg-bulk-upload',
		'wallpress_page_wpg-categories',
		'wallpress_page_wpg-resolutions',
		'wallpress_page_wpg-settings',
	];

	global $post;
	$is_wpg_post = in_array( $hook, [ 'post.php', 'post-new.php' ], true )
		&& isset( $post->post_type )
		&& $post->post_type === 'wpg_image';

	if ( ! in_array( $hook, $wpg_pages, true ) && ! $is_wpg_post ) {
		return;
	}

	wp_enqueue_style(
		'wpg-admin-style',
		WPG_URL . 'admin/css/admin-style.css',
		[],
		WPG_VERSION
	);

	wp_enqueue_script(
		'wpg-admin-script',
		WPG_URL . 'admin/js/admin-script.js',
		[],
		WPG_VERSION,
		true
	);

	wp_localize_script( 'wpg-admin-script', 'wpg_admin', [
		'ajaxurl'    => admin_url( 'admin-ajax.php' ),
		'nonce'      => wp_create_nonce( 'wpg_nonce' ),
		'plugin_url' => WPG_URL,
	] );
}

// ─── Public Assets ────────────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'wpg_public_enqueue_scripts' );
function wpg_public_enqueue_scripts() {
	// Accent color from settings
	$accent   = sanitize_hex_color( get_option( 'wpg_accent_color', '#7fff00' ) ) ?: '#7fff00';
	$per_page = absint( get_option( 'wpg_per_page', 16 ) );
	$grid     = sanitize_key( get_option( 'wpg_default_grid', 'uniform' ) );

	wp_enqueue_style(
		'wpg-public-style',
		WPG_URL . 'public/css/public-style.css',
		[],
		WPG_VERSION
	);

	// Inject dynamic accent CSS variable
	wp_add_inline_style( 'wpg-public-style', ":root { --wpg-accent: {$accent}; }" );

	wp_enqueue_script(
		'wpg-public-script',
		WPG_URL . 'public/js/public-script.js',
		[],
		WPG_VERSION,
		true
	);

	wp_localize_script( 'wpg-public-script', 'wpg_public', [
		'ajaxurl'    => admin_url( 'admin-ajax.php' ),
		'nonce'      => wp_create_nonce( 'wpg_nonce' ),
		'grid_style' => $grid,
		'per_page'   => $per_page,
	] );
}

// ─── WebP Compatibility Fix ───────────────────────────────────────────────────
// Prioritise Imagick (supports WebP) over GD when both are available.
add_filter( 'wp_image_editors', function ( $editors ) {
	// Move WP_Image_Editor_Imagick to front so it handles WebP first.
	$imagick = 'WP_Image_Editor_Imagick';
	$editors = array_diff( $editors, [ $imagick ] );
	array_unshift( $editors, $imagick );
	return $editors;
} );

// If the server has neither Imagick nor GD-with-WebP, skip sub-size generation
// for WebP uploads to prevent the "cannot generate responsive image sizes" error.
add_filter( 'intermediate_image_sizes_advanced', function ( $sizes, $metadata, $attachment_id ) {
	$file = get_attached_file( $attachment_id );
	if ( $file && strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ) === 'webp' ) {
		// Check GD WebP support
		$gd_has_webp = function_exists( 'imagetypes' ) && ( imagetypes() & IMG_WEBP );
		// Check Imagick WebP support
		$imagick_has_webp = class_exists( 'Imagick' ) && in_array( 'WEBP', array_map( 'strtoupper', Imagick::queryFormats() ), true );
		if ( ! $gd_has_webp && ! $imagick_has_webp ) {
			return []; // Skip resizing — avoids the misleading error notice.
		}
	}
	return $sizes;
}, 10, 3 );

// Mark WebP as a displayable image type even when GD lacks WebP support,
// so WordPress doesn't reject the upload entirely.
add_filter( 'file_is_displayable_image', function ( $result, $path ) {
	if ( ! $result && file_exists( $path ) ) {
		$info = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( $info && isset( $info['mime'] ) && $info['mime'] === 'image/webp' ) {
			return true;
		}
	}
	return $result;
}, 10, 2 );

// ─── Schema Output (only if enabled in settings) ─────────────────────────────
add_action( 'wp_head', 'wpg_maybe_output_schema' );
function wpg_maybe_output_schema() {
	if ( ! get_option( 'wpg_enable_schema', 0 ) ) {
		return;
	}
	if ( ! is_singular( 'wpg_image' ) ) {
		return;
	}
	$post_id    = get_the_ID();
	$title      = get_the_title( $post_id );
	$thumb_url  = get_the_post_thumbnail_url( $post_id, 'full' );
	$permalink  = get_permalink( $post_id );
	$resolution = esc_html( get_post_meta( $post_id, '_wpg_resolution', true ) );
	$author     = esc_html( get_post_meta( $post_id, '_wpg_author_name', true ) );

	if ( ! $thumb_url ) {
		return;
	}

	$schema = [
		'@context'         => 'https://schema.org',
		'@type'            => 'ImageObject',
		'name'             => $title,
		'url'              => $permalink,
		'contentUrl'       => $thumb_url,
		'thumbnailUrl'     => $thumb_url,
		'description'      => get_the_excerpt( $post_id ),
	];
	if ( $resolution ) {
		$schema['description'] = $resolution;
	}
	if ( $author ) {
		$schema['author'] = [ '@type' => 'Person', 'name' => $author ];
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
