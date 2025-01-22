<?php

namespace MeuMouse\Flexify_Checkout;

use MeuMouse\Flexify_Checkout\Init;
use MeuMouse\Flexify_Checkout\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Order class
 *
 * @since 1.0.0
 * @version 3.9.8
 * @package MeuMouse.com
 */
class Order {

	/**
	 * Construct function
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'maybe_assign_guest_order_to_existing_customer' ) );
	}


	/**
	 * For the guest orders, check if there exists a user with matching email.
	 * If it does then assign this order to the user.
	 *
	 * @since 1.0.0
	 * @version 3.7.0
	 * @param int $order_id | Order ID
	 * @return void
	 */
	public static function maybe_assign_guest_order_to_existing_customer( $order_id ) {
		if ( Init::get_setting('enable_assign_guest_orders') !== 'yes' ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( empty( $order ) ) {
			return;
		}

		if ( 0 !== $order->get_user_id() ) {
			return;
		}

		$email = $order->get_billing_email();

		// Check if there is an existing user with the given email address.
		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			return;
		}

		// Assign the order to the existing user
		$order->set_customer_id( $user->ID );
		$order->save();
	}


	/**
	 * Get customer fragment from order based on billing fields
	 *
	 * @since 3.9.8
	 * @param WC_Order $order | Order object
	 * @return array
	 */
	public static function get_order_customer_fragment( $order ) {
		if ( ! isset( $order ) ) {
			return array();
		}

		// get all registered billing fields
		$billing_fields = Helpers::export_all_checkout_fields();
		$fragment_data = array();

		// iterate for each billing fields
		foreach ( $billing_fields as $field_id => $field_data ) {
			// remove prefix 'billing_' for build the fragment key
			$key = str_replace( 'billing_', '', $field_id );

			// get the billing value saved on order
			$fragment_data[$key] = $order->get_meta( $field_id, true ) ?: Helpers::get_billing_field( $order, $key );
		}

		/**
		 * Filter: Customize the order customer fragment data.
		 *
		 * @since 3.9.8
		 * @param array $fragment_data | Customer fragment data
		 * @param WC_Order $order | Order object
		 */
		return apply_filters( 'flexify_checkout_order_customer_fragments', $fragment_data, $order );
	}


	/**
	 * Get shipping method names from an order, separated by commas
	 *
	 * @since 3.9.8
	 * @param WC_Order $order | Order object
	 * @return string Comma-separated shipping method names
	 */
	public static function get_order_shipping_methods( $order ) {
		if ( ! isset( $order ) ) {
			return '';
		}

		// get all shipping methods from order
		$shipping_methods = $order->get_shipping_methods();
		$shipping_labels = array();

		// iterate for each shipping method for get the names
		foreach ( $shipping_methods as $shipping_method ) {
			$label = $shipping_method->get_method_title();

			if ( ! empty( $label ) ) {
				$shipping_labels[] = $label;
			}
		}

		// return shipping method names comma separated
		return implode( ', ', $shipping_labels );
	}
}

new Order();

if ( ! class_exists('MeuMouse\Flexify_Checkout\Order\Order') ) {
    class_alias( 'MeuMouse\Flexify_Checkout\Order', 'MeuMouse\Flexify_Checkout\Order\Order' );
}