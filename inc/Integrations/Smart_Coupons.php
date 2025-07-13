<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Smart Coupons plugin
 *
 * @since 1.0.0
 * @version 5.0.0
 * @link https://woocommerce.com/products/smart-coupons/
 * @package MeuMouse.com
 */
class Smart_Coupons {

    /**
     * Construct function
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'init', array( $this, 'compat_woo_smart_coupon' ) );
    }


    /**
     * Compatibility between Flexify checkout and WooCommerce Smart Coupons
     * 
     * @since 1.0.0
     * @version 5.0.0
     * @return void
     */
    public function compat_woo_smart_coupon() {
        if ( ! class_exists('WC_SC_Purchase_Credit') ) {
            return;
        }
        
        $purchase_credit_instance = \WC_SC_Purchase_Credit::get_instance();
        add_action( 'woocommerce_after_checkout_billing_form', array( $purchase_credit_instance, 'gift_certificate_receiver_detail_form' ) );
    }
}

new Smart_Coupons();