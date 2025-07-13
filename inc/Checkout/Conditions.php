<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Create conditions for checkout components
 *
 * @since 3.5.0
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Conditions {

    /**
     * Construct function
     * 
     * @since 3.5.0
     * @return void
     */
    public function __construct() {
        // Conditions for checkout fields
        add_filter( 'woocommerce_checkout_fields', array( $this, 'checkout_fields_conditions' ), 150 );

        // Conditions for payment gateways
        add_filter( 'woocommerce_available_payment_gateways', array( $this, 'payment_gateways_conditions' ), 10, 1 );

        // Conditions for shipping methods
        add_filter( 'woocommerce_package_rates', array( $this, 'shipping_methods_conditions' ), 10, 2 );
    }


    /**
     * Set custom conditions for checkout fields
     * 
     * @since 3.5.0
     * @param array $fields | Checkout fields
     * @return array $fields
     */
    public function checkout_fields_conditions( $fields ) {
        $field_conditions = self::filter_component_type('field');

        if ( ! is_flexify_checkout() || empty( $field_conditions ) ) {
            return $fields;
        }

        $cart = WC()->cart;
        list( $cart_product_ids, $cart_product_categories, $cart_product_attributes ) = self::get_cart_product_details( $cart );

        // iterate for each condition
        foreach ( $field_conditions as $condition_value ) {
            // iterate for each checkout field
            foreach ( $fields as $fieldset_key => $fieldset ) {
                // iterate for each checkout field id and values
                foreach ( $fieldset as $field_key => $field ) {
                    if ( $condition_value['component_field'] === $field_key ) {
                        if ( $condition_value['type_rule'] === 'show' ) {
                            $fields[$fieldset_key][$field_key]['required'] = false;
                            $fields[$fieldset_key][$field_key]['class'][] = 'has-condition';
                        
                            // add condition class on label for remove "optional" info
                            if ( isset( $field['required'] ) && $field['required'] ) {
                                $fields[$fieldset_key][$field_key]['class'][] = 'required-field';
                                $fields[$fieldset_key][$field_key]['label_class'] = 'has-condition required-field';
                            } else {
                                $fields[$fieldset_key][$field_key]['label_class'] = 'has-condition';
                            }
                        } elseif ( $condition_value['type_rule'] === 'hide' ) {
                            $fields[$fieldset_key][$field_key]['required'] = false;
                            $fields[$fieldset_key][$field_key]['class'][] = 'has-condition';

                            // add condition class on label for remove "optional" info
                            if ( isset( $field['required'] ) && $field['required'] ) {
                                $fields[$fieldset_key][$field_key]['class'][] = 'required-field';
                                $fields[$fieldset_key][$field_key]['label_class'] = 'has-condition required-field';
                            } else {
                                $fields[$fieldset_key][$field_key]['label_class'] = 'has-condition';
                            }
                        }
                    }
                }
            }
        }

        return $fields;
    }

    
    /**
     * Set custom conditions for payment gateways
     * 
     * @since 3.5.0
     * @version 5.0.0
     * @param array $available_gateways | Available payment gateways
     * @return array
     */
    public function payment_gateways_conditions( $available_gateways ) {
        $payment_conditions = self::filter_component_type('payment');

        if ( ! is_flexify_checkout() || empty( $payment_conditions ) ) {
            return $available_gateways;
        }

        $cart = WC()->cart;
        list( $cart_product_ids, $cart_product_categories, $cart_product_attributes ) = self::get_cart_product_details( $cart );

        // iterate for each condition
        foreach ( $payment_conditions as $condition_value ) {
            // iterate for each payment gateway
            foreach ( $available_gateways as $gateway_id => $gateway) {
                if ( $condition_value['payment_method'] === $gateway_id ) {
                    if ( $condition_value['type_rule'] === 'show' ) {
                        if ( ! self::verify_conditions( $condition_value, $cart, $cart_product_ids, $cart_product_categories, $cart_product_attributes ) ) {
                            unset( $available_gateways[$gateway_id] );
                        }
                    } elseif ( $condition_value['type_rule'] === 'hide' ) {
                        if ( self::verify_conditions( $condition_value, $cart, $cart_product_ids, $cart_product_categories, $cart_product_attributes ) ) {
                            unset( $available_gateways[$gateway_id] );
                        }
                    }
                }
            }
        }

        return $available_gateways;
    }


    /**
     * Set custom conditions for checkout shipping methods
     * 
     * @since 3.5.0
     * @param array $shipping_methods | Package rates
     * @param array $package | Package of cart items
     * @return array $rates
     */
    public function shipping_methods_conditions( $shipping_methods, $package ) {
        $shipping_conditions = self::filter_component_type('shipping');

        // If conditions do not match, return all shipping methods
        if ( ! is_flexify_checkout() || empty( $shipping_conditions ) ) {
            return $shipping_methods;
        }

        $cart = WC()->cart;
        list( $cart_product_ids, $cart_product_categories, $cart_product_attributes ) = self::get_cart_product_details( $cart );

        // iterate for each condition
        foreach ( $shipping_conditions as $condition => $condition_value ) {
            if ( $condition_value['type_rule'] === 'show' ) {
                $specific_shipping_methods = self::display_specific_shipping_methods( $shipping_methods, $condition_value, $cart, $cart_product_ids, $cart_product_categories, $cart_product_attributes );
                $shipping_methods = array_intersect_key( $shipping_methods, array_flip( $specific_shipping_methods ) );
            } elseif ( $condition_value['type_rule'] === 'hide' ) {
                $methods_to_remove = self::remove_specific_shipping_methods( $shipping_methods, $condition_value, $cart, $cart_product_ids, $cart_product_categories, $cart_product_attributes );
                $shipping_methods = array_diff_key( $shipping_methods, array_flip( $methods_to_remove ) );
            }
        }

        return $shipping_methods;
    }


    /**
     * Get cart product details
     * 
     * @since 3.5.0
     * @version 3.6.5
     * @param object $cart | Cart object
     * @return array | Product details (IDs, categories, attributes)
     */
    public static function get_cart_product_details( $cart ) {
        $cart_product_ids = array();
        $cart_product_categories = array();
        $cart_product_attributes = array();
    
        foreach ( $cart->get_cart() as $cart_item ) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            $cart_product_ids[] = $product_id;
    
            $product_categories = wp_get_post_terms( $product_id, 'product_cat', array('fields' => 'names') );
            $cart_product_categories[$product_id] = $product_categories;
    
            $product_attributes = $product->get_attributes();
            $attributes = array();
    
            foreach ( $product_attributes as $attribute_key => $attribute ) {
                if ( is_object( $attribute ) ) {
                    if ( $attribute->is_taxonomy() ) {
                        $terms = wc_get_product_terms( $product_id, $attribute->get_name(), array('fields' => 'names') );
                        $attributes[$attribute->get_name()] = $terms;
                    } else {
                        $attributes[$attribute->get_name()] = $attribute->get_name();
                    }
                } else {
                    $attributes[$attribute_key] = $attribute;
                }
            }
    
            $cart_product_attributes[$product_id] = $attributes;
        }
    
        return array( $cart_product_ids, $cart_product_categories, $cart_product_attributes );
    }


    /**
     * Get specific shipping methods to show
     * 
     * @since 3.5.0
     * @param array $shipping_methods | Available shipping methods
     * @param array $condition_value | Condition value
     * @param object $cart | Cart object
     * @param array $cart_product_ids | Cart product IDs
     * @param array $cart_product_categories | Cart product categories
     * @param array $cart_product_attributes | Cart product attributes
     * @return array | Specific shipping methods to show
     */
    public static function display_specific_shipping_methods( $shipping_methods, $condition_value, $cart, $cart_product_ids, $cart_product_categories, $cart_product_attributes ) {
        $specific_shipping_methods = array();
        
        foreach ( $shipping_methods as $shipping_id => $shipping ) {
            $method_parts = explode(':', $shipping_id);
            $method_name = $method_parts[0];

            if ( ! self::verify_conditions( $condition_value, $cart, $cart_product_ids, $cart_product_categories, $cart_product_attributes ) ) {
                continue;
            }

            if ( $method_name === $condition_value['shipping_method'] ) {
                $specific_shipping_methods[] = $shipping_id;
            }
        }

        return $specific_shipping_methods;
    }


    /**
     * Get shipping methods to remove
     * 
     * @param array $shipping_methods | Available shipping methods
     * @param array $condition_value | Condition value
     * @param object $cart | Cart object
     * @param array $cart_product_ids | Cart product IDs
     * @param array $cart_product_categories | Cart product categories
     * @param array $cart_product_attributes | Cart product attributes
     * @return array | Methods to remove
     */
    public static function remove_specific_shipping_methods( $shipping_methods, $condition_value, $cart, $cart_product_ids, $cart_product_categories, $cart_product_attributes ) {
        $methods_to_remove = array();

        foreach ( $shipping_methods as $shipping_id => $shipping ) {
            $method_parts = explode(':', $shipping_id);
            $method_name = $method_parts[0];

            if ( $method_name === $condition_value['shipping_method'] && self::verify_conditions( $condition_value, $cart, $cart_product_ids, $cart_product_categories, $cart_product_attributes ) ) {
                $methods_to_remove[] = $shipping_id;
            }
        }

        return $methods_to_remove;
    }


    /**
     * Verify all conditions
     * 
     * @param array $condition_value | Condition value
     * @param object $cart | Cart object
     * @param array $cart_product_ids | Cart product IDs
     * @param array $cart_product_categories | Cart product categories
     * @param array $cart_product_attributes | Cart product attributes
     * @return bool | True if all conditions are met, false otherwise
     */
    public static function verify_conditions( $condition_value, $cart, $cart_product_ids, $cart_product_categories, $cart_product_attributes ) {
        $condition_value_compare = isset( $condition_value['condition_value'] ) ? $condition_value['condition_value'] : '';

        // first condition layer
        if ( $condition_value['verification_condition'] === 'field' ) {
            if ( ! self::check_fields_conditions( $condition_value['condition'], $condition_value['verification_condition_field'], $condition_value_compare ) ) {
                return false;
            }
        } elseif ( $condition_value['verification_condition'] === 'qtd_cart_total' ) {
            if ( ! self::check_cart_conditions( 'qtd_cart_total', $cart, $condition_value['condition'], $condition_value_compare ) ) {
                return false;
            }
        } elseif ( $condition_value['verification_condition'] === 'cart_total_value' ) {
            if ( ! self::check_cart_conditions( 'cart_total_value', $cart, $condition_value['condition'], $condition_value_compare ) ) {
                return false;
            }
        }

        // second condition layer
        if ( isset( $condition_value['specific_user'] ) || isset( $condition_value['specific_role'] ) ) {
            if ( $condition_value['specific_user'] === 'specific_user' ) {
                if ( ! self::check_specific_user_or_roles( get_current_user_id(), 'specific_user', $specific_users, $specific_role, $user_roles ) ) {
                    return false;
                }
            } elseif ( $condition_value['specific_role'] === 'specific_role' ) {
                if ( ! self::check_specific_user_or_roles( get_current_user_id(), 'specific_role', $specific_users, $specific_role, $user_roles ) ) {
                    return false;
                }
            }
        }

        // third condition layer
        if ( isset( $condition_value['specific_products'] ) && $condition_value['specific_products'] === 'specific_products' ) {
            if ( ! self::check_product_filter( 'specific_products', $cart_product_ids, $condition_value['specific_products'] ) ) {
                return false;
            }
        } elseif ( isset( $condition_value['specific_categories'] ) && $condition_value['specific_categories'] === 'specific_categories' ) {
            if ( ! self::check_product_filter( 'specific_categories', $cart_product_categories, $condition_value['specific_categories'] ) ) {
                return false;
            }
        } elseif ( isset( $condition_value['specific_attributes'] ) && $condition_value['specific_attributes'] === 'specific_attributes' ) {
            if ( ! self::check_product_filter( 'specific_attributes', $cart_product_attributes, $condition_value['specific_attributes'] ) ) {
                return false;
            }
        }

        return true;
    }


    /**
     * Filter component type
     * 
     * @param string $type | Component type
     * @return array | Filtered component type
     */
    public static function filter_component_type( $type ) {
        $conditions = get_option('flexify_checkout_conditions', array());

        return array_filter($conditions, function ($condition) use ($type) {
            return isset( $condition['component'] ) && $condition['component'] === $type;
        });
    }

    /**
     * Check specific user function or role for conditions
     * 
     * @since 3.5.0
     * @param int $user_id | Get user ID
     * @param string $condition | Check condition
     * @param array $specific_users | Get specific users ID
     * @param string $specific_role | Get specific user role
     * @param object $user_roles | Object user roles
     * @return bool
     */
    public static function check_specific_user_or_roles( $user_id, $condition, $specific_users, $specific_role, $user_roles ) {
        // check application on users or roles
        if ( $condition === 'specific_user' ) {
            // Stop if user ID is not in array
            if ( ! in_array( $user_id, $specific_users ) ) {
                return false;
            } else {
                return true;
            }
        } elseif ( $condition === 'specific_role' ) {
            // Stop if user role is not in array
            if ( ! in_array( $specific_role, $user_roles ) ) {
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * Check product filters for condition
     * 
     * @since 3.5.0
     * @param string $product_filter | Get product filter type
     * @param array $product_id | Get product ID for search meets
     * @param array $specific_products | Get specific products array
     * @param array $specific_categories | Get specific product categories array
     * @param array $specific_attributes | Get specific product attributes array
     * @return bool
     */
    public static function check_product_filter( $product_filter, $product_id, $specific_products, $specific_categories, $specific_attributes ) {
        // Verify that the product meets the product filter conditions
        if ( $product_filter === 'specific_products' ) {
            $has_product_intersection = array_intersect( $specific_products, $product_id );

            if ( ! empty( $has_product_intersection ) ) {
                return true;
            } else {
                return false;
            }
        } elseif ( $product_filter === 'specific_categories' ) {
            $product_categories = wp_get_post_terms( $product_id, 'product_cat', array('fields' => 'ids') );

            $has_cat_intersection = array_intersect( $specific_categories, $product_categories );

            if ( ! empty( $has_cat_intersection ) ) {
                return true;
            } else {
                return false;
            }
        } elseif ( $product_filter === 'specific_attributes' ) {
            $product_attributes = wc_get_product( $product_id )->get_attributes();

            // iterate for each product attribute
            foreach ( $product_attributes as $attribute => $value ) {
                $attribute_terms = wc_get_product_terms( $product_id, $attribute, array('fields' => 'ids'));

                $has_attr_intersection = array_intersect( $specific_attributes, $attribute_terms );

                if ( ! empty( $has_attr_intersection ) ) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }


    /**
     * Check condition
     * 
     * @since 3.5.0
     * @param string $condition | Check condition
     * @param string $value | Get condition value
     * @param string $value_compare | Optional value for compare with $value
     * @return bool
     */
    public static function check_condition( $condition, $value, $value_compare = '' ) {
        switch ( $condition ) {
            case 'is':
                return $value === $value_compare;
                
            case 'is_not':
                return $value !== $value_compare;
                
            case 'empty':
                return empty( $value );
                
            case 'not_empty':
                return ! empty( $value );
                
            case 'contains':
                return strpos( $value, $value_compare ) !== false;
                
            case 'not_contain':
                return strpos( $value, $value_compare ) === false;
                
            case 'start_with':
                if ( version_compare( PHP_VERSION, '8.0.0' ) >= 0 ) {
                    return str_starts_with( $value, $value_compare );
                } else {
                    return strpos( $value, $value_compare ) === 0;
                }
                
            case 'finish_with':
                if ( version_compare( PHP_VERSION, '8.0.0' ) >= 0 ) {
                    return str_ends_with( $value, $value_compare );
                } else {
                    $length = strlen( $value_compare );
                    return substr( $value, -$length ) === $value_compare;
                }
                
            case 'bigger_then':
                return $value > $value_compare;
                
            case 'less_than':
                return $value < $value_compare;
                
            case '':
                return false;

            case 'none':
            default:
                return false;
        }
    }


    /**
     * Check checkout fields conditions
     * 
     * @since 3.5.0
     * @param string $condition | Get condition type
     * @param string $field | Field ID to check condition
     * @param string $value | Field value to check
     * @return bool
     */
    public static function check_fields_conditions( $condition, $field, $value ) {
        // Get fields from the session and ensure it is an array
        $get_fields = WC()->session->get('flexify_checkout_customer_fields');

        // Ensure $get_fields is an array
        if ( ! is_array( $get_fields ) ) {
            return false;
        }

        // iterate for each billing field from target session
        foreach ( $get_fields as $field_id => $field_value ) {
            if ( $field_id === $field && self::check_condition( $condition, $field_value, $value ) ) {
                return true;
            }
        }

        return false;
    }


    /**
     * Check cart conditions
     * 
     * @since 3.5.0
     * @param string $condition_type | Condition type
     * @param object $cart | Object cart
     * @param string $condition | Condition for check
     * @param string $condition_value | Condition value for validate
     * @return bool
     */
    public static function check_cart_conditions( $condition_type, $cart, $condition, $condition_value ) {
        if ( $condition_type === 'qtd_cart_total' ) {
            // iterate for each cart item
            foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
                $quantity = $cart_item['quantity'];
        
                // check if condition meets
                if ( self::check_condition( $condition, $quantity, $condition_value ) ) {
                    return true;
                }
            }
        } elseif ( $condition_type === 'cart_total_value' ) {
            // get cart total value
            $cart_total = $cart->get_cart_contents_total();

            // check if condition meets
            if ( self::check_condition( $condition, $cart_total, $condition_value ) ) {
                return true;
            }
        }

        return false;
    }
}