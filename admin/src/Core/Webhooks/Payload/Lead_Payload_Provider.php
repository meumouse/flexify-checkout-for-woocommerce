<?php

namespace MeuMouse\Flexify_Checkout\Core\Webhooks\Payload;

use MeuMouse\Flexify_Checkout\Core\Webhooks\Webhook_Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Build a lead-collected payload (cart context + sanitized lead data).
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Core\Webhooks\Payload
 * @author MeuMouse.com
 */
class Lead_Payload_Provider implements Payload_Provider {

    /**
     * Build the lead payload.
     *
     * @since 6.0.0
     * @param array $context Expects { cart_id: int, lead: array }.
     * @return array
     */
    public function build( array $context ) {
        $cart_id = isset( $context['cart_id'] ) ? absint( $context['cart_id'] ) : 0;
        $lead = isset( $context['lead'] ) && is_array( $context['lead'] ) ? Webhook_Helpers::sanitize_array( $context['lead'] ) : array();

        return array(
            'cart' => ( new Cart_Payload_Provider() )->build( array( 'cart_id' => $cart_id ) ),
            'lead' => $lead,
        );
    }
}
