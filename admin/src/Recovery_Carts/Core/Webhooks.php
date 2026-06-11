<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Core;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handle webhook dispatchers for plugin events
 *
 * @since 1.3.2
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Core
 * @author MeuMouse.com
 */
class Webhooks {

    /**
     * Debug mode flag
     *
     * @since 1.3.2
     * @var bool
     */
    public static $debug_mode = FC_RECOVERY_CARTS_DEBUG_MODE;

    /**
     * Constructor
     *
     * @since 1.3.2
     */
    public function __construct() {
        add_action( 'Flexify_Checkout/Recovery_Carts/Cart_Abandoned', array( $this, 'handle_cart_abandoned' ), 10, 1 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Cart_Abandoned_Manually', array( $this, 'handle_cart_abandoned' ), 10, 1 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Cart_Lost', array( $this, 'handle_cart_lost' ), 10, 1 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Cart_Lost_Manually', array( $this, 'handle_cart_lost' ), 10, 1 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Cart_Recovered', array( $this, 'handle_cart_recovered' ), 10, 2 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Cart_Recovered_Manually', array( $this, 'handle_cart_recovered_manual' ), 10, 1 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Purchased_Cart', array( $this, 'handle_cart_purchased' ), 10, 2 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Order_Abandoned', array( $this, 'handle_order_abandoned' ), 10, 2 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Lead_Collected', array( $this, 'handle_lead_collected' ), 10, 2 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Checkout_Lead_Collected', array( $this, 'handle_checkout_lead_collected' ), 10, 2 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Follow_Up_Message_Sent', array( $this, 'handle_follow_up_sent' ), 10, 5 );
    }


    /**
     * Get registered webhook events
     *
     * @since 1.3.2
     * @return array
     */
    public static function get_registered_events() {
        $events = array(
            'cart_abandoned' => array(
                'label' => esc_html__( 'Carrinho abandonado', 'fc-recovery-carts' ),
                'description' => esc_html__( 'Disparado quando um carrinho é marcado como abandonado.', 'fc-recovery-carts' ),
            ),
            'order_abandoned' => array(
                'label' => esc_html__( 'Pedido abandonado', 'fc-recovery-carts' ),
                'description' => esc_html__( 'Executado quando um pedido permanece pendente e é marcado como abandonado.', 'fc-recovery-carts' ),
            ),
            'cart_lost' => array(
                'label' => esc_html__( 'Carrinho perdido', 'fc-recovery-carts' ),
                'description' => esc_html__( 'Acionado após o término dos follow ups quando o cliente não concluiu o pedido.', 'fc-recovery-carts' ),
            ),
            'cart_recovered' => array(
                'label' => esc_html__( 'Carrinho recuperado', 'fc-recovery-carts' ),
                'description' => esc_html__( 'Executado quando um carrinho abandonado gera um pedido concluído.', 'fc-recovery-carts' ),
            ),
            'purchased_cart' => array(
                'label' => esc_html__( 'Pedido concluído', 'fc-recovery-carts' ),
                'description' => esc_html__( 'Disparado quando um pedido é concluído diretamente pelo cliente.', 'fc-recovery-carts' ),
            ),
            'lead_collected' => array(
                'label' => esc_html__( 'Lead capturado', 'fc-recovery-carts' ),
                'description' => esc_html__( 'Acionado quando os dados de contato são coletados via modal ou checkout.', 'fc-recovery-carts' ),
            ),
            'follow_up_sent' => array(
                'label' => esc_html__( 'Follow up enviado', 'fc-recovery-carts' ),
                'description' => esc_html__( 'Disparado sempre que uma mensagem de follow up é enviada.', 'fc-recovery-carts' ),
            ),
        );

        return apply_filters( 'Flexify_Checkout/Recovery_Carts/Webhooks/Registered_Events', $events );
    }


    /**
     * Handle cart abandoned webhook
     *
     * @since 1.3.2
     * @param int $cart_id | Cart ID
     * @return void
     */
    public function handle_cart_abandoned( $cart_id ) {
        $cart_id = absint( $cart_id );

        if ( ! $cart_id ) {
            return;
        }

        $payload = array(
            'event' => 'cart_abandoned',
            'triggered_at' => $this->current_time_iso(),
            'cart' => $this->prepare_cart_payload( $cart_id ),
        );

        $this->dispatch_event_webhooks( 'cart_abandoned', $payload );
    }


    /**
     * Handle cart lost webhook
     *
     * @since 1.3.2
     * @param int $cart_id | Cart ID
     * @return void
     */
    public function handle_cart_lost( $cart_id ) {
        $cart_id = absint( $cart_id );

        if ( ! $cart_id ) {
            return;
        }

        $payload = array(
            'event' => 'cart_lost',
            'triggered_at' => $this->current_time_iso(),
            'cart' => $this->prepare_cart_payload( $cart_id ),
        );

        $this->dispatch_event_webhooks( 'cart_lost', $payload );
    }


    /**
     * Handle cart recovered webhook
     *
     * @since 1.3.2
     * @param int $cart_id | Cart ID
     * @param int|null $order_id | WooCommerce order ID
     * @return void
     */
    public function handle_cart_recovered( $cart_id, $order_id = null ) {
        $cart_id = absint( $cart_id );
        $order_id = $order_id ? absint( $order_id ) : null;

        if ( ! $cart_id ) {
            return;
        }

        $payload = array(
            'event' => 'cart_recovered',
            'triggered_at' => $this->current_time_iso(),
            'cart' => $this->prepare_cart_payload( $cart_id ),
            'order' => $this->prepare_order_payload( $order_id ),
        );

        $this->dispatch_event_webhooks( 'cart_recovered', $payload );
    }


    /**
     * Handle cart recovered manually webhook
     *
     * @since 1.3.2
     * @param int $cart_id | Cart ID
     * @return void
     */
    public function handle_cart_recovered_manual( $cart_id ) {
        $this->handle_cart_recovered( $cart_id, null );
    }


    /**
     * Handle purchased cart webhook
     *
     * @since 1.3.2
     * @param int $cart_id | Cart ID
     * @param int $order_id | WooCommerce order ID
     * @return void
     */
    public function handle_cart_purchased( $cart_id, $order_id ) {
        $cart_id = absint( $cart_id );
        $order_id = absint( $order_id );

        if ( ! $cart_id || ! $order_id ) {
            return;
        }

        $payload = array(
            'event' => 'purchased_cart',
            'triggered_at' => $this->current_time_iso(),
            'cart' => $this->prepare_cart_payload( $cart_id ),
            'order' => $this->prepare_order_payload( $order_id ),
        );

        $this->dispatch_event_webhooks( 'purchased_cart', $payload );
    }


    /**
     * Handle order abandoned webhook
     *
     * @since 1.3.2
     * @param int $order_id | WooCommerce order ID
     * @param int $cart_id | Cart ID
     * @return void
     */
    public function handle_order_abandoned( $order_id, $cart_id ) {
        $cart_id = absint( $cart_id );
        $order_id = absint( $order_id );

        if ( ! $cart_id ) {
            return;
        }

        $payload = array(
            'event' => 'order_abandoned',
            'triggered_at' => $this->current_time_iso(),
            'cart' => $this->prepare_cart_payload( $cart_id ),
            'order' => $this->prepare_order_payload( $order_id ),
        );

        $this->dispatch_event_webhooks( 'order_abandoned', $payload );
    }


    /**
     * Handle lead collected webhook
     *
     * @since 1.3.2
     * @param int $cart_id | Cart ID
     * @param array $lead_data | Lead data
     * @return void
     */
    public function handle_lead_collected( $cart_id, $lead_data ) {
        $cart_id = absint( $cart_id );

        if ( ! $cart_id ) {
            return;
        }

        $payload = array(
            'event' => 'lead_collected',
            'source' => 'modal',
            'triggered_at' => $this->current_time_iso(),
            'cart' => $this->prepare_cart_payload( $cart_id ),
            'lead' => is_array( $lead_data ) ? Helpers::sanitize_array( $lead_data ) : array(),
        );

        $this->dispatch_event_webhooks( 'lead_collected', $payload );
    }


    /**
     * Handle checkout lead collected webhook
     *
     * @since 1.3.2
     * @param int $cart_id | Cart ID
     * @param array $lead_data | Lead data
     * @return void
     */
    public function handle_checkout_lead_collected( $cart_id, $lead_data ) {
        $cart_id = absint( $cart_id );

        if ( ! $cart_id ) {
            return;
        }

        $payload = array(
            'event' => 'lead_collected',
            'source' => 'checkout',
            'triggered_at' => $this->current_time_iso(),
            'cart' => $this->prepare_cart_payload( $cart_id ),
            'lead' => is_array( $lead_data ) ? Helpers::sanitize_array( $lead_data ) : array(),
        );

        $this->dispatch_event_webhooks( 'lead_collected', $payload );
    }


    /**
     * Handle follow up webhook
     *
     * @since 1.3.2
     * @param int $cart_id | Cart ID
     * @param string $event_key | Follow up event key
     * @param string $message | Message sent
     * @param array $channels | Channels used
     * @param array $event_settings | Original event settings
     * @return void
     */
    public function handle_follow_up_sent( $cart_id, $event_key, $message, $channels, $event_settings ) {
        $cart_id = absint( $cart_id );
        $event_key = sanitize_key( $event_key );

        if ( ! $cart_id || ! $event_key ) {
            return;
        }

        $channels = array_filter( array_map( 'sanitize_key', (array) $channels ) );
        $event_settings = is_array( $event_settings ) ? Helpers::sanitize_array( $event_settings ) : array();

        $payload = array(
            'event' => 'follow_up_sent',
            'triggered_at' => $this->current_time_iso(),
            'cart' => $this->prepare_cart_payload( $cart_id ),
            'follow_up' => array(
                'event_key' => $event_key,
                'title' => $event_settings['title'] ?? '',
                'message' => is_string( $message ) ? $message : '',
                'channels' => array_values( $channels ),
                'delay_time' => $event_settings['delay_time'] ?? '',
                'delay_type' => $event_settings['delay_type'] ?? '',
                'coupon' => $event_settings['coupon'] ?? array(),
            ),
        );

        $this->dispatch_event_webhooks( 'follow_up_sent', $payload );
    }


    /**
     * Dispatch webhook requests for a given event
     *
     * @since 1.3.2
     * @param string $event_key | Event key
     * @param array $payload | Payload to send
     * @return void
     */
    protected function dispatch_event_webhooks( $event_key, array $payload ) {
        $settings = Admin::get_setting('webhooks');

        if ( ! is_array( $settings ) || empty( $settings[ $event_key ] ) || ! is_array( $settings[ $event_key ] ) ) {
            return;
        }

        foreach ( $settings[ $event_key ] as $webhook ) {
            $enabled = $webhook['enabled'] ?? 'yes';
            $url = isset( $webhook['url'] ) ? esc_url_raw( $webhook['url'] ) : '';

            if ( $enabled !== 'yes' || empty( $url ) || ! wp_http_validate_url( $url ) ) {
                continue;
            }

            $headers = $this->prepare_headers( $webhook['headers'] ?? array() );
            $prepared_payload = apply_filters( 'Flexify_Checkout/Recovery_Carts/Webhooks/Payload', $payload, $event_key, $webhook );
            $prepared_payload = apply_filters( 'Flexify_Checkout/Recovery_Carts/Webhooks/Payload/' . $event_key, $prepared_payload, $webhook );
            $body = wp_json_encode( $prepared_payload );

            if ( false === $body ) {
                $this->maybe_log( sprintf( '[FCRC][Webhooks] Falha ao codificar payload JSON para o evento %s.', $event_key ) );
                continue;
            }

            $request_args = array(
                'method' => 'POST',
                'timeout' => 10,
                'headers' => $headers,
                'body' => $body,
            );

            $request_args = apply_filters( 'Flexify_Checkout/Recovery_Carts/Webhooks/Request_Args', $request_args, $event_key, $webhook, $prepared_payload );

            $response = wp_remote_post( $url, $request_args );

            if ( is_wp_error( $response ) ) {
                $this->maybe_log( sprintf( '[FCRC][Webhooks] Erro ao enviar webhook (%s): %s', $event_key, $response->get_error_message() ) );
                continue;
            }

            $status_code = wp_remote_retrieve_response_code( $response );

            if ( $status_code < 200 || $status_code >= 300 ) {
                $this->maybe_log( sprintf( '[FCRC][Webhooks] Resposta inesperada (%s): HTTP %d', $event_key, $status_code ) );
            }
        }
    }


    /**
     * Prepare headers for webhook request
     *
     * @since 1.3.2
     * @param array $headers | Headers config
     * @return array
     */
    protected function prepare_headers( $headers ) {
        $prepared = array(
            'Content-Type' => 'application/json; charset=utf-8',
            'Accept' => 'application/json',
            'User-Agent' => 'Flexify Checkout Recovery Carts/' . FC_RECOVERY_CARTS_VERSION,
        );

        if ( is_array( $headers ) ) {
            foreach ( $headers as $header ) {
                $name = isset( $header['name'] ) ? trim( $header['name'] ) : '';
                $value = isset( $header['value'] ) ? trim( $header['value'] ) : '';

                if ( $name !== '' && $value !== '' ) {
                    $prepared[ $name ] = $value;
                }
            }
        }

        return apply_filters( 'Flexify_Checkout/Recovery_Carts/Webhooks/Headers', $prepared );
    }


    /**
     * Prepare cart payload data
     *
     * @since 1.3.2
     * @param int $cart_id | Cart ID
     * @return array
     */
    protected function prepare_cart_payload( $cart_id ) {
        $cart_id = absint( $cart_id );

        if ( ! $cart_id ) {
            return array();
        }

        $cart_post = get_post( $cart_id );

        if ( ! $cart_post || $cart_post->post_type !== 'fc-recovery-carts' ) {
            return array( 'id' => $cart_id );
        }

        $cart_items = get_post_meta( $cart_id, '_fcrc_cart_items', true );
        $cart_total = get_post_meta( $cart_id, '_fcrc_cart_total', true );
        $abandoned_time = get_post_meta( $cart_id, '_fcrc_abandoned_time', true );

        return array(
            'id' => $cart_id,
            'status' => get_post_status( $cart_id ),
            'created_at' => get_post_time( 'c', true, $cart_post ),
            'updated_at' => $this->normalize_timestamp( get_post_meta( $cart_id, '_fcrc_cart_updated_time', true ) ),
            'abandoned_at' => $this->normalize_timestamp( $abandoned_time ),
            'total' => floatval( $cart_total ),
            'currency' => get_option('woocommerce_currency'),
            'recovery_url' => Helpers::generate_recovery_cart_link( $cart_id, 'webhook', 'webhook' ),
            'customer' => array(
                'first_name' => get_post_meta( $cart_id, '_fcrc_first_name', true ),
                'last_name' => get_post_meta( $cart_id, '_fcrc_last_name', true ),
                'full_name' => get_post_meta( $cart_id, '_fcrc_full_name', true ),
                'email' => get_post_meta( $cart_id, '_fcrc_cart_email', true ),
                'phone' => get_post_meta( $cart_id, '_fcrc_cart_phone', true ),
            ),
            'location' => array(
                'city' => get_post_meta( $cart_id, '_fcrc_location_city', true ),
                'state' => get_post_meta( $cart_id, '_fcrc_location_state', true ),
                'country_code' => get_post_meta( $cart_id, '_fcrc_location_country_code', true ),
                'ip' => get_post_meta( $cart_id, '_fcrc_location_ip', true ),
            ),
            'items' => $this->prepare_cart_items( $cart_items ),
        );
    }


    /**
     * Prepare cart items payload
     *
     * @since 1.3.2
     * @param array $items | Items data
     * @return array
     */
    protected function prepare_cart_items( $items ) {
        if ( ! is_array( $items ) ) {
            return array();
        }

        $prepared = array();

        foreach ( $items as $item ) {
            $product_id = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;

            $prepared[] = array(
                'product_id' => $product_id,
                'name'       => isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '',
                'quantity'   => isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 0,
                'price'      => isset( $item['price'] ) ? floatval( $item['price'] ) : 0.0,
                'total'      => isset( $item['total'] ) ? floatval( $item['total'] ) : 0.0,
                'image'      => isset( $item['image'] ) ? esc_url_raw( $item['image'] ) : '',
                'url'        => $this->get_product_permalink( $product_id ),
            );
        }

        return $prepared;
    }


    /**
     * Get product permalink by ID
     *
     * @since 1.3.2
     * @param int $product_id | Product ID
     * @return string
     */
    protected function get_product_permalink( $product_id ) {
        $product_id = absint( $product_id );

        if ( ! $product_id ) {
            return '';
        }

        if ( function_exists('wc_get_product') ) {
            $product = wc_get_product( $product_id );

            if ( $product && is_callable( array( $product, 'get_permalink' ) ) ) {
                return esc_url_raw( $product->get_permalink() );
            }
        }

        $url = get_permalink( $product_id );

        return $url ? esc_url_raw( $url ) : '';
    }


    /**
     * Prepare order payload data
     *
     * @since 1.3.2
     * @param int|null $order_id | WooCommerce order ID
     * @return array
     */
    protected function prepare_order_payload( $order_id ) {
        $order_id = $order_id ? absint( $order_id ) : 0;

        if ( ! $order_id ) {
            return array();
        }

        if ( ! function_exists('wc_get_order') ) {
            return array( 'id' => $order_id );
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return array( 'id' => $order_id );
        }

        $items = array();

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();

            $items[] = array(
                'product_id' => $product ? $product->get_id() : 0,
                'name'       => $item->get_name(),
                'quantity'   => $item->get_quantity(),
                'total'      => floatval( $item->get_total() ),
                'subtotal'   => floatval( $item->get_subtotal() ),
                'url'        => $product ? esc_url_raw( $product->get_permalink() ) : '',
            );
        }

        return array(
            'id' => $order->get_id(),
            'status' => $order->get_status(),
            'total' => floatval( $order->get_total() ),
            'currency' => $order->get_currency(),
            'created_at' => $order->get_date_created() ? $order->get_date_created()->date_i18n( 'c' ) : '',
            'updated_at' => $order->get_date_modified() ? $order->get_date_modified()->date_i18n( 'c' ) : '',
            'payment_method' => $order->get_payment_method(),
            'billing' => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
            ),
            'items' => $items,
        );
    }


    /**
     * Normalize timestamps to ISO8601
     *
     * @since 1.3.2
     * @param mixed $value | Timestamp or string
     * @return string
     */
    protected function normalize_timestamp( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        if ( is_numeric( $value ) ) {
            $timestamp = absint( $value );
        } else {
            $timestamp = strtotime( $value );
        }

        if ( ! $timestamp ) {
            return '';
        }

        return gmdate( 'c', $timestamp );
    }


    /**
     * Get current time in ISO8601
     *
     * @since 1.3.2
     * @return string
     */
    protected function current_time_iso() {
        return gmdate( 'c', current_time( 'timestamp', true ) );
    }


    /**
     * Log debug messages when enabled
     *
     * @since 1.3.2
     * @param string $message | Message to log
     * @return void
     */
    protected function maybe_log( $message ) {
        if ( self::$debug_mode ) {
            error_log( $message );
        }
    }
}