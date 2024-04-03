<?php
/**
 * Flexify_Checkout_Compat_Force_Sells.
 *
 * Compatibility with Force Sells(https://woocommerce.com/products/force-sells/).
 *
 * @package Flexify_Checkout
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Force_Sells' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Force_Sells.
 *
 * @class    Flexify_Checkout_Compat_Force_Sells.
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Force_Sells {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'hooks' ) );
	}

	/**
	 * Hooks.
	 */
	public static function hooks() {
		if ( ! class_exists( 'WC_Force_Sells' ) ) {
			return;
		}

		$force_sells = WC_Force_Sells::get_instance();

		remove_filter( 'woocommerce_cart_item_quantity', array( $force_sells, 'cart_item_quantity' ), 10 );
		add_filter( 'woocommerce_cart_item_quantity', array( __CLASS__, 'fix_quantity_markup' ), 10, 2 );
	}

	/**
	 * Fix quantity markup.
	 *
	 * @param int    $quantity      Quantity.
	 * @param string $cart_item_key Item key.
	 *
	 * @return string
	 */
	public static function fix_quantity_markup( $quantity, $cart_item_key ) {
		if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['forced_by'] ) ) {
			return '<div class="force-sells-qty">' . esc_html__( 'Quantidade', 'flexify-checkout-for-woocommerce' ) . ':' . WC()->cart->cart_contents[ $cart_item_key ]['quantity'] . '</div>';
		}

		return $quantity;
	}
}
