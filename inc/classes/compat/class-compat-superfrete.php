<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with SuperFrete plugin
 *
 * @since 3.9.0
 * @package MeuMouse.com
 */
class SuperFrete {
	
	/**
     * Construct function
     *
     * @since 3.9.0
     * @return void
     */
    public function __construct() {
		add_action( 'wp', array( $this, 'remove_actions' ), 20 );
	}


    /**
     * Remove actions on checkout
     * 
     * @since 3.9.0
     * @return void
     */
    public function remove_actions() {
        if ( ! is_flexify_checkout() || ! class_exists('SuperfreteShipping') ) {
			return;
		}

        global $wp_filter;

        if ( isset( $wp_filter['woocommerce_review_order_before_order_total']->callbacks ) ) {
            foreach ( $wp_filter['woocommerce_review_order_before_order_total']->callbacks as $priority => $hooks ) {
                foreach ( $hooks as $hook_key => $hook ) {
                    if ( is_array( $hook['function'] ) && is_a( $hook['function'][0], 'SuperfreteShipping' ) && $hook['function'][1] === 'superfrete_get_shippings_to_cart' ) {
                        remove_action( 'woocommerce_review_order_before_order_total', array( $hook['function'][0], $hook['function'][1] ), $priority );
                    }
                }
            }
        }
    }
}

new SuperFrete();