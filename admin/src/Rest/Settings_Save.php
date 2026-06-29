<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Repository;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Persist the admin settings payload.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Settings_Save extends Abstract_Route {

    /**
     * Route path for saving settings.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/settings';

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
        $payload = $request->get_json_params();
        $settings = isset( $payload['settings'] ) && is_array( $payload['settings'] ) ? $payload['settings'] : array();

        if ( empty( $settings ) ) {
            return $this->error_response( __( 'No settings received.', 'flexify-checkout-for-woocommerce' ) );
        }

        $saved = Repository::save_settings( $settings );

        return $this->success_response( array(
            'message' => __( 'The settings have been saved.', 'flexify-checkout-for-woocommerce' ),
            'settings' => $saved,
        ) );
    }
}
