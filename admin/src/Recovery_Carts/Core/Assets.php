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

        // Recovery pages migrated to the Vue SPA enqueue their assets via
        // Core\Settings_Assets (Vite). Skip the legacy admin assets there so
        // they don't load against a DOM that no longer exists.
        $is_analytics_page = Helpers::check_admin_page('fc-recovery-carts')
            && ! Helpers::check_admin_page('fc-recovery-carts-list')
            && ! Helpers::check_admin_page('fc-recovery-carts-queue')
            && ! Helpers::check_admin_page('fc-recovery-carts-settings');

        if (
            $is_analytics_page
            || Helpers::check_admin_page('fc-recovery-carts-list')
            || Helpers::check_admin_page('fc-recovery-carts-queue')
        ) {
            return;
        }

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
					'toast_aria_label' => esc_html__( 'Close', 'flexify-checkout-for-woocommerce' ),
                    'confirm_delete_follow_up' => esc_html__( 'Are you sure you want to delete this event?', 'flexify-checkout-for-woocommerce' ),
                    'emoji_picker' => array(
						'placeholder' => esc_html__( 'Search', 'flexify-checkout-for-woocommerce' ),
						'button_title' => esc_html__( 'Use the TAB key to quickly insert an emoji', 'flexify-checkout-for-woocommerce' ),
						'filters' => array(
							'tones_title' => esc_html__( 'Diversity', 'flexify-checkout-for-woocommerce' ),
							'recent_title' => esc_html__( 'Recent', 'flexify-checkout-for-woocommerce' ),
							'smileys_people_title' => esc_html__( 'Smileys & People', 'flexify-checkout-for-woocommerce' ),
							'animals_nature_title' => esc_html__( 'Animals & Nature', 'flexify-checkout-for-woocommerce' ),
							'food_drink_title' => esc_html__( 'Food & Drink', 'flexify-checkout-for-woocommerce' ),
							'activity_title' => esc_html__( 'Activities', 'flexify-checkout-for-woocommerce' ),
							'travel_places_title' => esc_html__( 'Travel & Places', 'flexify-checkout-for-woocommerce' ),
							'objects_title' => esc_html__( 'Objects', 'flexify-checkout-for-woocommerce' ),
							'symbols_title' => esc_html__( 'Symbols', 'flexify-checkout-for-woocommerce' ),
							'flags_title' => esc_html__( 'Flags', 'flexify-checkout-for-woocommerce' ),
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
					'toast_aria_label' => esc_html__( 'Close', 'flexify-checkout-for-woocommerce' ),
                    'total_recovered' => __( 'Recovered value', 'flexify-checkout-for-woocommerce' ),
                    'notifications_chart' => __( 'Notifications', 'flexify-checkout-for-woocommerce' ),
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
                'intl_search_input_placeholder' => esc_html__( 'Search', 'flexify-checkout-for-woocommerce' ),
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