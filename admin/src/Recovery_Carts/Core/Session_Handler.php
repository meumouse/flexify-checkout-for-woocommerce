<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Core;

use WC_Session_Handler;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Extends WooCommerce session handler to detect expired sessions
 *
 * @since 1.0.0
 * @version 1.3.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Core
 * @author MeuMouse.com
 */
class Session_Handler extends WC_Session_Handler {

    /**
     * Construct function
     * 
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        // Hook to replace WooCommerce session handler
        add_filter( 'woocommerce_session_handler', array( $this, 'replace_session_handler' ) );

        parent::__construct(); // Ensure WooCommerce session functions are initialized
    }


    /**
     * Overrides the WooCommerce session handler with our custom handler
     *
     * @since 1.0.0
     * @param string $session_class WooCommerce default session handler
     * @return string Custom session handler class.
     */
    public function replace_session_handler( $session_class ) {
        if ( ! class_exists('WC_Session_Handler') ) {
            return $session_class; // WooCommerce is not loaded, use default handler
        }

        return __CLASS__; // Returns the current class as the new session handler
    }


    /**
     * Overrides the session cleanup function to detect abandoned carts
     *
     * @since 1.0.0
     * @version 1.3.0
     * @return void
     */
    public function cleanup_sessions() {
        global $wpdb;

        parent::cleanup_sessions(); // Run the default WooCommerce cleanup

        $time_limit_seconds = Helpers::get_abandonment_time_seconds();
        $current_time = current_time( 'timestamp', true );

        $query = $wpdb->prepare("
            SELECT session_id, session_key
            FROM {$wpdb->prefix}woocommerce_sessions
            WHERE session_expiry < %d", $current_time - $time_limit_seconds
        );

        $sessions = $wpdb->get_results( $query );

        if ( ! empty( $sessions ) ) {
            foreach ( $sessions as $session ) {
                // Get the cart_id from session
                $cart_id = get_user_meta( $session->session_key, 'fcrc_cart_id', true );

                if ( $cart_id ) {
                    // Mark the cart as abandoned
                    update_post_meta( $cart_id, '_fcrc_abandoned_time', $current_time );

                    wp_update_post( array(
                        'ID' => $cart_id,
                        'post_status' => 'abandoned',
                    ));
                }
            }
        }
    }
}