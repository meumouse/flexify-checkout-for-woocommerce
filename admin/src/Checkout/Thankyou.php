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
 * @package MeuMouse\Flexify_Checkout\Checkout
 * @author MeuMouse.com
 */
class Thankyou {

	/**
	 * Construct function.
	 *
	 * Registers the hooks that ensure third-party trackers (PixelYourSite,
	 * GA4, Google Ads, GTM) resolve the thank-you context correctly when the
	 * order-received endpoint is rendered by Flexify's SPA template.
	 *
	 * @since 5.5.3
	 * @return void
	 */
	public function __construct() {
		add_action( 'parse_request', array( $this, 'prime_order_received_query_var' ), 1 );
		add_filter( 'woocommerce_is_order_received_page', array( $this, 'force_order_received_page' ), 20 );
	}


	/**
	 * Detect the order id from the request URI using the configured
	 * order-received endpoint slug.
	 *
	 * @since 5.5.3
	 * @return int Order id or 0.
	 */
	protected static function extract_order_received_from_uri() {
		$request_uri = ! empty( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';

		if ( $request_uri === '' ) {
			return 0;
		}

		$path = (string) parse_url( $request_uri, PHP_URL_PATH );

		if ( $path === '' ) {
			$path = $request_uri;
		}

		$slug = get_option( 'woocommerce_checkout_order_received_endpoint', 'order-received' );

		if ( ! is_string( $slug ) || $slug === '' ) {
			$slug = 'order-received';
		}

		$slug = trim( $slug, '/' );

		if ( $slug === '' ) {
			return 0;
		}

		if ( preg_match( '#/' . preg_quote( $slug, '#' ) . '/(\d+)(?:/|$)#', $path, $matches ) ) {
			return (int) $matches[1];
		}

		return 0;
	}


	/**
	 * Populate `$wp->query_vars['order-received']` defensively when the request
	 * URI matches the order-received endpoint but WordPress' own routing has
	 * not yet primed it.
	 *
	 * WooCommerce's native `is_order_received_page()` builds its return value
	 * from `is_page($checkout_page_id) && isset($wp->query_vars['order-received'])`
	 * — when Flexify's SPA template renders the thank-you page the queried
	 * object isn't always the checkout page, so the native check returns false
	 * even on a perfectly valid `/order-received/{id}/` URL.
	 *
	 * @since 5.5.3
	 * @param \WP $wp WP request object.
	 * @return void
	 */
	public function prime_order_received_query_var( $wp ) {
		if ( ! ( $wp instanceof \WP ) ) {
			return;
		}

		if ( ! empty( $wp->query_vars['order-received'] ) ) {
			return;
		}

		$order_id = self::extract_order_received_from_uri();

		if ( $order_id > 0 ) {
			$wp->query_vars['order-received'] = $order_id;
		}
	}


	/**
	 * Force `is_order_received_page()` to return true whenever the request URI
	 * matches the order-received endpoint, regardless of whether
	 * `is_page($checkout_page_id)` evaluates to true on the SPA template render.
	 *
	 * @since 5.5.3
	 * @param bool $is_order_received Current filter value.
	 * @return bool
	 */
	public function force_order_received_page( $is_order_received ) {
		if ( $is_order_received ) {
			return true;
		}

		if ( is_admin() || ( function_exists('wp_doing_ajax') && wp_doing_ajax() ) ) {
			return $is_order_received;
		}

		if ( function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received') ) {
			return true;
		}

		global $wp;

		if ( isset( $wp ) && ! empty( $wp->query_vars['order-received'] ) ) {
			return true;
		}

		if ( self::extract_order_received_from_uri() > 0 ) {
			return true;
		}

		return $is_order_received;
	}


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
				<p><?php echo sprintf( esc_html__( 'Order #%s', 'flexify-checkout-for-woocommerce' ), esc_html( $order_id ) ); ?></p>
				<h1><?php echo sprintf( esc_html__( 'Thank you, %s!', 'flexify-checkout-for-woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></h1>	
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
				<div class='flexify-review-customer__label'><label><?php esc_html_e( 'Contact', 'flexify-checkout-for-woocommerce' ); ?></label></div>
				
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
						<label><?php esc_html_e( 'Delivery', 'flexify-checkout-for-woocommerce' ); ?></label>
					</div>

					<div class='flexify-review-customer__content'>
						<p><?php echo Steps::replace_placeholders( Admin_Options::get_setting('text_shipping_customer_review'), Orders::get_order_customer_fragment( $order ), $address_prefix ); ?><p>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( order_has_shipping_method( $order ) ) : ?>
				<div class="flexify-review-customer__row flexify-review-customer__row--shipping-address">
					<div class='flexify-review-customer__label'><label><?php esc_html_e( 'Shipping', 'flexify-checkout-for-woocommerce' ); ?></label></div>
					
					<div class='flexify-review-customer__content'>
						<p><?php echo Orders::get_order_shipping_methods( $order ); ?><p>
					</div>
				</div>
			<?php endif; ?>

			<div class="flexify-review-customer__row">
				<div class='flexify-review-customer__label'><label><?php esc_html_e( 'Payment', 'flexify-checkout-for-woocommerce' ); ?></label></div>
				
				<div class='flexify-review-customer__content'>
					<p><?php echo $order->get_payment_method_title(); ?></p>
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

					// Product may have been deleted/trashed after the order was placed.
					if ( ! $product instanceof \WC_Product ) {
						continue;
					}

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
						echo sprintf( '<span class="flexify-ty-footer__contact-span">%s <a href="%s">%s</a></span>', esc_html__( 'Need help?', 'flexify-checkout-for-woocommerce' ), esc_url( $contact_page_url ), esc_html__( 'Contact', 'flexify-checkout-for-woocommerce' ) );
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
	 * Resolve the order number, honoring WooCommerce Sequential Order Numbers Pro.
	 *
	 * @since 6.0.0
	 * @param WC_Order $order Order object.
	 * @return string
	 */
	public static function get_order_number( $order ) {
		if ( class_exists('WC_Sequential_Order_Numbers_Pro_Loader') ) {
			return (string) $order->get_order_number();
		}

		return (string) $order->get_id();
	}


	/**
	 * Estimated delivery window for the Swift thank-you page.
	 *
	 * WooCommerce has no native delivery estimate, so we derive one from the
	 * order date plus a configurable lead time (in calendar days). Returns a
	 * formatted range string, or an empty string when the order has no shipping.
	 *
	 * @since 6.0.0
	 * @param WC_Order $order Order object.
	 * @return string
	 */
	public static function get_estimated_delivery( $order ) {
		if ( ! order_has_shipping_method( $order ) ) {
			return '';
		}

		/**
		 * Filter the minimum/maximum lead time (calendar days) used to build the
		 * estimated delivery window on the Swift thank-you page.
		 *
		 * @since 6.0.0
		 * @param int      $days  Lead time in days.
		 * @param WC_Order $order Order object.
		 */
		$min_days = (int) apply_filters( 'flexify_checkout_thankyou_estimated_delivery_min_days', 3, $order );
		$max_days = (int) apply_filters( 'flexify_checkout_thankyou_estimated_delivery_max_days', 5, $order );

		$created = $order->get_date_created();
		$base = ( $created instanceof \WC_DateTime ) ? $created->getTimestamp() : time();

		$from = $base + ( $min_days * DAY_IN_SECONDS );
		$to = $base + ( max( $min_days, $max_days ) * DAY_IN_SECONDS );

		$format = apply_filters( 'flexify_checkout_thankyou_estimated_delivery_date_format', 'l, j F', $order );

		$estimate = sprintf( '%s &ndash; %s', date_i18n( $format, $from ), date_i18n( $format, $to ) );

		/**
		 * Filter the final estimated delivery string.
		 *
		 * @since 6.0.0
		 * @param string   $estimate Formatted range.
		 * @param WC_Order $order    Order object.
		 */
		return apply_filters( 'flexify_checkout_thankyou_estimated_delivery', $estimate, $order );
	}


	/**
	 * Build the "What happens next" progress steps for the Swift thank-you page.
	 *
	 * Each step carries a state of `done`, `current`, or `upcoming`, derived from
	 * the order status, so the timeline reflects where the order is.
	 *
	 * @since 6.0.0
	 * @param WC_Order $order Order object.
	 * @return array<int,array<string,string>>
	 */
	public static function get_progress_steps( $order ) {
		$created = $order->get_date_created();
		$created_label = ( $created instanceof \WC_DateTime ) ? date_i18n( wc_date_format(), $created->getTimestamp() ) : '';

		$steps = array(
			array(
				'key' => 'confirmed',
				'label' => __( 'Order confirmed', 'flexify-checkout-for-woocommerce' ),
				'description' => $created_label,
			),
			array(
				'key' => 'processing',
				'label' => __( 'Order processing', 'flexify-checkout-for-woocommerce' ),
				'description' => __( 'We are preparing your items for shipment', 'flexify-checkout-for-woocommerce' ),
			),
			array(
				'key' => 'shipped',
				'label' => __( 'Shipped', 'flexify-checkout-for-woocommerce' ),
				'description' => __( 'You will receive tracking information via email', 'flexify-checkout-for-woocommerce' ),
			),
			array(
				'key' => 'delivered',
				'label' => __( 'Delivered', 'flexify-checkout-for-woocommerce' ),
				'description' => self::get_estimated_delivery( $order ),
			),
		);

		// Map the WooCommerce status to the index of the "current" step. Steps
		// before it are completed; steps after it are upcoming.
		$status_map = array(
			'pending' => 1,
			'on-hold' => 1,
			'failed' => 1,
			'processing' => 1,
			'completed' => 3,
			'cancelled' => 0,
			'refunded' => 0,
		);

		$status = $order->get_status();
		$current = isset( $status_map[ $status ] ) ? $status_map[ $status ] : 1;

		foreach ( $steps as $index => &$step ) {
			if ( $index < $current ) {
				$step['state'] = 'done';
			} elseif ( $index === $current ) {
				$step['state'] = 'current';
			} else {
				$step['state'] = 'upcoming';
			}
		}

		unset( $step );

		/**
		 * Filter the Swift thank-you progress steps.
		 *
		 * @since 6.0.0
		 * @param array    $steps Progress steps.
		 * @param WC_Order $order Order object.
		 */
		return apply_filters( 'flexify_checkout_thankyou_progress_steps', $steps, $order );
	}


	/**
	 * Render the full Swift-theme thank-you page (matches the React checkout
	 * aesthetic). Delegates each section to a focused renderer below.
	 *
	 * @since 6.0.0
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public static function render_swift( $order ) {
		if ( ! ( $order instanceof \WC_Order ) ) {
			return;
		}

		do_action( 'flexify_checkout_thankyou_before_order_status', $order ); ?>

		<div class="flexify-swift-ty">
			<?php
			self::render_swift_confetti( $order );
			self::render_swift_header( $order );
			self::render_swift_summary( $order );
			self::render_swift_delivery( $order );
			self::render_swift_progress( $order );
			self::downloads( $order );
			self::render_swift_help( $order );
			?>
		</div>
		<?php

		do_action( 'flexify_checkout_thankyou_after_order_status', $order );
	}


	/**
	 * Swift thank-you: one-shot confetti celebration on page load.
	 *
	 * Self-contained canvas animation (no external dependency). Fires a couple
	 * of bursts, then removes itself. Honors `prefers-reduced-motion` and only
	 * plays once per order (a sessionStorage flag keyed by order id), so a
	 * reload doesn't replay it.
	 *
	 * @since 6.0.0
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public static function render_swift_confetti( $order ) {
		/**
		 * Filter whether the Swift thank-you confetti animation plays.
		 *
		 * @since 6.0.0
		 * @param bool     $enabled Whether to render the confetti.
		 * @param WC_Order $order   Order object.
		 */
		if ( ! apply_filters( 'flexify_checkout_thankyou_confetti', true, $order ) ) {
			return;
		}

		$colors = apply_filters( 'flexify_checkout_thankyou_confetti_colors', array(
			Admin_Options::get_setting('set_primary_color') ?: '#22c55e',
			'#6366f1',
			'#f59e0b',
			'#ec4899',
			'#06b6d4',
			'#a855f7',
		), $order );

		$colors = array_values( array_filter( array_map( 'sanitize_hex_color', (array) $colors ) ) );

		if ( empty( $colors ) ) {
			$colors = array( '#22c55e' );
		}

		$key = 'flexify_ty_confetti_' . $order->get_id(); ?>

		<canvas class="flexify-swift-ty__confetti" aria-hidden="true"></canvas>
		<script>
		( function() {
			var canvas = document.currentScript.previousElementSibling;

			if ( ! canvas || canvas.dataset.flexifyConfettiBound ) {
				return;
			}

			canvas.dataset.flexifyConfettiBound = '1';

			var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

			try {
				if ( reduce || window.sessionStorage.getItem(<?php echo wp_json_encode( $key ); ?>) ) {
					canvas.remove();

					return;
				}

				window.sessionStorage.setItem(<?php echo wp_json_encode( $key ); ?>, '1');
			} catch ( e ) {}

			var colors = <?php echo wp_json_encode( $colors ); ?>;
			var ctx = canvas.getContext('2d');
			var dpr = window.devicePixelRatio || 1;
			var particles = [];
			var running = true;

			function resize() {
				canvas.width = window.innerWidth * dpr;
				canvas.height = window.innerHeight * dpr;
				ctx.setTransform( dpr, 0, 0, dpr, 0, 0 );
			}

			resize();
			window.addEventListener('resize', resize);

			function burst( originX ) {
				var count = 80;

				for ( var i = 0; i < count; i++ ) {
					var angle = ( Math.PI * ( 0.5 + ( Math.random() - 0.5 ) * 0.7 ) );
					var speed = 8 + Math.random() * 9;

					particles.push({
						x: originX,
						y: window.innerHeight + 10,
						vx: Math.cos( angle ) * speed * ( originX < window.innerWidth / 2 ? 1 : -1 ) + ( Math.random() - 0.5 ) * 3,
						vy: -Math.sin( angle ) * speed - Math.random() * 6,
						size: 5 + Math.random() * 6,
						color: colors[ Math.floor( Math.random() * colors.length ) ],
						rotation: Math.random() * Math.PI,
						spin: ( Math.random() - 0.5 ) * 0.3,
						life: 1,
						decay: 0.006 + Math.random() * 0.006,
					});
				}
			}

			burst( window.innerWidth * 0.2 );
			burst( window.innerWidth * 0.8 );

			setTimeout( function() {
				burst( window.innerWidth * 0.5 );
			}, 280 );

			function frame() {
				if ( ! running ) {
					return;
				}

				ctx.clearRect( 0, 0, canvas.width, canvas.height );

				var alive = false;

				for ( var i = 0; i < particles.length; i++ ) {
					var p = particles[i];

					if ( p.life <= 0 ) {
						continue;
					}

					alive = true;
					p.vy += 0.28;
					p.vx *= 0.99;
					p.x += p.vx;
					p.y += p.vy;
					p.rotation += p.spin;
					p.life -= p.decay;

					ctx.save();
					ctx.globalAlpha = Math.max( 0, p.life );
					ctx.translate( p.x, p.y );
					ctx.rotate( p.rotation );
					ctx.fillStyle = p.color;
					ctx.fillRect( -p.size / 2, -p.size / 2, p.size, p.size * 0.6 );
					ctx.restore();
				}

				if ( alive ) {
					window.requestAnimationFrame( frame );
				} else {
					running = false;
					window.removeEventListener('resize', resize);
					canvas.remove();
				}
			}

			window.requestAnimationFrame( frame );
		} )();
		</script>
		<?php
	}


	/**
	 * Swift thank-you: success header with order number (copy) and email line.
	 *
	 * @since 6.0.0
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public static function render_swift_header( $order ) {
		$order_number = self::get_order_number( $order );
		$email = $order->get_billing_email(); ?>

		<div class="flexify-swift-ty__hero">
			<span class="flexify-swift-ty__check" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</span>

			<h1 class="flexify-swift-ty__title"><?php esc_html_e( 'Thank you for your order!', 'flexify-checkout-for-woocommerce' ); ?></h1>
			<p class="flexify-swift-ty__subtitle"><?php esc_html_e( 'Your order has been received and is being processed', 'flexify-checkout-for-woocommerce' ); ?></p>

			<div class="flexify-swift-ty__order-number">
				<span class="flexify-swift-ty__order-number-label"><?php esc_html_e( 'Order number', 'flexify-checkout-for-woocommerce' ); ?></span>
				<span class="flexify-swift-ty__order-number-value" data-flexify-copy-value="<?php echo esc_attr( $order_number ); ?>"><?php echo esc_html( $order_number ); ?></span>
				<button type="button" class="flexify-swift-ty__copy" data-flexify-copy aria-label="<?php esc_attr_e( 'Copy order number', 'flexify-checkout-for-woocommerce' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
						<rect x="9" y="9" width="11" height="11" rx="2" stroke="currentColor" stroke-width="1.6"/>
						<path d="M5 15V5a2 2 0 0 1 2-2h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
					</svg>
				</button>
			</div>

			<?php if ( ! empty( $email ) ) : ?>
				<p class="flexify-swift-ty__email">
					<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
						<rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/>
						<path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<?php printf( esc_html__( 'Order confirmation sent to %s', 'flexify-checkout-for-woocommerce' ), '<strong>' . esc_html( $email ) . '</strong>' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}


	/**
	 * Swift thank-you: collapsible order summary (items + totals).
	 *
	 * @since 6.0.0
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public static function render_swift_summary( $order ) { ?>
		<details class="flexify-swift-ty__card flexify-swift-ty__summary" open>
			<summary class="flexify-swift-ty__card-head">
				<span class="flexify-swift-ty__card-title"><?php esc_html_e( 'Order Summary', 'flexify-checkout-for-woocommerce' ); ?></span>
				<svg class="flexify-swift-ty__chevron" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</summary>

			<div class="flexify-swift-ty__card-body">
				<div class="flexify-swift-ty__items">
					<?php
					foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
						$product = $item->get_product();

						if ( ! $product instanceof \WC_Product ) {
							continue;
						}

						$image = $product->get_image( array( 56, 56 ) ); ?>

						<div class="flexify-swift-ty__item">
							<div class="flexify-swift-ty__item-image"><?php echo wp_kses_post( $image ); ?></div>
							<div class="flexify-swift-ty__item-info">
								<span class="flexify-swift-ty__item-name"><?php echo esc_html( $product->get_name() ); ?></span>
								<span class="flexify-swift-ty__item-meta"><?php echo wc_display_item_meta( $item, array( 'echo' => false ) ); ?></span>
								<span class="flexify-swift-ty__item-qty"><?php printf( esc_html__( 'Qty: %s', 'flexify-checkout-for-woocommerce' ), esc_html( $item->get_quantity() ) ); ?></span>
							</div>
							<div class="flexify-swift-ty__item-price"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></div>
						</div>
						<?php
					}
					?>
				</div>

				<div class="flexify-swift-ty__totals">
					<?php foreach ( $order->get_order_item_totals() as $key => $total ) :
						if ( 'payment_method' === $key ) {
							continue;
						}

						$is_total = ( 'order_total' === $key ); ?>

						<div class="flexify-swift-ty__total-row<?php echo $is_total ? ' flexify-swift-ty__total-row--grand' : ''; ?>">
							<span class="flexify-swift-ty__total-label"><?php echo esc_html( trim( $total['label'], ':' ) ); ?></span>
							<span class="flexify-swift-ty__total-value"><?php echo wp_kses_post( $total['value'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</details>
		<?php
	}


	/**
	 * Swift thank-you: delivery details (address, method, estimated delivery).
	 *
	 * @since 6.0.0
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public static function render_swift_delivery( $order ) {
		$has_shipping = order_has_shipping_method( $order );
		$ship_different = get_post_meta( $order->get_id(), '_flexify_ship_different_address', true ) === 'yes';

		$address = ( $has_shipping || $ship_different ) ? $order->get_formatted_shipping_address() : '';

		if ( empty( $address ) ) {
			$address = $order->get_formatted_billing_address();
		}

		$ship_name = trim( $order->get_formatted_shipping_full_name() );

		if ( empty( $ship_name ) ) {
			$ship_name = trim( $order->get_formatted_billing_full_name() );
		}

		$shipping_method = Orders::get_order_shipping_methods( $order );
		$estimate = self::get_estimated_delivery( $order ); ?>

		<div class="flexify-swift-ty__card">
			<div class="flexify-swift-ty__card-head flexify-swift-ty__card-head--static">
				<span class="flexify-swift-ty__card-title"><?php esc_html_e( 'Delivery Details', 'flexify-checkout-for-woocommerce' ); ?></span>
			</div>

			<div class="flexify-swift-ty__card-body">
				<?php if ( ! empty( $address ) ) : ?>
					<div class="flexify-swift-ty__detail">
						<span class="flexify-swift-ty__detail-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
								<circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.6"/>
							</svg>
						</span>
						<div class="flexify-swift-ty__detail-body">
							<span class="flexify-swift-ty__detail-label"><?php esc_html_e( 'Shipping to', 'flexify-checkout-for-woocommerce' ); ?></span>
							<?php if ( ! empty( $ship_name ) ) : ?>
								<span class="flexify-swift-ty__detail-name"><?php echo esc_html( $ship_name ); ?></span>
							<?php endif; ?>
							<address class="flexify-swift-ty__detail-address"><?php echo wp_kses_post( $address ); ?></address>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $shipping_method ) ) : ?>
					<div class="flexify-swift-ty__detail">
						<span class="flexify-swift-ty__detail-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M3 7h11v9H3z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
								<path d="M14 10h4l3 3v3h-7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
								<circle cx="7" cy="18" r="1.6" stroke="currentColor" stroke-width="1.6"/>
								<circle cx="17" cy="18" r="1.6" stroke="currentColor" stroke-width="1.6"/>
							</svg>
						</span>
						<div class="flexify-swift-ty__detail-body">
							<span class="flexify-swift-ty__detail-label"><?php esc_html_e( 'Shipping method', 'flexify-checkout-for-woocommerce' ); ?></span>
							<span class="flexify-swift-ty__detail-name"><?php echo esc_html( $shipping_method ); ?></span>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $estimate ) ) : ?>
					<div class="flexify-swift-ty__estimate">
						<span class="flexify-swift-ty__estimate-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M3 8.5 12 4l9 4.5-9 4.5-9-4.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
								<path d="M3 8.5V16l9 4.5 9-4.5V8.5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
							</svg>
						</span>
						<div class="flexify-swift-ty__estimate-body">
							<span class="flexify-swift-ty__estimate-label"><?php esc_html_e( 'Estimated delivery', 'flexify-checkout-for-woocommerce' ); ?></span>
							<span class="flexify-swift-ty__estimate-date"><?php echo wp_kses_post( $estimate ); ?></span>
							<span class="flexify-swift-ty__estimate-note"><?php esc_html_e( 'We will send you tracking information as soon as your order ships', 'flexify-checkout-for-woocommerce' ); ?></span>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}


	/**
	 * Swift thank-you: "What happens next" status timeline.
	 *
	 * @since 6.0.0
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public static function render_swift_progress( $order ) {
		$steps = self::get_progress_steps( $order );

		if ( empty( $steps ) ) {
			return;
		} ?>

		<div class="flexify-swift-ty__card">
			<div class="flexify-swift-ty__card-head flexify-swift-ty__card-head--static">
				<span class="flexify-swift-ty__card-title"><?php esc_html_e( 'What happens next', 'flexify-checkout-for-woocommerce' ); ?></span>
			</div>

			<div class="flexify-swift-ty__card-body">
				<ol class="flexify-swift-ty__timeline">
					<?php foreach ( $steps as $step ) :
						$state = isset( $step['state'] ) ? $step['state'] : 'upcoming'; ?>

						<li class="flexify-swift-ty__timeline-item flexify-swift-ty__timeline-item--<?php echo esc_attr( $state ); ?>">
							<span class="flexify-swift-ty__timeline-dot" aria-hidden="true">
								<?php if ( 'done' === $state ) : ?>
									<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								<?php endif; ?>
							</span>
							<div class="flexify-swift-ty__timeline-body">
								<span class="flexify-swift-ty__timeline-label"><?php echo esc_html( $step['label'] ); ?></span>
								<?php if ( ! empty( $step['description'] ) ) : ?>
									<span class="flexify-swift-ty__timeline-desc"><?php echo wp_kses_post( $step['description'] ); ?></span>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</div>
		<?php
	}


	/**
	 * Swift thank-you: "Need help" footer with track + support actions.
	 *
	 * @since 6.0.0
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public static function render_swift_help( $order ) {
		$track_url = $order->get_view_order_url();

		$contact_page = Admin_Options::get_setting('contact_page_thankyou');
		$support_url = '';

		if ( ! empty( $contact_page ) ) {
			$support_url = $contact_page !== 'custom_link' ? get_permalink( $contact_page ) : Admin_Options::get_setting('contact_page_thankyou_custom_link');
		}

		if ( empty( $track_url ) && empty( $support_url ) ) {
			return;
		} ?>

		<div class="flexify-swift-ty__card">
			<div class="flexify-swift-ty__card-head flexify-swift-ty__card-head--static">
				<span class="flexify-swift-ty__card-title"><?php esc_html_e( 'Need help with your order?', 'flexify-checkout-for-woocommerce' ); ?></span>
			</div>

			<div class="flexify-swift-ty__card-body">
				<div class="flexify-swift-ty__help-actions">
					<?php if ( ! empty( $track_url ) ) : ?>
						<a class="flexify-swift-ty__help-action" href="<?php echo esc_url( $track_url ); ?>">
							<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
								<path d="M3 7h11v9H3z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
								<path d="M14 10h4l3 3v3h-7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
								<circle cx="7" cy="18" r="1.6" stroke="currentColor" stroke-width="1.6"/>
								<circle cx="17" cy="18" r="1.6" stroke="currentColor" stroke-width="1.6"/>
							</svg>
							<span><?php esc_html_e( 'Track order', 'flexify-checkout-for-woocommerce' ); ?></span>
						</a>
					<?php endif; ?>

					<?php if ( ! empty( $support_url ) ) : ?>
						<a class="flexify-swift-ty__help-action" href="<?php echo esc_url( $support_url ); ?>">
							<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
								<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Z" stroke="currentColor" stroke-width="1.6"/>
								<path d="M9.5 9.5a2.5 2.5 0 0 1 4.8.9c0 1.7-2.5 2.1-2.5 3.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
								<circle cx="12" cy="17" r="1" fill="currentColor"/>
							</svg>
							<span><?php esc_html_e( 'Contact support', 'flexify-checkout-for-woocommerce' ); ?></span>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<script>
		( function() {
			var root = document.querySelector('.flexify-swift-ty');

			if ( ! root || root.dataset.flexifyCopyBound ) {
				return;
			}

			root.dataset.flexifyCopyBound = '1';

			root.addEventListener('click', function( event ) {
				var button = event.target.closest('[data-flexify-copy]');

				if ( ! button ) {
					return;
				}

				var value = root.querySelector('[data-flexify-copy-value]');
				var text = value ? value.getAttribute('data-flexify-copy-value') : '';

				if ( ! text || ! navigator.clipboard ) {
					return;
				}

				navigator.clipboard.writeText( text ).then( function() {
					button.classList.add('is-copied');

					setTimeout( function() {
						button.classList.remove('is-copied');
					}, 1600 );
				} );
			} );
		} )();
		</script>
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
