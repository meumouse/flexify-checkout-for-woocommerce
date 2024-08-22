<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with Pagar.me gateway.
 *
 * @since 2.1.0
 * @version 3.8.0
 * @link https://br.wordpress.org/plugins/pagarme-payments-for-woocommerce/
 */
class Compat_Pagarme {

    /**
     * Construct function.
     *
     * @since 2.1.0
     * @return void
     */
    public function __construct() {
        add_action( 'wp_print_styles', array( $this, 'remove_style_from_core' ), 999 );
        add_action( 'wp_head', array( $this, 'add_header_styles' ), 999 );
    }

    /**
     * Remove style from core.
     */
    public function remove_style_from_core() {
        if ( ! class_exists('Woocommerce\Pagarme\Core') ) {
            return;
        }

        wp_dequeue_style( 'front-style-' . \Woocommerce\Pagarme\Core::SLUG );
    }

    /**
     * Add header styles.
     */
    public function add_header_styles() {
        if ( ! class_exists('Woocommerce\Pagarme\Core') ) {
            return;
        }
        
        $css = '#wcmp-checkout-errors {';
        $css .= 'display: none;';
        $css .= '}';
        
        ?>
        <style type="text/css">
            <?php echo $css; ?>
        </style>
        <?php
    }
}

new Compat_Pagarme();