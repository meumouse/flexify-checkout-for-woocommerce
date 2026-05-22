<?php

namespace MeuMouse\Flexify_Checkout\Core;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use MeuMouse\Flexify_Checkout\Admin\Admin_Options;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Init class plugin
 * 
 * @since 5.0.0
 * @version 5.2.0

 * @author MeuMouse.com
 */
class Init {
    /**
     * Schema version for options bootstrap/migrations.
     *
     * @since 5.4.3
     * @var int
     */
    const SCHEMA_VERSION = 1;

    /**
     * Ensure bootstrap is registered once.
     *
     * @since 5.5.0
     * @var bool
     */
    private static $bootstrapped = false;

    /**
     * Plugin base name
	 * 
	 * @since 5.0.0
	 * @return string
     */
    public $basename = '';

    /**
     * Plugin file constant
	 * 
	 * @since 5.0.0
	 * @return string
     */
    public $plugin_file = '';

    /**
     * Plugin directory path
     * 
     * @since 5.2.0
     * @return string
     */
    public $directory = '';

    /**
     * Bootstrap plugin lifecycle hooks.
     *
     * @since 5.5.0
     * @param string $plugin_file Plugin main file.
     * @return void
     */
    public static function bootstrap( $plugin_file, $plugin_version ) {
        if ( self::$bootstrapped ) {
            return;
        }

        self::define_constants( $plugin_file, $plugin_version );

        do_action( 'Flexify_Checkout/Before_Init' );

        add_action( 'before_woocommerce_init', function() use ( $plugin_file ) {
            self::declare_woo_compatibility( $plugin_file );
        } );

        add_action( 'init', function() use ( $plugin_version ) {
            new self( $plugin_version );
        }, 99 );

        self::$bootstrapped = true;
    }


    /**
     * Activation callback.
     *
     * @since 5.5.0
     * @param string $plugin_file Plugin main file.
     * @return void
     */
    public static function activate( $plugin_file, $plugin_version ) {
        self::define_constants( $plugin_file, $plugin_version );
        self::maybe_bootstrap_defaults( true );
        self::clear_wc_template_cache();
    }


    /**
     * Deactivation callback.
     *
     * @since 5.5.0
     * @param string $plugin_file Plugin main file.
     * @return void
     */
    public static function deactivate( $plugin_file, $plugin_version ) {
        self::define_constants( $plugin_file, $plugin_version );
        self::clear_wc_template_cache();
    }


    /**
     * Define plugin constants.
     *
     * @since 5.5.0
     * @param string $plugin_file Plugin main file.
     * @return void
     */
    private static function define_constants( $plugin_file, $plugin_version ) {
        $base_file = $plugin_file;
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
            'FLEXIFY_CHECKOUT_SLUG' => 'flexify-checkout-for-woocommerce',
            'FLEXIFY_CHECKOUT_VERSION' => $plugin_version,
            'FLEXIFY_CHECKOUT_ADMIN_EMAIL' => get_option( 'admin_email' ),
            'FLEXIFY_CHECKOUT_DOCS_LINK' => 'https://ajuda.meumouse.com/docs/flexify-checkout-for-woocommerce/overview',
            'FLEXIFY_CHECKOUT_DEV_MODE' => false,
        );

        foreach ( $constants as $key => $value ) {
            if ( ! defined( $key ) ) {
                define( $key, $value );
            }
        }
    }


    /**
     * Setup WooCommerce High-Performance Order Storage compatibility.
     *
     * @since 5.5.0
     * @param string $plugin_file Plugin main file.
     * @return void
     */
    public static function declare_woo_compatibility( $plugin_file ) {
        if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '7.1', '>' ) && class_exists( FeaturesUtil::class ) ) {
            FeaturesUtil::declare_compatibility( 'custom_order_tables', $plugin_file, true );
            FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', $plugin_file, false );
        }
    }

    /**
     * Construct function
     * 
     * @since 1.0.0
     * @version 5.2.0
     * @return void
     */
    public function __construct( $plugin_version = '' ) {
        if ( ! empty( $plugin_version ) && ! defined( 'FLEXIFY_CHECKOUT_VERSION' ) ) {
            define( 'FLEXIFY_CHECKOUT_VERSION', $plugin_version );
        }

        $this->basename = defined( 'FLEXIFY_CHECKOUT_BASENAME' ) ? FLEXIFY_CHECKOUT_BASENAME : '';
        $this->plugin_file = defined( 'FLEXIFY_CHECKOUT_FILE' ) ? FLEXIFY_CHECKOUT_FILE : '';
        $this->directory = defined( 'FLEXIFY_CHECKOUT_PATH' ) ? FLEXIFY_CHECKOUT_PATH : '';

        // Display notice if PHP version is bottom 7.4
		if ( version_compare( phpversion(), '7.4', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
			return;
		}

        // load text domain
        load_plugin_textdomain( 'flexify-checkout-for-woocommerce', false, dirname( $this->basename ) . '/languages/' );

        // enable debug mode
        define( 'FLEXIFY_CHECKOUT_DEBUG_MODE', Admin_Options::get_setting('enable_debug_mode') === 'yes' ? true : false );

		// load plugin functions
		include_once( FLEXIFY_CHECKOUT_INC_PATH . 'Core/Functions.php' );

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
            self::maybe_bootstrap_defaults();
            $this->instance_classes();

            // add settings link on plugins list
            add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'add_action_plugin_links' ), 10, 4 );

            // add docs link on plugins list
            add_filter( 'plugin_row_meta', array( $this, 'add_row_meta_links' ), 10, 4 );
            
            $url = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

			// remove Pro badge if plugin is licensed
			if ( get_option('flexify_checkout_license_status') !== 'valid' && false !== strpos( $url, 'wp-admin/plugins.php' ) ) {
				add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'be_pro_link' ), 10, 4 );
				add_action( 'admin_head', array( '\MeuMouse\Flexify_Checkout\Views\Styles', 'be_pro_styles' ) );
			}
        } else {
            add_action( 'admin_notices', array( $this, 'woocommerce_version_notice' ) );
			deactivate_plugins('flexify-checkout-for-woocommerce/flexify-checkout-for-woocommerce.php');
			add_action( 'admin_notices', array( $this, 'deactivate_flexify_checkout_notice' ) );
        }

        // hook after plugin init
		do_action('Flexify_Checkout/Init');
    }


    /**
	 * PHP version notice
	 * 
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public function php_version_notice() {
		$class = 'notice notice-error is-dismissible';
		$message = __( '<strong>Flexify Checkout</strong> requer a versÃ£o do PHP 7.4 ou maior. Contate o suporte da sua hospedagem para realizar a atualizaÃ§Ã£o.', 'flexify-checkout-for-woocommerce' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
	}


    /**
     * Ensure required options/default structures exist and are sane.
     *
     * @since 5.4.3
     * @param bool $force Force full defaults merge when true.
     * @return void
     */
    public static function maybe_bootstrap_defaults( $force = false ) {
        $admin_options = new Admin_Options();
        $stored_schema_version = intval( get_option( 'flexify_checkout_schema_version', 0 ) );

        $stored_settings = get_option( 'flexify_checkout_settings', array() );
        $stored_step_fields = maybe_unserialize( get_option( 'flexify_checkout_step_fields', array() ) );

        $needs_settings = $force || ! is_array( $stored_settings ) || empty( $stored_settings );
        $needs_step_fields = $force || ! is_array( $stored_step_fields ) || empty( $stored_step_fields );
        $needs_migration = $stored_schema_version < self::SCHEMA_VERSION;

        if ( $needs_settings || $needs_migration ) {
            $admin_options->set_default_options();
        }

        if ( $needs_step_fields || $needs_migration ) {
            $admin_options->set_checkout_step_fields();
        }

        // Safety net: remove conditions that reference missing fields.
        if ( class_exists( '\MeuMouse\Flexify_Checkout\Core\Ajax' ) ) {
            \MeuMouse\Flexify_Checkout\Core\Ajax::scrub_orphan_checkout_conditions();
        }

        if ( $needs_settings || $needs_step_fields || $needs_migration ) {
            update_option( 'flexify_checkout_schema_version', self::SCHEMA_VERSION );
        }
    }


	/**
	 * Clear WooCommerce template cache
	 *
	 * @since 1.0.0
     * @version 5.0.0
	 * @return void
	 */
    public static function clear_wc_template_cache() {
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
		$message = __( '<strong>Flexify Checkout</strong> requer a versÃ£o do WooCommerce 6.0 ou maior. FaÃ§a a atualizaÃ§Ã£o do plugin WooCommerce.', 'flexify-checkout-for-woocommerce' );

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
	 * @param string $plugin_meta | An array of the pluginâ€™s metadata, including the version, author, author URI, and plugin URI
	 * @param string $plugin_file | Path to the plugin file relative to the plugins directory
	 * @param array $plugin_data | An array of plugin data
	 * @param string $status | Status filter currently applied to the plugin list
	 * @return string
	 */
	public function add_row_meta_links( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( strpos( $plugin_file, $this->basename ) !== false ) {
			$new_links = array(
				'docs' => '<a href="'. FLEXIFY_CHECKOUT_DOCS_LINK .'" target="_blank">'. __( 'DocumentaÃ§Ã£o', 'flexify-checkout-for-woocommerce' ) .'</a>',
			);
			
			$plugin_meta = array_merge( $plugin_meta, $new_links );
		}
	 
		return $plugin_meta;
	}


	/**
	 * Plugin action links Pro version
	 * 
	 * @since 3.3.0
	 * @version 5.0.2
     * @param array $action_links | Current action links
	 * @return array
	 */
	public function be_pro_link( $action_links ) {
		$plugins_links = array(
			'<a id="get-pro-flexify-checkout" target="_blank" href="https://meumouse.com/plugins/flexify-checkout-para-woocommerce/?utm_source=wordpress&utm_medium=plugins-list&utm_campaign=flexify-checkout">' . __( 'Seja PRO', 'flexify-checkout-for-woocommerce' ) . '</a>'
		);
	
		return array_merge( $plugins_links, $action_links );
	}


    /**
     * Instance classes after load Composer
     * 
     * @since 5.0.0
     * @version 5.2.0
     * @return void
     */
    public function instance_classes() {
        /**
         * Filter to add new classes
         * 
         * @since 5.0.0
         * @param array $classes | Array with classes to instance
         */
        $manual_classes = apply_filters( 'Flexify_Checkout/Init/Instance_Classes', array(
            '\MeuMouse\Flexify_Checkout\Compatibility\Backward_Compatibility',
            '\MeuMouse\Flexify_Checkout\Tracking\Router',
            '\MeuMouse\Flexify_Checkout\API\REST_Checkout_Fields',
            '\MeuMouse\Flexify_Checkout\Admin\Settings\Views\Integrations',
        ));

        // iterate through manual classes and instance them
        foreach ( $manual_classes as $class ) {
            if ( class_exists( $class ) ) {
                $instance = new $class();

                if ( method_exists( $instance, 'init' ) ) {
                    $instance->init();
                }
            }
        }

        // get classmap from Composer
        $classmap = include_once $this->directory . 'vendor/composer/autoload_classmap.php';

        // ensure classmap is an array
        if ( ! is_array( $classmap ) ) {
            $classmap = array();
        }

        // iterate through classmap and instance classes
        foreach ( $classmap as $class => $path ) {
            // skip classes not in the plugin namespace
            if ( strpos( $class, 'MeuMouse\\Flexify_Checkout\\' ) !== 0 ) {
                continue;
            }

            // skip the Init class to prevent duplicate instances
            if ( strpos( $class, 'MeuMouse\\Flexify_Checkout\\Core\\Init' ) !== false ) {
                continue;
            }

            // skip specific utility classes
            if ( $class === 'Composer\\InstalledVersions' ) {
                continue;
            }

            // check if class exists
            if ( ! class_exists( $class ) ) {
                continue;
            }

            // use ReflectionClass to check if class is instantiable
            $reflection = new \ReflectionClass( $class );

            // instance only if class is not abstract, trait or interface
            if ( ! $reflection->isInstantiable() ) {
                continue;
            }

            // check if class has a constructor
            $constructor = $reflection->getConstructor();

            // skip classes that require mandatory arguments in __construct
            if ( $constructor && $constructor->getNumberOfRequiredParameters() > 0 ) {
                continue;
            }

            // safe instance
            $instance = new $class();

            // this is useful for classes that need to run some initialization code
            if ( method_exists( $instance, 'init' ) ) {
                $instance->init();
            }
        }
    }
}

