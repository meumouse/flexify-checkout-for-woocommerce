<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Core\Helpers;
use MeuMouse\Flexify_Checkout\Admin\Orders;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Functions related to the Thank you page
 *
 * @since 1.0.0
 * @version 5.2.0
 * @package MeuMouse.com
 */
class Thankyou {

	/**
	 * Left column of the Thank you page.
	 *
	 * @since 1.0.0
	 * @version 2.0.0
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public static function left_column( $order ) {
		if ( empty( $order ) ) {
			return;
		}

		self::render_customer_details( $order );
		self::downloads( $order );
		self::contact_us( $order );
	}

	/**
	 * Render Status section.
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @param object $order | Order object
	 * @return void
	 */
	public static function render_status( $order ) {
		if ( ! ( $order instanceof \WC_Order ) ) {
			return;
		}

		/**
		 * Thank you page: Before order status
		 *
		 * @since 1.0.0
		 */
		do_action( 'flexify_checkout_thankyou_before_order_status', $order ); ?>

		<div class="flexify-ty-status">
			<svg class="flexify-checkout-check-icon-thankyou" viewBox="0 0 24 24" fill="none"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round"></g><g> <circle cx="12" cy="12" r="10" stroke="#ffffff" stroke-width="1.5"></circle> <path d="M8.5 12.5L10.5 14.5L15.5 9.5" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
			<div class="flexify-ty-status__left">
				<?php

				// compability with WooCommerce Sequential Order Numbers Pro
				if ( class_exists('WC_Sequential_Order_Numbers_Pro_Loader') ) {
					$order_id = $order->get_order_number();
				} else {
					$order_id = $order->get_id();
				}

				?>
				<p><?php echo sprintf( esc_html__( 'Pedido #%s', 'flexify-checkout-for-woocommerce' ), esc_html( $order_id ) ); ?></p>
				<h1><?php echo sprintf( esc_html__( 'Obrigado, %s!', 'flexify-checkout-for-woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></h1>	
			</div>
		</div>
		<?php

		/**
		 * Thank you page: After order status.
		 *
		 * @since 1.0.0
		 */
		do_action( 'flexify_checkout_thankyou_after_order_status', $order );
	}


	/**
	 * Content box.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	public static function render_content_box( $order ) {
		$settings = Core_Settings::$settings;

		/**
		 * Thank you page content.
		 *
		 * @since 1.0.0
		 */
		$content = apply_filters( 'flexify_thankyou_content', $settings['thankyou_thankyou_content'], $order );

		if ( empty( trim( $content ) ) ) {
			return;
		}

		global $allowedposttags;

		$allowed_tags = array_merge(
			$allowedposttags,
			array(
				'iframe' => array(
					'title' => true,
					'frameborder' => true,
					'src' => true,
					'allow' => true,
					'allowfullscreen' => true,
					'width' => true,
					'height' => true,
				),
				'form' => array(
					'id' => true,
					'class' => true,
					'action' => true,
					'method' => true,
					'enctype' => true,
					'novalidate' => true,
					'data-options' => true,
				),
				'input' => array(
					'type' => true,
					'name' => true,
					'id' => true,
				),
			)
		);

		?>
		<div class="flexify-ty-content flexify-ty-box flexify-ty-box--content">
		<?php
			/**
			 * The content filter for things like oEmbed, capital_P_dangit etc.
			 *
			 * @since 1.0.0
			 */
			echo wp_kses( apply_filters( 'the_content', wpautop( $content ) ), $allowed_tags );

			/**
			 * Hook: after thank you page content.
			 *
			 * @since 1.0.0
			 */
			do_action( 'flexify_checkout_thankyou_after_content' );
		?>
		</div>
		<?php
	}

	/**
	 * Customer details box
	 *
	 * @since 1.0.0
	 * @version 3.9.8
	 * @param object $order | Object WC_Order
	 * @return void
	 */
	public static function render_customer_details( $order ) {
		/**
		 * Thank you page: Before customer details
		 *
		 * @since 1.0.0
		 */
		do_action( 'flexify_thankyou_before_customer_details', $order ); ?>

		<div class="flexify-review-customer flexify-review-customer--ty">
			<div class="flexify-review-customer__row flexify-review-customer__row--contact">
				<div class='flexify-review-customer__label'><label><?php esc_html_e( 'Contato', 'flexify-checkout-for-woocommerce' ); ?></label></div>
				
				<div class='flexify-review-customer__content'>
					<p><?php echo Steps::replace_placeholders( Admin_Options::get_setting('text_contact_customer_review'), Orders::get_order_customer_fragment( $order ), 'billing' ); ?> </p>
				</div>
			</div>

			<?php 
			// Check if shipping address is different from billing
			$ship_different = get_post_meta( $order->get_id(), '_flexify_ship_different_address', true ) === 'yes';
			$address_prefix = $ship_different ? 'shipping' : 'billing';

			if ( Admin_Options::get_setting('enable_optimize_for_digital_products') !== 'yes' || order_has_shipping_method( $order ) ) : ?>
				<div class="flexify-review-customer__row flexify-review-customer__row--address">
					<div class='flexify-review-customer__label'>
						<label><?php esc_html_e( 'Entrega', 'flexify-checkout-for-woocommerce' ); ?></label>
					</div>

					<div class='flexify-review-customer__content'>
						<p><?php echo Steps::replace_placeholders( Admin_Options::get_setting('text_shipping_customer_review'), Orders::get_order_customer_fragment( $order ), $address_prefix ); ?><p>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( order_has_shipping_method( $order ) ) : ?>
				<div class="flexify-review-customer__row flexify-review-customer__row--shipping-address">
					<div class='flexify-review-customer__label'><label><?php esc_html_e( 'Envio', 'flexify-checkout-for-woocommerce' ); ?></label></div>
					
					<div class='flexify-review-customer__content'>
						<p><?php echo Orders::get_order_shipping_methods( $order ); ?><p>
					</div>
				</div>
			<?php endif; ?>

			<div class="flexify-review-customer__row">
				<div class='flexify-review-customer__label'><label><?php esc_html_e( 'Pagamento', 'flexify-checkout-for-woocommerce' ); ?></label></div>
				
				<div class='flexify-review-customer__content'>
					<p><?php echo __( $order->get_payment_method_title() ); ?></p>
				</div>
			</div>

			<?php
			/**
			 * After Customer detail rows.
			 *
			 * @since 1.0.0
			 */
			do_action( 'flexify_thankyou_after_customer_details_payment_row', $order ); ?>
		</div>

		<?php
		/**
		 * Thank you page: After customer details.
		 *
		 * @since 1.0.0
		 */
		do_action( 'flexify_thankyou_after_customer_details', $order );
	}


	/**
	 * Render Product details.
	 *
	 * @since 1.0.0
	 * @version 3.8.0
	 * @param object $order | Object WC_Order
	 * @return void
	 */
	public static function render_product_details( $order ) {
		if ( empty( $order ) ) {
			return;
		}

		$order_items = $order->get_items( 'line_item' );

		/**
		 * Thank you page: Before Product details.
		 *
		 * @since 1.0.0
		 */
		do_action( 'flexify_thankyou_before_product_details', $order ); ?>

		<div class="flexify-ty-product-details">
			<div class="flexify-cart-order-item-wrap">
				<?php
				foreach ( $order_items as $item_id => $item ) {
					$product = $item->get_product();
					$product = $item->get_product();
					$qty = $item->get_quantity();
					$refunded_qty = $order->get_qty_refunded_for_item( $item_id );

					if ( $refunded_qty ) {
						$qty_display = '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * -1 ) ) . '</ins>';
					} else {
						$qty_display = esc_html( $qty );
					}

					/**
					 * Order item class.
					 *
					 * @since 1.0.0
					 */
					$item_class = apply_filters( 'flexify_order_items_class', 'flexify-cart-order-item flexify-cart-order-item--ty', $item, $order ); ?>

					<div class="<?php echo esc_attr( $item_class ); ?>">
						<div class="flexify-cart-image flexify-cart-image--ty">
							<?php
							$product_image = $product->get_image();

							if ( $product_image !== null ) {
								echo wp_kses_post( $product_image );
							}
							?>
						</div>
						<div class="flexify-cart-order-item__info">
							<h3 class="flexify-cart-order-item__info-name">
								<?php echo esc_html( $product->get_name() ); ?>
							</h3>
							<span class="flexify-cart-order-item__info-varient">
								<?php
								/**
								 * Thank you page: Order item meta start.
								 *
								 * @since 1.0.0
								 */
								do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false );

								if ( $item !== null ) {
									echo wc_display_item_meta( $item );
								}

								/**
								 * Thank you page: Order item meta end.
								 *
								 * @since 1.0.0
								 */
								do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false ); ?>
							</span>
							<div class="flexify-cart-order-item__info-qty">
								<?php
								/**
								 * Order item quantity HTML.
								 *
								 * @since 1.0.0
								 */
								echo wp_kses_post( apply_filters( 'woocommerce_order_item_quantity_html', ' <strong class="product-quantity">' . sprintf( '&times;&nbsp;%s', esc_html( $qty_display ) ) . '</strong>', $item ) );
								?>
							</div>
						</div>
						<div class="flexify-cart-order-item__price">
							<?php
							$formatted_line_subtotal = $order->get_formatted_line_subtotal( $item );

							if ( $formatted_line_subtotal !== null ) {
								echo wp_kses_post( $formatted_line_subtotal );
							}
							?>
						</div>
					</div>
					<?php
				}
				?>
			</div>
				<?php foreach ( $order->get_order_item_totals() as $key => $total ) :
					if ( 'payment_method' === $key ) :
						continue;
					endif; ?>

					<div class="flexify-cart-totals <?php echo 'flexify-cart-totals--' . esc_html( $key ); ?>">
						<div class="flexify-cart-totals__label"><span><?php echo esc_html( trim( $total['label'], ':' ) ); ?></span></div>
						<div class="flexify-cart-totals__value">
							<?php if ( 'order_total' === $key ) :
								echo sprintf( '<div class="flexify-cart-totals__currency-badge">%s</div>', esc_html( $order->get_currency() ) );
							endif; ?>

							<span><?php echo wp_kses_post( $total['value'] ); ?></span>
						</div>
					</div>
				<?php endforeach; ?>
		</div>
		<?php

		/**
		 * Thank you page: After Product details.
		 *
		 * @since 1.0.0
		 */
		do_action( 'flexify_thankyou_after_product_details', $order );
	}

	/**
	 * Need to show the map.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return bool.
	 */
	public static function need_to_show_map( $order ) {
		// Return false if shipping is not enabled.
		if ( ! wc_shipping_enabled() || 0 === wc_get_shipping_method_count( true ) ) {
			return false;
		}

		// Return false if shipping method is 'Local Pickup'.
		if ( ! $order->needs_shipping_address() ) {
			return false;
		}

		// Check if at least one product needs shipping.
		$needs_shipping = false;

		foreach ( $order->get_items() as $item ) {
			if ( $item->is_type( 'line_item' ) ) {
				$product = $item->get_product();

				if ( $product && $product->needs_shipping() ) {
					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Show Contact Us at the footer
	 *
	 * @since 1.0.0
	 * @version 5.2.0
	 * @param WC_Order $order | Order object
	 * @return void
	 */
	public static function contact_us( $order ) {
		$contact_page = apply_filters( 'Flexify_Checkout/Thankyou/Contact_Link', Admin_Options::get_setting('contact_page_thankyou') ); ?>
		
		<div class="flexify-ty-footer">
			<span class="flexify-ty-footer__contact">
				<?php if ( ! empty( $contact_page ) ) :
					$contact_page_url = Admin_Options::get_setting('contact_page_thankyou') !== 'custom_link' ? get_permalink( $contact_page ) : Admin_Options::get_setting('contact_page_thankyou_custom_link');
					
					echo '<span class="flexift-ty-footer-contact-container">';
						echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(0, 0, 0, 1);transform: ;msFilter:;"><path d="M12 2C6.486 2 2 6.486 2 12v4.143C2 17.167 2.897 18 4 18h1a1 1 0 0 0 1-1v-5.143a1 1 0 0 0-1-1h-.908C4.648 6.987 7.978 4 12 4s7.352 2.987 7.908 6.857H19a1 1 0 0 0-1 1V18c0 1.103-.897 2-2 2h-2v-1h-4v3h6c2.206 0 4-1.794 4-4 1.103 0 2-.833 2-1.857V12c0-5.514-4.486-10-10-10z"></path></svg>';
						echo sprintf( '<span class="flexify-ty-footer__contact-span">%s <a href="%s">%s</a></span>', esc_html__( 'Precisa de ajuda?', 'flexify-checkout-for-woocommerce' ), esc_url( $contact_page_url ), esc_html__( 'Entrar em contato', 'flexify-checkout-for-woocommerce' ) );
					echo '</span>';
				endif; ?>
			</span>

			<?php if ( ! empty( Admin_Options::get_setting('text_view_shop_thankyou') ) ) : ?>
				<span class="flexify-ty-footer__continue-shipping">
					<a class="flexify-button flexify-button--ty" href="<?php echo esc_url( Helpers::get_shop_page_url() ); ?>" ><?php echo Admin_Options::get_setting('text_view_shop_thankyou') ?></a>
				</span>
			<?php endif; ?>
		</div>
		<?php
	}


	/**
	 * Display downloads table
	 *
	 * @since 1.0.0
	 * @version 5.2.0
	 * @param WC_Order $order | Order object
	 * @return void
	 */
	public static function downloads( $order ) {
		$downloads = $order->get_downloadable_items();
		$show_downloads = $order->has_downloadable_item() && $order->is_download_permitted();

		if ( ! $show_downloads ) :
			return;
		endif; ?>

		<div class="flexify-ty-downloads">
			<div class="flexify-ty-box">
				<?php wc_get_template( 'order/order-downloads.php', array(
					'downloads' => $downloads,
					'show_title' => true,
				)); ?>
			</div>
		</div>
		<?php
	}
}