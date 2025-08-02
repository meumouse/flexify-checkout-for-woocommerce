<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( class_exists('WooCommerce_Germanized') ) {
    /**
     * Compatibility with Germanized plugin.
     *
     * @since 1.0.0
     * @version 5.0.0
     * @package MeuMouse.com
     */
    class Germanized {

        /**
         * Construct function
         *
         * @since 1.0.0
         * @return void
         */
        public function __construct() {
            add_action( 'init', array( $this, 'compat_germanized' ) );
        }

        /**
         * Germanized compatibility.
         * 
         * @since 1.0.0
         * @return void
         */
        public function compat_germanized() {
            if ( ! class_exists('WooCommerce_Germanized') ) {
                return;
            }
            
            add_action( 'woocommerce_review_order_after_payment', array( $this, 'compat_gzd_order_review_title' ), 100 );
            remove_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_back_to_cart' );
        }

        /**
         * Add title to review order for consistency.
         */
        public function compat_gzd_order_review_title() {
            echo '<h4>' . esc_html__('Resumo do pedido', 'flexify-checkout-for-woocommerce') . '</h4>';
        }
    }
}