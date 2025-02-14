<?php

namespace MeuMouse\Flexify_Checkout\Compat;

use MeuMouse\Flexify_Checkout\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Ricky theme
 *
 * @since 3.8.8
 * @package MeuMouse.com
 */
class Ricky {

	/**
	 * Construct function
	 * 
	 * @since 3.8.8
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp', array( __CLASS__, 'remove_actions' ), 20 );
        add_action( 'wp_print_styles', array( __CLASS__, 'remove_scripts' ), 99);
	}


	/**
	 * Remove action hooks
	 * 
	 * @since 3.8.8
	 * @return void
	 */
	public static function remove_actions() {
		if ( ! Helpers::check_active_theme('Ricky') || ! is_flexify_template() ) {
			return;
		}
		
		remove_action( 'woocommerce_checkout_before_order_review', 'woocommerce_checkout_coupon_form', 10 );
		remove_action( 'wp_footer', 'ideapark_wp_footer' );
	}


    /**
     * Remove scripts on checkout and thankyou page
     * 
     * @since 3.8.8
     * @return void
     */
    public static function remove_scripts() {
        if ( Helpers::check_active_theme('Ricky') && is_flexify_template() ) {
            global $wp_styles;
    
            // iterate for each registered style
            foreach ( $wp_styles->registered as $handle => $style ) {
                if ( strpos( $style->src, 'ricky/min.css' ) !== false ) {
                    wp_dequeue_style( $handle );
                    wp_deregister_style( $handle );
                }
            }
        }      
    }
}

new Ricky();