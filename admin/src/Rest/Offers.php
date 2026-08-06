<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Offers_Store;
use MeuMouse\Flexify_Checkout\API\License;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Checkout offers CRUD endpoints (order bump, upsell, cross-sell, downsell).
 *
 * GET returns the full offers list enriched with product data; POST upserts a
 * single offer; DELETE removes one by id. Managing offers is a Pro feature, so
 * every mutation is gated by an active license on top of the manage_woocommerce
 * capability enforced by the base permission check.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Offers extends Abstract_Route {

    /**
     * Route path for the offers collection.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/offers';


    /**
     * Register the collection (GET/POST) and single-item (DELETE) routes.
     *
     * @since 6.0.0
     * @return void
     */
    public function register_route() {
        register_rest_route( self::REST_NAMESPACE, $this->route, array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_offers' ),
                'permission_callback' => array( $this, 'permission' ),
            ),
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'save_offer' ),
                'permission_callback' => array( $this, 'permission' ),
            ),
        ) );

        register_rest_route( self::REST_NAMESPACE, $this->route . '/(?P<id>[a-zA-Z0-9_]+)', array(
            array(
                'methods' => 'DELETE',
                'callback' => array( $this, 'delete_offer' ),
                'permission_callback' => array( $this, 'permission' ),
            ),
        ) );
    }


    /**
     * The abstract base requires a handle(); the collection GET is the default.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        return $this->get_offers( $request );
    }


    /**
     * Return the full offers list, enriched with product data for the admin.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function get_offers( WP_REST_Request $request ) {
        return $this->success_response( array(
            'offers' => Offers_Store::get_offers_for_client(),
        ) );
    }


    /**
     * Create or update a single offer.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function save_offer( WP_REST_Request $request ) {
        if ( ! License::is_valid() ) {
            return $this->pro_required_response();
        }

        $payload = $request->get_json_params();
        $payload = is_array( $payload ) ? $payload : array();
        $offer = isset( $payload['offer'] ) && is_array( $payload['offer'] ) ? $payload['offer'] : $payload;

        $id = Offers_Store::upsert_offer( $offer );

        if ( false === $id ) {
            return $this->error_response( __( 'Could not save the offer.', 'flexify-checkout-for-woocommerce' ) );
        }

        return $this->success_response( array(
            'id' => $id,
            'message' => __( 'Offer saved.', 'flexify-checkout-for-woocommerce' ),
            'offers' => Offers_Store::get_offers_for_client(),
        ) );
    }


    /**
     * Delete a single offer by id.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function delete_offer( WP_REST_Request $request ) {
        if ( ! License::is_valid() ) {
            return $this->pro_required_response();
        }

        $id = sanitize_text_field( (string) $request->get_param('id') );

        if ( ! Offers_Store::delete_offer( $id ) ) {
            return $this->error_response( __( 'Offer not found.', 'flexify-checkout-for-woocommerce' ) );
        }

        return $this->success_response( array(
            'message' => __( 'Offer removed.', 'flexify-checkout-for-woocommerce' ),
            'offers' => Offers_Store::get_offers_for_client(),
        ) );
    }


    /**
     * Standard "Pro required" error for gated mutations.
     *
     * @since 6.0.0
     * @return \WP_REST_Response
     */
    protected function pro_required_response() {
        return $this->error_response(
            __( 'Managing checkout offers requires an active Pro license.', 'flexify-checkout-for-woocommerce' ),
            array( 'pro_required' => true )
        );
    }
}
