<?php
/**
 * Template: wpg_category taxonomy archive
 *
 * Auto-loaded via template_include filter for all wpg_category archives.
 * Uses theme header/footer, no Divi Theme Builder setup required.
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div id="main-content" class="wpg-tax-wrap">
	<div class="wpg-tax-inner">
		<?php echo do_shortcode( '[wpg_category_page]' ); ?>
	</div>
</div>

<?php
get_footer();
