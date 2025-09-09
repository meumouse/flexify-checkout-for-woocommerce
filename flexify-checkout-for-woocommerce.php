<?php

/**
 * Plugin Name: 			Flexify Checkout para WooCommerce
 * Description: 			Extensão que otimiza a finalização de compras em multi etapas para lojas WooCommerce.
 * Plugin URI: 				https://meumouse.com/plugins/flexify-checkout-para-woocommerce/?utm_source=wordpress&utm_medium=plugins_list&utm_campaign=flexify_checkout
 * Requires Plugins: 		woocommerce
 * Author: 					MeuMouse.com
 * Author URI: 				https://meumouse.com/?utm_source=wordpress&utm_medium=plugins_list&utm_campaign=flexify_checkout
 * Version: 				5.2.1
 * WC requires at least: 	6.0.0
 * WC tested up to: 		10.1.2
 * Requires PHP: 			7.4
 * Tested up to:      		6.8.2
 * Text Domain: 			flexify-checkout-for-woocommerce
 * Domain Path: 			/languages
 * 
 * @package					Flexify Checkout para WooCommerce - MeuMouse.com
 * @author					MeuMouse.com
 * @copyright 				2025 MeuMouse.com
 * @license 				Proprietary - See license.md for details
 */

namespace MeuMouse\Flexify_Checkout;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Flexify_Checkout
 * 
 * @since 1.0.0
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Flexify_Checkout {

	/**
	 * The single instance of Flexify_Checkout class
	 *
	 * @since 5.0.0
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Plugin slug
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public static $slug = 'flexify-checkout-for-woocommerce';

	/**
	 * Plugin version
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public static $version = '5.2.1';

	/**
	 * Plugin initiated
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public $initiated = false;

	/**
	 * Construct the plugin
	 * 
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public function __construct() {
		// hook before plugin init
		do_action('Flexify_Checkout/Before_Init');

		// initialize plugin
		add_action( 'init', array( $this, 'init' ), 99 );

		// set compatibility with HPOS
		add_action( 'before_woocommerce_init', array( $this, 'declare_woo_compatibility' ) );
	}


	/**
     * Setup WooCommerce High-Performance Order Storage (HPOS) compatibility
     * 
     * @since 1.0.0
     * @version 5.2.0
     * @return void
     */
    public function declare_woo_compatibility() {
        if ( defined('WC_VERSION') && version_compare( WC_VERSION, '7.1', '>' ) ) {
			if ( class_exists( FeaturesUtil::class ) ) {
				/**
				 * Setup compatibility with HPOS/Custom order table feature of WooCommerce
				 * 
				 * @since 1.0.0
				 */
				FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );

				/**
				 * Display incompatible notice with WooCommerce checkout blocks
				 * 
				 * @since 3.8.0
				 */
				FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, false );
			}
		}
    }


	/**
	 * Checker dependencies before activate plugin
	 * 
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public function init() {
		// define constants
		$this->setup_constants();

		// load Composer
		require_once FLEXIFY_CHECKOUT_PATH . 'vendor/autoload.php';

		// initialize classes
		new \MeuMouse\Flexify_Checkout\Core\Init;
	}


	/**
	 * Define constants
	 * 
	 * @since 1.0.0
	 * @version 5.2.0
	 * @return void
	 */
	public function setup_constants() {
		$base_file = __FILE__;
		$base_dir = plugin_dir_path( $base_file );
		$base_url = plugin_dir_url( $base_file );

		$constants = array(
			'FLEXIFY_CHECKOUT_BASENAME' => plugin_basename( $base_file ),
			'FLEXIFY_CHECKOUT_FILE' => $base_file,
			'FLEXIFY_CHECKOUT_PATH' => $base_dir,
			'FLEXIFY_CHECKOUT_INC_PATH' => $base_dir . 'inc/',
			'FLEXIFY_CHECKOUT_URL' => $base_url,
			'FLEXIFY_CHECKOUT_ASSETS' => $base_url . 'assets/',
			'FLEXIFY_CHECKOUT_ABSPATH' => dirname( $base_file ) . '/',
			'FLEXIFY_CHECKOUT_TEMPLATES_DIR' => $base_dir . 'templates/',
			'FLEXIFY_CHECKOUT_SETTINGS_TABS_DIR' => $base_dir . 'inc/Views/Settings/Tabs/',
			'FLEXIFY_CHECKOUT_SLUG' => self::$slug,
			'FLEXIFY_CHECKOUT_VERSION' => self::$version,
			'FLEXIFY_CHECKOUT_ADMIN_EMAIL' => get_option('admin_email'),
			'FLEXIFY_CHECKOUT_DOCS_LINK' => 'https://ajuda.meumouse.com/docs/flexify-checkout-for-woocommerce/overview',
			'FLEXIFY_CHECKOUT_DEV_MODE' => false,
		);

		// iterate for each constant item
		foreach ( $constants as $key => $value ) {
			if ( ! defined( $key ) ) {
				define( $key, $value );
			}
		}
	}


	/**
	 * Cloning is forbidden
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Trapaceando?', 'flexify-checkout-for-woocommerce' ), '1.0.0' );
	}


	/**
	 * Unserializing instances of this class is forbidden
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Trapaceando?', 'flexify-checkout-for-woocommerce' ), '1.0.0' );
	}


	/**
	 * Ensures only one instance of Flexify_Checkout class is loaded or can be loaded
	 *
	 * @since 5.0.0
	 * @return object | Flexify_Checkout instance
	 */
	public static function run() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
}

/**
 * Initialise the plugin
 * 
 * @since 5.0.0
 * @return object Flexify_Checkout
 */
Flexify_Checkout::run();