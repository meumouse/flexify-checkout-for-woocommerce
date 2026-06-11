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
 * @version 5.5.2
 * @package MeuMouse\Flexify_Checkout\Core
 * @author MeuMouse.com
 */
class Init {
    /**
     * Schema version for options bootstrap/migrations.
     *
     * @since 5.4.3
     * @var int
     */
    const SCHEMA_VERSION = 2;

    /**
     * Option name that stores the cached class registry.
     *
     * @since 5.5.2
     * @var string
     */
    const CLASS_REGISTRY_OPTION = 'flexify_checkout_class_registry';

    /**
     * Option name that tracks the plugin version the registry was built for.
     *
     * @since 5.5.2
     * @var string
     */
    const CLASS_REGISTRY_VERSION_OPTION = 'flexify_checkout_class_registry_version';

    /**
     * Transient gating the "prevent illegal copies" admin check.
     *
     * @since 5.5.2
     * @var string
     */
    const ILLEGAL_COPY_TRANSIENT = 'flexify_checkout_illegal_copy_check';

    /**
     * Ensure bootstrap is registered once.
     *
     * @since 5.5.0
     * @var bool
     */
    private static $bootstrapped = false;

    /**
     * Plugin base name.
     *
     * @since 5.0.0
     * @var string
     */
    public $basename = '';

    /**
     * Plugin main file path.
     *
     * @since 5.0.0
     * @var string
     */
    public $plugin_file = '';

    /**
     * Plugin directory path.
     *
     * @since 5.2.0
     * @var string
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

        // Invalidate the cached class registry whenever this plugin is updated,
        // so a fresh classmap scan runs on the next request.
        add_action( 'upgrader_process_complete', array( __CLASS__, 'maybe_invalidate_registry_on_upgrade' ), 10, 2 );

        self::$bootstrapped = true;
    }


    /**
     * Drop the cached class registry whenever this plugin is upgraded.
     *
     * @since 5.5.2
     * @param \WP_Upgrader $upgrader Upgrader instance.
     * @param array $hook_extra Context provided by the upgrader.
     * @return void
     */
    public static function maybe_invalidate_registry_on_upgrade( $upgrader, $hook_extra ) {
        if ( empty( $hook_extra['type'] ) || $hook_extra['type'] !== 'plugin' ) {
            return;
        }

        if ( ! defined( 'FLEXIFY_CHECKOUT_BASENAME' ) ) {
            return;
        }

        $plugins = array();

        if ( ! empty( $hook_extra['plugins'] ) && is_array( $hook_extra['plugins'] ) ) {
            $plugins = $hook_extra['plugins'];
        } elseif ( ! empty( $hook_extra['plugin'] ) ) {
            $plugins = array( $hook_extra['plugin'] );
        }

        if ( in_array( FLEXIFY_CHECKOUT_BASENAME, $plugins, true ) ) {
            self::invalidate_class_registry();
        }
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
        self::invalidate_class_registry();

        // WooCommerce template cache helper may not be loaded yet during activation;
        // defer the call so it runs after WC bootstraps.
        add_action( 'init', array( __CLASS__, 'clear_wc_template_cache' ), 99 );
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
        self::invalidate_class_registry();
        delete_transient( self::ILLEGAL_COPY_TRANSIENT );

        if ( function_exists('wc_clear_template_cache') ) {
            self::clear_wc_template_cache();
        }
    }


    /**
     * Drop the cached class registry so the next bootstrap rebuilds it.
     *
     * @since 5.5.2
     * @return void
     */
    public static function invalidate_class_registry() {
        delete_option( self::CLASS_REGISTRY_OPTION );
        delete_option( self::CLASS_REGISTRY_VERSION_OPTION );
    }


    /**
     * Define plugin constants.
     *
     * @since 5.5.0
     * @param string $plugin_file Plugin main file.
     * @return void
     */
    private static function define_constants( $plugin_file, $plugin_version ) {
        $base_dir = plugin_dir_path( $plugin_file );
        $base_url = plugin_dir_url( $plugin_file );

        // Paths and URLs (filesystem layout).
        $paths = array(
            'FLEXIFY_CHECKOUT_FILE' => $plugin_file,
            'FLEXIFY_CHECKOUT_BASENAME' => plugin_basename( $plugin_file ),
            'FLEXIFY_CHECKOUT_PATH' => $base_dir,
            'FLEXIFY_CHECKOUT_ABSPATH' => dirname( $plugin_file ) . '/',
            'FLEXIFY_CHECKOUT_INC_PATH' => $base_dir . 'inc/',
            'FLEXIFY_CHECKOUT_TEMPLATES_DIR' => $base_dir . 'templates/',
            'FLEXIFY_CHECKOUT_SETTINGS_TABS_DIR' => $base_dir . 'inc/Views/Settings/Tabs/',
            'FLEXIFY_CHECKOUT_URL' => $base_url,
            'FLEXIFY_CHECKOUT_ASSETS' => $base_url . 'assets/',
        );

        // Plugin metadata.
        $metadata = array(
            'FLEXIFY_CHECKOUT_SLUG' => 'flexify-checkout-for-woocommerce',
            'FLEXIFY_CHECKOUT_VERSION' => $plugin_version,
            'FLEXIFY_CHECKOUT_DOCS_LINK' => 'https://ajuda.meumouse.com/docs/flexify-checkout-for-woocommerce/overview',
            'FLEXIFY_CHECKOUT_DEV_MODE' => false,
        );

        foreach ( $paths + $metadata as $key => $value ) {
            if ( ! defined( $key ) ) {
                define( $key, $value );
            }
        }

        // Lazy values: deferred to avoid forcing an option read on every request
        // (FLEXIFY_CHECKOUT_ADMIN_EMAIL) or a debug-setting lookup at bootstrap
        // (FLEXIFY_CHECKOUT_DEBUG_MODE). Use flexify_checkout_is_debug() / the
        // helper for admin email instead of constants when possible.
        if ( ! defined( 'FLEXIFY_CHECKOUT_ADMIN_EMAIL' ) ) {
            define( 'FLEXIFY_CHECKOUT_ADMIN_EMAIL', get_option( 'admin_email' ) );
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
     * @version 5.5.2
     * @param string $plugin_version Plugin version coming from bootstrap.
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

        // Load shared procedural helpers (defines is_flexify_checkout(), the
        // woocommerce_is_checkout helper, etc.). flexify_checkout_is_debug()
        // is provided there and replaces the eager FLEXIFY_CHECKOUT_DEBUG_MODE
        // constant for callers that no longer need a compile-time bool.
        include_once( FLEXIFY_CHECKOUT_INC_PATH . 'Core/Functions.php' );

        // BC shim: keep the constant defined for any third-party code that
        // still reads it directly. The helper above is the preferred API.
        if ( ! defined( 'FLEXIFY_CHECKOUT_DEBUG_MODE' ) ) {
            define( 'FLEXIFY_CHECKOUT_DEBUG_MODE', function_exists('flexify_checkout_is_debug') ? flexify_checkout_is_debug() : false );
        }

        // load WordPress plugin class if function is_plugin_active() is not defined
        if ( ! function_exists('is_plugin_active') ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        // Schedule the illegal-copy sweep on admin pages only; the actual check
        // is now gated behind a daily transient so it doesn't hit is_dir() on
        // every admin_init.
        if ( is_admin() ) {
            add_action( 'admin_init', array( $this, 'maybe_remove_illegal_copy' ) );
        }

        // Check if WooCommerce is active.
        if ( is_plugin_active('woocommerce/woocommerce.php') && defined('WC_VERSION') && version_compare( WC_VERSION, '6.0', '>' ) ) {
            self::maybe_bootstrap_defaults();
            $this->instance_classes();

            if ( is_admin() ) {
                $this->register_admin_listing_hooks();
            }
        } else {
            add_action( 'admin_notices', array( $this, 'woocommerce_version_notice' ) );
            deactivate_plugins( 'flexify-checkout-for-woocommerce/flexify-checkout-for-woocommerce.php' );
            add_action( 'admin_notices', array( $this, 'deactivate_flexify_checkout_notice' ) );
        }

        // hook after plugin init
        do_action( 'Flexify_Checkout/Init' );
    }


    /**
     * Register hooks that only affect the plugins.php listing screen.
     *
     * Previously these ran on every admin request and even computed $_SERVER
     * strings on every frontend hit through the "Pro badge" block. Now they
     * are gated behind is_admin() and the Pro badge logic is further gated to
     * the plugins.php screen via $pagenow.
     *
     * @since 5.5.2
     * @return void
     */
    private function register_admin_listing_hooks() {
        add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'add_action_plugin_links' ), 10, 4 );
        add_filter( 'plugin_row_meta', array( $this, 'add_row_meta_links' ), 10, 4 );

        global $pagenow;

        if ( $pagenow !== 'plugins.php' ) {
            return;
        }

        if ( get_option( 'flexify_checkout_license_status' ) === 'valid' ) {
            return;
        }

        add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'be_pro_link' ), 10, 4 );
        add_action( 'admin_head', array( '\MeuMouse\Flexify_Checkout\Views\Styles', 'be_pro_styles' ) );
    }


    /**
     * Sweep the unauthorized activator plugin if it is present.
     *
     * Gated by a daily transient so the filesystem check only runs once per
     * 24h per site instead of on every admin_init.
     *
     * @since 5.5.2
     * @return void
     */
    public function maybe_remove_illegal_copy() {
        if ( wp_doing_ajax() ) {
            return;
        }

        if ( get_transient( self::ILLEGAL_COPY_TRANSIENT ) ) {
            return;
        }

        set_transient( self::ILLEGAL_COPY_TRANSIENT, 1, DAY_IN_SECONDS );

        $plugin_slug = 'meumouse-ativador/meumouse-ativador.php';
        $plugin_dir = WP_PLUGIN_DIR . '/meumouse-ativador';

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

        if ( is_plugin_active( $plugin_slug ) ) {
            deactivate_plugins( $plugin_slug, true );
        }

        $result = delete_plugins( array( $plugin_slug ) );

        if ( is_wp_error( $result ) ) {
            error_log( 'Error on delete plugin: ' . $result->get_error_message() );
        }
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
     * Ensure required options/default structures exist and are sane.
     *
     * @since 5.4.3
     * @param bool $force Force full defaults merge when true.
     * @return void
     */
    public static function maybe_bootstrap_defaults( $force = false ) {
        $stored_schema_version = intval( get_option( 'flexify_checkout_schema_version', 0 ) );
        $stored_settings = get_option( 'flexify_checkout_settings', array() );
        $stored_step_fields = maybe_unserialize( get_option( 'flexify_checkout_step_fields', array() ) );

        $needs_settings = $force || ! is_array( $stored_settings ) || empty( $stored_settings );
        $needs_step_fields = $force || ! is_array( $stored_step_fields ) || empty( $stored_step_fields );
        $needs_migration = $stored_schema_version < self::SCHEMA_VERSION;

        // Hot path: nothing to do, avoid instantiating Admin_Options.
        if ( ! $needs_settings && ! $needs_step_fields && ! $needs_migration ) {
            return;
        }

        $admin_options = new Admin_Options();

        if ( $needs_settings || $needs_migration ) {
            $admin_options->set_default_options();
        }

        if ( $needs_step_fields || $needs_migration ) {
            $admin_options->set_checkout_step_fields();
        }

        // Drop deprecated managed fields that should no longer be regenerated.
        if ( $needs_migration ) {
            self::purge_deprecated_step_fields();
        }

        // Safety net: orphan condition cleanup only matters during migration.
        if ( $needs_migration && class_exists( '\MeuMouse\Flexify_Checkout\Core\Ajax' ) ) {
            \MeuMouse\Flexify_Checkout\Core\Ajax::scrub_orphan_checkout_conditions();
        }

        update_option( 'flexify_checkout_schema_version', self::SCHEMA_VERSION );
    }


    /**
     * Remove deprecated managed step fields from the stored registry.
     *
     * Some fields (e.g. billing_document and billing_sex, legacy SuperFrete
     * aliases) were previously registered as plugin defaults and force-merged
     * on every bootstrap, which made them impossible to delete from the field
     * manager (billing_sex duplicated the native billing_gender field). They
     * are no longer part of the defaults, so this cleans any stored copy.
     *
     * @since 5.5.4
     * @return void
     */
    public static function purge_deprecated_step_fields() {
        $deprecated_fields = array( 'billing_document', 'billing_sex' );
        $step_fields = maybe_unserialize( get_option( 'flexify_checkout_step_fields', array() ) );

        if ( ! is_array( $step_fields ) ) {
            return;
        }

        $updated = false;

        foreach ( $deprecated_fields as $field_id ) {
            if ( isset( $step_fields[ $field_id ] ) ) {
                unset( $step_fields[ $field_id ] );
                $updated = true;
            }
        }

        if ( $updated ) {
            update_option( 'flexify_checkout_step_fields', maybe_serialize( $step_fields ) );
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
	 * @param string $plugin_meta | An array of the pluginâ€™s metadata, including the version, author, author URI, and plugin URI
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
     * @version 5.2.0
     * @return void
     */
    public function instance_classes() {
        /**
         * Filter to add new classes
         *
         * @since 5.0.0
         * @param array $classes Array with classes to instance.
         */
        $manual_classes = apply_filters( 'Flexify_Checkout/Init/Instance_Classes', array(
            '\MeuMouse\Flexify_Checkout\Compatibility\Backward_Compatibility',
            '\MeuMouse\Flexify_Checkout\Tracking\Router',
            '\MeuMouse\Flexify_Checkout\API\REST_Checkout_Fields',
            '\MeuMouse\Flexify_Checkout\Admin\Settings\Views\Integrations',
            '\MeuMouse\Flexify_Checkout\Admin\Settings_Import_Export',
        ));

        $manual_classes_map = array();
        $is_admin = is_admin();

        // Manual classes always run first so they are guaranteed available for
        // any other class that resolves them in its constructor.
        foreach ( $manual_classes as $class ) {
            $normalized = ltrim( (string) $class, '\\' );
            $manual_classes_map[ $normalized ] = true;

            $this->boot_class( $class );
        }

        foreach ( self::get_class_registry() as $class => $context ) {
            if ( isset( $manual_classes_map[ $class ] ) ) {
                continue;
            }

            // Admin-only classes don't need to load on the frontend (or REST/AJAX
            // requests that aren't admin AJAX).
            if ( $context === 'admin' && ! $is_admin ) {
                continue;
            }

            $this->boot_class( $class );
        }
    }


    /**
     * Instance a single class and call its optional init() bootstrap method.
     *
     * @since 5.5.2
     * @param string $class Fully-qualified class name.
     * @return void
     */
    private function boot_class( $class ) {
        if ( ! class_exists( $class ) ) {
            return;
        }

        $instance = new $class();

        if ( method_exists( $instance, 'init' ) ) {
            $instance->init();
        }
    }


    /**
     * Get the cached registry of instantiable plugin classes.
     *
     * The registry is rebuilt only when the plugin version changes (or when the
     * cache is missing). This replaces the previous per-request scan that used
     * ReflectionClass on every entry of vendor/composer/autoload_classmap.php.
     *
     * @since 5.5.2
     * @return array<string,string> Map of class name => context tag (always|admin).
     */
    private static function get_class_registry() {
        $current_version = defined( 'FLEXIFY_CHECKOUT_VERSION' ) ? FLEXIFY_CHECKOUT_VERSION : '';
        $cached_version = (string) get_option( self::CLASS_REGISTRY_VERSION_OPTION, '' );

        if ( $cached_version !== '' && $cached_version === $current_version ) {
            $registry = get_option( self::CLASS_REGISTRY_OPTION, array() );

            if ( is_array( $registry ) && ! empty( $registry ) ) {
                return $registry;
            }
        }

        $registry = self::build_class_registry();

        update_option( self::CLASS_REGISTRY_OPTION, $registry, false );
        update_option( self::CLASS_REGISTRY_VERSION_OPTION, $current_version, false );

        return $registry;
    }


    /**
     * Build the class registry by scanning Composer's classmap.
     *
     * Only runs on cache miss (first request after a plugin update). Uses
     * ReflectionClass to filter out abstracts/interfaces/traits and classes
     * that require constructor arguments — same rules as before, just cached.
     *
     * @since 5.5.2
     * @return array<string,string>
     */
    private static function build_class_registry() {
        $classmap_file = defined( 'FLEXIFY_CHECKOUT_PATH' ) ? FLEXIFY_CHECKOUT_PATH . 'vendor/composer/autoload_classmap.php' : '';

        if ( $classmap_file === '' || ! is_readable( $classmap_file ) ) {
            return array();
        }

        $classmap = include $classmap_file;

        if ( ! is_array( $classmap ) ) {
            return array();
        }

        $skip = array(
            'MeuMouse\\Flexify_Checkout\\Core\\Init' => true,
            'Composer\\InstalledVersions' => true,
        );

        $registry = array();

        foreach ( $classmap as $class => $path ) {
            $normalized = ltrim( (string) $class, '\\' );

            if ( isset( $skip[ $normalized ] ) ) {
                continue;
            }

            if ( strpos( $normalized, 'MeuMouse\\Flexify_Checkout\\' ) !== 0 ) {
                continue;
            }

            if ( ! class_exists( $class ) ) {
                continue;
            }

            $reflection = new \ReflectionClass( $class );

            if ( ! $reflection->isInstantiable() ) {
                continue;
            }

            $constructor = $reflection->getConstructor();

            if ( $constructor && $constructor->getNumberOfRequiredParameters() > 0 ) {
                continue;
            }

            $registry[ $normalized ] = self::resolve_class_context( $normalized );
        }

        return $registry;
    }


    /**
     * Resolve the runtime context where a class needs to be instanced.
     *
     * Conservative by default: anything outside the well-known admin/settings
     * trees stays as 'always' so integrations that register frontend hooks via
     * an "admin-looking" namespace are not accidentally skipped.
     *
     * @since 5.5.2
     * @param string $class Normalized fully-qualified class name.
     * @return string One of: 'always', 'admin'.
     */
    private static function resolve_class_context( $class ) {
        $admin_only_prefixes = array(
            'MeuMouse\\Flexify_Checkout\\Views\\Settings\\',
            'MeuMouse\\Flexify_Checkout\\Admin\\Settings\\Views\\',
        );

        foreach ( $admin_only_prefixes as $prefix ) {
            if ( strpos( $class, $prefix ) === 0 ) {
                return 'admin';
            }
        }

        return 'always';
    }
}