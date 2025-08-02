<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

use MeuMouse\Flexify_Checkout\API\License;
use MeuMouse\Flexify_Checkout\Core\Helpers;
use MeuMouse\Flexify_Checkout\Admin\Admin_Options;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Coupon related functions
 *
 * @since 1.0.0
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Coupons {

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public function __construct() {
		// Unhook default coupon form
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

		// apply coupon via URL param on load page
		add_action( 'wp', array( $this, 'auto_apply_coupon' ) );

		add_action( 'woocommerce_removed_coupon', array( __CLASS__, 'register_removed_coupon' ) );

		// Apply coupon via URL param on load page
		add_action( 'template_redirect', array( __CLASS__, 'apply_coupon_via_url' ) );

		// render coupon field
		if ( Helpers::is_coupon_enabled() ) {
			self::render_coupon_field();
		}
	}


	/**
	 * Auto apply coupon if enabled in the settings
	 * 
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public function auto_apply_coupon() {
		if ( Admin_Options::get_setting('enable_auto_apply_coupon_code') === 'no' || empty( Admin_Options::get_setting('coupon_code_for_auto_apply') ) || ! License::is_valid() ) {
			return;
		}

		$coupon = Admin_Options::get_setting('coupon_code_for_auto_apply');

		if ( empty( $coupon ) || ! is_checkout() ) {
			return;
		}

		if ( '1' === WC()->session->get('flexify_dont_auto_apply_coupon_flag') ) {
			return;
		}

		if ( ! WC()->cart->has_discount( $coupon ) ) {
			WC()->cart->apply_coupon( $coupon );
		}
	}


	/**
	 * Register removed coupon in session so we do not apply it automatically
	 *
	 * @since 1.0.0
	 * @param string $removed_coupon
	 * @return void
	 */
	public static function register_removed_coupon( $removed_coupon ) {
		$auto_coupon = self::get_auto_apply_coupon();

		if ( empty( $auto_coupon ) ) {
			return;
		}

		if ( $removed_coupon === $auto_coupon ) {
			WC()->session->set( 'flexify_dont_auto_apply_coupon_flag', '1' );
		}
	}


	/**
	 * Get Coupon to be auto applied
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return string|false
	 */
	public static function get_auto_apply_coupon() {
		if ( Admin_Options::get_setting('enable_auto_apply_coupon_code') === 'no' ) {
			return apply_filters( 'Flexify_Checkout/Coupons/Auto_Apply_Coupon', false );
		}

		/**
		 * Coupon to be auto-applied
		 *
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return string
		 */
		return apply_filters( 'Flexify_Checkout/Coupons/Auto_Apply_Coupon', Admin_Options::get_setting('coupon_code_for_auto_apply') );
	}


	/**
	 * Apply coupon via URL
	 *
	 * @since 1.0.0
	 * @version 3.9.8
	 * @return void
	 */
	public static function apply_coupon_via_url() {
		$coupon = Admin_Options::get_setting('coupon_code_for_auto_apply') && License::is_valid() ? Admin_Options::get_setting('coupon_code_for_auto_apply') : '';

		if ( empty( $coupon ) || ! is_checkout() ) {
			return;
		}

		if ( WC()->cart->has_discount( $coupon ) ) {
			return;
		}

		if ( Admin_Options::get_setting('enable_auto_apply_coupon_code') === 'yes' && License::is_valid() ) {
			WC()->cart->add_discount( sanitize_text_field( $coupon ) );
		}
	}


	/**
     * Register coupon field position hooks
     * 
     * @since 5.0.0
     * @return array
     */
    public static function get_coupon_field_position() {
        return apply_filters( 'Flexify_Checkout/Checkout/Coupon_Field_Position', array(
            'sidebar' => array(
                'title' => esc_html__( 'Barra lateral (Padrão)', 'flexify-checkout-for-woocommerce' ),
                'hook' => 'woocommerce_review_order_before_subtotal',
				'callback' => function() {
					?>
						<tr class="coupon-form">
							<td colspan="2">
								<?php Steps::render_coupon_form(); ?>
							</td>
						</tr>
					<?php
				},
            ),
            'before_payment_title' => array(
                'title' => esc_html__( 'Antes da forma de pagamento', 'flexify-checkout-for-woocommerce' ),
                'hook' => 'Flexify_Checkout/Steps/Payment/Before_Title',
				'callback' => array( '\MeuMouse\Flexify_Checkout\Checkout\Steps', 'render_coupon_form' ),
            ),
        ));
    }


	/**
	 * Render coupon field
	 * 
	 * @since 5.0.0
	 * @return void
	 */
	public static function render_coupon_field() {
		foreach ( self::get_coupon_field_position() as $position => $value ) {
			if ( Admin_Options::get_setting('render_coupon_field_hook') === $position ) {
				add_action( $value['hook'], $value['callback'], 10 );
			}
		}
	}
}