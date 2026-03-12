<?php
/**
 * Register Custom Post Types and Taxonomies
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPG_Post_Types {

	public static function init() {
		add_action( 'init', [ __CLASS__, 'register' ] );
		add_action( 'init', [ __CLASS__, 'register_taxonomies' ] );

		// SEO Plugin sitemap filters
		add_filter( 'wpseo_sitemap_post_types',   [ __CLASS__, 'yoast_include_post_type' ] );
		add_filter( 'rank_math/sitemap/post_types', [ __CLASS__, 'yoast_include_post_type' ] );
		add_filter( 'wpseo_sitemap_taxonomies',    [ __CLASS__, 'yoast_include_taxonomies' ] );
	}

	/**
	 * Register wpg_image CPT
	 */
	public static function register() {
		$labels = [
			'name'               => __( 'Wallpapers',             'wallpress-gallery' ),
			'singular_name'      => __( 'Wallpaper',              'wallpress-gallery' ),
			'add_new'            => __( 'Add New',                'wallpress-gallery' ),
			'add_new_item'       => __( 'Add New Wallpaper',      'wallpress-gallery' ),
			'edit_item'          => __( 'Edit Wallpaper',         'wallpress-gallery' ),
			'new_item'           => __( 'New Wallpaper',          'wallpress-gallery' ),
			'view_item'          => __( 'View Wallpaper',         'wallpress-gallery' ),
			'search_items'       => __( 'Search Wallpapers',      'wallpress-gallery' ),
			'not_found'          => __( 'No wallpapers found',    'wallpress-gallery' ),
			'not_found_in_trash' => __( 'No wallpapers in trash', 'wallpress-gallery' ),
			'menu_name'          => __( 'Wallpapers',             'wallpress-gallery' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,   // Required for Yoast SEO & Rank Math
			'query_var'           => true,
			'has_archive'         => true,
			'rewrite'             => [
				'slug'       => 'wallpapers',
				'with_front' => false,
			],
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-format-gallery',
			'supports'            => [
				'title',
				'thumbnail',
				'excerpt',
				'custom-fields',
				'comments',
			],
			'taxonomies'          => [ 'wpg_category', 'wpg_tag' ],
			'show_in_sitemap'     => true,   // For XML sitemaps
		];

		register_post_type( 'wpg_image', $args );
	}

	/**
	 * Register wpg_category and wpg_tag taxonomies
	 */
	public static function register_taxonomies() {
		// ── Category (hierarchical) ───────────────────────────────────────────
		$cat_labels = [
			'name'              => __( 'Wallpaper Categories',  'wallpress-gallery' ),
			'singular_name'     => __( 'Wallpaper Category',    'wallpress-gallery' ),
			'search_items'      => __( 'Search Categories',    'wallpress-gallery' ),
			'all_items'         => __( 'All Categories',       'wallpress-gallery' ),
			'parent_item'       => __( 'Parent Category',      'wallpress-gallery' ),
			'parent_item_colon' => __( 'Parent Category:',     'wallpress-gallery' ),
			'edit_item'         => __( 'Edit Category',        'wallpress-gallery' ),
			'update_item'       => __( 'Update Category',      'wallpress-gallery' ),
			'add_new_item'      => __( 'Add New Category',     'wallpress-gallery' ),
			'new_item_name'     => __( 'New Category Name',    'wallpress-gallery' ),
			'menu_name'         => __( 'Categories',           'wallpress-gallery' ),
		];

		register_taxonomy( 'wpg_category', 'wpg_image', [
			'labels'            => $cat_labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'      => true,   // Required for SEO plugins
			'show_admin_column' => false,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => false,
			'rewrite'           => [
				'slug'       => 'wallpaper-category',
				'with_front' => false,
			],
			'meta_box_cb'       => false,  // We handle our own UI
		] );

		// ── Tag (non-hierarchical) ────────────────────────────────────────────
		$tag_labels = [
			'name'                       => __( 'Wallpaper Tags',  'wallpress-gallery' ),
			'singular_name'              => __( 'Tag',             'wallpress-gallery' ),
			'search_items'               => __( 'Search Tags',     'wallpress-gallery' ),
			'popular_items'              => __( 'Popular Tags',    'wallpress-gallery' ),
			'all_items'                  => __( 'All Tags',        'wallpress-gallery' ),
			'edit_item'                  => __( 'Edit Tag',        'wallpress-gallery' ),
			'update_item'                => __( 'Update Tag',      'wallpress-gallery' ),
			'add_new_item'               => __( 'Add New Tag',     'wallpress-gallery' ),
			'new_item_name'              => __( 'New Tag Name',    'wallpress-gallery' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'wallpress-gallery' ),
			'add_or_remove_items'        => __( 'Add or remove tags',        'wallpress-gallery' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'wallpress-gallery' ),
			'menu_name'                  => __( 'Tags',            'wallpress-gallery' ),
		];

		register_taxonomy( 'wpg_tag', 'wpg_image', [
			'labels'            => $tag_labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'      => true,   // Required for SEO plugins
			'show_admin_column' => false,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'rewrite'           => [
				'slug'       => 'wallpaper-tag',
				'with_front' => false,
			],
			'meta_box_cb'       => false,
		] );
	}

	// ── Yoast SEO: include wpg_image in sitemap ───────────────────────────────
	public static function yoast_include_post_type( $post_types ) {
		$post_types['wpg_image'] = 'wpg_image';
		return $post_types;
	}

	// ── Yoast SEO: include our taxonomies in sitemap ──────────────────────────
	public static function yoast_include_taxonomies( $taxonomies ) {
		$taxonomies['wpg_category'] = 'wpg_category';
		$taxonomies['wpg_tag']      = 'wpg_tag';
		return $taxonomies;
	}
}

WPG_Post_Types::init();
