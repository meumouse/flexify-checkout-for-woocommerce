<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Checkout\Headless_Data;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * GET /flexify-checkout/v1/address/autocomplete
 *
 * Server-side proxy to the Google Places API (New) Autocomplete endpoint. The
 * API key stays on the server and never reaches the browser. Only registered
 * when the address search is enabled and a key is configured.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Address_Autocomplete extends Abstract_Route {

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/address/autocomplete';

    /**
     * Argument schema.
     *
     * @since 6.0.0
     * @var array
     */
    protected $args = array(
        'q' => array(
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
        $query = trim( (string) $request->get_param('q') );

        if ( strlen( $query ) < 3 ) {
            return rest_ensure_response( array( 'suggestions' => array() ) );
        }

        $body = array(
            'input' => $query,
            'languageCode' => 'pt-BR',
            'includedRegionCodes' => array( 'br' ),
        );

        $session_token = sanitize_text_field( (string) $request->get_param('session_token') );

        if ( '' !== $session_token ) {
            $body['sessionToken'] = $session_token;
        }

        $response = wp_remote_post( 'https://places.googleapis.com/v1/places:autocomplete', array(
            'timeout' => 10,
            'headers' => array(
                'X-Goog-Api-Key' => (string) Admin_Options::get_setting('google_maps_api_key'),
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( $body ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $this->error_response( __( 'Não foi possível buscar o endereço.', 'flexify-checkout-for-woocommerce' ) );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $suggestions = array();

        if ( isset( $data['suggestions'] ) && is_array( $data['suggestions'] ) ) {
            foreach ( $data['suggestions'] as $suggestion ) {
                $prediction = $suggestion['placePrediction'] ?? array();

                if ( empty( $prediction['placeId'] ) ) {
                    continue;
                }

                $primary = $prediction['structuredFormat']['mainText']['text'] ?? ( $prediction['text']['text'] ?? '' );
                $secondary = $prediction['structuredFormat']['secondaryText']['text'] ?? '';

                if ( '' === trim( (string) $primary ) ) {
                    continue;
                }

                $suggestions[] = array(
                    'place_id' => (string) $prediction['placeId'],
                    'primary' => trim( (string) $primary ),
                    'secondary' => trim( (string) $secondary ),
                );
            }
        }

        return rest_ensure_response( array( 'suggestions' => $suggestions ) );
    }
}
