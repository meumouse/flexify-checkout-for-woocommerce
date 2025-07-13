<?php

use MeuMouse\Flexify_Checkout\Core\Helpers;

/**
 * Template for empty cart
 *
 * @since 1.0.0
 * @version 3.8.0
 * @package MeuMouse.com
 */

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div class="flexify-empty-cart">
	<div class="flexify-empty-cart__wrap">
		<div class="flexify-empty-cart__icon-border">
			<div class="flexify-empty-cart__icon"></div>
		</div>
		<div class="flexify-empty-cart__text">
			<p><?php esc_html_e( 'Seu carrinho está vazio, visite nossa loja para comprar algum produto.', 'flexify-checkout-for-woocommerce' ); ?></p>
		</div>
		<div class="flexify-empty-cart__button">
			<a class="flexify-button flexify-button--reverse flexify-button--emptycart" href="<?php echo esc_url( Helpers::get_shop_page_url() ); ?>"><?php esc_html_e( 'Retornar à loja', 'flexify-checkout-for-woocommerce' ); ?></a>
		</div>
	</div>
</div>