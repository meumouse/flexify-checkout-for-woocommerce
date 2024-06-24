<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

class Flexify_Checkout_Admin_Options extends Flexify_Checkout_Init {

  /**
   * Flexify_Checkout_Admin constructor.
   *
   * @since 1.0.0
   * @version 3.5.0
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

    // install Inter bank module in AJAX
    add_action( 'wp_ajax_install_inter_bank_module', array( $this, 'install_inter_bank_module_callback' ) );

    // remove checkout fields on click delete button
    add_action( 'wp_ajax_remove_checkout_fields', array( $this, 'remove_checkout_fields_callback' ) );

    // processing new field
    add_action( 'wp_ajax_add_new_field_to_checkout', array( $this, 'add_new_field_to_checkout_callback' ) );

    // get AJAX call from upload files from alternative activation license
    add_action( 'wp_ajax_alternative_activation_license', array( $this, 'alternative_activation_license_callback' ) );

    // get AJAX call from add new font
    add_action( 'wp_ajax_add_new_font_action', array( $this, 'add_new_font_action_callback' ) );

    // get AJAX call for query products search
    add_action( 'wp_ajax_get_woo_products_ajax', array( $this, 'get_woo_products_callback' ) );

    // get AJAX call for query products categories
    add_action( 'wp_ajax_get_woo_categories_ajax', array( $this, 'get_woo_categories_callback' ) );
    
    // get AJAX call for query products categories
    add_action( 'wp_ajax_get_woo_attributes_ajax', array( $this, 'get_woo_attributes_callback' ) );

    // get AJAX call for query WP users
    add_action( 'wp_ajax_search_users_ajax', array( $this, 'search_users_ajax_callback' ) );

    // get AJAX call from add new condition
    add_action( 'wp_ajax_add_new_checkout_condition', array( $this, 'add_new_checkout_condition_callback' ) );

    // get AJAX call from exclude condition item
    add_action( 'wp_ajax_exclude_condition_item', array( $this, 'exclude_condition_item_callback' ) );

    // get AJAX call from add new email provider
    add_action( 'wp_ajax_add_new_email_provider', array( $this, 'add_new_email_provider_callback' ) );

    // get AJAX call from remove email provider item
    add_action( 'wp_ajax_remove_email_provider', array( $this, 'remove_email_provider_callback' ) );
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
   * @version 3.5.0
   * @return void
   */
  public function flexify_checkout_ajax_save_options_callback() {
    if ( isset( $_POST['form_data'] ) ) {
        // Convert serialized data into an array
        parse_str( $_POST['form_data'], $form_data );

        $options = get_option('flexify_checkout_settings');
        $options['enable_flexify_checkout'] = isset( $form_data['enable_flexify_checkout'] ) ? 'yes' : 'no';
        $options['enable_autofill_company_info'] = isset( $form_data['enable_autofill_company_info'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_back_to_shop_button'] = isset( $form_data['enable_back_to_shop_button'] ) ? 'yes' : 'no';
        $options['enable_skip_cart_page'] = isset( $form_data['enable_skip_cart_page'] ) ? 'yes' : 'no';
        $options['enable_terms_is_checked_default'] = isset( $form_data['enable_terms_is_checked_default'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_aditional_notes'] = isset( $form_data['enable_aditional_notes'] ) ? 'yes' : 'no';
        $options['enable_optimize_for_digital_products'] = isset( $form_data['enable_optimize_for_digital_products'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_link_image_products'] = isset( $form_data['enable_link_image_products'] ) ? 'yes' : 'no';
        $options['enable_fill_address'] = isset( $form_data['enable_fill_address'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_change_product_quantity'] = isset( $form_data['enable_change_product_quantity'] ) && self::license_valid() ? 'yes' : 'no';
        $options['enable_remove_product_cart'] = isset( $form_data['enable_remove_product_cart'] ) && self::license_valid() ? 'yes' : 'no';
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
        $options['enable_manage_fields'] = isset( $form_data['enable_manage_fields'] ) ? 'yes' : 'no';
        $options['enable_display_local_pickup_kangu'] = isset( $form_data['enable_display_local_pickup_kangu'] ) ? 'yes' : 'no';
        $options['enable_field_masks'] = isset( $form_data['enable_field_masks'] ) ? 'yes' : 'no';
        $options['check_password_strenght'] = isset( $form_data['check_password_strenght'] ) ? 'yes' : 'no';
        $options['email_providers_suggestion'] = isset( $form_data['email_providers_suggestion'] ) ? 'yes' : 'no';
        $options['display_opened_order_review_mobile'] = isset( $form_data['display_opened_order_review_mobile'] ) ? 'yes' : 'no';

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

              // update field mask on change
              if ( isset( $value['input_mask'] ) ) {
                $fields[$index]['input_mask'] = $value['input_mask'];
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

        wp_send_json( $response ); // Send JSON response
    }
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

      wp_send_json( $response ); // send response
    }
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
            'type' => isset( $_POST['get_field_type'] ) ? sanitize_text_field( $_POST['get_field_type'] ) : '',
            'label' => isset( $_POST['get_field_label'] ) ? sanitize_text_field( $_POST['get_field_label'] ) : '',
            'position' => isset( $_POST['get_field_position'] ) ? sanitize_text_field( $_POST['get_field_position'] ) : '',
            'classes' => isset( $_POST['get_field_classes'] ) ? sanitize_text_field( $_POST['get_field_classes'] ) : '',
            'label_classes' => isset( $_POST['get_field_label_classes'] ) ? sanitize_text_field( $_POST['get_field_label_classes'] ) : '',
            'required' => isset( $_POST['get_field_required'] ) ? sanitize_text_field( $_POST['get_field_required'] ) : '',
            'priority' => isset( $_POST['get_field_priority'] ) ? sanitize_text_field( $_POST['get_field_priority'] ) : '',
            'source' => isset( $_POST['get_field_source'] ) ? sanitize_text_field( $_POST['get_field_source'] ) : '',
            'enabled' => 'yes',
            'step' => isset( $_POST['get_field_step'] ) ? sanitize_text_field( $_POST['get_field_step'] ) : '',
            'options' => isset( $_POST['get_field_options_for_select'] ) ? $_POST['get_field_options_for_select'] : null,
            'input_mask' => isset( $_POST['input_mask'] ) ? sanitize_text_field( $_POST['input_mask'] ) : '',
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

      wp_send_json( $response ); // send response
    }
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
          
                wp_send_json( $response ); // send response
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
         
        if ( ! is_wp_error( $installed ) && $installed ) {
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

    $decrypted_data = decrypt_license_file( $file_content, $decrypt_keys );

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
  }


  /**
   * Add new font to library on AJAX callback
   * 
   * @since 3.5.0
   * @return void
   */
  public function add_new_font_action_callback() {
    if ( isset( $_POST['new_font_id'] ) ) {
      $font_id = strtolower( $_POST['new_font_id'] );

      $new_font = array(
        $font_id => array(
          'font_name' => $_POST['new_font_name'],
          'font_url' => $_POST['new_font_url'],
        ),
      );

      // get settings array
      $options = get_option('flexify_checkout_settings', array());

      // add new theme to array settings
      if ( ! isset( $options['font_family'][$font_id] ) ) {
        $options['font_family'][$font_id] = $new_font[$font_id];
      }

      // save the updated options
      $new_font_added = update_option( 'flexify_checkout_settings', $options );

      // check if new theme added with successful
      if ( $new_font_added ) {
        $response = array(
          'status' => 'success',
          'reload' => true,
        );
      } else {
        $response = array(
          'status' => 'error',
          'font_exists' => true,
          'reload' => false,
        );
      }

      // send response to frontend
      wp_send_json( $response );
    }
  }


  /**
   * Get WooCommerce products in AJAX
   * 
   * @since 3.5.0
   * @return void
   */
  public function get_woo_products_callback() {
    if ( isset( $_POST['search_query'] ) ) {
      $search_query = sanitize_text_field( $_POST['search_query'] );
    
      $args = array(
          'post_type' => 'product',
          'status' => 'publish',
          'posts_per_page' => -1, // Return all results
          's' => $search_query,
      );
      
      $products = new WP_Query( $args );
      
      if ( $products->have_posts() ) {
          while ( $products->have_posts() ) {
            $products->the_post();

            echo '<li class="list-group-item" data-product-id="'. get_the_ID() .'">' . get_the_title() . '</li>';
          }
      } else {
          echo esc_html__( 'Nenhuma produto encontrado.', 'flexify-checkout-for-woocommerce' );
      }
      
      wp_die(); // end ajax call
    }
  }


  /**
   * Get WooCommerce product categories in AJAX
   * 
   * @since 3.5.0
   * @return void
   */
  public function get_woo_categories_callback() {
    if ( isset( $_POST['search_query'] ) ) {
      $search_query = sanitize_text_field( $_POST['search_query'] );
    
      $args = array(
          'taxonomy' => 'product_cat',
          'hide_empty' => false,
          'name__like' => $search_query,
      );
      
      $categories = get_terms( $args );
      
      if ( ! empty( $categories ) ) {
          foreach ( $categories as $category ) {
              echo '<li class="list-group-item" data-category-id="'. $category->term_id .'">'. $category->name .'</li>';
          }
      } else {
          echo esc_html__( 'Nenhuma categoria encontrada.', 'flexify-checkout-for-woocommerce' );
      }
      
      wp_die(); // end ajax call
    }
  }

  
  /**
   * Get product attributes in AJAX
   * 
   * @since 3.5.0
   * @return void
   */
  public function get_woo_attributes_callback() {
    if ( isset( $_POST['search_query'] ) ) {
      $search_query = sanitize_text_field( $_POST['search_query'] );

      // get all registered attribute taxonomies
      $attribute_taxonomies = wc_get_attribute_taxonomies();

      if ( ! empty( $attribute_taxonomies ) ) {
          foreach ( $attribute_taxonomies as $attribute_taxonomy ) {
              // Use the taxonomy name instead of the 'attribute_name'
              $taxonomy_name = 'pa_' . $attribute_taxonomy->attribute_name;

              // Verify that the taxonomy name contains the search term
              if ( strpos( $taxonomy_name, $search_query ) !== false ) {
                  $args = array(
                      'taxonomy' => $taxonomy_name,
                      'hide_empty' => false,
                  );

                  $attributes = get_terms( $args );

                  if ( ! empty( $attributes ) ) {
                      foreach ( $attributes as $attribute ) {
                          echo '<li class="list-group-item" data-attribute-id="' . $attribute->term_id . '">' . $attribute->name . '</li>';
                      }
                  } else {
                    echo esc_html__( 'Nenhum atributo encontrado.', 'flexify-checkout-for-woocommerce' );
                  }
              }
          }
      }

      wp_die(); // end ajax call
    }
  }


  /**
   * Search WP users in AJAX
   * 
   * @since 3.5.0
   * @return void
   */
  public function search_users_ajax_callback() {
    if ( isset( $_POST['search_query'] ) ) {
      $search_query = sanitize_text_field( $_POST['search_query'] );

      // Run a query to search for users based on the search term
      $args = array(
          'search' => '*' . $search_query . '*',
          'search_columns' => array(
            'user_login',
            'user_email',
            'user_nicename',
            'display_name',
          ),
          'number' => -1, // Return all results
      );

      $users = get_users( $args );

      if ( ! empty( $users ) ) {
          foreach ( $users as $user ) {
            echo '<li class="list-group-item" data-user-id="' . $user->ID . '">' . $user->display_name . '</li>';
          }
      } else {
          echo esc_html__( 'Nenhum usuário encontrado.', 'flexify-checkout-for-woocommerce' );
      }

      wp_die(); // end ajax call
    }
  }


  /**
   * Add new condition AJAX callback
   * 
   * @since 3.5.0
   * @return void
   */
  public function add_new_checkout_condition_callback() {
    if ( isset( $_POST['type_rule'] ) && $_POST['type_rule'] !== 'none' ) {
      $form_condition = array(
        'type_rule' => isset( $_POST['type_rule'] ) ? sanitize_text_field( $_POST['type_rule'] ) : null,
        'component' => isset( $_POST['component'] ) ? sanitize_text_field( $_POST['component'] ) : null,
        'component_field' => isset( $_POST['component_field'] ) ? sanitize_text_field( $_POST['component_field'] ) : null,
        'verification_condition' => isset( $_POST['verification_condition'] ) ? sanitize_text_field( $_POST['verification_condition'] ) : null,
        'verification_condition_field' => isset( $_POST['verification_condition_field'] ) ? sanitize_text_field( $_POST['verification_condition_field'] ) : null,
        'condition' => isset( $_POST['condition'] ) ? sanitize_text_field( $_POST['condition'] ) : null,
        'condition_value' => isset( $_POST['condition_value'] ) ? sanitize_text_field( $_POST['condition_value'] ) : null,
        'payment_method' => isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : null,
        'shipping_method' => isset( $_POST['shipping_method'] ) ? sanitize_text_field( $_POST['shipping_method'] ) : null,
        'filter_user' => isset( $_POST['filter_user'] ) ? sanitize_text_field( $_POST['filter_user'] ) : null,
        'specific_user' => isset( $_POST['filter_user'] ) ? $_POST['filter_user'] : null,
        'specific_role' => isset( $_POST['specific_role'] ) ? sanitize_text_field( $_POST['specific_role'] ) : null,
        'specific_products' => isset( $_POST['specific_products'] ) ? $_POST['specific_products'] : null,
        'specific_categories' => isset( $_POST['specific_categories'] ) ? $_POST['specific_categories'] : null,
        'specific_attributes' => isset( $_POST['specific_attributes'] ) ? $_POST['specific_attributes'] : null,
        'product_filter' => isset( $_POST['product_filter'] ) ? sanitize_text_field( $_POST['product_filter'] ) : null,
      );

      // remove null values
      $form_condition = array_filter( $form_condition, function( $value ) {
        return ! is_null( $value );
      });

      // get current conditions
      $current_conditions = get_option('flexify_checkout_conditions', array());

      $empty_conditions = false;

      // check if conditions is empty
      if ( empty( $current_conditions ) ) {
        $empty_conditions = true;
      }

      // merge new condition with existing
      $current_conditions[] = $form_condition;

      // Update conditions
      $update_conditions = update_option( 'flexify_checkout_conditions', $current_conditions );

      // check if successfully updated
      if ( $update_conditions ) {
        $get_fields = Flexify_Checkout_Helpers::get_checkout_fields_on_admin();
        $condition_type = array(
          'show' => esc_html__( 'Mostrar', 'flexify-checkout-for-woocommerce' ),
          'hide' => esc_html__( 'Ocultar', 'flexify-checkout-for-woocommerce' ),
        );

        $component_type_label = '';

        if ( $form_condition['component'] === 'field' ) {
            $field_id = $form_condition['component_field'];
            $component_type_label = sprintf( esc_html__( 'Campo %s', 'flexify-checkout-for-woocommerce' ), $get_fields['billing'][$field_id]['label'] );
        } elseif ( $form_condition['component'] === 'shipping' ) {
            $shipping_id = $form_condition['shipping_method'];
            $component_type_label = sprintf( esc_html__( 'Forma de entrega %s', 'flexify-checkout-for-woocommerce' ), WC()->shipping->get_shipping_methods()[$shipping_id]->method_title );
        } elseif ( $form_condition['component'] === 'payment' ) {
            $payment_id = $form_condition['payment_method'];
            $component_type_label = sprintf( esc_html__( 'Forma de pagamento %s', 'flexify-checkout-for-woocommerce' ), WC()->payment_gateways->payment_gateways()[$payment_id]->method_title );
        }

        $component_verification_label = '';

        if ( $form_condition['verification_condition'] === 'field' ) {
            $field_id = $form_condition['verification_condition_field'];
            $component_verification_label = sprintf( esc_html__( 'Campo %s', 'flexify-checkout-for-woocommerce' ), $get_fields['billing'][$field_id]['label'] );
        } elseif ( $form_condition['verification_condition'] === 'qtd_cart_total' ) {
            $component_verification_label = esc_html__( 'Quantidade total do carrinho', 'flexify-checkout-for-woocommerce' );
        } elseif ( $form_condition['verification_condition'] === 'cart_total_value' ) {
            $component_verification_label = esc_html__( 'Valor total do carrinho', 'flexify-checkout-for-woocommerce' );
        }

        $condition = array(
          'is' => esc_html__( 'É', 'flexify-checkout-for-woocommerce' ),
          'is_not' => esc_html__( 'Não é', 'flexify-checkout-for-woocommerce' ),
          'empty' => esc_html__( 'Vazio', 'flexify-checkout-for-woocommerce' ),
          'not_empty' => esc_html__( 'Não está vazio', 'flexify-checkout-for-woocommerce' ),
          'contains' => esc_html__( 'Contém', 'flexify-checkout-for-woocommerce' ),
          'not_contain' => esc_html__( 'Não contém', 'flexify-checkout-for-woocommerce' ),
          'start_with' => esc_html__( 'Começa com', 'flexify-checkout-for-woocommerce' ),
          'finish_with' => esc_html__( 'Termina com', 'flexify-checkout-for-woocommerce' ),
          'bigger_then' => esc_html__( 'Maior que', 'flexify-checkout-for-woocommerce' ),
          'less_than' => esc_html__( 'Menor que', 'flexify-checkout-for-woocommerce' ),
        );
        
        $condition_value = isset( $form_condition['condition_value'] ) ? $form_condition['condition_value'] : '';

        $response = array(
          'status' => 'success',
          'toast_header_success' => esc_html( 'Nova condição adicionada', 'flexify-checkout-for-woocommerce' ),
          'toast_body_success' => esc_html( 'Condição criada com sucesso!', 'flexify-checkout-for-woocommerce' ),
          'condition_line_1' => sprintf( esc_html__( 'Condição: %s %s', 'flexify-checkout-for-woocommerce' ), $condition_type[$form_condition['type_rule']], $component_type_label ),
          'condition_line_2' => sprintf( esc_html__( 'Se: %s %s %s', 'flexify-checkout-for-woocommerce' ), $component_verification_label, mb_strtolower( $condition[$form_condition['condition']] ), $condition_value ),
        );

        if ( $empty_conditions ) {
          $response[] = array(
            'empty_conditions' => 'yes',
          );
        }
      } else {
        $response = array(
          'status' => 'error',
          'error_message' => esc_html__( 'Ops! Não foi possível criar uma nova condição.', 'flexify-checkout-for-woocommerce' ),
        );
      }

      // send response
      wp_send_json( $response );
    }
  }


  /**
   * Exclude condition item AJAX callback
   * 
   * @since 3.5.0
   * @return void
   */
  public function exclude_condition_item_callback() {
    if ( isset( $_POST['condition_index'] ) ) {
      $exclude_item = sanitize_text_field( $_POST['condition_index'] );
      $get_conditions = get_option('flexify_checkout_conditions', array());

      if ( isset( $get_conditions[$exclude_item] ) ) {
        unset( $get_conditions[$exclude_item] );

        $update_conditions = update_option('flexify_checkout_conditions', $get_conditions);

        if ( $update_conditions ) {
          $response = array(
            'status' => 'success',
            'toast_header_success' => esc_html( 'Excluído com sucesso', 'flexify-checkout-for-woocommerce' ),
            'toast_body_success' => esc_html( 'Condição excluída com sucesso!', 'flexify-checkout-for-woocommerce' ),
          );
  
          if ( empty( $get_conditions ) ) {
            $response[] = array(
              'empty_conditions' => 'yes',
              'empty_conditions_message' => esc_html( 'Ainda não existem condições.', 'flexify-checkout-for-woocommerce' ),
            );
          }
        } else {
          $response = array(
            'status' => 'error',
            'toast_header_error' => esc_html( 'Erro ao excluir', 'flexify-checkout-for-woocommerce' ),
            'toast_body_error' => esc_html( 'Ops! Não foi possível excluir a condição.', 'flexify-checkout-for-woocommerce' ),
          );
        }
  
        // send response
        wp_send_json( $response );
      }
    }
  }


  /**
   * Add new email provider for suggestion on checkout
   * 
   * @since 3.5.0
   * @return void
   */
  public function add_new_email_provider_callback() {
    if ( isset( $_POST['new_provider'] ) ) {
      $new_provider = sanitize_text_field( $_POST['new_provider'] );
      $get_options = get_option('flexify_checkout_settings', array());
      $providers = $get_options['set_email_providers'];
      $providers[] = $new_provider;
      $get_options['set_email_providers'] = $providers;
      $update_providers = update_option( 'flexify_checkout_settings', $get_options );

      if ( $update_providers ) {
        $response = array(
          'status' => 'success',
          'new_provider' => $new_provider,
          'toast_header_success' => esc_html( 'Provedor de e-mail adicionado', 'flexify-checkout-for-woocommerce' ),
          'toast_body_success' => esc_html( 'Novo provedor de e-mail adicionado com sucesso!', 'flexify-checkout-for-woocommerce' ),
        );
      } else {
        $response = array(
          'status' => 'error',
          'toast_header_error' => esc_html( 'Erro ao adicionar', 'flexify-checkout-for-woocommerce' ),
          'toast_body_error' => esc_html( 'Ops! Não foi possível adicionar o novo provedor.', 'flexify-checkout-for-woocommerce' ),
        );
      }

      wp_send_json( $response );
    }
  }


  /**
   * Exclude email provider item
   * 
   * @since 3.5.0
   * @return void
   */
  public function remove_email_provider_callback() {
    if ( isset( $_POST['exclude_provider'] ) ) {
      $exclude_provider = sanitize_text_field( $_POST['exclude_provider'] );
      $get_options = get_option('flexify_checkout_settings', array());
      $providers = $get_options['set_email_providers'];
      $search_provider = array_search( $exclude_provider, $providers );

      if ( $search_provider !== false ) {
        unset( $providers[$search_provider] );

        $get_options['set_email_providers'] = $providers;
        $update_providers = update_option( 'flexify_checkout_settings', $providers );

        if ( $update_providers ) {
          $response = array(
            'status' => 'success',
            'toast_header_success' => esc_html( 'Provedor de e-mail removido', 'flexify-checkout-for-woocommerce' ),
            'toast_body_success' => esc_html( 'Provedor de e-mail removido com sucesso!', 'flexify-checkout-for-woocommerce' ),
          );
        } else {
          $response = array(
            'status' => 'error',
            'toast_header_error' => esc_html( 'Erro ao remover', 'flexify-checkout-for-woocommerce' ),
            'toast_body_error' => esc_html( 'Ops! Não foi possível remover o provedor de e-mail.', 'flexify-checkout-for-woocommerce' ),
          );
        }

        wp_send_json( $response );
      }
    }
  }
}

new Flexify_Checkout_Admin_Options();