<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Core;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handler with WooCommerce coupons
 * 
 * @since 1.0.0
 * @version 1.3.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Core
 * @author MeuMouse.com
 */
class Coupons {

    /**
     * Generate WooCommerce discount coupon
     *
     * @since 1.0.0
     * @version 1.3.0
     * @param array $coupon_data | Coupon settings data
     * @param int $cart_id | The recovery cart post ID
     * @return mixed int|WP_Error Coupon ID or error
     */
    public static function generate_wc_coupon( $coupon_data, $cart_id ) {
        if ( empty( $coupon_data ) || ! isset( $coupon_data['discount_type'], $coupon_data['discount_value'] ) ) {
            error_log( 'Coupon data is empty or missing required fields.' );
            return new \WP_Error( 'missing_data', __( 'Dados insuficientes para criar o cupom.', 'fc-recovery-carts' ) );
        }

        $discount_value = floatval( $coupon_data['discount_value'] );

        // Check if discount value is valid
        if ( $discount_value <= 0 ) {
            return new \WP_Error(
                'invalid_discount_value',
                __( 'O valor do desconto deve ser maior que zero para gerar um cupom.', 'fc-recovery-carts' )
            );
        }
    
        // Get prefix
        $coupon_prefix = $coupon_data['coupon_prefix'];
    
        // Generate coupon code
        $coupon_code = ( isset( $coupon_data['generate_coupon'] ) && $coupon_data['generate_coupon'] === 'yes' ) ? strtoupper( $coupon_prefix . wp_generate_password( 6, false ) ) : '';
    
        // Check if coupon already exists
        $query = new \WP_Query( array(
            'post_type' => 'shop_coupon',
            'title' => $coupon_code,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
        ));

        if ( $query->have_posts() ) {
            error_log( 'Coupon already exists.' );
            
            return new \WP_Error( 'duplicate_coupon', __( 'O cupom já existe.', 'fc-recovery-carts' ) );
        }

        // reset query
        wp_reset_postdata();
    
        // Create WooCommerce coupon object
        $coupon = new \WC_Coupon();
        $coupon->set_code( $coupon_code );
        $coupon->set_discount_type( $coupon_data['discount_type'] );
        $coupon->set_amount( floatval( $coupon_data['discount_value'] ) );
        $coupon->set_free_shipping( isset( $coupon_data['allow_free_shipping'] ) && $coupon_data['allow_free_shipping'] === 'yes' );
    
        // Determine expiration time
        $current_time = strtotime( current_time('mysql') );
        $expiry_seconds = ! empty( $coupon_data['expiration_time'] ) ? Helpers::convert_to_seconds( $coupon_data['expiration_time'], $coupon_data['expiration_time_unit'] ) : 0;
    
        // Adjust expiration logic
        if ( $expiry_seconds < DAY_IN_SECONDS ) {
            $expiry_timestamp = strtotime('tomorrow', $current_time);
        } else {
            $expiry_timestamp = $current_time + $expiry_seconds;
        }
    
        // Set expiration date
        $coupon->set_date_expires( $expiry_timestamp );
    
        // Set usage limits
        if ( ! empty( $coupon_data['limit_usages'] ) ) {
            $coupon->set_usage_limit( $coupon_data['limit_usages'] );
        }
        if ( ! empty( $coupon_data['limit_usages_per_user'] ) ) {
            $coupon->set_usage_limit_per_user( $coupon_data['limit_usages_per_user'] );
        }
    
        // Save coupon
        $coupon->save();
    
        // Store coupon metadata
        update_post_meta( $cart_id, '_fcrc_coupon_id', $coupon->get_id() );
        update_post_meta( $cart_id, '_fcrc_coupon_code', $coupon_code );
        update_post_meta( $cart_id, '_fcrc_coupon_expiration_date', $expiry_timestamp );
    
        // Schedule an event to update expiration to the previous day
        if ( ! empty( $coupon_data['expiration_time'] ) ) {
            wp_schedule_single_event( $expiry_timestamp, 'fcrc_delete_coupon_on_expiration', array( $coupon->get_id() ) );
        }

        if (  FC_RECOVERY_CARTS_DEBUG_MODE ) {
            error_log( 'Coupon ID: ' . $coupon->get_id() );
            error_log( 'Coupon generated: ' . $coupon_code );
            error_log( 'Coupon expiration date: ' . $expiry_timestamp );
        }
    
        return array(
            'coupon_id' => $coupon->get_id(),
            'coupon_code' => $coupon_code,
            'expiration_date' => $expiry_timestamp,
        );
    }
    

    /**
     * Delete coupon post on expiration
     *
     * @since 1.0.0
     * @version 1.2.0
     * @param int $coupon_id | The WooCommerce coupon ID
     * @return void
     */
    public static function delete_coupon_on_expiration( $coupon_id ) {
        if ( ! $coupon_id ) {
            return;
        }
    
        $coupon_post = get_post( $coupon_id );
    
        if ( ! $coupon_post || $coupon_post->post_type !== 'shop_coupon' ) {
            if ( FC_RECOVERY_CARTS_DEBUG_MODE ) {
                error_log( "Coupon {$coupon_id} not found or is not a valid shop_coupon." );
            }
            return;
        }
    
        // Delete the coupon post
        wp_delete_post( $coupon_id, true );
    
        if ( FC_RECOVERY_CARTS_DEBUG_MODE ) {
            error_log( "Coupon {$coupon_id} deleted after expiration." );
        }
    }
}