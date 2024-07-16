<?php

namespace MeuMouse\Flexify_Checkout\Compat\Blocksy;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Blocksy Theme.
 *
 * @since 1.0.0
 * @version 3.7.0
 * @package MeuMouse.com
 */
class Compat_Blocksy {
	/**
	 * Construct function.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'set_blocksy_global_variable' ) );
	}

	/**
	 * Set this global variable to prevent Blocksy from overriding the checkout template.
	 *
	 * @return void
	 */
	public static function set_blocksy_global_variable() {
		if ( ! is_flexify_checkout() || ! flexify_checkout_check_theme_active('Blocksy') ) {
			return;
		}
		
		$GLOBALS['ct_skip_checkout'] = 1;
	}
}

new Compat_Blocksy();