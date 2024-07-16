<?php

namespace MeuMouse\Flexify_Checkout\License;

// Exit if accessed directly.
defined('ABSPATH') || exit;
    
/**
 * Connect to license authentication server
 * 
 * @since 1.0.0
 * @since 3.7.0
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
    public $licenseMessage;
    public $show_message = false;
    public $active_license = false;
    public $deactive_license = false;
    public $clear_cache = false;
    public $site_not_allowed = false;
    public $product_not_allowed = false;

    /**
     * Construct function
     * 
     * @since 1.0.0
     * @since 3.7.0
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
    }


    /**
     * Get plugin instance
     * 
     * @since 1.0.0
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
     * @since 3.7.0
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
     * @since 3.7.0
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
     * @since 3.7.0
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
     * @since 3.7.0
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
     * @since 3.7.0
     * @param string $response Raw API response.
     * @return stdClass|mixed Object decoded from the JSON response or error object, if applicable.
     */
    private function process_response( $response ) {
        if ( get_option('flexify_checkout_alternative_license_activation') === 'active' ) {
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
                    update_option( 'flexify_checkout_alternative_license_activation_activation', 'yes' );

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
     * @since 3.7.0
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
            $finalData = wp_json_encode( $data );
            $url = rtrim( $this->server_host, '/' ) . "/" . ltrim( $relative_url, '/' );
    
            if ( ! empty( $this->product_key ) ) {
                $finalData = $this->encrypt( $finalData );
            }
    
            if ( function_exists('wp_remote_post') ) {
                $request_params = [
                    'method' => 'POST',
                    'sslverify' => true,
                    'timeout' => 60,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => [],
                    'body' => $finalData,
                    'cookies' => []
                ];
    
                $server_response = wp_remote_post( $url, $request_params );
    
                if ( is_wp_error( $server_response ) ) {
                    $request_params['sslverify'] = false;
                    $server_response = wp_remote_post( $url, $request_params );
    
                    if ( is_wp_error( $server_response ) ) {
                        $curl_error_message = $server_response->get_error_message();
    
                        // Check if it is a cURL 35 error
                        if ( strpos( $curl_error_message, 'cURL error 35' ) !== false ) {
                            $error = 'Erro cURL 35: Problema de comunicação SSL/TLS.';
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
                    CURLOPT_POSTFIELDS => $finalData,
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
                        $error = 'Erro cURL 35: Problema de comunicação SSL/TLS.';
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
     * @since 3.7.0
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
     * @since 3.7.0
     * @return string
     */
    private function get_key_name() {
        return hash( 'crc32b', self::get_domain() . $this->plugin_file . $this->product_id . $this->product_base . $this->product_key . "LIC" );
    }


    /**
     * Set response base option
     * 
     * @since 1.0.0
     * @since 3.7.0
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
     * @since 3.7.0
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
     * @since 3.7.0
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
     * @since 3.7.0
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
     * @since 3.7.0
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
     * @since 3.7.0
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

                if ( empty( $response->code ) ) {
                    if ( ! empty( $response->status ) ) {
                        $message = $response->msg;
                        $this->remove_response_base();

                        return true;
                    } else {
                        $message = $response->msg;
                    }
                } else {
                    $message = $response->message;
                }
            }
        } else {
            $this->remove_response_base();
            delete_transient('flexify_checkout_api_request_cache');
            delete_transient('flexify_checkout_api_response_cache');

            return true;
        }

        return false;
    }


    /**
     * Check if license is active and valid
     * 
     * @since 1.0.0
     * @since 3.7.0
     * @param string $purchase_key | License key
     * @param string $error | Error message
     * @param object $response_object | Response object
     * @return mixed string or bool
     */
    final function check_license_object( $purchase_key, &$error = '', &$response_object = null ) {
        if ( get_option('flexify_checkout_alternative_license_activation') === 'active' ) {
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
    
                            // Armazena a resposta em cache por um período de tempo
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
     * @since 3.7.0
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
     * @since 3.7.0
     * @return void
     */
    public function licenses_api_connection() {
        if ( current_user_can('manage_woocommerce') ) {
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
                  delete_option('flexify_checkout_alternative_license');
                } else {
                  update_option( 'flexify_checkout_license_status', 'invalid' );
                }
      
                if ( isset( $_POST['flexify_checkout_active_license'] ) && License::is_valid() ) {
                  $this->active_license = true;
                }
              } else {
                  if ( ! empty( $license_key ) && ! empty( $this->license_message ) ) {
                      $this->showMessage = true;
                  }
              }
            }
      
            // deactive license action
            if ( isset( $_POST['flexify_checkout_deactive_license'] ) ) {
              if ( self::deactive_license( FLEXIFY_CHECKOUT_FILE, $message ) ) {
                update_option( 'flexify_checkout_license_status', 'invalid' );
                delete_option('flexify_checkout_license_key');
                delete_option('flexify_checkout_license_response_object');
                delete_option('flexify_checkout_alternative_license_decrypted');
                delete_option('flexify_checkout_alternative_license_activation');
                delete_option('flexify_checkout_temp_license_key');
                delete_option('flexify_checkout_alternative_license');
                delete_transient('flexify_checkout_api_request_cache');
                delete_transient('flexify_checkout_api_response_cache');
                delete_transient('flexify_checkout_license_status_cached');
      
                $this->deactive_license = true;
              }
            }
      
            // clear activation cache
            if ( isset( $_POST['flexify_checkout_clear_activation_cache'] ) ) {
              delete_transient('flexify_checkout_api_request_cache');
              delete_transient('flexify_checkout_api_response_cache');
              delete_transient('flexify_checkout_license_status_cached');
      
              $this->clear_cache = true;
            }
        }
    }


    /**
     * Generate alternative activation object from decrypted license
     * 
     * @since 3.3.0
     * @since 3.7.0
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
            $this->site_not_allowed = true;

            return;
        }

        // stop if product license is not same this product
        if ( ! in_array( $license_data_array->selected_product, $allowed_products ) ) {
            $this->product_not_allowed = true;

            return;
        }

        $license_object = $license_data_array->license_object;

        if ( $this_domain === $license_data_array->site_domain ) {
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
        }
    }


    /**
     * Check if license is valid
     * 
     * @since 2.5.0
     * @version 3.7.0
     * @return bool
     */
    public static function is_valid() {
        $cached_result = get_transient('flexify_checkout_license_status_cached');

        // If the result is cached, return it
        if ( $cached_result !== false ) {
            return $cached_result;
        }

        $object_query = get_option('flexify_checkout_license_response_object');

        if ( ! empty( $object_query ) && isset( $object_query->is_valid )  ) {
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
     * @version 3.7.0
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

new License();