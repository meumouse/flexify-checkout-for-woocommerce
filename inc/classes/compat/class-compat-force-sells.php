<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Force Sells plugin.
 *
 * @since 1.0.0
 * @version 3.8.0
 * @package MeuMouse.com
 */
class Compat_Force_Sells {
    /**
     * Construct function.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'init', array( $this, 'hooks' ) );
    }

    /**
     * Hooks.
     */
    public function hooks() {
        if ( ! class_exists('WC_Force_Sells') ) {
            return;
        }
        
        $force_sells = \WC_Force_Sells::get_instance();

        remove_filter( 'woocommerce_cart_item_quantity', array( $force_sells, 'cart_item_quantity' ), 10 );
        add_filter( 'woocommerce_cart_item_quantity', array( $this, 'fix_quantity_markup' ), 10, 2 );
    }

    /**
     * Fix quantity markup.
     *
     * @param int $quantity Quantity.
     * @param string $cart_item_key Item key.
     *
     * @return string
     */
    public function fix_quantity_markup( $quantity, $cart_item_key ) {
        if ( isset( WC()->cart->cart_contents[$cart_item_key]['forced_by']) ) {
            return '<div class="force-sells-qty">' . esc_html__('Quantidade', 'flexify-checkout-for-woocommerce') . ':' . WC()->cart->cart_contents[$cart_item_key]['quantity'] . '</div>';
        }

        return $quantity;
    }
}

new Compat_Force_Sells();