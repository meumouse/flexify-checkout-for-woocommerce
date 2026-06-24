<?php

namespace MeuMouse\Flexify_Checkout\Core\Webhooks;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Migration as Recovery_Migration;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * One-shot migration of recovery-scoped webhooks into the unified option.
 *
 * Webhooks used to live under flexify_checkout_recovery_carts_settings['webhooks'].
 * This copies them into flexify_checkout_settings['webhooks'] once, so the global
 * Webhooks tab and the dispatcher read from a single place going forward.
 *
 * The legacy key is intentionally left untouched for one release (the read
 * fallback in Webhook_Settings ignores it once the unified key exists); a later
 * cleanup can drop it.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Core\Webhooks
 * @author MeuMouse.com
 */
class Webhook_Migration {

    /**
     * Version-stamped flag option marking the migration as done.
     *
     * @since 6.0.0
     * @var string
     */
    const MIGRATED_OPTION = 'flexify_checkout_webhooks_migrated';


    /**
     * Run the migration at most once.
     *
     * @since 6.0.0
     * @return void
     */
    public static function maybe_migrate() {
        if ( get_option( self::MIGRATED_OPTION ) ) {
            return;
        }

        // Don't touch the data while the standalone recovery addon is still
        // active — it owns the legacy option until it is deactivated.
        if ( class_exists( Recovery_Migration::class ) && Recovery_Migration::is_standalone_active() ) {
            return;
        }

        $unified = get_option( Webhook_Settings::OPTION, array() );

        // Already has unified webhooks → nothing to copy, just stamp.
        if ( is_array( $unified ) && ! empty( $unified[ Webhook_Settings::KEY ] ) ) {
            self::mark_done();

            return;
        }

        $legacy = get_option( Webhook_Settings::LEGACY_OPTION, array() );

        if ( is_array( $legacy ) && ! empty( $legacy[ Webhook_Settings::KEY ] ) && is_array( $legacy[ Webhook_Settings::KEY ] ) ) {
            Webhook_Settings::save_all( $legacy[ Webhook_Settings::KEY ] );
        }

        self::mark_done();
    }


    /**
     * Stamp the migration flag with the current plugin version.
     *
     * @since 6.0.0
     * @return void
     */
    private static function mark_done() {
        update_option( self::MIGRATED_OPTION, defined('FLEXIFY_CHECKOUT_VERSION') ? FLEXIFY_CHECKOUT_VERSION : '1', false );
    }
}
