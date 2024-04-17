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


/**
 * Remove filter/action declared by class
 * This function can remove hooks applied by classes that were declared without an accessible variable.
 * 
 * @since 3.3.0
 * @param string $hook_name | Hook name
 * @param string $method_name | Method name
 * @param int $priority | Priority
 * @link https://wordpress.stackexchange.com/a/304861
 * @return bool
 */
function remove_filters_with_method_name( $hook_name = '', $method_name = '', $priority = 0 ) {
    global $wp_filter;

    // Take only filters on right hook name and priority
    if ( ! isset( $wp_filter[ $hook_name ][ $priority ] ) || ! is_array( $wp_filter[ $hook_name ][ $priority ] ) ) {
        return false;
    }

    // Loop on filters registered
    foreach ( (array) $wp_filter[ $hook_name ][ $priority ] as $unique_id => $filter_array ) {
        // Test if filter is an array ! (always for class/method)
        if ( isset( $filter_array['function'] ) && is_array( $filter_array['function'] ) ) {
            // Test if object is a class and method is equal to param !
            if ( is_object( $filter_array['function'][0] ) && get_class( $filter_array['function'][0] ) && $filter_array['function'][1] == $method_name ) {
                // Test for WordPress >= 4.7 WP_Hook class (https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/)
                if ( is_a( $wp_filter[ $hook_name ], 'WP_Hook' ) ) {
                    unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $unique_id ] );
                } else {
                    unset( $wp_filter[ $hook_name ][ $priority ][ $unique_id ] );
                }
            }
        }
    }

    return false;
}