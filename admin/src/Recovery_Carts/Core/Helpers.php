<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Core;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Cron\Scheduler_Manager;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Helpers class
 * 
 * @since 1.0.0
 * @version 1.4.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Core
 * @author MeuMouse.com
 */
class Helpers {

    /**
     * Get debug mode
     * 
     * @since 1.3.0
     * @return bool
     */
    public static $debug_mode = FC_RECOVERY_CARTS_DEBUG_MODE;
   
    /**
     * Check admin page from partial URL
     * 
     * @since 1.0.0
     * @param $admin_page | Page string for check from admin.php?page=
     * @return bool
     */
    public static function check_admin_page( $admin_page ) {
        $current_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
        return strpos( $current_url, "admin.php?page=$admin_page" );
    }


    /**
     * Check if is product
     * 
     * @since 1.0.0
     * @return bool
     */
    public static function is_product() {
        $page_id = get_queried_object_id();
        $is_product_page = false;
        $is_shop_page = false;
        $has_product = false;
    
        // check if is a single product page
        if ( is_singular('product') ) {
            return true;
        }
    
        // check if is shop page
        if ( function_exists('wc_get_page_id') && $page_id === wc_get_page_id('shop') ) {
            return true;
        }
    
        // check if the page contains products (like category or search files)
        if ( is_post_type_archive('product') || is_tax('product_cat') || is_tax('product_tag') || is_search() ) {
            return true;
        }

        return false;
    }


    /**
     * Converts abandonment time into seconds
     *
     * @since 1.0.0
     * @version 1.3.0
     * @return int Time in seconds
     */
    public static function get_abandonment_time_seconds() {
        $time_limit = Admin::get_setting('time_for_lost_carts');
        $time_unit = Admin::get_setting('time_unit_for_lost_carts');

        switch ( $time_unit ) {
            case 'minutes':
                return (int) $time_limit * 60;
            case 'hours':
                return (int) $time_limit * 3600;
            case 'days':
                return (int) $time_limit * 86400;
            default:
                return 1800; // Default: 30 minutes
        }
    }

    
    /**
     * Converts time to seconds based on unit
     *
     * @since 1.0.0
     * @version 1.3.0
     * @param int $time | The time value
     * @param string $unit | The unit of time (minutes, hours, days)
     * @return int Time in seconds
     */
    public static function convert_to_seconds( $time, $unit ) {
        switch ( $unit ) {
            case 'minutes':
                return (int) $time * 60;
            case 'hours':
                return (int) $time * 3600;
            case 'days':
                return (int) $time * 86400;
            default:
                return 0; // Default: 0 seconds
        }
    }


    /**
     * Generates a recovery cart link with initial products and UTM parameters
     *
     * @since 1.0.0
     * @version 1.1.0
     * @param int $cart_id | The recovery cart post ID
     * @param string $medium | The medium of the link (whatsapp, email, etc.)
     * @return string The recovery cart URL
     */
    public static function generate_recovery_cart_link( $cart_id, $source = 'joinotify', $medium = 'whatsapp' ) {
        if ( ! $cart_id ) {
            return '';
        }

        // Base URL (Checkout page)
        $cart_page_url = wc_get_checkout_url();

        // Build query parameters
        $query_params = array(
            'recovery_cart' => $cart_id, // Cart ID identifier
            'utm_source' => $source,
            'utm_medium' => $medium,
            'utm_campaign' => 'recovery_carts',
        );

        // Generate recovery link without products
        return add_query_arg( $query_params, $cart_page_url );
    }


    /**
     * Restores the cart from the recovery link
     *
     * @since 1.0.0
     * @version 1.3.7
     * @return void
     */
    public static function maybe_restore_cart() {
        if ( is_admin() ) {
            return;
        }

        // temporary tracer: log WC cart state on the redirect target so we can
        // see whether items survived the redirect
        if ( self::$debug_mode && function_exists('WC') && WC()->cart ) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';

            if ( strpos( $uri, 'recovery_cart' ) === false ) {
                $cart_keys = array_keys( WC()->cart->get_cart() );
                error_log( '[FCRC][Trace] front-end request URL=' . $uri . ' wc_cart_keys=' . wp_json_encode( $cart_keys ) . ' session_fcrc_cart_id=' . var_export( WC()->session ? WC()->session->get('fcrc_cart_id') : null, true ) );
            }
        }

        if ( ! isset( $_GET['recovery_cart'] ) ) {
            return;
        }

        if ( self::$debug_mode ) {
            error_log( '[FCRC][Restore] maybe_restore_cart triggered. URL: ' . ( $_SERVER['REQUEST_URI'] ?? '' ) );
        }

        $cart_id = intval( $_GET['recovery_cart'] );

        if ( ! $cart_id || get_post_type( $cart_id ) !== 'fc-recovery-carts' ) {
            if ( self::$debug_mode ) {
                error_log( "[FCRC][Restore] Cart ID {$cart_id} invalid or not a recovery cart post." );
            }

            return;
        }

        // Do not restore carts that already completed the recovery cycle
        if ( self::is_cart_cycle_finished( $cart_id ) ) {
            if ( self::$debug_mode ) {
                error_log( "[FCRC][Restore] Cart ID {$cart_id} already completed. Skipping restore." );
            }

            wp_safe_redirect( wc_get_checkout_url() );

            exit;
        }

        // get products from cart
        $cart_items = get_post_meta( $cart_id, '_fcrc_cart_items', true );

        if ( self::$debug_mode ) {
            error_log( "[FCRC][Restore] Cart {$cart_id} items meta: " . print_r( $cart_items, true ) );
        }

        if ( empty( $cart_items ) || ! is_array( $cart_items ) ) {
            if ( self::$debug_mode ) {
                error_log( "[FCRC][Restore] No products found in meta for cart {$cart_id}." );
            }

            return;
        }

        if ( ! function_exists('WC') || ! WC()->session || ! WC()->cart ) {
            if ( self::$debug_mode ) {
                error_log( "[FCRC][Restore] WooCommerce session/cart unavailable while restoring cart {$cart_id}." );
            }

            return;
        }

        // Set recovery mode
        WC()->session->set( 'fcrc_cart_recovery_mode', true );

        if ( self::$debug_mode ) {
            error_log( "[FCRC][Restore] Recovery mode flag set. Emptying current cart and restoring " . count( $cart_items ) . " item(s)." );
        }

        // clear cart before restoring cart
        WC()->cart->empty_cart();

        // add products to cart
        foreach ( $cart_items as $item ) {
            $product_id = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;

            if ( ! $product_id ) {
                continue;
            }

            $quantity = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;
            $variation_id = isset( $item['variation_id'] ) ? (int) $item['variation_id'] : 0;
            $variation = isset( $item['variation'] ) && is_array( $item['variation'] ) ? $item['variation'] : array();

            // when restoring a variable product, the saved variation attributes
            // may be missing (legacy data saved before variation persistence).
            // rebuild them from the variation post itself so add_to_cart accepts.
            if ( $variation_id > 0 && empty( $variation ) && function_exists('wc_get_product') ) {
                $variation_product = wc_get_product( $variation_id );

                if ( $variation_product && is_callable( array( $variation_product, 'get_variation_attributes' ) ) ) {
                    $variation = $variation_product->get_variation_attributes();
                }
            }

            $added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );

            if ( self::$debug_mode ) {
                error_log( sprintf(
                    '[FCRC][Restore] add_to_cart(product=%d, variation=%d, qty=%d, variation_attrs=%s) => %s',
                    $product_id,
                    $variation_id,
                    $quantity,
                    wp_json_encode( $variation ),
                    $added ? 'OK (' . $added . ')' : 'FAILED'
                ));

                if ( ! $added && function_exists('wc_get_notices') ) {
                    error_log( '[FCRC][Restore] WC notices after failure: ' . wp_json_encode( wc_get_notices('error') ) );
                }
            }
        }

        // Clear recovery mode flag now that the loop is complete
        WC()->session->__unset('fcrc_cart_recovery_mode');

        if ( self::$debug_mode ) {
            error_log( '[FCRC][Restore] Cart contents after restore: ' . wp_json_encode( array_keys( WC()->cart->get_cart() ) ) );
        }

        // do not show WC error notices accumulated during silent restoration
        if ( function_exists('wc_clear_notices') ) {
            wc_clear_notices();
        }

        // store cart ID in session and cookie
        WC()->session->set( 'fcrc_cart_id', $cart_id );
        setcookie( 'fcrc_cart_id', $cart_id, strtotime( current_time('mysql') ) + ( 7 * 24 * 60 * 60 ), COOKIEPATH, COOKIE_DOMAIN );

        // Persist WC cart session immediately so the redirected request can
        // read the restored items (instead of relying on the shutdown hook,
        // which may not commit before the browser fires the next request).
        if ( is_callable( array( WC()->cart, 'set_session' ) ) ) {
            WC()->cart->set_session();
        }

        // Force WC to issue the `wp_woocommerce_session_xxx` cookie. In a
        // brand-new browser session (e.g. anonymous tab) the visitor has no
        // WC session cookie yet — without this call, the redirected request
        // receives a fresh empty session and our restored cart is lost.
        if ( is_callable( array( WC()->session, 'set_customer_session_cookie' ) ) ) {
            WC()->session->set_customer_session_cookie( true );
        }

        if ( is_callable( array( WC()->session, 'save_data' ) ) ) {
            WC()->session->save_data();
        }

        if ( self::$debug_mode ) {
            $headers_sent = headers_sent( $hs_file, $hs_line );

            error_log( sprintf(
                '[FCRC][Restore] Cart %d restored. headers_sent=%s%s. Redirecting to: %s',
                $cart_id,
                $headers_sent ? 'YES' : 'no',
                $headers_sent ? ' (at ' . $hs_file . ':' . $hs_line . ')' : '',
                wc_get_checkout_url()
            ));
        }

        // redirect to checkout
        wp_safe_redirect( wc_get_checkout_url() );

        exit;
    }


    /**
     * Clears the cart ID from session and cookie
     *
     * @since 1.0.0
     * @version 1.3.0
     * @return void
     */
    public static function clear_active_cart() {
        // Remove from WooCommerce session
        if ( WC()->session ) {
            WC()->session->set( 'fcrc_cart_id', null );
            WC()->session->set( 'fcrc_active_cart', null );
        }

        // Remove from cookie
        if ( isset( $_COOKIE['fcrc_cart_id'] ) ) {
            unset( $_COOKIE['fcrc_cart_id'] );
            setcookie( 'fcrc_cart_id', '', strtotime( current_time('mysql') ) - 3600, COOKIEPATH, COOKIE_DOMAIN );
        }

        if ( self::$debug_mode ) {
            error_log( 'Cart ID removed from session and cookies.' );
        }
    }


    /**
     * Checks if the cart cycle is finished
     * 
     * @since 1.1.0
     * @version 1.3.5
     * @param int $cart_id | The cart ID to check
     * @return bool
     */
    public static function is_cart_cycle_finished( $cart_id = null ) {
        if ( ! $cart_id ) {
            $cart_id = self::get_current_cart_id();
        }
        
        if ( self::$debug_mode ) {
            error_log( '[Helpers] is_cart_cycle_finished called. cart_id: ' . ($cart_id ? $cart_id : 'null') );
        }
        
        if ( ! $cart_id || empty( $cart_id ) ) {
            if ( self::$debug_mode ) {
                error_log( '[Helpers] No cart ID or empty. Returning false.' );
            }

            return false;
        }
        
        $status = get_post_status( $cart_id );
        
        if ( self::$debug_mode ) {
            error_log( '[Helpers] Cart status for ID ' . $cart_id . ': ' . ($status ? $status : 'not found') );
        }
        
        $finished = in_array( $status, array( 'recovered', 'purchased', 'completed', 'order_abandoned', 'lost' ), true );
        
        if ( self::$debug_mode ) {
            error_log( '[Helpers] is_cart_cycle_finished result: ' . ($finished ? 'true' : 'false') );
        }
        
        return $finished;
    }


    /**
     * Get current cart ID from WooCommerce session or cookie
     * 
     * @since 1.1.0
     * @version 1.1.2
     * @return string|null
     */
    public static function get_current_cart_id() {
        if ( function_exists('WC') && WC()->session instanceof \WC_Session && WC()->session->get('fcrc_cart_id') !== null ) {
            $cart_id = WC()->session->get('fcrc_cart_id');
        } else {
            $cart_id = $_COOKIE['fcrc_cart_id'] ?? null;
        }

        if ( self::$debug_mode ) {
            error_log( 'Current cart ID: ' . $cart_id );
        }

        return $cart_id;
    }


    /**
     * Recursively merge two arrays, adding missing keys from defaults
     *
     * @since 1.3.0
     * @param array $defaults | The default values
     * @param array $current | The current values
     * @return array
     */
    public static function recursive_merge( $defaults, $current ) {
        foreach ( $defaults as $key => $value ) {
            if ( is_array( $value ) ) {
                if ( isset( $current[ $key ] ) && is_array( $current[ $key ] ) ) {
                    $current[ $key ] = self::recursive_merge( $value, $current[ $key ] );
                } else {
                    $current[ $key ] = $value;
                }
            } else {
                if ( ! isset( $current[ $key ] ) ) {
                    $current[ $key ] = $value;
                }
            }
        }

        return $current;
    }


    /**
     * Recursively sanitize array values
     *
     * @since 1.3.0
     * @param array $array | The array to sanitize
     * @return array
     */
    public static function sanitize_array( $array ) {
        foreach ( $array as $key => $value ) {
            if ( is_array( $value ) ) {
                $array[ $key ] = self::sanitize_array( $value );
            } else {
                if ( strpos( $key, 'message' ) !== false ) {
                    $array[ $key ] = sanitize_textarea_field( wp_unslash( $value ) );
                } else {
                    $array[ $key ] = sanitize_text_field( wp_unslash( $value ) );
                }
            }
        }
        return $array;
    }


    /**
     * Check if plugin Flexify Checkout is Pro
     * 
     * @since 1.0.0
     * @version 1.3.0
     * @return bool
     */
    public static function is_pro() {
        $get_status = get_option( 'flexify_checkout_license_status', 'invalid' );

        if ( $get_status === 'valid' ) {
            return true;
        }

        return false;
    }

    
    /**
     * Retrieve the client's IP address (Cookie or REMOTE_ADDR)
     *
     * @since 1.3.0
     * @return string
     */
    public static function get_client_ip() {
        if ( ! empty( $_COOKIE['fcrc_location'] ) ) {
            $location = json_decode( stripslashes( $_COOKIE['fcrc_location'] ), true );

            if ( ! empty( $location['ip'] ) ) {
                return $location['ip'];
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '';
    }


    /**
     * Retrieve user data previous collected by IP, if exists
     *
     * @since 1.3.0
     * @param string $ip | IP address
     * @return array User contact data
     */
    public static function get_user_data_by_ip( $ip ) {
        $map = get_option( 'fcrc_ip_user_map', array() );

        return $map[ $ip ] ?? array();
    }


    /**
     * Get user data from multiple sources
     *
     * @since 1.3.0
     * @return array {first_name, last_name, phone, email}
     */
    public static function get_cart_contact_data() {
        // if user is logged
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();

            return array(
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->user_email,
                'phone' => get_user_meta( $user->ID, 'billing_phone', true ),
            );
        }

        // from flexify checkout session
        if ( WC()->session && $session = WC()->session->get('flexify_checkout_customer_fields') ) {
            return array(
                'first_name' => $session['billing_first_name'] ?? '',
                'last_name' => $session['billing_last_name'] ?? '',
                'email' => $session['billing_email'] ?? '',
                'phone' => $session['billing_phone'] ?? '',
            );
        }

        // from cookies
        $first = $_COOKIE['fcrc_first_name'] ?? '';
        $last = $_COOKIE['fcrc_last_name'] ?? '';
        $email = $_COOKIE['fcrc_email'] ?? '';
        $phone = $_COOKIE['fcrc_phone'] ?? '';

        if ( $first || $last || $email || $phone ) {
            return compact( 'first', 'last', 'email', 'phone' );
        }

        // fallback IP
        $ip = self::get_client_ip();

        return self::get_user_data_by_ip( $ip );
    }


    /**
     * Get formatted channel label
     * 
     * @since 1.3.0
     * @param string $channel | Channel name
     * @return string
     */
    public static function get_formatted_channel_label( $channel ) {
        if ( $channel === 'whatsapp' ) {
            return esc_html__( 'WhatsApp', 'fc-recovery-carts' );
        } elseif ( $channel === 'email' ) {
            return esc_html__( 'E-mail', 'fc-recovery-carts' );
        }
        
        return ucfirst( $channel );
    }


    /**
     * Cancel all scheduled follow-up events (hook 'fcrc_send_follow_up_message') for a given cart ID
     *
     * @since 1.3.0
     * @version 1.3.6
     * @param int $cart_id | The recovery cart post ID
     * @return void
     */
    public static function cancel_scheduled_follow_up_events( $cart_id ) {
        // Query all cron-event posts for this cart and the follow-up hook
        $events = get_posts( array(
            'post_type' => 'fcrc-cron-event',
            'post_status' => array( 'publish', 'draft' ),
            'meta_query' => array(
                array(
                    'key' => '_fcrc_cart_id',
                    'value' => $cart_id,
                ),
                array(
                    'key' => '_fcrc_cron_event_key',
                    'value' => 'fcrc_send_follow_up_message',
                ),
            ),
            'posts_per_page' => -1, // get all posts
        ));

        foreach ( $events as $event ) {
            $args = get_post_meta( $event->ID, '_fcrc_cron_args', true );

            if ( ! is_array( $args ) ) {
                $args = array(
                    'cart_id' => $cart_id,
                );
            }

            Scheduler_Manager::unschedule_event( 'fcrc_send_follow_up_message', $args );

            // Ensure queue entry is removed even if it was already moved to draft by the processor
            wp_delete_post( $event->ID, true );

            // Log the cancellation if debug mode is enabled
            if ( self::$debug_mode ) {
                error_log( sprintf( 'Cancelled follow-up event for cart %d, cron_post_id %d', $cart_id, $event->ID ) );
            }
        }
    }


    /**
     * Checks if WP-CLI is available in the current environment.
     *
     * @since 1.3.2
     * @return bool
     */
    public static function has_wp_cli() {
        $available = false;

        if ( defined('WP_CLI') && WP_CLI ) {
            $available = true;
        } elseif ( class_exists( '\\WP_CLI' ) ) {
            $available = true;
        } elseif ( self::command_exists('wp') ) {
            $available = true;
        } elseif ( defined('ABSPATH') && file_exists( ABSPATH . 'wp-cli.phar' ) ) {
            $available = true;
        }

        /**
         * Filter the detection of the WP-CLI availability.
         *
         * @since 1.3.2
         * @param bool $available Whether WP-CLI appears to be available.
         */
        return (bool) apply_filters( 'Flexify_Checkout/Recovery_Carts/Has_WP_CLI', $available );
    }


    /**
     * Checks if a shell command exists on the server
     *
     * @since 1.3.2
     * @param string $command | Command name
     * @return bool
     */
    protected static function command_exists( $command ) {
        if ( empty( $command ) ) {
            return false;
        }

        $checks = array();

        if ( self::is_shell_function_available('shell_exec') ) {
            $shell_variants = array(
                'command -v ' . escapeshellarg( $command ),
                'which ' . escapeshellarg( $command ),
                'where ' . escapeshellarg( $command ),
            );

            foreach ( $shell_variants as $variant ) {
                $result = @shell_exec( $variant );

                if ( is_string( $result ) ) {
                    $checks[] = trim( $result );
                }
            }
        }

        foreach ( $checks as $result ) {
            if ( ! empty( $result ) ) {
                return true;
            }
        }

        if ( self::is_shell_function_available('exec') ) {
            $variants = array(
                'command -v ' . escapeshellarg( $command ),
                'which ' . escapeshellarg( $command ),
                'where ' . escapeshellarg( $command ),
            );

            foreach ( $variants as $variant ) {
                $output = array();
                $return_var = 1;

                @exec( $variant, $output, $return_var );

                if ( 0 === $return_var && ! empty( $output ) ) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Checks if a shell function is enabled in the current PHP configuration.
     *
     * @since 1.3.2
     * @param string $function | Function name
     * @return bool
     */
    protected static function is_shell_function_available( $function ) {
        if ( ! function_exists( $function ) ) {
            return false;
        }

        $disabled = array_map( 'trim', explode( ',', (string) ini_get('disable_functions') ) );

        return ! in_array( $function, $disabled, true );
    }


    /**
     * Clean up old lost carts for the current IP
     * 
     * @since 1.3.5
     * @param string $client_ip
     * @return void
     */
    public static function cleanup_old_lost_carts( $client_ip ) {
        if ( ! $client_ip ) {
            return;
        }
        
        $lost_carts = get_posts( array(
            'post_type'      => 'fc-recovery-carts',
            'post_status'    => array( 'lost' ),
            'meta_key'       => '_fcrc_location_ip',
            'meta_value'     => $client_ip,
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => '_fcrc_cart_updated_time',
                    'value'   => current_time('timestamp', true) - (24 * 60 * 60), // Últimas 24 horas
                    'compare' => '<',
                    'type'    => 'NUMERIC',
                ),
            ),
        ));
        
        foreach ( $lost_carts as $cart_id ) {
            wp_delete_post( $cart_id, true );
            
            if ( self::$debug_mode ) {
                error_log( '[Cart_Events] Cleaned up old lost cart ID: ' . $cart_id . ' for IP: ' . $client_ip );
            }
        }
    }


    /**
     * Cancel scheduled follow-up events if an order was placed 24 hours after abandonment
     * matching the cart contact info and at least one product.
     *
     * @since 1.3.8
     * @param int $cart_id | The recovery cart post ID
     * @return bool True when follow-ups were canceled.
     */
    public static function maybe_cancel_followups_after_late_purchase( $cart_id ) {
        $abandoned_time = get_post_meta( $cart_id, '_fcrc_abandoned_time', true );

        if ( empty( $abandoned_time ) ) {
            return false;
        }

        $abandoned_time = is_numeric( $abandoned_time ) ? (int) $abandoned_time : strtotime( $abandoned_time );

        if ( ! $abandoned_time ) {
            return false;
        }

        $threshold_time = $abandoned_time + DAY_IN_SECONDS;
        $current_time = current_time( 'timestamp', true );

        if ( $current_time < $threshold_time ) {
            return false;
        }

        $cart_email = get_post_meta( $cart_id, '_fcrc_cart_email', true );
        $cart_phone = get_post_meta( $cart_id, '_fcrc_cart_phone', true );

        if ( empty( $cart_email ) && empty( $cart_phone ) ) {
            return false;
        }

        $cart_items = get_post_meta( $cart_id, '_fcrc_cart_items', true );

        if ( empty( $cart_items ) || ! is_array( $cart_items ) ) {
            return false;
        }

        $cart_product_ids = array();

        foreach ( $cart_items as $item ) {
            if ( isset( $item['product_id'] ) ) {
                $cart_product_ids[] = (int) $item['product_id'];
            }
        }

        $cart_product_ids = array_filter( array_unique( $cart_product_ids ) );

        if ( empty( $cart_product_ids ) ) {
            return false;
        }

        $meta_query = array();

        if ( $cart_email ) {
            $meta_query[] = array(
                'key' => '_billing_email',
                'value' => $cart_email,
                'compare' => '=',
            );
        }

        if ( $cart_phone ) {
            $meta_query[] = array(
                'key' => '_billing_phone',
                'value' => $cart_phone,
                'compare' => '=',
            );
        }

        if ( empty( $meta_query ) ) {
            return false;
        }

        if ( count( $meta_query ) > 1 ) {
            $meta_query = array_merge( array( 'relation' => 'OR' ), $meta_query );
        }

        $order_ids = get_posts( array(
            'post_type' => 'shop_order',
            'post_status' => array_keys( wc_get_order_statuses() ),
            'fields' => 'ids',
            'posts_per_page' => 20,
            'date_query' => array(
                array(
                    'after' => gmdate( 'Y-m-d H:i:s', $threshold_time ),
                    'inclusive' => true,
                ),
            ),
            'meta_query' => $meta_query,
        ));

        if ( empty( $order_ids ) ) {
            return false;
        }

        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( $order_id );

            if ( ! $order ) {
                continue;
            }

            foreach ( $order->get_items() as $item ) {
                $product_id = (int) $item->get_product_id();

                if ( $product_id && in_array( $product_id, $cart_product_ids, true ) ) {
                    self::cancel_scheduled_follow_up_events( $cart_id );

                    if ( self::$debug_mode ) {
                        error_log( sprintf( 'Follow-ups canceled for cart %d due to order %d after 24h.', $cart_id, $order_id ) );
                    }

                    return true;
                }
            }
        }

        return false;
    }
}