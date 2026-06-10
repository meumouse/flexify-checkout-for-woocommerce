<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Admin\Fonts_Manager;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Delete a custom font from the fonts library.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Fonts_Delete extends Abstract_Route {

    /**
     * Route path for deleting fonts.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/fonts/delete';

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
        $font_id = isset( $payload['font_id'] ) ? sanitize_key( (string) $payload['font_id'] ) : '';

        if ( empty( $font_id ) ) {
            return $this->error_response( __( 'Fonte inválida informada.', 'flexify-checkout-for-woocommerce' ) );
        }

        if ( Fonts_Manager::is_builtin_font( $font_id ) ) {
            return $this->error_response( __( 'Fontes padrão não podem ser excluídas.', 'flexify-checkout-for-woocommerce' ) );
        }

        $fonts = Fonts_Manager::get_fonts();

        if ( ! isset( $fonts[ $font_id ] ) ) {
            return $this->error_response( __( 'Fonte não encontrada.', 'flexify-checkout-for-woocommerce' ) );
        }

        if ( ! Fonts_Manager::delete_font( $font_id ) ) {
            return $this->error_response( __( 'Ops! Não foi possível remover a fonte.', 'flexify-checkout-for-woocommerce' ) );
        }

        Fonts_Manager::maybe_reset_selected_font( $font_id );

        return $this->success_response( array(
            'message' => __( 'A fonte foi removida da biblioteca.', 'flexify-checkout-for-woocommerce' ),
            'fonts' => Fonts_Manager::get_fonts(),
            'current_font' => Admin_Options::get_setting('set_font_family'),
        ) );
    }
}
