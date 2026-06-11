<?php

/**
 * Plugin Name: 			Flexify Checkout para WooCommerce
 * Description: 			Extensão que otimiza a finalização de compras em multi etapas para lojas WooCommerce.
 * Plugin URI: 				https://meumouse.com/plugins/flexify-checkout-para-woocommerce/?utm_source=wordpress&utm_medium=plugins_list&utm_campaign=flexify_checkout
 * Requires Plugins: 		woocommerce
 * Author: 					MeuMouse.com
 * Author URI: 				https://meumouse.com/?utm_source=wordpress&utm_medium=plugins_list&utm_campaign=flexify_checkout
 * Version: 				5.5.4
 * WC requires at least: 	6.0.0
 * WC tested up to: 		10.7.0
 * Requires PHP: 			7.4
 * Tested up to:      		7.0
 * Text Domain: 			flexify-checkout-for-woocommerce
 * Domain Path: 			/languages
 * 
 * @package					Flexify Checkout para WooCommerce - MeuMouse.com
 * @author					MeuMouse.com
 * @copyright 				2026 MeuMouse.com
 * @license 				Proprietary - See license.md for details
 */

namespace MeuMouse\Flexify_Checkout;

use MeuMouse\Flexify_Checkout\Core\Init;

// Exit if accessed directly.
defined('ABSPATH') || exit;

const FLEXIFY_CHECKOUT_PLUGIN_VERSION = '5.5.4';

/**
 * Composer autoload.
 */
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Legacy shim for backwards compatibility.
 *
 * @since 5.5.0
 * @deprecated 5.5.0 Use \MeuMouse\Flexify_Checkout\Core\Init::bootstrap() instead.
 */
class Flexify_Checkout {
	private static $instance = null;

	public function __construct() {
		Init::bootstrap( __FILE__, FLEXIFY_CHECKOUT_PLUGIN_VERSION );
	}

	public static function run() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

register_activation_hook( __FILE__, function() {
	Init::activate( __FILE__, FLEXIFY_CHECKOUT_PLUGIN_VERSION );
});

register_deactivation_hook( __FILE__, function() {
	Init::deactivate( __FILE__, FLEXIFY_CHECKOUT_PLUGIN_VERSION );
});

Init::bootstrap( __FILE__, FLEXIFY_CHECKOUT_PLUGIN_VERSION );