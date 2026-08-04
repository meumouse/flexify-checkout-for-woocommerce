<?php

namespace MeuMouse\Flexify_Checkout\API;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Facade for the Modular Distribution Service (MDS) PHP SDK.
 *
 * Wires Flexify Checkout into the new MDS API (https://api.meumouse.com) for
 * licensing, signed update checks and rollback, replacing the legacy
 * {@see License} (custom AES transport) and {@see Updater} (unsigned static
 * JSON) paths.
 *
 * The whole integration is gated behind {@see self::is_enabled()} and defaults
 * to OFF: until a real per-product `api_key` and ed25519 `public_key` are
 * provisioned on the server and the feature flag is flipped, the plugin keeps
 * using the legacy path unchanged. This is the client half of the migration;
 * the server side (key generation, product registration, signed responses)
 * lives in the mds-api project.
 *
 * Clube M bundle: a single plugin install can be licensed either against the
 * Flexify Checkout product or against the Clube M bundle (license keys prefixed
 * "CM-"). Because the SDK binds license + updates together per product slug, we
 * register exactly one integration per request — the one matching the stored
 * license key — mirroring the legacy single-product swap.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\API
 * @author MeuMouse.com
 */
class MDS {

    /**
     * Default MDS API base URL.
     *
     * @since 6.0.0
     * @var string
     */
    const API_BASE_URL = 'https://api.meumouse.com';

    /**
     * Whether the SDK loader has already been required this request.
     *
     * @since 6.0.0
     * @var bool
     */
    private static $booted = false;

    /**
     * Require the SDK loader and hook product registration.
     *
     * Safe to call unconditionally and early (before `plugins_loaded`): the
     * loader only elects and boots the newest embedded copy at
     * `plugins_loaded` priority -100, and registration is further gated behind
     * {@see self::is_enabled()}.
     *
     * @since 6.0.0
     * @return void
     */
    public static function boot() {
        if ( self::$booted ) {
            return;
        }

        $loader = self::sdk_loader_path();

        if ( ! is_readable( $loader ) ) {
            return;
        }

        require_once $loader;
        self::$booted = true;

        add_action( 'mds_sdk_loaded', array( __CLASS__, 'on_sdk_loaded' ) );
    }


    /**
     * Path to the vendored SDK loader.
     *
     * @since 6.0.0
     * @return string
     */
    private static function sdk_loader_path() {
        $base = defined('FLEXIFY_CHECKOUT_PATH') ? FLEXIFY_CHECKOUT_PATH : plugin_dir_path( dirname( dirname( __DIR__ ) ) );

        return $base . 'admin/vendor/meumouse/mds-php-sdk/mds-sdk.php';
    }


    /**
     * Register the active product with the SDK once it has booted.
     *
     * @since 6.0.0
     * @return void
     */
    public static function on_sdk_loaded() {
        if ( ! self::is_enabled() ) {
            return;
        }

        // Register only the integration matching the stored license key so a
        // single plugin install never wires two competing plugin updaters.
        self::register_product( self::active_slug() );

        // Seed the SDK's license state from the legacy options on first run so
        // already-activated customers are not forced to re-activate at cutover.
        self::maybe_migrate_legacy_state();
    }


    /**
     * Whether the new MDS integration should be used at all.
     *
     * Requires: the feature flag on, the SDK facade class present, ext-sodium
     * available for signature verification, and both credentials configured
     * for the active product. Any missing piece keeps the legacy path live.
     *
     * @since 6.0.0
     * @return bool
     */
    public static function is_enabled() {
        // Feature flag: constant override wins, otherwise a stored option.
        $flag = defined('FLEXIFY_CHECKOUT_MDS_SDK')
            ? (bool) FLEXIFY_CHECKOUT_MDS_SDK
            : ( get_option('flexify_checkout_mds_sdk_enabled') === 'yes' );

        /**
         * Filters whether the MDS SDK integration is active.
         *
         * @since 6.0.0
         * @param bool $flag Current flag value.
         */
        $flag = (bool) apply_filters( 'Flexify_Checkout/MDS/Enabled', $flag );

        if ( ! $flag ) {
            return false;
        }

        if ( ! class_exists('\MeuMouse\MDS\SDK\SDK') ) {
            return false;
        }

        if ( ! function_exists('sodium_crypto_sign_verify_detached') ) {
            return false;
        }

        $config = self::config_for_slug( self::active_slug() );

        return ! empty( $config['api_key'] ) && ! empty( $config['public_key'] );
    }


    /**
     * Product slug that matches the currently stored license key.
     *
     * @since 6.0.0
     * @return string
     */
    public static function active_slug() {
        return self::slug_for_key( (string) get_option('flexify_checkout_license_key', '') );
    }


    /**
     * Resolve the product slug for a given license key.
     *
     * Keys prefixed "CM-" belong to the Clube M bundle; everything else is the
     * standalone Flexify Checkout product.
     *
     * @since 6.0.0
     * @param string $key License key.
     * @return string
     */
    public static function slug_for_key( $key ) {
        if ( strpos( (string) $key, 'CM-' ) === 0 ) {
            return self::bundle_slug();
        }

        return defined('FLEXIFY_CHECKOUT_SLUG') ? FLEXIFY_CHECKOUT_SLUG : 'flexify-checkout-for-woocommerce';
    }


    /**
     * Clube M bundle product slug.
     *
     * @since 6.0.0
     * @return string
     */
    public static function bundle_slug() {
        return defined('FLEXIFY_CHECKOUT_MDS_BUNDLE_SLUG') ? FLEXIFY_CHECKOUT_MDS_BUNDLE_SLUG : 'clube-m';
    }


    /**
     * Build the SDK configuration array for a product slug.
     *
     * Credentials come from constants (preferred, so they can be baked into a
     * build) or filters, and default to empty — which keeps the integration
     * inert until real values are provisioned.
     *
     * @since 6.0.0
     * @param string $slug Product slug.
     * @return array<string,mixed>
     */
    public static function config_for_slug( $slug ) {
        $fcw_slug = defined('FLEXIFY_CHECKOUT_SLUG') ? FLEXIFY_CHECKOUT_SLUG : 'flexify-checkout-for-woocommerce';
        $is_bundle = ( $slug === self::bundle_slug() );

        $api_key = $is_bundle
            ? ( defined('FLEXIFY_CHECKOUT_MDS_BUNDLE_API_KEY') ? FLEXIFY_CHECKOUT_MDS_BUNDLE_API_KEY : '' )
            : ( defined('FLEXIFY_CHECKOUT_MDS_API_KEY') ? FLEXIFY_CHECKOUT_MDS_API_KEY : '' );

        // The ed25519 public key is a property of the signing server, so both
        // products share it unless a bundle-specific override is defined.
        $public_key = defined('FLEXIFY_CHECKOUT_MDS_PUBLIC_KEY') ? FLEXIFY_CHECKOUT_MDS_PUBLIC_KEY : '';

        if ( $is_bundle && defined('FLEXIFY_CHECKOUT_MDS_BUNDLE_PUBLIC_KEY') ) {
            $public_key = FLEXIFY_CHECKOUT_MDS_BUNDLE_PUBLIC_KEY;
        }

        $config = array(
            'product_slug'    => $slug,
            'type'            => 'plugin',
            'file'            => defined('FLEXIFY_CHECKOUT_BASENAME') ? FLEXIFY_CHECKOUT_BASENAME : $fcw_slug . '/' . $fcw_slug . '.php',
            'current_version' => defined('FLEXIFY_CHECKOUT_VERSION') ? FLEXIFY_CHECKOUT_VERSION : '',
            'api_base_url'    => defined('FLEXIFY_CHECKOUT_MDS_API_BASE') ? FLEXIFY_CHECKOUT_MDS_API_BASE : self::API_BASE_URL,
            'api_key'         => (string) $api_key,
            'public_key'      => (string) $public_key,
            'item_name'       => $is_bundle ? 'Clube M' : 'Flexify Checkout for WooCommerce',
            'text_domain'     => 'flexify-checkout-for-woocommerce',
        );

        /**
         * Filters the SDK product configuration before registration.
         *
         * @since 6.0.0
         * @param array  $config Product config passed to SDK::register().
         * @param string $slug   Product slug.
         */
        return apply_filters( 'Flexify_Checkout/MDS/Product_Config', $config, $slug );
    }


    /**
     * Register a product with the SDK (idempotent per slug).
     *
     * @since 6.0.0
     * @param string $slug Product slug.
     * @return \MeuMouse\MDS\SDK\Integration|null
     */
    public static function register_product( $slug ) {
        if ( ! class_exists('\MeuMouse\MDS\SDK\SDK') ) {
            return null;
        }

        $existing = \MeuMouse\MDS\SDK\SDK::get( $slug );

        if ( $existing ) {
            return $existing;
        }

        $config = self::config_for_slug( $slug );

        if ( empty( $config['api_key'] ) || empty( $config['public_key'] ) ) {
            return null;
        }

        try {
            return \MeuMouse\MDS\SDK\SDK::register( $config );
        } catch ( \Throwable $e ) {
            if ( class_exists('\MeuMouse\Flexify_Checkout\Core\Logs\Logger') ) {
                \MeuMouse\Flexify_Checkout\Core\Logs\Logger::register_log( 'MDS SDK register failed: ' . $e->getMessage(), 'ERROR' );
            }

            return null;
        }
    }


    /**
     * Ensure the integration matching a license key is registered, then return it.
     *
     * Used during activation, when the key being validated may differ from the
     * one that was stored when the SDK booted this request.
     *
     * @since 6.0.0
     * @param string $key License key.
     * @return \MeuMouse\MDS\SDK\Integration|null
     */
    public static function integration_for_key( $key ) {
        return self::register_product( self::slug_for_key( $key ) );
    }


    /**
     * The integration for the currently stored license key, if registered.
     *
     * @since 6.0.0
     * @return \MeuMouse\MDS\SDK\Integration|null
     */
    public static function active_integration() {
        if ( ! class_exists('\MeuMouse\MDS\SDK\SDK') ) {
            return null;
        }

        $slug = self::active_slug();
        $integration = \MeuMouse\MDS\SDK\SDK::get( $slug );

        return $integration ? $integration : self::register_product( $slug );
    }


    /**
     * License manager for the active product, if available.
     *
     * @since 6.0.0
     * @return \MeuMouse\MDS\SDK\License\Manager|null
     */
    public static function license() {
        $integration = self::active_integration();

        return $integration ? $integration->license() : null;
    }


    /**
     * The ed25519 public key used to verify signed server messages (webhooks).
     *
     * @since 6.0.0
     * @return string Base64-encoded public key, or empty string.
     */
    public static function public_key() {
        $config = self::config_for_slug( self::active_slug() );

        return isset( $config['public_key'] ) ? (string) $config['public_key'] : '';
    }


    /* ---------------------------------------------------------------------- */
    /* Legacy state migration                                                  */
    /* ---------------------------------------------------------------------- */

    /**
     * Option that records which product slug has already been migrated.
     *
     * @since 6.0.0
     * @var string
     */
    const MIGRATION_FLAG = 'flexify_checkout_mds_state_migrated';

    /**
     * Bridge the legacy license options into the SDK's `license_state` so a site
     * that is already licensed stays licensed the moment the flag is flipped —
     * no forced re-activation.
     *
     * Idempotent: runs once per active product slug (re-runs only if the license
     * key later switches between the standalone and Clube M bundle products).
     * Never clobbers an existing SDK activation unless forced. The seeded state
     * is marked `signed => false` on purpose: the next daily heartbeat replaces
     * it with a genuine signed verdict from the server.
     *
     * @since 6.0.0
     * @param bool $force Re-run even when already migrated / SDK state exists.
     * @return bool True when a migration was written.
     */
    public static function maybe_migrate_legacy_state( $force = false ) {
        if ( ! self::is_enabled() ) {
            return false;
        }

        $key = (string) get_option('flexify_checkout_license_key', '');

        // Nothing was ever activated on the legacy side — let the SDK start clean.
        if ( '' === $key ) {
            return false;
        }

        $slug = self::slug_for_key( $key );

        if ( ! $force && (string) get_option( self::MIGRATION_FLAG, '' ) === $slug ) {
            return false;
        }

        $state_name = self::option_key( $slug, 'license_state' );
        $key_name   = self::option_key( $slug, 'license_key' );

        // Respect an activation the SDK already performed against the server.
        $existing = self::read_option( $state_name, null );

        if ( ! $force && ! empty( $existing ) ) {
            update_option( self::MIGRATION_FLAG, $slug, false );

            return false;
        }

        $legacy = get_option('flexify_checkout_license_response_object');
        $status_option = (string) get_option('flexify_checkout_license_status', '');

        $valid = ( 'valid' === $status_option ) || ( is_object( $legacy ) && ! empty( $legacy->is_valid ) );
        $expires_at = self::normalize_legacy_expiry( is_object( $legacy ) && isset( $legacy->expire_date ) ? $legacy->expire_date : null );

        $status = $valid ? 'active' : 'invalid';

        if ( $expires_at && strtotime( $expires_at ) < time() ) {
            $status = 'expired';
            $valid  = false;
        }

        $extra = array( 'migrated_from_legacy' => true );

        if ( is_object( $legacy ) ) {
            if ( ! empty( $legacy->license_title ) ) {
                $extra['license_title'] = (string) $legacy->license_title;
            }

            if ( ! empty( $legacy->support_end ) ) {
                $extra['support_end'] = (string) $legacy->support_end;
            }

            if ( ! empty( $legacy->renew_link ) ) {
                $extra['renew_link'] = (string) $legacy->renew_link;
            }
        }

        $now = time();

        $state = array(
            'status'          => $status,
            'valid'           => (bool) $valid,
            'domain'          => class_exists('\MeuMouse\MDS\SDK\Support\Environment') ? \MeuMouse\MDS\SDK\Support\Environment::domain() : '',
            'expires_at'      => $expires_at,
            'checked_at'      => $now,
            // Start the grace window fresh so a valid seat survives until the
            // first real heartbeat; keep 0 for invalid so it is not trusted.
            'last_success_at' => $valid ? $now : 0,
            'signed'          => false,
            'message'         => '',
            'extra'           => $extra,
        );

        self::store_option( $key_name, $key );
        self::store_option( $state_name, $state );
        update_option( self::MIGRATION_FLAG, $slug, false );

        if ( class_exists('\MeuMouse\Flexify_Checkout\Core\Logs\Logger') ) {
            \MeuMouse\Flexify_Checkout\Core\Logs\Logger::register_log(
                sprintf( 'MDS: migrated legacy license state for "%s" (valid=%s, expires=%s)', $slug, $valid ? 'yes' : 'no', $expires_at ? $expires_at : 'never' ),
                'INFO'
            );
        }

        return true;
    }


    /**
     * Normalise a legacy expiry string into an ISO-8601 date or null (lifetime).
     *
     * @since 6.0.0
     * @param mixed $raw Legacy expire_date value.
     * @return string|null
     */
    private static function normalize_legacy_expiry( $raw ) {
        if ( empty( $raw ) ) {
            return null;
        }

        $normalized = strtolower( trim( (string) $raw ) );

        if ( in_array( $normalized, array( 'no expiry', 'unlimited', 'lifetime', 'never', 'no expiration' ), true ) ) {
            return null;
        }

        $timestamp = strtotime( (string) $raw );

        return $timestamp ? gmdate( 'c', $timestamp ) : null;
    }


    /**
     * Replicate the SDK's option key naming (Config\Product::key()) without
     * constructing a Product (which requires credentials).
     *
     * @since 6.0.0
     * @param string $slug   Product slug.
     * @param string $suffix Optional suffix.
     * @return string
     */
    private static function option_key( $slug, $suffix = '' ) {
        $base = 'mds_' . preg_replace( '/[^a-z0-9_]/', '_', strtolower( (string) $slug ) );

        return '' === $suffix ? $base : $base . '_' . $suffix;
    }


    /**
     * Read an option the same way the SDK does (site option on multisite).
     *
     * @since 6.0.0
     * @param string $name    Option name.
     * @param mixed  $default Default value.
     * @return mixed
     */
    private static function read_option( $name, $default ) {
        return is_multisite() ? get_site_option( $name, $default ) : get_option( $name, $default );
    }


    /**
     * Write an option the same way the SDK does (site option on multisite,
     * non-autoloaded on single site).
     *
     * @since 6.0.0
     * @param string $name  Option name.
     * @param mixed  $value Value.
     * @return void
     */
    private static function store_option( $name, $value ) {
        if ( is_multisite() ) {
            update_site_option( $name, $value );
        } else {
            update_option( $name, $value, false );
        }
    }
}
