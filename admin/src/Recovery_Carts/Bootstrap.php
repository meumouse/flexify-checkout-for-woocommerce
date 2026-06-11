<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Bootstraps the natively-integrated cart recovery feature.
 *
 * The cart recovery code was previously shipped as the standalone
 * "flexify-checkout-recovery-carts-addon" plugin. It now lives under
 * admin/src/Recovery_Carts and is wired into the main plugin here:
 *
 * 1. Defines the legacy FC_RECOVERY_CARTS_* constants the ported code still
 *    reads, remapped onto the host plugin's paths/URLs.
 * 2. Includes the procedural helpers from Core/Functions.php (analytics).
 * 3. Appends the recovery classes to the host Init class registry through the
 *    'Flexify_Checkout/Init/Instance_Classes' filter, so they boot regardless
 *    of the version-cached classmap registry.
 *
 * Classes resolve through Composer PSR-4 (MeuMouse\Flexify_Checkout\ =>
 * admin/src/), so no classmap entries are required. Views\* (WP_List_Table
 * subclasses) are intentionally left out of the boot list — they are created
 * on demand by the admin page callbacks, never at bootstrap.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts
 * @author MeuMouse.com
 */
class Bootstrap {

    /**
     * Guard against double registration.
     *
     * @since 6.0.0
     * @var bool
     */
    private static $registered = false;

    /**
     * Register the cart recovery feature with the host plugin.
     *
     * Must run after Init::bootstrap() has defined the FLEXIFY_CHECKOUT_*
     * constants and before Init::instance_classes() applies the
     * 'Flexify_Checkout/Init/Instance_Classes' filter (init, priority 99).
     *
     * @since 6.0.0
     * @return void
     */
    public static function register() {
        if ( self::$registered ) {
            return;
        }

        self::$registered = true;

        self::define_constants();
        self::include_functions();

        add_filter( 'Flexify_Checkout/Init/Instance_Classes', array( __CLASS__, 'register_classes' ) );
    }


    /**
     * Define the legacy FC_RECOVERY_CARTS_* constants on top of the host
     * plugin's paths so the ported code keeps working unchanged.
     *
     * @since 6.0.0
     * @return void
     */
    private static function define_constants() {
        $base_dir = defined('FLEXIFY_CHECKOUT_PATH') ? FLEXIFY_CHECKOUT_PATH : plugin_dir_path( dirname( __DIR__, 2 ) );
        $base_url = defined('FLEXIFY_CHECKOUT_URL') ? FLEXIFY_CHECKOUT_URL : plugin_dir_url( dirname( __DIR__, 2 ) );

        $constants = array(
            'FC_RECOVERY_CARTS_BASENAME'    => defined('FLEXIFY_CHECKOUT_BASENAME') ? FLEXIFY_CHECKOUT_BASENAME : '',
            'FC_RECOVERY_CARTS_FILE'        => defined('FLEXIFY_CHECKOUT_FILE') ? FLEXIFY_CHECKOUT_FILE : '',
            'FC_RECOVERY_CARTS_DIR'         => $base_dir,
            'FC_RECOVERY_CARTS_INC'         => $base_dir . 'admin/src/Recovery_Carts/',
            'FC_RECOVERY_CARTS_URL'         => $base_url,
            'FC_RECOVERY_CARTS_ASSETS'      => $base_url . 'assets/recovery-carts/',
            'FC_RECOVERY_CARTS_ABSPATH'     => defined('FLEXIFY_CHECKOUT_ABSPATH') ? FLEXIFY_CHECKOUT_ABSPATH : dirname( $base_dir ) . '/',
            'FC_RECOVERY_CARTS_SLUG'        => 'flexify-checkout-recovery-carts-addon',
            'FC_RECOVERY_CARTS_VERSION'     => defined('FLEXIFY_CHECKOUT_VERSION') ? FLEXIFY_CHECKOUT_VERSION : '',
            'FC_RECOVERY_CARTS_DOCS_URL'    => 'https://ajuda.meumouse.com/docs/fc-recovery-carts/overview',
            'FC_RECOVERY_CARTS_DEBUG_MODE'  => defined('FLEXIFY_CHECKOUT_DEBUG_MODE') ? FLEXIFY_CHECKOUT_DEBUG_MODE : false,
        );

        foreach ( $constants as $key => $value ) {
            if ( ! defined( $key ) ) {
                define( $key, $value );
            }
        }

        // Deferred: avoids an option read at bootstrap when nothing needs it.
        if ( ! defined('FC_RECOVERY_CARTS_ADMIN_EMAIL') ) {
            define( 'FC_RECOVERY_CARTS_ADMIN_EMAIL', get_option('admin_email') );
        }
    }


    /**
     * Load the procedural analytics helpers (fcrc_* functions).
     *
     * @since 6.0.0
     * @return void
     */
    private static function include_functions() {
        $functions = FC_RECOVERY_CARTS_INC . 'Core/Functions.php';

        if ( is_readable( $functions ) ) {
            include_once $functions;
        }
    }


    /**
     * Append the cart recovery classes to the host Init boot list.
     *
     * The host Init::boot_class() instances each entry and calls its optional
     * init() method. Pure-static utility classes (Helpers, Default_Options,
     * Components, Placeholders) are included to faithfully mirror the previous
     * standalone auto-instancing; their constructors are side-effect free.
     *
     * @since 6.0.0
     * @param array $classes Existing manual boot classes.
     * @return array
     */
    public static function register_classes( $classes ) {
        // Migration always boots so it can retire the standalone addon. While the
        // standalone is still active it owns the feature; booting the rest of the
        // native stack too would double-register hooks (duplicate cart tracking,
        // cron, etc.) for that one overlapping request. So defer the rest until
        // the standalone has been deactivated (Migration does that on admin_init).
        $classes[] = '\MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Migration';

        if ( \MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Migration::is_standalone_active() ) {
            return $classes;
        }

        $recovery = array(
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Components',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Default_Options',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Ajax',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Assets',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Cart_Events',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Coupons',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Hooks',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Order_Events',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Placeholders',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Session_Handler',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Webhooks',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Cron\Queue_Processor',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Cron\Recovery_Handler',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Cron\Scheduler_Manager',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Cron\WP_CLI_Command',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Frontend\Lead_Capture',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Frontend\Styles',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Integrations\Joinotify',
            // REST controllers (flexify-checkout/v1) backing the Vue admin pages.
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Rest\Analytics',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Rest\Carts',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Rest\Cart_Delete',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Rest\Queue',
            '\MeuMouse\Flexify_Checkout\Recovery_Carts\Rest\Queue_Delete',
        );

        return array_merge( $classes, $recovery );
    }
}
