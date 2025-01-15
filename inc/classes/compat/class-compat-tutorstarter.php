<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Tutor Starter theme
 *
 * @since 3.8.8
 * @package MeuMouse.com
 */
class Tutor_Starter {
	
	/**
     * Construct function
     *
     * @since 3.8.8
     * @return void
     */
    public function __construct() {
		add_action( 'init', array( __CLASS__, 'compat_tutorstarter' ), 20 );
	}


	/**
	 * Remove filters and actions
	 * 
	 * @since 3.8.8
     * @return void
	 */
	public static function compat_tutorstarter() {
		if ( ! is_flexify_checkout() || ! class_exists('Tutor_Starter\\Init') ) {
			return;
		}

        remove_filter( 'woocommerce_order_button_html', 'tutorstarter_order_btn_html' );
	}
}

new Tutor_Starter();