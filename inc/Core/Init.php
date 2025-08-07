<?php

namespace MeuMouse\Flexify_Checkout\Core;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Init class plugin
 * 
 * @since 5.0.0
 * @version 5.0.2
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
     * @version 5.0.2
     * @return void
     */
    public function __construct() {
        // Display notice if PHP version is bottom 7.4
		if ( version_compare( phpversion(), '7.4', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
			return;
		}

        // load text domain
        load_plugin_textdomain( 'flexify-checkout-for-woocommerce', false, dirname( $this->basename ) . '/languages/' );

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
            self::instance_classes();

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
		$message = __( '<strong>Flexify Checkout</strong> requer a versão do PHP 7.4 ou maior. Contate o suporte da sua hospedagem para realizar a atualização.', 'flexify-checkout-for-woocommerce' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
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
     * @return void
     */
    public static function instance_classes() {
        $base_namespace = 'MeuMouse\\Flexify_Checkout';
        $base_path = FLEXIFY_CHECKOUT_INC_PATH;

        /**
         * Filter to add new classes
         * 
         * @since 5.0.0
         * @param array $classes | Array with classes to instance
         */
        $manual_classes = apply_filters( 'Flexify_Checkout/Init/Instance_Classes', array(
            '\MeuMouse\Flexify_Checkout\Compatibility\Backward_Compatibility',
        ));

        foreach ( $manual_classes as $class ) {
            if ( class_exists( $class ) ) {
                $instance = new $class();

                if ( method_exists( $instance, 'init' ) ) {
                    $instance->init();
                }
            }
        }

        // walk recursivily all that classes on /inc directory
        $rii = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $base_path ) );

        foreach ( $rii as $file ) {
            if ( $file->isDir() || $file->getExtension() !== 'php' ) {
                continue;
            }

            // Skip plain function files
            if ( strpos( file_get_contents( $file->getPathname() ), 'class ' ) === false ) {
                continue;
            }

            // relative path from inc/
            $relative_path = substr( $file->getPathname(), strlen( $base_path ) );

            // convert path to PSR-4 class name
            $class_path = str_replace( ['/', '\\', '.php'], ['\\', '\\', '' ], $relative_path );
            $class_name = $base_namespace . '\\' . $class_path;

            // sanitize class name
		    $class_name = trim( $class_name, '\\' );

            // skip if class already declared
            if ( class_exists( $class_name, false ) ) {
                continue;
            }

            // try to load class
            if ( ! class_exists( $class_name ) ) {
                continue;
            }

            $reflection = new \ReflectionClass( $class_name );

            // skip if not instantiable
            if ( ! $reflection->isInstantiable() ) {
                continue;
            }

            // skip if requires parameters
            if ( $reflection->getConstructor() && $reflection->getConstructor()->getNumberOfRequiredParameters() > 0 ) {
                continue;
            }
            
            // safe instance
            $instance = $reflection->newInstance();

            // optional instance method
            if ( method_exists( $instance, 'init' ) ) {
                $instance->init();
            }
        }
    }
}