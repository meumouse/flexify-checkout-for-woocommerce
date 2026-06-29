<?php

namespace MeuMouse\Flexify_Checkout\Core\Logs;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Dedicated file logger for Flexify Checkout.
 *
 * Writes structured (JSON per line) entries to a protected directory under the
 * WordPress uploads folder, so the admin can review requests, payments, errors
 * and webhook events from a single place. Each entry carries a timestamp, a
 * severity level, a category and an optional context payload.
 *
 * Activation rule: error-tier levels (emergency/alert/critical/error) are always
 * persisted so problems are captured even with debug mode off; lower levels
 * (warning/notice/info/debug) are only written when debug mode is enabled.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Core\Logs
 * @author MeuMouse.com
 */
class Logger {

    /**
     * Sub-directory (under uploads) that stores the log files.
     *
     * @since 6.0.0
     * @var string
     */
    const LOG_DIRNAME = 'flexify-checkout-logs';

    /**
     * Maximum size, in bytes, a single daily file may reach before it is
     * rotated to a ".1" sibling (kept so the modal can still read recent
     * history without loading an unbounded file).
     *
     * @since 6.0.0
     * @var int
     */
    const MAX_FILE_BYTES = 5242880; // 5 MB.

    /**
     * Number of days a log file is retained before automatic cleanup.
     *
     * @since 6.0.0
     * @var int
     */
    const RETENTION_DAYS = 30;

    /**
     * Severity levels considered "errors" — always persisted regardless of the
     * debug mode toggle.
     *
     * @since 6.0.0
     * @var array<int,string>
     */
    const ERROR_LEVELS = array( 'emergency', 'alert', 'critical', 'error' );

    /**
     * Known categories used by the bundled instrumentation. Free-form strings
     * are accepted too; these are only used to seed the modal filter.
     *
     * @since 6.0.0
     * @var array<int,string>
     */
    const CATEGORIES = array( 'request', 'payment', 'error', 'webhook', 'license', 'general' );

    /**
     * Cached absolute path to the log directory.
     *
     * @since 6.0.0
     * @var string|null
     */
    protected static $log_dir = null;

    /**
     * Whether the daily cleanup sweep already ran this request.
     *
     * @since 6.0.0
     * @var bool
     */
    protected static $cleaned = false;


    /**
     * Resolve the absolute path to the (protected) log directory, creating it
     * with hardening files on first use.
     *
     * @since 6.0.0
     * @return string Trailing-slashed directory path, or '' when uploads is unavailable.
     */
    public static function get_log_dir() {
        if ( self::$log_dir !== null ) {
            return self::$log_dir;
        }

        $uploads = wp_upload_dir();

        if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
            self::$log_dir = '';

            return self::$log_dir;
        }

        $dir = trailingslashit( $uploads['basedir'] ) . self::LOG_DIRNAME . '/';

        if ( ! is_dir( $dir ) ) {
            wp_mkdir_p( $dir );
        }

        // Harden the directory against direct browsing/download.
        if ( is_dir( $dir ) ) {
            if ( ! file_exists( $dir . '.htaccess' ) ) {
                @file_put_contents( $dir . '.htaccess', "Order deny,allow\nDeny from all\n" );
            }

            if ( ! file_exists( $dir . 'index.html' ) ) {
                @file_put_contents( $dir . 'index.html', '' );
            }
        }

        self::$log_dir = $dir;

        return self::$log_dir;
    }


    /**
     * Per-site hash suffix used to make log filenames unguessable.
     *
     * @since 6.0.0
     * @return string
     */
    protected static function file_hash() {
        return substr( wp_hash( 'flexify-checkout-logs' ), 0, 12 );
    }


    /**
     * Absolute path to the active (today's) log file.
     *
     * @since 6.0.0
     * @return string Path, or '' when the directory is unavailable.
     */
    public static function get_active_file() {
        $dir = self::get_log_dir();

        if ( $dir === '' ) {
            return '';
        }

        $date = function_exists('current_time') ? current_time('Y-m-d') : gmdate('Y-m-d');

        return $dir . sprintf( 'flexify-%s-%s.log', $date, self::file_hash() );
    }


    /**
     * Decide whether a given level should be written under the current settings.
     *
     * @since 6.0.0
     * @param string $level Severity level.
     * @return bool
     */
    protected static function should_write( $level ) {
        if ( in_array( $level, self::ERROR_LEVELS, true ) ) {
            return true;
        }

        return function_exists('flexify_checkout_is_debug') && flexify_checkout_is_debug();
    }


    /**
     * Write a log entry.
     *
     * @since 6.0.0
     * @param string $level Severity: emergency|alert|critical|error|warning|notice|info|debug.
     * @param string $category Logical group (e.g. request, payment, error, webhook).
     * @param string $message Human-readable message.
     * @param array<string,mixed> $context Optional structured context.
     * @return bool True when the entry was written.
     */
    public static function log( $level, $category, $message, array $context = array() ) {
        $level = strtolower( (string) $level );
        $category = sanitize_key( (string) $category ) ?: 'general';

        if ( ! self::should_write( $level ) ) {
            return false;
        }

        $file = self::get_active_file();

        if ( $file === '' ) {
            return false;
        }

        self::maybe_cleanup();
        self::maybe_rotate( $file );

        $entry = array(
            'time' => function_exists('current_time') ? current_time('mysql') : gmdate('Y-m-d H:i:s'),
            'ts' => microtime( true ),
            'level' => $level,
            'category' => $category,
            'message' => is_string( $message ) ? $message : wp_json_encode( $message ),
            'context' => self::sanitize_context( $context ),
        );

        $line = wp_json_encode( $entry );

        if ( false === $line ) {
            return false;
        }

        return (bool) @file_put_contents( $file, $line . "\n", FILE_APPEND | LOCK_EX );
    }


    /**
     * Convenience writer for the error level (always persisted).
     *
     * @since 6.0.0
     * @param string $category Category.
     * @param string $message Message.
     * @param array<string,mixed> $context Context.
     * @return bool
     */
    public static function error( $category, $message, array $context = array() ) {
        return self::log( 'error', $category, $message, $context );
    }


    /**
     * Convenience writer for the warning level (debug-gated).
     *
     * @since 6.0.0
     * @param string $category Category.
     * @param string $message Message.
     * @param array<string,mixed> $context Context.
     * @return bool
     */
    public static function warning( $category, $message, array $context = array() ) {
        return self::log( 'warning', $category, $message, $context );
    }


    /**
     * Convenience writer for the info level (debug-gated).
     *
     * @since 6.0.0
     * @param string $category Category.
     * @param string $message Message.
     * @param array<string,mixed> $context Context.
     * @return bool
     */
    public static function info( $category, $message, array $context = array() ) {
        return self::log( 'info', $category, $message, $context );
    }


    /**
     * Convenience writer for the debug level (debug-gated).
     *
     * @since 6.0.0
     * @param string $category Category.
     * @param string $message Message.
     * @param array<string,mixed> $context Context.
     * @return bool
     */
    public static function debug( $category, $message, array $context = array() ) {
        return self::log( 'debug', $category, $message, $context );
    }


    /**
     * Log a caught Throwable with file/line/trace context.
     *
     * @since 6.0.0
     * @param \Throwable $e Exception or error.
     * @param string $category Category, defaults to "error".
     * @param array<string,mixed> $context Extra context merged into the entry.
     * @return bool
     */
    public static function log_exception( $e, $category = 'error', array $context = array() ) {
        if ( ! ( $e instanceof \Throwable ) ) {
            return false;
        }

        $context = array_merge( array(
            'exception' => get_class( $e ),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ), $context );

        return self::log( 'error', $category, $e->getMessage(), $context );
    }


    /**
     * Sanitize the context payload so it is always JSON-encodable and bounded.
     *
     * @since 6.0.0
     * @param array<string,mixed> $context Raw context.
     * @return array<string,mixed>
     */
    protected static function sanitize_context( array $context ) {
        if ( empty( $context ) ) {
            return array();
        }

        // Re-encode through JSON to drop resources/closures and cap the depth.
        $encoded = wp_json_encode( $context, 0, 6 );

        if ( false === $encoded ) {
            return array( '_note' => 'context not serializable' );
        }

        $decoded = json_decode( $encoded, true );

        return is_array( $decoded ) ? $decoded : array();
    }


    /**
     * Rotate the active file to a ".1" sibling once it crosses the size cap.
     *
     * @since 6.0.0
     * @param string $file Active file path.
     * @return void
     */
    protected static function maybe_rotate( $file ) {
        if ( ! file_exists( $file ) ) {
            return;
        }

        if ( filesize( $file ) < self::MAX_FILE_BYTES ) {
            return;
        }

        $rotated = $file . '.1';

        if ( file_exists( $rotated ) ) {
            @unlink( $rotated );
        }

        @rename( $file, $rotated );
    }


    /**
     * Remove log files older than the retention window. Runs at most once per
     * request and is cheap (a single glob + mtime check).
     *
     * @since 6.0.0
     * @return void
     */
    protected static function maybe_cleanup() {
        if ( self::$cleaned ) {
            return;
        }

        self::$cleaned = true;

        $dir = self::get_log_dir();

        if ( $dir === '' ) {
            return;
        }

        $files = glob( $dir . 'flexify-*.log*' );

        if ( ! is_array( $files ) ) {
            return;
        }

        $threshold = time() - ( self::RETENTION_DAYS * DAY_IN_SECONDS );

        foreach ( $files as $file ) {
            if ( is_file( $file ) && filemtime( $file ) < $threshold ) {
                @unlink( $file );
            }
        }
    }


    /**
     * List the available log files, newest first.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>> Each: { name, size, modified }.
     */
    public static function get_files() {
        $dir = self::get_log_dir();

        if ( $dir === '' ) {
            return array();
        }

        $files = glob( $dir . 'flexify-*.log*' );

        if ( ! is_array( $files ) ) {
            return array();
        }

        // Newest first by modification time.
        usort( $files, static function ( $a, $b ) {
            return filemtime( $b ) <=> filemtime( $a );
        } );

        return array_map( static function ( $file ) {
            return array(
                'name' => basename( $file ),
                'size' => filesize( $file ),
                'modified' => gmdate( 'c', filemtime( $file ) ),
            );
        }, $files );
    }


    /**
     * Read and parse log entries, newest first, with optional filtering.
     *
     * @since 6.0.0
     * @param array<string,mixed> $args {
     *     @type string $level    Filter by exact level.
     *     @type string $category Filter by exact category.
     *     @type string $search   Case-insensitive substring match on the message.
     *     @type int    $page     1-based page number.
     *     @type int    $per_page Entries per page (capped at 200).
     *     @type int    $scan_max Maximum raw lines to scan across files.
     * }
     * @return array<string,mixed> { entries, total, page, per_page, categories, levels }
     */
    public static function get_entries( array $args = array() ) {
        $defaults = array(
            'level' => '',
            'category' => '',
            'search' => '',
            'page' => 1,
            'per_page' => 50,
            'scan_max' => 5000,
        );

        $args = array_merge( $defaults, $args );
        $args['page'] = max( 1, (int) $args['page'] );
        $args['per_page'] = min( 200, max( 1, (int) $args['per_page'] ) );
        $args['scan_max'] = max( 100, (int) $args['scan_max'] );

        $all = self::read_raw_entries( $args['scan_max'] );

        // Build the filter facets from the unfiltered set.
        $categories = array();
        $levels = array();

        foreach ( $all as $entry ) {
            if ( ! empty( $entry['category'] ) ) {
                $categories[ $entry['category'] ] = true;
            }

            if ( ! empty( $entry['level'] ) ) {
                $levels[ $entry['level'] ] = true;
            }
        }

        $search = strtolower( trim( (string) $args['search'] ) );

        $filtered = array_values( array_filter( $all, static function ( $entry ) use ( $args, $search ) {
            if ( $args['level'] !== '' && ( $entry['level'] ?? '' ) !== $args['level'] ) {
                return false;
            }

            if ( $args['category'] !== '' && ( $entry['category'] ?? '' ) !== $args['category'] ) {
                return false;
            }

            if ( $search !== '' ) {
                $haystack = strtolower( (string) ( $entry['message'] ?? '' ) );

                if ( strpos( $haystack, $search ) === false ) {
                    return false;
                }
            }

            return true;
        } ) );

        $total = count( $filtered );
        $offset = ( $args['page'] - 1 ) * $args['per_page'];
        $page_entries = array_slice( $filtered, $offset, $args['per_page'] );

        return array(
            'entries' => $page_entries,
            'total' => $total,
            'page' => $args['page'],
            'per_page' => $args['per_page'],
            'categories' => array_keys( $categories ),
            'levels' => array_keys( $levels ),
        );
    }


    /**
     * Read raw parsed entries from the log files, newest first.
     *
     * Files are read most-recent first; within a file, lines are reversed so the
     * newest line comes first. Scanning stops once $scan_max lines are gathered.
     *
     * @since 6.0.0
     * @param int $scan_max Maximum number of lines to parse.
     * @return array<int,array<string,mixed>>
     */
    protected static function read_raw_entries( $scan_max ) {
        $files = self::get_files();
        $entries = array();
        $count = 0;

        $dir = self::get_log_dir();

        foreach ( $files as $meta ) {
            $path = $dir . $meta['name'];

            if ( ! is_readable( $path ) ) {
                continue;
            }

            $contents = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

            if ( ! is_array( $contents ) ) {
                continue;
            }

            $contents = array_reverse( $contents );

            foreach ( $contents as $line ) {
                if ( $count >= $scan_max ) {
                    break 2;
                }

                $decoded = json_decode( $line, true );

                if ( ! is_array( $decoded ) ) {
                    // Tolerate non-JSON lines (e.g. legacy error_log output).
                    $decoded = array(
                        'time' => '',
                        'level' => 'info',
                        'category' => 'general',
                        'message' => $line,
                        'context' => array(),
                    );
                }

                $entries[] = $decoded;
                $count++;
            }
        }

        return $entries;
    }


    /**
     * Return the concatenated raw text of every log file, newest first.
     *
     * Used by the download endpoint so the admin gets a single .log file.
     *
     * @since 6.0.0
     * @return string
     */
    public static function get_raw_contents() {
        $files = self::get_files();
        $dir = self::get_log_dir();
        $chunks = array();

        foreach ( $files as $meta ) {
            $path = $dir . $meta['name'];

            if ( is_readable( $path ) ) {
                $chunks[] = "===== {$meta['name']} =====\n" . (string) file_get_contents( $path );
            }
        }

        return implode( "\n", $chunks );
    }


    /**
     * Delete every log file in the directory.
     *
     * @since 6.0.0
     * @return int Number of files removed.
     */
    public static function clear() {
        $dir = self::get_log_dir();

        if ( $dir === '' ) {
            return 0;
        }

        $files = glob( $dir . 'flexify-*.log*' );

        if ( ! is_array( $files ) ) {
            return 0;
        }

        $removed = 0;

        foreach ( $files as $file ) {
            if ( is_file( $file ) && @unlink( $file ) ) {
                $removed++;
            }
        }

        return $removed;
    }
}
