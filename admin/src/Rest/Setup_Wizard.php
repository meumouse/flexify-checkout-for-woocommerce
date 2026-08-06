<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Brazilian_Fields_Setup;
use MeuMouse\Flexify_Checkout\Admin\Settings\Registry;
use MeuMouse\Flexify_Checkout\Admin\Settings\Repository;
use MeuMouse\Flexify_Checkout\API\License;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * First-run setup wizard endpoints.
 *
 * Exposes the context the fullscreen wizard needs (GET) and applies every
 * answer transactionally (POST): WooCommerce selling/shipping countries, the
 * Swift checkout theme, digital-product optimization, native Brazilian fields
 * and an optional license activation. A single Repository::save_settings()
 * call persists every plugin setting so the update_option hook that drives
 * Brazilian_Fields_Setup::sync() fires exactly once.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Setup_Wizard extends Abstract_Route {

    /**
     * Route path for the wizard.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/setup-wizard';

    /**
     * Option flag marking the wizard as completed (or skipped).
     *
     * @since 6.0.0
     * @var string
     */
    const COMPLETED_OPTION = 'flexify_checkout_wizard_completed';


    /**
     * Register both the context (GET) and apply (POST) routes.
     *
     * @since 6.0.0
     * @return void
     */
    public function register_route() {
        register_rest_route( self::REST_NAMESPACE, $this->route, array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_context' ),
                'permission_callback' => array( $this, 'permission' ),
            ),
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'handle' ),
                'permission_callback' => array( $this, 'permission' ),
            ),
        ) );
    }


    /**
     * Return the data the wizard needs to render its steps.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function get_context( WP_REST_Request $request ) {
        $countries = array();

        if ( function_exists('WC') && WC()->countries ) {
            foreach ( WC()->countries->get_countries() as $code => $label ) {
                $countries[] = array(
                    'value' => (string) $code,
                    'label' => html_entity_decode( (string) $label, ENT_QUOTES, 'UTF-8' ),
                );
            }
        }

        $settings = Repository::get_settings();

        return $this->success_response( array(
            'context' => array(
                'countries' => $countries,
                'store_country' => (string) get_option( 'woocommerce_default_country', 'BR' ),
                'selling' => array(
                    'mode' => (string) get_option( 'woocommerce_allowed_countries', 'all' ),
                    'specific' => (array) get_option( 'woocommerce_specific_allowed_countries', array() ),
                    'except' => (array) get_option( 'woocommerce_all_except_countries', array() ),
                ),
                'shipping' => array(
                    'mode' => (string) get_option( 'woocommerce_ship_to_countries', '' ),
                    'specific' => (array) get_option( 'woocommerce_specific_ship_to_countries', array() ),
                ),
                'wcbcf_active' => Brazilian_Fields_Setup::is_wcbcf_active(),
                'theme' => isset( $settings['flexify_checkout_theme'] ) ? (string) $settings['flexify_checkout_theme'] : 'swift',
                'digital_optimization' => isset( $settings['enable_optimize_for_digital_products'] ) ? (string) $settings['enable_optimize_for_digital_products'] : 'no',
                'brazilian' => array(
                    'enabled' => isset( $settings['enable_native_brazilian_fields'] ) ? (string) $settings['enable_native_brazilian_fields'] : 'no',
                    'person_type_mode' => isset( $settings['brazilian_person_type_mode'] ) ? (string) $settings['brazilian_person_type_mode'] : 'both',
                    'show_rg' => isset( $settings['brazilian_show_rg'] ) ? (string) $settings['brazilian_show_rg'] : 'no',
                    'show_ie' => isset( $settings['brazilian_show_ie'] ) ? (string) $settings['brazilian_show_ie'] : 'no',
                    'show_birthdate' => isset( $settings['brazilian_show_birthdate'] ) ? (string) $settings['brazilian_show_birthdate'] : 'no',
                    'show_gender' => isset( $settings['brazilian_show_gender'] ) ? (string) $settings['brazilian_show_gender'] : 'no',
                    'cellphone_mode' => isset( $settings['brazilian_cellphone_mode'] ) ? (string) $settings['brazilian_cellphone_mode'] : 'optional',
                    'neighborhood_required' => isset( $settings['brazilian_neighborhood_required'] ) ? (string) $settings['brazilian_neighborhood_required'] : 'no',
                ),
                'license' => array(
                    'is_valid' => License::is_valid(),
                    'masked_key' => method_exists( License::class, 'license_title' ) ? (string) get_option( 'flexify_checkout_license_key', '' ) : '',
                ),
                'needs_setup_wizard' => 'yes' !== get_option( self::COMPLETED_OPTION, 'no' ),
            ),
        ) );
    }


    /**
     * Apply the wizard answers.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $payload = $request->get_json_params();
        $payload = is_array( $payload ) ? $payload : array();

        // Skipping just records that the operator has seen the wizard.
        if ( ! empty( $payload['skip'] ) ) {
            update_option( self::COMPLETED_OPTION, 'yes' );
            update_option( 'flexify_checkout_wizard_result', 'skipped' );

            return $this->success_response( array(
                'message' => __( 'Setup assistant dismissed.', 'flexify-checkout-for-woocommerce' ),
                'runtime' => Registry::get_runtime_data(),
            ) );
        }

        $notices = array();

        // 1) Selling and shipping countries (WooCommerce core options).
        $this->apply_selling_countries( isset( $payload['selling'] ) ? (array) $payload['selling'] : array() );
        $product_types = isset( $payload['product_types'] ) ? sanitize_text_field( (string) $payload['product_types'] ) : 'both';
        $this->apply_shipping_countries( isset( $payload['shipping'] ) ? (array) $payload['shipping'] : array(), $product_types );

        // 2) Collect plugin-setting changes into one payload so a single save
        //    triggers the Brazilian fields sync at most once.
        $settings_patch = array();

        // Digital-product optimization: enabled only when the store sells
        // exclusively virtual products (Pro; applies once a license is active).
        if ( 'virtual' === $product_types ) {
            $settings_patch['enable_optimize_for_digital_products'] = 'yes';
        }

        // 3) Swift checkout preference (falls back to the free "modern" theme).
        $swift_enabled = ! isset( $payload['swift_enabled'] ) || ! empty( $payload['swift_enabled'] );
        $settings_patch['flexify_checkout_theme'] = $swift_enabled ? 'swift' : 'modern';

        if ( $swift_enabled && ! License::is_valid() ) {
            $notices[] = __( 'The Swift checkout will activate once a Pro license or trial is active; the classic checkout is used until then.', 'flexify-checkout-for-woocommerce' );
        }

        // 4) Brazilian fields.
        $brazil = isset( $payload['brazil'] ) ? (array) $payload['brazil'] : array();
        $bmw_install_required = false;

        if ( ! empty( $brazil['enabled'] ) ) {
            $method = isset( $brazil['method'] ) ? sanitize_text_field( (string) $brazil['method'] ) : 'native';

            if ( 'bmw' === $method ) {
                // Native fields stay off; the frontend guides the BMW install.
                $settings_patch['enable_native_brazilian_fields'] = 'no';

                if ( ! Brazilian_Fields_Setup::is_wcbcf_active() ) {
                    $bmw_install_required = true;
                }
            } else {
                $settings_patch['enable_native_brazilian_fields'] = 'yes';
                $settings_patch['brazilian_person_type_mode'] = $this->sanitize_person_type( $brazil['person_type'] ?? 'both' );
                $settings_patch['brazilian_show_rg'] = $this->yn( $brazil['show_rg'] ?? false );
                $settings_patch['brazilian_show_ie'] = $this->yn( $brazil['show_ie'] ?? false );
                $settings_patch['brazilian_show_birthdate'] = $this->yn( $brazil['show_birthdate'] ?? false );
                $settings_patch['brazilian_show_gender'] = $this->yn( $brazil['show_gender'] ?? false );
                $settings_patch['brazilian_cellphone_mode'] = $this->sanitize_cellphone_mode( $brazil['cellphone_mode'] ?? 'optional' );
                $settings_patch['brazilian_neighborhood_required'] = $this->yn( $brazil['neighborhood_required'] ?? false );
            }
        }

        // 5) Optional extra toggles (all opt-in, only known keys are honored).
        $other = isset( $payload['other'] ) ? (array) $payload['other'] : array();
        $allowed_other = array( 'enable_skip_cart_page', 'enable_auto_apply_coupon_code', 'enable_ddi_phone_field' );

        foreach ( $allowed_other as $key ) {
            if ( array_key_exists( $key, $other ) ) {
                $settings_patch[ $key ] = $this->yn( $other[ $key ] );
            }
        }

        // Persist all plugin settings in a single write (fires the sync hook).
        if ( ! empty( $settings_patch ) ) {
            Repository::save_settings( $settings_patch );
        }

        // 6) Optional license activation.
        $license = isset( $payload['license'] ) ? (array) $payload['license'] : array();
        $license_action = isset( $license['action'] ) ? sanitize_text_field( (string) $license['action'] ) : 'free';
        $license_result = $this->apply_license( $license_action, isset( $license['key'] ) ? (string) $license['key'] : '' );

        if ( ! empty( $license_result['message'] ) ) {
            $notices[] = $license_result['message'];
        }

        // 7) Mark the wizard as completed.
        update_option( self::COMPLETED_OPTION, 'yes' );
        update_option( 'flexify_checkout_wizard_result', 'completed' );

        return $this->success_response( array(
            'message' => __( 'Setup completed! Your store checkout is ready.', 'flexify-checkout-for-woocommerce' ),
            'notices' => $notices,
            'bmw_install_required' => $bmw_install_required,
            'license_activated' => ! empty( $license_result['activated'] ),
            'runtime' => Registry::get_runtime_data(),
        ) );
    }


    /**
     * Update the WooCommerce selling-countries options.
     *
     * @since 6.0.0
     * @param array<string,mixed> $selling Selling step payload.
     * @return void
     */
    private function apply_selling_countries( $selling ) {
        $mode = isset( $selling['mode'] ) ? sanitize_text_field( (string) $selling['mode'] ) : 'all';
        $codes = $this->sanitize_country_list( $selling['countries'] ?? array() );

        switch ( $mode ) {
            case 'all_except':
                update_option( 'woocommerce_allowed_countries', 'all_except' );
                update_option( 'woocommerce_all_except_countries', $codes );
                break;

            case 'specific':
                update_option( 'woocommerce_allowed_countries', 'specific' );
                update_option( 'woocommerce_specific_allowed_countries', $codes );

                // When selling to a single country, make it the store base country.
                if ( 1 === count( $codes ) ) {
                    update_option( 'woocommerce_default_country', $codes[0] );
                }
                break;

            case 'all':
            default:
                update_option( 'woocommerce_allowed_countries', 'all' );
                break;
        }
    }


    /**
     * Update the WooCommerce shipping-countries options.
     *
     * @since 6.0.0
     * @param array<string,mixed> $shipping Shipping step payload.
     * @param string $product_types Selected product-type mode.
     * @return void
     */
    private function apply_shipping_countries( $shipping, $product_types ) {
        // Virtual-only stores don't ship anything.
        if ( 'virtual' === $product_types ) {
            update_option( 'woocommerce_ship_to_countries', 'disabled' );

            return;
        }

        $mode = isset( $shipping['mode'] ) ? sanitize_text_field( (string) $shipping['mode'] ) : 'same';
        $codes = $this->sanitize_country_list( $shipping['countries'] ?? array() );

        switch ( $mode ) {
            case 'all':
                update_option( 'woocommerce_ship_to_countries', 'all' );
                break;

            case 'specific':
                update_option( 'woocommerce_ship_to_countries', 'specific' );
                update_option( 'woocommerce_specific_ship_to_countries', $codes );
                break;

            case 'disabled':
                update_option( 'woocommerce_ship_to_countries', 'disabled' );
                break;

            case 'same':
            default:
                // Empty string = ship only to countries the store sells to.
                update_option( 'woocommerce_ship_to_countries', '' );
                break;
        }
    }


    /**
     * Apply the license step.
     *
     * @since 6.0.0
     * @param string $action One of activate|free|trial.
     * @param string $key License key (for the activate action).
     * @return array{activated:bool,message:string}
     */
    private function apply_license( $action, $key ) {
        if ( 'activate' !== $action ) {
            if ( 'trial' === $action ) {
                // Trial provisioning is handled by the MDS API (not yet wired).
                return array(
                    'activated' => false,
                    'message' => __( 'The free trial will be available soon.', 'flexify-checkout-for-woocommerce' ),
                );
            }

            return array( 'activated' => false, 'message' => '' );
        }

        $license_key = sanitize_text_field( $key );

        if ( '' === $license_key ) {
            return array( 'activated' => false, 'message' => __( 'Enter a license code.', 'flexify-checkout-for-woocommerce' ) );
        }

        delete_transient('flexify_checkout_api_request_cache');
        delete_transient('flexify_checkout_api_response_cache');
        delete_transient('flexify_checkout_license_status_cached');

        update_option( 'flexify_checkout_license_key', $license_key ) || add_option( 'flexify_checkout_license_key', $license_key );
        update_option( 'flexify_checkout_temp_license_key', $license_key ) || add_option( 'flexify_checkout_temp_license_key', $license_key );

        $message = '';
        $response_obj = new \stdClass();

        if ( License::check_license( $license_key, $message, $response_obj, FLEXIFY_CHECKOUT_FILE ) ) {
            if ( $response_obj && $response_obj->is_valid ) {
                update_option( 'flexify_checkout_license_status', 'valid' );
                delete_option('flexify_checkout_temp_license_key');
                delete_option('flexify_checkout_license_expired');
                delete_option('flexify_checkout_alternative_license_activation');
            } else {
                update_option( 'flexify_checkout_license_status', 'invalid' );
            }
        }

        if ( License::is_valid() ) {
            return array( 'activated' => true, 'message' => __( 'License activated successfully.', 'flexify-checkout-for-woocommerce' ) );
        }

        return array(
            'activated' => false,
            'message' => ! empty( $message ) ? $message : __( 'The license could not be activated. You can add it later in the License tab.', 'flexify-checkout-for-woocommerce' ),
        );
    }


    /**
     * Sanitize a list of ISO country codes.
     *
     * @since 6.0.0
     * @param mixed $list Raw country list.
     * @return array<int,string>
     */
    private function sanitize_country_list( $list ) {
        if ( ! is_array( $list ) ) {
            return array();
        }

        $codes = array();

        foreach ( $list as $code ) {
            $code = strtoupper( sanitize_text_field( (string) $code ) );

            if ( '' !== $code ) {
                $codes[] = $code;
            }
        }

        return array_values( array_unique( $codes ) );
    }


    /**
     * Normalize a truthy value to yes/no.
     *
     * @since 6.0.0
     * @param mixed $value Raw value.
     * @return string
     */
    private function yn( $value ) {
        return in_array( $value, array( true, 'yes', '1', 1, 'true', 'on' ), true ) ? 'yes' : 'no';
    }


    /**
     * Sanitize the person-type mode.
     *
     * @since 6.0.0
     * @param mixed $value Raw value.
     * @return string
     */
    private function sanitize_person_type( $value ) {
        $value = sanitize_text_field( (string) $value );

        return in_array( $value, array( 'both', 'individual', 'legal', 'none' ), true ) ? $value : 'both';
    }


    /**
     * Sanitize the cell-phone mode.
     *
     * @since 6.0.0
     * @param mixed $value Raw value.
     * @return string
     */
    private function sanitize_cellphone_mode( $value ) {
        $value = sanitize_text_field( (string) $value );

        return in_array( $value, array( 'optional', 'required', 'disable' ), true ) ? $value : 'optional';
    }
}
