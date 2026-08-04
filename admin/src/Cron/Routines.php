<?php

namespace MeuMouse\Flexify_Checkout\Cron;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\API\Updater;
use MeuMouse\Flexify_Checkout\API\MDS;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Class for handle with Cron routines
 * 
 * @since 5.0.0

 * @author MeuMouse.com
 */
class Routines {

	/**
	 * Construct function
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function __construct() {
        // When the MDS SDK is active it owns updates (signed checks + WP-Cron
        // heartbeat), so the legacy packages.meumouse.com routines are skipped.
        if ( MDS::is_enabled() ) {
            wp_clear_scheduled_hook('Flexify_Checkout/Updates/Auto_Updates');
            wp_clear_scheduled_hook('Flexify_Checkout/Updates/Check_Daily_Updates');

            return;
        }

        $timestamp = time();

        // enable auto updates
        if ( Admin_Options::get_setting('enable_auto_updates') === 'yes' ) {
            // Schedule the cron event if not already scheduled
            if ( ! wp_next_scheduled('Flexify_Checkout/Updates/Auto_Updates') ) {
                wp_schedule_event( $timestamp, 'daily', 'Flexify_Checkout/Updates/Auto_Updates' );
            }

            $updater = new Updater();

            // auto update plugin action
            add_action( 'Flexify_Checkout/Updates/Auto_Updates', array( $updater, 'auto_update_plugin' ) );
        } elseif ( wp_next_scheduled('Flexify_Checkout/Updates/Auto_Updates') ) {
            // Auto-updates were turned off: drop the previously scheduled event so
            // it doesn't keep firing without a handler attached.
            wp_clear_scheduled_hook('Flexify_Checkout/Updates/Auto_Updates');
        }

        // schedule daily updates
        if ( ! wp_next_scheduled('Flexify_Checkout/Updates/Check_Daily_Updates') ) {
            wp_schedule_event( $timestamp, 'daily', 'Flexify_Checkout/Updates/Check_Daily_Updates' );
        }

        // check daily updates
        add_action( 'Flexify_Checkout/Updates/Check_Daily_Updates', array( '\MeuMouse\Flexify_Checkout\API\Updater', 'check_daily_updates' ) );
	}
}
