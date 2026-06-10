<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Fields_Store;
use MeuMouse\Flexify_Checkout\API\License;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Add a new custom checkout field.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Fields_Add extends Abstract_Route {

    /**
     * Route path for adding a checkout field.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/fields/add';

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
            return $this->error_response( __( 'O gerenciador de campos requer uma licença Pro ativa.', 'flexify-checkout-for-woocommerce' ) );
        }

        $payload = $request->get_json_params();
        $data = isset( $payload['field'] ) && is_array( $payload['field'] ) ? $payload['field'] : array();
        $result = Fields_Store::add_field( $data );

        if ( is_wp_error( $result ) ) {
            return $this->error_response( $result->get_error_message() );
        }

        return $this->success_response( array(
            'message' => __( 'Novo campo para finalização de compras adicionado com sucesso!', 'flexify-checkout-for-woocommerce' ),
            'fields' => $result,
        ) );
    }
}
