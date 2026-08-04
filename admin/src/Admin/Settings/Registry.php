<?php

namespace MeuMouse\Flexify_Checkout\Admin\Settings;

use MeuMouse\Flexify_Checkout\Admin\Fonts_Manager;
use MeuMouse\Flexify_Checkout\API\License;
use MeuMouse\Flexify_Checkout\Core\Helpers;
use MeuMouse\Flexify_Checkout\Checkout\Coupons;
use MeuMouse\Flexify_Checkout\Validations\ISO3166;
use MeuMouse\Flexify_Checkout\Views\Settings\Settings_Panel;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Build the settings schema and bootstrap payload for the Vue admin app.
 *
 * The schema is a declarative tree (tabs > cards > fields) consumed by the
 * frontend FieldRenderer. Field visibility dependencies are expressed via
 * visible_when and resolved client-side.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Admin\Settings
 * @author MeuMouse.com
 */
class Registry {

    /**
     * Build the full bootstrap payload consumed by the Vue settings app.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public static function get_bootstrap_data() {
        return apply_filters( 'Flexify_Checkout/Admin/Settings_Bootstrap', array(
            'settings' => Repository::get_settings(),
            'schema' => self::get_schema(),
            'runtime' => self::get_runtime_data(),
        ));
    }


    /**
     * Runtime (non-persisted) context for the settings app.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public static function get_runtime_data() {
        global $wp_version;

        $license_object = get_option( 'flexify_checkout_license_response_object', null );
        $admin_email = get_option('admin_email');

        return array(
            'version' => defined('FLEXIFY_CHECKOUT_VERSION') ? FLEXIFY_CHECKOUT_VERSION : '',
            'docs_link' => defined('FLEXIFY_CHECKOUT_DOCS_LINK') ? FLEXIFY_CHECKOUT_DOCS_LINK : '',
            'is_pro' => License::is_valid(),
            'is_debug' => function_exists('flexify_checkout_is_debug') ? flexify_checkout_is_debug() : false,
            'license' => array(
                'key' => (string) get_option( 'flexify_checkout_license_key', '' ),
                'masked_key' => self::mask_license_key( (string) get_option( 'flexify_checkout_license_key', '' ) ),
                'is_valid' => License::is_valid(),
                'title' => method_exists( License::class, 'license_title' ) ? License::license_title() : '',
                'expire' => method_exists( License::class, 'license_expire' ) ? License::license_expire() : '',
                'domain' => isset( $license_object->domain ) ? (string) $license_object->domain : '',
                'buy_url' => 'https://meumouse.com/plugins/flexify-checkout-para-woocommerce/?utm_source=wordpress&utm_medium=plugins-list&utm_campaign=flexify_checkout',
            ),
            'report_problems_url' => add_query_arg( array(
                'wpf9053_2' => rawurlencode( (string) $admin_email ),
                'wpf9053_5' => rawurlencode( 'Flexify Checkout para WooCommerce' ),
                'wpf9053_9' => rawurlencode( License::is_valid() ? 'Sim' : 'Não' ),
                'wpf9053_7' => rawurlencode( License::get_domain() ),
                'wpf9053_6' => rawurlencode( wp_get_theme()->get('Name') ),
            ), 'https://meumouse.com/reportar-problemas/' ),
            'fields' => Fields_Store::get_fields(),
            'conditions' => Conditions_Store::get_rules_for_client(),
            'layout' => Layout_Store::get_layout_for_client(),
            'builder_preview_url' => function_exists('wc_get_checkout_url') ? add_query_arg( 'flexify_builder', wp_create_nonce('flexify_builder_preview'), wc_get_checkout_url() ) : '',
            'integrations' => Integrations_Data::get_cards_for_client(),
            'fonts' => Fonts_Manager::get_fonts(),
            'shipping_methods' => self::build_shipping_method_options(),
            'shipping_zones' => self::build_shipping_zone_options(),
            'payment_gateways' => self::build_payment_gateway_options(),
            'user_roles' => self::build_user_role_options(),
            'currency_symbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : 'R$',
            'countries' => array_map( static function ( $code, $label ) {
                return array(
                    'value' => (string) $code,
                    'label' => (string) $label,
                );
            }, array_keys( ISO3166::country_codes() ), array_values( ISO3166::country_codes() ) ),
            'themes' => array_values( array_map( static function ( $theme ) {
                return array(
                    'value' => $theme['id'],
                    'label' => $theme['label'],
                    'status' => isset( $theme['status'] ) ? $theme['status'] : 'active',
                    'icon' => isset( $theme['icon'] ) ? $theme['icon'] : '',
                    'pro' => isset( $theme['pro'] ) ? (bool) $theme['pro'] : false,
                    'badges' => isset( $theme['badges'] ) ? array_values( (array) $theme['badges'] ) : array(),
                );
            }, Settings_Panel::get_registered_themes() ) ),
            'system' => array(
                'wp_version' => $wp_version,
                'multisite' => is_multisite(),
                'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
                'php_version' => phpversion(),
                'wc_version' => defined('WC_VERSION') ? WC_VERSION : '',
                'extensions' => array(
                    'dom' => extension_loaded('dom'),
                    'curl' => extension_loaded('curl'),
                    'openssl' => extension_loaded('openssl'),
                    'gd' => extension_loaded('gd'),
                ),
                'php_settings' => array(
                    'post_max_size' => ini_get('post_max_size'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'max_input_vars' => ini_get('max_input_vars'),
                    'memory_limit' => ini_get('memory_limit'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                ),
                'file_get_content' => function_exists('file_get_contents') && filter_var( ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN ),
            ),
        );
    }


    /**
     * Mask a license key for display, keeping only the edges visible.
     *
     * @since 6.0.0
     * @param string $key License key.
     * @return string
     */
    private static function mask_license_key( $key ) {
        if ( '' === $key ) {
            return '';
        }

        $parts = explode( '-', $key );

        if ( count( $parts ) > 2 ) {
            $masked = array();

            foreach ( $parts as $index => $part ) {
                if ( 0 === $index ) {
                    $masked[] = strlen( $part ) > 6 ? substr( $part, 0, 6 ) . str_repeat( 'X', strlen( $part ) - 6 ) : $part;
                } elseif ( $index === count( $parts ) - 1 ) {
                    $masked[] = $part;
                } else {
                    $masked[] = str_repeat( 'X', strlen( $part ) );
                }
            }

            return implode( '-', $masked );
        }

        if ( strlen( $key ) <= 8 ) {
            return $key;
        }

        return substr( $key, 0, 4 ) . str_repeat( 'X', max( 0, strlen( $key ) - 8 ) ) . substr( $key, -4 );
    }


    /**
     * Flat map of field key => definition, derived from the schema.
     *
     * Used by Repository to sanitize incoming values by declared type.
     *
     * @since 6.0.0
     * @return array<string,array<string,mixed>>
     */
    public static function get_field_definitions() {
        $definitions = array();

        foreach ( self::get_schema() as $tab ) {
            if ( empty( $tab['cards'] ) || ! is_array( $tab['cards'] ) ) {
                continue;
            }

            foreach ( $tab['cards'] as $card ) {
                if ( empty( $card['fields'] ) || ! is_array( $card['fields'] ) ) {
                    continue;
                }

                self::collect_field_definitions( $card['fields'], $definitions );
            }
        }

        return $definitions;
    }


    /**
     * Collect field definitions recursively, including popup sub-fields.
     *
     * @since 6.0.0
     * @param array<int,array<string,mixed>> $fields Field definitions list.
     * @param array<string,array<string,mixed>> $definitions Accumulator (by reference).
     * @return void
     */
    private static function collect_field_definitions( $fields, &$definitions ) {
        foreach ( $fields as $field ) {
            if ( ! is_array( $field ) ) {
                continue;
            }

            if ( ! empty( $field['key'] ) ) {
                $definitions[ $field['key'] ] = $field;
            }

            if ( ! empty( $field['popup']['fields'] ) && is_array( $field['popup']['fields'] ) ) {
                self::collect_field_definitions( $field['popup']['fields'], $definitions );
            }
        }
    }


    /**
     * Build the settings schema consumed by the Vue app.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>>
     */
    public static function get_schema() {
        $schema = array(
            self::tab_general(),
            self::tab_cart(),
            self::tab_account(),
            self::tab_fields(),
            self::tab_conditions(),
            self::tab_texts(),
            self::tab_thankyou(),
            self::tab_styles(),
        );

        // Global, checkout-wide webhooks. Pro-gated (webhooks were previously
        // only available behind the Pro recovery tab).
        if ( License::is_valid() ) {
            $schema[] = self::tab_webhooks();
        }

        $schema[] = self::tab_about();

        /**
         * Filter the settings schema before sending it to the Vue app.
         *
         * @since 6.0.0
         * @param array $schema Schema tree (tabs > cards > fields).
         */
        return apply_filters( 'Flexify_Checkout/Admin/Settings_Schema', $schema );
    }


    /**
     * General tab definition.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_general() {
        $shop_card_fields = array(
            self::field_toggle( 'enable_back_to_shop_button', __( 'Show Back to Shop button', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to display the "Back to shop" button at the first step of checkout.', 'flexify-checkout-for-woocommerce' ) ),
            self::field_toggle( 'enable_skip_cart_page', __( 'Skip cart page', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to automatically redirect the user from the cart page to checkout.', 'flexify-checkout-for-woocommerce' ) ),
            self::field_toggle( 'display_opened_order_review_mobile', __( 'Show order summary open by default', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to show the order summary open by default on mobile devices.', 'flexify-checkout-for-woocommerce' ) ),
            self::field_toggle( 'enable_checkout_countdown', __( 'Enable checkout countdown', 'flexify-checkout-for-woocommerce' ), __( 'Allows setting a time limit for the customer to complete the purchase or create urgency.', 'flexify-checkout-for-woocommerce' ), array(
                'popup' => array(
                    'button' => __( 'Configure', 'flexify-checkout-for-woocommerce' ),
                    'title' => __( 'Configure countdown', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_text( 'checkout_countdown_title', __( 'Countdown title', 'flexify-checkout-for-woocommerce' ), __( 'Allows setting a title to be displayed next to the countdown.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_dimension( 'checkout_countdown_value', 'checkout_countdown_unit', __( 'Total duration', 'flexify-checkout-for-woocommerce' ), __( 'Allows setting the countdown time limit.', 'flexify-checkout-for-woocommerce' ), array(
                            array( 'value' => 'minutes', 'label' => __( 'Minutes', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'days', 'label' => __( 'Days', 'flexify-checkout-for-woocommerce' ) ),
                        ) ),
                        self::field_select( 'checkout_countdown_action', __( 'Action after expiry', 'flexify-checkout-for-woocommerce' ), __( 'Allows defining the type of action to be executed after the countdown expires.', 'flexify-checkout-for-woocommerce' ), array(
                            array( 'value' => 'hide', 'label' => __( 'Hide', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'restart', 'label' => __( 'Restart countdown', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'logout', 'label' => __( 'End checkout session', 'flexify-checkout-for-woocommerce' ) ),
                        ) ),
                        self::field_text( 'checkout_countdown_redirect_url', __( 'Redirect URL', 'flexify-checkout-for-woocommerce' ), __( 'Allows defining which address the user will be redirected to after the session ends.', 'flexify-checkout-for-woocommerce' ), array(
                            'type' => 'url',
                            'visible_when' => array( array( 'field' => 'checkout_countdown_action', 'equals' => 'logout' ) ),
                        ) ),
                        self::field_select( 'countdown_background_type', __( 'Countdown background color', 'flexify-checkout-for-woocommerce' ), __( 'Enter the background color of the countdown bar.', 'flexify-checkout-for-woocommerce' ), array(
                            array( 'value' => 'primary', 'label' => __( 'Use default color', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'custom', 'label' => __( 'Set custom', 'flexify-checkout-for-woocommerce' ) ),
                        ) ),
                        self::field_color( 'countdown_background_color', __( 'Custom background color', 'flexify-checkout-for-woocommerce' ), '', array(
                            'default' => '#141D26',
                            'visible_when' => array( array( 'field' => 'countdown_background_type', 'equals' => 'custom' ) ),
                        ) ),
                        self::field_select( 'countdown_font_color_type', __( 'Countdown text color', 'flexify-checkout-for-woocommerce' ), __( 'Enter the text color of the countdown bar.', 'flexify-checkout-for-woocommerce' ), array(
                            array( 'value' => 'default', 'label' => __( 'Use default color', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'custom', 'label' => __( 'Set custom', 'flexify-checkout-for-woocommerce' ) ),
                        ) ),
                        self::field_color( 'countdown_font_color', __( 'Custom text color', 'flexify-checkout-for-woocommerce' ), '', array(
                            'default' => '#ffffff',
                            'visible_when' => array( array( 'field' => 'countdown_font_color_type', 'equals' => 'custom' ) ),
                        ) ),
                    ),
                ),
            ) ),
            self::field_toggle( 'enable_animation_process_purchase', __( 'Enable purchase processing animations', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to customize the purchase processing animation.', 'flexify-checkout-for-woocommerce' ), array(
                'pro' => true,
                'popup' => array(
                    'button' => __( 'Configure animation', 'flexify-checkout-for-woocommerce' ),
                    'title' => __( 'Configure processing animation', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_text( 'text_animation_process_purchase_1', __( 'Animation text 1', 'flexify-checkout-for-woocommerce' ), __( 'Enter the text that will be displayed in processing animation 1.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_media( 'animation_process_purchase_file_1', __( 'Animation file 1', 'flexify-checkout-for-woocommerce' ), __( 'Attach the Lottie animation link or file in .json format', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_text( 'text_animation_process_purchase_2', __( 'Animation text 2', 'flexify-checkout-for-woocommerce' ), __( 'Enter the text that will be displayed in processing animation 2.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_media( 'animation_process_purchase_file_2', __( 'Animation file 2', 'flexify-checkout-for-woocommerce' ), __( 'Attach the Lottie animation link or file in .json format', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_text( 'text_animation_process_purchase_3', __( 'Animation text 3', 'flexify-checkout-for-woocommerce' ), __( 'Enter the text that will be displayed in processing animation 3.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_media( 'animation_process_purchase_file_3', __( 'Animation file 3', 'flexify-checkout-for-woocommerce' ), __( 'Attach the Lottie animation link or file in .json format', 'flexify-checkout-for-woocommerce' ) ),
                    ),
                ),
            ) ),
        );

        if ( class_exists('Kangu_Shipping_Method') ) {
            $shop_card_fields[] = self::field_toggle( 'enable_display_local_pickup_kangu', __( 'Show physical store address for Kangu order pickup', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to show your store\'s address as the Kangu order pickup point.', 'flexify-checkout-for-woocommerce' ) );
        }

        return array(
            'id' => 'general',
            'title' => __( 'General', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'slider-alt',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'general-shop',
                    'fields' => $shop_card_fields,
                ),
                array(
                    'id' => 'general-checkout',
                    'fields' => array(
                        self::field_toggle( 'enable_terms_is_checked_default', __( 'Terms and conditions active by default', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option for the terms and conditions option of the last step to be active by default, if a terms and conditions page is configured.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_auto_apply_coupon_code', __( 'Apply discount coupon automatically', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to specify a discount coupon to be applied automatically at checkout.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_text( 'coupon_code_for_auto_apply', __( 'Discount coupon code', 'flexify-checkout-for-woocommerce' ), __( 'Please provide the discount coupon code that will be automatically applied at checkout.', 'flexify-checkout-for-woocommerce' ), array(
                            'placeholder' => 'CUPOMDEDESCONTO',
                            'visible_when' => array( array( 'field' => 'enable_auto_apply_coupon_code', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_toggle( 'direct_checkout_api', __( 'Enable API for direct checkout link creation', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to enable an endpoint for direct checkout link creation via API.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                    ),
                ),
            ),
        );
    }


    /**
     * Cart tab definition.
     *
     * Holds the cart/product behaviors previously rendered under the General
     * tab. Storage keys are unchanged — only the display tab moves.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_cart() {
        return array(
            'id' => 'cart',
            'title' => __( 'Cart', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'cart',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'cart-products',
                    'fields' => array(
                        self::field_toggle( 'enable_link_image_products', __( 'Make product images clickable', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to allow the user to access the product by clicking on the product image.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'enable_change_product_quantity', __( 'Allow changing product quantities', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to display the product quantity selectors at checkout.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_remove_product_cart', __( 'Allow removing products from cart', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to display the product removal button from the cart at checkout.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_remove_quantity_select', __( 'Remove quantity controls on individually sold products', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to remove quantity controls at checkout for products that are sold individually.', 'flexify-checkout-for-woocommerce' ) ),
                    ),
                ),
            ),
        );
    }


    /**
     * Account & access tab definition.
     *
     * Holds the login/account behaviors previously rendered under the General
     * tab. Storage keys are unchanged — only the display tab moves.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_account() {
        return array(
            'id' => 'account',
            'title' => __( 'Account & Access', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'user',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'account-access',
                    'fields' => array(
                        self::field_toggle( 'auto_display_login_modal', __( 'Automatically open login popup', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option so the login popup opens automatically when an existing account is recognized for the provided email.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'check_password_strenght', __( 'Enable user password strength verification', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to enforce password strength verification when creating a user account during checkout.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'email_providers_suggestion', __( 'Enable email autofill suggestion', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to display the email provider suggestion during checkout.', 'flexify-checkout-for-woocommerce' ), array(
                            'popup' => array(
                                'button' => __( 'Configure providers', 'flexify-checkout-for-woocommerce' ),
                                'title' => __( 'Configure email suggestions', 'flexify-checkout-for-woocommerce' ),
                                'component' => 'email-providers',
                            ),
                        ) ),
                        self::field_toggle( 'enable_assign_guest_orders', __( 'Assign orders from guest users', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option so that guest user orders at checkout are assigned to existing users.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                    ),
                ),
            ),
        );
    }


    /**
     * Thank you page tab definition.
     *
     * Consolidates the thank-you page options previously rendered under the
     * General tab. Storage keys are unchanged — only the display tab moves.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_thankyou() {
        return array(
            'id' => 'thankyou',
            'title' => __( 'Thank-you Page', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'like',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'thankyou-main',
                    'fields' => array(
                        self::field_toggle( 'enable_thankyou_page_template', __( 'Activate Flexify Checkout thank you page', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to load the Flexify Checkout thank you page model.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_select( 'contact_page_thankyou', __( 'Contact page', 'flexify-checkout-for-woocommerce' ), __( 'Select the contact page that will be displayed to customers at checkout.', 'flexify-checkout-for-woocommerce' ), self::build_pages_options(), array(
                            'visible_when' => array( array( 'field' => 'enable_thankyou_page_template', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_text( 'contact_page_thankyou_custom_link', __( 'Custom contact link', 'flexify-checkout-for-woocommerce' ), '', array(
                            'type' => 'url',
                            'visible_when' => array(
                                array( 'field' => 'enable_thankyou_page_template', 'equals' => 'yes' ),
                                array( 'field' => 'contact_page_thankyou', 'equals' => 'custom_link' ),
                            ),
                        ) ),
                    ),
                ),
            ),
        );
    }


    /**
     * Texts tab definition.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_texts() {
        $empty_hint = __( 'Leave blank to not display.', 'flexify-checkout-for-woocommerce' );
        $review_hint = __( 'Use the variables below to retrieve field information. Or leave blank to not display.', 'flexify-checkout-for-woocommerce' );
        $placeholders = self::build_placeholder_hints();

        return array(
            'id' => 'texts',
            'title' => __( 'Texts', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'text',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'texts-steps',
                    'fields' => array(
                        self::field_text( 'text_header_step_1', __( 'Informational text of the contact stage fields', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_shipping_methods_label', __( 'Delivery methods title', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_header_step_2', __( 'Informational text of the delivery stage fields', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_header_step_3', __( 'Informational text of the payment stage fields', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_header_sidebar_right', __( 'Informational text of the cart items', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_check_step_1', __( 'Informational text of the contact stage verifier', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_check_step_2', __( 'Informational text of the delivery stage verifier', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_check_step_3', __( 'Informational text of the payment stage verifier', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_previous_step_button', __( 'Text of the button to go back steps', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_view_shop_thankyou', __( 'Text of the button to revisit the store on the thank-you page', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                    ),
                ),
                array(
                    'id' => 'texts-reviews',
                    'fields' => array(
                        self::field_textarea( 'text_contact_customer_review', __( 'Text of the contact information summary', 'flexify-checkout-for-woocommerce' ), $review_hint, array(
                            'component' => 'placeholder-textarea',
                            'rows' => 4,
                            'placeholders' => $placeholders,
                        ) ),
                        self::field_textarea( 'text_shipping_customer_review', __( 'Text of the delivery information summary', 'flexify-checkout-for-woocommerce' ), $review_hint, array(
                            'component' => 'placeholder-textarea',
                            'rows' => 4,
                            'placeholders' => $placeholders,
                        ) ),
                    ),
                ),
            ),
        );
    }


    /**
     * Build the placeholder hints list for the review text fields.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_placeholder_hints() {
        $hints = array();

        if ( method_exists( Helpers::class, 'get_placeholder_input_values' ) ) {
            foreach ( Helpers::get_placeholder_input_values() as $value ) {
                $hints[] = array(
                    'token' => isset( $value['placeholder_html'] ) ? (string) $value['placeholder_html'] : '',
                    'description' => isset( $value['description'] ) ? (string) $value['description'] : '',
                );
            }
        }

        return $hints;
    }


    /**
     * Fields tab definition.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_fields() {
        return array(
            'id' => 'fields',
            'title' => __( 'Fields and stages', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'list-plus',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'checkout-builder',
                    'title' => __( 'Checkout builder', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Visually build the steps, fields and components of the React checkout (order bump, content blocks, coupon, summary and notes) with live preview.', 'flexify-checkout-for-woocommerce' ),
                    'component' => 'checkout-builder',
                    'fields' => array(
                        self::field_toggle( 'enable_checkout_builder', __( 'Enable the checkout builder', 'flexify-checkout-for-woocommerce' ), __( 'Applies the layout built in the builder to the React checkout. When disabled, the checkout uses the default steps.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_manage_fields', __( 'Manage the fields and stages of the checkout', 'flexify-checkout-for-woocommerce' ), __( 'Applies the field customizations (created in the builder) to the checkout.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                    ),
                ),
                array(
                    'id' => 'fields-options',
                    'title' => __( 'Field options', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'General behaviors of the form fields.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_aditional_notes', __( 'Show additional observations field', 'flexify-checkout-for-woocommerce' ), __( 'Displays the order notes field.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'enable_field_masks', __( 'Add masks to fields', 'flexify-checkout-for-woocommerce' ), __( 'Applies input masks (CPF, ZIP code, phone...).', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'enable_optimize_for_digital_products', __( 'Optimize for digital products', 'flexify-checkout-for-woocommerce' ), __( 'Hides shipping fields when the cart contains only virtual products.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'hide_header_stepper_buttons', __( 'Hide step indicator', 'flexify-checkout-for-woocommerce' ), __( 'Removes the stepper from the checkout header.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_unset_wcbcf_fields_not_brazil', __( 'Hide Brazilian fields for other countries', 'flexify-checkout-for-woocommerce' ), __( 'Hides Brazilian Market fields when the country is not Brazil.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                    ),
                ),
                array(
                    'id' => 'fields-brazilian',
                    'title' => __( 'Brazilian fields (native)', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Add CPF/CNPJ, person type, RG, State Registration, birthdate, gender, cell phone, number and neighborhood natively — no separate Brazilian Market plugin required.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_native_brazilian_fields', __( 'Enable native Brazilian fields', 'flexify-checkout-for-woocommerce' ), __( 'Seeds and keeps the Brazilian checkout fields and their person-type conditions in sync with the options below.', 'flexify-checkout-for-woocommerce' ), array(
                            'popup' => array(
                                'button' => __( 'Configure fields', 'flexify-checkout-for-woocommerce' ),
                                'title' => __( 'Brazilian fields', 'flexify-checkout-for-woocommerce' ),
                                'fields' => array(
                                    self::field_select( 'brazilian_person_type_mode', __( 'Person type', 'flexify-checkout-for-woocommerce' ), __( 'Which document set the checkout collects.', 'flexify-checkout-for-woocommerce' ), array(
                                        array( 'value' => 'both', 'label' => __( 'Individual and Legal entity (selectable)', 'flexify-checkout-for-woocommerce' ) ),
                                        array( 'value' => 'individual', 'label' => __( 'Individual only (CPF)', 'flexify-checkout-for-woocommerce' ) ),
                                        array( 'value' => 'legal', 'label' => __( 'Legal entity only (CNPJ)', 'flexify-checkout-for-woocommerce' ) ),
                                        array( 'value' => 'none', 'label' => __( 'Do not use person type', 'flexify-checkout-for-woocommerce' ) ),
                                    ) ),
                                    self::field_toggle( 'brazilian_show_rg', __( 'Show RG field', 'flexify-checkout-for-woocommerce' ), __( 'Displays the RG (ID) field for individuals.', 'flexify-checkout-for-woocommerce' ) ),
                                    self::field_toggle( 'brazilian_show_ie', __( 'Show State Registration field', 'flexify-checkout-for-woocommerce' ), __( 'Displays the Inscrição Estadual (IE) field for legal entities.', 'flexify-checkout-for-woocommerce' ) ),
                                    self::field_toggle( 'brazilian_show_birthdate', __( 'Show birthdate field', 'flexify-checkout-for-woocommerce' ), '' ),
                                    self::field_toggle( 'brazilian_show_gender', __( 'Show gender field', 'flexify-checkout-for-woocommerce' ), '' ),
                                    self::field_select( 'brazilian_cellphone_mode', __( 'Cell phone field', 'flexify-checkout-for-woocommerce' ), '', array(
                                        array( 'value' => 'optional', 'label' => __( 'Optional', 'flexify-checkout-for-woocommerce' ) ),
                                        array( 'value' => 'required', 'label' => __( 'Required', 'flexify-checkout-for-woocommerce' ) ),
                                        array( 'value' => 'disable', 'label' => __( 'Do not show', 'flexify-checkout-for-woocommerce' ) ),
                                    ) ),
                                    self::field_toggle( 'brazilian_neighborhood_required', __( 'Make neighborhood required', 'flexify-checkout-for-woocommerce' ), '' ),
                                    self::field_toggle( 'brazilian_validate_cpf', __( 'Validate CPF', 'flexify-checkout-for-woocommerce' ), __( 'Rejects an invalid CPF on order placement.', 'flexify-checkout-for-woocommerce' ) ),
                                    self::field_toggle( 'brazilian_validate_cnpj', __( 'Validate CNPJ', 'flexify-checkout-for-woocommerce' ), __( 'Rejects an invalid CNPJ on order placement.', 'flexify-checkout-for-woocommerce' ) ),
                                ),
                            ),
                        ) ),
                    ),
                ),
                array(
                    'id' => 'fields-address',
                    'title' => __( 'Address', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Filling, validation and behavior of the address fields.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_autofill_company_info', __( 'Automatically fill in company information', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to automatically fill in the company information when entering the CNPJ (Available only in Brazil).', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_fill_address', __( 'Automatically fill in address', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to fill in the delivery fields when entering the ZIP code (Recommended), (Available only in Brazil).', 'flexify-checkout-for-woocommerce' ), array(
                            'pro' => true,
                            'popup' => array(
                                'button' => __( 'Configure API', 'flexify-checkout-for-woocommerce' ),
                                'title' => __( 'Configure address fill API', 'flexify-checkout-for-woocommerce' ),
                                'fields' => array(
                                    self::field_text( 'get_address_api_service', __( 'API service for address lookup', 'flexify-checkout-for-woocommerce' ), __( 'Please provide the API address to obtain the user\'s address through their ZIP code in JSON format. Use the variable {postcode} to specify the ZIP code.', 'flexify-checkout-for-woocommerce' ) ),
                                    self::field_text( 'api_auto_fill_address_param', __( 'Address retrieval property', 'flexify-checkout-for-woocommerce' ), __( 'Please provide the property to obtain the address that is returned by the API service.', 'flexify-checkout-for-woocommerce' ) ),
                                    self::field_text( 'api_auto_fill_address_neightborhood_param', __( 'Neighborhood retrieval property', 'flexify-checkout-for-woocommerce' ), __( 'Please provide the property to obtain the neighborhood that is returned by the API service.', 'flexify-checkout-for-woocommerce' ) ),
                                    self::field_text( 'api_auto_fill_address_city_param', __( 'City retrieval property', 'flexify-checkout-for-woocommerce' ), __( 'Please provide the property to obtain the city that is returned by the API service.', 'flexify-checkout-for-woocommerce' ) ),
                                    self::field_text( 'api_auto_fill_address_state_param', __( 'State retrieval property', 'flexify-checkout-for-woocommerce' ), __( 'Please provide the property to obtain the state that is returned by the API service.', 'flexify-checkout-for-woocommerce' ) ),
                                ),
                            ),
                        ) ),
                        self::field_toggle( 'enable_shipping_to_different_address', __( 'Allow shipping to a different address', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to allow the user to send their order to a different address from the billing address.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_saved_addresses', __( 'Enable saved addresses', 'flexify-checkout-for-woocommerce' ), __( 'Lets logged-in customers save multiple addresses with a nickname and pick one on their next purchase. Adds a "Saved addresses" tab to the My Account area.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'validate_address_by_postcode', __( 'Enable address validation by ZIP code', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to validate if the user\'s city and state match the provided billing ZIP code.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'enable_ddi_phone_field', __( 'Enable international phone', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to display the country selector in the phone number field. Useful if you sell to other countries.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                    ),
                ),
                array(
                    'id' => 'fields-coupon',
                    'title' => __( 'Coupon field', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Visibility and position of the discount coupon field.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_hide_coupon_code_field', __( 'Hide discount coupon field', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_select( 'render_coupon_field_hook', __( 'Coupon field position', 'flexify-checkout-for-woocommerce' ), '', self::build_coupon_position_options(), array( 'pro' => true ) ),
                    ),
                ),
            ),
        );
    }


    /**
     * Conditions tab definition.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_conditions() {
        return array(
            'id' => 'conditions',
            'title' => __( 'Conditions', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'filter-alt',
            'layout' => 'custom',
            'cards' => array(
                array(
                    'id' => 'conditions-manager',
                    'title' => __( 'Conditions manager', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Show, hide or apply discounts on fields, shipping and payment methods based on conditional rules (users, products, countries, shipping regions and more).', 'flexify-checkout-for-woocommerce' ),
                    'component' => 'conditions-manager',
                ),
            ),
        );
    }


    /**
     * Styles tab definition.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_styles() {
        $unit_options = array(
            array( 'value' => 'px', 'label' => 'px' ),
            array( 'value' => 'em', 'label' => 'em' ),
            array( 'value' => 'rem', 'label' => 'rem' ),
        );

        return array(
            'id' => 'styles',
            'title' => __( 'Styles', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'palette',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'styles-theme',
                    'component' => 'theme-picker',
                ),
                array(
                    'id' => 'styles-header',
                    'fields' => array(
                        self::field_select( 'checkout_header_type', __( 'Type of brand in the header', 'flexify-checkout-for-woocommerce' ), __( 'Select the type of brand that will be displayed in the header of the checkout page.', 'flexify-checkout-for-woocommerce' ), array(
                            array( 'value' => 'logo', 'label' => __( 'Image (Default)', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'text', 'label' => __( 'Text', 'flexify-checkout-for-woocommerce' ) ),
                        ) ),
                        self::field_media( 'search_image_header_checkout', __( 'Header image', 'flexify-checkout-for-woocommerce' ), '', array(
                            'visible_when' => array( array( 'field' => 'checkout_header_type', 'equals' => 'logo' ) ),
                        ) ),
                        self::field_text( 'logo_header_link', __( 'Header image link', 'flexify-checkout-for-woocommerce' ), __( 'Please provide the header image link.', 'flexify-checkout-for-woocommerce' ), array(
                            'type' => 'url',
                            'visible_when' => array( array( 'field' => 'checkout_header_type', 'equals' => 'logo' ) ),
                        ) ),
                        self::field_dimension( 'header_width_image_checkout', 'unit_header_width_image_checkout', __( 'Header image width', 'flexify-checkout-for-woocommerce' ), '', $unit_options, array(
                            'visible_when' => array( array( 'field' => 'checkout_header_type', 'equals' => 'logo' ) ),
                        ) ),
                        self::field_text( 'text_brand_checkout_header', __( 'Header text', 'flexify-checkout-for-woocommerce' ), __( 'Please provide the text that will be displayed in the header of the checkout page.', 'flexify-checkout-for-woocommerce' ), array(
                            'placeholder' => 'CHECKOUT',
                            'visible_when' => array( array( 'field' => 'checkout_header_type', 'equals' => 'text' ) ),
                        ) ),
                    ),
                ),
                array(
                    'id' => 'styles-shortcodes',
                    'fields' => array(
                        self::field_text( 'shortcode_header', __( 'Custom Header', 'flexify-checkout-for-woocommerce' ), __( 'Add your custom header using the shortcode.', 'flexify-checkout-for-woocommerce' ), array(
                            'placeholder' => '[shortcode id="100"]',
                        ) ),
                        self::field_text( 'shortcode_footer', __( 'Custom Footer', 'flexify-checkout-for-woocommerce' ), __( 'Add your custom footer using the shortcode.', 'flexify-checkout-for-woocommerce' ), array(
                            'placeholder' => '[shortcode id="101"]',
                        ) ),
                    ),
                ),
                array(
                    'id' => 'styles-appearance',
                    'fields' => array(
                        self::field_color( 'set_primary_color', __( 'Primary color', 'flexify-checkout-for-woocommerce' ), __( 'The primary color defines the color of elements that will have actions or information on the checkout page.', 'flexify-checkout-for-woocommerce' ), array( 'default' => '#141D26' ) ),
                        self::field_color( 'set_primary_color_on_hover', __( 'Secondary color', 'flexify-checkout-for-woocommerce' ), __( 'The secondary color defines the color of elements that will have actions or information on the checkout page.', 'flexify-checkout-for-woocommerce' ), array( 'default' => '#33404D' ) ),
                        self::field_color( 'set_placeholder_color', __( 'Field title color', 'flexify-checkout-for-woocommerce' ), __( 'Enter the color of the field titles for checkout.', 'flexify-checkout-for-woocommerce' ), array( 'default' => '#33404D' ) ),
                        self::field_dimension( 'input_border_radius', 'unit_input_border_radius', __( 'Element border radius', 'flexify-checkout-for-woocommerce' ), __( 'Define the border radius of fields, buttons, and checkout elements.', 'flexify-checkout-for-woocommerce' ), $unit_options ),
                        self::field_select( 'set_font_family', __( 'Font family', 'flexify-checkout-for-woocommerce' ), __( 'Define which font will be applied at checkout. You can add new custom fonts or from Google Fonts.', 'flexify-checkout-for-woocommerce' ), self::build_font_options(), array(
                            'wide' => true,
                            'popup' => array(
                                'button' => __( 'Manage sources', 'flexify-checkout-for-woocommerce' ),
                                'title' => __( 'Manage sources', 'flexify-checkout-for-woocommerce' ),
                                'component' => 'fonts-manager',
                            ),
                        ) ),
                        self::field_dimension( 'h2_size', 'h2_size_unit', __( 'H2 size', 'flexify-checkout-for-woocommerce' ), __( 'Set the font size for h2 subtitle tags (Heading 2).', 'flexify-checkout-for-woocommerce' ), $unit_options ),
                        self::field_code( 'custom_css_checkout', __( 'Custom CSS', 'flexify-checkout-for-woocommerce' ), __( 'Add custom CSS that will be applied at checkout.', 'flexify-checkout-for-woocommerce' ), 'css' ),
                        self::field_code( 'custom_js_checkout', __( 'Custom JS', 'flexify-checkout-for-woocommerce' ), __( 'Add custom JavaScript that will be executed at checkout.', 'flexify-checkout-for-woocommerce' ), 'javascript' ),
                    ),
                ),
            ),
        );
    }


    /**
     * Webhooks tab definition.
     *
     * Renders the global webhooks manager component, which reads/writes its
     * configuration through its own REST endpoint (flexify-checkout/v1/webhooks).
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_webhooks() {
        return array(
            'id' => 'webhooks',
            'title' => __( 'Webhooks', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'webhook',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'webhooks-manager',
                    'component' => 'webhooks-manager',
                ),
            ),
        );
    }


    /**
     * About tab definition.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_about() {
        return array(
            'id' => 'about',
            'title' => __( 'About', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'info-circle',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'about-updates',
                    'fields' => array(
                        self::field_toggle( 'enable_auto_updates', __( 'Enable automatic updates', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option so the Flexify Checkout plugin is updated automatically whenever possible.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_update_notices', __( 'Show available update notice', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to display an available update notification.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'enable_debug_mode', __( 'Enable debug mode', 'flexify-checkout-for-woocommerce' ), __( 'Enable this option to turn on debug mode and access information in the browser console, disable script and style minification, and other details for troubleshooting.', 'flexify-checkout-for-woocommerce' ) ),
                    ),
                ),
                array(
                    'id' => 'about-logs',
                    'component' => 'logs-viewer',
                ),
                array(
                    'id' => 'about-actions',
                    'component' => 'about-actions',
                ),
                array(
                    'id' => 'about-system',
                    'component' => 'system-status',
                ),
            ),
        );
    }


    /**
     * Build a toggle field definition.
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_toggle( $key, $label, $description = '', $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'toggle',
            'label' => $label,
            'description' => $description,
        ), $extra );
    }


    /**
     * Build a text field definition.
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_text( $key, $label, $description = '', $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'text',
            'label' => $label,
            'description' => $description,
        ), $extra );
    }


    /**
     * Build a textarea field definition.
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_textarea( $key, $label, $description = '', $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'textarea',
            'label' => $label,
            'description' => $description,
        ), $extra );
    }


    /**
     * Build a number field definition.
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_number( $key, $label, $description = '', $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'number',
            'label' => $label,
            'description' => $description,
        ), $extra );
    }


    /**
     * Build a select field definition.
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<int,array<string,string>> $options Options list (value/label pairs).
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_select( $key, $label, $description, $options, $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'select',
            'label' => $label,
            'description' => $description,
            'options' => $options,
        ), $extra );
    }


    /**
     * Build a color field definition.
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_color( $key, $label, $description = '', $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'color',
            'label' => $label,
            'description' => $description,
        ), $extra );
    }


    /**
     * Build a dimension field definition (numeric value + unit select).
     *
     * @since 6.0.0
     * @param string $key Value setting key.
     * @param string $unit_key Unit setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<int,array<string,string>> $units Unit options.
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_dimension( $key, $unit_key, $label, $description, $units, $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'dimension',
            'unit_key' => $unit_key,
            'label' => $label,
            'description' => $description,
            'units' => $units,
        ), $extra );
    }


    /**
     * Build a media field definition (URL input + WordPress media picker).
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_media( $key, $label, $description = '', $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'media',
            'label' => $label,
            'description' => $description,
        ), $extra );
    }


    /**
     * Build a code editor field definition.
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param string $language Editor language (css|javascript).
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_code( $key, $label, $description, $language, $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'code-editor',
            'label' => $label,
            'description' => $description,
            'language' => $language,
        ), $extra );
    }


    /**
     * Build theme options from the registered themes.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_theme_options() {
        $options = array();

        foreach ( Settings_Panel::get_registered_themes() as $theme ) {
            $options[] = array(
                'value' => $theme['id'],
                'label' => $theme['label'],
                'disabled' => isset( $theme['status'] ) && 'active' !== $theme['status'],
            );
        }

        return $options;
    }


    /**
     * Build font family options from the saved fonts library.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_font_options() {
        $settings = Repository::get_settings();
        $fonts = isset( $settings['font_family'] ) && is_array( $settings['font_family'] ) ? $settings['font_family'] : array();
        $options = array();

        foreach ( $fonts as $font_id => $font ) {
            $options[] = array(
                'value' => (string) $font_id,
                'label' => isset( $font['font_name'] ) ? (string) $font['font_name'] : (string) $font_id,
            );
        }

        return $options;
    }


    /**
     * Build coupon field position options.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_coupon_position_options() {
        $options = array();

        if ( class_exists( Coupons::class ) && method_exists( Coupons::class, 'get_coupon_field_position' ) ) {
            foreach ( Coupons::get_coupon_field_position() as $position => $value ) {
                $options[] = array(
                    'value' => (string) $position,
                    'label' => isset( $value['title'] ) ? (string) $value['title'] : (string) $position,
                );
            }
        }

        return $options;
    }


    /**
     * Build options for the registered shipping methods.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_shipping_method_options() {
        $options = array();

        if ( function_exists('WC') && WC()->shipping ) {
            foreach ( WC()->shipping->get_shipping_methods() as $shipping ) {
                $options[] = array(
                    'value' => (string) $shipping->id,
                    'label' => (string) $shipping->method_title,
                );
            }
        }

        return $options;
    }


    /**
     * Build options for the configured WooCommerce shipping zones.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_shipping_zone_options() {
        $options = array();

        if ( ! class_exists('\WC_Shipping_Zones') ) {
            return $options;
        }

        // "Rest of the World" zone (id 0).
        $options[] = array(
            'value' => '0',
            'label' => __( 'Rest of the world', 'flexify-checkout-for-woocommerce' ),
        );

        foreach ( \WC_Shipping_Zones::get_zones() as $zone ) {
            $options[] = array(
                'value' => (string) $zone['id'],
                'label' => (string) $zone['zone_name'],
            );
        }

        return $options;
    }


    /**
     * Build options for the registered payment gateways.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_payment_gateway_options() {
        $options = array();

        if ( function_exists('WC') && WC()->payment_gateways ) {
            foreach ( WC()->payment_gateways->payment_gateways() as $payment ) {
                $options[] = array(
                    'value' => (string) $payment->id,
                    'label' => (string) $payment->get_title(),
                );
            }
        }

        return $options;
    }


    /**
     * Build options for the registered user roles.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_user_role_options() {
        $translations = array(
            'administrator' => __( 'Administrator', 'flexify-checkout-for-woocommerce' ),
            'author' => __( 'Author', 'flexify-checkout-for-woocommerce' ),
            'subscriber' => __( 'Subscriber', 'flexify-checkout-for-woocommerce' ),
            'customer' => __( 'Customer', 'flexify-checkout-for-woocommerce' ),
            'contributor' => __( 'Collaborator', 'flexify-checkout-for-woocommerce' ),
            'editor' => __( 'Editor', 'flexify-checkout-for-woocommerce' ),
            'shop_manager' => __( 'Store manager', 'flexify-checkout-for-woocommerce' ),
            'translator' => __( 'Translator', 'flexify-checkout-for-woocommerce' ),
        );

        $options = array();

        foreach ( wp_roles()->roles as $role_key => $role ) {
            $options[] = array(
                'value' => (string) $role_key,
                'label' => isset( $translations[ $role_key ] ) ? $translations[ $role_key ] : (string) $role['name'],
            );
        }

        return $options;
    }


    /**
     * Build page options for the thank you contact link select.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_pages_options() {
        $options = array(
            array(
                'value' => 'custom_link',
                'label' => __( 'Custom link', 'flexify-checkout-for-woocommerce' ),
            ),
        );

        foreach ( get_pages() as $page ) {
            $options[] = array(
                'value' => (string) $page->ID,
                'label' => $page->post_title,
            );
        }

        return $options;
    }
}
