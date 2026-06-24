<?php

namespace MeuMouse\Flexify_Checkout\Core\Webhooks\Payload;

use MeuMouse\Flexify_Checkout\Core\Webhooks\Webhook_Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Build a generic data payload for events without a richer model
 * (countdown expiration, WhatsApp verification, browser tracking events).
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Core\Webhooks\Payload
 * @author MeuMouse.com
 */
class Generic_Payload_Provider implements Payload_Provider {

    /**
     * Build the generic payload.
     *
     * @since 6.0.0
     * @param array $context Expects { data: array }.
     * @return array
     */
    public function build( array $context ) {
        $data = isset( $context['data'] ) && is_array( $context['data'] ) ? Webhook_Helpers::sanitize_array( $context['data'] ) : array();

        return array(
            'data' => $data,
        );
    }
}
