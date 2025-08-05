<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( class_exists('WC_Gateway_Loja5_Woo_Cielo_Webservice') ) {
	/**
	 * Compatibility with Cielo API - Loja5
	 * 
	 * @since 3.2.0
	 * @version 5.0.0
	 * @see https://www.loja5.com.br/plugin-woocommerce-pagamento-cielo-api-cartao-boleto-pix-p676c271c285.html
	 * @package MeuMouse.com
	 */
	class Cielo_Loja5 {

		/**
		 * Construct function
		 * 
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return void
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'remove_filters' ) );
		}


		/**
		 * Remove filters
		 * 
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return void
		 */
		public function remove_filters() {
			if ( ! class_exists('WC_Gateway_Loja5_Woo_Cielo_Webservice') ) {
				return;    
			}
			
			remove_filter( 'woocommerce_order_button_html', 'loja5_woo_cielo_webservice_custom_order_button_html' );
		}
	}
}