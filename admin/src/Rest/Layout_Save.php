<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Fields_Store;
use MeuMouse\Flexify_Checkout\Admin\Settings\Layout_Store;
use MeuMouse\Flexify_Checkout\API\License;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Persist the checkout builder layout.
 *
 * Returns the synced field map alongside the stored layout so the admin can
 * refresh both the builder and the Fields Manager from a single round-trip.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Layout_Save extends Abstract_Route {

    /**
     * Route path for saving the layout.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/layout';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'POST';


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        if ( ! License::is_valid() ) {
            return $this->error_response( __( 'The checkout builder requires an active Pro license.', 'flexify-checkout-for-woocommerce' ) );
        }

        $payload = $request->get_json_params();
        $layout = isset( $payload['layout'] ) && is_array( $payload['layout'] ) ? $payload['layout'] : null;

        if ( null === $layout || empty( $layout['steps'] ) ) {
            return $this->error_response( __( 'Invalid layout data.', 'flexify-checkout-for-woocommerce' ) );
        }

        Layout_Store::save_layout( $layout );

        $client = Layout_Store::get_layout_for_client();

        return $this->success_response( array(
            'message' => __( 'Checkout builder saved successfully!', 'flexify-checkout-for-woocommerce' ),
            'layout' => array(
                'version' => $client['version'],
                'steps' => $client['steps'],
            ),
            'field_catalog' => $client['field_catalog'],
            'fields' => Fields_Store::get_fields(),
        ) );
    }
}
