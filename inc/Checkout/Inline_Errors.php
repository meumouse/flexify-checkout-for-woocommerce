<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Core\Helpers;
use MeuMouse\Flexify_Checkout\Validations\Utils;

use WC_Validation;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handle with checkout fields inline errors
 *
 * @since 5.0.0
 * @package MeuMouse.com
 */
class Inline_Errors {

    /**
     * Build the response for a single field
     * 
     * @since 1.0.0
     * @version 5.0.0
     * @param string $field_id | The field ID
     */
    public static function render_field_error( $field_id, $key, $args, $value, $country ) {
        if ( defined('DOING_AJAX') && DOING_AJAX ) {
            $key = $field_id;
            $args = wp_parse_args( $args, array() );
            $value = sanitize_text_field( $value );
            $country = sanitize_text_field( $country );
        }

        // validate fields
        $result = self::validate_field( $key, $args, $value, $country );

        // if is AJAX request, return array
        if ( defined('DOING_AJAX') && in_array( filter_input( INPUT_POST, 'action' ), array( 'flexify_check_for_inline_errors' ), true ) ) {
            return self::prepare_response( $result, $key, $value );
        }

        // inject HTML inline and attributes
        return self::inject_error_html( $field_id, $result );
    }


    /**
     * Apply each validation rule until an error is found
     * 
     * @since 1.0.0
     * @version 5.0.0
     * @param string $key | Field ID
     * @param array $args | Field arguments
     * @param string $value | Field value
     * @param string $country | Country code
     * @return array | Validation result
     */
    public static function validate_field( $key, $args, $value, $country ) {
        $message = '';
        $message_type = 'error';
        $is_custom = false;
        $global = false;

        // required field
        if ( ! empty( $args['required'] ) && '' === trim( $value ) ) {
            $message = sprintf( __( '%s é um campo obrigatório.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
        }

        // country code
        if ( empty( $message ) && 'country' === $args['type'] ) {
            if ( ! WC()->countries->country_exists( $value ) ) {
                $message = sprintf( __( "'%s' não é um código de país válido.", 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
                $is_custom = true;
            }
        }

        // postcode validation
        if ( empty( $message ) && 'billing_postcode' === $key ) {
            if ( ! WC_Validation::is_postcode( $value, $country ) ) {
                $message = ( 'IE' === $country )
                    ? sprintf( __( '%1$s não é válido. Procure o Eircode <a href="%2$s" target="_blank">aqui</a>.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ), 'https://finder.eircode.ie' )
                    : sprintf( __( '%s não é um código postal válido.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
                $is_custom = true;
            }
        }

        // phone validation
        if ( empty( $message ) && in_array( $args['type'], [ 'phone' ], true ) ) {
            if ( ! WC_Validation::is_phone( $value ) ) {
                $message = sprintf( __( '%s não é um número de telefone válido.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
                $is_custom = true;
            }
        }

        // CPF validation
        if ( empty( $message ) && strpos( $key, 'cpf' ) !== false ) {
            if ( ! Utils::validate_cpf( $value ) ) {
                $message = sprintf( __( 'O %s informado não é válido.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
                $is_custom = true;
            }
        }

        // CNPJ validation
        if ( empty( $message ) && strpos( $key, 'cnpj' ) !== false ) {
            if ( ! Utils::validate_cnpj( $value ) ) {
                $message = sprintf( __( 'O %s informado não é válido.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
                $is_custom = true;
            }
        }

        // email validation
        if ( empty( $message ) && 'email' === $args['type'] ) {
            if ( ! is_email( $value ) ) {
                $message = sprintf( __( '%s não é um endereço de e-mail válido.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
                $is_custom = true;
            } elseif ( ! is_user_logged_in() && email_exists( $value ) ) {
                // check if email already exists
                $message = apply_filters( 'flexify_woocommerce_registration_error_email_exists',
                    __( 'Uma conta já está registrada com este endereço de e-mail. <a href="#" data-login>Deseja entrar?</a>', 'flexify-checkout-for-woocommerce' )
                );

                $message_type = 'info';
            }
        }

        // final filters
        $message = apply_filters( 'flexify_custom_inline_message', $message, $key, $args, $value, $country );
        $global = apply_filters( 'flexify_custom_global_message', $global, $key, $args, $value, $country );

        return compact( 'message', 'message_type', 'is_custom', 'global' );
    }


    /**
     * Prepare response array for AJAX request
     * 
     * @since 1.0.0
     * @version 5.0.0
     * @param array $result | Validation array
     * @param string $key | Field ID
     * @param string $value | Field value
     */
    public static function prepare_response( $result, $key, $value ) {
        return array(
            'message' => $result['message'],
            'isCustom' => $result['is_custom'],
            'globalMessage' => $result['global'],
            'messageType' => $result['message_type'],
            'input_value' => $value,
            'input_id' => $key,
        );
    }


    /**
     * Inject HTML error on field
     * 
     * @since 1.0.0
     * @version 5.0.0
     * @param string $field_html | Field ID
     * @param array $result | Validation array
     * @return string
     */
    public static function inject_error_html( $field_html, $result ) {
        if ( ! $result['message'] ) {
            return $field_html;
        }

        // add attribute 'data-flexify-error'
        $field_html = str_replace( '<p ', '<p data-flexify-error="1" ', $field_html );

        // replace field with error message
        $error = '<span class="error">' . esc_html( $result['message'] ) . '</span>';
        $field_html = preg_replace( '/<\/p>/', $error . '</p>', $field_html, 1 );

        return $field_html;
    }
}