<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Flexify_Checkout_Order.
 *
 * @since 1.0.0
 * @version 1.0.0
 * @package MeuMouse.com
 */
class Flexify_Checkout_Order {
	/**
	 * Run.
	 *
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
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public static function maybe_assign_guest_order_to_existing_customer( $order_id ) {
		$settings = get_option( 'flexify_checkout_settings' );

		if ( empty( $settings['enable_assign_guest_orders'] ) || $settings['enable_assign_guest_orders'] == 'no' ) {
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

		// Assign the order to the existing user.
		$order->set_customer_id( $user->ID );
		$order->save();
	}
}

new Flexify_Checkout_Order();