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
        );

        return $params;
    }
}