<?php

namespace MeuMouse\Flexify_Checkout\Core\Webhooks\Payload;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Contract for webhook payload builders.
 *
 * A provider turns an event context (order id, cart id, generic data, …) into
 * the serializable array sent in the webhook body.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Core\Webhooks\Payload
 * @author MeuMouse.com
 */
interface Payload_Provider {

    /**
     * Build the payload for the given context.
     *
     * @since 6.0.0
     * @param array $context Event context.
     * @return array
     */
    public function build( array $context );
}
