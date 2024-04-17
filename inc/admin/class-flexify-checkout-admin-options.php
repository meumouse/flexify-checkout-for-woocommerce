<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

class Flexify_Checkout_Admin_Options extends Flexify_Checkout_Init {

  /**
   * Flexify_Checkout_Admin constructor.
   *
   * @since 1.0.0
   * @version 3.3.0
   * @package MeuMouse.com
   */
  public function __construct() {
    parent::__construct();

    // add submenu on WooCommerce
    add_action( 'admin_menu', array( $this, 'flexify_checkout_admin_menu' ) );

    // get AJAX calls on change options
    add_action( 'wp_ajax_flexify_checkout_ajax_save_options', array( $this, 'flexify_checkout_ajax_save_options_callback' ) );

    // get AJAX call from upload files from Inter bank module
    add_action( 'wp_ajax_upload_file', array( $this, 'upload_files_callback' ) );

    // Inter bank module actions
    add_action( 'admin_init', array( $this, 'inter_bank_module_actions' ) );

    // install Inter bank module in AJAX
    add_action( 'wp_ajax_install_inter_bank_module', array( $this, 'install_inter_bank_module_callback' ) );

    // remove checkout fields on click delete button
    add_action( 'wp_ajax_remove_checkout_fields', array( $this, 'remove_checkout_fields_callback' ) );

    // processing new field
    add_action( 'wp_ajax_add_new_field_to_checkout', array( $this, 'add_new_field_to_checkout_callback' ) );

    // get AJAX call from upload files from alternative activation license
    add_action( 'wp_ajax_alternative_activation_license', array( $this, 'alternative_activation_license_callback' ) );
  }

  /**
   * Function for create submenu in WooCommerce
   * 
   * @since 1.0.0
   * @access public
   * @return array
   */
  public function flexify_checkout_admin_menu() {
    add_submenu_page(
      'woocommerce', // parent page slug
      esc_html__( 'Flexify Checkout para WooCommerce', 'flexify-checkout-for-woocommerce'), // page title
      esc_html__( 'Flexify Checkout', 'flexify-checkout-for-woocommerce'), // submenu title
      'manage_woocommerce', // user capabilities
      'flexify-checkout-for-woocommerce', // page slug
      array( $this, 'flexify_checkout_settings_page' ), // public function for print content page
    );
  }


  /**
   * Plugin general setting page and save options
   * 
   * @since 1.0.0
   * @return void
   */
  public function flexify_checkout_settings_page() {
    include_once FLEXIFY_CHECKOUT_PATH . 'inc/admin/settings.php';
  }


  /**
   * Save options in AJAX
   * 
   * @since 1.0.0
   * @version 3.3.0
   * @return void
   * @package MeuMouse.com
   */
  public function flexify_checkout_ajax_save_options_callback() {
    if ( isset( $_POST['form_data'] ) ) {
        // Convert serialized data into an array
        parse_str( $_POST['form_data'], $form_data );

        $options = get_option( 'flexify_checkout_settings' );
        $options['enable_flexify_checkout'] = isset( $form_data['enable_flexify_checkout'] ) ? 'yes' : 'no';
        $options['enable_autofill_company_info'] = isset( $form_data['enable_autofill_company_info'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_back_to_shop_button'] = isset( $form_data['enable_back_to_shop_button'] ) ? 'yes' : 'no';
        $options['enable_skip_cart_page'] = isset( $form_data['enable_skip_cart_page'] ) ? 'yes' : 'no';
        $options['enable_terms_is_checked_default'] = isset( $form_data['enable_terms_is_checked_default'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_aditional_notes'] = isset( $form_data['enable_aditional_notes'] ) ? 'yes' : 'no';
        $options['enable_optimize_for_digital_products'] = isset( $form_data['enable_optimize_for_digital_products'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_link_image_products'] = isset( $form_data['enable_link_image_products'] ) ? 'yes' : 'no';
        $options['enable_fill_address'] = isset( $form_data['enable_fill_address'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_add_remove_products'] = isset( $form_data['enable_add_remove_products'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_ddi_phone_field'] = isset( $form_data['enable_ddi_phone_field'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_hide_coupon_code_field'] = isset( $form_data['enable_hide_coupon_code_field'] ) ? 'yes' : 'no';
        $options['enable_auto_apply_coupon_code'] = isset( $form_data['enable_auto_apply_coupon_code'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_assign_guest_orders'] = isset( $form_data['enable_assign_guest_orders'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_inter_bank_pix_api'] = isset( $form_data['enable_inter_bank_pix_api'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_inter_bank_ticket_api'] = isset( $form_data['enable_inter_bank_ticket_api'] ) && self::license_valid() ? 'yes' : 'no';
        $options['flexify_checkout_theme'] = isset( $form_data['flexify_checkout_theme'] ) ? 'modern' : 'modern';
        $options['enable_thankyou_page_template'] = isset( $form_data['enable_thankyou_page_template'] ) ? 'yes' : 'no';
        $options['inter_bank_debug_mode'] = isset( $form_data['inter_bank_debug_mode'] ) ? 'yes' : 'no';
        $options['enable_unset_wcbcf_fields_not_brazil'] = isset( $form_data['enable_unset_wcbcf_fields_not_brazil'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_manage_fields'] = isset( $form_data['enable_manage_fields'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_display_local_pickup_kangu'] = isset( $form_data['enable_display_local_pickup_kangu'] ) ? 'yes' : 'no';

        // check if form data exists "checkout_step" name and is array
        if ( isset( $form_data['checkout_step'] ) && is_array( $form_data['checkout_step'] ) ) {
          $form_data_fields = $form_data['checkout_step'];
          $fields = get_option('flexify_checkout_step_fields', array());
          $fields = maybe_unserialize( $fields );

          // Iterate through the updated data
          foreach ( $form_data_fields as $index => $value ) {
              // update enabled field on change
              if ( ! isset( $value['enabled'] ) ) {
                $fields[$index]['enabled'] = 'no';
              }

              // update required field on change
              if ( ! isset( $value['required'] ) ) {
                $fields[$index]['required'] = 'no';
              }

              // update priority on change
              if ( isset( $value['priority'] ) ) {
                $fields[$index]['priority'] = $value['priority'];
              }

              // update step on change
              if ( isset( $value['step'] ) ) {
                $fields[$index]['step'] = $value['step'];
              }

              // update label on change
              if ( isset( $value['label'] ) ) {
                $fields[$index]['label'] = $value['label'];
              }

              // update label class on change
              if ( isset( $value['label_classes'] ) ) {
                $fields[$index]['label_classes'] = $value['label_classes'];
              }

              // update field class on change
              if ( isset( $value['classes'] ) ) {
                $fields[$index]['classes'] = $value['classes'];
              }

              // update position on change
              if ( isset( $value['position'] ) ) {
                $fields[$index]['position'] = $value['position'];
              }

              // Merge updated data with existing tab data
              $fields[$index] = array_merge( $fields[$index], $value );
          }

          // Update option with merged data
          if ( self::license_valid() ) {
            update_option('flexify_checkout_step_fields', maybe_serialize( $fields ));
          }
        }

        // Merge the form data with the default options
        $updated_options = wp_parse_args( $form_data, $options );

        // Save the updated options
        update_option( 'flexify_checkout_settings', $updated_options );

        $response = array(
          'status' => 'success',
          'options' => $updated_options,
        );

        echo wp_json_encode( $response ); // Send JSON response
    }

    wp_die();
  }


  /**
   * Remove checkout fields
   * 
   * @since 3.2.0
   * @return void
   */
  public function remove_checkout_fields_callback() {
    if ( isset( $_POST['field_to_remove'] ) ) {
      $field_to_remove = sanitize_text_field( $_POST['field_to_remove'] );
 
      // Get the current fields options
      $get_fields = get_option('flexify_checkout_step_fields', array());
      $get_fields = maybe_unserialize( $get_fields );
 
      // Remove the field with the specified index
      if ( isset( $get_fields[$field_to_remove] ) ) {
        unset( $get_fields[$field_to_remove] );
 
        // Update the fields options
        update_option('flexify_checkout_step_fields', maybe_serialize( $get_fields ));
      }
 
      $response = array(
        'status' => 'success',
        'field' => $field_to_remove,
      );

      echo wp_json_encode( $response ); // Send JSON response
    }
 
    wp_die();
  }


  /**
   * Processing form on add new field to checkout
   * 
   * @since 3.2.0
   * @return void
   */
  public function add_new_field_to_checkout_callback() {
    if ( isset( $_POST['get_field_id'] ) ) {
      $field_id = sanitize_text_field( $_POST['get_field_id'] );

      // Get the current fields options
      $get_fields = get_option('flexify_checkout_step_fields', array());
      $get_fields = maybe_unserialize( $get_fields );

      if ( ! isset( $get_fields[$field_id] ) ) {
        $new_field = array(
          $field_id => array(
            'id' => $field_id,
            'type' => sanitize_text_field( $_POST['get_field_type'] ),
            'label' => sanitize_text_field( $_POST['get_field_label'] ),
            'position' => sanitize_text_field( $_POST['get_field_position'] ),
            'classes' => sanitize_text_field( $_POST['get_field_classes'] ),
            'label_classes' => sanitize_text_field( $_POST['get_field_label_classes'] ),
            'required' => sanitize_text_field( $_POST['get_field_required'] ),
            'priority' => sanitize_text_field( $_POST['get_field_priority'] ),
            'source' => sanitize_text_field( $_POST['get_field_source'] ),
            'enabled' => 'yes',
            'step' => sanitize_text_field( $_POST['get_field_step'] ),
            'options' => isset( $_POST['get_field_options_for_select'] ) ? $_POST['get_field_options_for_select'] : null,
          )
        );

        // merge new field with existing fields
        $new_field = array_merge( $get_fields, $new_field );

        // Update the fields options
        update_option('flexify_checkout_step_fields', maybe_serialize( $new_field ));
      }

      $response = array(
        'status' => 'success',
      );

      echo wp_send_json( $response ); // Send JSON response
    }

    wp_die();
  }


  /**
   * Processing files uploaded for Inter Bank module
   * 
   * @since 2.3.0
   * @return void
   */
  public function upload_files_callback() {
    // Checks if the file upload action has been triggered
    if ( isset( $_POST['action'] ) && $_POST['action'] == 'upload_file' ) {
        $uploads_dir = wp_upload_dir();
        $upload_path = $uploads_dir['basedir'] . '/flexify_checkout_integrations/';

        // Checks if the file was sent
        if ( !empty( $_FILES["file"] ) ) {
            $file = $_FILES["file"];
            $type = $_POST["type"];

            // Checks if it is a .crt or .key file
            if ( ( $type === "dropzone-crt" && pathinfo( $file["name"], PATHINFO_EXTENSION ) === "crt") || ( $type === "dropzone-key" && pathinfo( $file["name"], PATHINFO_EXTENSION ) === "key" ) ) {
                $file_tmp_name = $file["tmp_name"];
                $new_file_name = generate_hash(20) . ( $type === "dropzone-crt" ? ".crt" : ".key" );

                move_uploaded_file( $file_tmp_name, $upload_path . $new_file_name );

                update_option('flexify_checkout_inter_bank_' . ($type === "dropzone-crt" ? "crt" : "key") . '_file', $new_file_name);

                $response = array(
                    'status' => 'success',
                    'message' => 'Arquivo carregado com sucesso.',
                );
          
                wp_send_json( $response ); // Send JSON response
            } else {
                $response = array(
                    'status' => 'invalid_file',
                    'message' => 'Arquivo inválido. O arquivo deve ser um .crt ou .key.',
                );
          
                wp_send_json( $response );
            }
        } else {
            $response = array(
                'status' => 'error',
                'message' => 'Erro ao carregar o arquivo. O arquivo não foi enviado.',
            );
      
            wp_send_json( $response );
        }
    } else {
        $response = array(
            'status' => 'error',
            'message' => 'Erro ao carregar o arquivo. A ação não foi acionada corretamente.',
        );
  
        wp_send_json( $response );
    }
  }


  /**
   * Process for remove inter bank files
   * 
   * @since 2.3.0
   * @return void
   */
  public function inter_bank_module_actions() {
    if ( isset( $_POST['exclude_inter_bank_crt_key_files'] ) ) {
        $uploads_dir = wp_upload_dir();
        $upload_path = $uploads_dir['basedir'] . '/flexify_checkout_integrations/';
        $crt_file = get_option('flexify_checkout_inter_bank_crt_file');
        $key_file = get_option('flexify_checkout_inter_bank_key_file');

        // exclude crt file
        if ( !empty( $crt_file ) ) {
            $file_path = $upload_path . $crt_file;

            if ( file_exists( $file_path ) ) {
              wp_delete_file( $file_path );
            }
        }

        // exclude key file
        if ( !empty( $key_file ) ) {
            $file_path = $upload_path . $key_file;

            if ( file_exists( $file_path ) ) {
              wp_delete_file( $file_path );
            }
        }

        delete_option('flexify_checkout_inter_bank_crt_file');
        delete_option('flexify_checkout_inter_bank_key_file');
    }

    if ( isset( $_POST['active_inter_bank_module'] ) ) {
      if ( !function_exists( 'activate_plugin' ) ) {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
      }

      $plugin_path = 'module-inter-bank-for-flexify-checkout/module-inter-bank-for-flexify-checkout.php';
      $activate = activate_plugin( $plugin_path );

      if ( false === $activate ) {
          $error_message = get_plugin_activation_error( $plugin_path );
          echo '<div class="notice notice-error">
          <p>'. sprintf( esc_html( 'Erro ao ativar o plugin:', 'flexify-checkout-for-woocommerce' ), $error_message ) .'</p>
          </div>';
      } else {
        echo '<div class="notice notice-success">
        <p>'. esc_html( 'O módulo adicional foi ativo com sucesso!', 'flexify-checkout-for-woocommerce' ) .'</p>
        </div>';
      }
    }
  }


  /**
   * Install Inter Bank module
   * 
   * @since 2.3.0
   * @return void
   */
  public function install_inter_bank_module_callback() {
    if ( isset( $_POST['plugin_url'] ) ) {
        $plugin_slug = 'module-inter-bank-for-flexify-checkout/module-inter-bank-for-flexify-checkout.php';
        $plugin_zip = $_POST['plugin_url'];
    
    
        if ( is_plugin_installed( $plugin_slug ) ) {
          upgrade_plugin( $plugin_slug );
          $installed = true;
        } else {
          $installed = install_plugin( $plugin_zip );
        }
         
        if ( !is_wp_error( $installed ) && $installed ) {
            $activate = activate_plugin( $plugin_slug );
            $response = array(
              'status' => 'success',
              'message' => esc_html( 'Tudo certo! Módulo adicional instalado e ativo.', 'flexify-checkout-for-woocommerce' ),
            );
  
            wp_send_json( $response );
        } else {
            $response = array(
              'status' => 'error',
              'message' => esc_html( 'Não foi possível instalar o módulo adicional.', 'flexify-checkout-for-woocommerce' ),
            );
    
            wp_send_json( $response );
        }
    }
  }


  /**
   * Handle alternative activation license file .key
   * 
   * @since 3.3.0
   * @return void
   */
  public function alternative_activation_license_callback() {
    if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'alternative_activation_license' ) {
        $response = array(
          'status' => 'error',
          'message' => 'Erro ao carregar o arquivo. A ação não foi acionada corretamente.',
        );

        wp_send_json( $response );
    }

    // Verifica se o arquivo foi enviado
    if ( empty( $_FILES['file'] ) ) {
        $response = array(
          'status' => 'error',
          'message' => 'Erro ao carregar o arquivo. O arquivo não foi enviado.',
        );

        wp_send_json( $response );
    }

    $file = $_FILES['file'];

    // Verifica se é um arquivo .key
    if ( pathinfo( $file['name'], PATHINFO_EXTENSION ) !== 'key' ) {
        $response = array(
          'status' => 'invalid_file',
          'message' => 'Arquivo inválido. O arquivo deve ser um .crt ou .key.',
        );
        
        wp_send_json( $response );
    }

    // Lê o conteúdo do arquivo
    $file_content = file_get_contents( $file['tmp_name'] );

    $decrypt_keys = array(
        '49D52DA9137137C0', // original product key
        'B729F2659393EE27', // Clube M
    );

    $decrypted_data = $this->decrypt_with_multiple_keys( $file_content, $decrypt_keys );

    if ( $decrypted_data !== null ) {
        update_option( 'flexify_checkout_alternative_license_decrypted', $decrypted_data );
        
        $response = array(
          'status' => 'success',
          'message' => 'Licença enviada e decriptografada com sucesso.',
        );
    } else {
        $response = array(
          'status' => 'error',
          'message' => 'Não foi possível descriptografar o arquivo de licença.',
        );
    }

    wp_send_json( $response );

    wp_die();
  }


  /**
   * Try to decrypt with multiple keys
   * 
   * @since 3.3.0
   * @param string $encrypted_data | Encrypted data
   * @param array $possible_keys | Array list with decryp keys
   * @return mixed Decrypted string or null
   */
  public function decrypt_with_multiple_keys( $encrypted_data, $possible_keys ) {
    foreach ( $possible_keys as $key ) {
      $decrypted_data = openssl_decrypt( $encrypted_data, 'AES-256-CBC', $key, 0, substr( $key, 0, 16 ) );

      // Checks whether decryption was successful
      if ( $decrypted_data !== false ) {
        return $decrypted_data;
      }
    }
    
    return null;
  }
}

new Flexify_Checkout_Admin_Options();