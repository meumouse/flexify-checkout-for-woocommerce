<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Admin\Fonts_Manager;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Create or update a font in the fonts library.
 *
 * Accepts JSON for Google fonts and multipart/form-data for uploaded
 * font files, mirroring the legacy flexify_checkout_save_font behavior.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Fonts_Save extends Abstract_Route {

    /**
     * Route path for saving fonts.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/fonts';

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
        $params = $request->get_json_params();

        if ( empty( $params ) ) {
            $params = $request->get_body_params();
        }

        $font_id = isset( $params['font_id'] ) ? sanitize_key( (string) $params['font_id'] ) : '';
        $font_name = isset( $params['font_name'] ) ? sanitize_text_field( (string) $params['font_name'] ) : '';
        $font_type = isset( $params['font_type'] ) ? sanitize_key( (string) $params['font_type'] ) : 'google';

        if ( empty( $font_id ) || empty( $font_name ) ) {
            return $this->error_response( __( 'Informe um identificador e um nome válidos para a fonte.', 'flexify-checkout-for-woocommerce' ) );
        }

        $fonts = Fonts_Manager::get_fonts();
        $is_new = ! isset( $fonts[ $font_id ] );
        $request_is_new = isset( $params['is_new'] ) ? 'yes' === sanitize_text_field( (string) $params['is_new'] ) : $is_new;

        if ( $is_new && Fonts_Manager::is_builtin_font( $font_id ) ) {
            return $this->error_response( __( 'Este identificador é reservado para as fontes padrão.', 'flexify-checkout-for-woocommerce' ) );
        }

        if ( $request_is_new && isset( $fonts[ $font_id ] ) ) {
            return $this->error_response( __( 'Ops! Essa fonte já existe.', 'flexify-checkout-for-woocommerce' ) );
        }

        $font_data = array(
            'font_name' => $font_name,
            'type' => ( 'upload' === $font_type ) ? 'upload' : 'google',
        );

        if ( 'google' === $font_data['type'] ) {
            $font_url = isset( $params['font_url'] ) ? esc_url_raw( (string) $params['font_url'] ) : '';

            if ( empty( $font_url ) ) {
                return $this->error_response( __( 'Informe a URL de incorporação do Google Fonts.', 'flexify-checkout-for-woocommerce' ) );
            }

            $font_data['font_url'] = $font_url;
        } else {
            $font_weight = isset( $params['font_weight'] ) ? sanitize_text_field( (string) $params['font_weight'] ) : '400';
            $font_style = isset( $params['font_style'] ) ? sanitize_text_field( (string) $params['font_style'] ) : 'normal';
            $existing = isset( $fonts[ $font_id ]['font_files'] ) && is_array( $fonts[ $font_id ]['font_files'] ) ? $fonts[ $font_id ]['font_files'] : array();

            $font_files = array(
                'woff2' => isset( $existing['woff2'] ) ? esc_url_raw( $existing['woff2'] ) : '',
                'woff' => isset( $existing['woff'] ) ? esc_url_raw( $existing['woff'] ) : '',
                'ttf' => isset( $existing['ttf'] ) ? esc_url_raw( $existing['ttf'] ) : '',
            );

            $files = $request->get_file_params();

            if ( ! empty( $files['font_file'] ) && ! empty( $files['font_file']['name'] ) ) {
                $ext = strtolower( pathinfo( $files['font_file']['name'], PATHINFO_EXTENSION ) );
                $allowed = array( 'woff2', 'woff', 'ttf' );

                if ( ! in_array( $ext, $allowed, true ) ) {
                    return $this->error_response( __( 'Extensão inválida. Use WOFF, WOFF2 ou TTF.', 'flexify-checkout-for-woocommerce' ) );
                }

                $upload = Fonts_Manager::handle_font_upload( $files['font_file'], $font_id, $font_weight, $font_style, $ext );

                if ( is_wp_error( $upload ) ) {
                    return $this->error_response( $upload->get_error_message() );
                }

                if ( ! empty( $font_files[ $ext ] ) && $font_files[ $ext ] !== $upload ) {
                    Fonts_Manager::delete_file_by_url( $font_files[ $ext ] );
                }

                $font_files[ $ext ] = $upload;
            }

            if ( empty( $font_files['woff2'] ) && empty( $font_files['woff'] ) && empty( $font_files['ttf'] ) ) {
                return $this->error_response( __( 'Envie ao menos um arquivo de fonte (WOFF, WOFF2 ou TTF).', 'flexify-checkout-for-woocommerce' ) );
            }

            $font_data['font_weight'] = $font_weight;
            $font_data['font_style'] = $font_style;
            $font_data['font_files'] = array_filter( $font_files );
        }

        if ( Fonts_Manager::is_builtin_font( $font_id ) ) {
            $font_data['source'] = 'default';
        } elseif ( isset( $fonts[ $font_id ]['source'] ) ) {
            $font_data['source'] = $fonts[ $font_id ]['source'];
        } else {
            $font_data['source'] = 'custom';
        }

        if ( ! Fonts_Manager::save_font( $font_id, $font_data ) ) {
            return $this->error_response( __( 'Ops! Não foi possível salvar a fonte.', 'flexify-checkout-for-woocommerce' ) );
        }

        return $this->success_response( array(
            'message' => __( 'As configurações da fonte foram salvas com sucesso!', 'flexify-checkout-for-woocommerce' ),
            'fonts' => Fonts_Manager::get_fonts(),
            'current_font' => Admin_Options::get_setting('set_font_family'),
        ) );
    }
}
