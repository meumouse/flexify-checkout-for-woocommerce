<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with eRede Loja5 plugin.
 *
 * @since 3.8.0
 * @package MeuMouse.com
 */
class Compat_Erede_Loja5 {
    
    /**
     * Construct function.
     *
     * @since 3.8.0
     * @return void
     */
    public function __construct() {
        add_action( 'init', array( $this, 'compat_erede' ) );
    }


    /**
     * Add compatibility for eRede Loja5 plugin.
     * 
     * @since 3.8.0
     */
    public function compat_erede() {
        if ( ! class_exists('WC_Gateway_Loja5_Woo_Novo_Erede') || ! is_flexify_checkout() ) {
            return;
        }
        
        add_action( 'wp_head', array( __CLASS__, 'add_styles' ) );
    }


    /**
     * Add styles for beauty design
     * 
     * @since 3.8.0
     * @return string
     */
    public static function add_styles(){
        ob_start(); ?>

        .payment_box.payment_method_loja5_woo_novo_erede input,
        .payment_box.payment_method_loja5_woo_novo_erede select {
            height: 4rem !important;
            box-shadow: none !important;
        }

        .payment_box.payment_method_loja5_woo_novo_erede input::placeholder,
        .payment_box.payment_method_loja5_woo_novo_erede select::placeholder {
            color: #ADB5BD !important;
        }

        .payment_box.payment_method_loja5_woo_novo_erede select {
            border: 1px solid #e5e5e5 !important;
            padding: 1rem !important;
        }

        .payment_box.payment_method_loja5_woo_novo_erede p.form-row > label {
            top: -20px !important;
            background-color: #fff !important;
            padding: 0.5rem !important;
        }

		<?php $css = ob_get_clean();
		$css = wp_strip_all_tags( $css );

		printf( __('<style>%s</style>'), $css );
    }
}

new Compat_Erede_Loja5();