<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Core;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Coupons;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handle with replacement text placeholders
 * 
 * @since 1.0.0
 * @version 1.3.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Core
 * @author MeuMouse.com
 */
class Placeholders {

    /**
     * Register available message placeholders
     *
     * @since 1.0.0
     * @version 1.3.0
     * @return array
     */
    public static function register_placeholders() {
        /**
         * Allow third parties to add or modify placeholders
         *
         * @since 1.3.0
         * @param array | Array of placeholders
         */
        return apply_filters( 'Flexify_Checkout/Recovery_Carts/Register_Placeholders', array(
            '{{ first_name }}' => array(
                'title' => esc_html__( 'Primeiro nome', 'fc-recovery-carts' ),
                'callback' => function( $cart_id, $event ) {
                    $fallback = Admin::get_setting( 'fallback_first_name' );
                    $cart_data = get_post_meta( $cart_id );

                    return $cart_data['_fcrc_first_name'][0] ?? $fallback;
                },
            ),
            '{{ last_name }}' => array(
                'title' => esc_html__( 'Sobrenome', 'fc-recovery-carts' ),
                'callback' => function( $cart_id, $event ) {
                    $cart_data = get_post_meta( $cart_id );

                    return $cart_data['_fcrc_last_name'][0] ?? '';
                },
            ),
            '{{ recovery_link }}' => array(
                'title' => esc_html__( 'Link de recuperação do carrinho', 'fc-recovery-carts' ),
                'callback' => function( $cart_id, $event ) {
                    return Helpers::generate_recovery_cart_link( $cart_id );
                },
            ),
            '{{ coupon_code }}' => array(
                'title' => esc_html__( 'Código do cupom', 'fc-recovery-carts' ),
                'callback' => function( $cart_id, $event ) {
                    if ( isset( $event['coupon']['generate_coupon'] ) && $event['coupon']['generate_coupon'] === 'yes' ) {
                        // generate coupon code and save on cart post meta
                        Coupons::generate_wc_coupon( $event['coupon'], $cart_id );

                        return get_post_meta( $cart_id, '_fcrc_coupon_code', true );
                    }

                    return $event['coupon']['coupon_code'] ?? '';
                },
            ),
            '{{ products_list }}' => array(
                'title' => esc_html__( 'Lista de produtos no carrinho ou pedido, separados por vírgula', 'fc-recovery-carts' ),
                'callback' => function( $cart_id, $event ) {
                    // retrieve products list
                    $items = get_post_meta( $cart_id, '_fcrc_cart_items', true );

                    if ( ! is_array( $items ) ) {
                        return '';
                    }

                    // get only names
                    $names = array_map(
                        function( $item ) {
                            return $item['name'] ?? '';
                        },

                        $items
                    );

                    // filter empty values and join by comma
                    $names = array_filter( $names );

                    return implode( ', ', $names );
                },
            ),
            '{{ cart_total }}' => array(
                'title' => esc_html__( 'Valor total do carrinho', 'fc-recovery-carts' ),
                'callback' => function( $cart_id, $event ) {
                    $total = (float) get_post_meta( $cart_id, '_fcrc_cart_total', true );

                    if ( ! $total ) {
                        return '';
                    }

                    if ( function_exists('wc_price') ) {
                        return html_entity_decode( wp_strip_all_tags( wc_price( $total ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                    }

                    return $total;
                },
            ),
        ));
    }


    /**
     * Replace placeholders in a message with actual values
     *
     * @since 1.0.0
     * @version 1.3.0
     * @param string $message | The message containing placeholders
     * @param int $cart_id | The cart ID
     * @param array $event | The follow-up event settings (including coupon config)
     * @return string The processed message
     */
    public static function replace_placeholders( $message, $cart_id, $event = array() ) {
        $placeholders = self::register_placeholders();

        foreach ( $placeholders as $key => $data ) {
            if ( isset( $data['callback'] ) && is_callable( $data['callback'] ) ) {
                $value = call_user_func( $data['callback'], $cart_id, $event );
                $message = str_replace( $key, $value, $message );
            }
        }

        return $message;
    }
}