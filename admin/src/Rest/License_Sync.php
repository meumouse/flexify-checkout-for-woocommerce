<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Registry;
use MeuMouse\Flexify_Checkout\API\License;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Sync license status with the remote license server.
 *
 * Mirrors the legacy flexify_checkout_sync_license AJAX behavior.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class License_Sync extends Abstract_Route {

    /**
     * Route path for license sync.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/license/sync';

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
        $api_url = 'https://api.meumouse.com/wp-json/license/license/view';

        // send request
        $response = wp_remote_post( $api_url, array(
            'body' => array(
                'api_key' => '315D36C6-0C80F95B-3CAC4C7C-6BE7D8E0',
                'license_code' => get_option( 'flexify_checkout_license_key', '' ),
            ),
            'timeout' => 30,
        ));

        if ( is_wp_error( $response ) ) {
            error_log( '[FLEXIFY CHECKOUT] Error on sync licence: ' . print_r( $response, true ) );

            return $this->error_response( __( 'Could not communicate with the license server.', 'flexify-checkout-for-woocommerce' ) );
        }

        $response_body = wp_remote_retrieve_body( $response );
        $response_code = wp_remote_retrieve_response_code( $response );
        $details = json_decode( $response_body );

        if ( 200 !== $response_code || ! $details || ! isset( $details->data ) ) {
            return $this->error_response( __( 'Invalid response from the license server.', 'flexify-checkout-for-woocommerce' ) );
        }

        update_option( 'flexify_checkout_license_info', $details );

        $data = $details->data;

        $obj = new \stdClass();
        $obj->is_valid = ( $data->status === 'A' );
        $obj->expire_date = isset( $data->expiry_time ) ? $data->expiry_time : '';
        $obj->license_title = isset( $data->license_title ) ? $data->license_title : '';
        $obj->app_version = FLEXIFY_CHECKOUT_VERSION;
        $obj->domain = License::get_domain();
        $obj->license_key = $data->purchase_key;
        $obj->product_id = $data->product_id;
        $obj->product_base_name = $data->product_base_name;

        update_option( 'flexify_checkout_license_response_object', $obj );
        update_option( 'flexify_checkout_license_status', $obj->is_valid ? 'valid' : 'invalid' );

        if ( ! empty( $obj->expire_date ) && $obj->expire_date !== 'No expiry' ) {
            License::schedule_license_expiration_check( strtotime( $obj->expire_date ) );
        }

        delete_transient('flexify_checkout_license_status_cached');

        return $this->success_response( array(
            'message' => __( 'License synced successfully.', 'flexify-checkout-for-woocommerce' ),
            'runtime' => Registry::get_runtime_data(),
        ) );
    }
}
