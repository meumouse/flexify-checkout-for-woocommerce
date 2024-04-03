<?php
/**
 * Flexify_Checkout_Compat_Blocksy.
 *
 * Compatibility with Blocksy Theme.
 *
 * @package Flexify_Checkout
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Blocksy' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Blocksy.
 *
 * @class    Flexify_Checkout_Compat_Blocksy.
 */
class Flexify_Checkout_Compat_Blocksy {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'set_blocksy_global_variable' ) );
	}

	/**
	 * Set this global variable to prevent Blocksy from overriding the checkout template.
	 *
	 * @return void
	 */
	public static function set_blocksy_global_variable() {
		$GLOBALS['ct_skip_checkout'] = 1;
	}
}
