<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Cron;

use WP_CLI;
use function get_post_meta;
use function get_post_modified_time;
use function get_post_status;
use function get_post_time;
use function get_posts;
use function sanitize_text_field;
use function WP_CLI\Utils\format_items;
use function WP_CLI\Utils\get_flag_value;
use function strtolower;
use function trim;
use function implode;
use function untrailingslashit;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Registers WP-CLI commands to execute the PHP scheduler.
 *
 * @since 1.3.2
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Cron
 * @author MeuMouse.com
 */
class WP_CLI_Command {

    /**
     * Construct function
     * 
     * @since 1.3.2
     * @return void
     */
    public function __construct() {
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            WP_CLI::add_command( 'fcrc scheduler', array( $this, 'run_scheduler' ) );
            WP_CLI::add_command( 'fcrc cart-list', array( $this, 'list_carts' ) );
        }
    }

    /**
     * Execute pending jobs using the configured scheduler.
     *
     * ## OPTIONS
     *
     * [--loop]
     * : Mantém o processo ativo, executando o agendador continuamente no intervalo configurado.
     *
     * [--max-runs=<number>]
     * : Limita a quantidade de ciclos quando utilizado com `--loop`.
     *
     * [--print-cron]
     * : Exibe uma linha de cron já formatada com o caminho do WordPress e encerra a execução.
     *
     * @since 1.3.2
     * @param array $args | Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function run_scheduler( $args, $assoc_args ) {
        if ( ! Scheduler_Manager::is_php_cron_enabled() ) {
            WP_CLI::warning( 'PHP Cron is not enabled in the plugin settings.' );
            return;
        }

        $print_cron = (bool) get_flag_value( $assoc_args, 'print-cron', false );

        if ( $print_cron ) {
            $cron_entry = $this->build_cron_example();

            WP_CLI::log( 'Add the following line to your system crontab (crontab -e):' );
            WP_CLI::log( $cron_entry );

            return;
        }

        $scheduler = Scheduler_Manager::build_scheduler();

        $scheduler->call( function() {
            Queue_Processor::dispatch_due_events();
        })->everyMinute();

        $scheduler->call( function() {
            do_action('fcrc_delete_old_anonymous_carts');
        })->hourly();

        $interval = Scheduler_Manager::get_php_cron_interval_seconds();
        $loop = (bool) get_flag_value( $assoc_args, 'loop', false );
        $max_runs = (int) get_flag_value( $assoc_args, 'max-runs', 0 );

        if ( ! $loop ) {
            WP_CLI::log( sprintf( 'Run this command every %d seconds using a system cron or as a long-lived process.', $interval ) );
            WP_CLI::log( 'Use "wp fcrc scheduler --loop" to keep the process alive or "wp fcrc scheduler --print-cron" for a cron example.' );
        }

        if ( $loop ) {
            if ( function_exists('ignore_user_abort') ) {
                ignore_user_abort( true );
            }

            if ( function_exists('set_time_limit') ) {
                set_time_limit( 0 );
            }

            WP_CLI::log( sprintf( 'Running scheduler loop every %d seconds.', $interval ) );
        }

        $runs = 0;

        do {
            $cycle_start = microtime( true );

            $scheduler->run();
            $runs++;

            if ( ! $loop ) {
                break;
            }

            if ( $max_runs > 0 && $runs >= $max_runs ) {
                break;
            }

            $elapsed = microtime( true ) - $cycle_start;
            $sleep_for = $interval - $elapsed;

            if ( $sleep_for > 0 ) {
                usleep( (int) round( $sleep_for * 1000000 ) );
            }
        } while ( true );

        if ( $loop && $max_runs > 0 ) {
            WP_CLI::success( sprintf( 'Scheduler loop finished after %d runs.', $runs ) );
        } elseif ( $loop ) {
            WP_CLI::success( 'Scheduler loop stopped.' );
        } else {
            WP_CLI::success( 'Scheduler executed successfully.' );
        }
    }


    /**
     * Build a sample cron line to execute the scheduler via system cron.
     *
     * @since 1.3.2
     * @return string
     */
    protected function build_cron_example() {
        $path = defined('ABSPATH') ? untrailingslashit( ABSPATH ) : getcwd();
        $path = $path ? $path : '.';

        $quoted_path = escapeshellarg( $path );

        $command = sprintf(
            'cd %1$s && wp --path=%1$s fcrc scheduler --quiet >> %1$s/wp-content/uploads/fcrc-scheduler.log 2>&1',
            $quoted_path
        );

        $cron_interval = Scheduler_Manager::get_php_cron_interval_seconds();
        $expression = $cron_interval >= MINUTE_IN_SECONDS ? '* * * * *' : '*/1 * * * *';

        if ( $cron_interval > MINUTE_IN_SECONDS && $cron_interval % MINUTE_IN_SECONDS === 0 ) {
            $minutes = (int) ( $cron_interval / MINUTE_IN_SECONDS );
            $expression = sprintf( '*/%d * * * *', $minutes );
        }

        return sprintf( '%s %s', $expression, $command );
    }


    /**
     * List recovery carts filtered by status.
     *
     * ## OPTIONS
     *
     * <status>
     * : Cart status to filter by. Accepts lead, shopping, abandoned, order_abandoned,
     * recovered, lost, purchased or all. "losted" is kept as an alias of "lost" for
     * backward compatibility.
     *
     * [--limit=<number>]
     * : Maximum number of carts to return. Use 0 for no limit. Default: 20.
     *
     * [--fields=<fields>]
     * : Comma-separated list of fields to display. Available fields: id, status,
     * customer, email, phone, total, created_at, updated_at. Default: all fields.
     *
     * [--format=<format>]
     * : Render output in a particular format. See WP-CLI docs for details.
     * Default: table.
     *
     * [--search=<term>]
     * : Search term to match against cart titles and content.
     *
     * [--orderby=<orderby>]
     * : Field to order results by. Accepts ID, date, title or modified. Default: date.
     *
     * [--order=<order>]
     * : Order direction. Accepts ASC or DESC. Default: DESC.
     *
     * ## EXAMPLES
     *
     *     wp fcrc cart-list abandoned
     *     wp fcrc cart-list recovered --limit=50 --fields=id,status,email,total --format=csv
     *
     * @since 1.3.2
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function list_carts( $args, $assoc_args ) {
        if ( empty( $args ) ) {
            WP_CLI::error( 'You must provide a cart status. Use "all" to retrieve every status.' );
        }

        $status = $this->normalize_status( $args[0] );
        $allowed_statuses = $this->get_allowed_statuses();

        if ( 'all' !== $status && ! in_array( $status, $allowed_statuses, true ) ) {
            WP_CLI::error( sprintf( 'Invalid status "%s". Allowed statuses: %s or "all".', $status, implode( ', ', $allowed_statuses ) ) );
        }

        $limit = isset( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : 20;
        $limit = $limit > 0 ? $limit : -1;

        $orderby = isset( $assoc_args['orderby'] ) ? $this->sanitize_orderby( $assoc_args['orderby'] ) : 'date';
        $order   = isset( $assoc_args['order'] ) ? $this->sanitize_order( $assoc_args['order'] ) : 'DESC';

        $query_args = array(
            'post_type'      => 'fc-recovery-carts',
            'post_status'    => 'all' === $status ? $allowed_statuses : $status,
            'posts_per_page' => $limit,
            'orderby'        => $orderby,
            'order'          => $order,
            'fields'         => 'ids',
        );

        if ( isset( $assoc_args['search'] ) && $assoc_args['search'] !== '' ) {
            $query_args['s'] = sanitize_text_field( $assoc_args['search'] );
        }

        $cart_ids = get_posts( $query_args );

        if ( empty( $cart_ids ) ) {
            $status_label = 'all' === $status ? 'any status' : sprintf( 'status "%s"', $status );
            WP_CLI::warning( sprintf( 'No recovery carts found for %s.', $status_label ) );
            return;
        }

        $items = array();

        foreach ( $cart_ids as $cart_id ) {
            $items[] = array(
                'id'         => $cart_id,
                'status'     => get_post_status( $cart_id ),
                'customer'   => get_post_meta( $cart_id, '_fcrc_full_name', true ),
                'email'      => get_post_meta( $cart_id, '_fcrc_cart_email', true ),
                'phone'      => get_post_meta( $cart_id, '_fcrc_cart_phone', true ),
                'total'      => $this->format_cart_total( get_post_meta( $cart_id, '_fcrc_cart_total', true ) ),
                'created_at' => get_post_time( 'Y-m-d H:i:s', true, $cart_id ),
                'updated_at' => get_post_modified_time( 'Y-m-d H:i:s', true, $cart_id ),
            );
        }

        $default_fields = array( 'id', 'status', 'customer', 'email', 'phone', 'total', 'created_at', 'updated_at' );
        $fields = isset( $assoc_args['fields'] ) ? array_map( 'trim', explode( ',', $assoc_args['fields'] ) ) : $default_fields;
        $fields = array_values( array_intersect( $fields, $default_fields ) );

        if ( empty( $fields ) ) {
            WP_CLI::error( 'No valid fields provided. Available fields: ' . implode( ', ', $default_fields ) . '.' );
        }

        $format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

        format_items( $format, $items, $fields );
    }


    /**
     * Normalize status aliases.
     *
     * @since 1.3.2
     * @param string $status Raw status input.
     * @return string
     */
    private function normalize_status( $status ) {
        $status = strtolower( trim( $status ) );

        $aliases = array(
            'losted' => 'lost',
        );

        return $aliases[ $status ] ?? $status;
    }


    /**
     * Retrieve available cart statuses.
     *
     * @since 1.3.2
     * @return array
     */
    private function get_allowed_statuses() {
        return array( 'lead', 'shopping', 'abandoned', 'order_abandoned', 'recovered', 'lost', 'purchased' );
    }


    /**
     * Sanitize orderby parameter.
     *
     * @since 1.3.2
     * @param string $orderby Raw orderby.
     * @return string
     */
    private function sanitize_orderby( $orderby ) {
        $orderby = strtolower( trim( $orderby ) );
        $allowed = array( 'id', 'date', 'title', 'modified' );

        return in_array( $orderby, $allowed, true ) ? $orderby : 'date';
    }


    /**
     * Sanitize order parameter.
     *
     * @since 1.3.2
     * @param string $order Raw order.
     * @return string
     */
    private function sanitize_order( $order ) {
        $order = strtoupper( trim( $order ) );

        return in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';
    }


    /**
     * Format cart total for CLI output.
     *
     * @since 1.3.2
     * @param mixed $total Cart total meta value.
     * @return string
     */
    private function format_cart_total( $total ) {
        if ( '' === $total || null === $total ) {
            return '0.00';
        }

        return sprintf( '%.2f', (float) $total );
    }
}