<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Rest;

use MeuMouse\Flexify_Checkout\Rest\Abstract_Route;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Cart recovery — full settings endpoint.
 *
 * GET/POST flexify-checkout/v1/recovery/settings. Reads and writes the cart
 * recovery settings surfaced in the "Recuperação" tab of the Vue settings:
 * the common flat options plus the complex editors (follow-up events,
 * payment-method delays, lead modal and webhooks). Replaces the legacy
 * server-rendered settings screen and its admin-ajax save.
 *
 * Saves are merge-safe: the full flexify_checkout_recovery_carts_settings
 * option is read, only the managed keys are overwritten, then it is stored.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Rest
 * @author MeuMouse.com
 */
class Recovery_Settings extends Abstract_Route {

    /**
     * Option name.
     *
     * @since 6.0.0
     * @var string
     */
    const OPTION = 'flexify_checkout_recovery_carts_settings';

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/recovery/settings';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var array
     */
    protected $methods = array( 'GET', 'POST' );

    /**
     * Flat scalar keys managed by this endpoint.
     *
     * @since 6.0.0
     * @var array<int,string>
     */
    const SCALAR_KEYS = array(
        'time_for_lost_carts',
        'time_unit_for_lost_carts',
        'follow_up_purchase_block_days',
        'fallback_first_name',
        'joinotify_sender_phone',
        'joinotify_test_phone',
        'select_coupon',
    );

    /**
     * Nested array keys managed by this endpoint.
     *
     * @since 6.0.0
     * @var array<int,string>
     */
    const ARRAY_KEYS = array(
        'follow_up_events',
        'payment_methods',
        'collect_lead_modal',
    );

    /**
     * Toggle keys (stored under toggle_switchs) managed by this endpoint.
     *
     * @since 6.0.0
     * @var array<int,string>
     */
    const TOGGLE_KEYS = array(
        'enable_cart_recovery',
        'enable_joinotify_integration',
        'enable_email_integration',
        'enable_modal_add_to_cart',
        'enable_international_phone_modal',
        'display_modal_for_logged_users',
        'enable_get_location_from_ip',
    );


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        if ( 'POST' === $request->get_method() ) {
            return $this->save( $request );
        }

        return $this->success_response( array(
            'settings' => $this->read_settings(),
            'support' => $this->support_data(),
        ) );
    }


    /**
     * Read the managed settings from the option.
     *
     * @since 6.0.0
     * @return array
     */
    private function read_settings() {
        $options = get_option( self::OPTION, array() );
        $options = is_array( $options ) ? $options : array();
        $toggles = isset( $options['toggle_switchs'] ) && is_array( $options['toggle_switchs'] ) ? $options['toggle_switchs'] : array();

        $settings = array();

        foreach ( self::SCALAR_KEYS as $key ) {
            $settings[ $key ] = isset( $options[ $key ] ) ? $options[ $key ] : '';
        }

        foreach ( self::ARRAY_KEYS as $key ) {
            $settings[ $key ] = isset( $options[ $key ] ) && is_array( $options[ $key ] ) ? $options[ $key ] : array();
        }

        $settings['toggles'] = array();

        foreach ( self::TOGGLE_KEYS as $key ) {
            $default = ( 'enable_cart_recovery' === $key ) ? 'yes' : 'no';
            $settings['toggles'][ $key ] = isset( $toggles[ $key ] ) ? $toggles[ $key ] : $default;
        }

        return $settings;
    }


    /**
     * Supporting data the editor needs (option lists).
     *
     * @since 6.0.0
     * @return array
     */
    private function support_data() {
        return array(
            'time_units' => array(
                array( 'value' => 'minutes', 'label' => __( 'Minutos', 'flexify-checkout-for-woocommerce' ) ),
                array( 'value' => 'hours', 'label' => __( 'Horas', 'flexify-checkout-for-woocommerce' ) ),
                array( 'value' => 'days', 'label' => __( 'Dias', 'flexify-checkout-for-woocommerce' ) ),
            ),
            'discount_types' => array(
                array( 'value' => 'percent', 'label' => __( 'Percentual (%)', 'flexify-checkout-for-woocommerce' ) ),
                array( 'value' => 'fixed_cart', 'label' => __( 'Valor fixo', 'flexify-checkout-for-woocommerce' ) ),
            ),
            'coupons' => $this->get_coupons(),
            'gateways' => $this->get_gateways(),
        );
    }


    /**
     * Existing WooCommerce coupon codes.
     *
     * @since 6.0.0
     * @return array<int,string>
     */
    private function get_coupons() {
        $posts = get_posts( array(
            'post_type' => 'shop_coupon',
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
        ) );

        $codes = array();

        foreach ( $posts as $id ) {
            $codes[] = get_the_title( $id );
        }

        return $codes;
    }


    /**
     * Active WooCommerce payment gateways (id + title).
     *
     * @since 6.0.0
     * @return array<int,array{id:string,title:string}>
     */
    private function get_gateways() {
        $gateways = array();

        if ( function_exists('WC') && WC()->payment_gateways ) {
            foreach ( WC()->payment_gateways->payment_gateways() as $id => $gateway ) {
                $gateways[] = array(
                    'id' => (string) $id,
                    'title' => wp_strip_all_tags( $gateway->get_title() ),
                );
            }
        }

        return $gateways;
    }


    /**
     * Persist the submitted managed settings (merge-safe).
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    private function save( WP_REST_Request $request ) {
        $input = $request->get_param('settings');
        $input = is_array( $input ) ? $input : array();

        $options = get_option( self::OPTION, array() );
        $options = is_array( $options ) ? $options : array();

        foreach ( self::SCALAR_KEYS as $key ) {
            if ( ! array_key_exists( $key, $input ) ) {
                continue;
            }

            $value = $input[ $key ];
            $options[ $key ] = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
        }

        foreach ( self::ARRAY_KEYS as $key ) {
            if ( isset( $input[ $key ] ) && is_array( $input[ $key ] ) ) {
                $options[ $key ] = Helpers::sanitize_array( $input[ $key ] );
            }
        }

        if ( isset( $input['toggles'] ) && is_array( $input['toggles'] ) ) {
            if ( ! isset( $options['toggle_switchs'] ) || ! is_array( $options['toggle_switchs'] ) ) {
                $options['toggle_switchs'] = array();
            }

            foreach ( self::TOGGLE_KEYS as $key ) {
                if ( array_key_exists( $key, $input['toggles'] ) ) {
                    $options['toggle_switchs'][ $key ] = ( 'yes' === $input['toggles'][ $key ] ) ? 'yes' : 'no';
                }
            }
        }

        update_option( self::OPTION, $options );

        return $this->success_response( array( 'settings' => $this->read_settings() ) );
    }
}
