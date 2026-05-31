<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

class Yit_Migrate_Helper {

	private function __construct() {}

	public static function init_actions() {

		// To be able to replace the src, scripts should not be concatenated.
		if ( ! defined( 'CONCATENATE_SCRIPTS' ) ) {
			define( 'CONCATENATE_SCRIPTS', false );
		}

		$GLOBALS['concatenate_scripts'] = false;
		add_action( 'wp_default_scripts', array( __CLASS__, 'replace_scripts' ), -1 );
	}

	// Pre-register scripts on 'wp_default_scripts' action, they won't be overwritten by $wp_scripts->add().
	private static function set_script( $scripts, $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
		$script = $scripts->query( $handle, 'registered' );

		if ( $script ) {
			// If already added
			$script->src  = $src;
			$script->deps = $deps;
			$script->ver  = $ver;
			$script->args = $in_footer;

			unset( $script->extra['group'] );

			if ( $in_footer ) {
				$script->add_data( 'group', 1 );
			}
		} else {
			// Add the script
			if ( $in_footer ) {
				$scripts->add( $handle, $src, $deps, $ver, 1 );
			} else {
				$scripts->add( $handle, $src, $deps, $ver );
			}
		}
	}

	/*
	 * Enqueue jQuery migrate, and force it to be the development version.
	 *
	 * This will ensure that console errors are generated, and we can surface these to the
	 * end user in a responsible manner so that they can update their plugins and theme,
	 * or make a decision to switch to other plugin/theme if no updates are available.
	 */
	public static function replace_scripts( $scripts ) {

		self::set_script( $scripts, 'jquery-migrate', YIT_THEME_ASSETS_URL . '/js/jquery-migrate-1.4.1-wp.js', array(), '1.4.1-wp' );
		self::set_script( $scripts, 'jquery', false, array( 'jquery-core', 'jquery-migrate' ), '1.12.4-wp' );
	}

}
