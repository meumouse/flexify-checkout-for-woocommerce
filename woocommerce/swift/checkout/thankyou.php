<?php

use MeuMouse\Flexify_Checkout\Checkout\Steps;
use MeuMouse\Flexify_Checkout\Checkout\Thankyou;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Thankyou page (Swift theme).
 *
 * Server-rendered order-received page that mirrors the React (Swift) checkout
 * aesthetic. The React app redirects here after placing the order; this is the
 * legacy/classic render path kept for the thank-you endpoint.
 *
 * This template can be overridden by copying it to
 * yourtheme/woocommerce/checkout/thankyou.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package MeuMouse.com
 * @since 6.0.0
 * @version 6.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( empty( $order ) ) {
	$order_id = absint( get_query_var( 'order-received' ) );

	if ( ! $order_id && isset( $_GET['order-received'] ) ) {
		$order_id = absint( $_GET['order-received'] );
	}

	$order = $order_id ? wc_get_order( $order_id ) : false;
}

// Abort the render if there is no valid WC_Order.
if ( ! $order instanceof WC_Order ) :
	return;
endif; ?>

<div class="flexify-common-wrap flexify-common-wrap--swift">
	<?php
		Steps::render_header( false );

		do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
		do_action( 'woocommerce_thankyou', $order->get_id() );

		Thankyou::render_swift( $order );
	?>
</div>
