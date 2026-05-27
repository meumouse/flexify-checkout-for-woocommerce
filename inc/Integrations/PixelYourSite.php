<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with PixelYourSite (Free or PRO).
 *
 * Strategy: PYS owns Meta Purchase and InitiateCheckout (browser + CAPI),
 * Flexify keeps GA4/dataLayer/TikTok/Google Ads and complementary Meta events
 * (AddShippingInfo / AddPaymentInfo) so the same Meta standard event is never
 * emitted by both plugins. Deduplication on Meta side relies on event_name +
 * event_id from a single owner.
 *
 * @since 5.5.2
 * @package MeuMouse\Flexify_Checkout\Integrations
 * @author MeuMouse.com
 */
class PixelYourSite {

    /**
     * Internal Flexify events that overlap with PYS Meta standard events.
     *
     * @since 5.5.2
     * @var array
     */
    const META_OVERLAPPING_EVENTS = array(
        'fc_begin_checkout',
        'fc_purchase',
    );


    /**
     * Construct function.
     *
     * @since 5.5.2
     * @return void
     */
    public function __construct() {
        if ( ! self::is_active() ) {
            return;
        }

        add_filter( 'Flexify_Checkout/Tracking/Routes', array( $this, 'enforce_ownership_on_routes' ), 30, 3 );
        add_filter( 'Flexify_Checkout/Tracking/Is_Destination_Enabled', array( $this, 'block_meta_destination_dispatch' ), 30, 6 );
        add_filter( 'Flexify_Checkout/Assets/Script_Data', array( $this, 'append_script_flags' ), 25 );

        // Compatibility shims so PYS resolves the order context correctly on the
        add_action( 'parse_request', array( $this, 'ensure_order_received_query_var' ), 1 );
        add_filter( 'woocommerce_is_order_received_page', array( $this, 'force_order_received_detection' ), 20 );
        add_filter( 'pys_woo_checkout_order_id', array( $this, 'resolve_order_id_for_pys' ), 20 );
    }


    /**
     * Check if any PixelYourSite plugin (Free or PRO) is active.
     *
     * @since 5.5.2
     * @return bool
     */
    public static function is_active() {
        return defined( 'PYS_VERSION' ) || defined( 'PYS_FREE_VERSION' );
    }


    /**
     * Whether the PYS Meta (Facebook) pixel is enabled with a configured Pixel ID.
     *
     * @since 5.5.2
     * @return bool
     */
    public static function is_pys_meta_enabled() {
        if ( ! self::is_active() ) {
            return false;
        }

        if ( function_exists( 'PYS' ) && function_exists( 'Facebook' ) ) {
            try {
                $facebook = \Facebook();

                if ( is_object( $facebook ) && method_exists( $facebook, 'configured' ) ) {
                    return (bool) $facebook->configured();
                }
            } catch ( \Throwable $e ) {
                // Fall through to option check.
                unset( $e );
            }
        }

        $core = get_option( 'pys_core_settings', array() );

        if ( ! is_array( $core ) ) {
            $core = array();
        }

        $facebook_opts = get_option( 'pys_facebook_settings', array() );

        if ( ! is_array( $facebook_opts ) ) {
            $facebook_opts = array();
        }

        $has_pixel_id = false;

        if ( ! empty( $facebook_opts['pixel_id'] ) ) {
            $has_pixel_id = true;
        }

        if ( ! $has_pixel_id && ! empty( $facebook_opts['facebook_pixel_id'] ) ) {
            $has_pixel_id = true;
        }

        return (bool) apply_filters( 'Flexify_Checkout/Integrations/PixelYourSite/Meta_Enabled', $has_pixel_id, $core, $facebook_opts );
    }


    /**
     * Get the ownership map for Meta standard events that overlap between the two plugins.
     *
     * Returns an array keyed by Flexify internal event name where value is the owner slug:
     * 'pys' (default), 'flexify' or 'none'.
     *
     * @since 5.5.2
     * @return array
     */
    public static function get_ownership_map() {
        $default = array(
            'fc_begin_checkout' => 'pys',
            'fc_purchase' => 'pys',
        );

        $map = apply_filters( 'Flexify_Checkout/Integrations/PixelYourSite/Ownership_Map', $default );

        return is_array( $map ) ? $map : $default;
    }


    /**
     * Force Flexify tracking routes to honor the ownership map for Meta.
     *
     * @since 5.5.2
     * @param array $routes Merged routes.
     * @param array $default Default routes.
     * @param array $destinations Destinations list.
     * @return array
     */
    public function enforce_ownership_on_routes( $routes, $default = array(), $destinations = array() ) {
        if ( ! is_array( $routes ) ) {
            return $routes;
        }

        if ( ! self::is_pys_meta_enabled() ) {
            return $routes;
        }

        $ownership = self::get_ownership_map();

        foreach ( $ownership as $event_name => $owner ) {
            if ( $owner !== 'pys' ) {
                continue;
            }

            if ( ! isset( $routes[ $event_name ] ) || ! is_array( $routes[ $event_name ] ) ) {
                continue;
            }

            $routes[ $event_name ]['meta'] = 'no';
        }

        return $routes;
    }


    /**
     * Belt and suspenders: block any meta dispatch for owned events at the dispatcher level.
     *
     * Even if a third-party filter re-enables the route, this stops the server-side Meta CAPI
     * call from going out for events owned by PYS.
     *
     * @since 5.5.2
     * @param bool $enabled Current enabled flag.
     * @param string $destination Destination key.
     * @param array $settings Tracking settings.
     * @param string $event_name Internal event name.
     * @param array $payload Event payload.
     * @param mixed $router Router instance.
     * @return bool
     */
    public function block_meta_destination_dispatch( $enabled, $destination, $settings, $event_name, $payload, $router = null ) {
        unset( $settings, $payload, $router );

        if ( $destination !== 'meta' || ! $enabled ) {
            return $enabled;
        }

        if ( ! self::is_pys_meta_enabled() ) {
            return $enabled;
        }

        $ownership = self::get_ownership_map();

        if ( isset( $ownership[ $event_name ] ) && $ownership[ $event_name ] === 'pys' ) {
            return false;
        }

        return $enabled;
    }


    /**
     * Get the configured order-received endpoint slug, with default fallback.
     *
     * @since 5.5.3
     * @return string
     */
    protected static function get_order_received_slug() {
        $slug = get_option( 'woocommerce_checkout_order_received_endpoint', 'order-received' );

        if ( ! is_string( $slug ) || $slug === '' ) {
            $slug = 'order-received';
        }

        return trim( $slug, '/' );
    }


    /**
     * Extract the order-received id from the current request URI, if present.
     *
     * Reads the configured endpoint slug so merchants who renamed the endpoint
     * (Settings → Advanced → Checkout endpoints) are still detected.
     *
     * @since 5.5.3
     * @return int Order id, or 0 if not present.
     */
    protected static function extract_order_received_from_uri() {
        $request_uri = ! empty( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';

        if ( $request_uri === '' ) {
            return 0;
        }

        $path = (string) parse_url( $request_uri, PHP_URL_PATH );

        if ( $path === '' ) {
            $path = $request_uri;
        }

        $slug = self::get_order_received_slug();

        if ( $slug === '' ) {
            return 0;
        }

        if ( preg_match( '#/' . preg_quote( $slug, '#' ) . '/(\d+)(?:/|$)#', $path, $matches ) ) {
            return (int) $matches[1];
        }

        return 0;
    }


    /**
     * Ensure `$wp->query_vars['order-received']` is populated as early as possible
     * on order-received URLs.
     *
     * Flexify's SPA template runs through `template_include` → `the_post()` /
     * `the_content()`, which can leave the global query context pointed at the
     * checkout page when PYS reads it at `wp_footer` time. We populate the
     * query var defensively on `parse_request` so any downstream consumer
     * (PYS, third-party trackers) sees the order id even if WC's own routing
     * fails to set it for some reason.
     *
     * @since 5.5.3
     * @param \WP $wp WP request object.
     * @return void
     */
    public function ensure_order_received_query_var( $wp ) {
        if ( ! ( $wp instanceof \WP ) ) {
            return;
        }

        if ( ! empty( $wp->query_vars['order-received'] ) ) {
            return;
        }

        $order_id = self::extract_order_received_from_uri();

        if ( $order_id > 0 ) {
            $wp->query_vars['order-received'] = $order_id;
        }
    }


    /**
     * Force `is_order_received_page()` to return true when the current request
     * is clearly on the order-received endpoint, even if WC's own detection has
     * not yet primed the query vars.
     *
     * This replicates the user-side mu-plugin (`flexify-pys-fix.php`) inside the
     * plugin so customers do not need to maintain a band-aid file.
     *
     * @since 5.5.3
     * @param bool $is_order_received Current value provided by WooCommerce.
     * @return bool
     */
    public function force_order_received_detection( $is_order_received ) {
        if ( $is_order_received ) {
            return true;
        }

        if ( is_admin() || ( function_exists('wp_doing_ajax') && wp_doing_ajax() ) ) {
            return $is_order_received;
        }

        if ( function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received') ) {
            return true;
        }

        global $wp;

        if ( isset( $wp ) && ! empty( $wp->query_vars['order-received'] ) ) {
            return true;
        }

        if ( function_exists('get_query_var') && get_query_var('order-received') ) {
            return true;
        }

        if ( ! empty( $_GET['key'] ) && self::extract_order_received_from_uri() > 0 ) {
            return true;
        }

        return $is_order_received;
    }


    /**
     * Resolve the order id for PixelYourSite when its own detection returns
     * null on the Flexify thank-you page.
     *
     * Tries, in order:
     *   1. The provided value, if any.
     *   2. `$_GET['key']` → `wc_get_order_id_by_order_key()` (matches the URL
     *      Flexify generates on redirect: `?key=wc_order_...`).
     *   3. `$wp->query_vars['order-received']`.
     *   4. Regex match against the request URI using the configured slug.
     *
     * Hooked on the `pys_woo_checkout_order_id` filter exposed by PixelYourSite
     * Pro 12.x.
     *
     * @since 5.5.3
     * @param mixed $order_id Current resolved order id (may be null/0/false).
     * @return mixed Resolved order id or the original value if none could be found.
     */
    public function resolve_order_id_for_pys( $order_id ) {
        $order_id = absint( $order_id );

        if ( $order_id > 0 ) {
            return $order_id;
        }

        if ( ! function_exists('wc_get_order') ) {
            return $order_id;
        }

        if ( ! empty( $_GET['key'] ) ) {
            $order_key = sanitize_key( wp_unslash( $_GET['key'] ) );

            if ( $order_key !== '' && function_exists('wc_get_order_id_by_order_key') ) {
                $resolved = wc_get_order_id_by_order_key( $order_key );

                if ( $resolved ) {
                    return absint( $resolved );
                }
            }
        }

        global $wp;

        if ( isset( $wp ) && ! empty( $wp->query_vars['order-received'] ) ) {
            return absint( $wp->query_vars['order-received'] );
        }

        if ( function_exists('get_query_var') ) {
            $from_query = absint( get_query_var('order-received') );

            if ( $from_query > 0 ) {
                return $from_query;
            }
        }

        $from_uri = self::extract_order_received_from_uri();

        if ( $from_uri > 0 ) {
            return $from_uri;
        }

        return $order_id;
    }


    /**
     * Expose integration flags to the frontend tracking script for observability and
     * for the browser layer to skip its own fbq('track') calls on owned events.
     *
     * @since 5.5.2
     * @param array $params Script data.
     * @return array
     */
    public function append_script_flags( $params ) {
        if ( ! is_array( $params ) ) {
            return $params;
        }

        $params['pixelyoursite_integration'] = array(
            'active' => self::is_active() ? 'yes' : 'no',
            'meta_enabled' => self::is_pys_meta_enabled() ? 'yes' : 'no',
            'ownership' => self::get_ownership_map(),
            'thankyou_compat' => array(
                'order_received_slug' => self::get_order_received_slug(),
                'resolved_order_id' => self::extract_order_received_from_uri(),
                'is_order_received_page' => ( function_exists('is_order_received_page') && is_order_received_page() ) ? 'yes' : 'no',
            ),
        );

        return $params;
    }
}