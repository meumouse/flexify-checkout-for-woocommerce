<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Checkout\Headless_Data;
use MeuMouse\Flexify_Checkout\Checkout\Saved_Addresses;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Customer address book endpoints (Pro).
 *
 * Collection:  GET    /flexify-checkout/v1/customer/addresses
 *              POST   /flexify-checkout/v1/customer/addresses
 * Item:        PUT    /flexify-checkout/v1/customer/addresses/{id}
 *              DELETE /flexify-checkout/v1/customer/addresses/{id}
 *
 * Requires a logged-in user and a valid REST nonce; data is scoped to the
 * authenticated user so a customer can only touch their own addresses.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Customer_Addresses extends Abstract_Route {

    /**
     * Collection route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/customer/addresses';


    /**
     * Only register when the address book is available (Pro + toggle on).
     *
     * @since 6.0.0
     * @return bool
     */
    protected function should_register() {
        return Headless_Data::is_saved_addresses_available();
    }


    /**
     * Register both the collection and the single-item routes.
     *
     * @since 6.0.0
     * @return void
     */
    public function register_route() {
        register_rest_route( self::REST_NAMESPACE, $this->route, array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'handle' ),
                'permission_callback' => array( $this, 'permission' ),
            ),
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'create_item' ),
                'permission_callback' => array( $this, 'permission' ),
            ),
        ) );

        register_rest_route( self::REST_NAMESPACE, $this->route . '/(?P<id>[a-zA-Z0-9\-]+)', array(
            array(
                'methods' => 'PUT, PATCH',
                'callback' => array( $this, 'update_item' ),
                'permission_callback' => array( $this, 'permission' ),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array( $this, 'delete_item' ),
                'permission_callback' => array( $this, 'permission' ),
            ),
        ) );
    }


    /**
     * Permission: authenticated user with a valid REST nonce.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return bool
     */
    public function permission( WP_REST_Request $request ) {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $nonce = $request->get_header('X-WP-Nonce');

        return (bool) wp_verify_nonce( $nonce ? $nonce : (string) $request->get_param('_wpnonce'), 'wp_rest' );
    }


    /**
     * GET the current user's saved addresses.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        return $this->success_response( array(
            'addresses' => Saved_Addresses::get_all( get_current_user_id() ),
        ) );
    }


    /**
     * POST a new saved address.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function create_item( WP_REST_Request $request ) {
        $entry = Saved_Addresses::add( get_current_user_id(), $this->read_payload( $request ) );

        return $this->success_response( array(
            'address' => $entry,
            'addresses' => Saved_Addresses::get_all( get_current_user_id() ),
        ) );
    }


    /**
     * PUT/PATCH update an existing saved address.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function update_item( WP_REST_Request $request ) {
        $id = sanitize_text_field( (string) $request->get_param('id') );
        $entry = Saved_Addresses::update( get_current_user_id(), $id, $this->read_payload( $request ) );

        if ( null === $entry ) {
            return $this->error_response( __( 'Address not found.', 'flexify-checkout-for-woocommerce' ) );
        }

        return $this->success_response( array(
            'address' => $entry,
            'addresses' => Saved_Addresses::get_all( get_current_user_id() ),
        ) );
    }


    /**
     * DELETE a saved address.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function delete_item( WP_REST_Request $request ) {
        $id = sanitize_text_field( (string) $request->get_param('id') );
        $removed = Saved_Addresses::delete( get_current_user_id(), $id );

        if ( ! $removed ) {
            return $this->error_response( __( 'Address not found.', 'flexify-checkout-for-woocommerce' ) );
        }

        return $this->success_response( array(
            'addresses' => Saved_Addresses::get_all( get_current_user_id() ),
        ) );
    }


    /**
     * Extract the address payload from the request body.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return array<string,mixed>
     */
    protected function read_payload( WP_REST_Request $request ) {
        return array(
            'nickname' => $request->get_param('nickname'),
            'billing' => $request->get_param('billing'),
            'extra' => $request->get_param('extra'),
            'is_default' => $request->get_param('is_default'),
        );
    }
}
