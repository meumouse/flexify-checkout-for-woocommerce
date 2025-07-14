<?php

use MeuMouse\Flexify_Checkout\Checkout\Steps;
use MeuMouse\Flexify_Checkout\Checkout\Thankyou;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package MeuMouse.com
 * @since 1.0.0
 * @version 5.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( empty( $order ) ) {
    // Se você estiver usando pretty permalinks
    $order_id = absint( get_query_var( 'order-received' ) );

    // Ou, se preferir, use $_GET:
    if ( ! $order_id && isset( $_GET['order-received'] ) ) {
        $order_id = absint( $_GET['order-received'] );
    }

    $order = $order_id ? wc_get_order( $order_id ) : false;
}

// Se ainda não tiver um WC_Order válido, aborta a renderização
if ( ! $order instanceof WC_Order ) :
    return;
endif; ?>

<div class="flexify-common-wrap">
	<?php 
		Steps::render_header();
		Thankyou::render_status( $order );

		do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
		do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

	<div class="flexify-common-wrap__wrapper">
		<div class="flexify-common-wrap__content-left">
			<?php Thankyou::left_column( $order ); ?>
		</div>
		<div class="flexify-common-wrap__content-right">
			<section class="flexify-ty-order-review">
				<?php Thankyou::render_product_details( $order ); ?>
			</section>
		</div>
	</div>
</div>