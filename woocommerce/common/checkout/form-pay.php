<?php
/**
 * Pay for order form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-pay.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

?>

<form id="order_review" method="post" class='flexify-order-pay'>
	<div class="flexify-common-wrap">
		<div class="flexify-order-pay-header flexify-order-pay-header--mobile">
			<?php
			if ( Flexify_Checkout_Helpers::is_modern_theme() ) {
				Flexify_Checkout_Steps::render_header( false );
			}
			?>
		</div>
		<div class="flexify-common-wrap__wrapper">
			<div class="flexify-common-wrap__content-left">
				<div class="flexify-step">
					<div class="flexify-order-pay-header flexify-order-pay-header--desktop">
						<?php
						if ( Flexify_Checkout_Helpers::is_modern_theme() ) {
							Flexify_Checkout_Steps::render_header( false );
						}
						?>
					</div>
					<h2 class="flexify-heading flexify-heading--order-pay"><?php esc_html_e( 'Pagar pelo pedido', 'flexify-checkout-for-woocommerce' ); ?></h2>
					<div id="order_review">
						<div id="payment">
							<?php if ( $order->needs_payment() ) : ?>
								<ul class="wc_payment_methods payment_methods methods">
									<?php
									if ( ! empty( $available_gateways ) ) {
										foreach ( $available_gateways as $gateway ) {
											wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
										}
									} else {
										echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . esc_html( apply_filters( 'woocommerce_no_available_payment_methods_message', esc_html__( 'Desculpe, parece que não há métodos de pagamento disponíveis para sua localização. Entre em contato conosco se precisar de assistência ou desejar pagar de outra forma.', 'woocommerce' ) ) ) . '</li>';
									}
									?>
								</ul>
							<?php endif; ?>
							<div class="form-row">
								<input type="hidden" name="woocommerce_pay" value="1" />

								<?php wc_get_template( 'checkout/terms.php' ); ?>

								<?php
								/**
								 * Order pay page - before submit button.
								 *
								 * @since 1.0.0
								 */
								do_action( 'woocommerce_pay_order_before_submit' );
								?>

								<footer class="flexify-footer flexify-footer--order-pay">
									<?php
									if ( Flexify_Checkout_Helpers::is_modern_theme() ) {
										?>
										<a class='flexify-step__back' href="<?php echo esc_url( wc_get_account_endpoint_url('orders') ); ?>"><?php esc_html_e( 'Voltar para a conta', 'flexify-checkout-for-woocommerce' ); ?></a>
										<?php
									}
									?>
									<?php echo '<button type="submit" class="button alt" id="place_order" data-text="' . esc_attr( Flexify_Checkout_Helpers::get_order_pay_btn_text( $order ) ) . '" value="' . esc_html__( 'Pagar pelo pedido' ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . wp_kses_post( $order_button_text ) . '</button>'; ?>
								</footer>

								<?php
								/**
								 * Order pay page - after submit button.
								 *
								 * @since 1.0.0
								 */
								do_action( 'woocommerce_pay_order_after_submit' );
								?>

								<?php wp_nonce_field( 'woocommerce-pay', 'woocommerce-pay-nonce' ); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="flexify-common-wrap__content-right">
				<section class="flexify-order-pay-order-review">
					<?php Flexify_Checkout_Thankyou::render_product_details( $order ); ?>
				</section>
			</div>
		</div>
	</div>
</form>