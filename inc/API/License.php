<?php

namespace MeuMouse\Flexify_Checkout\API;

// Exit if accessed directly.
defined('ABSPATH') || exit;
    
/**
 * Connect to license authentication server
 * 
 * @since 1.0.0
 * @version 5.1.1
 * @package MeuMouse.com
 */
class License {

    private $product_key;
    private $product_id;
    private $product_base;
    public $fcw_product_key = '49D52DA9137137C0';
    private $fcw_product_id = '3';
    private $fcw_product_base = 'flexify-checkout-for-woocommerce';
    private $clube_m_produt_id = '7';
    private $clube_m_product_base = 'clube-m';
    private $clube_m_product_key = 'B729F2659393EE27';
    private $server_host = 'https://api.meumouse.com/wp-json/license/';
    private $plugin_file;
    private $version = FLEXIFY_CHECKOUT_VERSION;
    private $is_theme = false;
    private $email_address = FLEXIFY_CHECKOUT_ADMIN_EMAIL;
    private static $_onDeleteLicense = array();
    private static $self_obj;
    public $response_obj;
    public $license_message;

    /**
     * Construct function
     * 
     * @since 1.0.0
     * @since 5.1.1
     * @param string $plugin_base_file
     * @return void
     */
    public function __construct( $plugin_base_file = '' ) {
        $license_key = get_option('flexify_checkout_license_key');

        // check if license is for Clube M, else license is product base
        if ( strpos( $license_key, 'CM-' ) === 0 ) {
            $this->product_base = $this->clube_m_product_base;
            $this->product_id = $this->clube_m_produt_id;
            $this->product_key = $this->clube_m_product_key;
        } else {
            $this->product_base = $this->fcw_product_base;
            $this->product_id = $this->fcw_product_id;
            $this->product_key = $this->fcw_product_key;
        }

        $this->plugin_file = $plugin_base_file;
        $dir = dirname( $plugin_base_file );
        $dir = str_replace('\\','/', $dir );

        if ( strpos( $dir,'wp-content/themes' ) !== FALSE ) {
            $this->is_theme = true;
        }

        // connect with API server for authenticate license  
        add_action( 'admin_init', array( $this, 'licenses_api_connection' ) );

        // alternative activation process
        add_action( 'admin_init', array( $this, 'alternative_activation_process' ) );

        // deactive license on expire time
        add_action( 'Flexify_Checkout/License/Check_Expires_Time', array( $this, 'check_license_expires_time' ) );

        // register schedule event first time
        if ( ! get_option('flexify_checkout_schedule_expiration_check_runned') ) {
            add_action( 'admin_init', array( __CLASS__, 'schedule_license_expiration_check' ) );
        }

        // set logger source
    //    Logger::set_logger_source( 'woo-custom-installments-license', false );

        if ( get_option('flexify_checkout_license_expired') ) {
            add_action( 'admin_notices', array( $this, 'license_expired_notice' ) );
        }

        // display require license modal before activated
        add_action( 'Flexify_Checkout/Settings/Header', array( $this, 'display_modal_license_require' ) );
    }


    /**
     * Get plugin instance
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @param self $plugin_base_file | Plugin file
     * @return self|null
     */
    static function &get_instance( $plugin_base_file = null ) {
        if ( empty( self::$self_obj ) ) {
            if ( ! empty( $plugin_base_file ) ) {
                self::$self_obj = new self( $plugin_base_file );
            }
        }

        return self::$self_obj;
    }


    /**
     * Get renew license link
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @param object $response_object | Response object
     * @param string $type | Renew type
     * @return string
     */
    private static function get_renew_link( $response_object, $type = 's' ) {
        if ( empty( $response_object->renew_link ) ) {
            return '';
        }

        $show_button = false;

        if ( $type == 's' ) {
            $support_str = strtolower( trim( $response_object->support_end ) );

            if ( strtolower( trim( $response_object->support_end ) ) == 'no support' ) {
                $show_button = true;
            } elseif ( ! in_array( $support_str, ["unlimited"] ) ) {
                if ( strtotime( 'ADD 30 DAYS', strtotime( $response_object->support_end ) ) < time() ) {
                    $show_button = true;
                }
            }
            
            if ( $show_button ) {
                return $response_object->renew_link . ( strpos( $response_object->renew_link, '?' ) === FALSE ? '?type=s&lic=' . rawurlencode( $response_object->license_key ) : '&type=s&lic='. rawurlencode( $response_object->license_key ) );
            }

            return '';
        } else {
            $show_button = false;
            $expire_str = strtolower( trim( $response_object->expire_date ) );

            if ( ! in_array( $expire_str, array( 'unlimited', 'no expiry' ) ) ) {
                if ( strtotime( 'ADD 30 DAYS', strtotime( $response_object->expire_date ) ) < time() ) {
                    $show_button = true;
                }
            }

            if ( $show_button ) {
                return $response_object->renew_link . ( strpos( $response_object->renew_link, '?' ) === FALSE ? '?type=l&lic=' . rawurlencode( $response_object->license_key ) : '&type=l&lic=' . rawurlencode( $response_object->license_key ) );
            }

            return '';
        }
    }


    /**
     * Encrypt response
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @param string $plaintext | Object response to encrypt
     * @param string $password | Product key
     * @return string
     */
    private function encrypt( $plaintext, $password = '' ) {
        if ( empty( $password ) ) {
            $password = $this->product_key;
        }

        $plaintext = wp_rand( 10, 99 ) . $plaintext . wp_rand( 10, 99 );
        $method = 'aes-256-cbc';
        $key = substr( hash( 'sha256', $password, true ), 0, 32 );
        $iv = substr( strtoupper( md5( $password ) ), 0, 16 );

        return base64_encode( openssl_encrypt( $plaintext, $method, $key, OPENSSL_RAW_DATA, $iv ) );
    }
    

    /**
     * Decrypt response
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @param string $encrypted | Encrypted response
     * @param string $password | Product key
     * @return string
     */
    private function decrypt( $encrypted, $password = '' ) {
        if ( empty( $password ) ) {
            $password = $this->product_key;
        }

        $logger = wc_get_logger();
        $plugin_log_file = 'flexify-checkout-for-woocommerce-log';
        $logger->info('(Flexify Checkout para WooCommerce) Response encrypted: ' . print_r( $encrypted, true ), array('source' => $plugin_log_file));

        if ( is_string( $encrypted ) ) {
            $method = 'aes-256-cbc';
            $key = substr( hash( 'sha256', $password, true ), 0, 32 );
            $iv = substr( strtoupper( md5( $password ) ), 0, 16 );
    
            $plaintext = openssl_decrypt( base64_decode( $encrypted ), $method, $key, OPENSSL_RAW_DATA, $iv );
    
            if ( $plaintext === false ) {
                $logger->info('(Flexify Checkout para WooCommerce) Falha na descriptografia. Input: $encrypted: ' . print_r( $plaintext, true ), array('source' => $plugin_log_file));
                
                return '';
            }
    
            return substr( $plaintext, 2, -2 );
        } else {
            $logger->info('(Flexify Checkout para WooCommerce) A entrada para decrypt não é uma string. Tipo: ' . gettype( $encrypted ), array('source' => $plugin_log_file));
            
            return '';
        }
    }


    /**
     * Get site domain
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @return string
     */
    public static function get_domain() {
        if ( function_exists('site_url') ) {
            return site_url();
        }

        if ( defined('WPINC') && function_exists('get_bloginfo') ) {
            return get_bloginfo('url');
        } else {
            $base_url = ( ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == "on" ) ? "https" : "http" );
            $base_url .= "://" . $_SERVER['HTTP_HOST'];
            $base_url .= str_replace( basename( $_SERVER['SCRIPT_NAME'] ), "", $_SERVER['SCRIPT_NAME'] );

            return $base_url;
        }
    }


    /**
     * Processes the API response
     *
     * @since 1.0.0
     * @version 3.8.0
     * @param string $response Raw API response.
     * @return stdClass|mixed Object decoded from the JSON response or error object, if applicable.
     */
    private function process_response( $response ) {
        if ( get_option('flexify_checkout_alternative_license') === 'active' ) {
            return;
        }

        if ( ! empty( $response ) ) {
            $resbk = $response;
            $decrypted_response = $response;
            $logger = wc_get_logger();
            $plugin_log_file = 'flexify-checkout-for-woocommerce-log';

            $logger->info('(Flexify Checkout para WooCommerce) Response: ' . print_r( $response, true ), array('source' => $plugin_log_file));

            if ( ! empty( $this->product_key ) ) {
                // Try to decrypt
                $decrypted_response = $this->decrypt( $response );

                // Add a WooCommerce log to verify decrypted content
                $logger->info('(Flexify Checkout para WooCommerce) Decrypted response: ' . print_r( $decrypted_response, true ), array('source' => $plugin_log_file));

                if ( empty( $decrypted_response ) ) {
                    update_option( 'flexify_checkout_alternative_license_activation', 'yes' );

                    // Handle decryption failure
                    $decryption_error = new \stdClass();
                    $decryption_error->status = false;
                    $decryption_error->msg = __( 'Ocorreu um erro na conexão com o servidor de verificação de licenças. Verifique o erro nos logs do WooCommerce.', 'flexify-checkout-for-woocommerce' );
                    $decryption_error->data = NULL;

                    return $decryption_error;
                }
            }

            // Ensure decrypted_response is a string before decoding the JSON
            if (is_object($decrypted_response)) {
                $decrypted_response = json_encode($decrypted_response);
            }

            // Try decoding the JSON
            $decoded_response = json_decode( $decrypted_response );

            $logger->info('(Flexify Checkout para WooCommerce) Response decoded: ' . print_r( $decoded_response, true ), array('source' => $plugin_log_file));

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                // Handle JSON decoding error
                $json_error = new \stdClass();
                $json_error->status = false;
                $json_error->msg = sprintf( __( 'Erro JSON: %s', 'flexify-checkout-for-woocommerce' ), json_last_error_msg() );
                $json_error->data = $resbk;

                return $json_error;
            }

            return $decoded_response;
        }

        // Treat unknown response
        $unknown_response = new \stdClass();
        $unknown_response->msg = __( 'Resposta desconhecida', 'flexify-checkout-for-woocommerce' );
        $unknown_response->status = false;
        $unknown_response->data = NULL;

        return $unknown_response;
    }


    /**
     * Request on API server
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @param string $relative_url | API URL to concat
     * @param object $data | Object data to encode and add to body request
     * @param string $error | Error message
     * @return string
     */
    private function _request( $relative_url, $data, &$error = '' ) {
        $transient_name = 'flexify_checkout_api_request_cache';
        $cached_response = get_transient( $transient_name );

        if ( false === $cached_response ) {
            $response = new \stdClass();
            $response->status = false;
            $response->msg = __( 'Resposta vazia.', 'flexify-checkout-for-woocommerce' );
            $response->is_request_error = false;
            $final_data = wp_json_encode( $data );
            $url = rtrim( $this->server_host, '/' ) . "/" . ltrim( $relative_url, '/' );
    
            if ( ! empty( $this->product_key ) ) {
                $final_data = $this->encrypt( $final_data );
            }
    
            if ( function_exists('wp_remote_post') ) {
                $request_params = array(
                    'method' => 'POST',
                    'sslverify' => true,
                    'timeout' => 60,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array(),
                    'body' => $final_data,
                    'cookies' => array(),
                );
    
                $server_response = wp_remote_post( $url, $request_params );

                $logger = wc_get_logger();
                $plugin_log_file = 'flexify-checkout-for-woocommerce-log';

                $logger->info('(Flexify Checkout para WooCommerce) Request response: ' . print_r( $server_response, true ), array('source' => $plugin_log_file));
    
                if ( is_wp_error( $server_response ) ) {
                    $request_params['sslverify'] = false;
                    $server_response = wp_remote_post( $url, $request_params );
    
                    if ( is_wp_error( $server_response ) ) {
                        $curl_error_message = $server_response->get_error_message();
    
                        // Check if it is a cURL 35 error
                        if ( strpos( $curl_error_message, 'cURL error 35' ) !== false ) {
                            $error = __( 'Erro cURL 35: Problema de comunicação SSL/TLS.', 'flexify-checkout-for-woocommerce' );
                        } else {
                            $response->msg = $curl_error_message;
                            $response->status = false;
                            $response->data = NULL;
                            $response->is_request_error = true;
                        }
                    } else {
                        // If data response is successful, cache for 7 days
                        if ( ! empty( $server_response['body'] ) && ( is_array( $server_response ) && 200 === (int) wp_remote_retrieve_response_code( $server_response ) ) && $server_response['body'] != "GET404" ) {
                            $cached_response = $server_response['body'];
                            set_transient( $transient_name, $cached_response, 7 * DAY_IN_SECONDS );
                        }
                    }
                } else {
                    if ( ! empty( $server_response['body'] ) && ( is_array( $server_response ) && 200 === (int) wp_remote_retrieve_response_code( $server_response ) ) && $server_response['body'] != "GET404" ) {
                        $cached_response = $server_response['body'];
                    }
                }
            } elseif ( ! extension_loaded( 'curl' ) ) {
                $response->msg = __( 'A extensão cURL está faltando.', 'flexify-checkout-for-woocommerce' );
                $response->status = false;
                $response->data = NULL;
                $response->is_request_error = true;
            } else {
                // Curl when in last resort
                $curlParams = array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 120,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $final_data,
                    CURLOPT_HTTPHEADER => array(
                        "Content-Type: text/plain",
                        "cache-control: no-cache"
                    )
                );
    
                $curl = curl_init();
                curl_setopt_array( $curl, $curlParams );
                $server_response = curl_exec( $curl );
                $curlErrorNo = curl_errno( $curl );
                $error = curl_error( $curl );
                curl_close( $curl );
    
                if ( ! curl_exec( $curl ) ) {
                    $error_message = curl_error( $curl );
    
                    // Check if it is a cURL 35 error
                    if ( strpos( $error_message, 'cURL error 35' ) !== false ) {
                        $error = __( 'Erro cURL 35: Problema de comunicação SSL/TLS.', 'flexify-checkout-for-woocommerce' );
                    } else {
                        $response->msg = sprintf( __( 'Erro cURL: %s', 'flexify-checkout-for-woocommerce' ), $error_message );
                    }
                }
    
                if ( ! $curlErrorNo ) {
                    if ( ! empty( $server_response ) ) {
                        $cached_response = $server_response;
                    }
                } else {
                    $curl = curl_init();
                    $curlParams[CURLOPT_SSL_VERIFYPEER] = false;
                    $curlParams[CURLOPT_SSL_VERIFYHOST] = false;
                    curl_setopt_array( $curl, $curlParams );
                    $server_response = curl_exec( $curl );
                    $curlErrorNo = curl_errno( $curl );
                    $error = curl_error( $curl );
                    curl_close( $curl );
    
                    if ( ! $curlErrorNo ) {
                        if ( ! empty( $server_response ) ) {
                            $cached_response = $server_response;
                        }
                    } else {
                        $response->msg = $error;
                        $response->status = false;
                        $response->data = NULL;
                        $response->is_request_error = true;
                    }
                }
            }
    
            // If there is a response, set it in cache
            if ( ! empty( $cached_response ) ) {
                set_transient( $transient_name, $cached_response, 7 * DAY_IN_SECONDS );
            }
    
            return $this->process_response( $cached_response ? $cached_response : $response ); // Fixed from process_response to processes_response
        }
    
        return $this->process_response( $cached_response );
    }

    
    /**
     * Build object to send response API
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @param string $purchase_key | License key
     * @return object
     */
    private function get_response_param( $purchase_key ) {
        $req = new \stdClass();
        $req->license_key = $purchase_key;
        $req->email = $this->email_address;
        $req->domain = self::get_domain();
        $req->app_version = $this->version;
        $req->product_id = $this->product_id;
        $req->product_base = $this->product_base;

        return $req;
    }


    /**
     * Generate hash key
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @return string
     */
    private function get_key_name() {
        return hash( 'crc32b', self::get_domain() . $this->plugin_file . $this->product_id . $this->product_base . $this->product_key . "LIC" );
    }


    /**
     * Set response base option
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @param object $response | Response object
     * @return void
     */
    private function set_response_base( $response ) {
        $key = $this->get_key_name();
        $data = $this->encrypt( maybe_serialize( $response ), self::get_domain() );
        update_option( $key, $data ) || add_option( $key, $data );
    }


    /**
     * Get response base option
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @return string
     */
    public function get_response_base() {
        $key = $this->get_key_name();
        $response = get_option( $key, NULL );

        if ( empty( $response ) ) {
            return NULL;
        }

        return maybe_unserialize( $this->decrypt( $response, self::get_domain() ) );
    }


    /**
     * Remove response base option
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @return string
     */
    public function remove_response_base() {
        $key = $this->get_key_name();
        $is_deleted = delete_option( $key );

        foreach ( self::$_onDeleteLicense as $func ) {
            if ( is_callable( $func ) ) {
                call_user_func( $func );
            }
        }

        return $is_deleted;
    }


    /**
     * Deactive license action
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @param string $plugin_base_file | Plugin base file
     * @param string $message | Error message
     * @return object
     */
    public static function deactive_license( $plugin_base_file, &$message = "" ) {
        $obj = self::get_instance( $plugin_base_file );

        return $obj->deactive_license_process( $message );
    }


    /**
     * Check purchase key
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @param string $purchase_key | License key
     * @param string $error | Error message
     * @param object $response | Response object
     * @param string $plugin_base_file | Plugin base file
     * @return object
     */
    public static function check_license( $purchase_key, &$error = '', &$response = null, $plugin_base_file = '' ) {
        $obj = self::get_instance( $plugin_base_file );

        return $obj->check_license_object( $purchase_key, $error, $response );
    }


    /**
     * Deactive license process
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @param string $message | Error message
     * @return bool
     */
    final function deactive_license_process( &$message = '' ) {
        $old_response = $this->get_response_base();

        if ( ! empty( $old_response->is_valid ) ) {
            if ( ! empty( $old_response->license_key ) ) {
                $param = $this->get_response_param( $old_response->license_key );
                $response = $this->_request( 'product/deactive/' . $this->product_id, $param, $message );
                update_option('flexify_checkout_license_response_object', $response);

                $logger = wc_get_logger();
                $plugin_log_file = 'flexify-checkout-for-woocommerce-log';
                $logger->info('(Flexify Checkout para WooCommerce) Deactive response object: ' . print_r( $response, true ), array('source' => $plugin_log_file));

                if ( empty( $response->code ) ) {
                    if ( ! empty( $response->status ) ) {
                        $message = $response->msg;
                        $this->remove_response_base();

                        return true;
                    } else {
                        $message = $response->msg;

                        return true;
                    }
                } else {
                    $message = $response->message;
                }
            }
        } else {
            $this->remove_response_base();

            return true;
        }

        return false;
    }


    /**
     * Check if license is active and valid
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @param string $purchase_key | License key
     * @param string $error | Error message
     * @param object $response_object | Response object
     * @return mixed object or bool
     */
    final function check_license_object( $purchase_key, &$error = '', &$response_object = null ) {
        if ( get_option('flexify_checkout_alternative_license') === 'active' ) {
            return;
        }

        if ( empty( $purchase_key ) ) {
            $this->remove_response_base();
            $error = "";
    
            return false;
        }
    
        $transient_name = 'flexify_checkout_api_response_cache';
        $cached_response = get_transient( $transient_name );
    
        if ( false !== $cached_response ) {
            $response_object = maybe_unserialize( $cached_response );
            unset( $response_object->next_request );
    
            return true;
        }
    
        $old_response = $this->get_response_base();
        $isForce = false;
    
        if ( ! empty( $old_response ) ) {
            if ( ! empty( $old_response->expire_date ) && strtolower( $old_response->expire_date ) != "no expiry" && strtotime( $old_response->expire_date ) < time() ) {
                $isForce = true;
            }
    
            if ( ! $isForce && ! empty( $old_response->is_valid ) && $old_response->next_request > time() && ( ! empty( $old_response->license_key ) && $purchase_key == $old_response->license_key ) ) {
                $response_object = clone $old_response;
                unset( $response_object->next_request );
    
                return true;
            }
        }
    
        $param = $this->get_response_param( $purchase_key );
        $response = $this->_request( 'product/active/' . $this->product_id, $param, $error );

        if ( empty( $response->is_request_error ) ) {
            if ( empty( $response->code ) ) {
                if ( ! empty( $response->status ) ) {
                    if ( ! empty( $response->data ) ) {
                        $serialObj = $this->decrypt( $response->data, $param->domain );
                        $licenseObj = maybe_unserialize( $serialObj );
                        update_option( 'flexify_checkout_license_response_object', $licenseObj );
    
                        if ( $licenseObj->is_valid ) {
                            $response_object = new \stdClass();
                            $response_object->is_valid = $licenseObj->is_valid;
    
                            if ( $licenseObj->request_duration > 0 ) {
                                $response_object->next_request = strtotime( "+ {$licenseObj->request_duration} hour" );
                            } else {
                                $response_object->next_request = time();
                            }
    
                            $response_object->expire_date = $licenseObj->expire_date;
                            $response_object->support_end = $licenseObj->support_end;
                            $response_object->license_title = $licenseObj->license_title;
                            $response_object->license_key = $purchase_key;
                            $response_object->msg = $response->msg;
                            $response_object->renew_link = ! empty( $licenseObj->renew_link ) ? $licenseObj->renew_link : '';
                            $response_object->expire_renew_link = self::get_renew_link( $response_object, "l" );
                            $response_object->support_renew_link = self::get_renew_link( $response_object, "s" );
                            $this->set_response_base( $response_object );
    
                            // Cache the response for 1 day
                            set_transient( $transient_name, maybe_serialize( $response_object ), DAY_IN_SECONDS );
    
                            unset( $response_object->next_request );
                            delete_transient( $this->product_base . "_up" );
    
                            return true;
                        } else {
                            if ( $this->check_old_response( $old_response, $response_object, $response ) ) {
                                return true;
                            } else {
                                $this->remove_response_base();
                                $error = ! empty( $response->msg ) ? $response->msg : '';
                            }
                        }
                    } else {
                        $error = __( 'Dados inválidos.', 'flexify-checkout-for-woocommerce' );
                    }
                } else {
                    $error = $response->msg;
                }
            } else {
                $error = $response->message;
            }
        } else {
            if ( $this->check_old_response( $old_response, $response_object, $response ) ) {
                return true;
            } else {
                $this->remove_response_base();
                $error = ! empty( $response->msg ) ? $response->msg : '';
            }
        }
    
        return $this->check_old_response( $old_response, $response_object );
    }


    /**
     * Check if old response is active
     * 
     * @since 1.0.0
     * @version 3.8.0
     * @param object $old_response | 
     * @param object $response_object | 
     * @return bool
     */
    private function check_old_response( &$old_response, &$response_object ) {
        if ( ! empty( $old_response ) && ( empty( $old_response->tried ) || $old_response->tried <= 2 ) ) {
            $old_response->next_request = strtotime('+ 1 hour');
            $old_response->tried = empty( $old_response->tried ) ? 1 : ( $old_response->tried + 1 );
            $response_object = clone $old_response;
            unset( $response_object->next_request );

            if ( isset( $response_object->tried ) ) {
                unset( $response_object->tried );
            }

            $this->set_response_base( $old_response );

            return true;
        }

        return false;
    }


    /**
     * Load API settings
     * 
     * @since 1.0.0
     * @version 3.8.3
     * @return void
     */
    public function licenses_api_connection() {
        $this->response_obj = new \stdClass();
        $message = '';
        $license_key = get_option('flexify_checkout_license_key', '');
    
        // active license action
        if ( isset( $_POST['flexify_checkout_active_license'] ) ) {
            // clear response cache first
            delete_transient('flexify_checkout_api_request_cache');
            delete_transient('flexify_checkout_api_response_cache');
            delete_transient('flexify_checkout_license_status_cached');
    
            $license_key = ! empty( $_POST['flexify_checkout_license_key'] ) ? $_POST['flexify_checkout_license_key'] : '';
            update_option( 'flexify_checkout_license_key', $license_key ) || add_option('flexify_checkout_license_key', $license_key );
            update_option( 'flexify_checkout_temp_license_key', $license_key ) || add_option('flexify_checkout_temp_license_key', $license_key );
    
            // Check on the server if the license is valid and update responses and options
            if ( self::check_license( $license_key, $this->license_message, $this->response_obj, FLEXIFY_CHECKOUT_FILE ) ) {
                if ( $this->response_obj && $this->response_obj->is_valid ) {
                    update_option( 'flexify_checkout_license_status', 'valid' );
                    delete_option('flexify_checkout_temp_license_key');
                    delete_option('flexify_checkout_alternative_license_activation');
                } else {
                    update_option( 'flexify_checkout_license_status', 'invalid' );
                }
        
                if ( isset( $_POST['flexify_checkout_active_license'] ) && self::is_valid() ) {
                    add_action( 'Flexify_Checkout/Settings/Header', array( $this, 'activated_license_notice' ) );
                }
            } else {
                if ( ! empty( $license_key ) && ! empty( $this->license_message ) ) {
                    add_action( 'Flexify_Checkout/Settings/Header', array( $this, 'display_license_messages' ) );
                }
            }
        }
    }


    /**
     * Generate alternative activation object from decrypted license
     * 
     * @since 3.3.0
     * @version 3.8.0
     * @return void
     */
    public function alternative_activation_process() {
        $decrypted_license_data = get_option('flexify_checkout_alternative_license_decrypted');
        $license_data_array = json_decode( stripslashes( $decrypted_license_data ) );
        $this_domain = self::get_domain();

        $allowed_products = array(
            $this->fcw_product_id,
            $this->clube_m_produt_id,
        );

        if ( $license_data_array === null ) {
            return;
        }

        // stop if this site is not same from license site
        if ( $this_domain !== $license_data_array->site_domain ) {
            add_action( 'Flexify_Checkout/Settings/Header', array( $this, 'not_allowed_site_notice' ) );

            return;
        }

        // stop if product license is not same this product
        if ( ! in_array( $license_data_array->selected_product, $allowed_products ) ) {
            add_action( 'Flexify_Checkout/Settings/Header', array( $this, 'not_allowed_product_notice' ) );

            return;
        }

        $license_object = $license_data_array->license_object;

        if ( $this_domain === $license_data_array->site_domain ) {
            delete_transient('flexify_checkout_api_request_cache');
            delete_transient('flexify_checkout_api_response_cache');
            delete_transient('flexify_checkout_license_status_cached');

            $obj = new \stdClass();
            $obj->license_key = $license_data_array->license_code;
            $obj->email = $license_data_array->user_email;
            $obj->domain = $this_domain;
            $obj->app_version = FLEXIFY_CHECKOUT_VERSION;
            $obj->product_id = $license_data_array->selected_product;
            $obj->product_base = $license_data_array->product_base;
            $obj->is_valid = $license_object->is_valid;
            $obj->license_title = $license_object->license_title;
            $obj->expire_date = $license_object->expire_date;

            update_option( 'flexify_checkout_alternative_license', 'active' );
            update_option( 'flexify_checkout_license_response_object', $obj );
            update_option( 'flexify_checkout_license_key', $obj->license_key );
            update_option( 'flexify_checkout_license_status', 'valid' );
            delete_option('flexify_checkout_alternative_license_decrypted');

            add_action( 'Flexify_Checkout/Settings/Header', array( $this, 'activated_license_notice' ) );
        }
    }


    /**
     * Check if license is valid
     * 
     * @since 1.2.5
     * @version 3.8.0
     * @return bool
     */
    public static function is_valid() {
        $cached_result = get_transient('flexify_checkout_license_status_cached');

        // If the result is cached, return it
        if ( $cached_result !== false ) {
            return $cached_result;
        }

        $object_query = get_option('flexify_checkout_license_response_object');

        if ( is_object( $object_query ) && ! empty( $object_query ) && isset( $object_query->is_valid )  ) {
            // set response cache for 24h
            set_transient('flexify_checkout_license_status_cached', true, 86400);

            return true;
        } else {
            set_transient('flexify_checkout_license_status_cached', false, 86400);
            update_option( 'flexify_checkout_license_status', 'invalid' );

            return false;
        }
    }


    /**
     * Get license title
     * 
     * @version 1.2.5
     * @return string
     */
    public static function license_title() {
        $object_query = get_option('flexify_checkout_license_response_object');
    
        if ( is_object( $object_query ) && ! empty( $object_query ) && isset( $object_query->license_title ) ) {
          return $object_query->license_title;
        } else {
          return esc_html__( 'Não disponível', 'flexify-checkout-for-woocommerce' );
        }
    }


    /**
     * Get license expire date
     * 
     * @since 1.2.5
     * @version 3.8.0
     * @return string
     */
    public static function license_expire() {
        $object_query = get_option('flexify_checkout_license_response_object');

        if ( is_object( $object_query ) && ! empty( $object_query ) && isset( $object_query->expire_date ) ) {
            if ( $object_query->expire_date === 'No expiry' ) {
                return esc_html__( 'Nunca expira', 'flexify-checkout-for-woocommerce' );
            } else {
                if ( strtotime( $object_query->expire_date ) < time() ) {
                    $object_query->is_valid = false;

                    update_option( 'flexify_checkout_license_response_object', $object_query );
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


    /**
     * Check if license is expired
     * 
     * @since 3.8.0
     * @return bool
     */
    public static function expired_license() {
        $object_query = get_option('flexify_checkout_license_response_object');

        if ( is_object( $object_query ) && ! empty( $object_query ) && isset( $object_query->expire_date ) ) {
            if ( $object_query->expire_date === 'No expiry' ) {
                return false;
            } else {
                if ( strtotime( $object_query->expire_date ) < time() ) {
                    $object_query->is_valid = false;

                    update_option( 'flexify_checkout_license_response_object', $object_query );

                    return false;
                }
            }
        }
    }


    /**
     * Try to decrypt license with multiple keys
     * 
     * @since 1.3.0
     * @version 3.8.0
     * @param string $encrypted_data | Encrypted data
     * @param array $possible_keys | Array list with decryp keys
     * @return mixed Decrypted string or null
     */
    public static function decrypt_alternative_license( $encrypted_data, $possible_keys ) {
        foreach ( $possible_keys as $key ) {
            $decrypted_data = openssl_decrypt( $encrypted_data, 'AES-256-CBC', $key, 0, substr( $key, 0, 16 ) );

            // Checks whether decryption was successful
            if ( $decrypted_data !== false ) {
                return $decrypted_data;
            }
        }
        
        return null;
    }


    /**
	 * Display admin notice when license is expired
	 * 
	 * @since 5.1.1
	 * @return void
	 */
    public function license_expired_notice() {
        if ( self::expired_license() ) {
			$class = 'notice notice-error is-dismissible';
			$message = __( 'Sua licença do <strong>Flexify Checkout</strong> expirou, realize a renovação para continuar aproveitando os recursos Pro.', 'flexify-checkout-for-woocommerce' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
		}
    }


    /**
     * Display modal for require license when is not Pro
     * 
     * @since 3.8.0
     * @return void
     */
    public function display_modal_license_require() {
        if ( ! self::is_valid() ) : ?>
            <div id="popup-pro-notice" class="popup-container">
                <div class="popup-content popup-sm">
                    <div class="popup-body">
                        <div class="d-flex flex-column align-items-center p-4">
                            <div class="btn-icon rounded-circle p-2 mb-3 bg-translucent-primary">
                                <svg class="icon-pro icon-primary" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                            </div>

                            <h5 class="text-center mb-2 mt-3"><?php echo esc_html__('Este recurso está disponível na versão Pro', 'flexify-checkout-for-woocommerce'); ?></h5>
                            <span class="title-hightlight mt-2 mb-3"></span>
                            <span class="text-muted fs-lg p-3"><?php echo esc_html__( 'Uma licença permite que você desbloqueie todos os recursos Pro que o plugin tem a oferecer.', 'flexify-checkout-for-woocommerce' ) ?></span>
                        </div>
                        
                        <div class="my-4 p-3">
                            <button id="active_license_form" class="btn btn-lg btn-outline-secondary me-3"><?php echo esc_html__('Já tenho uma licença', 'flexify-checkout-for-woocommerce'); ?></button>
                            <a class="btn btn-lg btn-primary d-inline-flex" href="https://meumouse.com/plugins/flexify-checkout-para-woocommerce/?utm_source=wordpress&utm_medium=plugins-list&utm_campaign=flexify_checkout" target="_blank">
                                <span><?php echo esc_html__( 'Comprar uma licença', 'flexify-checkout-for-woocommerce' ) ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif;
    }


    /**
     * Check expiration license on schedule event
     * 
     * @since 5.1.1
     * @return void
     */
    public static function schedule_license_expiration_check( $expiration_timestamp = 0 ) {
        // Cancel any previous bookings to avoid duplication
        wp_clear_scheduled_hook('Flexify_Checkout/License/Check_Expires_Time');

        if ( $expiration_timestamp > 0 ) {
            if ( $expiration_timestamp > time() ) {
                // Add 3 days to timestamp
                $expiration_timestamp += 3 * DAY_IN_SECONDS;

                // Schedule event to expire at exactly the right time
                wp_schedule_single_event( $expiration_timestamp, 'Flexify_Checkout/License/Check_Expires_Time' );
            }
        } else {
            $info = get_option('flexify_checkout_license_info');

            if ( is_object( $info ) && ! empty( $info->expiry_time ) ) {
                $expiration_timestamp = strtotime( $info->expiry_time );
            } else {
                $object_query = get_option('flexify_checkout_license_response_object');

                if ( is_object( $object_query ) && ! empty( $object_query->expire_date ) ) {
                    $expiration_timestamp = strtotime( $object_query->expire_date );
                }
            }

            if ( ! empty( $expiration_timestamp ) && $expiration_timestamp > time() ) {
                // Add 3 days to timestamp
                $expiration_timestamp += 3 * DAY_IN_SECONDS;

                // Schedule event to expire at exactly the right time
                wp_schedule_single_event( $expiration_timestamp, 'Flexify_Checkout/License/Check_Expires_Time' );
            }
        }

        // register runned event
        update_option( 'flexify_checkout_schedule_expiration_check_runned', true );
    }


    /**
     * Deactivate license on scheduled event
     * 
     * @since 5.1.1
     * @return void
     */
    public function check_license_expires_time() {
        $license_key = get_option('flexify_checkout_license_key');
        $api_expiry_time = $this->get_expires_time( $license_key );

        if ( $api_expiry_time ) {
            $expiration_timestamp = strtotime( $api_expiry_time );

            // license expired
            if ( $expiration_timestamp < time() ) {
                update_option( 'flexify_checkout_license_expired', true );
                $message = '';

                self::deactive_license( FLEXIFY_CHECKOUT_FILE, $message );
            } else {
                self::schedule_license_expiration_check( $expiration_timestamp );
            }
        }
    }


    /**
     * Get license expires time
     * 
     * @since 5.1.1
     * @param string $license_key | License key
     * @return array
     */
    public function get_expires_time( $license_key ) {
        $api_url = $this->server_host . 'license/view';

        $response = wp_remote_post( $api_url, array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'api_key' => '41391199-FE02BDAA-3E8E3920-CDACDE2F',
                'license_code' => $license_key
            ),
            'timeout' => 30,
        ));

        if ( is_wp_error( $response ) ) {
            Logger::register_log( 'Error getting license expiration time: ' . $response->get_error_message(), 'ERROR' );

            return false;
        }

        $response_body = wp_remote_retrieve_body( $response );
        $decoded_response = json_decode( $response_body, true );

        // check if response is valid
        if ( ! is_array( $decoded_response ) || empty( $decoded_response['data']['expiry_time'] ) ) {
            Logger::register_log( 'Invalid response from license API: ' . print_r( $decoded_response, true ), 'ERROR' );
            return false;
        }

        return $decoded_response['data']['expiry_time'];
    }


    /**
     * Display notice for activated Pro license
     * 
     * @since 3.8.0
     * @return void
     */
    public function activated_license_notice() {
        ?>
        <div class="toast update-notice-wci show">
            <div class="toast-header bg-success text-white">
                <svg class="icon icon-white me-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/><g><path d="M10.5 15.25C10.307 15.2353 10.1276 15.1455 9.99998 15L6.99998 12C6.93314 11.8601 6.91133 11.7029 6.93756 11.55C6.96379 11.3971 7.03676 11.2562 7.14643 11.1465C7.2561 11.0368 7.39707 10.9638 7.54993 10.9376C7.70279 10.9114 7.86003 10.9332 7.99998 11L10.47 13.47L19 5.00004C19.1399 4.9332 19.2972 4.91139 19.45 4.93762C19.6029 4.96385 19.7439 5.03682 19.8535 5.14649C19.9632 5.25616 20.0362 5.39713 20.0624 5.54999C20.0886 5.70286 20.0668 5.86009 20 6.00004L11 15C10.8724 15.1455 10.6929 15.2353 10.5 15.25Z" fill="#ffffff"/> <path d="M12 21C10.3915 20.9974 8.813 20.5638 7.42891 19.7443C6.04481 18.9247 4.90566 17.7492 4.12999 16.34C3.54037 15.29 3.17596 14.1287 3.05999 12.93C2.87697 11.1721 3.2156 9.39921 4.03363 7.83249C4.85167 6.26578 6.1129 4.9746 7.65999 4.12003C8.71001 3.53041 9.87134 3.166 11.07 3.05003C12.2641 2.92157 13.4719 3.03725 14.62 3.39003C14.7224 3.4105 14.8195 3.45215 14.9049 3.51232C14.9903 3.57248 15.0622 3.64983 15.116 3.73941C15.1698 3.82898 15.2043 3.92881 15.2173 4.03249C15.2302 4.13616 15.2214 4.2414 15.1913 4.34146C15.1612 4.44152 15.1105 4.53419 15.0425 4.61352C14.9745 4.69286 14.8907 4.75712 14.7965 4.80217C14.7022 4.84723 14.5995 4.87209 14.4951 4.87516C14.3907 4.87824 14.2867 4.85946 14.19 4.82003C13.2186 4.52795 12.1987 4.43275 11.19 4.54003C10.193 4.64212 9.22694 4.94485 8.34999 5.43003C7.50512 5.89613 6.75813 6.52088 6.14999 7.27003C5.52385 8.03319 5.05628 8.91361 4.77467 9.85974C4.49307 10.8059 4.40308 11.7987 4.50999 12.78C4.61208 13.777 4.91482 14.7431 5.39999 15.62C5.86609 16.4649 6.49084 17.2119 7.23999 17.82C8.00315 18.4462 8.88357 18.9137 9.8297 19.1953C10.7758 19.4769 11.7686 19.5669 12.75 19.46C13.747 19.3579 14.713 19.0552 15.59 18.57C16.4349 18.1039 17.1818 17.4792 17.79 16.73C18.4161 15.9669 18.8837 15.0864 19.1653 14.1403C19.4469 13.1942 19.5369 12.2014 19.43 11.22C19.4201 11.1169 19.4307 11.0129 19.461 10.9139C19.4914 10.8149 19.5409 10.7228 19.6069 10.643C19.6728 10.5631 19.7538 10.497 19.8453 10.4485C19.9368 10.3999 20.0369 10.3699 20.14 10.36C20.2431 10.3502 20.3471 10.3607 20.4461 10.3911C20.5451 10.4214 20.6372 10.471 20.717 10.5369C20.7969 10.6028 20.863 10.6839 20.9115 10.7753C20.9601 10.8668 20.9901 10.9669 21 11.07C21.1821 12.829 20.842 14.6026 20.0221 16.1695C19.2022 17.7363 17.9389 19.0269 16.39 19.88C15.3288 20.4938 14.1495 20.8755 12.93 21C12.62 21 12.3 21 12 21Z" fill="#ffffff"/></g></svg>
                <span class="me-auto"><?php echo esc_html__( 'Licença ativada com sucesso!', 'flexify-checkout-for-woocommerce' ); ?></span>
                <button class="btn-close btn-close-white ms-2 hide-toast" type="button" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"><?php echo esc_html__( 'Todos os recursos da versão Pro agora estão ativos!', 'flexify-checkout-for-woocommerce' ); ?></div>
        </div>
        <?php
    }


    /**
     * Display admin notices for license messages
     * 
     * @since 3.8.0
     * @return void
     */
    public function display_license_messages() {
        if ( ! empty( $this->license_message ) ) : ?>
            <div class="toast toast-danger show">
                <div class="toast-header bg-danger text-white">
                    <svg class="icon icon-white me-2" viewBox="0 0 24 24" style="fill: rgba(255, 255, 255, 1);transform: ;msFilter:;"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>
                    <span class="me-auto"><?php echo esc_html__( 'Ops! Ocorreu um erro.', 'flexify-checkout-for-woocommerce' ); ?></span>
                    <button class="btn-close btn-close-white ms-2 hide-toast" type="button" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body"><?php echo esc_html__( $this->license_message, 'flexify-checkout-for-woocommerce' ); ?></div>
            </div>
        <?php endif;
    }


    /**
     * Display not allowed site notice
     * 
     * @since 3.8.0
     * @return void
     */
    public function not_allowed_site_notice() {
        ?>
        <div class="toast toast-danger show">
            <div class="toast-header bg-danger text-white">
                <svg class="icon icon-white me-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/><g><path d="M10.5 15.25C10.307 15.2353 10.1276 15.1455 9.99998 15L6.99998 12C6.93314 11.8601 6.91133 11.7029 6.93756 11.55C6.96379 11.3971 7.03676 11.2562 7.14643 11.1465C7.2561 11.0368 7.39707 10.9638 7.54993 10.9376C7.70279 10.9114 7.86003 10.9332 7.99998 11L10.47 13.47L19 5.00004C19.1399 4.9332 19.2972 4.91139 19.45 4.93762C19.6029 4.96385 19.7439 5.03682 19.8535 5.14649C19.9632 5.25616 20.0362 5.39713 20.0624 5.54999C20.0886 5.70286 20.0668 5.86009 20 6.00004L11 15C10.8724 15.1455 10.6929 15.2353 10.5 15.25Z" fill="#ffffff"/> <path d="M12 21C10.3915 20.9974 8.813 20.5638 7.42891 19.7443C6.04481 18.9247 4.90566 17.7492 4.12999 16.34C3.54037 15.29 3.17596 14.1287 3.05999 12.93C2.87697 11.1721 3.2156 9.39921 4.03363 7.83249C4.85167 6.26578 6.1129 4.9746 7.65999 4.12003C8.71001 3.53041 9.87134 3.166 11.07 3.05003C12.2641 2.92157 13.4719 3.03725 14.62 3.39003C14.7224 3.4105 14.8195 3.45215 14.9049 3.51232C14.9903 3.57248 15.0622 3.64983 15.116 3.73941C15.1698 3.82898 15.2043 3.92881 15.2173 4.03249C15.2302 4.13616 15.2214 4.2414 15.1913 4.34146C15.1612 4.44152 15.1105 4.53419 15.0425 4.61352C14.9745 4.69286 14.8907 4.75712 14.7965 4.80217C14.7022 4.84723 14.5995 4.87209 14.4951 4.87516C14.3907 4.87824 14.2867 4.85946 14.19 4.82003C13.2186 4.52795 12.1987 4.43275 11.19 4.54003C10.193 4.64212 9.22694 4.94485 8.34999 5.43003C7.50512 5.89613 6.75813 6.52088 6.14999 7.27003C5.52385 8.03319 5.05628 8.91361 4.77467 9.85974C4.49307 10.8059 4.40308 11.7987 4.50999 12.78C4.61208 13.777 4.91482 14.7431 5.39999 15.62C5.86609 16.4649 6.49084 17.2119 7.23999 17.82C8.00315 18.4462 8.88357 18.9137 9.8297 19.1953C10.7758 19.4769 11.7686 19.5669 12.75 19.46C13.747 19.3579 14.713 19.0552 15.59 18.57C16.4349 18.1039 17.1818 17.4792 17.79 16.73C18.4161 15.9669 18.8837 15.0864 19.1653 14.1403C19.4469 13.1942 19.5369 12.2014 19.43 11.22C19.4201 11.1169 19.4307 11.0129 19.461 10.9139C19.4914 10.8149 19.5409 10.7228 19.6069 10.643C19.6728 10.5631 19.7538 10.497 19.8453 10.4485C19.9368 10.3999 20.0369 10.3699 20.14 10.36C20.2431 10.3502 20.3471 10.3607 20.4461 10.3911C20.5451 10.4214 20.6372 10.471 20.717 10.5369C20.7969 10.6028 20.863 10.6839 20.9115 10.7753C20.9601 10.8668 20.9901 10.9669 21 11.07C21.1821 12.829 20.842 14.6026 20.0221 16.1695C19.2022 17.7363 17.9389 19.0269 16.39 19.88C15.3288 20.4938 14.1495 20.8755 12.93 21C12.62 21 12.3 21 12 21Z" fill="#ffffff"/></g></svg>
                <span class="me-auto"><?php echo esc_html__( 'Ops! Ocorreu um erro.', 'flexify-checkout-for-woocommerce' ) ?></span>
                <button class="btn-close btn-close-white ms-2 hide-toast" type="button" aria-label="Fechar"></button>
            </div>
            <div class="toast-body"><?php echo esc_html__( 'O domínio de ativação não é permitido.', 'flexify-checkout-for-woocommerce' ) ?></div>
        </div>
        <?php
    }


    /**
     * Display not allowed product notice
     * 
     * @since 3.8.0
     * @return void
     */
    public function not_allowed_product_notice() {
        ?>
        <div class="toast toast-danger show">
            <div class="toast-header bg-danger text-white">
                <svg class="icon icon-white me-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/><g><path d="M10.5 15.25C10.307 15.2353 10.1276 15.1455 9.99998 15L6.99998 12C6.93314 11.8601 6.91133 11.7029 6.93756 11.55C6.96379 11.3971 7.03676 11.2562 7.14643 11.1465C7.2561 11.0368 7.39707 10.9638 7.54993 10.9376C7.70279 10.9114 7.86003 10.9332 7.99998 11L10.47 13.47L19 5.00004C19.1399 4.9332 19.2972 4.91139 19.45 4.93762C19.6029 4.96385 19.7439 5.03682 19.8535 5.14649C19.9632 5.25616 20.0362 5.39713 20.0624 5.54999C20.0886 5.70286 20.0668 5.86009 20 6.00004L11 15C10.8724 15.1455 10.6929 15.2353 10.5 15.25Z" fill="#ffffff"/> <path d="M12 21C10.3915 20.9974 8.813 20.5638 7.42891 19.7443C6.04481 18.9247 4.90566 17.7492 4.12999 16.34C3.54037 15.29 3.17596 14.1287 3.05999 12.93C2.87697 11.1721 3.2156 9.39921 4.03363 7.83249C4.85167 6.26578 6.1129 4.9746 7.65999 4.12003C8.71001 3.53041 9.87134 3.166 11.07 3.05003C12.2641 2.92157 13.4719 3.03725 14.62 3.39003C14.7224 3.4105 14.8195 3.45215 14.9049 3.51232C14.9903 3.57248 15.0622 3.64983 15.116 3.73941C15.1698 3.82898 15.2043 3.92881 15.2173 4.03249C15.2302 4.13616 15.2214 4.2414 15.1913 4.34146C15.1612 4.44152 15.1105 4.53419 15.0425 4.61352C14.9745 4.69286 14.8907 4.75712 14.7965 4.80217C14.7022 4.84723 14.5995 4.87209 14.4951 4.87516C14.3907 4.87824 14.2867 4.85946 14.19 4.82003C13.2186 4.52795 12.1987 4.43275 11.19 4.54003C10.193 4.64212 9.22694 4.94485 8.34999 5.43003C7.50512 5.89613 6.75813 6.52088 6.14999 7.27003C5.52385 8.03319 5.05628 8.91361 4.77467 9.85974C4.49307 10.8059 4.40308 11.7987 4.50999 12.78C4.61208 13.777 4.91482 14.7431 5.39999 15.62C5.86609 16.4649 6.49084 17.2119 7.23999 17.82C8.00315 18.4462 8.88357 18.9137 9.8297 19.1953C10.7758 19.4769 11.7686 19.5669 12.75 19.46C13.747 19.3579 14.713 19.0552 15.59 18.57C16.4349 18.1039 17.1818 17.4792 17.79 16.73C18.4161 15.9669 18.8837 15.0864 19.1653 14.1403C19.4469 13.1942 19.5369 12.2014 19.43 11.22C19.4201 11.1169 19.4307 11.0129 19.461 10.9139C19.4914 10.8149 19.5409 10.7228 19.6069 10.643C19.6728 10.5631 19.7538 10.497 19.8453 10.4485C19.9368 10.3999 20.0369 10.3699 20.14 10.36C20.2431 10.3502 20.3471 10.3607 20.4461 10.3911C20.5451 10.4214 20.6372 10.471 20.717 10.5369C20.7969 10.6028 20.863 10.6839 20.9115 10.7753C20.9601 10.8668 20.9901 10.9669 21 11.07C21.1821 12.829 20.842 14.6026 20.0221 16.1695C19.2022 17.7363 17.9389 19.0269 16.39 19.88C15.3288 20.4938 14.1495 20.8755 12.93 21C12.62 21 12.3 21 12 21Z" fill="#ffffff"/></g></svg>
                <span class="me-auto"><?php echo esc_html__( 'Ops! Ocorreu um erro.', 'flexify-checkout-for-woocommerce' ) ?></span>
                <button class="btn-close btn-close-white ms-2 hide-toast" type="button" aria-label="Fechar"></button>
            </div>
            <div class="toast-body"><?php echo esc_html__( 'A licença informada não é permitida para este produto.', 'flexify-checkout-for-woocommerce' ) ?></div>
        </div>
        <?php
    }
}