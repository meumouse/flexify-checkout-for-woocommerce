<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Add and manipulate checkout fragments
 *
 * @since 5.0.0
 * @package MeuMouse.com
 */
class Fragments {

    /**
     * Construct function
     * 
     * @since 5.0.0
     * @return void
     */
    public function __construct() {
        // update fragments on update_order_review event
		add_action( 'woocommerce_update_order_review_fragments', array( $this, 'update_order_review_framents' ) );
		
        // replace empty cart fragment
        add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'override_empty_cart_fragment' ) );
    }


    /**
	 * Update order review fragments
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @param array $fragments | Current checkout fragments
	 * @return array
	 */
	public function update_order_review_framents( $fragments ) {
		$session_key = WC()->session->get('flexify_checkout_ship_different_address') === 'yes' ? 'shipping' : 'billing';
		
		$fragments['.flexify-review-customer'] = Steps::render_customer_review();
		$fragments['.flexify-checkout-review-customer-contact'] = Steps::replace_placeholders( Admin_Options::get_setting('text_contact_customer_review'), Steps::get_review_customer_fragment() );
		$fragments['.flexify-checkout-review-shipping-address'] = Steps::replace_placeholders( Admin_Options::get_setting('text_shipping_customer_review'), Steps::get_review_customer_fragment(), $session_key );
		$fragments['.flexify-checkout-review-shipping-method'] = Helpers::get_shipping_method();

		// start buffer
		ob_start();

		wc_get_template('checkout/cart-heading.php');

		$fragments['.flexify-heading--order-review'] = ob_get_clean();

		$new_fragments = array(
			'total' => WC()->cart->get_total(),
			'shipping_row' => Steps::get_shipping_row(),
			'shipping_options' => Steps::get_shipping_options_fragment(),
        	'payment_options' => Steps::get_payment_options_fragment(),
		);

		if ( isset( $fragments['flexify'] ) ) {
			$fragments['flexify'] = array_merge( $fragments['flexify'], $new_fragments );
		} else {
			$fragments['flexify'] = $new_fragments;
		}

		return $fragments;
	}


	/**
	 * Override empty cart fragment
	 *
	 * @since 1.0.0
     * @version 5.0.0
	 * @param array $fragments | Checkout fragments
	 * @return array
	 */
	public function override_empty_cart_fragment( $fragments ) {
		if ( ! WC()->cart->is_empty() || is_customize_preview() ) {
			return $fragments;
		}

        // remove form.woocommerce-checkout fragment
		unset( $fragments['form.woocommerce-checkout'] );

        // start buffer
		ob_start();

		include_once( FLEXIFY_CHECKOUT_PATH . 'woocommerce/common/checkout/empty-cart.php' );

		$fragments['flexify'] = array(
			'empty_cart' => ob_get_clean(),
		);

		return $fragments;
	}
}