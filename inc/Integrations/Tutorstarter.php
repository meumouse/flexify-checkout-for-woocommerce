<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Tutor Starter theme
 *
 * @since 3.8.8
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Tutorstarter {
	
	/**
     * Construct function
     *
     * @since 3.8.8
     * @version 5.0.0
     * @return void
     */
    public function __construct() {
		add_action( 'init', array( $this, 'remove_filters' ), 20 );
	}


	/**
	 * Remove filters and actions
	 * 
	 * @since 3.8.8
     * @version 5.0.0
     * @return void
	 */
	public function remove_filters() {
		if ( ! is_flexify_checkout() || ! class_exists('\Tutorstarter\Init') ) {
			return;
		}

        remove_filter( 'woocommerce_order_button_html', 'tutorstarter_order_btn_html' );
	}
}

new Tutorstarter();