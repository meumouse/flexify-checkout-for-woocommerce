<?php

namespace MeuMouse\Flexify_Checkout\Compat\Cielo_Loja5;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Cielo API - Loja5
 * 
 * @since 3.2.0
 * @version 3.7.0
 * @package MeuMouse.com
 */
class Cielo_Loja5 {
	/**
	 * Construct function.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		self::run();
	}

	/**
	 * Run.
	 */
	public static function run() {
		if ( ! class_exists('WC_Gateway_Loja5_Woo_Cielo_Webservice') ) {
			return;    
		}
		
		remove_filter( 'woocommerce_order_button_html', 'loja5_woo_cielo_webservice_custom_order_button_html' );
	}
}

new Cielo_Loja5();
