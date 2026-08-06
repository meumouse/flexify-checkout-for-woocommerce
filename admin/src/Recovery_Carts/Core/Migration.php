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
 * This class therefore performs an idempotent data-migration pass (which is a
 * no-op for the shared identifiers, but guards against legacy option aliases and
 * exposes an extension hook) and then retires the standalone on the first admin
 * request: it deactivates it, deletes the plugin files (Flexify Checkout 6.0
 * ships the feature natively, so the standalone is redundant), refreshes rewrite
 * rules once, and shows a dismissible notice reporting what happened. An audit of
 * the standalone confirmed it ships no uninstall.php / register_uninstall_hook
 * and no destructive deactivation routine, so deleting it will not remove the
 * data; as an extra safeguard the settings option is snapshotted and restored
 * around the deletion. Automatic deletion can be disabled with the
 * 'Flexify_Checkout/Recovery_Carts/Auto_Delete_Standalone' filter.
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
     * Settings option shared with the standalone addon (snapshotted around the
     * plugin deletion as a safeguard).
     *
     * @since 6.0.0
     * @var string
     */
    const SETTINGS_OPTION = 'flexify_checkout_recovery_carts_settings';

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
     * Whether the standalone addon is installed on disk (active or not).
     *
     * @since 6.0.0
     * @return bool
     */
    public static function is_standalone_installed() {
        return file_exists( trailingslashit( WP_PLUGIN_DIR ) . self::STANDALONE_BASENAME );
    }


    /**
     * Run the data migration and retire the standalone addon (deactivate + delete).
     *
     * Idempotent: once the data pass has run and the standalone is gone, this
     * returns early on subsequent requests.
     *
     * @since 6.0.0
     * @return void
     */
    public function maybe_migrate() {
        if ( ! current_user_can('activate_plugins') ) {
            return;
        }

        $already_migrated = (bool) get_option( self::MIGRATED_OPTION );
        $installed = self::is_standalone_installed();

        // Fully done: data migrated and no standalone left on disk.
        if ( $already_migrated && ! $installed ) {
            return;
        }

        // 1. Data migration. Identifiers are shared, so this is a no-op in
        //    practice, but it stays idempotent and extensible for edge cases.
        self::migrate_data();

        // 2. Retire the standalone if it is still present.
        if ( $installed ) {
            if ( ! function_exists('deactivate_plugins') ) {
                include_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            if ( self::is_standalone_active() ) {
                deactivate_plugins( self::STANDALONE_BASENAME );
            }

            $outcome = self::delete_standalone();

            // Drop any stale rewrite rules the standalone left behind.
            flush_rewrite_rules( false );

            update_option( self::NOTICE_OPTION, $outcome, false );
        }

        update_option( self::MIGRATED_OPTION, defined('FLEXIFY_CHECKOUT_VERSION') ? FLEXIFY_CHECKOUT_VERSION : '1', false );
    }


    /**
     * Idempotent data migration from the standalone addon.
     *
     * The standalone and the native module share every storage identifier
     * (CPTs, statuses, _fcrc_* meta, the settings option and cron hooks), so
     * there is nothing to transform. This method only copies any legacy option
     * alias into the current key when the current one is missing and fires an
     * extension hook, so third parties or future versions can migrate extra data.
     *
     * @since 6.0.0
     * @return void
     */
    public static function migrate_data() {
        /**
         * Map of legacy option name => current option name to copy when the
         * current key is absent. Empty by default (identifiers are shared).
         *
         * @since 6.0.0
         * @param array $aliases | [ legacy_key => current_key ].
         */
        $aliases = apply_filters( 'Flexify_Checkout/Recovery_Carts/Legacy_Option_Aliases', array() );

        foreach ( (array) $aliases as $legacy_key => $current_key ) {
            if ( false === get_option( $current_key, false ) ) {
                $legacy_value = get_option( $legacy_key, false );

                if ( false !== $legacy_value ) {
                    update_option( $current_key, $legacy_value, false );
                }
            }
        }

        /**
         * Fires during the one-shot cart recovery data migration, after the
         * built-in option aliasing. Use it to migrate any additional data.
         *
         * @since 6.0.0
         */
        do_action( 'Flexify_Checkout/Recovery_Carts/Migrate_Data' );
    }


    /**
     * Delete the standalone plugin's files.
     *
     * Snapshots the shared settings option and restores it afterwards, in case
     * deletion triggers any unexpected cleanup. Falls back to a "deactivated"
     * outcome (leaving the files in place) when auto-deletion is filtered off,
     * the user lacks the capability, or the filesystem is not writable.
     *
     * @since 6.0.0
     * @return string 'deleted' when the files were removed, otherwise 'deactivated'.
     */
    private static function delete_standalone() {
        /**
         * Allow disabling the automatic deletion of the standalone plugin.
         *
         * @since 6.0.0
         * @param bool $auto_delete | Defaults to true.
         */
        if ( ! apply_filters( 'Flexify_Checkout/Recovery_Carts/Auto_Delete_Standalone', true ) ) {
            return 'deactivated';
        }

        if ( ! current_user_can('delete_plugins') ) {
            return 'deactivated';
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        // Safeguard: preserve the shared settings across the deletion.
        $settings_backup = get_option( self::SETTINGS_OPTION, false );

        // Initialise the filesystem; bail to a deactivation-only outcome when it
        // is not directly writable (e.g. hosts that require FTP credentials).
        if ( ! WP_Filesystem() ) {
            return 'deactivated';
        }

        $result = delete_plugins( array( self::STANDALONE_BASENAME ) );

        // Restore the settings option if the deletion removed it.
        if ( false !== $settings_backup && false === get_option( self::SETTINGS_OPTION, false ) ) {
            update_option( self::SETTINGS_OPTION, $settings_backup, false );
        }

        if ( is_wp_error( $result ) || true !== $result ) {
            return 'deactivated';
        }

        return 'deleted';
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
        $state = get_option( self::NOTICE_OPTION );

        if ( ! $state || ! current_user_can('manage_woocommerce') ) {
            return;
        }

        $dismiss_url = wp_nonce_url(
            add_query_arg( 'fcrc_dismiss_migration_notice', '1' ),
            'fcrc_dismiss_migration_notice'
        );

        $plugin_name = '<strong>Flexify Checkout - Recovery Carts</strong>';

        if ( 'deleted' === $state ) {
            $message = sprintf(
                /* translators: %s: standalone plugin name */
                esc_html__( 'Cart recovery is now native to Flexify Checkout. The %s plugin has been automatically deactivated and removed — your data has been preserved.', 'flexify-checkout-for-woocommerce' ),
                $plugin_name
            );
        } else {
            // 'deactivated' and the legacy 'yes' value.
            $message = sprintf(
                /* translators: %s: standalone plugin name */
                esc_html__( 'Cart recovery is now native to Flexify Checkout. The %s plugin has been automatically disabled and can be safely deleted — your data has been preserved.', 'flexify-checkout-for-woocommerce' ),
                $plugin_name
            );
        }

        printf(
            '<div class="notice notice-success is-dismissible"><p>%1$s</p><p><a href="%2$s" class="button button-secondary">%3$s</a></p></div>',
            wp_kses( $message, array( 'strong' => array() ) ),
            esc_url( $dismiss_url ),
            esc_html__( 'Got it, hide notice', 'flexify-checkout-for-woocommerce' )
        );
    }
}
