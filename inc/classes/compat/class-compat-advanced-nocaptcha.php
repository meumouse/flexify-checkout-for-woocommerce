<?php

namespace MeuMouse\Flexify_Checkout\Compat\Advanced_Nocaptcha;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Advanced_Nocaptcha.
 *
 * Compatibility with Advanced noCaptcha & invisible Captcha (v2 & v3) plugin.
 * [https://wordpress.org/plugins/advanced-nocaptcha-recaptcha/]
 *
 * @since 1.0.0
 * @version 3.7.0
 * @package MeuMouse.com
 */
class Advanced_Nocaptcha {
	/**
	 * Run.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'add_captcha' ) );
	}

	/**
	 * Add captcha field.
	 */
	public static function add_captcha() {
		if ( ! class_exists('anr_captcha_class') ) {
			return;
		}

		$anr_captcha = anr_captcha_class::init();
		add_action( 'woocommerce_review_order_before_payment', array( $anr_captcha, 'wc_form_field' ), 10 );
	}
}

new Advanced_Nocaptcha();