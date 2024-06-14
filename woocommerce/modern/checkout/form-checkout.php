<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package MeuMouse.com
 * @version 1.0.0
 */

/**
 * Allow changing the default status of the order summary on mobile.
 *
 * @since 1.0.0
 */
$mobile_show_summary = apply_filters( 'flexify_checkout_mobile_order_summary_open', false );

/**
 * Before the checkout form.
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) :
	/**
	 * Message to indicate logging in is required for checkout.
	 *
	 * @since 1.0.0
	 */
	$message = apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'Você precisa estar logado para finalizar sua compra.', 'flexify-checkout-for-woocommerce' ) );
	$message = sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', esc_url( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ), __( 'Entrar', 'flexify-checkout-for-woocommerce' ), $message ); ?>

	<div class="woocommerce-error"><?php echo wp_kses_post( $message ); ?></div>
	<?php
	return;
endif; ?>

<button type="button" class="flexify-checkout__sidebar-header">
	<div class="flexify-checkout__sidebar-header-inner">
		<span class="flexify-checkout__sidebar-header-link">
			<svg class="flexify-checkout-heading-count-icon" viewBox="0 0 24 24" fill="none"><path d="M3.86376 16.4552C3.00581 13.0234 2.57684 11.3075 3.47767 10.1538C4.3785 9 6.14721 9 9.68462 9H14.3153C17.8527 9 19.6214 9 20.5222 10.1538C21.4231 11.3075 20.9941 13.0234 20.1362 16.4552C19.5905 18.6379 19.3176 19.7292 18.5039 20.3646C17.6901 21 16.5652 21 14.3153 21H9.68462C7.43476 21 6.30983 21 5.49605 20.3646C4.68227 19.7292 4.40943 18.6379 3.86376 16.4552Z" stroke="#1C274C" stroke-width="1.5"></path><path d="M19.5 9.5L18.7896 6.89465C18.5157 5.89005 18.3787 5.38775 18.0978 5.00946C17.818 4.63273 17.4378 4.34234 17.0008 4.17152C16.5619 4 16.0413 4 15 4M4.5 9.5L5.2104 6.89465C5.48432 5.89005 5.62128 5.38775 5.90221 5.00946C6.18199 4.63273 6.56216 4.34234 6.99922 4.17152C7.43808 4 7.95872 4 9 4" stroke="#1C274C" stroke-width="1.5"></path><path d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4C15 4.55228 14.5523 5 14 5H10C9.44772 5 9 4.55228 9 4Z" stroke="#1C274C" stroke-width="1.5"></path><path d="M8 13V17" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M16 13V17" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M12 13V17" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
			<span class="flexify-checkout__sidebar-header-link--show" style="<?php echo $mobile_show_summary ? '' : 'display:none'; ?>"><?php esc_html_e( 'Mostrar resumo do pedido', 'flexify-checkout-for-woocommerce' ); ?></span>
			<span class="flexify-checkout__sidebar-header-link--hide" style="<?php echo $mobile_show_summary ? 'display:none' : ''; ?>"><?php esc_html_e( 'Ocultar resumo do pedido', 'flexify-checkout-for-woocommerce' ); ?></span>
		</span>
		<span class="flexify-checkout__sidebar-header-total"><?php wc_cart_totals_order_total_html(); ?></span>
	</div>
</button>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
	<div class="flexify-checkout__content-wrapper">
		<div class="flexify-checkout__content-left">
			<?php
			/**
			 * Content left start
			 *
			 * @since 1.0.0
			 */
			do_action( 'flexify_checkout_content_left_start', $checkout );

			Flexify_Checkout_Steps::render_header(); ?>

			<div class="flexify-checkout__steps">
				<?php Flexify_Checkout_Steps::render_steps(); ?>
			</div>

			<?php
			/**
			 * Content left start
			 *
			 * @since 1.0.0
			 */
			do_action( 'flexify_checkout_content_left_end', $checkout ); ?>
		</div>
		<?php if ( Flexify_Checkout_Sidebar::is_sidebar_enabled() ) : ?>
			<div class="flexify-checkout__content-right">
				<?php
				/**
				 * Content right start.
				 *
				 * @since 1.0.0
				 */
				do_action( 'flexify_checkout_content_right_start', $checkout ); ?>

				<section class="flexify-checkout__order-review">
					<?php wc_get_template('checkout/cart-heading.php'); ?>

					<div id="order_review" class="woocommerce-checkout-review-order">
						<?php
						/**
						 * Order review
						 *
						 * @since 1.0.0
						 */
						do_action( 'flexify_checkout_order_review', $checkout ); ?>
					</div>
				</section>

				<?php
				/**
				 * Content right end
				 *
				 * @since 1.0.0
				 */
				do_action( 'flexify_checkout_content_right_end', $checkout ); ?>
			</div>
		<?php endif; ?>
	</div>
</form>

<?php
/**
 * After the checkout form
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_after_checkout_form', $checkout );