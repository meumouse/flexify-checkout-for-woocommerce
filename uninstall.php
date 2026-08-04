<?php

/**
 * Flexify Checkout for WooCommerce uninstall routine.
 *
 * Runs when the plugin is deleted from the WordPress admin. Performs a
 * CONSERVATIVE cleanup: it removes only disposable data (cached class
 * registry, plugin transients) and unschedules any orphaned WP-Cron events.
 *
 * User configuration is intentionally PRESERVED so reinstalling keeps the
 * store's checkout setup:
 *   - flexify_checkout_settings
 *   - flexify_checkout_step_fields
 *   - flexify_checkout_schema_version
 *   - license options (flexify_checkout_license_*)
 *
 * @package Flexify Checkout for WooCommerce - MeuMouse.com
 * @author  MeuMouse.com
 * @since   6.0.0
 */

// Exit if not called by WordPress during uninstall.
if ( ! defined('WP_UNINSTALL_PLUGIN') ) {
    exit;
}

/**
 * Disposable options: rebuilt automatically on the next bootstrap.
 */
$disposable_options = array(
    'flexify_checkout_class_registry',
    'flexify_checkout_class_registry_version',
);

foreach ( $disposable_options as $option ) {
    delete_option( $option );
}

/**
 * Known plugin transients.
 */
$transients = array(
    'flexify_checkout_illegal_copy_check',
);

foreach ( $transients as $transient ) {
    delete_transient( $transient );
}

/**
 * Unschedule every recurring/single WP-Cron event the plugin registers, in
 * case the plugin is deleted while still active (WordPress skips the
 * deactivation hook when an active plugin is deleted directly).
 */
$scheduled_hooks = array(
    'Flexify_Checkout/Updates/Auto_Updates',
    'Flexify_Checkout/Updates/Check_Daily_Updates',
    'Flexify_Checkout/License/Check_Expires_Time',
);

foreach ( $scheduled_hooks as $hook ) {
    wp_clear_scheduled_hook( $hook );
}
