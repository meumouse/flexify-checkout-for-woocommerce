<?php

/**
 * Plugin Name: 			Flexify Checkout para WooCommerce
 * Description: 			Extensão que otimiza a finalização de compras em multi etapas para lojas WooCommerce.
 * Plugin URI: 				https://meumouse.com/plugins/flexify-checkout-para-woocommerce/
 * Requires Plugins: 		woocommerce
 * Author: 					MeuMouse.com
 * Author URI: 				https://meumouse.com/
 * Version: 				3.9.3
 * WC requires at least: 	6.0.0
 * WC tested up to: 		9.2.3
 * Requires PHP: 			7.4
 * Tested up to:      		6.6.2
 * Text Domain: 			flexify-checkout-for-woocommerce
 * Domain Path: 			/languages
 * License: 				GPL2
 */

namespace MeuMouse\Flexify_Checkout;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Flexify_Checkout
 * 
 * @since 1.0.0
 * @version 3.9.0
 * @package MeuMouse.com
 */
class Flexify_Checkout {

	/**
	 * Plugin slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public static $slug = 'flexify-checkout-for-woocommerce';

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public static $version = '3.9.3';

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
	 * @version 3.9.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 99 );
	}


	/**
	 * Checker dependencies before activate plugin
	 * 
	 * @since 1.0.0
	 * @version 3.9.0
	 * @return void
	 */
	public function init() {
		// Display notice if PHP version is bottom 7.4
		if ( version_compare( phpversion(), '7.4', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
			return;
		}
		
		$this->define_constants();

		load_plugin_textdomain( 'flexify-checkout-for-woocommerce', false, dirname( FLEXIFY_CHECKOUT_BASENAME ) . '/languages/' );
		
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		// check if WooCommerce is active
		if ( is_plugin_active('woocommerce/woocommerce.php') && version_compare( WC_VERSION, '6.0', '>' ) ) {
			add_action( 'before_woocommerce_init', array( __CLASS__, 'setup_woo_new_features_compatibility' ) );
			add_filter( 'plugin_action_links_' . FLEXIFY_CHECKOUT_BASENAME, array( $this, 'setup_action_links' ), 10, 4 );
			add_filter( 'plugin_row_meta', array( $this, 'setup_row_meta_links' ), 10, 4 );

			$this->setup_includes();
			$this->initiated = true;

			$url = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

			// remove Pro badge if plugin is licensed
			if ( get_option('flexify_checkout_license_status') !== 'valid' && false !== strpos( $url, 'wp-admin/plugins.php' ) ) {
				add_filter( 'plugin_action_links_' . FLEXIFY_CHECKOUT_BASENAME, array( $this, 'be_pro_link' ), 10, 4 );
				add_action( 'admin_head', array( $this, 'be_pro_styles' ) );
			}
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_version_notice' ) );
			deactivate_plugins('flexify-checkout-for-woocommerce/flexify-checkout-for-woocommerce.php');
			add_action( 'admin_notices', array( $this, 'deactivate_flexify_checkout_notice' ) );
		}
	}


	/**
	 * Define constants
	 * 
	 * @since 1.0.0
	 * @version 3.9.0
	 * @return void
	 */
	private function define_constants() {
		$this->define( 'FLEXIFY_CHECKOUT_FILE', __FILE__ );
		$this->define( 'FLEXIFY_CHECKOUT_PATH', plugin_dir_path( __FILE__ ) );
		$this->define( 'FLEXIFY_CHECKOUT_URL', plugin_dir_url( __FILE__ ) );
		$this->define( 'FLEXIFY_CHECKOUT_ASSETS', FLEXIFY_CHECKOUT_URL . 'assets/' );
		$this->define( 'FLEXIFY_CHECKOUT_INC_PATH', FLEXIFY_CHECKOUT_PATH . 'inc/' );
		$this->define( 'FLEXIFY_CHECKOUT_TPL_PATH', FLEXIFY_CHECKOUT_PATH . 'templates/' );
		$this->define( 'FLEXIFY_CHECKOUT_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'FLEXIFY_CHECKOUT_VERSION', self::$version );
		$this->define( 'FLEXIFY_CHECKOUT_SLUG', self::$slug );
		$this->define( 'FLEXIFY_CHECKOUT_ADMIN_EMAIL', get_option('admin_email') );
		$this->define( 'FLEXIFY_CHECKOUT_DOCS_LINK', 'https://ajuda.meumouse.com/docs/flexify-checkout-for-woocommerce/overview' );
		$this->define( 'FLEXIFY_CHECKOUT_PLUGIN_NAME', esc_html__( 'Flexify Checkout para WooCommerce', 'flexify-checkout-for-woocommerce' ) );
	}


	/**
	 * Run on activation
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function activate() {
		self::clear_wc_template_cache();
	}


	/**
	 * Deactivate plugin
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function deactivate() {
		self::clear_wc_template_cache();
	}


	/**
	 * Clear WooCommerce template cache
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function clear_wc_template_cache() {
		if ( function_exists('wc_clear_template_cache') ) {
			wc_clear_template_cache();
		}
	}


	/**
	 * Define constant if not already set
	 *
	 * @since 1.0.0
	 * @param string $name | Constant name
	 * @param string|bool $value | Constant value
	 * @return void
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}


	/**
	 * Load classes
	 * 
	 * @since 1.0.0
	 * @version 3.8.7
	 * @return void
	 */
	private function setup_includes() {
		$includes = apply_filters( 'flexify_checkout_setup_includes', array(
			'functions.php',
			'classes/class-license.php',
			'class-init.php',
			'classes/class-admin-options.php',
			'classes/class-core.php',
			'classes/class-helpers.php',
			'classes/class-assets.php',
			'classes/class-ajax.php',
			'classes/class-compat-autoloader.php',
			'classes/class-sidebar.php',
			'classes/class-steps.php',
			'classes/class-order.php',
			'classes/class-conditions.php',
			'classes/class-thankyou.php',
			'classes/class-logger.php',
			'classes/class-modules.php',
			'classes/class-updater.php',
		));

		foreach ( $includes as $file ) {
			$file_path = FLEXIFY_CHECKOUT_INC_PATH . $file;

			if ( file_exists( $file_path ) ) {
				include_once $file_path;
			}
		}
	}

	
	/**
	 * WooCommerce version notice
	 * 
	 * @since 1.0.0
	 * @version 3.6.5
	 * @return void
	 */
	public function woocommerce_version_notice() {
		$class = 'notice notice-error is-dismissible';
		$message = sprintf( __( '<strong>%s</strong> requer a versão do WooCommerce 6.0 ou maior. Faça a atualização do plugin WooCommerce.', 'flexify-checkout-for-woocommerce' ), FLEXIFY_CHECKOUT_PLUGIN_NAME );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
	}


	/**
	 * Notice if WooCommerce is deactivate
	 * 
	 * @since 1.0.0
	 * @version 3.8.0
	 * @return void
	 */
	public function deactivate_flexify_checkout_notice() {
		if ( current_user_can('install_plugins') ) {
			$class = 'notice notice-error is-dismissible';
			$message = sprintf( __( '<strong>%s</strong> requer que <strong>WooCommerce</strong> esteja instalado e ativado.', 'flexify-checkout-for-woocommerce' ), FLEXIFY_CHECKOUT_PLUGIN_NAME );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
		}
	}


	/**
	 * PHP version notice
	 * 
	 * @since 1.0.0
	 * @version 3.8.0
	 * @return void
	 */
	public function php_version_notice() {
		$class = 'notice notice-error is-dismissible';
		$message = sprintf( __( '<strong>%s</strong> requer a versão do PHP 7.4 ou maior. Contate o suporte da sua hospedagem para realizar a atualização.', 'flexify-checkout-for-woocommerce' ), FLEXIFY_CHECKOUT_PLUGIN_NAME );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
	}


	/**
	 * Plugin action links
	 * 
	 * @since 1.0.0
	 * @param array $action_links
	 * @return string
	 */
	public function setup_action_links( $action_links ) {
		$plugins_links = array(
			'<a href="' . admin_url('admin.php?page=flexify-checkout-for-woocommerce') . '">'. __( 'Configurar', 'flexify-checkout-for-woocommerce' ) .'</a>',
		);

		return array_merge( $plugins_links, $action_links );
	}


	/**
	 * Add meta links on plugin
	 * 
	 * @since 3.0.0
	 * @param string $plugin_meta | An array of the plugin’s metadata, including the version, author, author URI, and plugin URI
	 * @param string $plugin_file | Path to the plugin file relative to the plugins directory
	 * @param array $plugin_data | An array of plugin data
	 * @param string $status | Status filter currently applied to the plugin list
	 * @return string
	 */
	public function setup_row_meta_links( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( strpos( $plugin_file, FLEXIFY_CHECKOUT_BASENAME ) !== false ) {
			$new_links = array(
				'docs' => '<a href="'. FLEXIFY_CHECKOUT_DOCS_LINK .'" target="_blank">'. __( 'Documentação', 'flexify-checkout-for-woocommerce' ) .'</a>',
			);
			
			$plugin_meta = array_merge( $plugin_meta, $new_links );
		}
	 
		return $plugin_meta;
	}


	/**
	 * Plugin action links Pro version
	 * 
	 * @since 3.3.0
	 * @version 3.8.0
	 * @return array
	 */
	public static function be_pro_link( $action_links ) {
		$plugins_links = array(
			'<a id="get-pro-flexify-checkout" target="_blank" href="https://meumouse.com/plugins/flexify-checkout-para-woocommerce/?utm_source=wordpress&utm_medium=plugins-list&utm_campaign=flexify-checkout">' . __( 'Seja PRO', 'flexify-checkout-for-woocommerce' ) . '</a>'
		);
	
		return array_merge( $plugins_links, $action_links );
	}


	/**
	 * Display badge in CSS for get pro in plugins page
	 * 
	 * @since 3.3.0
	 * @version 3.8.0
	 * @return void
	 */
	public function be_pro_styles() {
		ob_start(); ?>

		#get-pro-flexify-checkout {
			display: inline-block;
			padding: 0.35em 0.6em;
			font-size: 0.8125em;
			font-weight: 600;
			line-height: 1;
			color: #fff;
			text-align: center;
			white-space: nowrap;
			vertical-align: baseline;
			border-radius: 0.25rem;
			background-color: #008aff;
			transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out;
		}

		#get-pro-flexify-checkout:hover {
			background-color: #0078ed;
		}

		<?php $css = ob_get_clean();
		$css = wp_strip_all_tags( $css );

		printf( __('<style>%s</style>'), $css );
	}


	/**
	 * Declare compatibility with new WooCommerce features
	 *
	 * @since 1.0.0
	 * @version 3.8.0
	 * @return void
	 */
	public static function setup_woo_new_features_compatibility() {
		if ( defined('WC_VERSION') && version_compare( WC_VERSION, '7.1', '>' ) ) {
			/**
			 * Setup compatibility with HPOS/Custom order table feature of WooCommerce
			 * 
			 * @since 1.0.0
			 */
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', FLEXIFY_CHECKOUT_FILE, true );
			}

			/**
			 * Display incompatible notice with WooCommerce checkout blocks
			 * 
			 * @since 3.8.0
			 */
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', FLEXIFY_CHECKOUT_FILE, false );
			}
		}
	}


	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Trapaceando?', 'flexify-checkout-for-woocommerce' ), '1.0.0' );
	}


	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Trapaceando?', 'flexify-checkout-for-woocommerce' ), '1.0.0' );
	}
}

$flexify_checkout = new Flexify_Checkout();

if ( $flexify_checkout->initiated ) {
	register_activation_hook( __FILE__, array( $flexify_checkout, 'activate' ) );
	register_deactivation_hook( __FILE__, array( $flexify_checkout, 'deactivate' ) );
}