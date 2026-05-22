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
 * @version 5.2.0
 * @package MeuMouse.com
 */
class Admin_Options {

    /**
     * Action used to create Brazilian essential fields and conditions.
     *
     * @since 5.5.0
     * @var string
     */
    const BRAZIL_ESSENTIAL_SETUP_ACTION = 'flexify_create_br_essential_fields';

    /**
     * Query arg used to display setup success notice.
     *
     * @since 5.5.0
     * @var string
     */
    const BRAZIL_ESSENTIAL_SETUP_SUCCESS_ARG = 'flexify_br_essential_fields_created';

    /**
     * Construct function
     *
     * @since 1.0.0
     * @version 5.1.0
     * @return void
     */
    public function __construct() {
        // handle action for creating brazilian essential fields and conditions
        add_action( 'admin_init', array( __CLASS__, 'handle_brazil_essential_setup_action' ) );

        // handle for billing country admin notice
        add_action( 'woocommerce_checkout_init', array( __CLASS__, 'check_billing_country_field' ) );
        add_action( 'admin_notices', array( __CLASS__, 'show_billing_country_warning' ) );
        add_action( 'admin_footer', array( __CLASS__, 'dismiss_billing_country_warning_script' ) );

        // display admin notice for creating brazilian essential fields and conditions
        add_action( 'admin_notices', array( __CLASS__, 'show_brazil_essential_setup_notice' ) );
        add_action( 'admin_notices', array( __CLASS__, 'show_brazil_essential_setup_success_notice' ) );

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
     * @version 5.2.0
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
     * Handle action for creating Brazilian essential fields and conditions.
     *
     * @since 5.5.0
     * @return void
     */
    public static function handle_brazil_essential_setup_action() {
        if ( ! is_admin() || wp_doing_ajax() || ! current_user_can('manage_options') ) {
            return;
        }

        $action = filter_input( INPUT_GET, 'flexify_action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if ( $action !== self::BRAZIL_ESSENTIAL_SETUP_ACTION ) {
            return;
        }

        $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, self::BRAZIL_ESSENTIAL_SETUP_ACTION ) ) {
            return;
        }

        if ( ! self::is_brazil_store() ) {
            return;
        }

        $fields_updated = self::ensure_brazil_essential_fields();
        $conditions_updated = self::ensure_brazil_essential_conditions();

        $redirect_url = add_query_arg( array(
            self::BRAZIL_ESSENTIAL_SETUP_SUCCESS_ARG => ( $fields_updated || $conditions_updated ) ? '1' : '0',
        ), admin_url('admin.php?page=flexify-checkout-for-woocommerce') );

        wp_safe_redirect( $redirect_url );
        exit;
    }


    /**
     * Display notice for creating Brazilian essential fields and conditions.
     *
     * @since 5.5.0
     * @return void
     */
    public static function show_brazil_essential_setup_notice() {
        if ( ! is_admin() || wp_doing_ajax() || ! current_user_can('manage_options') ) {
            return;
        }

        if ( ! self::is_brazil_store() || self::has_cpf_or_cnpj_field() ) {
            return;
        }

        $setup_url = wp_nonce_url(
            add_query_arg( array(
                'page' => 'flexify-checkout-for-woocommerce',
                'flexify_action' => self::BRAZIL_ESSENTIAL_SETUP_ACTION,
            ), admin_url('admin.php') ),
            self::BRAZIL_ESSENTIAL_SETUP_ACTION
        );

        $class = 'notice notice-warning';
        $message = esc_html__( 'Identificamos que sua loja vende no Brasil, deseja criar os campos e condições essenciais para integração com formas de pagamento e entregas nacionais?', 'flexify-checkout-for-woocommerce' );
        $link_label = esc_html__( 'Criar campos e condições essenciais', 'flexify-checkout-for-woocommerce' );

        printf( '<div class="%1$s"><p>%2$s <a href="%3$s">%4$s</a></p></div>', esc_attr( $class ), $message, esc_url( $setup_url ), $link_label );
    }


    /**
     * Display success notice after creating Brazilian essential fields and conditions.
     *
     * @since 5.5.0
     * @return void
     */
    public static function show_brazil_essential_setup_success_notice() {
        if ( ! is_admin() || wp_doing_ajax() || ! current_user_can('manage_options') ) {
            return;
        }

        if ( ! is_flexify_checkout_admin_settings() ) {
            return;
        }

        $created = filter_input( INPUT_GET, self::BRAZIL_ESSENTIAL_SETUP_SUCCESS_ARG, FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if ( $created !== '1' ) {
            return;
        }

        $class = 'notice notice-success is-dismissible';
        $message = esc_html__( 'Campos e condições essenciais para o Brasil foram criados com sucesso.', 'flexify-checkout-for-woocommerce' );

        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
    }


    /**
     * Check if store base country is Brazil.
     *
     * @since 5.5.0
     * @return bool
     */
    private static function is_brazil_store() {
        if ( function_exists('WC') && WC() && WC()->countries ) {
            return WC()->countries->get_base_country() === 'BR';
        }

        $default_country = (string) get_option( 'woocommerce_default_country', '' );
        $country_code = strtoupper( strstr( $default_country, ':', true ) ?: $default_country );

        return $country_code === 'BR';
    }


    /**
     * Check if CPF or CNPJ field already exists on step fields registry.
     *
     * @since 5.5.0
     * @return bool
     */
    private static function has_cpf_or_cnpj_field() {
        $step_fields = maybe_unserialize( get_option( 'flexify_checkout_step_fields', array() ) );

        if ( ! is_array( $step_fields ) ) {
            return false;
        }

        return isset( $step_fields['billing_cpf'] ) || isset( $step_fields['billing_cnpj'] );
    }


    /**
     * Ensure Brazilian essential fields exist in step fields registry.
     *
     * @since 5.5.0
     * @return bool
     */
    private static function ensure_brazil_essential_fields() {
        $step_fields = maybe_unserialize( get_option( 'flexify_checkout_step_fields', array() ) );
        $default_options = new Default_Options();
        $brazil_fields = $default_options->get_brazilian_checkout_fields();
        $native_fields = $default_options->get_native_checkout_fields();
        $updated = false;

        if ( ! is_array( $step_fields ) ) {
            $step_fields = array();
        }

        $required_fields = array(
            'billing_persontype',
            'billing_cpf',
            'billing_cnpj',
            'billing_ie',
            'billing_rg',
            'billing_number',
        );

        foreach ( $required_fields as $field_id ) {
            if ( ! isset( $step_fields[ $field_id ] ) && isset( $brazil_fields[ $field_id ] ) ) {
                $step_fields[ $field_id ] = $brazil_fields[ $field_id ];
                $updated = true;
            }

            if ( isset( $step_fields[ $field_id ] ) && ( ! isset( $step_fields[ $field_id ]['enabled'] ) || $step_fields[ $field_id ]['enabled'] !== 'yes' ) ) {
                $step_fields[ $field_id ]['enabled'] = 'yes';
                $updated = true;
            }
        }

        if ( ! isset( $step_fields['billing_company'] ) && isset( $native_fields['billing_company'] ) ) {
            $step_fields['billing_company'] = $native_fields['billing_company'];
            $updated = true;
        }

        if ( isset( $step_fields['billing_company'] ) && ( ! isset( $step_fields['billing_company']['enabled'] ) || $step_fields['billing_company']['enabled'] !== 'yes' ) ) {
            $step_fields['billing_company']['enabled'] = 'yes';
            $updated = true;
        }

        if ( $updated ) {
            update_option( 'flexify_checkout_step_fields', maybe_serialize( $step_fields ) );
        }

        return $updated;
    }


    /**
     * Ensure Brazilian essential conditions exist.
     *
     * @since 5.5.0
     * @return bool
     */
    private static function ensure_brazil_essential_conditions() {
        $conditions = maybe_unserialize( get_option( 'flexify_checkout_conditions', array() ) );
        $updated = false;

        if ( ! is_array( $conditions ) ) {
            $conditions = array();
        }

        $required_conditions = array(
            array(
                'type_rule' => 'show',
                'component' => 'field',
                'component_field' => 'billing_cpf',
                'verification_condition' => 'field',
                'verification_condition_field' => 'billing_persontype',
                'condition' => 'is',
                'condition_value' => '1',
            ),
            array(
                'type_rule' => 'show',
                'component' => 'field',
                'component_field' => 'billing_rg',
                'verification_condition' => 'field',
                'verification_condition_field' => 'billing_persontype',
                'condition' => 'is',
                'condition_value' => '1',
            ),
            array(
                'type_rule' => 'show',
                'component' => 'field',
                'component_field' => 'billing_cnpj',
                'verification_condition' => 'field',
                'verification_condition_field' => 'billing_persontype',
                'condition' => 'is',
                'condition_value' => '2',
            ),
            array(
                'type_rule' => 'show',
                'component' => 'field',
                'component_field' => 'billing_company',
                'verification_condition' => 'field',
                'verification_condition_field' => 'billing_persontype',
                'condition' => 'is',
                'condition_value' => '2',
            ),
            array(
                'type_rule' => 'show',
                'component' => 'field',
                'component_field' => 'billing_ie',
                'verification_condition' => 'field',
                'verification_condition_field' => 'billing_persontype',
                'condition' => 'is',
                'condition_value' => '2',
            ),
        );

        foreach ( $required_conditions as $condition ) {
            if ( ! self::has_matching_condition_signature( $conditions, $condition ) ) {
                $conditions[] = $condition;
                $updated = true;
            }
        }

        if ( $updated ) {
            update_option( 'flexify_checkout_conditions', array_values( $conditions ) );
        }

        return $updated;
    }


    /**
     * Check if condition already exists by signature.
     *
     * @since 5.5.0
     * @param array $conditions List of stored conditions.
     * @param array $target Target condition.
     * @return bool
     */
    private static function has_matching_condition_signature( $conditions, $target ) {
        $signature_keys = array(
            'type_rule',
            'component',
            'component_field',
            'verification_condition',
            'verification_condition_field',
            'condition',
            'condition_value',
        );

        foreach ( $conditions as $condition ) {
            if ( ! is_array( $condition ) ) {
                continue;
            }

            $match = true;

            foreach ( $signature_keys as $key ) {
                if ( ( $condition[ $key ] ?? null ) !== ( $target[ $key ] ?? null ) ) {
                    $match = false;
                    break;
                }
            }

            if ( $match ) {
                return true;
            }
        }

        return false;
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
