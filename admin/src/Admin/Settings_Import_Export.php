<?php

namespace MeuMouse\Flexify_Checkout\Admin;

use MeuMouse\Flexify_Checkout\API\License;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handle export and import of plugin settings as JSON.
 *
 * Exports only the configuration option groups (general settings, checkout
 * step fields and conditions), deliberately leaving out license, cache and
 * runtime state so a snapshot can be safely moved between installations.
 *
 * @since 5.5.4
 * @author MeuMouse.com
 */
class Settings_Import_Export {

    /**
     * Format identifier written into exported files for forward validation.
     *
     * @since 5.5.4
     * @var string
     */
    const FORMAT = 'flexify-checkout-settings';

    /**
     * Option groups included in the export/import snapshot.
     *
     * The 'serialized' flag marks options stored through maybe_serialize() so
     * they are unserialized before export and re-serialized on import.
     *
     * @since 5.5.4
     * @var array<string,array{serialized:bool}>
     */
    const OPTION_GROUPS = array(
        'flexify_checkout_settings'   => array( 'serialized' => false ),
        'flexify_checkout_step_fields' => array( 'serialized' => true ),
        'flexify_checkout_conditions' => array( 'serialized' => false ),
    );

    /**
     * Build the export payload from the current configuration options.
     *
     * Static so the REST export endpoint can reuse it without re-instantiating
     * this class (which would re-register the legacy admin-ajax hooks).
     *
     * @since 5.5.4
     * @version 6.0.0
     * @return array
     */
    public static function build_payload() {
        $data = array();

        foreach ( self::OPTION_GROUPS as $option => $args ) {
            $value = get_option( $option, array() );

            if ( $args['serialized'] ) {
                $value = maybe_unserialize( $value );
            }

            $data[ $option ] = $value;
        }

        return array(
            'format'  => self::FORMAT,
            'version' => defined('FLEXIFY_CHECKOUT_VERSION') ? FLEXIFY_CHECKOUT_VERSION : '',
            'site_url' => home_url(),
            'exported_at' => current_time('mysql'),
            'data' => $data,
        );
    }


    /**
     * Apply an imported settings snapshot, overwriting the current option groups.
     *
     * Static so the REST import endpoint can reuse it without re-instantiating
     * this class (which would re-register the legacy admin-ajax hooks).
     *
     * @since 5.5.4
     * @version 6.0.0
     * @param array $data Map of option name => value from a valid payload.
     * @return bool True when at least one option group was written.
     */
    public static function apply_payload( $data ) {
        $applied = false;

        foreach ( self::OPTION_GROUPS as $option => $args ) {
            if ( ! array_key_exists( $option, $data ) ) {
                continue;
            }

            $value = $data[ $option ];

            // Skip Pro-only field overrides for sites without an active license so
            // an imported snapshot can't silently unlock gated configuration.
            if ( $option === 'flexify_checkout_step_fields' && ! License::is_valid() ) {
                continue;
            }

            if ( $args['serialized'] ) {
                $value = maybe_serialize( $value );
            }

            update_option( $option, $value );
            $applied = true;
        }

        return $applied;
    }
}
