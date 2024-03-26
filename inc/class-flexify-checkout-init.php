<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for init plugin
 * 
 * @since 1.0.0
 * @version 3.1.0
 * @package MeuMouse.com
 */
class Flexify_Checkout_Init {

  public $responseObj;
  public $licenseMessage;
  public $showMessage = false;
  public $activateLicense = false;
  public $deactivateLicense = false;
  
  /**
   * Construct function
   * 
   * @since 1.0.0
   * @return void
   */
  public function __construct() {
    // set default checkout fields options
    add_action( 'admin_init', array( $this, 'set_checkout_fields_steps_options' ) );

    // set default options
    add_action( 'admin_init', array( $this, 'flexify_checkout_set_default_options' ) );

    // connect with license api
    add_action( 'admin_init', array( $this, 'flexify_checkout_connect_api' ) );
  }


  /**
   * Set default options
   * 
   * @since 1.0.0
   * @return array
   */
  public function set_default_data_options() {
    $options = array(
      'enable_flexify_checkout' => 'yes',
      'enable_autofill_company_info' => 'no',
      'enable_street_number_field' => 'yes',
      'enable_back_to_shop_button' => 'no',
      'enable_skip_cart_page' => 'no',
      'enable_terms_is_checked_default' => 'yes',
      'enable_aditional_notes' => 'no',
      'enable_optimize_for_digital_products' => 'no',
      'enable_link_image_products' => 'no',
      'enable_fill_address' => 'yes',
      'enable_add_remove_products' => 'yes',
      'enable_ddi_phone_field' => 'no',
      'enable_hide_coupon_code_field' => 'no',
      'enable_auto_apply_coupon_code' => 'no',
      'enable_assign_guest_orders' => 'yes',
      'enable_inter_bank_pix_api' => 'no',
      'enable_inter_bank_ticket_api' => 'no',
      'checkout_header_type' => 'logo',
      'search_image_header_checkout' => '',
      'header_width_image_checkout' => '200',
      'unit_header_width_image_checkout' => 'px',
      'text_brand_checkout_header' => 'Checkout',
      'set_primary_color' => '#141D26',
      'set_primary_color_on_hover' => '#33404D',
      'set_placeholder_color' => '#33404D',
      'flexify_checkout_theme' => 'modern',
      'input_border_radius' => '0.5',
      'unit_input_border_radius' => 'rem',
      'set_font_family' => 'Inter',
      'h2_size' => '1.5',
      'h2_size_unit' => 'rem',
      'enable_thankyou_page_template' => 'yes',
      'pix_gateway_title' => 'Pix',
      'pix_gateway_description' => 'Pague via transferência imediata Pix a qualquer hora, a aprovação é imediata!',
      'pix_gateway_email_instructions' => 'Clique no botão abaixo para ver os dados de pagamento do seu Pix.',
      'pix_gateway_receipt_key' => '',
      'pix_gateway_expires' => '30',
      'bank_slip_gateway_title' => 'Boleto bancário',
      'bank_slip_gateway_description' => 'Pague com boleto. Aprovação de 1 a 3 dias úteis após o pagamento.',
      'bank_slip_gateway_email_instructions' => 'Clique no botão abaixo para acessar seu boleto ou utilize a linha digitável para pagar via Internet Banking.',
      'bank_slip_gateway_expires' => '3',
      'bank_slip_gateway_footer_message' => 'Pagamento do pedido #{order_id}. Não receber após o vencimento.',
      'inter_bank_client_id' => '',
      'inter_bank_client_secret' => '',
      'inter_bank_debug_mode' => 'no',
      'enable_set_country_from_ip' => 'yes',
      'get_user_ip_service' => 'https://api.ipify.org?format=json',
      'api_ip_param' => 'ip',
      'get_country_from_ip_service' => 'https://freeipapi.com/api/json/',
      'api_country_code_param' => 'api_ip_param',
      'enable_unset_wcbcf_fields_not_brazil' => 'no',
    );

    return $options;
  }


  /**
   * Gets the items from the array and inserts them into the option if it is empty,
   * or adds new items with default value to the option
   * 
   * @since 2.3.0
   * @return void
   */
  public function flexify_checkout_set_default_options() {
      $get_options = $this->set_default_data_options();
      $default_options = get_option('flexify_checkout_settings', array());

      if ( empty( $default_options ) ) {
          $options = $get_options;
          update_option('flexify_checkout_settings', $options);
      } else {
          $options = $default_options;
  
          foreach ( $get_options as $key => $value ) {
              if ( !isset( $options[$key] ) ) {
                  $options[$key] = $value;
              }
          }
  
          update_option('flexify_checkout_settings', $options);
      }
  }    


  /**
   * Checks if the option exists and returns the indicated array item
   * 
   * @since 1.0.0
   * @version 2.3.0
   * @param $key | Array key
   * @return mixed | string or false
   */
  public static function get_setting( $key ) {
    $default_options = get_option('flexify_checkout_settings', array());

    // check if array key exists and return key
    if ( isset( $default_options[$key] ) ) {
        return $default_options[$key];
    }

    return false;
  }


  /**
   * Get checkout step fields
   * 
   * @since 3.0.0
   * @return array
   */
  public static function get_wc_native_checkout_fields() {
    return array(
      'billing_email' => array(
        'id' => 'billing_email',
        'type' => 'email',
        'label' => esc_html__( 'Endereço de e-mail', 'flexify-checkout-for-woocommerce' ),
        'position' => 'full',
        'classes' => '',
        'label_classes' => '',
        'required' => 'yes',
        'priority' => '1',
        'source' => 'native',
        'enabled' => 'yes',
        'step' => '1',
      ),
      'billing_first_name' => array(
          'id' => 'billing_first_name',
          'type' => 'text',
          'label' => esc_html__( 'Nome', 'flexify-checkout-for-woocommerce' ),
          'position' => 'left',
          'classes' => '',
          'label_classes' => '',
          'required' => 'yes',
          'priority' => '2',
          'source' => 'native',
          'enabled' => 'yes',
          'step' => '1',
      ),
      'billing_last_name' => array(
        'id' => 'billing_last_name',
        'type' => 'text',
        'label' => esc_html__( 'Sobrenome', 'flexify-checkout-for-woocommerce' ),
        'position' => 'right',
        'classes' => '',
        'label_classes' => '',
        'required' => 'yes',
        'priority' => '3',
        'source' => 'native',
        'enabled' => 'yes',
        'step' => '1',
      ),
      'billing_phone' => array(
        'id' => 'billing_phone',
        'type' => 'tel',
        'label' => esc_html__( 'Telefone', 'flexify-checkout-for-woocommerce' ),
        'position' => 'full',
        'classes' => '',
        'label_classes' => '',
        'required' => 'yes',
        'priority' => '4',
        'source' => 'native',
        'enabled' => 'yes',
        'step' => '1',
      ),
      'billing_company' => array(
        'id' => 'billing_company',
        'type' => 'text',
        'label' => esc_html__( 'Empresa', 'flexify-checkout-for-woocommerce' ),
        'position' => 'full',
        'classes' => '',
        'label_classes' => '',
        'required' => 'yes',
        'priority' => '5',
        'source' => 'native',
        'enabled' => 'no',
        'step' => '1',
      ),
      'billing_country' => array(
        'id' => 'billing_country',
        'type' => 'select',
        'label' => esc_html__( 'País', 'flexify-checkout-for-woocommerce' ),
        'position' => 'full',
        'classes' => '',
        'label_classes' => '',
        'required' => 'yes',
        'priority' => '14',
        'source' => 'native',
        'enabled' => 'yes',
        'step' => '2',
      ),
      'billing_postcode' => array(
        'id' => 'billing_postcode',
        'type' => 'tel',
        'label' => esc_html__( 'CEP', 'flexify-checkout-for-woocommerce' ),
        'position' => 'full',
        'classes' => '',
        'label_classes' => '',
        'required' => 'yes',
        'priority' => '15',
        'source' => 'native',
        'enabled' => 'yes',
        'step' => '2',
      ),
      'billing_address_1' => array(
        'id' => 'billing_address_1',
        'type' => 'text',
        'label' => esc_html__( 'Endereço', 'flexify-checkout-for-woocommerce' ),
        'position' => 'left',
        'classes' => '',
        'label_classes' => '',
        'required' => 'yes',
        'priority' => '16',
        'source' => 'native',
        'enabled' => 'yes',
        'step' => '2',
      ),
      'billing_address_2' => array(
        'id' => 'billing_address_2',
        'type' => 'text',
        'label' => esc_html__( 'Apartamento, suíte, unidade, etc.', 'flexify-checkout-for-woocommerce' ),
        'position' => 'right',
        'classes' => '',
        'label_classes' => '',
        'required' => 'no',
        'priority' => '19',
        'source' => 'native',
        'enabled' => 'yes',
        'step' => '2',
      ),
      'billing_city' => array(
        'id' => 'billing_city',
        'type' => 'text',
        'label' => esc_html__( 'Cidade', 'flexify-checkout-for-woocommerce' ),
        'position' => 'left',
        'classes' => '',
        'label_classes' => '',
        'required' => 'yes',
        'priority' => '20',
        'source' => 'native',
        'enabled' => 'yes',
        'step' => '2',
      ),
      'billing_state' => array(
        'id' => 'billing_state',
        'type' => 'select',
        'label' => esc_html__( 'Estado', 'flexify-checkout-for-woocommerce' ),
        'position' => 'right',
        'classes' => '',
        'label_classes' => '',
        'required' => 'yes',
        'priority' => '21',
        'source' => 'native',
        'enabled' => 'yes',
        'step' => '2',
      ),
    );
  }


  /**
   * Get fields from Brazilian Market on WooCommerce plugin
   * 
   * @since 3.0.0
   * @return array
   */
  public static function get_wcbcf_fields() {
    return array(
      'billing_persontype' => array(
        'id' => 'billing_persontype',
        'type' => 'select',
        'label' => esc_html__( 'Tipo de Pessoa', 'flexify-checkout-for-woocommerce' ),
        'position' => 'full',
        'classes' => '',
        'label_classes' => '',
        'required' => 'yes',
        'priority' => '6',
        'source' => 'plugin',
        'enabled' => 'yes',
        'step' => '1',
      ),
      'billing_cpf' => array(
        'id' => 'billing_cpf',
        'type' => 'tel',
        'label' => esc_html__( 'CPF', 'flexify-checkout-for-woocommerce' ),
        'position' => 'full',
        'classes' => '',
        'label_classes' => '',
        'required' => 'yes',
        'priority' => '7',
        'source' => 'plugin',
        'enabled' => 'yes',
        'step' => '1',
      ),
      'billing_cnpj' => array(
        'id' => 'billing_cnpj',
        'type' => 'tel',
        'label' => esc_html__( 'CNPJ', 'flexify-checkout-for-woocommerce' ),
        'position' => 'full',
        'classes' => '',
        'label_classes' => '',
        'required' => 'yes',
        'priority' => '8',
        'source' => 'plugin',
        'enabled' => 'yes',
        'step' => '1',
      ),
      'billing_ie' => array(
        'id' => 'billing_ie',
        'type' => 'tel',
        'label' => esc_html__( 'Inscrição Estadual', 'flexify-checkout-for-woocommerce' ),
        'position' => 'full',
        'classes' => '',
        'label_classes' => '',
        'required' => 'no',
        'priority' => '9',
        'source' => 'plugin',
        'enabled' => 'no',
        'step' => '1',
      ),
      'billing_cellphone' => array(
        'id' => 'billing_cellphone',
        'type' => 'tel',
        'label' => esc_html__( 'Celular', 'flexify-checkout-for-woocommerce' ),
        'position' => 'full',
        'classes' => '',
        'label_classes' => '',
        'required' => 'no',
        'priority' => '10',
        'source' => 'plugin',
        'enabled' => 'no',
        'step' => '1',
      ),
      'billing_rg' => array(
        'id' => 'billing_rg',
        'type' => 'text',
        'label' => esc_html__( 'RG', 'flexify-checkout-for-woocommerce' ),
        'position' => 'full',
        'classes' => '',
        'label_classes' => '',
        'required' => 'no',
        'priority' => '11',
        'source' => 'plugin',
        'enabled' => 'no',
        'step' => '1',
      ),
      'billing_birthdate' => array(
        'id' => 'billing_birthdate',
        'type' => 'tel',
        'label' => esc_html__( 'Data de nascimento', 'flexify-checkout-for-woocommerce' ),
        'position' => 'full',
        'classes' => '',
        'label_classes' => '',
        'required' => 'no',
        'priority' => '12',
        'source' => 'plugin',
        'enabled' => 'no',
        'step' => '1',
      ),
      'billing_gender' => array(
        'id' => 'billing_gender',
        'type' => 'select',
        'label' => esc_html__( 'Gênero', 'flexify-checkout-for-woocommerce' ),
        'position' => 'full',
        'classes' => '',
        'label_classes' => '',
        'required' => 'no',
        'priority' => '13',
        'source' => 'plugin',
        'enabled' => 'no',
        'step' => '1',
      ),
      'billing_number' => array(
        'id' => 'billing_number',
        'type' => 'select',
        'label' => esc_html__( 'Número da residência', 'flexify-checkout-for-woocommerce' ),
        'position' => 'right',
        'classes' => '',
        'label_classes' => '',
        'required' => 'yes',
        'priority' => '17',
        'source' => 'plugin',
        'enabled' => 'yes',
        'step' => '2',
      ),
      'billing_neighborhood' => array(
        'id' => 'billing_neighborhood',
        'type' => 'text',
        'label' => esc_html__( 'Bairro', 'flexify-checkout-for-woocommerce' ),
        'position' => 'left',
        'classes' => '',
        'label_classes' => '',
        'required' => 'no',
        'priority' => '18',
        'source' => 'plugin',
        'enabled' => 'yes',
        'step' => '2',
      ),
    );
  }


  /**
   * Set default options checkout fields
   * 
   * @since 3.0.0
   * @version 3.1.0
   * @return void
   */
  public function set_checkout_fields_steps_options() {
    $get_fields = self::get_wc_native_checkout_fields();
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
          /**
           * Add integration with Brazilian Market on WooCommerce plugin
           * 
           * @since 1.0.0
           */
          if ( class_exists('Extra_Checkout_Fields_For_Brazil') && !isset( $get_field_options['billing_cpf'] ) ) {
            $wcbcf_fields = self::get_wcbcf_fields();
            $get_field_options = maybe_unserialize( $get_field_options );

            // Add Brazilian Market on WooCommerce fields to existing options
            $get_field_options = array_merge( $get_field_options, $wcbcf_fields );
            update_option('flexify_checkout_step_fields', maybe_serialize( $get_field_options ));
          }
    }
  }


  /**
   * Connect on API server for verify license
   * 
   * @since 1.0.0
   * @version 3.0.0
   * @return void
   */
  public function flexify_checkout_connect_api() {
    if ( current_user_can('manage_woocommerce') ) {
      $this->responseObj = new stdClass();
      $message = '';
      $license_key = get_option('flexify_checkout_license_key', '');
  
      // active license action
      if ( isset( $_POST['flexify_checkout_active_license'] ) ) {
        // clear response cache first
        delete_transient('flexify_checkout_api_request_cache');
        delete_transient('flexify_checkout_api_response_cache');

        update_option( 'flexify_checkout_license_key', $_POST );
        $license_key = !empty( $_POST['flexify_checkout_license_key'] ) ? $_POST['flexify_checkout_license_key'] : '';
        update_option( 'flexify_checkout_license_key', $license_key ) || add_option('flexify_checkout_license_key', $license_key );
        update_option( '_site_transient_update_plugins', '' );
      }

      if ( ! self::license_valid() ) {
        update_option( 'flexify_checkout_license_key', '' );
        update_option( 'flexify_checkout_license_status', 'invalid' );
      }

      // Check on the server if the license is valid and update responses and options
      if ( Flexify_Checkout_Api::CheckWPPlugin( $license_key, $this->licenseMessage, $this->responseObj, FLEXIFY_CHECKOUT_FILE ) ) {
          if ( $this->responseObj && $this->responseObj->is_valid ) {
            update_option( 'flexify_checkout_license_status', 'valid' );
          } else {
            update_option( 'flexify_checkout_license_status', 'invalid' );
          }

          if ( isset( $_POST['flexify_checkout_active_license'] ) && self::license_valid() ) {
            $this->activateLicense = true;
          }
      } else {
          if ( !empty( $license_key ) && !empty( $this->licenseMessage ) ) {
              $this->showMessage = true;
          }
      }

      // deactive license action
      if ( isset( $_POST['flexify_checkout_deactive_license'] ) ) {
        if ( Flexify_Checkout_Api::RemoveLicenseKey( FLEXIFY_CHECKOUT_FILE, $message ) ) {
          update_option( 'flexify_checkout_license_status', 'invalid' );
          delete_option( 'flexify_checkout_license_key' );
          update_option( '_site_transient_update_plugins', '' );
          delete_transient('flexify_checkout_api_request_cache');
          delete_transient('flexify_checkout_api_response_cache');
          delete_option('flexify_checkout_license_response_object');

          $this->deactivateLicense = true;
        }
      }

      // clear activation cache
      if ( isset( $_POST['flexify_checkout_clear_activation_cache'] ) || ! self::license_valid() ) {
        delete_transient('flexify_checkout_api_request_cache');
        delete_transient('flexify_checkout_api_response_cache');
        delete_option('flexify_checkout_license_response_object');
      }
    }
  }


  /**
   * Check if license is valid
   * 
   * @since 2.5.0
   * @version 3.0.0
   * @return bool
   */
  public static function license_valid() {
    $object_query = get_option('flexify_checkout_license_response_object');

    // clear api request and response cache if object is empty
    if ( empty( $object_query ) ) {
      delete_transient('flexify_checkout_api_request_cache');
      delete_transient('flexify_checkout_api_response_cache');
    }

    if ( ! empty( $object_query ) && isset( $object_query->is_valid )  ) {
      return true;
    } elseif ( empty( $object_query->status ) ) {
        delete_option('flexify_checkout_license_response_object');
        
        return false;
    } else {
        return false;
    }
  }


  /**
   * Get license title
   * 
   * @since 3.0.0
   * @return string
   */
  public static function license_title() {
    $object_query = get_option('flexify_checkout_license_response_object');

    if ( ! empty( $object_query ) && isset( $object_query->license_title ) ) {
      return $object_query->license_title;
    } else {
      return esc_html__(  'Não disponível', 'flexify-checkout-for-woocommerce' );
    }
  }


  /**
   * Get license expire date
   * 
   * @since 3.0.0
   * @return string
   */
  public static function license_expire() {
    $object_query = get_option('flexify_checkout_license_response_object');

    if ( ! empty( $object_query ) && isset( $object_query->expire_date ) ) {
      if ( $object_query->expire_date === 'No expiry' ) {
        return esc_html__( 'Nunca expira', 'flexify-checkout-for-woocommerce' );
      } else {
        if ( strtotime( $object_query->expire_date ) < time() ) {
          update_option( 'flexify_checkout_license_status', 'invalid' );
          delete_option('flexify_checkout_license_response_object');

          return esc_html__( 'Licença expirada', 'flexify-checkout-for-woocommerce' );
        }

        // get wordpress date format setting
        $date_format = get_option('date_format');

        return date( $date_format, strtotime( $object_query->expire_date ) );
      }
    }
  }
}

new Flexify_Checkout_Init();