<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Core;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * One-shot migration away from the standalone cart recovery addon.
 *
 * The cart recovery feature is now native to Flexify Checkout. Because the
 * standalone "flexify-checkout-recovery-carts-addon" stored everything in the
 * same WordPress database using identical identifiers (the fc-recovery-carts /
 * fcrc-cron-event CPTs, custom statuses, _fcrc_* meta, the
 * flexify_checkout_recovery_carts_settings option and the fcrc_* cron hooks),
 * there is no data to transform: the native feature simply keeps using it.
 *
 * This class therefore only retires the standalone: it deactivates it on the
 * first admin request, refreshes rewrite rules once, and shows a dismissible
 * notice telling the admin the plugin can be safely deleted. An audit of the
 * standalone confirmed it ships no uninstall.php / register_uninstall_hook and
 * no destructive deactivation routine, so deleting it will not remove the data.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Core
 * @author MeuMouse.com
 */
class Migration {

    /**
     * Standalone plugin basename.
     *
     * @since 6.0.0
     * @var string
     */
    const STANDALONE_BASENAME = 'flexify-checkout-recovery-carts-addon/flexify-checkout-recovery-carts-addon.php';

    /**
     * Option recording the version the migration ran for.
     *
     * @since 6.0.0
     * @var string
     */
    const MIGRATED_OPTION = 'flexify_checkout_recovery_migrated';

    /**
     * Option flag controlling the "standalone deactivated" admin notice.
     *
     * @since 6.0.0
     * @var string
     */
    const NOTICE_OPTION = 'flexify_checkout_recovery_show_migration_notice';

    /**
     * Constructor.
     *
     * @since 6.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'maybe_dismiss_notice' ) );
        add_action( 'admin_init', array( $this, 'maybe_migrate' ) );
        add_action( 'admin_notices', array( $this, 'render_notice' ) );
    }


    /**
     * Whether the standalone recovery addon is currently active.
     *
     * @since 6.0.0
     * @return bool
     */
    public static function is_standalone_active() {
        if ( function_exists('is_plugin_active') ) {
            return is_plugin_active( self::STANDALONE_BASENAME );
        }

        // Fallback used when this runs before wp-admin/includes/plugin.php is
        // loaded (register_classes() at init:99): read the option directly.
        $active = (array) get_option( 'active_plugins', array() );

        if ( in_array( self::STANDALONE_BASENAME, $active, true ) ) {
            return true;
        }

        if ( is_multisite() ) {
            $network_active = (array) get_site_option( 'active_sitewide_plugins', array() );

            return isset( $network_active[ self::STANDALONE_BASENAME ] );
        }

        return false;
    }


    /**
     * Deactivate the standalone addon once, now that the feature is native.
     *
     * @since 6.0.0
     * @return void
     */
    public function maybe_migrate() {
        if ( ! current_user_can('activate_plugins') ) {
            return;
        }

        if ( ! self::is_standalone_active() ) {
            return;
        }

        if ( ! function_exists('deactivate_plugins') ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // No data transformation: identifiers are shared. Just retire the addon.
        deactivate_plugins( self::STANDALONE_BASENAME );

        // Drop any stale rewrite rules the standalone left behind.
        flush_rewrite_rules( false );

        update_option( self::MIGRATED_OPTION, defined('FLEXIFY_CHECKOUT_VERSION') ? FLEXIFY_CHECKOUT_VERSION : '1', false );
        update_option( self::NOTICE_OPTION, 'yes', false );
    }


    /**
     * Handle dismissal of the migration notice.
     *
     * @since 6.0.0
     * @return void
     */
    public function maybe_dismiss_notice() {
        if ( empty( $_GET['fcrc_dismiss_migration_notice'] ) ) {
            return;
        }

        if ( ! current_user_can('manage_woocommerce') ) {
            return;
        }

        $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'fcrc_dismiss_migration_notice' ) ) {
            return;
        }

        delete_option( self::NOTICE_OPTION );
    }


    /**
     * Render the dismissible "standalone deactivated" notice.
     *
     * @since 6.0.0
     * @return void
     */
    public function render_notice() {
        if ( 'yes' !== get_option( self::NOTICE_OPTION ) || ! current_user_can('manage_woocommerce') ) {
            return;
        }

        $dismiss_url = wp_nonce_url(
            add_query_arg( 'fcrc_dismiss_migration_notice', '1' ),
            'fcrc_dismiss_migration_notice'
        );

        $message = sprintf(
            /* translators: %s: standalone plugin name */
            esc_html__( 'Cart recovery is now native to Flexify Checkout. The %s plugin has been automatically disabled and can be safely deleted — your data has been preserved.', 'flexify-checkout-for-woocommerce' ),
            '<strong>Flexify Checkout - Recovery Carts</strong>'
        );

        printf(
            '<div class="notice notice-success is-dismissible"><p>%1$s</p><p><a href="%2$s" class="button button-secondary">%3$s</a></p></div>',
            wp_kses( $message, array( 'strong' => array() ) ),
            esc_url( $dismiss_url ),
            esc_html__( 'Got it, hide notice', 'flexify-checkout-for-woocommerce' )
        );
    }
}
