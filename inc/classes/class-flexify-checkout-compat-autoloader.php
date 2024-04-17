<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( class_exists( 'Flexify_Checkout_Compat_Autoloader' ) ) {
	return;
}

/**
 * Class to automatically load classes for compatibility checker
 *
 * @since 1.0.0
 * @version 3.3.0
 * @package MeuMouse.com
 */
class Flexify_Checkout_Compat_Autoloader {
	/**
	 * Single instance of the Flexify_Checkout_Compat_Autoloader object.
	 *
	 * @var Flexify_Checkout_Compat_Autoloader
	 */
	public static $single_instance = null;

	/**
	 * Class args.
	 *
	 * @var array
	 */
	public static $args = array();

	/**
	 * Creates/returns the single instance Flexify_Checkout_Compat_Autoloader object.
	 *
	 * @since 1.0.0
	 * @param array $args Arguments.
	 * @return Flexify_Checkout_Compat_Autoloader
	 */
	public static function run( $args = array() ) {
		if ( null === self::$single_instance ) {
			self::$args = $args;
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Construct function
	 */
	private function __construct() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'instance_compat_classes' ), 999 );
	}

	/**
	 * Autoloader
	 *
	 * Classes should reside within /inc and follow the format of
	 * ~ class-the-name.php or {{class-prefix}}The_Name ~ class-the-name.php
	 *
	 * @since 1.0.0
	 * @param string $class_name
	 */
	private static function autoload( $class_name ) {
		/**
		 * If the class being requested does not start with our prefix,
		 * we know it's not one in our project
		 */
		if ( 0 !== strpos( $class_name, self::$args['prefix'] ) ) {
			return;
		}

		$file_name = strtolower(
			str_replace(
				array( self::$args['prefix'], '_' ),
				array( '', '-' ),
				$class_name
			)
		);

		$file = self::$args['inc_path'] . 'class-flexify-checkout-' . $file_name . '.php';

		// Include found file.
		if ( file_exists( $file ) ) {
			require $file;

			return;
		}
	}


	/**
	 * Instance compat classes
	 * 
	 * @since 1.2.5
	 * @return void
	 */
	public static function instance_compat_classes() {
		Flexify_Checkout_Compat_Astra::run();
		Flexify_Checkout_Compat_Avada::run();
		Flexify_Checkout_Compat_Cielo_Loja5::run();
		Flexify_Checkout_Compat_EpicJungle::run();
		Flexify_Checkout_Compat_Flatsome::run();
		Flexify_Checkout_Compat_Breakdance::run();
		Flexify_Checkout_Compat_Germanized::run();
		Flexify_Checkout_Compat_Martfury::run();
		Flexify_Checkout_Compat_Neve::run();
		Flexify_Checkout_Compat_Sales_Booster::run();
		Flexify_Checkout_Compat_Sendcloud::run();
		Flexify_Checkout_Compat_Shopkeeper::run();
		Flexify_Checkout_Compat_Shoptimizer::run();
		Flexify_Checkout_Compat_Siteground::run();
		Flexify_Checkout_Compat_Social_Login::run();
		Flexify_Checkout_Compat_Pagarme::run();
		Flexify_Checkout_Compat_Tokoo::run();
		Flexify_Checkout_Compat_Virtue::run();
		Flexify_Checkout_Compat_Woodmart::run();
		Flexify_Checkout_Compat_Delivery_Slots::run();
		Flexify_Checkout_Compat_Advanced_Nocaptcha::run();
		Flexify_Checkout_Compat_Divi::run();
		Flexify_Checkout_Compat_Salient::run();
		Flexify_Checkout_Compat_Gift_Vouchers_Codemenschen::run();
		Flexify_Checkout_Compat_Checkout_Field_Editor_For_WooCommerce::run();
		Flexify_Checkout_Compat_Auros::run();
		Flexify_Checkout_Compat_Fastcart::run();
		Flexify_Checkout_Compat_Kadence::run();
		Flexify_Checkout_Compat_Kangu::run();
		Flexify_Checkout_Compat_Blocksy::run();
		Flexify_Checkout_Compat_WooCommerce_Subscriptions::run();
		Flexify_Checkout_Compat_Force_Sells::run();
		Flexify_Checkout_Compat_Sala::run();
		Flexify_Checkout_Compat_Stripe_Express_Checkout::run();
	}
}

new Flexify_Checkout_Compat_Autoloader();