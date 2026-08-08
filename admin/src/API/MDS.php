<?php

namespace MeuMouse\Flexify_Checkout\API;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Core\Logs\Logger;

use MeuMouse\MDS\SDK\SDK;
use MeuMouse\MDS\SDK\Integration;
use MeuMouse\MDS\SDK\License\Manager as License_Manager;
use MeuMouse\MDS\SDK\License\LicenseStatus;
use MeuMouse\MDS\SDK\Support\Environment;

use InvalidArgumentException;
use Throwable;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Facade for the Modular Distribution Service (MDS) PHP SDK.
 *
 * Wires Flexify Checkout into the MDS API for licensing, signed update checks
 * and rollback, replacing the legacy {@see License} (custom AES transport) and
 * {@see Updater} (unsigned static JSON) paths.
 *
 * Credentials are compiled in as class constants so the shipped plugin works
 * out of the box, and every one of them can be overridden by a constant in
 * wp-config.php (handy for staging against a different MDS instance):
 *
 *     define( 'FLEXIFY_CHECKOUT_MDS_API_BASE', 'https://staging.meumouse.com' );
 *     define( 'FLEXIFY_CHECKOUT_MDS_API_KEY', 'mds_test_xxx' );
 *     define( 'FLEXIFY_CHECKOUT_MDS_PUBLIC_KEY', 'BASE64_ED25519_PUBLIC_KEY' );
 *
 * The integration is active whenever it is usable — ext-sodium available, SDK
 * autoloaded and both credentials configured. There is no opt-in flag:
 * {@see self::API_KEY} being empty is what keeps it inert. A site can still fall
 * back to the legacy path with a kill switch, either
 * `define( 'FLEXIFY_CHECKOUT_MDS_SDK', false )` or the option
 * `flexify_checkout_mds_sdk_enabled = 'no'`.
 *
 * Clube M bundle: since SDK 1.1.0 a bundle key is handled by the server, so this
 * plugin registers a single product and always sends its own `product_slug`. A
 * "CM-" key simply validates for it, and the resulting status carries a `bundle`
 * field ({@see self::bundle()}) describing which bundle granted the license.
 *
 * Unlike the reference SDK integration, no `settings_parent` is passed: the
 * plugin ships its own license screen (the Vue SPA at
 * `admin.php?page=flexify-checkout-license`, driven by {@see License}), so the
 * SDK must not auto-register a competing submenu.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\API
 * @author MeuMouse.com
 */
class MDS {

    /**
     * Product slug, must match the product slug registered on MDS.
     *
     * @since 6.0.0
     * @var string
     */
    const PRODUCT_SLUG = 'flexify-checkout-for-woocommerce';

    /**
     * Default MDS API base URL.
     *
     * @since 6.0.0
     * @var string
     */
    const API_BASE_URL = 'https://cloud.meumouse.com';

    /**
     * Public, low-privilege product API key issued by MDS.
     *
     * Scopes: updates:check, licenses:activate, licenses:deactivate. It is meant
     * to be readable inside the distributed plugin; it grants nothing beyond
     * those three operations.
     *
     * Empty until the product is provisioned on the server — while it is empty
     * the whole integration stays inert and the legacy path remains live.
     *
     * @since 6.0.0
     * @var string
     */
    const API_KEY = '';

    /**
     * Base64 ed25519 public key used to verify every signed MDS response.
     *
     * Must match the MDS_SIGNING_PUBLIC_KEY configured on the API. Responses
     * that are unsigned or fail verification are discarded by the SDK. It is a
     * property of the signing server, so every product shares it.
     *
     * @since 6.0.0
     * @var string
     */
    const PUBLIC_KEY = 'fLpjcbSx1ccEDAYjf0BheQDhn9W+iBYaJAxT+eQ0Mac=';

    /**
     * Option that records that the legacy license state has been migrated.
     *
     * @since 6.0.0
     * @var string
     */
    const MIGRATION_FLAG = 'flexify_checkout_mds_state_migrated';

    /**
     * Whether the SDK loader has already been required this request.
     *
     * @since 6.0.0
     * @var bool
     */
    private static $booted = false;

    /**
     * Whether registration already failed this request, so it is not retried
     * (and re-logged) by every accessor.
     *
     * @since 6.0.0
     * @var bool
     */
    private static $register_failed = false;


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
     * Path to the SDK loader installed by Composer.
     *
     * @since 6.0.0
     * @return string
     */
    private static function sdk_loader_path() {
        $base = defined('FLEXIFY_CHECKOUT_PATH') ? FLEXIFY_CHECKOUT_PATH : plugin_dir_path( dirname( dirname( __DIR__ ) ) );

        return $base . 'admin/vendor/meumouse/mds-php-sdk/mds-sdk.php';
    }


    /**
     * Register the product with the SDK once it has booted.
     *
     * @since 6.0.0
     * @return void
     */
    public static function on_sdk_loaded() {
        if ( ! self::is_enabled() ) {
            return;
        }

        if ( ! self::register_product() ) {
            return;
        }

        // Seed the SDK's license state from the legacy options on first run so
        // already-activated customers are not forced to re-activate at cutover.
        self::maybe_migrate_legacy_state();

        // The legacy Updater is skipped while the SDK owns updates, so the
        // auto-update preference has to be honoured from here.
        add_filter( 'auto_update_plugin', array( __CLASS__, 'enable_auto_update' ), 10, 2 );
    }


    /**
     * Tear down the license heartbeat on plugin deactivation.
     *
     * Only this plugin's own integration is shut down: other MeuMouse plugins
     * may share the elected SDK copy, and their schedulers must keep running.
     *
     * @since 6.0.0
     * @return void
     */
    public static function deactivate() {
        if ( ! class_exists( SDK::class ) ) {
            return;
        }

        $integration = SDK::get( self::product_slug() );

        if ( $integration ) {
            $integration->shutdown();
        }
    }


    /**
     * Whether the MDS integration should be used at all.
     *
     * Requires: no kill switch, the SDK facade class present, ext-sodium
     * available for signature verification, and both credentials configured.
     * Any missing piece keeps the legacy path live.
     *
     * @since 6.0.0
     * @return bool
     */
    public static function is_enabled() {
        if ( self::is_disabled_by_switch() ) {
            return false;
        }

        if ( ! self::is_supported() || ! self::is_configured() ) {
            return false;
        }

        /**
         * Filters whether the MDS SDK integration is active.
         *
         * Can only turn the integration off: the result is re-checked against
         * the credentials, so a filter never forces an unusable configuration.
         *
         * @since 6.0.0
         * @param bool $enabled Current value.
         */
        return (bool) apply_filters( 'Flexify_Checkout/MDS/Enabled', true );
    }


    /**
     * Whether a site-level kill switch sends this install back to the legacy path.
     *
     * @since 6.0.0
     * @return bool
     */
    private static function is_disabled_by_switch() {
        if ( defined('FLEXIFY_CHECKOUT_MDS_SDK') ) {
            return ! FLEXIFY_CHECKOUT_MDS_SDK;
        }

        return get_option('flexify_checkout_mds_sdk_enabled') === 'no';
    }


    /**
     * Whether the runtime can talk to MDS at all (SDK autoloaded + ed25519).
     *
     * @since 6.0.0
     * @return bool
     */
    public static function is_supported() {
        return class_exists( SDK::class ) && function_exists('sodium_crypto_sign_verify_detached');
    }


    /**
     * Whether both credentials are present.
     *
     * @since 6.0.0
     * @return bool
     */
    public static function is_configured() {
        $config = self::config();

        return ! empty( $config['api_key'] ) && ! empty( $config['public_key'] );
    }


    /**
     * Whether the site holds a valid, active license through the SDK.
     *
     * @since 6.0.0
     * @return bool
     */
    public static function is_active() {
        $integration = self::integration();

        return $integration ? $integration->is_licensed() : false;
    }


    /**
     * Product slug as MDS knows it.
     *
     * @since 6.0.0
     * @return string
     */
    public static function product_slug() {
        return defined('FLEXIFY_CHECKOUT_SLUG') ? FLEXIFY_CHECKOUT_SLUG : self::PRODUCT_SLUG;
    }


    /**
     * Build the SDK configuration array.
     *
     * Credentials come from the class constants above, and each one can be
     * overridden by a wp-config constant.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public static function config() {
        $config = array(
            'product_slug'    => self::product_slug(),
            'type'            => 'plugin',
            'file'            => self::get_plugin_file(),
            'current_version' => defined('FLEXIFY_CHECKOUT_VERSION') ? FLEXIFY_CHECKOUT_VERSION : '',
            'api_base_url'    => defined('FLEXIFY_CHECKOUT_MDS_API_BASE') ? FLEXIFY_CHECKOUT_MDS_API_BASE : self::API_BASE_URL,
            'api_key'         => defined('FLEXIFY_CHECKOUT_MDS_API_KEY') ? (string) FLEXIFY_CHECKOUT_MDS_API_KEY : self::API_KEY,
            'public_key'      => defined('FLEXIFY_CHECKOUT_MDS_PUBLIC_KEY') ? (string) FLEXIFY_CHECKOUT_MDS_PUBLIC_KEY : self::PUBLIC_KEY,
            'item_name'       => 'Flexify Checkout for WooCommerce',
            'text_domain'     => 'flexify-checkout-for-woocommerce',
            // No settings_parent on purpose: the plugin renders its own license
            // screen, so the SDK must not add a duplicate submenu.
        );

        /**
         * Filters the SDK product configuration before registration.
         *
         * @since 6.0.0
         * @param array $config Product config passed to SDK::register().
         */
        return apply_filters( 'Flexify_Checkout/MDS/Product_Config', $config );
    }


    /**
     * Register the product with the SDK (idempotent).
     *
     * @since 6.0.0
     * @return Integration|null
     */
    public static function register_product() {
        if ( ! class_exists( SDK::class ) || self::$register_failed ) {
            return null;
        }

        $existing = SDK::get( self::product_slug() );

        if ( $existing ) {
            return $existing;
        }

        $config = self::config();

        if ( empty( $config['api_key'] ) || empty( $config['public_key'] ) ) {
            self::$register_failed = true;

            self::log('MDS credentials are missing: updates and licensing are disabled.');

            return null;
        }

        try {
            return SDK::register( $config );
        } catch ( InvalidArgumentException $e ) {
            self::$register_failed = true;

            self::log( 'MDS registration failed: ' . $e->getMessage() );

            return null;
        } catch ( Throwable $e ) {
            self::$register_failed = true;

            self::log( 'MDS SDK register failed: ' . $e->getMessage() );

            return null;
        }
    }


    /**
     * The registered SDK integration, registering it on demand.
     *
     * @since 6.0.0
     * @return Integration|null
     */
    public static function integration() {
        if ( ! class_exists( SDK::class ) ) {
            return null;
        }

        $integration = SDK::get( self::product_slug() );

        return $integration ? $integration : self::register_product();
    }


    /**
     * License manager, if available.
     *
     * @since 6.0.0
     * @return License_Manager|null
     */
    public static function license() {
        $integration = self::integration();

        return $integration ? $integration->license() : null;
    }


    /**
     * Last persisted license status (no network call).
     *
     * @since 6.0.0
     * @return LicenseStatus|null
     */
    public static function status() {
        $manager = self::license();

        return $manager ? $manager->status() : null;
    }


    /**
     * Bundle that granted the current license, when the key is a bundle key.
     *
     * Returned by the server on every validation as an additive field:
     * `array( 'id', 'name', 'slug', 'products' )` — used to show "licensed via
     * Clube M" and what the bundle includes.
     *
     * @since 6.0.0
     * @return array<string,mixed>|null
     */
    public static function bundle() {
        $status = self::status();

        if ( ! $status ) {
            return null;
        }

        $bundle = $status->get('bundle');

        return is_array( $bundle ) && ! empty( $bundle ) ? $bundle : null;
    }


    /**
     * The ed25519 public key used to verify signed server messages (webhooks).
     *
     * @since 6.0.0
     * @return string Base64-encoded public key, or empty string.
     */
    public static function public_key() {
        $config = self::config();

        return isset( $config['public_key'] ) ? (string) $config['public_key'] : '';
    }


    /**
     * URL of the plugin license screen.
     *
     * @since 6.0.0
     * @return string
     */
    public static function get_license_url() {
        return admin_url('admin.php?page=flexify-checkout-license');
    }


    /**
     * Enable WordPress background updates for the plugin when the setting is on.
     *
     * @since 6.0.0
     * @param bool $update Whether to enable auto-update.
     * @param object $item Plugin update object.
     * @return bool
     */
    public static function enable_auto_update( $update, $item ) {
        if ( ! isset( $item->plugin ) || $item->plugin !== self::get_plugin_file() ) {
            return $update;
        }

        if ( ! class_exists( Admin_Options::class ) ) {
            return $update;
        }

        return Admin_Options::get_setting('enable_auto_updates') === 'yes' && self::is_active();
    }


    /**
     * Plugin basename ("flexify-checkout-for-woocommerce/flexify-checkout-for-woocommerce.php").
     *
     * @since 6.0.0
     * @return string
     */
    private static function get_plugin_file() {
        if ( defined('FLEXIFY_CHECKOUT_BASENAME') ) {
            return FLEXIFY_CHECKOUT_BASENAME;
        }

        $slug = self::product_slug();

        return $slug . '/' . $slug . '.php';
    }


    /* ---------------------------------------------------------------------- */
    /* Legacy state migration                                                  */
    /* ---------------------------------------------------------------------- */

    /**
     * Bridge the legacy license options into the SDK's `license_state` so a site
     * that is already licensed stays licensed at cutover — no forced
     * re-activation.
     *
     * Idempotent (guarded by {@see self::MIGRATION_FLAG}) and never clobbers an
     * existing SDK activation unless forced. The seeded state is marked
     * `signed => false` on purpose: the next daily heartbeat replaces it with a
     * genuine signed verdict from the server — which is also what fills in the
     * `bundle` field for a Clube M key.
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

        $slug = self::product_slug();

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

        $status = $valid ? LicenseStatus::STATUS_ACTIVE : LicenseStatus::STATUS_INVALID;

        if ( $expires_at && strtotime( $expires_at ) < time() ) {
            $status = LicenseStatus::STATUS_EXPIRED;
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
            'domain'          => class_exists( Environment::class ) ? Environment::domain() : '',
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

        self::log(
            sprintf( 'MDS: migrated legacy license state (valid=%s, expires=%s)', $valid ? 'yes' : 'no', $expires_at ? $expires_at : 'never' ),
            'info'
        );

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


    /**
     * Log an integration event through the plugin logger, when available.
     *
     * @since 6.0.0
     * @param string $message Message to log.
     * @param string $level Log level ("error" or "info").
     * @return void
     */
    private static function log( $message, $level = 'error' ) {
        if ( ! class_exists( Logger::class ) ) {
            return;
        }

        if ( 'info' === $level ) {
            Logger::info( 'license', $message );

            return;
        }

        Logger::error( 'license', $message );
    }
}
