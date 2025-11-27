<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( class_exists('WC_Subscriptions') ) {
	/**
	 * Compatibility with WooCommerce Subscriptions
	 *
	 * @since 1.0.0
	 * @version 5.4.0
	 * @link https://woocommerce.com/products/woocommerce-subscriptions/
	 * @package MeuMouse.com
	 */
	class Woo_Subscriptions {
		
		/**
		 * Construct function
		 *
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return void
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'init' ) );
		}


		/**
		 * Run on init hook
		 * 
		 * @since 1.0.0
		 * @version 5.4.0
		 * @return void
		 */
		public function init() {
			if ( ! class_exists('WC_Subscriptions') ) {
				return;
			}
			
			// remove default first renewal payment date
			remove_filter( 'wcs_cart_totals_order_total_html', 'wcs_add_cart_first_renewal_payment_date', 10 );
			add_filter( 'wcs_cart_totals_order_total_html', array( $this, 'add_first_renewal_payment_date' ), 10, 2 );

			// remove subscription old message (has wp_kses)
			remove_action( 'woocommerce_thankyou', array( 'WC_Subscriptions_Order', 'subscription_thank_you' ) );

			// add custom subscription message with wrapper
			add_action( 'woocommerce_thankyou', array( $this, 'subscription_thank_you_custom' ), 5, 1 );
		}


		/**
		 * Append the first renewal payment date to a string (which is the order total HTML string by default).
		 *
		 * @since 1.0.0
		 * @version 5.0.0
		 * @param string $order_total_html | Order total HTML
		 * @param mixed  $cart | Cart
		 * @return string
		 */
		public function add_first_renewal_payment_date( $order_total_html, $cart ) {
			if ( 0 !== $cart->next_payment_date ) {
				$first_renewal_date = date_i18n( wc_date_format(), wcs_date_to_time( get_date_from_gmt( $cart->next_payment_date ) ) );
				// Translators: placeholder is a date.
				$order_total_html .= '<div class="first-payment-date"><small>' . __( 'Primeira renovação', 'flexify-checkout-for-woocommerce' ) . '<br />' . $first_renewal_date . '</small></div>';
			}

			return $order_total_html;
		}


		/**
		 * Custom Subscription Thank You Message
		 * 
		 * @since 5.4.0
		 * @param int $order_id | Order ID
		 * @return void
		 */
		public function subscription_thank_you_custom( $order_id ) {
			if ( ! wcs_order_contains_subscription( $order_id, 'any' ) ) {
				return;
			}

			$subscriptions = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => 'any' ) );
			$subscription_count = count( $subscriptions );
			$thank_you_message = '<div class="flexify-checkout-woo-subscriptions-tks-message">';
			$thank_you_message .= '<svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>';
			$thank_you_message .= '<div class="message-content">';

			if ( $subscription_count ) {
				foreach ( $subscriptions as $subscription ) {
					if ( ! $subscription->has_status('active') ) {
						$thank_you_message .= '<p class="need-payment-message">'. esc_html__( 'Sua assinatura será ativada quando o pagamento for compensado.', 'flexify-checkout-for-woocommerce' ) .'</p>';
						break;
					}
				}
			}

			$my_account_subscriptions_url = wc_get_endpoint_url( 'subscriptions', '', wc_get_page_permalink( 'myaccount' ) );
			$thank_you_message .= '<p class="description">'. sprintf( __( 'Veja o status da sua assinatura na <a href="%s">sua conta</a>', 'flexify-checkout-for-woocommerce' ), esc_url( $my_account_subscriptions_url ) ) .'</p>';
			$thank_you_message .= '</div></div>';

			echo $thank_you_message;
		}
	}
}