<?php

namespace MeuMouse\Flexify_Checkout;

defined('ABSPATH') || exit;

/**
 * Class Error_Handler
 * Handles errors and exceptions for the plugin, deactivates the plugin on critical errors, and logs errors.
 * 
 * @since 3.8.5
 * @package MeuMouse.com
 */
class Error_Handler {

    use \MeuMouse\Flexify_Checkout\Logger;

    private $plugin_slug;
    private $log_file;
    private $is_plugin_deactivated = false;

    /**
     * Constructor to initialize the error handler.
     *
     * @since 3.8.5
     * @param string $plugin_slug The slug of the plugin to be managed
     * @return void
     */
    public function __construct( $plugin_slug ) {
        $this->plugin_slug = $plugin_slug;
        $this->log_file = FLEXIFY_CHECKOUT_PATH . 'error_log.txt';

        // Set the logger source and critical_only flag
        $this->set_logger_source( 'flexify_checkout', true );

        // Register error and exception handlers
        set_error_handler( array( $this, 'handle_error' ) );
        set_exception_handler( array( $this, 'handle_exception' ) );
        register_shutdown_function( array( $this, 'handle_shutdown' ) );
    }


    /**
     * Handles standard PHP errors.
     *
     * @since 3.8.5
     * @param int    $errno   The level of the error raised.
     * @param string $errstr  The error message.
     * @param string $errfile The filename that the error was raised in.
     * @param int    $errline The line number the error was raised at.
     * @return bool  Always returns true to prevent the default PHP error handler from running.
     */
    public function handle_error( $errno, $errstr, $errfile, $errline ) {
        // Check if the error occurred within the plugin's directory
        if ( strpos( $errfile, FLEXIFY_CHECKOUT_PATH ) !== false ) {
            $error_message = "Error: [$errno] $errstr in $errfile on line $errline";
            $this->log( $error_message, 'error' );

            // Deactivate the plugin if not already deactivated
            if ( ! $this->is_plugin_deactivated ) {
                $this->deactivate_plugin( $error_message );
            }
        }

        return true; // Prevent default PHP error handler
    }


    /**
     * Handles uncaught exceptions.
     *
     * @since 3.8.5
     * @param \Exception $exception The exception that was thrown.
     * @return void
     */
    public function handle_exception( $exception ) {
        // Check if the exception occurred within the plugin's directory
        if ( strpos( $exception->getFile(), FLEXIFY_CHECKOUT_PATH ) !== false ) {
            $error_message = 'Uncaught Exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine();
            $this->log( $error_message, 'critical' );

            // Deactivate the plugin if not already deactivated
            if ( ! $this->is_plugin_deactivated ) {
                $this->deactivate_plugin( $error_message );
            }
        }
    }


    /**
     * Handles shutdown errors.
     * Captures fatal errors that occur at the shutdown stage.
     * 
     * @since 3.8.5
     * @return void
     */
    public function handle_shutdown() {
        $last_error = error_get_last();

        if ( $last_error && ( $last_error['type'] & ( E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR ) ) ) {
            // Check if the fatal error occurred within the plugin's directory
            if ( strpos( $last_error['file'], FLEXIFY_CHECKOUT_PATH ) !== false ) {
                $error_message = 'Fatal Error: ' . $last_error['message'] . ' in ' . $last_error['file'] . ' on line ' . $last_error['line'];
                $this->log( $error_message, 'critical' );

                // Deactivate the plugin if not already deactivated
                if ( ! $this->is_plugin_deactivated ) {
                    $this->deactivate_plugin( $error_message );
                }
            }
        }
    }

    
    /**
     * Deactivates the plugin and displays an admin notice.
     * 
     * @since 3.8.5
     * @param string $error | Error message
     * @return void
     */
    private function deactivate_plugin( $error = '' ) {
        // Deactivate the plugin
        deactivate_plugins( FLEXIFY_CHECKOUT_BASENAME );

        // Set flag to true to prevent multiple deactivations
        $this->is_plugin_deactivated = true;

        // Display an admin notice or log that the plugin was deactivated
        add_action( 'admin_notices', function () use ( $error ) {
            $class = 'notice notice-error is-dismissible';
            $message = sprintf( __( '<strong>Flexify Checkout para WooCommerce</strong> foi desativado devido a um erro crítico: %s - Verifique o arquivo de logs para mais detalhes.', 'flexify-checkout-for-woocommerce' ), esc_html( $error ) );

            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
        });
    }
}

new Error_Handler('flexify-checkout-for-woocommerce/flexify-checkout-for-woocommerce.php');