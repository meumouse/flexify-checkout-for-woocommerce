<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Registry;
use MeuMouse\Flexify_Checkout\API\License;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Activate a license through the alternative (offline) .key file flow.
 *
 * Mirrors the legacy flexify_checkout_alternative_activation AJAX behavior:
 * accepts an uploaded encrypted .key file, decrypts it, validates the domain
 * and product, then stores the license options so License::is_valid() returns
 * true. Replaces the legacy admin-ajax handler removed with the &legacy=1 UI.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class License_Alternative_Activate extends Abstract_Route {

    /**
     * Route path for the alternative license activation.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/license/alternative-activate';

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
        $files = $request->get_file_params();

        // Ensure the file was uploaded.
        if ( empty( $files['file'] ) ) {
            return $this->error_response( __( 'Erro ao carregar o arquivo. O arquivo não foi enviado.', 'flexify-checkout-for-woocommerce' ) );
        }

        $file = $files['file'];

        // Validate upload error, size (a license key is small) and that the file
        // really came through an HTTP upload.
        if ( ! isset( $file['error'] ) || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 1048576 || ! is_uploaded_file( $file['tmp_name'] ) ) {
            return $this->error_response( __( 'Erro ao carregar o arquivo.', 'flexify-checkout-for-woocommerce' ) );
        }

        // Only accept .key files.
        if ( pathinfo( $file['name'], PATHINFO_EXTENSION ) !== 'key' ) {
            return $this->error_response( __( 'Arquivo inválido. O arquivo deve ser extensão .key', 'flexify-checkout-for-woocommerce' ) );
        }

        $file_content = file_get_contents( $file['tmp_name'] );

        $decrypt_keys = array(
            '49D52DA9137137C0', // original product key
            'B729F2659393EE27', // Clube M
        );

        $decrypted_data = License::decrypt_alternative_license( $file_content, $decrypt_keys );

        if ( $decrypted_data === null ) {
            return $this->error_response( __( 'Não foi possível descriptografar o arquivo de licença.', 'flexify-checkout-for-woocommerce' ) );
        }

        $license_data_array = json_decode( stripslashes( $decrypted_data ) );
        $this_domain = License::get_domain();

        if ( ! $license_data_array ) {
            return $this->error_response( __( 'O arquivo de licença não contém dados válidos.', 'flexify-checkout-for-woocommerce' ) );
        }

        if ( $this_domain !== $license_data_array->site_domain ) {
            return $this->error_response( __( 'O domínio de ativação não é permitido.', 'flexify-checkout-for-woocommerce' ) );
        }

        if ( ! in_array( $license_data_array->selected_product, array( '1', '7' ), true ) ) {
            return $this->error_response( __( 'A licença informada não é permitida para este produto', 'flexify-checkout-for-woocommerce' ) );
        }

        // Drop any cached API state before flipping the license to active.
        delete_transient('flexify_checkout_api_request_cache');
        delete_transient('flexify_checkout_api_response_cache');
        delete_transient('flexify_checkout_license_status_cached');

        $license_object = $license_data_array->license_object;

        // Build the license response object stored on the site.
        $obj = (object) array(
            'license_key' => $license_data_array->license_code,
            'email' => $license_data_array->user_email,
            'domain' => $this_domain,
            'app_version' => FLEXIFY_CHECKOUT_VERSION,
            'product_id' => $license_data_array->selected_product,
            'product_base' => $license_data_array->product_base,
            'is_valid' => $license_object->is_valid,
            'license_title' => $license_object->license_title,
            'expire_date' => $license_object->expire_date,
        );

        update_option( 'flexify_checkout_alternative_license', 'active' );
        update_option( 'flexify_checkout_license_response_object', $obj );
        update_option( 'flexify_checkout_license_key', $obj->license_key );
        update_option( 'flexify_checkout_license_status', 'valid' );

        return $this->success_response( array(
            'message' => __( 'A licença foi ativada com sucesso!', 'flexify-checkout-for-woocommerce' ),
            'runtime' => Registry::get_runtime_data(),
        ) );
    }
}
