<?php

namespace MeuMouse\Flexify_Checkout\Core;

use WP_Error;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Class for handling the installation of external modules (plugins)
 * 
 * @since 3.8.0
 * @version 5.3.3

 * @author MeuMouse.com
 */
class Modules {

    use Logger;

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
     * @version 5.2.0
     * @return void
     */
    public function __construct() {
        $this->set_logger_source( 'flexify-checkout-modules', false );

        // Set logging enabled or disabled
        $this->logging_enabled = false;
    }


    /**
     * Log a message if logging is enabled.
     * 
     * @since 3.8.0
     * @version 3.8.6
     * @param string $message
     * @param string $level
     * @return void
     */
    private function log( $message, $level = 'info' ) {
        if ( $this->logging_enabled ) {
            $this->log( $message, $level );
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
            $this->log('plugin_installation', "Erro ao instalar o plugin: " . $installed->get_error_message() );
        } else {
            $this->log('plugin_installation', "Plugin instalado: $plugin_zip");
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
            $this->log('plugin_installation', "Erro ao atualizar o plugin: " . $upgraded->get_error_message());
        } else {
            $this->log('plugin_installation', "Plugin atualizado: $plugin_slug");
        }

        return $upgraded;
    }


}
