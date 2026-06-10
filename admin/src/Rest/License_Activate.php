<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Registry;
use MeuMouse\Flexify_Checkout\API\License;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Activate a license key.
 *
 * Mirrors the legacy flexify_checkout_active_license AJAX behavior.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class License_Activate extends Abstract_Route {

    /**
     * Route path for license activation.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/license/activate';

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
        $license_key = isset( $payload['license_key'] ) ? sanitize_text_field( $payload['license_key'] ) : '';

        if ( empty( $license_key ) ) {
            return $this->error_response( __( 'Informe um código de licença.', 'flexify-checkout-for-woocommerce' ) );
        }

        // clear response cache first
        delete_transient('flexify_checkout_api_request_cache');
        delete_transient('flexify_checkout_api_response_cache');
        delete_transient('flexify_checkout_license_status_cached');

        update_option( 'flexify_checkout_license_key', $license_key ) || add_option( 'flexify_checkout_license_key', $license_key );
        update_option( 'flexify_checkout_temp_license_key', $license_key ) || add_option( 'flexify_checkout_temp_license_key', $license_key );

        $message = '';
        $response_obj = new \stdClass();

        // Check on the server if the license is valid and update responses and options
        if ( License::check_license( $license_key, $message, $response_obj, FLEXIFY_CHECKOUT_FILE ) ) {
            if ( $response_obj && $response_obj->is_valid ) {
                update_option( 'flexify_checkout_license_status', 'valid' );
                delete_option('flexify_checkout_temp_license_key');
                delete_option('flexify_checkout_license_expired');
                delete_option('flexify_checkout_alternative_license_activation');
            } else {
                update_option( 'flexify_checkout_license_status', 'invalid' );
            }

            if ( License::is_valid() ) {
                return $this->success_response( array(
                    'message' => __( 'Licença ativada com sucesso. Agora todos os recursos estão ativos!', 'flexify-checkout-for-woocommerce' ),
                    'runtime' => Registry::get_runtime_data(),
                ) );
            }
        }

        return $this->error_response(
            ! empty( $message ) ? $message : __( 'Ocorreu um erro ao ativar sua licença.', 'flexify-checkout-for-woocommerce' ),
            array( 'runtime' => Registry::get_runtime_data() )
        );
    }
}
