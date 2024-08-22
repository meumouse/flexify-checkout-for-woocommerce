<?php

namespace MeuMouse\Flexify_Checkout;

use WP_Error;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Class for handling custom logging
 * 
 * @since 3.8.0
 * @package MeuMouse.com
 */
class Logger {

    /**
     * The directory where logs will be stored
     * 
     * @var string
     */
    private $log_dir;

    /**
     * Logger constructor
     * 
     * @since 3.8.0
     * @return void
     */
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_dir = WP_CONTENT_DIR . '/flexify-checkout-logs/';

        // Ensure the log directory exists.
        if ( ! file_exists( $this->log_dir ) ) {
            wp_mkdir_p( $this->log_dir );
        }
    }


    /**
     * Write a log entry
     * 
     * @since 3.8.0
     * @param string $log_name | The name of the log file
     * @param string $message | The message to log
     * @return void
     */
    public function log( $log_name, $message ) {
        $file = $this->log_dir . sanitize_file_name( $log_name ) . '.log';

        // Create the message with timestamp
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message" . PHP_EOL;

        // Write the message to the log file.
        file_put_contents( $file, $log_entry, FILE_APPEND | LOCK_EX );
    }
}