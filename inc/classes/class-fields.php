<?php

namespace MeuMouse\Flexify_Checkout;

use MeuMouse\Flexify_Checkout\Init;
use MeuMouse\Flexify_Checkout\License;
use MeuMouse\Flexify_Checkout\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Class for handle checkout fields
 *
 * @since 3.9.8
 * @package MeuMouse.com
 */
class Fields {

    /**
     * Construct function
     * 
     * @since 3.9.8
     * @return void
     */
    public function __construct() {
        // Set priorities
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'custom_override_checkout_fields' ), 200 );

		// enable checkout fields manager
		if ( Init::get_setting('enable_manage_fields') === 'yes' && License::is_valid() ) {
			add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'flexify_checkout_fields_manager' ), 150 );
			add_filter( 'woocommerce_admin_billing_fields', array( __CLASS__, 'custom_admin_billing_fields' ), 10, 1 );
			add_action( 'woocommerce_customer_save_address', array( __CLASS__, 'save_custom_address_fields' ), 10, 1 );
			add_filter( 'woocommerce_billing_fields', array( __CLASS__, 'add_custom_fields_to_billing_address' ), 20, 1 );
			add_filter( 'woocommerce_customer_meta_fields', array( __CLASS__, 'custom_fields_on_user_profile' ) );
			add_filter( 'woocommerce_user_column_billing_address', array( __CLASS__, 'user_column_billing_address' ), 1, 2 );
			add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'add_custom_fields_to_shipping' ), 10, 1 );
		}

		add_filter( 'woocommerce_billing_fields', array( __CLASS__, 'custom_override_billing_field_priorities' ), 100 );
        add_filter( 'woocommerce_shipping_fields', array( __CLASS__, 'custom_override_shipping_field_priorities' ), 100 );

		// Additonal JS Patterns
		add_filter( 'woocommerce_form_field_args', array( __CLASS__, 'field_args' ), 20, 3 );

		// Remove placeholders
		add_filter( 'woocommerce_default_address_fields', array( __CLASS__, 'custom_override_default_fields' ) );

        add_filter( 'woocommerce_form_field', array( __CLASS__, 'remove_empty_placeholders_html' ), 10, 4 );
		add_filter( 'woocommerce_form_field_text', array( __CLASS__, 'modify_form_field_replace_placeholder' ) );
		add_filter( 'woocommerce_form_field_tel', array( __CLASS__, 'modify_form_field_replace_placeholder' ) );
		add_filter( 'woocommerce_form_field_email', array( __CLASS__, 'modify_form_field_replace_placeholder' ) );

        // Add inline errors
		add_filter( 'woocommerce_form_field', array( __CLASS__, 'render_inline_errors' ), 10, 5 );
    }


    /**
	 * Override checkout fields
	 *
	 * @since 1.0.0
	 * @version 3.9.7
	 * @param array $fields | Checkout fields
	 * @return array
	 */
	public static function custom_override_checkout_fields( $fields ) {
		if ( empty( $fields['shipping']['shipping_address_2']['label'] ) ) {
			$fields['shipping']['shipping_address_2']['label'] = __( 'Apartamento, suíte, unidade etc.', 'flexify-checkout-for-woocommerce' );
		}
		
		$remove_placeholder = array(
			'address_1',
			'address_2',
			'state',
			'country',
			'city',
			'postcode',
			'first_name',
			'last_name',
			'username',
			'password',
		);

		foreach ( $remove_placeholder as $field_name ) {
			if ( isset( $fields['billing'][ 'billing_' . $field_name ] ) ) {
				$fields['billing']['billing_' . $field_name]['placeholder'] = '';
			}

			if ( isset( $fields['shipping'][ 'shipping_' . $field_name ] ) ) {
				$fields['shipping']['shipping_' . $field_name]['placeholder'] = '';
			}

			if ( isset( $fields['account'][ 'account_' . $field_name ] ) ) {
				$fields['account']['account_' . $field_name]['placeholder'] = '';
			}
		}

		$fields['billing']['billing_first_name']['class'][] = 'required';
		$fields['billing']['billing_last_name']['class'][] = 'required';
		$fields['shipping']['shipping_first_name']['required'] = true;
		$fields['shipping']['shipping_last_name']['required'] = true;
		$fields['shipping']['shipping_first_name']['class'][] = 'required';
		$fields['shipping']['shipping_last_name']['class'][] = 'required';
		$fields['billing']['billing_address_2']['label_class'] = '';

		// set class for international phone number
		if ( isset( $fields['billing']['billing_phone'] ) && Init::get_setting('enable_ddi_phone_field') === 'yes' && License::is_valid() ) {
			$fields['billing']['billing_phone']['class'][] = 'flexify-intl-phone';
		}

		// check fields conditions
		if ( Init::get_setting('enable_manage_fields') !== 'yes' ) {
			if ( isset( $fields['billing']['billing_address_1'] ) ) {
				$fields['billing']['billing_address_1']['class'][] = 'row-first';
			}

			if ( isset( $fields['billing']['billing_address_2'] ) ) {
				$fields['billing']['billing_address_2']['class'][] = 'row-first';
			}

			if ( isset( $fields['billing_company'] ) ) {
			//	$fields['billing']['billing_company']['class'][] = 'validate-required required-field';
				$fields['billing']['billing_company']['required'] = false;
			}

			// Brazilian Market on WooCommerce integration
			if ( class_exists('Extra_Checkout_Fields_For_Brazil_Front_End') ) {
				$wcbcf_settings = get_option('wcbcf_settings');
				$person_type = intval( $wcbcf_settings['person_type'] );

				if ( isset( $fields['billing']['billing_number'] ) ) {
					$fields['billing']['billing_number']['class'][] = 'row-last';
				}

				if ( isset( $wcbcf_settings['neighborhood_required'] ) && '1' === $wcbcf_settings['neighborhood_required'] ) {
					if ( isset( $fields['billing']['billing_neighborhood'] ) ) {
						$fields['billing']['billing_neighborhood']['class'][] = 'required required-field';
						$fields['billing']['billing_neighborhood']['class'][] = 'row-last';
					}
				}

				if ( 0 !== $person_type ) {
					if ( 1 === $person_type || 2 === $person_type ) {
						if ( isset( $wcbcf_settings['rg'] ) ) {
							if ( isset( $fields['billing']['billing_cpf'] ) ) {
								$fields['billing']['billing_cpf']['class'][] = 'validate-required required-field';
							}

							if ( isset( $fields['billing']['billing_rg'] ) ) {
								$fields['billing']['billing_rg']['class'][] = 'validate-required required-field';
							}
						} else {
							if ( isset( $fields['billing']['billing_cpf'] ) ) {
								$fields['billing']['billing_cpf']['class'][] = 'validate-required required-field';
							}
						}
					}

					if ( 1 === $person_type || 3 === $person_type ) {
						if ( isset( $wcbcf_settings['ie'] ) ) {
							if ( isset( $fields['billing']['billing_cnpj'] ) ) {
								$fields['billing']['billing_cnpj']['class'][] = 'required required-field';
							}

							if ( isset( $fields['billing']['billing_ie'] ) ) {
								$fields['billing']['billing_ie']['class'][] = 'validate-required required-field';
							}
						} else {
							if ( isset( $fields['billing']['billing_cnpj'] ) ) {
								$fields['billing']['billing_cnpj']['class'][] = 'validate-required required-field';
							}
						}
					}
				}
			}

			if ( isset( $fields['billing']['billing_city'] ) ) {
				$fields['billing']['billing_city']['class'][] = 'row-first';
			}

			if ( isset( $fields['billing']['billing_state'] ) ) {
				$fields['billing']['billing_state']['class'][] = 'row-last';
			}
		}

		// remove shipping fields if optimize for digital products option is active
		if ( Init::get_setting('enable_optimize_for_digital_products') === 'yes' && License::is_valid() && flexify_checkout_only_virtual() ) {
			unset( $fields['order']['order_comments'] );

			// if manager fields is enabled
			if ( Init::get_setting('enable_manage_fields') === 'yes' ) {
				$get_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );

				foreach ( $get_fields as $index => $value ) {
					// prevent removing country field as it may cause address error on gateways that require this field
					if ( isset( $value['step'] ) && $value['step'] === '2' ) {
						$fields['billing'][$index]['required'] = false;
						unset( $fields['billing'][$index] );
					}
				}
			} else {
				$shipping_fields = apply_filters( 'flexify_checkout_remove_fields_for_digital_products', array(
					'billing_postcode',
					'billing_address_1',
					'billing_number',
					'billing_address_2',
					'billing_neighborhood',
					'billing_city',
					'billing_state',
				));

				foreach ( $shipping_fields as $field ) {
					$fields['billing'][$field]['required'] = false;
					unset( $fields['billing'][$field] );
				}

				$fields['billing']['billing_country']['required'] = false;
			}
		}
		
		return $fields;
	}


    /**
	 * Add new fields, reorder positions, and manage fields from WooCommerce checkout
	 * 
	 * @since 3.0.0
	 * @version 3.9.8
	 * @param array $fields | Checkout fields
	 * @return array
	 */
	public static function flexify_checkout_fields_manager( $fields ) {
		// get checkout fields from checkout controller
		$get_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );

		// iterate for each step field
		foreach ( $get_fields as $index => $value ) {
			// field custom class
			if ( isset( $value['classes'] ) ) {
				$fields['billing'][$index]['class'][] = $value['classes'];
			}

			// field input masks
			if ( ! empty( $value['input_mask'] ) ) {
				$fields['billing'][$index]['class'][] = 'has-mask';
			}

			// change array key for valid class
			$field_class = array(
				'left' => 'row-first',
				'right' => 'row-last',
				'full' => 'form-row-wide',
			);

			// field position
			if ( isset( $value['position'] ) ) {
				$fields['billing'][$index]['class'][] = $field_class[$value['position']];
			}

			// required field
			if ( isset( $value['required'] ) ) {
				$fields['billing'][$index]['required'] = $value['required'] === 'yes' ? true : false;
				$fields['billing'][$index]['class'][] = 'required';
			}

			// field custom label class
			if ( isset( $value['label_classes'] ) ) {
				$fields['billing'][$index]['label_class'] = $value['label_classes'];
			}

			// field label
			if ( isset( $value['label'] ) ) {
				$fields['billing'][$index]['label'] = $value['label'];
			}

			// add field priority
			if ( isset( $value['priority'] ) ) {
				$fields['billing'][$index]['priority'] = $value['priority'];
			}

			// adding new field
			if ( isset( $value['source'] ) && $value['source'] !== 'native' && isset( $value['type'] ) ) {
				// add new field type text
				if ( $value['type'] === 'text' ) {
					$fields['billing'][$index] = array(
						'type' => 'text',
						'label' => $value['label'],
						'class' => array( $value['classes'] ),
						'clear' => true,
						'required' => $value['required'] === 'yes' ? true : false,
						'priority' => $value['priority'],
					);

					// field position
					if ( isset( $value['position'] ) ) {
						$fields['billing'][$index]['class'][] = $field_class[$value['position']];
					}
				}

				// add new field type textarea
				if ( $value['type'] === 'textarea' ) {
					$fields['billing'][$index] = array(
						'type' => 'textarea',
						'label' => $value['label'],
						'class' => array( $value['classes'] ),
						'clear' => true,
						'required' => $value['required'] === 'yes' ? true : false,
						'priority' => $value['priority'],
					);

					// field position
					if ( isset( $value['position'] ) ) {
						$fields['billing'][$index]['class'][] = $field_class[$value['position']];
					}
				}

				// add new field type number
				if ( $value['type'] === 'number' ) {
					$fields['billing'][$index] = array(
						'type' => 'number',
						'label' => $value['label'],
						'class' => array( $value['classes'] ),
						'clear' => true,
						'required' => $value['required'] === 'yes' ? true : false,
						'priority' => $value['priority'],
					);

					// field position
					if ( isset( $value['position'] ) ) {
						$fields['billing'][$index]['class'][] = $field_class[$value['position']];
					}
				}

				// add new field type password
				if ( $value['type'] === 'password' ) {
					$fields['billing'][$index] = array(
						'type' => 'password',
						'label' => $value['label'],
						'class' => array( $value['classes'] ),
						'clear' => true,
						'required' => $value['required'] === 'yes' ? true : false,
						'priority' => $value['priority'],
					);

					// field position
					if ( isset( $value['position'] ) ) {
						$fields['billing'][$index]['class'][] = $field_class[$value['position']];
					}
				}

				// add new field type tel
				if ( $value['type'] === 'phone' ) {
					$fields['billing'][$index] = array(
						'type' => 'tel',
						'label' => $value['label'],
						'class' => array( $value['classes'] ),
						'clear' => true,
						'validate' => array('phone'),
						'required' => $value['required'] === 'yes' ? true : false,
						'priority' => $value['priority'],
					);

					// field position
					if ( isset( $value['position'] ) ) {
						$fields['billing'][$index]['class'][] = $field_class[$value['position']];
					}
				}

				// add new field type url
				if ( $value['type'] === 'url' ) {
					$fields['billing'][$index] = array(
						'type' => 'url',
						'label' => $value['label'],
						'class' => array( $value['classes'] ),
						'clear' => true,
						'required' => $value['required'] === 'yes' ? true : false,
						'priority' => $value['priority'],
					);

					// field position
					if ( isset( $value['position'] ) ) {
						$fields['billing'][$index]['class'][] = $field_class[$value['position']];
					}
				}

				// add new field type select
				if ( $value['type'] === 'select' && isset( $value['options'] ) && is_array( $value['options'] ) ) {
					$index_option = array();
					
					// get select options
					foreach ( $value['options'] as $option ) {
						if ( is_array( $option ) ) {
							$index_option[$option['value']] = $option['text'] ?? '';
						}
					}

					$fields['billing'][$index] = array(
						'type' => 'select',
						'options' => $index_option,
						'label' => $value['label'],
						'class' => array( $value['classes'] ),
						'clear' => true,
						'required' => $value['required'] === 'yes' ? true : false,
						'priority' => $value['priority'],
					);

					// field position
					if ( isset( $value['position'] ) ) {
						$fields['billing'][$index]['class'][] = $field_class[$value['position']];
					}
				}
			}

			// remove fields thats disabled
			if ( isset( $value['enabled'] ) && $value['enabled'] === 'no' ) {
				unset( $fields['billing'][$index] );
				unset( $fields['shipping'][$index] );
			}
		}

		return $fields;
	}


	/**
	 * Add custom fields to shipping different address
	 * 
	 * @since 3.9.8
	 * @param array $fields | Checkout fields
	 * @return array
	 */
	public static function add_custom_fields_to_shipping( $fields ) {
		// get checkout fields from checkout controller
		$get_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );

		// change array key for valid class
		$field_class = array(
			'left' => 'row-first',
			'right' => 'row-last',
			'full' => 'form-row-wide',
		);

		// iterate for each step field
		foreach ( $get_fields as $index => $value ) {
            // replace the billing_ prefix for shipping_ on field index
            $shipping_index = preg_replace( '/^billing_/', 'shipping_', $index );

			// adding new field
			if ( isset( $value['source'] ) && $value['source'] !== 'native' && isset( $value['type'] ) ) {
                // add shipping fields for form send to different address
                if ( isset( $value['step'] ) && $value['step'] === '2' ) {
                    $priority = isset( $value['priority'] ) ? $value['priority'] + 100 : '';

                    // add new field type text
                    if ( $value['type'] === 'text' ) {
                        $fields['shipping'][$shipping_index] = array(
                            'type' => 'text',
                            'label' => $value['label'],
                            'class' => array( $value['classes'] ),
                            'clear' => true,
                            'required' => $value['required'] === 'yes' ? true : false,
                            'priority' => $priority,
                        );

                        // field position
                        if ( isset( $value['position'] ) ) {
                            $fields['shipping'][$shipping_index]['class'][] = $field_class[$value['position']];
                        }
                    }

                    // add new field type textarea
                    if ( $value['type'] === 'textarea' ) {
                        $fields['shipping'][$shipping_index] = array(
                            'type' => 'textarea',
                            'label' => $value['label'],
                            'class' => array( $value['classes'] ),
                            'clear' => true,
                            'required' => $value['required'] === 'yes' ? true : false,
                            'priority' => $priority,
                        );

                        // field position
                        if ( isset( $value['position'] ) ) {
                            $fields['shipping'][$shipping_index]['class'][] = $field_class[$value['position']];
                        }
                    }

                    // add new field type number
                    if ( $value['type'] === 'number' ) {
                        $fields['shipping'][$shipping_index] = array(
                            'type' => 'number',
                            'label' => $value['label'],
                            'class' => array( $value['classes'] ),
                            'clear' => true,
                            'required' => $value['required'] === 'yes' ? true : false,
                            'priority' => $priority,
                        );

                        // field position
                        if ( isset( $value['position'] ) ) {
                            $fields['shipping'][$shipping_index]['class'][] = $field_class[$value['position']];
                        }
                    }

                    // add new field type password
                    if ( $value['type'] === 'password' ) {
                        $fields['shipping'][$shipping_index] = array(
                            'type' => 'password',
                            'label' => $value['label'],
                            'class' => array( $value['classes'] ),
                            'clear' => true,
                            'required' => $value['required'] === 'yes' ? true : false,
                            'priority' => $priority,
                        );

                        // field position
                        if ( isset( $value['position'] ) ) {
                            $fields['shipping'][$shipping_index]['class'][] = $field_class[$value['position']];
                        }
                    }

                    // add new field type tel
                    if ( $value['type'] === 'phone' ) {
                        $fields['shipping'][$shipping_index] = array(
                            'type' => 'tel',
                            'label' => $value['label'],
                            'class' => array( $value['classes'] ),
                            'clear' => true,
                            'validate' => array('phone'),
                            'required' => $value['required'] === 'yes' ? true : false,
                            'priority' => $priority,
                        );

                        // field position
                        if ( isset( $value['position'] ) ) {
                            $fields['shipping'][$shipping_index]['class'][] = $field_class[$value['position']];
                        }
                    }

                    // add new field type url
                    if ( $value['type'] === 'url' ) {
                        $fields['shipping'][$shipping_index] = array(
                            'type' => 'url',
                            'label' => $value['label'],
                            'class' => array( $value['classes'] ),
                            'clear' => true,
                            'required' => $value['required'] === 'yes' ? true : false,
                            'priority' => $priority,
                        );

                        // field position
                        if ( isset( $value['position'] ) ) {
                            $fields['shipping'][$shipping_index]['class'][] = $field_class[$value['position']];
                        }
                    }

                    // add new field type select
                    if ( $value['type'] === 'select' && isset( $value['options'] ) && is_array( $value['options'] ) ) {
                        $index_option = array();
                        
                        // get select options
                        foreach ( $value['options'] as $option ) {
                            if ( is_array( $option ) ) {
                                $index_option[$option['value']] = $option['text'] ?? '';
                            }
                        }

                        $fields['shipping'][$shipping_index] = array(
                            'type' => 'select',
                            'options' => $index_option,
                            'label' => $value['label'],
                            'class' => array( $value['classes'] ),
                            'clear' => true,
                            'required' => $value['required'] === 'yes' ? true : false,
                            'priority' => $priority,
                        );

                        // field position
                        if ( isset( $value['position'] ) ) {
                            $fields['shipping'][$shipping_index]['class'][] = $field_class[$value['position']];
                        }
                    }
                }
            }

            // add adjustments for shipping fields
            if ( isset( $value['step'] ) && $value['step'] === '2' ) {
                // field position
                if ( isset( $value['position'] ) ) {
                    $fields['shipping'][$shipping_index]['class'][] = $field_class[$value['position']];
                }

                // field custom class
                if ( isset( $value['classes'] ) ) {
                    $fields['shipping'][$shipping_index]['class'][] = $value['classes'];
                }

                // field input masks
                if ( ! empty( $value['input_mask'] ) ) {
                    $fields['shipping'][$shipping_index]['class'][] = 'has-mask';
                }

                // required field
                if ( isset( $value['required'] ) ) {
                    $fields['shipping'][$shipping_index]['required'] = $value['required'] === 'yes' ? true : false;
                    $fields['shipping'][$shipping_index]['class'][] = 'required';
                }

                // field custom label class
                if ( isset( $value['label_classes'] ) ) {
                    $fields['shipping'][$shipping_index]['label_class'] = $value['label_classes'];
                }

                // field label
                if ( isset( $value['label'] ) ) {
                    $fields['shipping'][$shipping_index]['label'] = $value['label'];
                }

                // add field priority
                if ( isset( $value['priority'] ) ) {
                    $fields['shipping'][$shipping_index]['priority'] = $value['priority'] + 100;
                }
            }
		}

		return $fields;
	}


	/**
	 * Add custom billing fields to the billing fields array in the admin
	 *
	 * @since 3.7.3
	 * @param array $fields | Billing fields array
	 * @return array
	 */
	public static function custom_admin_billing_fields( $fields ) {
		$new_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );

		foreach ( $new_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				$fields[$index] = array(
					'label' => isset( $value['label'] ) ? $value['label'] : '',
					'show'  => true,
					'class' => isset( $value['class'] ) ? $value['class'] : '',
				);
			}
		}

		return $fields;
	}


	/**
	 * Save custom address fields for WooCommerce customers
	 * 
	 * @since 3.7.0
	 * @param int $user_id | User ID
	 * @return void
	 */
	public static function save_custom_address_fields( $user_id ) {
		$new_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
	
		foreach ( $new_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				if ( isset( $_POST[$index] ) ) {
					update_user_meta( $user_id, $index, sanitize_text_field( $_POST[$index] ) );
				}
			}
		}
	}


	/**
	 * Show custom address fields in WooCommerce My Account addresses section
	 * 
	 * @since 3.7.0
	 * @param array $address | Address fields
	 * @param string $load_address | Address type (billing or shipping)
	 * @return array
	 */
	public static function show_custom_address_fields_in_my_account( $address, $load_address ) {
		$user_id = get_current_user_id();
		$new_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
	
		foreach ( $new_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				$address[$index] = array(
					'label' => $value['label'],
					'value' => get_user_meta( $user_id, $index, true ),
					'required' => isset( $value['required'] ) ? ( $value['required'] === 'yes' ? true : false ) : false,
					'class' => isset( $value['classes'] ) ? array( $value['classes'] ) : array(),
					'priority' => isset( $value['priority'] ) ? $value['priority'] : 100,
				);
			}
		}
	
		return $address;
	}


	/**
	 * Add custom fields to WooCommerce billing address fields
	 * 
	 * @since 3.7.0
	 * @param array $fields | Billing address fields
	 * @return array
	 */
	public static function add_custom_fields_to_billing_address( $fields ) {
		$new_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
	
		foreach ( $new_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				$fields[$index] = array(
					'label' => $value['label'],
					'type' => isset( $value['type'] ) ? $value['type'] : 'text',
					'required' => isset( $value['required'] ) ? ( $value['required'] === 'yes' ? true : false ) : false,
					'class' => isset( $value['classes'] ) ? array( $value['classes'] ) : array(),
					'priority' => isset( $value['priority'] ) ? $value['priority'] : 100,
				);
			}
		}
	
		return $fields;
	}


	/**
	 * Add new custom fields to user profile on WordPress user meta fields
	 * 
	 * @since 3.8.0
	 * @param array $fields | Current fields
	 * @return array
	 */
	public static function custom_fields_on_user_profile( $fields ) {
		$custom_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
		$new_fields['billing']['title'] = __( 'Endereço de cobrança', 'flexify-checkout-for-woocommerce' );
		
		foreach ( $custom_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' && $value['type'] !== 'select' ) {
				$new_fields['billing']['fields'][$index] = array(
					'label' => isset( $value['label'] ) ? $value['label'] : '',
					'description' => '',
					'type' => isset( $value['type'] ) ? $value['type'] : 'text',
					'required' => isset( $value['required'] ) && $value['required'] === 'yes' ? true : false,
				);
			}
		}
	
		$new_fields = apply_filters( 'flexify_customer_user_meta_fields', $new_fields );
	
		return array_merge( $fields, $new_fields );
	}


	/**
	 * Add column billing address on user meta fields
	 * 
	 * @since 3.8.0
	 * @param array $address | Address column
	 * @param int $user_id | User ID
	 * @return array
	 */
	public static function user_column_billing_address( $address, $user_id ) {
		$custom_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
	
		foreach ( $custom_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				$address[$index] = get_user_meta( $user_id, 'billing_' . $index, true );
			}
		}
	
		return $address;
	}
	

	/**
	 * Override billing field priorities.
	 *
	 * We override the priorities in `woocommerce_billing_fields` instead of
	 * `flexify_custom_override_checkout_fields` because plugins such as
	 * Checkout Field Editor for WooCommerce by ThemeHigh get the defaults
	 * from the earlier hook.
	 *
	 * @since 1.0.0
     * @version 3.9.8
	 * @param array $fields | Checkout fields
	 * @return array
	 */
	public static function custom_override_billing_field_priorities( $fields ) {
		if ( Init::get_setting('enable_manage_fields') === 'yes' && License::is_valid() ) {
			$step_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
	
			foreach ( $step_fields as $index => $value ) {
				$priority = isset( $value['priority'] ) ? $value['priority'] : '';

				self::set_field_priority( $fields, $index, $priority );
			}
		} else {
			self::set_field_priority( $fields, 'billing_email', 5 );
			self::set_field_priority( $fields, 'billing_first_name', 10 );
			self::set_field_priority( $fields, 'billing_last_name', 20 );
			self::set_field_priority( $fields, 'billing_phone', 40 );
			self::set_field_priority( $fields, 'billing_cellphone', 45 );
			self::set_field_priority( $fields, 'billing_persontype', 50 );
			self::set_field_priority( $fields, 'billing_cpf', 55 );
			self::set_field_priority( $fields, 'billing_rg', 60 );
			self::set_field_priority( $fields, 'billing_cnpj', 62 );
			self::set_field_priority( $fields, 'billing_ie', 63 );
			self::set_field_priority( $fields, 'billing_company', 63 );
			self::set_field_priority( $fields, 'billing_birthdate', 65 );
			self::set_field_priority( $fields, 'billing_sex', 66 );
			self::set_field_priority( $fields, 'billing_gender', 64 );
			self::set_field_priority( $fields, 'billing_country', 80 );
			self::set_field_priority( $fields, 'billing_postcode', 90 );
			self::set_field_priority( $fields, 'billing_address_1', 100 );
			self::set_field_priority( $fields, 'billing_number', 110 );
			self::set_field_priority( $fields, 'billing_neighborhood', 115 );
			self::set_field_priority( $fields, 'billing_address_2', 120 );
			self::set_field_priority( $fields, 'billing_city', 130 );
			self::set_field_priority( $fields, 'billing_state', 140 );
		}

		return $fields;
	}


	/**
	 * Override shipping field priorities.
	 *
	 * We override the priorities in `woocommerce_shipping_fields` instead of
	 * `flexify_custom_override_checkout_fields` because plugins such as
	 * Checkout Field Editor for WooCommerce by ThemeHigh get the defaults
	 * from the earlier hook.
	 *
	 * @since 1.0.0
	 * @version 3.9.8
	 * @param array $fields | Checkout fields
	 * @return array
	 */
	public static function custom_override_shipping_field_priorities( $fields ) {
        self::set_field_priority( $fields, 'shipping_first_name', 10 );
        self::set_field_priority( $fields, 'shipping_last_name', 20 );
        self::set_field_priority( $fields, 'shipping_company', 30 );
        self::set_field_priority( $fields, 'shipping_country', 40 );
        self::set_field_priority( $fields, 'shipping_postcode', 50 );
        self::set_field_priority( $fields, 'shipping_address_1', 60 );
        self::set_field_priority( $fields, 'shipping_number', 70 );
        self::set_field_priority( $fields, 'shipping_neighborhood', 80 );
        self::set_field_priority( $fields, 'shipping_address_2', 90 );
        self::set_field_priority( $fields, 'shipping_city', 100 );
        self::set_field_priority( $fields, 'shipping_state', 110 );

        return $fields;
	}
	

	/**
	 * Check if field exits, if it does then set the priority of given field.
	 *
	 * @param array $fields_group
	 * @param string $field_id
	 * @param int $priority
	 */
	public static function set_field_priority( &$fields_group, $field_id, $priority ) {
		if ( isset( $fields_group[$field_id] ) ) {
			$fields_group[$field_id]['priority'] = $priority;
		}
	}


	/**
	 * Override default fields
	 *
	 * @since 1.0.0
	 * @version 3.1.0
	 * @param array $fields | Checkout fields
	 * @return array
	 */
	public static function custom_override_default_fields( $fields ) {
		$fields_to_remove_placeholder = array(
			'street_number',
			'address_1',
			'address_2',
			'state',
			'country',
			'postcode',
			'first_name',
			'last_name',
		);

		$fields['address_2']['label'] = __( 'Apartamento, suíte, unidade etc.', 'flexify-checkout-for-woocommerce' );

		// Otherwise remove the placeholders.
		foreach ( $fields_to_remove_placeholder as $index ) {
			if ( isset( $fields[ $index ] ) ) {
				$fields[ $index ]['placeholder'] = '';
			}
		}

		return $fields;
	}


	/**
	 * Field args
	 *
	 * @since 1.0.0
	 * @param array $data
	 * @param string $key
	 * @param string $value
	 * @return array
	 */
	public static function field_args( $data, $key, $value ) {
		if ( 'billing_phone' === $key ) {
			$data['custom_attributes']['pattern'] = '^(\(?\+?[0-9]*\)?)?[0-9_\- \(\)]*$';
		}

		return $data;
	}


    /**
	 * Remove empty placeholders from the HTML
	 *
	 * @since 1.0.0
	 * @version 3.9.8
	 * @param string $field Field.
	 * @param string $key Key.
	 * @param array  $args Args.
	 * @param string $value Value.
	 *
	 * @return string
	 */
	public static function remove_empty_placeholders_html( $field, $key, $args, $value ) {
		if ( strpos( $field, 'placeholder=""' ) === false ) {
			return $field;
		}

		return str_replace( 'placeholder=""', '', $field );
	}


	/**
	 * Modify form field HTML.
	 *
	 * @param string $field Field.
	 * @param string $key Key.
	 * @param array  $args Args.
	 * @param string $value Value.
	 *
	 * @return string
	 */
	public static function modify_form_field_html( $field, $key, $args, $value ) {
		$field_required = __( 'Este campo é obrigatório', 'flexify-checkout-for-woocommerce' );
		$valid_number = __( 'Por favor insira um número de telefone válido', 'flexify-checkout-for-woocommerce' );

		if ( 'billing_phone' === $key || 'shipping_phone' === $key ) {
			return str_replace( '</p>', "<span class=\".error\">$valid_number</span></p>", $field );
		}

		if ( 'billing_first_name' === $key || 'billing_last_name' === $key || 'billing_email' === $key ) {
			return str_replace( '</p>', "<span class=\".error\">$field_required</span></p>", $field );
		}

		return $field;
	}


	/**
	 * Modify form field replace placeholder
	 *
	 * @since 1.0.0
     * @version 3.9.8
	 * @param string $field
	 * @return string
	 */
	public static function modify_form_field_replace_placeholder( $field ) {
		$field = str_replace( 'placeholder=""', '', $field );

		return str_replace( 'placeholder ', '', $field );
	}


    /**
	 * Render inline errors for validate fields
	 *
	 * @since 1.0.0
	 * @version 3.9.8
	 * @param string $field | Checkout field
	 * @param string $key | Field name and ID
	 * @param array $args | Array of field parameters (type, country, label, description, placeholder, maxlenght, required, autocomplete, id, class, label_class, input_class, return, options, custom_attributes, validate, default, autofocus)
	 * @param string $value | Field value by default
	 * @param string $country Country
	 * @return string
	 */
	public static function render_inline_errors( $field = '', $key = '', $args = array(), $value = '', $country = '' ) {
		$called_inline = false;

		if ( defined('DOING_AJAX') && DOING_AJAX && ! empty( $key ) ) {
			$called_inline = true;
		}

		// If we are doing AJAX, get the parameters from POST request.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ! $called_inline ) {
			$key = filter_input( INPUT_POST, 'key', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$args = filter_input( INPUT_POST, 'args', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY );
			$value = filter_input( INPUT_POST, 'value', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$country = filter_input( INPUT_POST, 'country', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}

		$message = '';
		$message_type = 'error';
		$global_message = false;
		$custom = false;

		if ( (bool) $args['required'] || $args['class'] === 'required-field' ) {
			$message = sprintf( __( '%s é um campo obrigatório.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );

			/**
			 * Filters the required field error message.
			 *
			 * @since 1.0.0
			 * @param string $message Message.
			 * @param string $key Key.
			 * @param array $args Arguments.
			 * @return string
			 */
			$message = apply_filters( 'flexify_required_field_error_msg', $message, $key, $args );
		}

		// is required field
		if ( (bool) $args['required'] && $value ) {
			if ( 'country' === $args['type'] && property_exists( WC()->countries, 'country_exists' ) && WC()->countries && ! WC()->countries->country_exists( $value ) ) {
				/* translators: ISO 3166-1 alpha-2 country code */
				$message = sprintf( __( "'%s' não é um código de país válido.", 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
				$custom  = true;
			}

			if ( 'billing_postcode' === $key && ! \WC_Validation::is_postcode( $value, $country ) ) {
				switch ( $country ) {
					case 'IE':
						/* translators: %1$s: field name, %2$s finder.eircode.ie URL */
						$message = sprintf( __( '%1$s não é válido. Você pode procurar o Eircode correto <a target="_blank" href="%2$s">aqui</a>.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ), 'https://finder.eircode.ie' );
						$custom  = true;
						break;
					default:
						/* translators: %s: field name */
						$message = sprintf( __( '%s não é um código postal válido.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
						$custom  = true;
						break;
				}
			}

			if ( 'phone' === $args['type'] && ! \WC_Validation::is_phone( $value ) || strpos( $key, 'billing_phone') !== false && ! \WC_Validation::is_phone( $value ) ) {
				$message = sprintf( __( '%s não é um número de telefone válido.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
				$custom  = true;
			}

			// add compatibility with multiple cpf fields
			if ( strpos( $key, 'billing_cpf' ) !== false && ! Helpers::validate_cpf( $value ) || ( isset( $args['class'] ) && 'validate-cpf-field' === $args['class'] && ! Helpers::validate_cpf( $value ) ) ) {
				$message = sprintf( __('O %s informado não é válido.', 'flexify-checkout-for-woocommerce'), esc_html( $args['label'] ) );
				$custom  = true;
			}

			// add compatibility with multiple cnpj fields
			if ( strpos( $key, 'billing_cnpj' ) !== false && ! Helpers::validate_cnpj( $value ) || ( isset( $args['class'] ) && 'validate-cnpj-field' === $args['class'] && ! Helpers::validate_cnpj( $value ) ) ) {
				$message = sprintf(__('O %s informado não é válido.', 'flexify-checkout-for-woocommerce'), esc_html( $args['label'] ) );
				$custom  = true;
			}

			if ( 'email' === $args['type'] && ! is_email( $value ) || ( isset( $args['class'] ) && 'validate-email-field' === $args['class'] && ! is_email( $value ) ) ) {
				$message = sprintf( __('%s não é um endereço de e-mail válido.', 'flexify-checkout-for-woocommerce'), esc_html( $args['label'] ) );
				$custom  = true;
			}

			if ( 'email' === $args['type'] && ! is_user_logged_in() && email_exists( $value ) ) {
				/**
				 * Filter text displayed during registration when an email already exists.
				 *
				 * @since 1.0.0
				 * @param string $email | Email address.
				 * @return string
				 */
				$message = apply_filters( 'flexify_woocommerce_registration_error_email_exists', sprintf( __( 'Uma conta já está registrada com este endereço de e-mail. <a href="#" data-login>Deseja entrar na sua conta?</a>', 'flexify-checkout-for-woocommerce' ), '' ) );
				$message_type = 'info';
			}
		}


		/**
		 * Filters the Inline Error Message.
		 *
		 * @since 1.0.0
		 * @param string $message Message.
		 * @param string $field Field.
		 * @param string $key Key.
		 * @param array $args Arguments.
		 * @param string $value Value.
		 * @param string $country Country.
		 * @return string
		 */
		$message = apply_filters( 'flexify_custom_inline_message', $message, $field, $key, $args, $value, $country );

		/**
		 * Filters the Global Error Message.
		 *
		 * @since 1.0.0
		 * @param string $message Message.
		 * @param string $field Field.
		 * @param string $key Key.
		 * @param array  $args Arguments.
		 * @param string $value Value.
		 * @param string $country Country.
		 * @return string
		 */
		$global_message = apply_filters( 'flexify_custom_global_message', $global_message, $field, $key, $args, $value, $country );

		// If we are doing AJAX, just return the message.
		$action = filter_input( INPUT_POST, 'action' );

		if ( defined('DOING_AJAX') && DOING_AJAX && in_array( $action, array( 'flexify_check_for_inline_error', 'flexify_check_for_inline_errors' ), true ) ) {
			$response = array(
				'message' => $message,
				'isCustom' => $custom,
				'globalMessage' => $global_message,
				'globalData' => array( 'data-flexify-error' => 1 ),
				'messageType' => $message_type,
				'input_value' => $value,
				'input_id' => $key,
			);

			if ( $called_inline ) {
				return $response;
			}

			wp_send_json_success( $response );

			exit;
		}

		// get step fields
		$fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
		$target_fields = array();

		foreach ( $fields as $index => $value ) {
			$target_fields[] = $index;
		}

		// filter for add check errors on custom conditions
		$target_fields = apply_filters( 'flexify_checkout_target_fields_for_check_errors', $target_fields );

		$data_attributes = '<p ';
		$data_attributes .= sprintf( 'data-type="%s"', esc_attr( $args['type'] ) ) . ' ';
		$data_attributes .= sprintf( 'data-label="%s"', esc_attr( $args['label'] ) ) . ' ';

		// check if field is allowed from array $target_fields list
		if ( in_array( $args['id'], $target_fields ) ) {
			if ( strpos( $field, '</p>' ) !== false ) {
				$error = '<span class="error">';
					$error .= $message;
				$error .= '</span>';

				$field = substr_replace( $field, $error, strpos( $field, '</p>' ), 0 ); // Add before closing paragraph tag.
				$field = substr_replace( $field, $data_attributes, strpos( $field, '<p>' ), 2 ); // Add to opening paragraph tag.
			}
		}

		return $field;
	}
}

if ( Init::get_setting('enable_flexify_checkout') === 'yes' ) {
    new Fields();
}