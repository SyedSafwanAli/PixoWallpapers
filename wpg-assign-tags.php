<?php
/**
 * One-time script: assign 5 random tags to every wpg_image post.
 * Usage : visit  /wpg-assign-tags.php?run=1  in your browser (logged-in admin).
 * DELETE this file after running.
 */

define( 'ABSPATH_GUARD', true );
require_once __DIR__ . '/wp-load.php';

// Only allow logged-in admins
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Forbidden', 403 );
}

if ( empty( $_GET['run'] ) ) {
	echo '<p>Add <strong>?run=1</strong> to the URL to execute.</p>';
	exit;
}

$tags = [
	'Madden NFL 26',
	'Neon American football helmet',
	'Blue aesthetic',
	'Dark blue',
	'2025 games',
	'Madden NFL 26 American football',
	'2025 games 5K',
	'Windows 11 AMOLED dark mode',
	'Abstract background',
	'Black background',
	'Romantic night sky',
	'Lake reflections',
	'Wooden pier',
	'Couple',
	'Moon',
	'Woman',
	'Valentine',
	'5K dark mode',
	'Dreamlike surrealism',
	'Black Clover key art',
	'Asta',
	'Anime series',
	'Loren Gray',
	'White aesthetic',
	'American singer',
	'White background',
	'Katsuki Bakugo artwork',
	'My Hero Academia',
	'Brown background',
	'5K',
	'Itachi Uchiha',
	'Moon minimalist',
	'Red background',
	'Iconic illustration',
	'5K Naruto',
	'2026',
	'5K Xbox games',
	'5K 2026 games',
	'Dark theme',
	'Rainbow Six Siege',
];

// Make sure all tags exist in wpg_tag taxonomy first
foreach ( $tags as $tag_name ) {
	if ( ! term_exists( $tag_name, 'wpg_tag' ) ) {
		wp_insert_term( $tag_name, 'wpg_tag' );
	}
}

// Fetch all wallpaper IDs
$all_ids = get_posts( [
	'post_type'      => 'wpg_image',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
] );

if ( empty( $all_ids ) ) {
	echo '<p>No wallpapers found.</p>';
	exit;
}

$total   = count( $all_ids );
$updated = 0;

foreach ( $all_ids as $post_id ) {
	// Pick 5 unique random tags
	$keys      = array_rand( $tags, 5 );
	$chosen    = array_map( fn( $k ) => $tags[ $k ], $keys );

	// wp_set_object_terms appends by default; pass false to REPLACE existing tags
	$result = wp_set_object_terms( $post_id, $chosen, 'wpg_tag', false );

	if ( ! is_wp_error( $result ) ) {
		$updated++;
	}
}

echo "<p>Done. Updated <strong>{$updated}</strong> of <strong>{$total}</strong> wallpapers.</p>";
echo '<p style="color:red;font-weight:bold;">Delete this file now: <code>wpg-assign-tags.php</code></p>';
