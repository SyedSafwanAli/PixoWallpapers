<?php
/**
 * Admin Menu & Pages
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPG_Admin_Pages {

	public static function init() {
		add_action( 'admin_menu',             [ __CLASS__, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts',  [ __CLASS__, 'enqueue_media' ] );
	}

	public static function enqueue_media( $hook ) {
		if ( strpos( $hook, 'wallpress-gallery' ) !== false || strpos( $hook, 'wpg-' ) !== false ) {
			wp_enqueue_media();
		}
	}

	public static function register_menus() {
		// ── Top-level menu ────────────────────────────────────────────────────
		add_menu_page(
			__( 'WallPress Gallery', 'wallpress-gallery' ),
			__( 'WallPress',         'wallpress-gallery' ),
			'manage_options',
			'wallpress-gallery',
			[ __CLASS__, 'page_dashboard' ],
			'dashicons-format-gallery',
			25
		);

		// ── Dashboard (replaces default top-level callback) ────────────────────
		add_submenu_page(
			'wallpress-gallery',
			__( 'Dashboard', 'wallpress-gallery' ),
			__( 'Dashboard', 'wallpress-gallery' ),
			'manage_options',
			'wallpress-gallery',
			[ __CLASS__, 'page_dashboard' ]
		);

		// ── All Images ────────────────────────────────────────────────────────
		add_submenu_page(
			'wallpress-gallery',
			__( 'All Images', 'wallpress-gallery' ),
			__( 'All Images', 'wallpress-gallery' ),
			'manage_options',
			'wpg-manage-images',
			[ __CLASS__, 'page_manage_images' ]
		);

		// ── Add New (link to native post-new.php) ─────────────────────────────
		add_submenu_page(
			'wallpress-gallery',
			__( 'Add New', 'wallpress-gallery' ),
			__( 'Add New',  'wallpress-gallery' ),
			'manage_options',
			'wpg-add-image',
			[ __CLASS__, 'page_add_image' ]
		);

		// ── Bulk Upload ───────────────────────────────────────────────────────
		add_submenu_page(
			'wallpress-gallery',
			__( 'Bulk Upload', 'wallpress-gallery' ),
			__( 'Bulk Upload', 'wallpress-gallery' ),
			'manage_options',
			'wpg-bulk-upload',
			[ __CLASS__, 'page_bulk_upload' ]
		);

		// ── Categories ────────────────────────────────────────────────────────
		add_submenu_page(
			'wallpress-gallery',
			__( 'Categories', 'wallpress-gallery' ),
			__( 'Categories', 'wallpress-gallery' ),
			'manage_options',
			'wpg-categories',
			[ __CLASS__, 'page_categories' ]
		);

		// ── Resolutions ───────────────────────────────────────────────────────
		add_submenu_page(
			'wallpress-gallery',
			__( 'Resolutions', 'wallpress-gallery' ),
			__( 'Resolutions', 'wallpress-gallery' ),
			'manage_options',
			'wpg-resolutions',
			[ __CLASS__, 'page_resolutions' ]
		);

		// ── Settings ─────────────────────────────────────────────────────────
		add_submenu_page(
			'wallpress-gallery',
			__( 'Settings', 'wallpress-gallery' ),
			__( 'Settings', 'wallpress-gallery' ),
			'manage_options',
			'wpg-settings',
			[ __CLASS__, 'page_settings' ]
		);
	}

	// ── Page callbacks ────────────────────────────────────────────────────────

	public static function page_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wallpress-gallery' ) );
		}
		include WPG_PATH . 'admin/views/dashboard.php';
	}

	public static function page_manage_images() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wallpress-gallery' ) );
		}
		include WPG_PATH . 'admin/views/manage-images.php';
	}

	public static function page_add_image() {
		// Redirect to native WP post editor for wpg_image
		wp_safe_redirect( admin_url( 'post-new.php?post_type=wpg_image' ) );
		exit;
	}

	public static function page_bulk_upload() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wallpress-gallery' ) );
		}
		include WPG_PATH . 'admin/views/upload.php';
	}

	public static function page_categories() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wallpress-gallery' ) );
		}
		include WPG_PATH . 'admin/views/categories.php';
	}

	public static function page_resolutions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wallpress-gallery' ) );
		}
		include WPG_PATH . 'admin/views/resolutions.php';
	}

	public static function page_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wallpress-gallery' ) );
		}
		include WPG_PATH . 'admin/views/settings.php';
	}
}

WPG_Admin_Pages::init();
