<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Astra theme
 *
 * @since 1.0.0
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Astra {

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @version 3.9.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'compat_astra' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'compat_astra_dequeue_scripts' ), 100 );
		add_action( 'wp_print_scripts', array( $this, 'compat_astra_dequeue_scripts' ), 100 );
	}
	

	/**
	 * Disable astra checkout customizations
	 * 
	 * @since 1.0.0
     * @version 5.0.0
	 * @return void
	 */
	public function compat_astra() {
		if ( ! class_exists('Astra_Woocommerce') || ! is_flexify_checkout() ) {
			return;
		}
		
		$astra = \Astra_Woocommerce::get_instance();

		remove_action( 'wp', array( $astra, 'woocommerce_checkout' ) );
		remove_action( 'wp_enqueue_scripts', array( $astra, 'add_styles' ) );
		remove_filter( 'woocommerce_enqueue_styles', array( $astra, 'woo_filter_style' ) );
		add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
		remove_filter( 'woocommerce_cart_item_remove_link', array( $astra, 'change_cart_close_icon' ), 10, 2 );
	}


	/**
	 * Dequeue scripts
	 * 
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public function compat_astra_dequeue_scripts() {
		if ( ! is_flexify_template() ) {
			return;
		}

		wp_dequeue_script('astra-checkout-persistence-form-data');
		wp_dequeue_style('astra-addon-css');
		wp_deregister_style('astra-addon-css');
		wp_dequeue_style('astra-theme-css');
		wp_deregister_style('astra-theme-css');
	}
}

new Astra();