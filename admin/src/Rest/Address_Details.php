<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Checkout\Headless_Data;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * GET /flexify-checkout/v1/address/details
 *
 * Server-side proxy to the Google Places API (New) Place Details endpoint.
 * Resolves a picked place id into the address breakdown (CEP, street, city,
 * state, etc.) used to fill the checkout fields. Key stays server-side.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Address_Details extends Abstract_Route {

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/address/details';

    /**
     * Argument schema.
     *
     * @since 6.0.0
     * @var array
     */
    protected $args = array(
        'place_id' => array(
            'required' => true,
            'type' => 'string',
        ),
        'session_token' => array(
            'required' => false,
            'type' => 'string',
        ),
    );


    /**
     * Only register when the address search is available.
     *
     * @since 6.0.0
     * @return bool
     */
    protected function should_register() {
        return Headless_Data::is_address_search_available();
    }


    /**
     * Public read access guarded by a REST nonce.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return bool
     */
    public function permission( WP_REST_Request $request ) {
        $nonce = $request->get_header('X-WP-Nonce');

        return (bool) wp_verify_nonce( $nonce ? $nonce : (string) $request->get_param('_wpnonce'), 'wp_rest' );
    }


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $place_id = preg_replace( '#^places/#', '', sanitize_text_field( (string) $request->get_param('place_id') ) );

        if ( '' === $place_id ) {
            return $this->error_response( __( 'Invalid address.', 'flexify-checkout-for-woocommerce' ) );
        }

        $url = 'https://places.googleapis.com/v1/places/' . rawurlencode( $place_id );
        $session_token = sanitize_text_field( (string) $request->get_param('session_token') );

        if ( '' !== $session_token ) {
            $url = add_query_arg( 'sessionToken', rawurlencode( $session_token ), $url );
        }

        $response = wp_remote_get( $url, array(
            'timeout' => 10,
            'headers' => array(
                'X-Goog-Api-Key' => (string) Admin_Options::get_setting('google_maps_api_key'),
                'X-Goog-FieldMask' => 'addressComponents,formattedAddress',
                'Accept-Language' => 'pt-BR',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $this->error_response( __( 'Could not get the address.', 'flexify-checkout-for-woocommerce' ) );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $components = isset( $data['addressComponents'] ) && is_array( $data['addressComponents'] ) ? $data['addressComponents'] : array();

        $address = $this->map_components( $components );
        $address['formatted'] = isset( $data['formattedAddress'] ) ? trim( (string) $data['formattedAddress'] ) : '';

        return rest_ensure_response( $address );
    }


    /**
     * Map Google address components to checkout field values.
     *
     * @since 6.0.0
     * @param array<int,array<string,mixed>> $components Address components.
     * @return array<string,string>
     */
    private function map_components( $components ) {
        $get = function( $type, $short = false ) use ( $components ) {
            foreach ( $components as $component ) {
                $types = isset( $component['types'] ) && is_array( $component['types'] ) ? $component['types'] : array();

                if ( in_array( $type, $types, true ) ) {
                    return (string) ( $short ? ( $component['shortText'] ?? $component['longText'] ?? '' ) : ( $component['longText'] ?? $component['shortText'] ?? '' ) );
                }
            }

            return '';
        };

        $postal = preg_replace( '/\D/', '', $get('postal_code') );

        return array(
            'cep' => 8 === strlen( $postal ) ? $postal : '',
            'address_1' => $get('route'),
            'number' => $get('street_number'),
            'neighborhood' => $get('sublocality_level_1') ?: $get('sublocality'),
            'city' => $get('administrative_area_level_2') ?: $get('locality'),
            'state' => $get('administrative_area_level_1', true),
            'country' => $get('country', true),
        );
    }
}
