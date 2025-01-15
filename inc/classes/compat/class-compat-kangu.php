<?php

namespace MeuMouse\Flexify_Checkout\Compat;
use MeuMouse\Flexify_Checkout\Init;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with Kangu shipping gateway
 *
 * @since 3.3.0
 * @version 3.8.0
 * @package MeuMouse.com
 */
class Compat_Kangu {

    /**
     * Construct function
     *
     * @since 3.3.0
     * @version 3.7.4
     * @return void
     */
    public function __construct() {
        add_action( 'init', array( $this, 'compat_kangu' ) );
    }


    /**
     * Add compatibility with Kangu shipping gateway on init hook
     * 
     * @since 3.7.4
     * @return void
     */
    public function compat_kangu() {
        if ( ! class_exists('KanguShipping') || ! is_flexify_checkout() ) {
			return;
		}

        add_action( 'wp_footer', function() {
            wp_dequeue_script('kangu-cart');
        }, 11 );

        add_filter( 'woocommerce_package_rates', array( $this, 'fix_kangu_shipping_methods' ), 10, 2 );

        remove_filters_with_method_name( 'woocommerce_cart_totals_after_shipping', 'get_shippings_to_cart', 10 );
        remove_filters_with_method_name( 'woocommerce_review_order_before_order_total', 'get_shippings_to_cart', 10 );

        add_action( 'woocommerce_after_shipping_rate', array( $this, 'add_extra_shipping_details' ), 10, 1 );

        if ( Init::get_setting( 'enable_display_local_pickup_kangu' ) === 'yes' ) {
            add_action( 'woocommerce_after_shipping_rate', array( $this, 'display_local_pickup_kangu' ) );
        }

        add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'format_names_for_kangu_shipping_method' ), 10, 2 );
    }


    /**
     * Reorder delivery methods using the following priority:
     * 1 native methods from WooCommerce or other plugins
     * 2 Kangu delivery
     * 3 Kangu withdrawal points
     * 
     * @since 3.3.0
     * @param array $rates    Package rates.
     * @param array $package  Package of cart items.
     * @return array
     */
    public function fix_kangu_shipping_methods( $rates, $package ) {
        $normal = $kangu = $kangu_r = [];
        
        foreach ( $rates as $rate_key => $rate ) {
            $meta = $rate->get_meta_data();

            if ( isset( $meta['service'] ) ) {
                if ( $rate->meta_data['service'] == 'R' ) {
                    $kangu_r[ $rate_key ] = $rate;
                } else {
                    $kangu[ $rate_key ] = $rate;
                }
            } else {
                $normal[ $rate_key ] = $rate;
            }
        }
        
        return array_merge( $normal, $kangu, $kangu_r );
    }

    
    /**
     * View additional information for Kangu methods.
     * For all, add the delivery date block.
     * For pick-up points, display a block with the address and other information.
     * 
     * @since 3.3.0
     * @param object $method  Shipping method.
     * @return void
     */
    public function add_extra_shipping_details( $method ) {
        $meta = $method->get_meta_data();
    
        if ( isset( $meta['point_address'] ) ) {
            printf( 
                '<div class="point-address"><div class="name">(Ponto Kangu) %s</div><div class="address">%s</div><div class="distance">%s</div></div>', 
                esc_html( $method->meta_data['point_label'] ),
                esc_html( $method->meta_data['point_address'] ),
                esc_html( $method->meta_data['point_distance'] )
            );
        }
        
        if ( isset( $meta['deadline'] ) ) {
            printf( '<p><small>%s</small></p>', esc_html( $method->meta_data['deadline'] ) );
        }
    }

    /**
     * Add in-store pickup address.
     * 
     * @since 3.3.0
     * @param object $method Shipping method.
     * @return void
     */
    public function display_local_pickup_kangu( $method ) {
        if ( $method->method_id == 'local_pickup' ) {
            printf(
                '<div class="point-address"><div class="address">%s %s, %s, %s</div></div>',
                get_option( 'woocommerce_store_address' ),
                get_option( 'woocommerce_store_address_2' ),
                get_option( 'woocommerce_store_postcode' ),
                get_option( 'woocommerce_store_city' )
            );
        }
    }


    /**
     * Format name of Kangu delivery methods.
     * 
     * @since 3.3.0
     * @param string $label Shipping label.
     * @param string $method Shipping method.
     * @return string
     */
    public function format_names_for_kangu_shipping_method( $label, $method ) {
        // The 'service' meta only exists for Kangu
        $meta = $method->get_meta_data();

        if ( isset( $meta['service'] ) ) {
            switch ( $method->meta_data['service'] ) {
                case 'X': // X = Correios e Carbono Zero
                case 'M': // M = Mini Envios
                    $name = str_replace(
                        [' via Kangu', 'Correios Sedex'],
                        ['', 'Sedex via Kangu'],
                        $method->meta_data['shipping_label']
                    );

                    $label = sprintf( '<span class="method-name">%s:</span> %s', $name, wc_price( $method->cost ) );
                    break;
                case 'E': // E = Transportadoras
                    $label = sprintf( '<span class="method-name">%s:</span> %s', 
                                      str_replace(
                                          [' via Kangu', 'Correios PAC'], 
                                          ['', 'PAC via Kangu'], 
                                          $method->meta_data['shipping_label']
                                      ), wc_price( $method->cost ) );
                    break;
                case 'R': // R = Pontos de Retirada
                    $label = sprintf( '<span class="method-name">Retirar em:</span> %s', wc_price( $method->cost ) );
                    break;
                default:
                    $label = str_replace(' via Kangu', '', $method->meta_data['shipping_label']);
                    break;
            }
        } else {
            $label = sprintf( '<span class="method-name">%s:</span> %s', $method->label, wc_price( $method->cost ) );
        }

        return $label;
    }
}

new Compat_Kangu();