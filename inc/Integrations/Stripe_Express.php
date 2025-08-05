<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Google Pay/Apple Pay express by Stripe
 *
 * @since 1.0.0
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Stripe_Express {

    /**
     * Construct function.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'wp', array( $this, 'compat_express_checkout' ) );
    }


    /**
     * Disable street number fields validation when order is placed from Google Pay/Apple Pay express
     * checkout button.
     * 
     * @since 1.0.0
     * @version 5.0.0
     * @return void
     */
    public function compat_express_checkout() {
        $wc_ajax = filter_input( INPUT_GET, 'wc-ajax' );
		
        if ( 'wc_stripe_create_order' !== $wc_ajax ) {
            return;
        }

        add_action( 'woocommerce_checkout_fields', array( $this, 'make_street_number_fields_options' ) );
    }


    /**
     * Make street number fields options
     *
     * @since 1.0.0
     * @version 5.0.0
     * @param array $fields | Checkout fields
     * @return array
     */
    public function make_street_number_fields_options( $fields ) {
        if ( isset( $fields['billing']['billing_street_number'] ) ) {
            $fields['billing']['billing_street_number']['required'] = false;
        }

        if ( isset( $fields['billing']['billing_number'] ) ) {
            $fields['billing']['billing_number']['required'] = false;
        }

        if ( isset( $fields['shipping']['shipping_street_number'] ) ) {
            $fields['shipping']['shipping_street_number']['required'] = false;
        }

        if ( isset( $fields['shipping']['shipping_number'] ) ) {
            $fields['shipping']['shipping_number']['required'] = false;
        }

        return $fields;
    }
}