<?php

namespace MeuMouse\Flexify_Checkout\Admin;

use MeuMouse\Flexify_Checkout\Admin\Default_Options;
use MeuMouse\Flexify_Checkout\Core\Helpers;
use MeuMouse\Flexify_Checkout\API\License;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Class to handle plugin admin panel objects and functions
 * 
 * @since 1.0.0
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Admin_Options {

    /**
     * Set settings tabs directory
     * 
     * @since 5.0.0
     * @return string
     */
    public $settings_tabs_dir = FLEXIFY_CHECKOUT_SETTINGS_TABS_DIR;

    /**
     * Construct function
     *
     * @since 1.0.0
     * @version 5.0.0
     * @return void
     */
    public function __construct() {
        // set default options
        add_action( 'admin_init', array( $this, 'set_default_options' ) );

        // set default checkout fields options
        add_action( 'admin_init', array( $this, 'set_checkout_step_fields' ) );

        // add submenu on WooCommerce
        add_action( 'admin_menu', array( $this, 'add_woo_submenu' ) );

        // render settings tabs
        add_action( 'Flexify_Checkout/Admin/Register_Settings_Tabs', array( $this, 'render_settings_tabs' ) );

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
     * @version 5.0.0
     * @return void
     */
    public function set_default_options() {
        $default_options = ( new Default_Options() )->set_default_data_options();
        $get_options = get_option('flexify_checkout_settings', array());

        // if empty settings
        if ( empty( $get_options ) ) {
            update_option( 'flexify_checkout_settings', $default_options );
        } else {
            // iterate for each plugin settings
            foreach ( $get_options as $option => $value ) {
                // iterate for each default settings
                foreach ( $default_options as $index => $option_value ) {
                    if ( ! isset( $get_options[$index] ) ) {
                        $get_options[$index] = $option_value;
                    }
                }
            }

            update_option( 'flexify_checkout_settings', $get_options );
        }
    }


    /**
     * Set default options checkout fields
     * 
     * @since 3.0.0
     * @version 5.0.0
     * @return void
     */
    public function set_checkout_step_fields() {
        $default_options = new Default_Options();
        $get_fields = $default_options->get_native_checkout_fields();
        $get_field_options = get_option('flexify_checkout_step_fields', array());
        $get_field_options = maybe_unserialize( $get_field_options );

        // create options if array is empty
        if ( empty( $get_field_options ) ) {
            $fields = array();

            foreach ( $get_fields as $key => $value ) {
                $fields[$key] = $value;
            }

            update_option('flexify_checkout_step_fields', maybe_serialize( $fields ) );
        } else {
            foreach ( $get_fields as $key => $value ) {
                if ( ! isset( $get_field_options[$key] ) ) {
                    $get_field_options[$key] = $value;
                }
            }

            update_option( 'flexify_checkout_step_fields', maybe_serialize( $get_field_options ) );
        }

        /**
         * Add integration with Brazilian Market on WooCommerce plugin
         * 
         * @since 1.0.0
         */
        if ( class_exists('Extra_Checkout_Fields_For_Brazil') && ! isset( $get_field_options['billing_cpf'] ) ) {
            $get_field_options = maybe_unserialize( $get_field_options );

            // Add Brazilian Market on WooCommerce fields to existing options
            $wcbcf_fields = $default_options->get_brazilian_checkout_fields();
            $get_field_options = array_merge( $get_field_options, $wcbcf_fields );

            update_option( 'flexify_checkout_step_fields', maybe_serialize( $get_field_options ) );
        }
    }
    

    /**
     * Function for create submenu in WooCommerce
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @return array
     */
    public function add_woo_submenu() {
        add_submenu_page(
            'woocommerce', // parent page slug
            esc_html__( 'Flexify Checkout para WooCommerce', 'flexify-checkout-for-woocommerce' ), // page title
            esc_html__( 'Flexify Checkout', 'flexify-checkout-for-woocommerce' ), // submenu title
            'manage_woocommerce', // user capabilities
            'flexify-checkout-for-woocommerce', // page slug
            array( $this, 'render_settings_page' ), // public function for print content page
        );
    }


    /**
     * Plugin general setting page and save options
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_settings_page() {
        include_once FLEXIFY_CHECKOUT_PATH . 'Views/Settings.php';
    }


    /**
     * Checks if the option exists and returns the indicated array item
     * 
     * @since 1.0.0
     * @version 5.0.0
     * @param $key | Array key
     * @return mixed | string or false
     */
    public static function get_setting( $key ) {
        $default_options = get_option( 'flexify_checkout_settings', array() );

        // check if array key exists and return key
        if ( isset( $default_options[$key] ) ) {
            return $default_options[$key];
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


    /**
	 * Register settings tabs
	 * 
	 * @since 5.0.0
	 * @return array
	 */
	public static function get_settings_tabs() {
		return apply_filters( 'Flexify_Checkout/Admin/Register_Settings_Tabs', array(
            'general' => array(
                'id' => 'general',
                'label' => esc_html__( 'Geral', 'flexify-checkout-for-woocommerce' ),
                'icon' => '<svg class="flexify-checkout-tab-icon"><path d="M7.5 14.5c-1.58 0-2.903 1.06-3.337 2.5H2v2h2.163c.434 1.44 1.757 2.5 3.337 2.5s2.903-1.06 3.337-2.5H22v-2H10.837c-.434-1.44-1.757-2.5-3.337-2.5zm0 5c-.827 0-1.5-.673-1.5-1.5s.673-1.5 1.5-1.5S9 17.173 9 18s-.673 1.5-1.5 1.5zm9-11c-1.58 0-2.903 1.06-3.337 2.5H2v2h11.163c.434 1.44 1.757 2.5 3.337 2.5s2.903-1.06 3.337-2.5H22v-2h-2.163c-.434-1.44-1.757-2.5-3.337-2.5zm0 5c-.827 0-1.5-.673-1.5-1.5s.673-1.5 1.5-1.5 1.5.673 1.5 1.5-.673 1.5-1.5 1.5z"></path><path d="M12.837 5C12.403 3.56 11.08 2.5 9.5 2.5S6.597 3.56 6.163 5H2v2h4.163C6.597 8.44 7.92 9.5 9.5 9.5s2.903-1.06 3.337-2.5h9.288V5h-9.288zM9.5 7.5C8.673 7.5 8 6.827 8 6s.673-1.5 1.5-1.5S11 5.173 11 6s-.673 1.5-1.5 1.5z"></path></svg>',
                'file' => $this->settings_tabs_dir . 'General.php',
            ),
            'texts' => array(
                'id' => 'texts',
                'label' => esc_html__( 'Textos', 'flexify-checkout-for-woocommerce' ),
                'icon' => '<svg class="flexify-checkout-tab-icon" viewBox="0 0 24 24"><path d="M5 8h2V6h3.252L7.68 18H5v2h8v-2h-2.252L13.32 6H17v2h2V4H5z"></path></svg>',
                'file' => $this->settings_tabs_dir . 'Texts.php',
            ),
            'fields' => array(
                'id' => 'fields',
                'label' => esc_html__( 'Campos e etapas', 'flexify-checkout-for-woocommerce' ),
                'icon' => '<svg class="flexify-checkout-tab-icon"><path d="M19 15v-3h-2v3h-3v2h3v3h2v-3h3v-2h-.937zM4 7h11v2H4zm0 4h11v2H4zm0 4h8v2H4z"></path></svg>',
                'file' => $this->settings_tabs_dir . 'Fields.php',
            ),
			'conditions' => array(
                'id' => 'conditions',
                'label' => esc_html__( 'Condições', 'flexify-checkout-for-woocommerce' ),
                'icon' => '<svg class="flexify-checkout-tab-icon" xmlns="http://www.w3.org/2000/svg"><path d="M21 3H5a1 1 0 0 0-1 1v2.59c0 .523.213 1.037.583 1.407L10 13.414V21a1.001 1.001 0 0 0 1.447.895l4-2c.339-.17.553-.516.553-.895v-5.586l5.417-5.417c.37-.37.583-.884.583-1.407V4a1 1 0 0 0-1-1zm-6.707 9.293A.996.996 0 0 0 14 13v5.382l-2 1V13a.996.996 0 0 0-.293-.707L6 6.59V5h14.001l.002 1.583-5.71 5.71z"></path></svg>',
                'file' => $this->settings_tabs_dir . 'Conditions.php',
            ),
			'integrations' => array(
                'id' => 'integrations',
                'label' => esc_html__( 'Integrações', 'flexify-checkout-for-woocommerce' ),
                'icon' => '<svg class="flexify-checkout-tab-icon"><path d="M3 8h2v5c0 2.206 1.794 4 4 4h2v5h2v-5h2c2.206 0 4-1.794 4-4V8h2V6H3v2zm4 0h10v5c0 1.103-.897 2-2 2H9c-1.103 0-2-.897-2-2V8zm0-6h2v3H7zm8 0h2v3h-2z"></path></svg>',
                'file' => $this->settings_tabs_dir . 'Integrations.php',
            ),
			'styles' => array(
                'id' => 'styles',
                'label' => esc_html__( 'Estilos', 'flexify-checkout-for-woocommerce' ),
                'icon' => '<svg class="flexify-checkout-tab-icon"><path d="M13.4 2.096a10.08 10.08 0 0 0-8.937 3.331A10.054 10.054 0 0 0 2.096 13.4c.53 3.894 3.458 7.207 7.285 8.246a9.982 9.982 0 0 0 2.618.354l.142-.001a3.001 3.001 0 0 0 2.516-1.426 2.989 2.989 0 0 0 .153-2.879l-.199-.416a1.919 1.919 0 0 1 .094-1.912 2.004 2.004 0 0 1 2.576-.755l.412.197c.412.198.85.299 1.301.299A3.022 3.022 0 0 0 22 12.14a9.935 9.935 0 0 0-.353-2.76c-1.04-3.826-4.353-6.754-8.247-7.284zm5.158 10.909-.412-.197c-1.828-.878-4.07-.198-5.135 1.494-.738 1.176-.813 2.576-.204 3.842l.199.416a.983.983 0 0 1-.051.961.992.992 0 0 1-.844.479h-.112a8.061 8.061 0 0 1-2.095-.283c-3.063-.831-5.403-3.479-5.826-6.586-.321-2.355.352-4.623 1.893-6.389a8.002 8.002 0 0 1 7.16-2.664c3.107.423 5.755 2.764 6.586 5.826.198.73.293 1.474.282 2.207-.012.807-.845 1.183-1.441.894z"></path><circle cx="7.5" cy="14.5" r="1.5"></circle><circle cx="7.5" cy="10.5" r="1.5"></circle><circle cx="10.5" cy="7.5" r="1.5"></circle><circle cx="14.5" cy="7.5" r="1.5"></circle></svg>',
                'file' => $this->settings_tabs_dir . 'Styles.php',
            ),
            'about' => array(
                'id' => 'about',
                'label' => esc_html__( 'Sobre', 'flexify-checkout-for-woocommerce' ),
                'icon' => '<svg class="flexify-checkout-tab-icon"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>>',
                'file' => $this->settings_tabs_dir . 'About.php',
            ),
        ));
	}


    /**
     * Render settings nav tabs
     *
     * @since 5.0.0
	 * @return void
     */
    public function render_settings_tabs() {
        $tabs = self::get_settings_tabs();

        foreach ( $tabs as $tab ) {
            printf( '<a href="#%1$s" class="nav-tab">%2$s %3$s</a>', esc_attr( $tab['id'] ), $tab['icon'], $tab['label'] );
        }
    }
}