<?php

namespace MeuMouse\Flexify_Checkout\Core\Webhooks;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Single source of truth for webhook endpoint configuration.
 *
 * Webhooks are stored under the unified main option
 * (flexify_checkout_settings) as a `webhooks` key shaped:
 *
 *     [ event_key => [ { enabled, url, headers: [ {name,value} ] }, ... ] ]
 *
 * To keep working for installs upgrading from the recovery-scoped webhooks,
 * reads fall back to the legacy recovery option until the one-shot migration
 * (Webhook_Migration) copies the data over. This fallback lives here and ONLY
 * here so the dispatcher and the REST UI never diverge.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Core\Webhooks
 * @author MeuMouse.com
 */
class Webhook_Settings {

    /**
     * Unified option holding all simple settings.
     *
     * @since 6.0.0
     * @var string
     */
    const OPTION = 'flexify_checkout_settings';

    /**
     * Sub-key under the unified option holding the webhooks map.
     *
     * @since 6.0.0
     * @var string
     */
    const KEY = 'webhooks';

    /**
     * Legacy recovery option, read as a fallback before migration runs.
     *
     * @since 6.0.0
     * @var string
     */
    const LEGACY_OPTION = 'flexify_checkout_recovery_carts_settings';


    /**
     * Read the full webhooks map.
     *
     * Prefers the unified option; falls back to the legacy recovery option when
     * the unified key is still empty (pre-migration window, e.g. a frontend
     * dispatch before any admin_init has run on the install).
     *
     * @since 6.0.0
     * @return array<string,array<int,array<string,mixed>>>
     */
    public static function get_all() {
        $settings = get_option( self::OPTION, array() );

        if ( is_array( $settings ) && ! empty( $settings[ self::KEY ] ) && is_array( $settings[ self::KEY ] ) ) {
            return $settings[ self::KEY ];
        }

        $legacy = get_option( self::LEGACY_OPTION, array() );

        if ( is_array( $legacy ) && ! empty( $legacy[ self::KEY ] ) && is_array( $legacy[ self::KEY ] ) ) {
            return $legacy[ self::KEY ];
        }

        return array();
    }


    /**
     * Get the configured endpoints for a single event.
     *
     * @since 6.0.0
     * @param string $event_key Event key.
     * @return array<int,array<string,mixed>>
     */
    public static function get_event_webhooks( $event_key ) {
        $all = self::get_all();

        return ( isset( $all[ $event_key ] ) && is_array( $all[ $event_key ] ) ) ? $all[ $event_key ] : array();
    }


    /**
     * Persist the full webhooks map into the unified option.
     *
     * Merges over the stored option array so no other settings keys are lost.
     *
     * @since 6.0.0
     * @param array<string,array<int,array<string,mixed>>> $config Webhooks map.
     * @return void
     */
    public static function save_all( array $config ) {
        $settings = get_option( self::OPTION, array() );

        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        $settings[ self::KEY ] = $config;

        update_option( self::OPTION, $settings );
    }
}
