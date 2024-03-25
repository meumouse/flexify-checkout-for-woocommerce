<?php
/**
 * Flexify_Checkout_Compat_Martfury.
 *
 * Compatibility with Martfury.
 *
 * @package Flexify_Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Martfury' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Martfury.
 *
 * @class    Flexify_Checkout_Compat_Martfury.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Martfury {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'wp', array( __CLASS__, 'compat_martfury' ) );
	}

	/**
	 * Martfury theme compatibility.
	 */
	public static function compat_martfury() {
		if ( ! function_exists( 'martfury_quick_view_modal' ) || ! Flexify_Checkout_Core::is_checkout() ) {
			return;
		}

		global $martfury_mobile;

		remove_action( 'wp_footer', 'martfury_quick_view_modal' );
		remove_action( 'wp_footer', 'martfury_off_canvas_mobile_menu' );
		remove_action( 'wp_footer', 'martfury_off_canvas_layer' );
		remove_action( 'wp_footer', 'martfury_off_canvas_user_menu' );
		remove_action( 'wp_footer', 'martfury_back_to_top' );

		if ( $martfury_mobile ) {
			remove_action( 'wp_footer', array( $martfury_mobile, 'mobile_modal_popup' ) );
			remove_action( 'wp_footer', array( $martfury_mobile, 'navigation_mobile' ) );
		}
	}
}
