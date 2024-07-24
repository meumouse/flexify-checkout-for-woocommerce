<?php

namespace MeuMouse\Flexify_Checkout\Order;

use MeuMouse\Flexify_Checkout\Init\Init;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Order class
 *
 * @since 1.0.0
 * @version 3.7.0
 * @package MeuMouse.com
 */
class Order {
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

		// Assign the order to the existing user.
		$order->set_customer_id( $user->ID );
		$order->save();
	}
}

new Order();