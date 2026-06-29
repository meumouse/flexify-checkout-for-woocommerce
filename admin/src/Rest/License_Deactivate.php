<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Registry;
use MeuMouse\Flexify_Checkout\API\License;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Deactivate the current license.
 *
 * Mirrors the legacy flexify_checkout_deactive_license AJAX behavior.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class License_Deactivate extends Abstract_Route {

    /**
     * Route path for license deactivation.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/license/deactivate';

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
        $message = '';
        $deactivation = License::deactive_license( FLEXIFY_CHECKOUT_FILE, $message );

        if ( ! $deactivation ) {
            return $this->error_response( __( 'An error occurred while deactivating your license.', 'flexify-checkout-for-woocommerce' ) );
        }

        delete_option('flexify_checkout_license_key');
        delete_option('flexify_checkout_license_response_object');
        delete_option('flexify_checkout_temp_license_key');
        delete_option('flexify_checkout_alternative_license');
        delete_option('flexify_checkout_alternative_license_activation');
        delete_option('flexify_checkout_alternative_license_decrypted');
        delete_transient('flexify_checkout_license_status_cached');
        delete_transient('flexify_checkout_api_request_cache');
        delete_transient('flexify_checkout_api_response_cache');

        return $this->success_response( array(
            'message' => __( 'The license has been deactivated. All Pro version features are now disabled!', 'flexify-checkout-for-woocommerce' ),
            'runtime' => Registry::get_runtime_data(),
        ) );
    }
}
