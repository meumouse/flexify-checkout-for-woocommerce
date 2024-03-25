<?php
/**
 * Flexify_Checkout_Compat_Neve.
 *
 * Compatibility with Neve.
 *
 * @package Flexify_Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Neve' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Neve.
 *
 * @class    Flexify_Checkout_Compat_Neve.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Neve {
	/**
	 * Run.
	 */
	public static function run() {
		add_filter( 'neve_filter_main_modules', array( __CLASS__, 'modify_modules' ) );
	}

	/**
	 * Disable Woo module.
	 *
	 * @param array $modules Array of modules.
	 *
	 * @return mixed
	 */
	public static function modify_modules( $modules ) {
		if ( ! Flexify_Checkout_Core::is_checkout( true ) ) {
			return $modules;
		}

		$key = array_search( 'Compatibility\WooCommerce', $modules );

		if ( false !== $key ) {
			unset( $modules[ $key ] );
		}

		return $modules;
	}
}
