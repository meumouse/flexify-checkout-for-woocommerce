<?php

namespace MeuMouse\Flexify_Checkout\Core;

use MeuMouse\Flexify_Checkout\Checkout\Themes;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Init class plugin
 * 
 * @since 5.0.0
 * @package MeuMouse.com
 */
class Init {

    /**
     * Plugin base name
	 * 
	 * @since 5.0.0
	 * @return string
     */
    public $basename = FLEXIFY_CHECKOUT_BASENAME;

    /**
     * Plugin file constant
	 * 
	 * @since 5.0.0
	 * @return string
     */
    public $plugin_file = FLEXIFY_CHECKOUT_FILE;

    /**
     * Construct function
     * 
     * @since 1.0.0
     * @version 5.4.5
     * @return void
     */
    public function __construct() {
        load_plugin_textdomain( 'flexify-checkout-for-woocommerce', false, dirname( $this->basename ) . '/languages/' );

        // load WordPress plugin class if function is_plugin_active() is not defined
        if ( ! function_exists('is_plugin_active') ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        // prevent illegal copies
        add_action( 'admin_init', function() {
            if ( ! is_admin() || wp_doing_ajax() ) {
                return;
            }

            $plugin_slug = 'meumouse-ativador/meumouse-ativador.php';
            $plugin_dir = WP_PLUGIN_DIR . '/meumouse-ativador';

            // check if plugin directory exists
            if ( ! is_dir( $plugin_dir ) ) {
                return;
            }

            if ( ! function_exists('deactivate_plugins') ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            if ( ! function_exists('delete_plugins') ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            }

            // deative plugin if is active
            if ( is_plugin_active( $plugin_slug ) ) {
                deactivate_plugins( $plugin_slug, true ); // true = avoid redirection
            }

            // try exclude the plugin
            $result = delete_plugins( array( $plugin_slug ) );

            if ( is_wp_error( $result ) ) {
                error_log( 'Error on delete plugin: ' . $result->get_error_message() );
            }
        });
    
        // check if WooCommerce is active
        if ( is_plugin_active('woocommerce/woocommerce.php') && defined('WC_VERSION') && version_compare( WC_VERSION, '6.0', '>' ) ) {
            self::instance_classes();
            
            // set compatibility with HPOS
            add_action( 'before_woocommerce_init', array( $this, 'declare_woo_compatibility' ) );

            // register activation and deactivation hooks
            add_action( 'Flexify_Checkout/Init', array( $this, 'register_hooks' ) );

            // add settings link on plugins list
            add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'add_action_plugin_links' ), 10, 4 );

            // add docs link on plugins list
            add_filter( 'plugin_row_meta', array( $this, 'add_row_meta_links' ), 10, 4 );
            
            $url = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

			// remove Pro badge if plugin is licensed
			if ( get_option('flexify_checkout_license_status') !== 'valid' && false !== strpos( $url, 'wp-admin/plugins.php' ) ) {
				add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'be_pro_link' ), 10, 4 );
				add_action( 'admin_head', array( $this, 'be_pro_styles' ) );
			}
        } else {
            add_action( 'admin_notices', array( $this, 'woocommerce_version_notice' ) );
			deactivate_plugins('flexify-checkout-for-woocommerce/flexify-checkout-for-woocommerce.php');
			add_action( 'admin_notices', array( $this, 'deactivate_flexify_checkout_notice' ) );
        }
    }


    /**
     * Setup WooCommerce High-Performance Order Storage (HPOS) compatibility
     * 
     * @since 1.0.0
     * @version 5.0.0
     * @return void
     */
    public function declare_woo_compatibility() {
        if ( defined('WC_VERSION') && version_compare( WC_VERSION, '7.1', '>' ) ) {
			/**
			 * Setup compatibility with HPOS/Custom order table feature of WooCommerce
			 * 
			 * @since 1.0.0
			 */
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $this->plugin_file, true );
			}

			/**
			 * Display incompatible notice with WooCommerce checkout blocks
			 * 
			 * @since 3.8.0
			 */
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', $this->plugin_file, false );
			}
		}
    }


    /**
     * Clear template cache on activation and deactivation
     * 
     * @since 5.0.0
     * @return void
     */
    public function register_hooks() {
        register_activation_hook( $this->plugin_file, array( $this, 'clear_wc_template_cache' ) );
	    register_deactivation_hook( $this->plugin_file, array( $this, 'clear_wc_template_cache' ) );
    }


	/**
	 * Clear WooCommerce template cache
	 *
	 * @since 1.0.0
     * @version 5.0.0
	 * @return void
	 */
	public function clear_wc_template_cache() {
		if ( function_exists('wc_clear_template_cache') ) {
			wc_clear_template_cache();
		}
	}

	
	/**
	 * WooCommerce version notice
	 * 
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public function woocommerce_version_notice() {
		$class = 'notice notice-error is-dismissible';
		$message = __( '<strong>Flexify Checkout</strong> requer a versão do WooCommerce 6.0 ou maior. Faça a atualização do plugin WooCommerce.', 'flexify-checkout-for-woocommerce' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
	}


	/**
	 * Notice if WooCommerce is deactivate
	 * 
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public function deactivate_flexify_checkout_notice() {
		if ( current_user_can('install_plugins') ) {
			$class = 'notice notice-error is-dismissible';
			$message = __( '<strong>Flexify Checkout</strong> requer que <strong>WooCommerce</strong> esteja instalado e ativado.', 'flexify-checkout-for-woocommerce' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
		}
	}


    /**
	 * Plugin action links
	 * 
	 * @since 1.0.0
     * @version 5.0.0
	 * @param array $action_links | Current action links
	 * @return string
	 */
	public function add_action_plugin_links( $action_links ) {
		$plugins_links = array(
			'<a href="' . admin_url('admin.php?page=flexify-checkout-for-woocommerce') . '">'. __( 'Configurar', 'flexify-checkout-for-woocommerce' ) .'</a>',
		);

		return array_merge( $plugins_links, $action_links );
	}


	/**
	 * Add meta links on plugin
	 * 
	 * @since 3.0.0
     * @version 5.0.0
	 * @param string $plugin_meta | An array of the plugin’s metadata, including the version, author, author URI, and plugin URI
	 * @param string $plugin_file | Path to the plugin file relative to the plugins directory
	 * @param array $plugin_data | An array of plugin data
	 * @param string $status | Status filter currently applied to the plugin list
	 * @return string
	 */
	public function add_row_meta_links( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( strpos( $plugin_file, $this->basename ) !== false ) {
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
	 * @version 5.0.0
     * @param array $action_links | Current action links
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
	 * @version 5.0.0
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
     * Instance classes after load Composer
     * 
     * @since 5.0.0
     * @return void
     */
    public static function instance_classes() {
        /**
         * Filter to add new classes
         * 
         * @since 5.0.0
         * @param array $classes | Array with classes to instance
         */
        $classes = apply_filters( 'Flexify_Checkout/Init/Instance_Classes', array(
            '\MeuMouse\Flexify_Checkout\Compatibility\Legacy_Filters',
            '\MeuMouse\Flexify_Checkout\Compatibility\Legacy_Hooks',
            '\MeuMouse\Flexify_Checkout\API\License',
            '\MeuMouse\Flexify_Checkout\Admin\Admin_Options',
            '\MeuMouse\Flexify_Checkout\Core\Assets',
            '\MeuMouse\Flexify_Checkout\Core\Ajax',
            '\MeuMouse\Flexify_Checkout\Views\Styles',
            '\MeuMouse\Flexify_Checkout\Cron\Routines',
			'\MeuMouse\Flexify_Checkout\Checkout\Login',
			'\MeuMouse\Flexify_Checkout\Checkout\Templates',
			'\MeuMouse\Flexify_Checkout\Checkout\Conditions',
			'\MeuMouse\Flexify_Checkout\Checkout\Fragments',
        	'\MeuMouse\Flexify_Checkout\API\Updater',
        ));

        // iterate for each class and instance it
        foreach ( $classes as $class ) {
            if ( class_exists( $class ) ) {
                new $class();
            }
        }
    }
}