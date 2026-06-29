<?php

namespace MeuMouse\Flexify_Checkout\Core\Logs;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Wires the file Logger into WordPress/WooCommerce runtime events.
 *
 * Captures three families of events out of the box:
 *  - HTTP requests: outbound wp_remote_* calls made by the plugin (matched by
 *    host or by the plugin User-Agent), with URL, status and duration.
 *  - Payments/orders: order creation at checkout, status transitions and
 *    payment completion, tied to the order id.
 *  - Errors: fatal PHP errors whose origin is inside the plugin directory.
 *
 * Webhook dispatch is logged from the Dispatcher itself (richer context).
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Core\Logs
 * @author MeuMouse.com
 */
class Handler {

    /**
     * Register the runtime hooks.
     *
     * @since 6.0.0
     */
    public function __construct() {
        // Outbound HTTP requests made by the plugin.
        add_action( 'http_api_debug', array( $this, 'log_http_request' ), 10, 5 );

        // Payments / orders.
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'log_order_processed' ), 10, 3 );
        add_action( 'woocommerce_order_status_changed', array( $this, 'log_order_status_changed' ), 10, 4 );
        add_action( 'woocommerce_payment_complete', array( $this, 'log_payment_complete' ), 10, 1 );

        // Fatal error capture scoped to the plugin.
        register_shutdown_function( array( $this, 'log_fatal_error' ) );
    }


    /**
     * Hosts whose outbound requests should be logged. Extendable via filter.
     *
     * @since 6.0.0
     * @return array<int,string> List of host fragments matched with stripos().
     */
    protected function logged_hosts() {
        /**
         * Filter the host fragments whose outbound HTTP requests are logged.
         *
         * @since 6.0.0
         * @param array<int,string> $hosts Default host fragments.
         */
        return apply_filters( 'Flexify_Checkout/Logs/Http_Hosts', array( 'meumouse.com' ) );
    }


    /**
     * Decide whether a given request is "ours" (plugin-originated).
     *
     * Matches either a known host fragment or the plugin User-Agent header that
     * the License/Updater/Webhook transports set.
     *
     * @since 6.0.0
     * @param string $url Request URL.
     * @param array  $args Request args.
     * @return bool
     */
    protected function is_plugin_request( $url, $args ) {
        foreach ( $this->logged_hosts() as $host ) {
            if ( $host !== '' && stripos( (string) $url, $host ) !== false ) {
                return true;
            }
        }

        $user_agent = '';

        if ( isset( $args['headers'] ) && is_array( $args['headers'] ) ) {
            foreach ( $args['headers'] as $name => $value ) {
                if ( strtolower( (string) $name ) === 'user-agent' ) {
                    $user_agent = (string) $value;
                    break;
                }
            }
        }

        if ( $user_agent === '' && isset( $args['user-agent'] ) ) {
            $user_agent = (string) $args['user-agent'];
        }

        return stripos( $user_agent, 'Flexify Checkout' ) !== false;
    }


    /**
     * Log an outbound HTTP request once it completes.
     *
     * Fired by WordPress' http_api_debug action after every wp_remote_* call.
     *
     * @since 6.0.0
     * @param array|\WP_Error $response The response or WP_Error.
     * @param string $context Always 'response' here.
     * @param string $transport Transport class name.
     * @param array  $args Request args.
     * @param string $url Request URL.
     * @return void
     */
    public function log_http_request( $response, $context, $transport, $args, $url ) {
        if ( $context !== 'response' || ! $this->is_plugin_request( $url, (array) $args ) ) {
            return;
        }

        $method = isset( $args['method'] ) ? strtoupper( (string) $args['method'] ) : 'GET';

        if ( is_wp_error( $response ) ) {
            Logger::error( 'request', sprintf( '%s %s failed: %s', $method, $url, $response->get_error_message() ), array(
                'method' => $method,
                'url' => $url,
                'error' => $response->get_error_message(),
            ) );

            return;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $level = ( $code >= 400 || $code === 0 ) ? 'error' : 'info';

        Logger::log( $level, 'request', sprintf( '%s %s → HTTP %d', $method, $url, $code ), array(
            'method' => $method,
            'url' => $url,
            'http_code' => $code,
            'message' => wp_remote_retrieve_response_message( $response ),
        ) );
    }


    /**
     * Log a new order created during checkout.
     *
     * @since 6.0.0
     * @param int   $order_id Order id.
     * @param array $posted_data Posted checkout data.
     * @param mixed $order Order object.
     * @return void
     */
    public function log_order_processed( $order_id, $posted_data, $order = null ) {
        $payment_method = '';

        if ( is_object( $order ) && method_exists( $order, 'get_payment_method' ) ) {
            $payment_method = $order->get_payment_method();
        }

        Logger::info( 'payment', sprintf( 'Order #%d created at checkout.', $order_id ), array(
            'order_id' => (int) $order_id,
            'payment_method' => $payment_method,
        ) );
    }


    /**
     * Log an order status transition.
     *
     * @since 6.0.0
     * @param int    $order_id Order id.
     * @param string $from Previous status.
     * @param string $to New status.
     * @param mixed  $order Order object.
     * @return void
     */
    public function log_order_status_changed( $order_id, $from, $to, $order = null ) {
        // Failed orders are an error-tier event; everything else is informational.
        $level = ( $to === 'failed' ) ? 'error' : 'info';

        Logger::log( $level, 'payment', sprintf( 'Order #%d status changed: %s → %s', $order_id, $from, $to ), array(
            'order_id' => (int) $order_id,
            'from' => (string) $from,
            'to' => (string) $to,
        ) );
    }


    /**
     * Log a completed payment.
     *
     * @since 6.0.0
     * @param int $order_id Order id.
     * @return void
     */
    public function log_payment_complete( $order_id ) {
        Logger::info( 'payment', sprintf( 'Payment completed for order #%d.', $order_id ), array(
            'order_id' => (int) $order_id,
        ) );
    }


    /**
     * Capture a fatal error originating inside the plugin directory.
     *
     * @since 6.0.0
     * @return void
     */
    public function log_fatal_error() {
        $error = error_get_last();

        if ( ! is_array( $error ) || ! isset( $error['type'] ) ) {
            return;
        }

        $fatal_types = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR );

        if ( ! in_array( $error['type'], $fatal_types, true ) ) {
            return;
        }

        $file = isset( $error['file'] ) ? (string) $error['file'] : '';

        // Only log errors whose origin is within this plugin to avoid capturing
        // unrelated fatals from other plugins/themes.
        if ( $file === '' || ! defined('FLEXIFY_CHECKOUT_PATH') || strpos( wp_normalize_path( $file ), wp_normalize_path( FLEXIFY_CHECKOUT_PATH ) ) !== 0 ) {
            return;
        }

        Logger::log( 'critical', 'error', $error['message'], array(
            'file' => $file,
            'line' => isset( $error['line'] ) ? (int) $error['line'] : 0,
            'type' => $error['type'],
        ) );
    }
}
