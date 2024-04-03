<?php
/**
 * Flexify_Checkout_Compat_Kadence.
 *
 * Compatibility with Kadence.
 *
 * @package Flexify_Checkout
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Kadence' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Kadence.
 *
 * @class    Flexify_Checkout_Compat_Kadence.
 */
class Flexify_Checkout_Compat_Kadence {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'hooks' ) );
	}

	/**
	 * Hooks
	 */
	public static function hooks() {
		if ( ! defined( 'KADENCE_VERSION' ) ) {
			return;
		}

		add_filter( 'flexify_checkout_allowed_sources', array( __CLASS__, 'allow_kadnece_sources' ) );
	}

	/**
	 * Allow essential Kadence CSS and JS.
	 *
	 * @param array $allowed_sources Allowed sources.
	 *
	 * @return array
	 */
	public static function allow_kadnece_sources( $allowed_sources ) {
		$allowed_sources[] = site_url() . '/wp-content/themes/kadence/assets/css/global.min.css';
		$allowed_sources[] = site_url() . '/wp-content/themes/kadence/assets/css/global.css';
		$allowed_sources[] = site_url() . '/wp-content/themes/kadence/assets/js/navigation.min.js';
		return $allowed_sources;
	}
}
