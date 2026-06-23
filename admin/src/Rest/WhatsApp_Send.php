<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Checkout\Headless_Data;
use WP_REST_Server;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * POST /flexify-checkout/v1/auth/whatsapp/send
 *
 * Generate a one-time code and deliver it over WhatsApp via Joinotify. Only
 * registered when WhatsApp login is enabled and Joinotify is active. The code
 * is stored hashed in a short-lived transient; the response never leaks it.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class WhatsApp_Send extends Abstract_Route {

    use WhatsApp_Login_Trait;

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/auth/whatsapp/send';

    /**
     * HTTP method.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = WP_REST_Server::CREATABLE;


    /**
     * Only register when WhatsApp login is available.
     *
     * @since 6.0.0
     * @return bool
     */
    protected function should_register() {
        return Headless_Data::is_whatsapp_login_available();
    }


    /**
     * Public access guarded by a REST nonce + rate limiting.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return bool
     */
    public function permission( WP_REST_Request $request ) {
        return $this->verify_rest_nonce( $request );
    }


    /**
     * Argument schema.
     *
     * @since 6.0.0
     * @var array
     */
    protected $args = array(
        'phone' => array(
            'required' => true,
            'type' => 'string',
        ),
    );


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $phone = $this->normalize_phone( (string) $request->get_param('phone') );

        if ( strlen( $phone ) < 10 ) {
            return $this->error_response( __( 'Informe um número de telefone válido.', 'flexify-checkout-for-woocommerce' ) );
        }

        // Rate limit: per IP and per phone.
        if ( $this->is_rate_limited( $phone ) ) {
            return $this->error_response( __( 'Muitas tentativas. Aguarde um momento e tente novamente.', 'flexify-checkout-for-woocommerce' ) );
        }

        $code = (string) wp_rand( 100000, 999999 );

        set_transient( $this->otp_transient_key( $phone ), array(
            'hash' => wp_hash( $code ),
            'attempts' => 0,
        ), 5 * MINUTE_IN_SECONDS );

        $sender = $this->get_sender();
        $receiver = function_exists('joinotify_prepare_receiver') ? joinotify_prepare_receiver( $phone ) : $phone;
        $message = sprintf(
            /* translators: %s: one-time access code. */
            __( 'Seu código de acesso é: %s', 'flexify-checkout-for-woocommerce' ),
            $code
        );

        if ( empty( $sender ) ) {
            return $this->error_response( __( 'Nenhum remetente do WhatsApp configurado.', 'flexify-checkout-for-woocommerce' ) );
        }

        joinotify_send_whatsapp_message_text( $sender, $receiver, $message );

        if ( defined('FLEXIFY_CHECKOUT_DEBUG_MODE') && FLEXIFY_CHECKOUT_DEBUG_MODE ) {
            error_log( '[FLEXIFY CHECKOUT] WhatsApp OTP sent to ' . $receiver );
        }

        return $this->success_response( array(
            'message' => __( 'Código enviado pelo WhatsApp.', 'flexify-checkout-for-woocommerce' ),
            'expires_in' => 5 * MINUTE_IN_SECONDS,
        ) );
    }
}
