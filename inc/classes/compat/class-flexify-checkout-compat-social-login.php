<?php
/**
 * Flexify_Checkout_Compat_Social_Login.
 *
 * Compatibility with Social Login.
 *
 * @package Flexify_Checkout
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Social_Login' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Social_Login.
 *
 * @class    Flexify_Checkout_Compat_Social_Login.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Social_Login {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'compat_social_login' ) );
	}

	/**
	 * Add social login compatibility.
	 */
	public static function compat_social_login() {
		if ( ! function_exists( 'wc_social_login' ) ) {
			return;
		}

		$social_login = wc_social_login();

		remove_action( 'woocommerce_login_form_end', array( $social_login->get_frontend_instance(), 'render_social_login_buttons' ) );
		add_action( 'woocommerce_login_form_start', array( $social_login->get_frontend_instance(), 'render_social_login_buttons' ) );
	}
}
