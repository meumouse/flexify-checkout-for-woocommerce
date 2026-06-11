<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Rest;

use MeuMouse\Flexify_Checkout\Rest\Abstract_Route;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Cart recovery — common settings endpoint.
 *
 * GET/POST flexify-checkout/v1/recovery/settings. Reads and writes the common,
 * flat recovery settings surfaced in the "Recuperação" tab of the Vue settings
 * (master toggle, scheduler, abandonment window, integrations, styles). The
 * complex editors (follow-up events, payment delays, webhooks, lead modal)
 * stay on the advanced screen, whose URL is returned here.
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
        'task_scheduler',
        'time_for_lost_carts',
        'time_unit_for_lost_carts',
        'follow_up_purchase_block_days',
        'fallback_first_name',
        'primary_color',
        'joinotify_sender_phone',
        'joinotify_test_phone',
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
            'time_units' => array(
                array( 'value' => 'minutes', 'label' => __( 'Minutos', 'fc-recovery-carts' ) ),
                array( 'value' => 'hours', 'label' => __( 'Horas', 'fc-recovery-carts' ) ),
                array( 'value' => 'days', 'label' => __( 'Dias', 'fc-recovery-carts' ) ),
            ),
            'advanced_url' => admin_url( 'admin.php?page=fc-recovery-carts-settings' ),
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

        $settings['toggles'] = array();

        foreach ( self::TOGGLE_KEYS as $key ) {
            // Master toggle defaults to on; the others default to their stored value.
            $default = ( 'enable_cart_recovery' === $key ) ? 'yes' : 'no';
            $settings['toggles'][ $key ] = isset( $toggles[ $key ] ) ? $toggles[ $key ] : $default;
        }

        return $settings;
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
