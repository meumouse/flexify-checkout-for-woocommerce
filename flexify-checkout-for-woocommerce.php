<?php

/**
 * Plugin Name: 			Flexify Checkout para WooCommerce
 * Description: 			Extensão que otimiza a finalização de compras em multi etapas para lojas WooCommerce.
 * Plugin URI: 				https://meumouse.com/plugins/flexify-checkout-para-woocommerce/
 * Author: 					MeuMouse.com
 * Author URI: 				https://meumouse.com/
 * Version: 				3.6.0
 * WC requires at least: 	6.0.0
 * WC tested up to: 		9.0.2
 * Requires PHP: 			7.4
 * Tested up to:      		6.5.5
 * Text Domain: 			flexify-checkout-for-woocommerce
 * Domain Path: 			/languages
 * License: 				GPL2
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Flexify_Checkout
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
	public static $version = '3.6.0';

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
	 * @return void
	 */
	public function __construct() {
		$this->define_constants();

		add_action( 'plugins_loaded', array( $this, 'flexify_checkout_load_checker' ), 5 );
		load_plugin_textdomain( 'flexify-checkout-for-woocommerce', false, dirname( FLEXIFY_CHECKOUT_BASENAME ) . '/languages/' );
	}


	/**
	 * Define constants
	 * 
	 * @since 1.0.0
	 * @version 3.0.0
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
		$this->define( 'FLEXIFY_CHECKOUT_DOCS_LINK', 'https://meumouse.com/docs/flexify-checkout-para-woocommerce/' );
	}


	/**
	 * Checker dependencies before activate plugin
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function flexify_checkout_load_checker() {
		// Display notice if PHP version is bottom 7.4
		if ( version_compare( phpversion(), '7.4', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'flexify_checkout_php_version_notice' ) );
			return;
		}
		
		if ( !function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		// check if WooCommerce is active
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && version_compare( WC_VERSION, '6.0', '>' ) ) {
			add_filter( 'plugin_action_links_' . FLEXIFY_CHECKOUT_BASENAME, array( $this, 'flexify_checkout_plugin_links' ), 10, 4 );
			add_filter( 'plugin_row_meta', array( $this, 'flexify_checkout_row_meta_links' ), 10, 4 );
			add_action( 'before_woocommerce_init', array( __CLASS__, 'setup_hpos_compatibility' ) );

			$this->setup_compat_autoloader();
			$this->setup_includes();
			$this->initiated = true;

			$url = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

			// remove Pro badge if plugin is licensed
			if ( get_option('flexify_checkout_license_status') !== 'valid' && false !== strpos( $url, 'wp-admin/plugins.php' ) ) {
				add_filter( 'plugin_action_links_' . FLEXIFY_CHECKOUT_BASENAME, array( $this, 'get_pro_flexify_checkout_link' ), 10, 4 );
				add_action( 'admin_head', array( $this, 'badge_pro_flexify_checkout' ) );
			}
		} else {
			add_action( 'admin_notices', array( $this, 'flexify_checkout_wc_version_notice' ) );
			deactivate_plugins( 'flexify-checkout-for-woocommerce/flexify-checkout-for-woocommerce.php' );
			add_action( 'admin_notices', array( $this, 'flexify_checkout_wc_deactivate_notice' ) );
		}
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
		if ( function_exists( 'wc_clear_template_cache' ) ) {
			wc_clear_template_cache();
		}
	}


	/**
	 * Setup compability checker autoloader
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_compat_autoloader() {
		include_once FLEXIFY_CHECKOUT_INC_PATH . 'classes/class-flexify-checkout-compat-autoloader.php';

		Flexify_Checkout_Compat_Autoloader::run(
			array(
				'prefix' => 'Flexify_Checkout_',
				'inc_path' => FLEXIFY_CHECKOUT_INC_PATH . 'classes/compat/',
			)
		);
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
	 * @return void
	 */
	private function setup_includes() {
		/**
		 * Plugin functions
		 * 
		 * @since 2.3.0
		 */
		include_once FLEXIFY_CHECKOUT_INC_PATH . 'flexify-checkout-functions.php';

		/**
		 * Class init plugin
		 * 
		 * @since 1.0.0
		 */
		include_once FLEXIFY_CHECKOUT_INC_PATH . 'class-flexify-checkout-init.php';

		/**
		 * Admin options
		 * 
		 * @since 1.0.0
		 */
		include_once FLEXIFY_CHECKOUT_INC_PATH . 'admin/class-flexify-checkout-admin-options.php';

		/**
		 * Load core plugin
		 * 
		 * @since 1.0.0
		 */
		include_once FLEXIFY_CHECKOUT_INC_PATH . 'classes/class-flexify-checkout-core.php';

		/**
		 * Load plugin assets
		 * 
		 * @since 1.0.0
		 */
		include_once FLEXIFY_CHECKOUT_INC_PATH . 'classes/class-flexify-checkout-assets.php';

		/**
		 * Load AJAX functions
		 * 
		 * @since 1.0.0
		 */
		include_once FLEXIFY_CHECKOUT_INC_PATH . 'classes/class-flexify-checkout-ajax.php';

		/**
		 * Load class helper
		 * 
		 * @since 1.0.0
		 */
		include_once FLEXIFY_CHECKOUT_INC_PATH . 'classes/class-flexify-checkout-helpers.php';

		/**
		 * Load guest user order
		 * 
		 * @since 1.0.0
		 */
		include_once FLEXIFY_CHECKOUT_INC_PATH . 'classes/class-flexify-checkout-order.php';

		/**
		 * Load checkout sidebar functions
		 * 
		 * @since 1.0.0
		 */
		include_once FLEXIFY_CHECKOUT_INC_PATH . 'classes/class-flexify-checkout-sidebar.php';

		/**
		 * Load checkout functions steps
		 * 
		 * @since 1.0.0
		 */
		include_once FLEXIFY_CHECKOUT_INC_PATH . 'classes/class-flexify-checkout-steps.php';

		/**
		 * Load thankyou page functions
		 * 
		 * @since 1.0.0
		 */
		include_once FLEXIFY_CHECKOUT_INC_PATH . 'classes/class-flexify-checkout-thankyou.php';

		/**
		 * Load checkout conditions
		 * 
		 * @since 3.5.0
		 */
		include_once FLEXIFY_CHECKOUT_INC_PATH . 'classes/class-flexify-checkout-conditions.php';

		/**
		 * Load API settings
		 * 
		 * @since 1.0.0
		 */
		include_once FLEXIFY_CHECKOUT_INC_PATH . 'classes/class-flexify-checkout-api.php';

		/**
		 * Update checker
		 * 
		 * @since 1.0.0
		 */
		include_once FLEXIFY_CHECKOUT_INC_PATH . 'classes/class-flexify-checkout-updater.php';
	}

	
	/**
	 * WooCommerce version notice
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function flexify_checkout_wc_version_notice() {
		echo '<div class="notice is-dismissible error">
			<p>' . __( '<strong>Flexify Checkout para WooCommerce</strong> requer a versão do WooCommerce 6.0 ou maior. Faça a atualização do plugin WooCommerce.', 'flexify-checkout-for-woocommerce' ) . '</p>
		</div>';
	}


	/**
	 * Notice if WooCommerce is deactivate
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function flexify_checkout_wc_deactivate_notice() {
		if ( ! current_user_can('install_plugins') ) {
			return;
		}

		echo '<div class="notice is-dismissible error">
			<p>' . __( '<strong>Flexify Checkout para WooCommerce</strong> requer que <strong>WooCommerce</strong> esteja instalado e ativado.', 'flexify-checkout-for-woocommerce' ) . '</p>
		</div>';
	}


	/**
	 * PHP version notice
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function flexify_checkout_php_version_notice() {
		echo '<div class="notice is-dismissible error">
			<p>' . __( '<strong>Flexify Checkout para WooCommerce</strong> requer a versão do PHP 7.4 ou maior. Contate o suporte da sua hospedagem para realizar a atualização.', 'flexify-checkout-for-woocommerce' ) . '</p>
		</div>';
	}


	/**
	 * Plugin action links
	 * 
	 * @since 1.0.0
	 * @param array $action_links
	 * @return string
	 */
	public function flexify_checkout_plugin_links( $action_links ) {
		$plugins_links = array(
			'<a href="' . admin_url( 'admin.php?page=flexify-checkout-for-woocommerce' ) . '">'. __( 'Configurar', 'flexify-checkout-for-woocommerce' ) .'</a>',
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
	public function flexify_checkout_row_meta_links( $plugin_meta, $plugin_file, $plugin_data, $status ) {
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
	 * @return array
	 */
	public static function get_pro_flexify_checkout_link( $action_links ) {
		$plugins_links = array(
			'<a id="get-pro-flexify-checkout" target="_blank" href="https://meumouse.com/plugins/flexify-checkout-para-woocommerce/?utm_source=wordpress&utm_medium=plugins-list&utm_campaign=flexify-checkout">' . __( 'Seja PRO', 'flexify-checkout-for-woocommerce' ) . '</a>'
		);
	
		return array_merge( $plugins_links, $action_links );
	}


	/**
	 * Display badge in CSS for get pro in plugins page
	 * 
	 * @since 3.3.0
	 * @return void
	 */
	public function badge_pro_flexify_checkout() {
		echo '<style>
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
		</style>';
	}


	/**
	 * Setp compatibility with HPOS/Custom order table feature of WooCommerce.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function setup_hpos_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', FLEXIFY_CHECKOUT_FILE, true );
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