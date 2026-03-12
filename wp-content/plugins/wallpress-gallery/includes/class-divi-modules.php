<?php
/**
 * Divi Modules Loader
 *
 * Checks for Divi/Extra/Divi Builder plugin, then loads custom modules
 * once the Divi builder framework is ready.
 *
 * @package WallPress_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPG_Divi_Modules {

	public function __construct() {
		// Only initialise when Divi's builder framework is ready
		add_action( 'et_builder_ready', [ $this, 'register_modules' ] );
	}

	/**
	 * Register modules — called on et_builder_ready.
	 * ET_Builder_Module is guaranteed to exist at this point.
	 */
	public function register_modules() {
		// Sanity check: make sure the base class is available
		if ( ! class_exists( 'ET_Builder_Module' ) ) {
			return;
		}

		require_once WPG_PATH . 'includes/divi/module-navbar.php';
		require_once WPG_PATH . 'includes/divi/module-category-filter.php';
	}
}
