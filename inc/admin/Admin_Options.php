<?php

namespace MeuMouse\Flexify_Checkout\Admin;

use MeuMouse\Flexify_Checkout\Core\Helpers;
use MeuMouse\Flexify_Checkout\API\License;
use MeuMouse\Flexify_Checkout\Checkout\Fields;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Class to handle plugin admin panel objects and functions
 * 
 * @since 1.0.0
 * @version 5.1.1
 * @package MeuMouse.com
 */
class Admin_Options {

    /**
     * Construct function
     *
     * @since 1.0.0
     * @version 5.1.0
     * @return void
     */
    public function __construct() {
        // handle for billing country admin notice
        add_action( 'woocommerce_checkout_init', array( __CLASS__, 'check_billing_country_field' ) );
        add_action( 'admin_notices', array( __CLASS__, 'show_billing_country_warning' ) );
        add_action( 'admin_footer', array( __CLASS__, 'dismiss_billing_country_warning_script' ) );

        // display notice when not has [woocommerce_checkout] shortcode
        add_action( 'admin_notices', array( __CLASS__, 'check_for_checkout_shortcode' ) );

        // display notice when not has PHP gd extension
        add_action( 'admin_notices', array( __CLASS__, 'missing_gd_extension_notice' ) );
    }


    /**
     * Gets the items from the array and inserts them into the option if it is empty,
     * or adds new items with default value to the option
     * 
     * @since 2.3.0
     * @version 5.1.0
     * @return void
     */
    public function set_default_options() {
        $default_options = ( new Default_Options() )->set_default_data_options();
        $get_options = get_option('flexify_checkout_settings', array());

        // Complement missing settings without overwriting existing ones.
        $merged_options = wp_parse_args( $get_options, $default_options );

        // Only save if the merged result differs from the stored options to avoid unnecessary writes.
        if ( $get_options !== $merged_options ) {
            update_option( 'flexify_checkout_settings', $merged_options );
        }
    }


    /**
     * Set default options checkout fields
     * 
     * @since 3.0.0
     * @version 5.1.1
     * @return void
     */
    public function set_checkout_step_fields() {
        $default_options = new Default_Options();
        $get_fields = $default_options->get_native_checkout_fields();
        $get_field_options = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );

        // Merge existing field options with defaults to fill in missing entries.
        $merged_fields = wp_parse_args( $get_field_options, $get_fields );

        // add brazilian market fields if base country is Brazil
        if ( class_exists('Extra_Checkout_Fields_For_Brazil') || Fields::get_base_country() === 'BR' ) {
            // Add Brazilian Market on WooCommerce fields to existing options.
            $wcbcf_fields = $default_options->get_brazilian_checkout_fields();
            $merged_fields = array_merge( $merged_fields, $wcbcf_fields );
        }

        // Update only when the final array differs from what is stored to avoid unnecessary writes.
        if ( $get_field_options !== $merged_fields ) {
            update_option( 'flexify_checkout_step_fields', maybe_serialize( $merged_fields ) );
        }
    }


    /**
     * Checks if the option exists and returns the indicated array item
     * 
     * @since 1.0.0
     * @version 5.1.0
     * @param $key | Array key
     * @return mixed | string or false
     */
    public static function get_setting( $key ) {
        $options  = get_option( 'flexify_checkout_settings', array() );
        $defaults = ( new Default_Options() )->set_default_data_options();
        $options  = wp_parse_args( $options, $defaults );

        // check if array key exists and return key
        if ( isset( $options[$key] ) ) {
            return $options[$key];
        }

        return false;
    }


    /**
     * Check if billing country is disabled on checkout
     * 
     * @since 3.7.3
     * @return void
     */
    public static function check_billing_country_field() {
        $checkout_fields = WC()->checkout()->get_checkout_fields();
        $is_disabled = empty( $checkout_fields['billing']['billing_country'] ) || $checkout_fields['billing']['billing_country']['required'] === false;

        update_option( 'billing_country_field_disabled', $is_disabled );
    }


    /**
     * Display admin notice when billing country field is disabled
    * 
    * @since 3.7.3
    * @return void
    */
    public static function show_billing_country_warning() {
        $is_disabled = get_option('billing_country_field_disabled');
        $hide_notice = get_user_meta( get_current_user_id(), 'hide_billing_country_notice', true );

        if ( $is_disabled && ! $hide_notice ) {
            $class = 'notice notice-error is-dismissible';
            $message = esc_html__( 'O campo País na finalização de compras está desativado, verifique se seu gateway de pagamentos depende deste campo para não receber o erro "Informe um endereço para continuar com sua compra."', 'flexify-checkout-for-woocommerce' );
            
            printf( '<div id="billing-country-warning" class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
        }
    }


    /**
     * Send action on dismiss notice for not display
     * 
     * @since 3.7.3
     * @return void
     */
    public static function dismiss_billing_country_warning_script() {
        ?>
        <script type="text/javascript">
            jQuery(document).on('click', '#billing-country-warning .notice-dismiss', function() {
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dismiss_billing_country_warning',
                    }
                });
            });
        </script>
        <?php
    }


    /**
	 * Display error message on WooCommerce checkout page if shortcode is missing
	 * 
	 * @since 4.5.0
	 * @return void
	 */
	public static function check_for_checkout_shortcode() {
		if ( ! Helpers::has_shortcode_checkout() ) {
			$class = 'notice notice-error is-dismissible';
			$message = __( 'O Flexify Checkout depende do shortcode [woocommerce_checkout] na página de finalização de compras para funcionar corretamente.', 'flexify-checkout-for-woocommerce' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
		}
	}
  

    /**
	 * Display error message when PHP extensionn gd is missing
	 * 
	 * @since 4.5.0
	 * @return void
	 */
	public static function missing_gd_extension_notice() {
		if ( ! extension_loaded('gd') && Admin_Options::get_setting('enable_inter_bank_pix_api') === 'yes' ) {
			$class = 'notice notice-error is-dismissible';
			$message = __( 'A extensão GD está desativada, e é necessária para gerar o QR Code do Pix. Ative-a em sua hospedagem para habilitar esse recurso.', 'flexify-checkout-for-woocommerce' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
		}
	}
}