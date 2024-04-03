<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Generate hash
 * 
 * @since 2.3.0
 * @version 3.0.0
 * @param int $lenght | Lenght hash
 * @return string
 */
function generate_hash( $length ) {
    $result = '';
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $charactersLength = strlen( $characters );

    for ( $i = 0; $i < $length; $i++ ) {
        $result .= $characters[wp_rand(0, $charactersLength - 1)];
    }

    return $result;
}

    
/**
 * Check if plugin is installed
 * 
 * @since 2.3.0
 * @param string $plugin_slug | Plugin slug
 * @return bool
 */
function is_plugin_installed( $plugin_slug ) {
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $all_plugins = get_plugins();
    
    if ( !empty( $all_plugins[$plugin_slug] ) ) {
        return true;
    } else {
        return false;
    }
}


/**
 * Install plugin
 * 
 * @since 2.3.0
 * @param string | URL of plugin
 * @return object
 */
function install_plugin( $plugin_zip ) {
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    wp_cache_flush();
        
    $upgrader = new Plugin_Upgrader();
    $installed = $upgrader->install( $plugin_zip );

    return $installed;
}


/**
 * Upgrade plugin
 * 
 * @since 2.3.0
 * @param string $plugin_slug | Plugin slug
 * @return object
 */
function upgrade_plugin( $plugin_slug ) {
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    wp_cache_flush();
        
    $upgrader = new Plugin_Upgrader();
    $upgraded = $upgrader->upgrade( $plugin_slug );

    return $upgraded;
}