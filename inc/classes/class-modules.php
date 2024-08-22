<?php

namespace MeuMouse\Flexify_Checkout;

use WP_Error;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Class for handling the installation of external modules (plugins)
 * 
 * @since 3.8.0
 * @package MeuMouse.com
 */
class Modules {

    /**
     * Logger instance.
     * 
     * @var Logger
     */
    private $logger;

    /**
     * Enable or disable logging.
     * 
     * @var bool
     */
    private $logging_enabled;

    /**
     * Construct function
     * 
     * @since 3.8.0
     * @return void
     */
    public function __construct() {
        // Set logging enabled or disabled
        $this->logging_enabled = true;

        // Initialize logger if logging is enabled
        if ( $this->logging_enabled ) {
            $this->logger = new Logger();
        }

        // Handle AJAX requests for installing external modules.
        add_action( 'wp_ajax_install_modules_action', array( $this, 'install_modules_ajax_callback' ) );

        // Activate plugin
        add_action( 'wp_ajax_activate_plugin_action', array( $this, 'activate_plugin_callback' ) );
    }

    /**
     * Log a message if logging is enabled.
     * 
     * @since 3.8.0
     * @param string $category
     * @param string $message
     * @return void
     */
    private function log( $category, $message ) {
        if ( $this->logging_enabled && $this->logger ) {
            $this->logger->log( $category, $message );
        }
    }


    /**
     * Handle AJAX request to install external plugins
     * 
     * @since 3.8.0
     * @return void
     */
    public function install_modules_ajax_callback() {
        if ( isset( $_POST['plugin_url'] ) && isset( $_POST['plugin_slug'] ) ) {
            $plugin_slug = sanitize_text_field( $_POST['plugin_slug'] );
            $plugin_zip = esc_url_raw( $_POST['plugin_url'] );

            // Capture any output to avoid HTML mixed with JSON
            ob_start();

            // Log the start of the installation process
            $this->log('plugin_installation', "Iniciando a instalação do plugin: $plugin_slug");

            // Check if the plugin is already installed
            if ( $this->is_plugin_installed( $plugin_slug ) ) {
                $this->log('plugin_installation', "Plugin já está instalado: $plugin_slug");
                
                // If the plugin is installed, try to update it
                $installed = $this->upgrade_plugin( $plugin_slug );
            } else {
                // If the plugin is not installed, try to install it
                $installed = $this->install_plugin( $plugin_zip );
            }

            // Clear any output to avoid HTML mixed with JSON
            ob_end_clean();

            if ( ! is_wp_error( $installed ) && $installed ) {
                $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_slug;
                $this->log('plugin_installation', "Tentando ativar o plugin: $plugin_slug no caminho " . $plugin_file );
                $activate = activate_plugin( $plugin_file );
            
                if ( ! is_wp_error( $activate ) ) {
                    $this->log('plugin_installation', "Plugin ativado com sucesso: $plugin_slug");
                    $response = array(
                        'status'  => 'success',
                        'toast_header' => esc_html__( 'Plugin instalado e ativado.', 'flexify-checkout-for-woocommerce' ),
                        'toast_body' => esc_html__( 'Plugin instalado e ativado com sucesso.', 'flexify-checkout-for-woocommerce' ),
                    );
                } else {
                    $this->log('plugin_installation', "Erro na ativação do plugin: $plugin_slug - " . $activate->get_error_message());
                    $this->log('plugin_installation', "Detalhes do erro na ativação: " . print_r( $activate, true ) );

                    $response = array(
                        'status'  => 'error',
                        'toast_header' => esc_html__( 'Falha ao ativar o plugin.', 'flexify-checkout-for-woocommerce' ),
                        'toast_body' => esc_html__( 'O plugin foi instalado, mas não pôde ser ativado.', 'flexify-checkout-for-woocommerce' ),
                    );
                }
            } else {
                $this->log('plugin_installation', "Falha na instalação/atualização do plugin: $plugin_slug");

                $response = array(
                    'status'  => 'error',
                    'toast_header' => esc_html__( 'Falha ao instalar/atualizar o plugin.', 'flexify-checkout-for-woocommerce' ),
                    'toast_body' => esc_html__( 'Ocorreu um erro ao tentar instalar ou atualizar o plugin.', 'flexify-checkout-for-woocommerce' ),
                );
            }

            // Send JSON response
            wp_send_json( $response );
        }
    }


    /**
     * Check if plugin is installed
     * 
     * @since 3.8.0
     * @param string $plugin_slug | Plugin slug
     * @return bool
     */
    public function is_plugin_installed( $plugin_slug ) {
        if ( ! function_exists('get_plugins') ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        return ! empty( $all_plugins[$plugin_slug] );
    }
    

    /**
     * Install plugin
     * 
     * @since 3.8.0
     * @param string $plugin_zip | URL of plugin
     * @return object|bool
     */
    public function install_plugin( $plugin_zip ) {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        wp_cache_flush();
        
        $upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
        $installed = $upgrader->install( $plugin_zip );

        if ( is_wp_error( $installed ) ) {
            $this->logger->log('plugin_installation', "Erro ao instalar o plugin: " . $installed->get_error_message() );
        } else {
            $this->logger->log('plugin_installation', "Plugin instalado: $plugin_zip");
        }

        return $installed;
    }


    /**
     * Upgrade plugin
     * 
     * @since 3.8.0
     * @param string $plugin_slug | Plugin slug
     * @return object|bool
     */
    public function upgrade_plugin( $plugin_slug ) {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        wp_cache_flush();
        
        $upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
        $upgraded = $upgrader->upgrade( $plugin_slug );

        if ( is_wp_error( $upgraded ) ) {
            $this->logger->log('plugin_installation', "Erro ao atualizar o plugin: " . $upgraded->get_error_message());
        } else {
            $this->logger->log('plugin_installation', "Plugin atualizado: $plugin_slug");
        }

        return $upgraded;
    }


    /**
     * Activate plugin when is installed
     * 
     * @since 3.8.0
     * @return void
     */
    public function activate_plugin_callback() {
        if ( isset( $_POST['plugin_slug'] ) ) {
            $plugin_slug = sanitize_text_field( $_POST['plugin_slug'] );
            $activate = activate_plugin( $plugin_slug );
    
            if ( is_wp_error( $activate ) ) {
                $response = array(
                    'status'  => 'error',
                    'toast_header' => esc_html__( 'Ops! Ocorreu um erro.', 'flexify-checkout-for-woocommerce' ),
                    'toast_body' => $activate->get_error_message(),
                );
            } else {
                $response = array(
                    'status'  => 'success',
                    'toast_header' => esc_html__( 'Plugin ativado com sucesso.', 'flexify-checkout-for-woocommerce' ),
                    'toast_body' => esc_html__( 'Novo recurso adicionado!', 'flexify-checkout-for-woocommerce' ),
                );
            }

            wp_send_json( $response );
        }
    }
}

new Modules();