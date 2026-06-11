<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Core;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Default_Options;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Components as Admin_Components;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handler for ajax requests
 * 
 * @since 1.0.0
 * @version 1.3.5
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Core
 * @author MeuMouse.com
 */
class Ajax {

    /**
     * Get debug mode
     * 
     * @since 1.3.0
     * @return bool
     */
    public static $debug_mode = FC_RECOVERY_CARTS_DEBUG_MODE;

    /**
     * Cached settings to avoid multiple get_option calls
     * 
     * @since 1.3.0
     * @var array|null
     */
    private static $cached_settings = null;

    /**
     * AJAX action nonce name
     * 
     * @since 1.3.0
     * @var string
     */
    private $ajax_nonce_name = 'fcrc_ajax_nonce';

    /**
     * Construct function
     *
     * @since 1.0.0
     * @version 1.3.5
     * @return void
     */
    public function __construct() {
        // Define AJAX actions for admin (logged in users only)
        $admin_ajax_actions = array(
            'fc_recovery_carts_save_options' => 'admin_save_options_callback',
            'fcrc_add_new_follow_up' => 'fcrc_add_new_follow_up_callback',
            'fcrc_delete_follow_up' => 'fcrc_delete_follow_up_callback',
            'fcrc_send_test_follow_up' => 'fcrc_send_test_follow_up_callback',
            'fcrc_get_carts_table_changes' => 'fcrc_get_carts_table_changes_callback',
            'fcrc_refresh_carts_table' => 'fcrc_refresh_carts_table_callback',
            'fcrc_get_analytics_data' => 'get_analytics_data_callback',
        );

        // Define AJAX actions for public (both logged in and not logged in users)
        $public_ajax_actions = array(
            'fcrc_lead_collected' => 'fcrc_lead_collected_callback',
            'fcrc_save_checkout_lead' => 'fcrc_save_checkout_lead_callback',
            'fcrc_update_location' => 'fcrc_update_location_callback',
        );

        // Register admin AJAX actions
        foreach ( $admin_ajax_actions as $action => $callback ) {
            add_action( "wp_ajax_$action", array( $this, $callback ) );
        }

        // Register public AJAX actions for both logged in and not logged in users
        foreach ( $public_ajax_actions as $action => $callback ) {
            add_action( "wp_ajax_$action", array( $this, $callback ) );
            add_action( "wp_ajax_nopriv_$action", array( $this, $callback ) );
        }
    }


    /**
     * Get settings with caching
     * 
     * @since 1.3.5
     * @return array
     */
    private function get_settings() {
        if ( self::$cached_settings === null ) {
            self::$cached_settings = get_option( 'flexify_checkout_recovery_carts_settings', array() );
        }

        return self::$cached_settings;
    }


    /**
     * Update settings with cache clearing
     * 
     * @since 1.3.5
     * @param array $settings
     * @return bool
     */
    private function update_settings( $settings ) {
        self::$cached_settings = $settings;
        
        return update_option( 'flexify_checkout_recovery_carts_settings', $settings );
    }


    /**
     * Validate AJAX request with nonce and required parameters
     * 
     * @since 1.3.5
     * @param array $required_params | Required parameters
     * @return void
     */
    private function validate_ajax_request( $required_params = array() ) {
        // Verify nonce for security
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], $this->ajax_nonce_name ) ) {
            wp_send_json_error( array(
                'message' => esc_html__( 'Requisição inválida ou expirada. Por favor, atualize a página.', 'fc-recovery-carts' )
            ));
        }

        // Check for required parameters
        foreach ( $required_params as $param ) {
            if ( ! isset( $_POST[ $param ] ) ) {
                wp_send_json_error( array(
                    'message' => sprintf( esc_html__( 'Parâmetro obrigatório ausente: %s', 'fc-recovery-carts' ), $param )
                ));
            }
        }
    }

    /**
     * Safely decode JSON data from POST requests
     * 
     * @since 1.3.5
     * @param mixed $input JSON string or array
     * @param bool $assoc Return associative array
     * @return array
     */
    private function safe_json_decode( $input, $assoc = true ) {
        if ( empty( $input ) ) {
            return array();
        }

        if ( is_array( $input ) ) {
            return $input;
        }

        $decoded = json_decode( stripslashes( $input ), $assoc );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            if ( self::$debug_mode ) {
                error_log( 'JSON decode error: ' . json_last_error_msg() . ' Input: ' . substr( $input, 0, 100 ) );
            }

            return array();
        }

        return $decoded ?: array();
    }


    /**
     * Handle exceptions in AJAX callbacks
     * 
     * @since 1.3.5
     * @param string $callback_name
     * @param \Exception $e
     * @return void
     */
    private function handle_ajax_exception( $callback_name, $e ) {
        if ( self::$debug_mode ) {
            error_log( "Error in $callback_name: " . $e->getMessage() . "\n" . $e->getTraceAsString() );
        }

        wp_send_json_error( array(
            'message' => esc_html__( 'Ocorreu um erro interno. Por favor, tente novamente.', 'fc-recovery-carts' ),
            'debug' => self::$debug_mode ? $e->getMessage() : null
        ));
    }


    /**
     * Generate event key from title
     * 
     * @since 1.3.0
     * @param string $title
     * @return string
     */
    private function generate_event_key( $title ) {
        $key = strtolower( trim( $title ) );
        $key = preg_replace( '/[^a-z0-9]+/', '_', $key );
        $key = preg_replace( '/_+/', '_', $key );
        $key = trim( $key, '_' );
        
        return $key ?: 'event_' . time();
    }


    /**
     * Get cart data with single WC()->cart call
     * 
     * @since 1.3.5
     * @return array
     */
    private function get_cart_data() {
        $cart = WC()->cart;
        
        if ( ! $cart ) {
            return array(
                'total' => 0,
                'items' => array(),
                'subtotal' => 0,
                'tax' => 0,
            );
        }

        return array(
            'total' => $cart->get_cart_contents_total(),
            'items' => $cart->get_cart(),
            'subtotal' => $cart->get_subtotal(),
            'tax' => $cart->get_taxes_total(),
        );
    }


    /**
     * Update cart post meta with standardized mapping
     * 
     * @since 1.3.5
     * @param int $cart_id | Post ID
     * @param array $data | New data
     * @return void
     */
    private function update_cart_meta( $cart_id, $data ) {
        $meta_map = array(
            'first_name' => '_fcrc_first_name',
            'last_name' => '_fcrc_last_name',
            'full_name' => '_fcrc_full_name',
            'phone' => '_fcrc_cart_phone',
            'email' => '_fcrc_cart_email',
            'cart_total' => '_fcrc_cart_total',
            'user_id' => '_fcrc_user_id',
            'cart_items' => '_fcrc_cart_items',
        );

        foreach ( $meta_map as $data_key => $meta_key ) {
            if ( isset( $data[ $data_key ] ) ) {
                update_post_meta( $cart_id, $meta_key, $data[ $data_key ] );
            }
        }
    }


    /**
     * Save options in AJAX
     * 
     * @since 1.0.0
     * @version 1.3.5
     * @return void
     */
    public function admin_save_options_callback() {
        try {
            // Validate request
            $this->validate_ajax_request( array( 'form_data' ) );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( array(
                    'message' => esc_html__( 'Permissão negada.', 'fc-recovery-carts' )
                ));
            }

            $raw = wp_unslash( $_POST['form_data'] );

            // Convert form data to associative array
            parse_str( $raw, $form_data );

            // Get current options 
            $options = $this->get_settings();

            // Get default options
            $default_options = ( new Default_Options() )->set_default_options();

            // Update all fields except arrays like toggle_switchs and follow_up_events
            foreach ( $default_options as $key => $value ) {
                if ( ! is_array( $value ) && isset( $form_data[ $key ] ) ) {
                    if ( strpos( $key, 'message' ) !== false ) {
                        $options[ $key ] = sanitize_textarea_field( $form_data[ $key ] );
                    } else {
                        $options[ $key ] = sanitize_text_field( $form_data[ $key ] );
                    }
                }
            }

            foreach ( $default_options as $key => $value ) {
                if ( is_array( $value ) && isset( $form_data[ $key ] ) ) {
                    if ( $key === 'follow_up_events' ) {
                        $options[ $key ] = Helpers::sanitize_array( $form_data[ $key ] );
                    } else {
                        $options[ $key ] = Helpers::recursive_merge( $value, Helpers::sanitize_array( $form_data[ $key ] ) );
                    }
                }
            }

            if ( ! isset( $form_data['follow_up_events'] ) ) {
                $options['follow_up_events'] = array();
            }

            // Save options
            $saved_options = $this->update_settings( $options );

            // Prepare response
            if ( $saved_options ) {
                $response = array(
                    'status' => 'success',
                    'toast_header_title' => esc_html__( 'Salvo com sucesso', 'fc-recovery-carts' ),
                    'toast_body_title' => esc_html__( 'As configurações foram atualizadas!', 'fc-recovery-carts' ),
                );
            } else {
                $response = array(
                    'status' => 'error',
                    'toast_header_title' => esc_html__( 'Ops! Ocorreu um erro.', 'fc-recovery-carts' ),
                    'toast_body_title' => esc_html__( 'Não foi possível salvar as configurações.', 'fc-recovery-carts' ),
                );
            }

            // Add debug info if in debug mode
            if ( self::$debug_mode ) {
                $response['debug'] = array(
                    'options' => $options,
                    'update_options' => $saved_options,
                );
            }

            wp_send_json( $response );

        } catch ( \Exception $e ) {
            $this->handle_ajax_exception( __FUNCTION__, $e );
        }
    }


    /**
     * Add new follow up event in AJAX
     * 
     * @since 1.0.0
     * @version 1.3.5
     * @return void
     */
    public function fcrc_add_new_follow_up_callback() {
        try {
            // Validate request
            $this->validate_ajax_request( array( 'title' ) );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( array(
                    'message' => esc_html__( 'Permissão negada.', 'fc-recovery-carts' )
                ) );
            }

            // Sanitize and prepare data
            $title = sanitize_text_field( $_POST['title'] );
            $event_key = $this->generate_event_key( $title );
            $message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';
            $delay_time = isset( $_POST['delay_time'] ) ? sanitize_text_field( $_POST['delay_time'] ) : '';
            $delay_type = isset( $_POST['delay_type'] ) ? sanitize_text_field( $_POST['delay_type'] ) : '';
            $whatsapp = isset( $_POST['whatsapp'] ) ? sanitize_text_field( $_POST['whatsapp'] ) : '';
            $coupon = $this->safe_json_decode( $_POST['coupon'] ?? '' );
            $start_time = isset( $_POST['start_time'] ) ? sanitize_text_field( $_POST['start_time'] ) : '';
            $end_time = isset( $_POST['end_time'] ) ? sanitize_text_field( $_POST['end_time'] ) : '';

            // Get current settings
            $settings = $this->get_settings();

            // Initialize follow up events array if not exists
            if ( ! isset( $settings['follow_up_events'] ) || ! is_array( $settings['follow_up_events'] ) ) {
                $settings['follow_up_events'] = array();
            }

            // Add new follow up event
            $settings['follow_up_events'][ $event_key ] = array(
                'enabled' => 'yes',
                'title' => $title,
                'message' => $message,
                'delay_time' => $delay_time,
                'delay_type' => $delay_type,
                'channels' => array(
                    'whatsapp' => $whatsapp,
                ),
                'send_window' => array(
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                ),
                'coupon' => array(
                    'enabled' => $coupon['enabled'] ?? 'no',
                    'generate_coupon' => $coupon['generate_coupon'] ?? 'no',
                    'coupon_prefix' => $coupon['coupon_prefix'] ?? '',
                    'coupon_code' => $coupon['coupon_code'] ?? '',
                    'discount_type' => $coupon['discount_type'] ?? '',
                    'discount_value' => $coupon['discount_value'] ?? '',
                    'allow_free_shipping' => $coupon['allow_free_shipping'] ?? 'no',
                    'expiration_time' => $coupon['expiration_time'] ?? '',
                    'expiration_time_unit' => $coupon['expiration_time_unit'] ?? '',
                    'limit_usages' => $coupon['limit_usages'] ?? '',
                    'limit_usages_per_user' => $coupon['limit_usages_per_user'] ?? '',
                ),
            );

            // Save settings
            $saved_options = $this->update_settings( $settings );

            // Prepare response
            if ( $saved_options ) {
                $response = array(
                    'status' => 'success',
                    'toast_header_title' => esc_html__( 'Salvo com sucesso', 'fc-recovery-carts' ),
                    'toast_body_title' => esc_html__( 'O evento foi adicionado com sucesso!', 'fc-recovery-carts' ),
                    'follow_up_list' => Admin_Components::follow_up_list(),
                );
            } else {
                $response = array(
                    'status' => 'error',
                    'toast_header_title' => esc_html__( 'Ops! Ocorreu um erro', 'fc-recovery-carts' ),
                    'toast_body_title' => esc_html__( 'Não foi possível adicionar o novo evento.', 'fc-recovery-carts' ),
                );
            }

            wp_send_json( $response );
        } catch ( \Exception $e ) {
            $this->handle_ajax_exception( __FUNCTION__, $e );
        }
    }


    /**
     * Delete follow up event in AJAX
     * 
     * @since 1.0.0
     * @version 1.3.5
     * @return void
     */
    public function fcrc_delete_follow_up_callback() {
        try {
            // Validate request
            $this->validate_ajax_request( array( 'event_key' ) );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( array(
                    'message' => esc_html__( 'Permissão negada.', 'fc-recovery-carts' )
                ) );
            }

            $event_key = sanitize_text_field( $_POST['event_key'] );

            if ( empty( $event_key ) ) {
                wp_send_json_error( array(
                    'message' => esc_html__( 'Chave do evento não especificada.', 'fc-recovery-carts' )
                ) );
            }

            // Get current settings
            $settings = $this->get_settings();

            // Check if follow up event exists
            if ( isset( $settings['follow_up_events'][ $event_key ] ) ) {
                // Delete follow up event
                unset( $settings['follow_up_events'][ $event_key ] );

                // Save settings
                $saved_options = $this->update_settings( $settings );

                if ( $saved_options ) {
                    $response = array(
                        'status' => 'success',
                        'toast_header_title' => esc_html__( 'Salvo com sucesso', 'fc-recovery-carts' ),
                        'toast_body_title' => esc_html__( 'O evento foi removido com sucesso!', 'fc-recovery-carts' ),
                        'follow_up_list' => Admin_Components::follow_up_list(),
                    );
                } else {
                    $response = array(
                        'status' => 'error',
                        'toast_header_title' => esc_html__( 'Ops! Ocorreu um erro', 'fc-recovery-carts' ),
                        'toast_body_title' => esc_html__( 'Não foi possível remover o evento.', 'fc-recovery-carts' ),
                    );
                }
            } else {
                $response = array(
                    'status' => 'error',
                    'toast_header_title' => esc_html__( 'Ops! Ocorreu um erro', 'fc-recovery-carts' ),
                    'toast_body_title' => esc_html__( 'Evento não encontrado.', 'fc-recovery-carts' ),
                );
            }

            wp_send_json( $response );
        } catch ( \Exception $e ) {
            $this->handle_ajax_exception( __FUNCTION__, $e );
        }
    }


    /**
     * Send a test follow up message via Joinotify using dummy placeholder data
     *
     * @since 1.4.0
     * @return void
     */
    public function fcrc_send_test_follow_up_callback() {
        try {
            $this->validate_ajax_request( array( 'event_key' ) );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( array(
                    'message' => esc_html__( 'Permissão negada.', 'fc-recovery-carts' )
                ) );
            }

            $event_key = sanitize_text_field( $_POST['event_key'] );
            $settings = $this->get_settings();
            $event = $settings['follow_up_events'][ $event_key ] ?? null;

            if ( ! $event ) {
                wp_send_json( array(
                    'status' => 'error',
                    'toast_header_title' => esc_html__( 'Ops! Ocorreu um erro', 'fc-recovery-carts' ),
                    'toast_body_title' => esc_html__( 'Evento de follow up não encontrado.', 'fc-recovery-carts' ),
                ) );
            }

            if ( empty( $event['channels']['whatsapp'] ) || $event['channels']['whatsapp'] !== 'yes' ) {
                wp_send_json( array(
                    'status' => 'error',
                    'toast_header_title' => esc_html__( 'Canal desativado', 'fc-recovery-carts' ),
                    'toast_body_title' => esc_html__( 'O canal WhatsApp não está ativo para este follow up.', 'fc-recovery-carts' ),
                ) );
            }

            $test_phone = trim( (string) ( $settings['joinotify_test_phone'] ?? '' ) );

            if ( empty( $test_phone ) ) {
                wp_send_json( array(
                    'status' => 'error',
                    'toast_header_title' => esc_html__( 'Telefone não configurado', 'fc-recovery-carts' ),
                    'toast_body_title' => esc_html__( 'Configure o telefone de teste nas opções da integração Joinotify.', 'fc-recovery-carts' ),
                ) );
            }

            if ( ! function_exists('joinotify_send_whatsapp_message_text') ) {
                wp_send_json( array(
                    'status' => 'error',
                    'toast_header_title' => esc_html__( 'Joinotify indisponível', 'fc-recovery-carts' ),
                    'toast_body_title' => esc_html__( 'A integração Joinotify não está ativa.', 'fc-recovery-carts' ),
                ) );
            }

            $dummy_values = array(
                '{{ first_name }}' => esc_html__( 'João', 'fc-recovery-carts' ),
                '{{ last_name }}' => esc_html__( 'da Silva', 'fc-recovery-carts' ),
                '{{ recovery_link }}' => home_url( '/?fcrc_recovery=teste123' ),
                '{{ coupon_code }}' => 'TESTE10',
                '{{ products_list }}' => esc_html__( 'Produto de exemplo 1, Produto de exemplo 2', 'fc-recovery-carts' ),
                '{{ cart_total }}' => function_exists('wc_price') ? html_entity_decode( wp_strip_all_tags( wc_price( 199.90 ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '199.90',
            );

            /**
             * Filter dummy placeholder values used on test follow up messages
             *
             * @since 1.4.0
             * @param array  $dummy_values | Map of placeholder => sample value
             * @param string $event_key    | Follow up event key
             * @param array  $event        | Follow up event settings
             */
            $dummy_values = apply_filters( 'Flexify_Checkout/Recovery_Carts/Test_Follow_Up/Dummy_Values', $dummy_values, $event_key, $event );

            $message = strtr( (string) ( $event['message'] ?? '' ), $dummy_values );
            $sender = Admin::get_setting('joinotify_sender_phone');
            $receiver = function_exists('joinotify_prepare_receiver') ? joinotify_prepare_receiver( $test_phone ) : $test_phone;

            if ( empty( $sender ) || $sender === 'none' ) {
                wp_send_json( array(
                    'status' => 'error',
                    'toast_header_title' => esc_html__( 'Remetente não configurado', 'fc-recovery-carts' ),
                    'toast_body_title' => esc_html__( 'Selecione um remetente nas opções da integração Joinotify.', 'fc-recovery-carts' ),
                ) );
            }

            $sent = joinotify_send_whatsapp_message_text( $sender, $receiver, $message );

            if ( $sent ) {
                wp_send_json( array(
                    'status' => 'success',
                    'toast_header_title' => esc_html__( 'Mensagem enviada', 'fc-recovery-carts' ),
                    'toast_body_title' => sprintf( esc_html__( 'Teste enviado para %s.', 'fc-recovery-carts' ), esc_html( $receiver ) ),
                ) );
            }

            wp_send_json( array(
                'status' => 'error',
                'toast_header_title' => esc_html__( 'Falha ao enviar', 'fc-recovery-carts' ),
                'toast_body_title' => esc_html__( 'Não foi possível enviar a mensagem de teste. Verifique a configuração do Joinotify.', 'fc-recovery-carts' ),
            ) );
        } catch ( \Exception $e ) {
            $this->handle_ajax_exception( __FUNCTION__, $e );
        }
    }


    /**
     * Lightweight endpoint that returns the timestamp of the last new cart
     * created with status shopping/lead. Used by the admin carts table JS
     * to detect changes and trigger a refresh.
     *
     * @since 1.4.0
     * @return void
     */
    public function fcrc_get_carts_table_changes_callback() {
        try {
            $this->validate_ajax_request();

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( array(
                    'message' => esc_html__( 'Permissão negada.', 'fc-recovery-carts' )
                ) );
            }

            wp_send_json( array(
                'status' => 'success',
                'last_change' => (int) get_option( 'fcrc_carts_table_last_change', 0 ),
            ) );
        } catch ( \Exception $e ) {
            $this->handle_ajax_exception( __FUNCTION__, $e );
        }
    }


    /**
     * Re-render the carts list table preserving current filters
     * (post_status, paged, orderby, order, search) and return the HTML.
     *
     * @since 1.4.0
     * @return void
     */
    public function fcrc_refresh_carts_table_callback() {
        try {
            $this->validate_ajax_request();

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( array(
                    'message' => esc_html__( 'Permissão negada.', 'fc-recovery-carts' )
                ) );
            }

            // forward filter params so the table renders with the current view
            $forwarded = array( 'page', 'post_status', 'paged', 'orderby', 'order', 's', 'fcrc_cart_search' );

            foreach ( $forwarded as $key ) {
                if ( isset( $_POST[ $key ] ) ) {
                    $_REQUEST[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
                    $_GET[ $key ] = $_REQUEST[ $key ];
                }
            }

            if ( ! class_exists('WP_List_Table') ) {
                require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
            }

            $table = new \MeuMouse\Flexify_Checkout\Recovery_Carts\Views\Carts_Table();
            $table->prepare_items();

            ob_start();
            $table->render_table_inner();
            $html = ob_get_clean();

            wp_send_json( array(
                'status' => 'success',
                'last_change' => (int) get_option( 'fcrc_carts_table_last_change', 0 ),
                'html' => $html,
            ) );
        } catch ( \Exception $e ) {
            $this->handle_ajax_exception( __FUNCTION__, $e );
        }
    }


    /**
     * Get lead collected
     *
     * @since 1.0.0
     * @version 1.3.5
     * @return void
     */
    public function fcrc_lead_collected_callback() {
        try {
            // Validate request
            $this->validate_ajax_request( array( 'first_name', 'last_name', 'phone', 'email' ) );

            // Sanitize input data
            $first_name = sanitize_text_field( $_POST['first_name'] );
            $last_name = sanitize_text_field( $_POST['last_name'] );
            $phone = sanitize_text_field( $_POST['phone'] );
            $email = sanitize_email( $_POST['email'] );
            $country_data = $this->safe_json_decode( $_POST['country_data'] ?? '' );
            $get_cart_id = sanitize_text_field( $_POST['cart_id'] ?? '' );

            // Process country data
            $country_code = $country_data['iso2'] ?? '';
            $country_dial_code = $country_data['dialCode'] ?? '';
            $format_phone = $country_dial_code . $phone;
            $phone = preg_replace( '/\D/', '', $format_phone );

            // Get full name
            $full_name = sprintf( '%s %s', $first_name, $last_name );

            // Get cart data
            $cart_data = $this->get_cart_data();

            // Check if user exists
            $user = get_user_by( 'email', $email );
            $user_id = $user ? $user->ID : 0;

            // Prepare cart meta data
            $cart_meta_data = array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'full_name' => $full_name,
                'phone' => $phone,
                'email' => $email,
                'cart_total' => $cart_data['total'],
                'cart_items' => $cart_data['items'],
                'user_id' => $user_id,
            );

            if ( ! $get_cart_id || empty( $get_cart_id ) ) {
                // Create a new post of type 'fc-recovery-carts'
                $cart_id = wp_insert_post( array(
                    'post_type' => 'fc-recovery-carts',
                    'post_status' => 'lead',
                    'post_title' => 'Lead: ' . $full_name,
                    'post_content' => '',
                ));

                if ( $cart_id && ! is_wp_error( $cart_id ) ) {
                    $this->update_cart_meta( $cart_id, $cart_meta_data );
                }
            } else {
                $cart_id = intval( $get_cart_id );
                $this->update_cart_meta( $cart_id, $cart_meta_data );
            }

            // Store cart id in session if available
            if ( $cart_id && ! is_wp_error( $cart_id ) && WC()->session ) {
                WC()->session->set( 'fcrc_cart_id', $cart_id );
            }

            // Get cached location data
            $location = $this->safe_json_decode( $_COOKIE['fcrc_location'] ?? '' );

            // Get IP address and map user
            $ip = $location['ip'] ?? '';
            if ( $ip && $cart_id && ! is_wp_error( $cart_id ) ) {
                $map = get_option( 'fcrc_ip_user_map', array() );

                $map[ $ip ] = array(
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'full_name' => $full_name,
                    'phone' => $phone,
                    'email' => $email,
                    'cart_id' => $cart_id,
                    'collected_at' => strtotime( current_time( 'mysql' ) ),
                );

                update_option( 'fcrc_ip_user_map', $map );
            }

            /**
             * Hook fired on lead collected
             * 
             * @since 1.0.0
             * @param int $cart_id Cart ID | Post ID
             * @param array $data Lead data
             */
            if ( $cart_id && ! is_wp_error( $cart_id ) ) {
                do_action( 'Flexify_Checkout/Recovery_Carts/Lead_Collected', $cart_id, array(
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'full_name' => $full_name,
                    'phone' => $phone,
                    'email' => $email,
                    'country' => $country_code,
                    'cart_total' => $cart_data['total'],
                    'user_id' => $user_id,
                ));

                $response = array(
                    'status' => 'success',
                    'cart_id' => $cart_id,
                );
            } else {
                $response = array(
                    'status' => 'error',
                    'message' => is_wp_error( $cart_id ) ? $cart_id->get_error_message() : esc_html__( 'Falha ao criar carrinho.', 'fc-recovery-carts' ),
                );
            }

            wp_send_json( $response );
        } catch ( \Exception $e ) {
            $this->handle_ajax_exception( __FUNCTION__, $e );
        }
    }


    /**
     * Get checkout lead data
     * 
     * @since 1.0.0
     * @version 1.3.5
     * @return void
     */
    public function fcrc_save_checkout_lead_callback() {
        try {
            // Validate request
            $this->validate_ajax_request( array( 'cart_id', 'first_name', 'last_name', 'phone', 'email' ) );

            $cart_id = intval( $_POST['cart_id'] );
            $first_name = sanitize_text_field( $_POST['first_name'] );
            $last_name = sanitize_text_field( $_POST['last_name'] );
            $phone = sanitize_text_field( $_POST['phone'] );
            $email = sanitize_email( $_POST['email'] );
            $full_name = sprintf( '%s %s', $first_name, $last_name );

            // Check if user exists
            $user = get_user_by( 'email', $email );
            $user_id = $user ? $user->ID : 0;

            if ( $cart_id ) {
                // Update cart meta
                $this->update_cart_meta( $cart_id, array(
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'full_name' => $full_name,
                    'phone' => $phone,
                    'email' => $email,
                    'user_id' => $user_id,
                ));

                // Get cached location data
                $location = $this->safe_json_decode( $_COOKIE['fcrc_location'] ?? '' );

                // Get IP address and map user
                $ip = $location['ip'] ?? '';

                if ( $ip ) {
                    $map = get_option( 'fcrc_ip_user_map', array() );

                    $map[ $ip ] = array(
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'full_name' => $full_name,
                        'phone' => $phone,
                        'email' => $email,
                        'cart_id' => $cart_id,
                        'collected_at' => strtotime( current_time( 'mysql' ) ),
                    );

                    update_option( 'fcrc_ip_user_map', $map );
                }

                /**
                 * Hook fired on lead collected on checkout
                 * 
                 * @since 1.0.0
                 * @param int $cart_id Cart ID | Post ID
                 * @param array $data Lead data
                 */
                do_action( 'Flexify_Checkout/Recovery_Carts/Checkout_Lead_Collected', $cart_id, array(
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'phone' => $phone,
                    'email' => $email,
                    'user_id' => $user_id,
                ));

                if ( self::$debug_mode ) {
                    error_log( 'New checkout lead collected from cart ID: ' . $cart_id );
                }

                wp_send_json( array(
                    'status' => 'success',
                ));
            } else {
                wp_send_json_error( array(
                    'message' => esc_html__( 'ID do carrinho inválido.', 'fc-recovery-carts' )
                ));
            }

        } catch ( \Exception $e ) {
            $this->handle_ajax_exception( __FUNCTION__, $e );
        }
    }


    /**
     * Get location data from IP address
     * 
     * @since 1.0.1
     * @version 1.3.5
     * @return void
     */
    public function fcrc_update_location_callback() {
        try {
            // Validate request
            $this->validate_ajax_request( array( 'cart_id', 'country_data' ) );

            $cart_id = intval( $_POST['cart_id'] );
            $country_data = $this->safe_json_decode( $_POST['country_data'] );

            if ( self::$debug_mode ) {
                error_log( 'Location data received: ' . print_r( $country_data, true ) );
            }

            if ( empty( $cart_id ) || empty( $country_data ) ) {
                wp_send_json_error( array(
                    'message' => esc_html__( 'Dados inválidos.', 'fc-recovery-carts' )
                ));
            }

            // Update location meta
            update_post_meta( $cart_id, '_fcrc_location_country_code', sanitize_text_field( $country_data['country_code'] ?? '' ) );
            update_post_meta( $cart_id, '_fcrc_location_country_name', sanitize_text_field( $country_data['country_name'] ?? '' ) );
            update_post_meta( $cart_id, '_fcrc_location_state', sanitize_text_field( $country_data['region'] ?? '' ) );
            update_post_meta( $cart_id, '_fcrc_location_city', sanitize_text_field( $country_data['city'] ?? '' ) );
            update_post_meta( $cart_id, '_fcrc_location_ip', sanitize_text_field( $country_data['ip'] ?? '' ) );

            wp_send_json_success( array(
                'message' => esc_html__( 'Dados de localização atualizados com sucesso.', 'fc-recovery-carts' )
            ));
        } catch ( \Exception $e ) {
            $this->handle_ajax_exception( __FUNCTION__, $e );
        }
    }

    
    /**
     * Get analytics data for analytics dashboard
     *
     * @since 1.3.0
     * @version 1.3.5
     * @return void
     */
    public function get_analytics_data_callback() {
        try {
            // Validate request
            $this->validate_ajax_request();

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( array(
                    'message' => esc_html__( 'Não autorizado.', 'fc-recovery-carts' )
                ) );
            }

            $period = isset( $_POST['period'] ) ? intval( $_POST['period'] ) : 7;
            $valid_periods = array();
            $period_filter = Admin_Components::period_filter();

            // Set valid periods
            foreach ( $period_filter as $key => $value ) {
                $valid_periods[] = $key;
            }

            if ( ! in_array( $period, $valid_periods, true ) ) {
                $period = 7;
            }

            // Query status
            $statuses = array(
                'lead', 'shopping', 'abandoned', 'order_abandoned', 'recovered', 'lost', 'purchased'
            );

            $carts_count = array();

            foreach ( $statuses as $status ) {
                $carts_count[ $status ] = fcrc_get_carts_count_by_status( $status, $period );
            }

            $recovered_chart_data = fcrc_get_daily_recovered_totals( $period );
            $recovered_total = array_sum( $recovered_chart_data['series'] );

            wp_send_json_success( array(
                'status' => 'success',
                'period' => $period,
                'counts' => $carts_count,
                'recovered_total' => $recovered_total,
                'recovered_chart' => $recovered_chart_data,
                'total_recovered_widget' => Admin_Components::get_total_recovered( $recovered_total, $period ),
                'cart_statuses_widget' => Admin_Components::get_cart_status( $period ),
                'notifications_chart' => fcrc_get_notifications_chart_data( $period ),
                'notifications_chart_widget' => Admin_Components::render_sent_notifications( $period ),
            ));
        } catch ( \Exception $e ) {
            $this->handle_ajax_exception( __FUNCTION__, $e );
        }
    }
}