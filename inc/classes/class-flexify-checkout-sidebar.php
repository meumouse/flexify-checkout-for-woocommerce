<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Sidebar related functions
 *
 * @since 1.0.0
 * @version 3.6.0
 * @package MeuMouse.com
 */
class Flexify_Checkout_Sidebar {
	/**
	 * Run.
	 */
	public function __construct() {
		// Cart Page Redirect.
		add_action( 'template_redirect', array( __CLASS__, 'redirect_template_to_checkout' ) );

		// Sidebar actions
		add_action( 'init', array( __CLASS__, 'sidebar_actions' ) );
	}

	/**
	 * Sidebar Actions.
	 *
	 * @since 1.0.0
	 * @version 3.6.0
	 * @return void
	 */
	public static function sidebar_actions() {
		if ( ! self::is_sidebar_enabled() ) {
			return;
		}

		// Remove the cart buttons if option is enabled
		if ( Flexify_Checkout_Init::get_setting('enable_skip_cart_page') === 'yes' ) {
			remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );
		}

		// Add ghost row for spacing.
		add_action( 'woocommerce_review_order_before_order_total', array( __CLASS__, 'review_order_add_ghost_row' ), 100 );

		// Change the order review area position.
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
		add_action( 'flexify_checkout_order_review', 'woocommerce_order_review', 10 );

		// Change the coupon form position.
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		add_action( 'woocommerce_review_order_before_subtotal', array( __CLASS__, 'checkout_add_coupon_form' ), 9 );

		// Add image to checkout.
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'add_image_to_cart' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_class', array( __CLASS__, 'cart_item_class' ), 10, 3 );

		// Change product quantity
		if ( Flexify_Checkout_Init::get_setting('enable_change_product_quantity') === 'yes' ) {
			add_filter( 'woocommerce_checkout_cart_item_quantity', array( __CLASS__, 'cart_quantity_control' ), 100, 3 );
		}

		// remove product button
		if ( Flexify_Checkout_Init::get_setting('enable_remove_product_cart') === 'yes' ) {
			add_filter( 'woocommerce_cart_item_subtotal', array( __CLASS__, 'cart_remove_link' ), 100, 3 );
		}

		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'handle_cart_qty_update' ) );

		add_filter( 'woocommerce_order_button_html', array( __CLASS__, 'place_order_button' ) );
	}


	/**
	 * Replace place order button
	 * 
	 * @since 3.2.0
	 * @param string $html 
	 * @return string
	 */
	public static function place_order_button( $html ) {
		if ( ! Flexify_Checkout_Helpers::is_modern_theme() ) {
			return $html;
		}

		ob_start(); ?>

		<footer class="flexify-footer">
			<?php
			Flexify_Checkout_Steps::back_button('payment');
			echo wp_kses_post( $html ); ?>
		</footer>
		<?php

		return ob_get_clean();
	}


	/**
	 * Add ghost row to order review for spacing
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function review_order_add_ghost_row() {
		if ( ! Flexify_Checkout_Helpers::is_modern_theme() ) {
			return;
		}

		echo '<tr class="flexify-checkout__order-review-ghost-row"><th></th><td></td></tr>';
	}

	/**
	 * Remove checkout shipping fields as we add them ourselves
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function remove_checkout_shipping() {
		remove_action( 'woocommerce_checkout_shipping', array( WC_Checkout::instance(), 'checkout_form_shipping' ) );
	}

	/**
	 * Is Sidebar Enabled.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function is_sidebar_enabled() {
		$settings = get_option( 'flexify_checkout_settings' );
		$theme = Flexify_Checkout_Core::get_theme();
		$show_sidebar = isset( $settings['styles_theme_show_sidebar'] ) && ! empty( $settings['styles_theme_show_sidebar'] ) ? (bool) $settings['styles_theme_show_sidebar'] : false;

		if ( 'classic' !== $theme ) {
			$show_sidebar = true;
		}

		return $show_sidebar;
	}

	/**
	 * Redirect Template to Checkout.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function redirect_template_to_checkout() {
		if ( ! self::is_sidebar_enabled() || Flexify_Checkout_Init::get_setting('enable_skip_cart_page') === 'no' ) {
			return;
		}

		if ( is_cart() && 0 === WC()->cart->cart_contents_count ) {
			wp_safe_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
			exit;
		}

		$queried_object = get_queried_object();

		if ( ! is_object( $queried_object ) || ! property_exists( $queried_object, 'ID' ) || wc_get_page_id( 'cart' ) !== $queried_object->ID ) {
			return;
		}

		$cancel_order = filter_input( INPUT_GET, 'cancel_order' );
		
		if ( ! empty( $cancel_order ) ) {
			return;
		}

		/**
		 * Check cart items are valid.
		 *
		 * @since 2.1.0
		 */
		do_action( 'woocommerce_check_cart_items' );

		if ( wc_notice_count( 'error' ) > 0 ) {
			return;
		}

		wp_safe_redirect( wc_get_checkout_url() );

		exit;
	}

	
	/**
	 * Add coupon form inside order summary section
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function checkout_add_coupon_form() {
		if ( ! Flexify_Checkout_Helpers::is_coupon_enabled() ) {
			return;
		}

		?>
			<tr class="coupon-form">
				<td colspan="2">
					<?php Flexify_Checkout_Steps::render_coupon_form(); ?>
				</td>
			</tr>
		<?php
	}


	/**
	 * Add image to cart
	 *
	 * @since 1.0.0
	 * @version 1.6.2
	 * @param string $name
	 * @param array $cart_item
	 * @param int $cart_item_key
	 * @return string
	 */
	public static function add_image_to_cart( $name, $cart_item, $cart_item_key ) {
		if ( ! is_checkout() ) {
			return $name;
		}

		if ( ! $cart_item['data']->get_image_id() ) {
			return $name;
		}

		// Filter to modify the cart item thumbnail
		$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $cart_item['data']->get_image(), $cart_item, $cart_item_key );

		if ( Flexify_Checkout_Init::get_setting('enable_link_image_products') === 'yes' ) {
			$thumbnail = sprintf( "<a href='%s'>%s</a>", $cart_item['data']->get_permalink(), $thumbnail );
		}

		$image = '<div class="flexify-cart-image flexify-cart-image--checkout flexify-checkout__cart-image">' . $thumbnail . '</div>';

		return $image . $name;
	}


	/**
	 * Add no image class to cart item
	 *
	 * @since 1.0.0
	 * @param string $class
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 * @return mixed|string
	 */
	public static function cart_item_class( $class, $cart_item, $cart_item_key ) {
		if ( $cart_item['data']->get_image_id() ) {
			return $class;
		}

		$class .= ' flexify-cart-item--no-image';

		return $class;
	}


	/**
	 * Cart quantity control
	 *
	 * @since 1.0.0
	 * @version 3.1.0
	 * @param string $output
	 * @param array $cart_item
	 * @param string $cart_item_key
	 * @return string
	 */
	public static function cart_quantity_control( $output, $cart_item, $cart_item_key ) {
		$product = wc_get_product( $cart_item['product_id'] );

		if ( ! is_object( $product ) ) {
			return $output;
		}

		$product_quantity = woocommerce_quantity_input(
			array(
				'input_name' => "cart[{$cart_item_key}][qty]",
				'input_value' => $cart_item['quantity'],
				'max_value' => $product->get_max_purchase_quantity(),
				'min_value' => '0',
				'product_name' => $product->get_name(),
			),
			$product,
			false
		);

		// Filter to modify cart item quantity
		return apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item );
	}

	/**
	 * Cart Remove Link.
	 *
	 * @param string $output Output of quantity.
	 * @param array  $cart_item Cart Item.
	 * @param string $cart_item_key Cart Item Key.
	 * @return string
	 */
	public static function cart_remove_link( $output, $cart_item, $cart_item_key ) {
		if ( ! is_checkout() ) {
			return $output;
		}

		$product = wc_get_product( $cart_item['product_id'] );

		/**
		 * Filter remove from cart link HTML
		 *
		 * @since 1.0.0
		 * @param string $link
		 * @param string $cart_item_key
		 */
		$remove_link = apply_filters( 'woocommerce_cart_item_remove_link',
			sprintf(
				'<a href="%s" class="remove" aria-label="%s" title="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
				esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
				esc_html__( 'Remover este item', 'flexify-checkout-for-woocommerce' ),
				esc_html__( 'Remover este item', 'flexify-checkout-for-woocommerce' ),
				esc_attr( $cart_item['product_id'] ),
				esc_attr( $product->get_sku() )
			),
			$cart_item_key
		);

		return '<span class="flexify-checkout__remove-link">' . $remove_link . '</span>' . $output;
	}

	/**
	 * Handle cart quantity update.
	 *
	 * @param string $post_data Post data.
	 *
	 * @return void
	 */
	public static function handle_cart_qty_update( $post_data ) {
		$data = array();
		$status = true;
		parse_str( $post_data, $data );

		if ( empty( $data['cart'] ) || ! is_array( $data['cart'] ) ) {
			return;
		}

		foreach ( $data['cart'] as $cart_key => $qty ) {
			$status = self::update_product_quantity( $cart_key, $qty['qty'] );

			if ( is_array( $status ) ) {
				break;
			}
		}

		if ( ! is_array( $status ) ) {
			return;
		}

		// Add the error message to Order review Fragments.
		add_action(
			'woocommerce_update_order_review_fragments',
			function( $fragments ) use ( $status ) {
				if ( ! is_array( $status ) || ! isset( $status['error'] ) ) {
					return $fragments;
				}

				if ( ! isset( $fragments['flexify'] ) ) {
					$fragments['flexify'] = array();
				}

				$fragments['flexify'] = array(
					'global_error' => $status['error'],
				);

				return $fragments;
			}
		);
	}

	/**
	 * Update product item quantity.
	 *
	 * @since 1.0.0
	 * @version 3.1.0
	 * @param string $cart_item_key | Cart Item key
	 * @param int $quantity | Product quantity
	 * @return true|array Returns `true` if update is successful, `Array` if there is an error.
	 */
	public static function update_product_quantity( $cart_item_key, $quantity ) {
		global $woocommerce;

		$updated = array();
		$cart = WC()->cart->get_cart();
		$product = isset( $cart[ $cart_item_key ] ) ? $cart[ $cart_item_key ]['data'] : false;

		$current_session_order_id = isset( WC()->session->order_awaiting_payment ) ? absint( WC()->session->order_awaiting_payment ) : 0;

		if ( empty( $product ) ) {
			return false;
		}

		// is_sold_individually.
		if ( $product->is_sold_individually() && $quantity > 1 ) {
			/* Translators: %s Product title. */
			$msg = sprintf( esc_html__( 'Você só pode comprar 1 %s por pedido.', 'flexify-checkout-for-woocommerce' ), $product->get_name() );
			$updated = array(
				'error' => $msg,
			);

			return $updated;
		}

		// We only need to check products managing stock, with a limited stock qty.
		if ( $product->managing_stock() && ! $product->backorders_allowed() ) {
			// Check stock based on all items in the cart and consider any held stock within pending orders.
			$held_stock = wc_get_held_stock_quantity( $product, $current_session_order_id );

			if ( $product->get_stock_quantity() < ( $held_stock + $quantity ) ) {
				/* translators: 1: product name 2: quantity in stock */
				$msg = sprintf( __( 'Desculpe, não temos "%1$s" suficientes em estoque para atender seu pedido (%2$s disponíveis). Pedimos desculpas por qualquer inconveniente causado.', 'flexify-checkout-for-woocommerce' ), $product->get_name(), wc_format_stock_quantity_for_display( $product->get_stock_quantity() - $held_stock, $product ) );

				$updated = array(
					'error' => $msg,
				);

				return $updated;
			}
		}

		if ( empty( $quantity ) ) {
			$updated = WC()->cart->remove_cart_item( $cart_item_key );
		} else {
			$updated = WC()->cart->set_quantity( $cart_item_key, intval( $quantity ), true );
		}

		return $updated;
	}
}

new Flexify_Checkout_Sidebar();