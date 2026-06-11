<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Core;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Cron\Recovery_Handler;

use WC;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handles cart recovery events, such as tracking and updating cart data
 *
 * @since 1.0.0
 * @version 1.4.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Core
 * @author MeuMouse.com
 */
class Cart_Events {

    /**
     * Get debug mode
     * 
     * @since 1.3.0
     * @return bool
     */
    public static $debug_mode = FC_RECOVERY_CARTS_DEBUG_MODE;

    /**
     * Constructor function
     *
     * @since 1.0.0
     * @version 1.1.0
     * @return void
     */
    public function __construct() {
        // Listen to cart changes
        add_action( 'woocommerce_add_to_cart', array( $this, 'update_cart_post' ), 10, 6 );
        add_action( 'woocommerce_cart_item_removed', array( $this, 'update_cart_post_on_change' ), 10, 2 );
        add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'update_cart_post_on_change' ), 10, 2 );

        // update last modified cart time
        add_action( 'woocommerce_cart_updated', array( $this, 'update_last_modified_cart_time' ) );

        add_action( 'wp_trash_post', array( $this, 'handle_cart_deletion' ), 10, 1 );
        add_action( 'before_delete_post', array( $this, 'handle_cart_deletion' ), 10, 1 );
    }


    /**
     * Updates the cart post when a product is added
     *
     * @since 1.0.0
     * @version 1.3.5
     * @param string    $cart_id | The cart item key
     * @param integer   $product_id | The ID of the product added to the cart
     * @param integer   $request_quantity | The quantity of the item added to the cart
     * @param integer   $variation_id | The variation ID (if applicable)
     * @param array     $variation | The variation data
     * @param array     $cart_item_data | Additional cart item data
     *
     * @return void
     */
    public function update_cart_post( $cart_id, $product_id, $request_quantity, $variation_id, $variation, $cart_item_data ) {
        // Check if we're in recovery mode — keep the flag set so it covers
        // all items in the restore loop. Helpers::maybe_restore_cart clears
        // the flag after finishing the loop.
        if ( function_exists('WC') && WC()->session instanceof \WC_Session && WC()->session->get('fcrc_cart_recovery_mode') ) {
            if ( self::$debug_mode ) {
                error_log( '[Cart_Events] Recovery mode detected on add_to_cart (product=' . $product_id . '). Skipping cart update.' );
            }

            return;
        }

        $cart_id = Helpers::get_current_cart_id();

        if ( self::$debug_mode ) {
            error_log( '[Cart_Events] update_cart_post called. Current cart ID: ' . $cart_id );
            error_log( '[Cart_Events] Product added: ' . $product_id . ', Quantity: ' . $request_quantity );
        }

        // check if cycle has already finished
        if ( $cart_id && Helpers::is_cart_cycle_finished( $cart_id ) ) {
            if ( self::$debug_mode ) {
                error_log( '[Cart_Events] Cart already finished. Skipping cart update. ID: ' . $cart_id );
            }

            return;
        }

        if ( ! $cart_id ) {
            if ( self::$debug_mode ) {
                error_log( '[Cart_Events] No cart ID found. Calling create_cart_post()' );
            }

            $create_cart_id = self::create_cart_post();

            if ( self::$debug_mode ) {
                error_log( '[Cart_Events] create_cart_post() returned: ' . ($create_cart_id ? $create_cart_id : 'null') );
            }
        }

        $get_cart_id = isset( $create_cart_id ) ? $create_cart_id : $cart_id;

        if ( self::$debug_mode ) {
            error_log( '[Cart_Events] Final cart ID for sync: ' . $get_cart_id );
        }

        self::sync_cart_with_post( $get_cart_id );
    }


    /**
     * Creates a new cart post if none exists
     * 
     * @since 1.1.0
     * @version 1.4.0
     * @return int $cart_id | The cart ID
     */
    public static function create_cart_post() {
        if ( self::$debug_mode ) {
            error_log( '[Cart_Events] create_cart_post() called' );
        }
        
        // stop processing if the cart already exists, admin requests or if cart is empty
        if ( is_admin() ) {
            if ( self::$debug_mode ) {
                error_log( '[Cart_Events] Admin area detected. Skipping.' );
            }

            return;
        }
        
        if ( Helpers::is_cart_cycle_finished() ) {
            if ( self::$debug_mode ) {
                error_log( '[Cart_Events] Cart cycle finished detected. Skipping.' );
            }

            return;
        }
        
        if ( WC()->cart->is_empty() ) {
            if ( self::$debug_mode ) {
                error_log( '[Cart_Events] Cart is empty. Skipping.' );
            }

            return;
        }
        
        // Check if cart already exists in session
        if ( function_exists('WC') && WC()->session instanceof \WC_Session ) {
            $session_cart_id = WC()->session->get('fcrc_cart_id');
            
            if ( self::$debug_mode ) {
                error_log( '[Cart_Events] Session cart ID check: ' . ($session_cart_id ? $session_cart_id : 'null') );
            }
            
            if ( $session_cart_id ) {
                $session_cart_status = get_post_status( $session_cart_id );
                
                if ( self::$debug_mode ) {
                    error_log( '[Cart_Events] Session cart status: ' . ($session_cart_status ? $session_cart_status : 'not found') );
                }
                
                // If session cart is still active, use it
                if ( in_array( $session_cart_status, array( 'shopping', 'abandoned' ), true ) ) {
                    if ( self::$debug_mode ) {
                        error_log('[Cart_Events] Cart already exists in session. Skipping cart creation. ID: ' . $session_cart_id );
                    }

                    return null;
                } else {
                    if ( self::$debug_mode ) {
                        error_log( '[Cart_Events] Session cart exists but status is not active: ' . $session_cart_status );
                    }
                }
            }
        }

        // Determine client IP (cookie fallback → REMOTE_ADDR)
        $client_ip = Helpers::get_client_ip();

        if ( self::$debug_mode ) {
            error_log( '[Cart_Events] Client IP: ' . $client_ip );
        }

        // Clean up old lost carts for this IP
        Helpers::cleanup_old_lost_carts( $client_ip );

        // If IP has an existing cart in lost/recovered/purchased state, skip
        if ( $client_ip ) {
            if ( self::$debug_mode ) {
                error_log( '[Cart_Events] Checking for ACTIVE carts for IP: ' . $client_ip );
            }
            
            $existing = get_posts( array(
                'post_type'      => 'fc-recovery-carts',
                'post_status'    => array( 'shopping', 'abandoned' ),
                'meta_key'       => '_fcrc_location_ip',
                'meta_value'     => $client_ip,
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'     => '_fcrc_cart_updated_time',
                        'value'   => current_time('timestamp', true) - (7 * 24 * 60 * 60), // last 7 days
                        'compare' => '>',
                        'type'    => 'NUMERIC',
                    ),
                ),
            ));

            if ( ! empty( $existing ) ) {
                $existing_id = $existing[0];
                $existing_status = get_post_status( $existing_id );
                $existing_time = get_post_meta( $existing_id, '_fcrc_cart_updated_time', true );
                
                if ( self::$debug_mode ) {
                    error_log( '[Cart_Events] ACTIVE cart found for IP ' . $client_ip . 
                            '. ID: ' . $existing_id . 
                            ', Status: ' . $existing_status . 
                            ', Last updated: ' . date('Y-m-d H:i:s', $existing_time) .
                            '. Skipping creation.' );
                }

                return null;
            } else {
                if ( self::$debug_mode ) {
                    error_log( '[Cart_Events] No ACTIVE carts found for IP: ' . $client_ip );
                    
                    $finished_carts = get_posts( array(
                        'post_type'      => 'fc-recovery-carts',
                        'post_status'    => array( 'lost', 'recovered', 'purchased', 'completed', 'order_abandoned' ),
                        'meta_key'       => '_fcrc_location_ip',
                        'meta_value'     => $client_ip,
                        'posts_per_page' => 1,
                        'fields'         => 'ids',
                    ));
                    
                    if ( ! empty( $finished_carts ) ) {
                        $finished_id = $finished_carts[0];
                        $finished_status = get_post_status( $finished_id );
                        
                        error_log( '[Cart_Events] Found FINISHED cart for same IP: ID=' . $finished_id . ', Status=' . $finished_status . ' - This will NOT block new cart creation.' );
                    }
                }
            }
        } else {
            if ( self::$debug_mode ) {
                error_log( '[Cart_Events] No client IP found' );
            }
        }

        // Build cart items array and total
        $cart_items_data = WC()->cart->get_cart();
        
        if ( self::$debug_mode ) {
            error_log( '[Cart_Events] Cart items count: ' . count($cart_items_data) );
        }
        
        $cart_items = array();
        $cart_total = 0;

        foreach ( $cart_items_data as $cart_item_key => $cart_item ) {
            $product = wc_get_product( $cart_item['product_id'] );

            if ( ! $product ) {
                continue;
            }

            $product_id = (int) $cart_item['product_id'];
            $variation_id = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;
            $quantity = (int) $cart_item['quantity'];
            $price = floatval( $product->get_price() );
            $total_price = $quantity * $price;
            $cart_total += $total_price;

            $cart_item_identifier = ! empty( $cart_item_key ) ? $cart_item_key : sprintf( '%d_%d', $product_id, $variation_id );

            $cart_items[ $cart_item_identifier ] = array(
                'product_id' => $product_id,
				'variation_id' => $variation_id,
                'variation' => isset( $cart_item['variation'] ) && is_array( $cart_item['variation'] ) ? $cart_item['variation'] : array(),
                'quantity' => $quantity,
                'price' => $price,
                'total' => $total_price,
                'name' => $product->get_name(),
                'image' => get_the_post_thumbnail_url( $product_id, 'thumbnail' ),
            );
        }

        // get cached location data
        $location = isset( $_COOKIE['fcrc_location'] ) ? json_decode( stripslashes( $_COOKIE['fcrc_location'] ), true ) : array();
        $contact = Helpers::get_cart_contact_data();
        $current_time = current_time( 'timestamp', true );

        if ( self::$debug_mode ) {
            error_log( '[Cart_Events] Location data: ' . print_r($location, true) );
            error_log( '[Cart_Events] Contact data: ' . print_r($contact, true) );
        }

        // Create new cart post
        $cart_id = wp_insert_post( array(
            'post_type' => 'fc-recovery-carts',
            'post_status' => 'shopping',
            'post_title' => sprintf( __( 'Novo carrinho - %s', 'fc-recovery-carts' ), $current_time ),
            'meta_input' => array(
                '_fcrc_cart_items' => $cart_items,
                '_fcrc_cart_total' => $cart_total,
                '_fcrc_cart_updated_time' => $current_time,
                '_fcrc_abandoned_time' => '',
                '_fcrc_first_name' => $contact['first_name'] ?? '',
                '_fcrc_last_name' => $contact['last_name']  ?? '',
                '_fcrc_full_name' => sprintf( '%s %s', $contact['first_name'] ?? '', $contact['last_name'] ?? '' ),
                '_fcrc_cart_phone' => apply_filters( 'Flexify_Checkout/Recovery_Carts/Contact_Phone', $contact['phone'] ?? '' ),
                '_fcrc_cart_email' => $contact['email']    ?? '',
                '_fcrc_location_city' => $location['city'] ?? '',
                '_fcrc_location_state' => $location['region'] ?? '',
                '_fcrc_location_country_code' => $location['country_code'] ?? '',
                '_fcrc_location_ip' => $location['ip'] ?? '',
            ),
        ));

        if ( is_wp_error( $cart_id ) ) {
            if ( self::$debug_mode ) {
                error_log( '[Cart_Events] Error creating cart post: ' . $cart_id->get_error_message() );
            }

            return null;
        }

        // Store cart ID in session
        WC()->session->set( 'fcrc_cart_id', $cart_id );

        // Store cart ID in cookie
        setcookie( 'fcrc_cart_id', $cart_id, $current_time + ( 7 * 24 * 60 * 60 ), COOKIEPATH, COOKIE_DOMAIN ); // Expires in 7 days

        // set flag for prevent duplicate carts
        WC()->session->set( 'fcrc_active_cart', true );

        /**
         * Fires when a new cart is created
         * 
         * @since 1.0.1
         * @param int $cart_id | The cart ID
         */
        do_action( 'Flexify_Checkout/Recovery_Carts/New_Cart_Created', $cart_id );

        if ( self::$debug_mode ) {
            error_log( "[Cart_Events] New cart created: " . $cart_id );
        }

        return $cart_id;
    }


    /**
     * Updates the cart post when an item is removed or its quantity is updated.
     *
     * @since 1.0.0
     * @param string $cart_item_key | The cart item key
     * @param array  $cart | The cart object
     *
     * @return void
     */
    public function update_cart_post_on_change( $cart_item_key, $cart ) {
        self::sync_cart_with_post();
    }


    /**
     * Synchronizes WooCommerce cart data with the recovery cart post
     *
     * @since 1.0.0
     * @version 1.4.0
     * @param string $cart_id | The cart ID
     * @return void
     */
    public static function sync_cart_with_post( $cart_id = null ) {
        if ( is_admin() || WC()->cart->is_empty() ) {
            return;
        }

        $cart_id = ! empty( $cart_id ) ? $cart_id : Helpers::get_current_cart_id();

        if ( $cart_id && get_post_type( $cart_id ) !== 'fc-recovery-carts' ) {
            Helpers::clear_active_cart();
    
            if ( self::$debug_mode ) {
                error_log( 'Invalid cart reference found. Session cleared.' );
            }
        }

        // check if is a cart completed
        if ( Helpers::is_cart_cycle_finished() ) {
            Helpers::clear_active_cart();

            if ( self::$debug_mode ) {
                error_log( 'Cart completed detected — do not create new cart post.' );
            }

            return;
        } else {
            Recovery_Handler::detect_cart_recovery();
        }

        // if there is no cart ID, create a new one
        if ( ! $cart_id ) {
            self::create_cart_post();
        }

        $has_post = get_post( $cart_id );
        $cart_status = get_post_status( $cart_id );

        // if there is no cart post or is status recovered, lost or purchased, then create a new one
        if ( ! $has_post || in_array( $cart_status, array( 'recovered', 'lost', 'purchased' ), true ) ) {
            self::create_cart_post();
        }

        // Fetch contact data from various sources (including IP fallback)
        $contact = Helpers::get_cart_contact_data();

        /**
         * Update contact phone
         * 
         * @since 1.0.1
         * @version 1.3.0
         * @param string $phone | The phone number
         */
        $phone = apply_filters( 'Flexify_Checkout/Recovery_Carts/Contact_Phone', $contact['phone'] ?? '' );

        // Prepare meta values to update
        $meta = array(
            '_fcrc_first_name' => $contact['first_name'] ?? '',
            '_fcrc_last_name' => $contact['last_name']  ?? '',
            '_fcrc_full_name' => sprintf( '%s %s', $contact['first_name'] ?? '', $contact['last_name'] ?? '' ),
            '_fcrc_cart_phone' => $phone,
            '_fcrc_cart_email' => $contact['email'] ?? '',
        );

        // Update only non-empty values
        foreach ( $meta as $key => $value ) {
            if ( $value !== '' ) {
                update_post_meta( $cart_id, $key, sanitize_text_field( $value ) );
            }
        }

        // get cached location data
        $location = isset( $_COOKIE['fcrc_location'] ) ? json_decode( stripslashes( $_COOKIE['fcrc_location'] ), true ) : array();

        // has location data
        if ( ! empty( $location ) ) {
            if ( ! empty( $location['city'] ) ) {
                update_post_meta( $cart_id, '_fcrc_location_city', $location['city'] ?? '' );
            }

            if ( ! empty( $location['region'] ) ) {
                update_post_meta( $cart_id, '_fcrc_location_state', $location['region'] ?? '' );
            }

            if ( ! empty( $location['country_code'] ) ) {
                update_post_meta( $cart_id, '_fcrc_location_country_code', $location['country_code'] ?? '' );
            }

            if ( ! empty( $location['ip'] ) ) {
                update_post_meta( $cart_id, '_fcrc_location_ip', $location['ip'] ?? '' );
            }
        }

        // get IP address
        $ip = $location['ip'] ?? '';
        $current_time = current_time( 'timestamp', true );

        // map user by IP
        if ( $ip ) {
            $map = get_option( 'fcrc_ip_user_map', array() );

            $map[ $ip ] = array(
                'first_name' => $contact['first_name'] ?? '',
                'last_name' => $contact['last_name']  ?? '',
                'full_name' => sprintf( '%s %s', $contact['first_name'] ?? '', $contact['last_name'] ?? '' ),
                'phone' => $phone,
                'email' => $contact['email'] ?? '',
                'cart_id' => $cart_id,
                'collected_at' => $current_time,
            );

            // save user mapped
            update_option( 'fcrc_ip_user_map', $map );
        }

        // Get WooCommerce cart contents
        $cart_items_data = WC()->cart->get_cart();
        $cart_items = array();
        $cart_total = 0;

        foreach ( $cart_items_data as $cart_item_key => $cart_item ) {
            $product = wc_get_product( $cart_item['product_id'] );

            if ( ! $product ) {
                continue;
            }

            $product_id = (int) $cart_item['product_id'];
            $variation_id = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;
            $quantity = (int) $cart_item['quantity'];
            $price = floatval( $product->get_price() );
            $total_price = $quantity * $price;
            $cart_total += $total_price;

            $cart_item_identifier = ! empty( $cart_item_key ) ? $cart_item_key : sprintf( '%d_%d', $product_id, $variation_id );

            $cart_items[ $cart_item_identifier ] = array(
                'product_id' => $product_id,
				'variation_id' => $variation_id,
                'variation' => isset( $cart_item['variation'] ) && is_array( $cart_item['variation'] ) ? $cart_item['variation'] : array(),
                'quantity' => $quantity,
                'price' => $price,
                'total' => $total_price,
                'name' => $product->get_name(),
                'image' => get_the_post_thumbnail_url( $product_id, 'thumbnail' ),
            );
        }

        // Update cart post metadata
        update_post_meta( $cart_id, '_fcrc_cart_items', $cart_items );
        update_post_meta( $cart_id, '_fcrc_cart_total', $cart_total );
        update_post_meta( $cart_id, '_fcrc_cart_updated_time', $current_time );
    }


    /**
     * Updates the cart's last modified time when it's updated
     *
     * @since 1.0.0
     * @version 1.1.0
     * @return void
     */
    public function update_last_modified_cart_time() {
        // Skip while we are restoring a recovery cart — otherwise the
        // woocommerce_cart_updated hook (fired after each add_to_cart)
        // would create a brand new cart post for the partial cart.
        if ( function_exists('WC') && WC()->session instanceof \WC_Session && WC()->session->get('fcrc_cart_recovery_mode') ) {
            if ( self::$debug_mode ) {
                error_log( '[Cart_Events] Recovery mode detected on cart_updated. Skipping sync.' );
            }

            return;
        }

        self::sync_cart_with_post();
    }


    /**
     * Handle cart deletion to cancel follow-ups
     *
     * @since 1.1.0
     * @param int $post_id | Post ID
     * @return void
     */
    public function handle_cart_deletion( $post_id ) {
        if ( get_post_type( $post_id ) !== 'fc-recovery-carts' ) {
            return;
        }

        /**
         * Fired when a cart is manually deleted
         *
         * @since 1.1.0
         * @param int $post_id
         */
        do_action( 'Flexify_Checkout/Recovery_Carts/Cart_Deleted_Manually', $post_id );
    }
}