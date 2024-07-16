<?php

namespace MeuMouse\Flexify_Checkout\Compat\Smart_Coupon;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Smart Coupon.
 *
 * @since 1.0.0
 * @version 3.7.0
 * @package MeuMouse.com
 */
class Compat_Smart_Coupon {

    /**
     * Construct function.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'init', array( $this, 'compat_woo_smart_coupon' ) );
    }

    /**
     * Compatibility between Flexify checkout and WooCommerce Smart Coupons [https://woocommerce.com/products/smart-coupons/]
     */
    public function compat_woo_smart_coupon() {
        if ( ! class_exists('WC_SC_Purchase_Credit') ) {
            return;
        }
        
        $woo_smart_coupon_purchase_credit = \WC_SC_Purchase_Credit::get_instance();
        add_action( 'woocommerce_after_checkout_billing_form', array( $woo_smart_coupon_purchase_credit, 'gift_certificate_receiver_detail_form' ) );
    }
}

new Compat_Smart_Coupon();