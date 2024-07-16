<?php

namespace MeuMouse\Flexify_Checkout\Compat\EpicJungle;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with EpicJungle theme.
 * 
 * @since 1.8.0
 * @version 3.7.0
 * @package MeuMouse.com
 */
class EpicJungle {
	/**
	 * Construct function.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'compat_epicjungle' ) );
	}


	/**
	 * Disable EpicJungle checkout customisations.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function compat_epicjungle() {
		if ( ! class_exists('EpicJungle') ) {
			return;
		}

		remove_action( 'woocommerce_after_checkout_form', 'woocommerce_checkout_coupon_form' );
		remove_filter( 'woocommerce_checkout_fields', 'epicjungle_change_priority_fields_checkout' );
		remove_filter( 'woocommerce_checkout_fields', 'epicjungle_priority_fields_integrate_brazilian_market', 30, 1 );
	}
}

new EpicJungle();