<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Core;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Enqueue assets class
 * 
 * @since 1.0.0
 * @version 1.3.5
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Core
 * @author MeuMouse.com
 */
class Assets {

    /**
     * Get debug mode
     * 
     * @since 1.3.0
     * @return bool
     */
    public static $debug_mode = FC_RECOVERY_CARTS_DEBUG_MODE;

    /**
     * Get assets URL
     * 
     * @since 1.3.0
     * @return bool
     */
    public $assets_url = FC_RECOVERY_CARTS_ASSETS;

    /**
     * Get current version
     * 
     * @since 1.3.0
     * @return string
     */
    public $version = FC_RECOVERY_CARTS_VERSION;
   
    /**
     * Construct function
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        // register settings scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

        // register frontend scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
    }


    /**
     * Register admin scripts
     * 
     * @since 1.0.0
     * @version 1.3.5
     * @return void
     */
    public function admin_scripts() {
        $min_file = self::$debug_mode ? '' : '.min';

        // add scripts on all 'fc-recovery-carts' prefix pages, except 'fc-recovery-carts-list'
        if ( Helpers::check_admin_page('fc-recovery-carts') && ! Helpers::check_admin_page('fc-recovery-carts-list') && ! Helpers::check_admin_page('fc-recovery-carts-queue') ) {
            // check if Flexify Dashboard is active for prevent duplicate Bootstrap files
			if ( ! class_exists('Flexify_Dashboard') ) {
                wp_enqueue_style( 'bootstrap-grid', $this->assets_url . 'vendor/bootstrap/bootstrap-grid.min.css', array(), '5.3.3' );
                wp_enqueue_style( 'bootstrap-utilities', $this->assets_url . 'vendor/bootstrap/bootstrap-utilities.min.css', array(), '5.3.3' );
			}

            // EmojioneArea library
			wp_enqueue_style( 'fcrc-emojionearea-styles', $this->assets_url . 'vendor/emojionearea/emojionearea.min.css', array(), '3.4.1' );
            wp_enqueue_script( 'fcrc-emojionearea-scripts', $this->assets_url . 'vendor/emojionearea/emojionearea.min.js', array('jquery'), '3.4.1' );

            // settings scripts
			wp_enqueue_style( 'fc-recovery-carts-styles', $this->assets_url . 'admin/css/settings'. $min_file .'.css', array(), $this->version );
			wp_enqueue_script( 'fc-recovery-carts-scripts', $this->assets_url . 'admin/js/settings'. $min_file .'.js', array('jquery'), $this->version, true );

			// settings params
			wp_localize_script( 'fc-recovery-carts-scripts', 'fcrc_settings_params', array(
				'debug_mode' => self::$debug_mode,
				'ajax_url' => admin_url('admin-ajax.php'),
                'ajax_nonce' => wp_create_nonce('fcrc_ajax_nonce'),
				'i18n' => array(
					'toast_aria_label' => esc_html__( 'Fechar', 'fc-recovery-carts' ),
                    'confirm_delete_follow_up' => esc_html__( 'Tem certeza que deseja excluir este evento?', 'fc-recovery-carts' ),
                    'emoji_picker' => array(
						'placeholder' => esc_html__( 'Pesquisar', 'fc-recovery-carts' ),
						'button_title' => esc_html__( 'Use a tecla TAB para inserir um emoji rapidamente', 'fc-recovery-carts' ),
						'filters' => array(
							'tones_title' => esc_html__( 'Diversidade', 'fc-recovery-carts' ),
							'recent_title' => esc_html__( 'Recentes', 'fc-recovery-carts' ),
							'smileys_people_title' => esc_html__( 'Sorrisos e Pessoas', 'fc-recovery-carts' ),
							'animals_nature_title' => esc_html__( 'Animais e Natureza', 'fc-recovery-carts' ),
							'food_drink_title' => esc_html__( 'Comidas e Bebidas', 'fc-recovery-carts' ),
							'activity_title' => esc_html__( 'Atividades', 'fc-recovery-carts' ),
							'travel_places_title' => esc_html__( 'Viajens e Lugares', 'fc-recovery-carts' ),
							'objects_title' => esc_html__( 'Objetos', 'fc-recovery-carts' ),
							'symbols_title' => esc_html__( 'Símbolos', 'fc-recovery-carts' ),
							'flags_title' => esc_html__( 'Bandeiras', 'fc-recovery-carts' ),
						),
					),
				),
                'enable_international_phone' => Admin::get_switch('enable_international_phone_modal'),
			));
        }

        // table scripts
        if ( Helpers::check_admin_page('fc-recovery-carts-list') ) {
            // carts table scripts
			wp_enqueue_style( 'fc-recovery-carts-table-styles', $this->assets_url . 'admin/css/carts-table'. $min_file .'.css', array(), $this->version );
            wp_enqueue_script( 'fc-recovery-carts-table-scripts', $this->assets_url . 'admin/js/carts-table'. $min_file .'.js', array('jquery'), $this->version, true );

            wp_localize_script( 'fc-recovery-carts-table-scripts', 'fcrc_carts_table_params', array(
                'debug_mode' => self::$debug_mode,
                'ajax_url' => admin_url('admin-ajax.php'),
                'ajax_nonce' => wp_create_nonce('fcrc_ajax_nonce'),
                'poll_interval' => (int) apply_filters( 'Flexify_Checkout/Recovery_Carts/Carts_Table/Poll_Interval', 10000 ),
            ));
        }

        // analytics scripts
        if ( Helpers::check_admin_page('fc-recovery-carts') && ! Helpers::check_admin_page('fc-recovery-carts-list') && ! Helpers::check_admin_page('fc-recovery-carts-queue') && ! Helpers::check_admin_page('fc-recovery-carts-settings') ) {
            // Apexcharts library
			wp_enqueue_style( 'apexcharts-styles', $this->assets_url . 'vendor/apexcharts/apexcharts.css', array(), '4.3.0' );
            wp_enqueue_script( 'apexcharts-scripts', $this->assets_url . 'vendor/apexcharts/apexcharts.min.js', array(), '4.3.0' );

            wp_enqueue_style( 'fc-recovery-carts-analytics-styles', $this->assets_url . 'admin/css/analytics'. $min_file .'.css', array(), $this->version );
			wp_enqueue_script( 'fc-recovery-carts-analytics-scripts', $this->assets_url . 'admin/js/analytics'. $min_file .'.js', array('jquery'), $this->version, true );

            // analytics params
			wp_localize_script( 'fc-recovery-carts-analytics-scripts', 'fcrc_analytics_params', array(
				'debug_mode' => self::$debug_mode,
				'ajax_url' => admin_url('admin-ajax.php'),
				'i18n' => array(
					'toast_aria_label' => esc_html__( 'Fechar', 'fc-recovery-carts' ),
                    'total_recovered' => __( 'Valor recuperado', 'fc-recovery-carts' ),
                    'notifications_chart' => __( 'Notificações', 'fc-recovery-carts' ),
				),
                'currency' => array(
                    'symbol' => html_entity_decode( get_woocommerce_currency_symbol() ),
                    'position' => get_option('woocommerce_currency_pos'), // 'left', 'right', 'left_space', 'right_space'
                    'decimal_separator' => wc_get_price_decimal_separator(),
                    'thousand_separator' => wc_get_price_thousand_separator(),
                    'decimals' => wc_get_price_decimals(),
                ),
			));
        }
    }


    /**
     * Register frontend scripts
     * 
     * @since 1.0.0
     * @version 1.3.0
     * @return void
     */
    public function frontend_scripts() {
        $min_file = self::$debug_mode ? '' : '.min';

        if ( Helpers::is_product() && ! is_flexify_checkout() ) {
            if ( Admin::get_switch('enable_international_phone_modal') === 'yes' ) {
                wp_enqueue_style( 'fc-recovery-carts-events-intl-tel-input-styles', $this->assets_url . 'vendor/intl-tel-input/css/intlTelInput'. $min_file .'.css', array(), '24.6.0' );
                wp_enqueue_style( 'fc-recovery-carts-events-intl-tel-input-styles-flag-offset-2x', $this->assets_url . 'vendor/intl-tel-input/css/flag-offset-2x.min.css', array(), $this->version );
                wp_enqueue_script( 'fc-recovery-carts-events-intl-tel-input', $this->assets_url . 'vendor/intl-tel-input/js/intlTelInput'. $min_file .'.js', array(), '24.6.0' );
            }

            wp_enqueue_style( 'fc-recovery-carts-elements-styles', $this->assets_url . 'frontend/css/fcrc-elements'. $min_file .'.css', array(), $this->version );
        }

        wp_enqueue_script( 'fc-recovery-carts-events-script', $this->assets_url . 'frontend/js/events'. $min_file .'.js', array('jquery'), $this->version, true );

        // events params
        wp_localize_script( 'fc-recovery-carts-events-script', 'fcrc_events_params', array(
            'debug_mode' => self::$debug_mode,
            'ajax_url' => admin_url('admin-ajax.php'),
            'triggers_list' => Admin::get_setting('collect_lead_modal')['triggers_list'],
            'path_to_utils' => $this->assets_url . 'vendor/intl-tel-input/js/utils.js',
            'i18n' => array(
                'intl_search_input_placeholder' => esc_html__( 'Pesquisar', 'fc-recovery-carts' ),
            ),
            'enable_international_phone' => Admin::get_switch('enable_international_phone_modal'),
            'is_product' => Helpers::is_product(),
            'abandonment_time_seconds' => Helpers::get_abandonment_time_seconds(),
            'ip_settings' => array(
                'enabled' => Admin::get_switch('enable_get_location_from_ip'),
                'get_ip' => 'https://api.ipify.org/?format=json',
                'ip_url' => Admin::get_setting('ip_api_settings')['ip_api_url'],
                'country_code' => Admin::get_setting('ip_api_settings')['country_code_map'],
                'country_name' => Admin::get_setting('ip_api_settings')['country_name_map'],
                'state_name' => Admin::get_setting('ip_api_settings')['state_name_map'],
                'city_name' => Admin::get_setting('ip_api_settings')['city_name_map'],
                'ip_returned' => Admin::get_setting('ip_api_settings')['ip_map'],
            ),
        ));

        // add checkout events
        if ( function_exists('is_flexify_checkout') && is_flexify_checkout() || is_checkout() ) {
            wp_enqueue_script( 'fc-recovery-carts-checkout-events-script', $this->assets_url . 'frontend/js/checkout-events'. $min_file .'.js', array('jquery'), $this->version, true );

            // checkout events params
            wp_localize_script( 'fc-recovery-carts-events-script', 'fcrc_checkout_params', array(
                'debug_mode' => self::$debug_mode,
                'ajax_url' => admin_url('admin-ajax.php'),
            ));
        }
    }
}