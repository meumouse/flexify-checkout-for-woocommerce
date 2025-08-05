<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Add and manipulate WooCommerce part templates
 *
 * @since 5.0.0
 * @package MeuMouse.com
 */
class Login {

    /**
     * Construct function
     *
     * @since 5.0.0
     * @return void
     */
    public function __construct() {
        // add body class
        add_action( 'body_class', array( $this, 'update_body_class' ) );

        // force load form login template
		add_action( 'flexify_checkout_before_layout', array( $this, 'load_form_login_template' ) );
    }


    /**
	 * Add additional classes to the body tag on checkout page
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @param array $classes | Current body classes
	 * @return array
	 */
	public function update_body_class( $classes ) {
		if ( ! is_checkout() ) {
			return $classes;
		}

		$classes[] = 'flexify-checkout-enabled';

		if ( ! is_user_logged_in() && 'yes' === get_option('woocommerce_enable_checkout_login_reminder') ) {
			$classes[] = 'flexify-wc-allow-login';
		}

		return $classes;
	}


    /**
	 * Force load form login template on checkout
	 * 
	 * @since 3.9.8
	 * @version 5.0.0
	 * @return void
	 */
	public function load_form_login_template() {
		if ( ! is_flexify_template() || did_action('flexify_checkout_form_login_loaded') > 0 ) {
			return; // template has been loaded
		}
	
		$form_login_path = FLEXIFY_CHECKOUT_PATH . 'woocommerce/common/checkout/form-login.php';
	
		// check if file exists
		if ( file_exists( $form_login_path ) ) {
			include_once $form_login_path;

			do_action('flexify_checkout_form_login_loaded'); // set loaded template
		}
	}
}