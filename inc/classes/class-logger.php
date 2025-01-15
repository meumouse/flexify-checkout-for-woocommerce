<?php

namespace MeuMouse\Flexify_Checkout;

defined('ABSPATH') || exit;

/**
 * Trait Logger
 *
 * Provides logging functionality for classes that use it.
 * Allows setting a source for logs and optionally logs only critical events.
 * 
 * @since 3.8.6
 * @package MeuMouse.com
 */
trait Logger {
  
  /**
   * The source identifier for the log entries.
   *
   * @var string
   */
  public $source;

  /**
   * Flag to determine if only critical logs should be saved.
   *
   * @var bool
   */
  public $critical_only;

  /**
   * WooCommerce logger instance.
   *
   * @var WC_Logger
   */
  public static $log;

  
  /**
   * Set the source for the logger and whether to log only critical events.
   *
   * @since 3.8.6
   * @param string $set | The source identifier for the logs.
   * @param bool $critical_only | Whether to log only critical events, default true.
   * @return void
   */
  public function set_logger_source( $set, $critical_only = true ) {
    $this->source = $set;
    $this->critical_only = $critical_only;
  }


  /**
   * Log an event.
   *
   * Logs a message with the given severity level. If $critical_only is true,
   * only logs messages with levels 'emergency', 'alert', or 'critical'.
   *
   * @since 3.8.0
   * @version 3.8.6
   * @param string $message | The log message.
   * @param string $level | Optional, defaults to 'info'. Valid levels: emergency|alert|critical|error|warning|notice|info|debug.
   * @return void
   */
  public function log( $message, $level = 'info' ) {
    if ( ! $this->source ) {
      return;
    }

    if ( $this->critical_only && ! in_array( $level, array( 'emergency', 'alert', 'critical' ) ) ) {
      return;
    }

    $message = is_string( $message ) ? $message : print_r( $message, true );

    if ( ! isset( self::$log ) ) {
      self::$log = wc_get_logger();
    }

    self::$log->log( $level, $message, array( 'source' => $this->source ) );
  }
}