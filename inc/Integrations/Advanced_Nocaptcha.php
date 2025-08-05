<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( class_exists('anr_captcha_class') ) {
	/**
	 * Advanced_Nocaptcha.
	 *
	 * Compatibility with Advanced noCaptcha & invisible Captcha (v2 & v3) plugin.
	 * [https://wordpress.org/plugins/advanced-nocaptcha-recaptcha/]
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @package MeuMouse.com
	 */
	class Advanced_Nocaptcha {

		/**
		 * Construct function
		 * 
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return void
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'add_captcha' ) );
		}


		/**
		 * Add captcha field
		 * 
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return void
		 */
		public function add_captcha() {
			if ( ! class_exists('anr_captcha_class') ) {
				return;
			}

			$anr_captcha = anr_captcha_class::init();
			add_action( 'woocommerce_review_order_before_payment', array( $anr_captcha, 'wc_form_field' ), 10 );
		}
	}
}