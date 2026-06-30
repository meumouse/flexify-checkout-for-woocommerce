<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Core;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Rest\Analytics;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Checkout funnel analytics.
 *
 * Backs the "Funil de checkout" section of the Analytics page. Two data
 * sources are combined:
 *
 * 1. A lightweight day-bucketed store (single autoloaded-off option) fed by the
 *    checkout step beacon — see Rest\Track_Funnel — which records how many
 *    sessions reached each checkout step (contact / shipping / payment) plus the
 *    device and traffic source captured at entry.
 * 2. WooCommerce orders + recovery carts + WooCommerce Order Attribution meta,
 *    queried on demand to derive the conversion/abandonment/ticket/coupon/
 *    payment-failure KPIs and the per-segment breakdowns. This needs no
 *    front-end capture, so those numbers are available retroactively.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Core
 * @author MeuMouse.com
 */
class Funnel_Analytics {

    /**
     * Option holding the day-bucketed step store.
     *
     * @since 6.0.0
     * @var string
     */
    const OPTION = 'flexify_checkout_funnel_stats';

    /**
     * Recognised funnel steps, in order.
     *
     * @since 6.0.0
     * @var array
     */
    const STEPS = array( 'contact', 'shipping', 'payment', 'purchase' );

    /**
     * How many days of buckets to retain in the store.
     *
     * @since 6.0.0
     * @var int
     */
    const RETENTION_DAYS = 400;


    /**
     * Constructor — bind the reliable server-side recorders.
     *
     * @since 6.0.0
     * @return void
     */
    public function __construct() {
        // Record the "purchase" step from the order itself so it never depends
        // on the browser beacon firing on the thank-you page.
        add_action( 'woocommerce_order_status_processing', array( $this, 'record_purchase_step' ), 5 );
        add_action( 'woocommerce_order_status_completed', array( $this, 'record_purchase_step' ), 5 );
    }


    /**
     * Record the purchase step for a paid order (once per order).
     *
     * @since 6.0.0
     * @param int $order_id Order id.
     * @return void
     */
    public function record_purchase_step( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        if ( $order->get_meta('_fcrc_funnel_purchase_recorded') === 'yes' ) {
            return;
        }

        $date = $order->get_date_created() ? $order->get_date_created()->date('Y-m-d') : gmdate( 'Y-m-d', (int) current_time('timestamp', true) );

        self::record_step( 'purchase', array(), $date );

        $order->update_meta_data( '_fcrc_funnel_purchase_recorded', 'yes' );
        $order->save();
    }


    /**
     * Increment a funnel step counter for a given day.
     *
     * Device/source dimensions are only captured at the entry step ("contact")
     * so each session contributes to the segment denominator exactly once.
     *
     * @since 6.0.0
     * @param string $step Step key (see STEPS).
     * @param array $context Optional { device, source } captured at entry.
     * @param string|null $date Y-m-d bucket; defaults to today (site time).
     * @return void
     */
    public static function record_step( $step, $context = array(), $date = null ) {
        if ( ! in_array( $step, self::STEPS, true ) ) {
            return;
        }

        $date = $date ?: gmdate( 'Y-m-d', (int) current_time('timestamp', true) );
        $stats = get_option( self::OPTION, array() );
        $stats = is_array( $stats ) ? $stats : array();

        if ( empty( $stats[ $date ] ) || ! is_array( $stats[ $date ] ) ) {
            $stats[ $date ] = array( 'steps' => array(), 'devices' => array(), 'sources' => array() );
        }

        $stats[ $date ]['steps'][ $step ] = ( $stats[ $date ]['steps'][ $step ] ?? 0 ) + 1;

        if ( $step === 'contact' ) {
            $device = ! empty( $context['device'] ) ? sanitize_key( $context['device'] ) : '';
            $source = ! empty( $context['source'] ) ? sanitize_key( $context['source'] ) : '';

            if ( $device ) {
                $stats[ $date ]['devices'][ $device ] = ( $stats[ $date ]['devices'][ $device ] ?? 0 ) + 1;
            }

            if ( $source ) {
                $stats[ $date ]['sources'][ $source ] = ( $stats[ $date ]['sources'][ $source ] ?? 0 ) + 1;
            }
        }

        update_option( self::OPTION, self::prune( $stats ), false );
    }


    /**
     * Drop buckets older than the retention window.
     *
     * @since 6.0.0
     * @param array $stats Store.
     * @return array
     */
    private static function prune( $stats ) {
        $cutoff = gmdate( 'Y-m-d', (int) current_time('timestamp', true) - ( self::RETENTION_DAYS * DAY_IN_SECONDS ) );

        foreach ( array_keys( $stats ) as $date ) {
            if ( (string) $date < $cutoff ) {
                unset( $stats[ $date ] );
            }
        }

        return $stats;
    }


    /**
     * Build the full funnel report for the Analytics page.
     *
     * @since 6.0.0
     * @param int $days Period length in days.
     * @return array
     */
    public static function get_report( $days ) {
        $days = max( 1, (int) $days );
        $now = (int) current_time('timestamp', true);
        $start = $now - ( $days * DAY_IN_SECONDS );
        $prev_start = $start - ( $days * DAY_IN_SECONDS );

        $current = self::compute_window( $start, $now );
        $previous = self::compute_window( $prev_start, $start );

        $has_data = $current['paid_count'] > 0
            || $current['failed_count'] > 0
            || $current['carts_started'] > 0
            || array_sum( $current['step_counts'] ) > 0;

        return array(
            'has_data' => $has_data,
            'metrics' => self::build_metrics( $current, $previous ),
            'steps' => self::build_steps( $current ),
            'segments' => self::build_segments( $current ),
        );
    }


    /**
     * Aggregate every signal for a single time window.
     *
     * @since 6.0.0
     * @param int $start_ts Window start (unix, exclusive).
     * @param int $end_ts Window end (unix, inclusive).
     * @return array
     */
    private static function compute_window( $start_ts, $end_ts ) {
        $result = array(
            'paid_count' => 0,
            'paid_total' => 0.0,
            'coupon_orders' => 0,
            'failed_count' => 0,
            'payment_methods' => array(),
            'customer_types' => array( 'new' => 0, 'returning' => 0 ),
            'order_devices' => array(),
            'order_sources' => array(),
            'completion_seconds' => array(),
            'carts_started' => 0,
            'carts_converted' => 0,
            'step_counts' => array( 'contact' => 0, 'shipping' => 0, 'payment' => 0, 'purchase' => 0 ),
            'store_devices' => array(),
            'store_sources' => array(),
        );

        // --- WooCommerce orders ---
        if ( function_exists('wc_get_orders') ) {
            $paid_statuses = self::paid_statuses();
            $cap = (int) apply_filters( 'Flexify_Checkout/Recovery_Carts/Analytics/Orders_Cap', 5000 );

            $orders = wc_get_orders( array(
                'limit' => $cap,
                'status' => array_merge( $paid_statuses, array('failed') ),
                'date_created' => ( $start_ts + 1 ) . '...' . $end_ts,
                'orderby' => 'date',
                'order' => 'ASC',
                'type' => 'shop_order',
            ) );

            $returning_cache = array();

            foreach ( $orders as $order ) {
                if ( ! $order instanceof \WC_Order ) {
                    continue;
                }

                if ( $order->get_status() === 'failed' ) {
                    $result['failed_count']++;
                    continue;
                }

                $result['paid_count']++;
                $result['paid_total'] += (float) $order->get_total();

                if ( count( $order->get_coupon_codes() ) > 0 ) {
                    $result['coupon_orders']++;
                }

                $pm_label = $order->get_payment_method_title();
                $pm_label = $pm_label !== '' ? $pm_label : ( $order->get_payment_method() ?: __( 'Unknown', 'flexify-checkout-for-woocommerce' ) );
                $result['payment_methods'][ $pm_label ] = ( $result['payment_methods'][ $pm_label ] ?? 0 ) + 1;

                $type = self::is_returning_customer( $order, $start_ts, $returning_cache ) ? 'returning' : 'new';
                $result['customer_types'][ $type ]++;

                $device = self::order_device( $order );

                if ( $device !== '' ) {
                    $result['order_devices'][ $device ] = ( $result['order_devices'][ $device ] ?? 0 ) + 1;
                }

                $source = self::order_source( $order );

                if ( $source !== '' ) {
                    $result['order_sources'][ $source ] = ( $result['order_sources'][ $source ] ?? 0 ) + 1;
                }

                // Completion time: cart creation -> order creation.
                $cart_id = (int) $order->get_meta('_fcrc_cart_id');

                if ( $cart_id && $order->get_date_created() ) {
                    $cart_created = (int) get_post_time( 'U', true, $cart_id );
                    $elapsed = $order->get_date_created()->getTimestamp() - $cart_created;

                    // Ignore non-positive or implausibly long spans (> 7 days).
                    if ( $cart_created > 0 && $elapsed > 0 && $elapsed <= 7 * DAY_IN_SECONDS ) {
                        $result['completion_seconds'][] = $elapsed;
                    }
                }
            }
        }

        // --- Recovery carts (started vs converted in the window) ---
        $result['carts_started'] = self::count_carts_between( null, $start_ts, $end_ts );
        $result['carts_converted'] = self::count_carts_between( array( 'recovered', 'purchased' ), $start_ts, $end_ts );

        // --- Step store buckets ---
        $stats = get_option( self::OPTION, array() );
        $stats = is_array( $stats ) ? $stats : array();
        $from = gmdate( 'Y-m-d', $start_ts + 1 );
        $to = gmdate( 'Y-m-d', $end_ts );

        foreach ( $stats as $date => $bucket ) {
            if ( (string) $date < $from || (string) $date > $to ) {
                continue;
            }

            foreach ( self::STEPS as $step ) {
                $result['step_counts'][ $step ] += (int) ( $bucket['steps'][ $step ] ?? 0 );
            }

            foreach ( (array) ( $bucket['devices'] ?? array() ) as $device => $count ) {
                $result['store_devices'][ $device ] = ( $result['store_devices'][ $device ] ?? 0 ) + (int) $count;
            }

            foreach ( (array) ( $bucket['sources'] ?? array() ) as $source => $count ) {
                $result['store_sources'][ $source ] = ( $result['store_sources'][ $source ] ?? 0 ) + (int) $count;
            }
        }

        return $result;
    }


    /**
     * Build the six KPI cards with previous-period deltas.
     *
     * @since 6.0.0
     * @param array $cur Current window aggregate.
     * @param array $prev Previous window aggregate.
     * @return array
     */
    private static function build_metrics( $cur, $prev ) {
        $cur_conv = self::conversion_rate( $cur );
        $prev_conv = self::conversion_rate( $prev );

        $cur_ticket = $cur['paid_count'] > 0 ? $cur['paid_total'] / $cur['paid_count'] : 0.0;
        $prev_ticket = $prev['paid_count'] > 0 ? $prev['paid_total'] / $prev['paid_count'] : 0.0;

        $cur_fail = self::failure_rate( $cur );
        $prev_fail = self::failure_rate( $prev );

        $cur_coupon = $cur['paid_count'] > 0 ? $cur['coupon_orders'] / $cur['paid_count'] : 0.0;
        $prev_coupon = $prev['paid_count'] > 0 ? $prev['coupon_orders'] / $prev['paid_count'] : 0.0;

        $cur_time = self::average( $cur['completion_seconds'] );
        $prev_time = self::average( $prev['completion_seconds'] );

        return array(
            'abandonment_rate' => self::percent_metric( 1 - $cur_conv, 1 - $prev_conv, false, $cur['carts_started'] > 0 || $cur['paid_count'] > 0 ),
            'conversion_rate' => self::percent_metric( $cur_conv, $prev_conv, true, $cur['carts_started'] > 0 || $cur['paid_count'] > 0 ),
            'average_ticket' => self::money_metric( $cur_ticket, $prev_ticket, $cur['paid_count'] > 0 ),
            'completion_time' => self::duration_metric( $cur_time, $prev_time, ! empty( $cur['completion_seconds'] ) ),
            'payment_failure_rate' => self::percent_metric( $cur_fail, $prev_fail, false, ( $cur['failed_count'] + $cur['paid_count'] ) > 0 ),
            'coupon_usage' => self::percent_metric( $cur_coupon, $prev_coupon, true, $cur['paid_count'] > 0 ),
        );
    }


    /**
     * Conversion rate for a window (orders over checkout entries).
     *
     * Uses the captured "contact" step as the denominator when the beacon has
     * data; otherwise falls back to the broader recovery-cart count so the card
     * still shows a meaningful number retroactively.
     *
     * @since 6.0.0
     * @param array $w Window aggregate.
     * @return float 0..1
     */
    private static function conversion_rate( $w ) {
        $entries = $w['step_counts']['contact'] > 0 ? $w['step_counts']['contact'] : $w['carts_started'];

        if ( $entries <= 0 ) {
            return 0.0;
        }

        return min( 1.0, $w['paid_count'] / $entries );
    }


    /**
     * Payment failure rate for a window.
     *
     * @since 6.0.0
     * @param array $w Window aggregate.
     * @return float 0..1
     */
    private static function failure_rate( $w ) {
        $attempted = $w['failed_count'] + $w['paid_count'];

        return $attempted > 0 ? $w['failed_count'] / $attempted : 0.0;
    }


    /**
     * Build the per-step completion list (relative to checkout entry).
     *
     * @since 6.0.0
     * @param array $w Window aggregate.
     * @return array
     */
    private static function build_steps( $w ) {
        $base = $w['step_counts']['contact'];
        $labels = array(
            'contact' => __( 'Contato', 'flexify-checkout-for-woocommerce' ),
            'shipping' => __( 'Entrega', 'flexify-checkout-for-woocommerce' ),
            'payment' => __( 'Pagamento', 'flexify-checkout-for-woocommerce' ),
        );

        $steps = array();

        foreach ( $labels as $key => $label ) {
            $count = (int) $w['step_counts'][ $key ];
            $percent = $base > 0 ? round( ( $count / $base ) * 100 ) : null;

            $steps[] = array(
                'key' => $key,
                'label' => $label,
                'count' => $count,
                'percent' => $percent,
            );
        }

        return $steps;
    }


    /**
     * Build the four segment breakdowns.
     *
     * @since 6.0.0
     * @param array $w Window aggregate.
     * @return array
     */
    private static function build_segments( $w ) {
        // Device / source prefer order attribution; fall back to the step store.
        $devices = ! empty( $w['order_devices'] ) ? $w['order_devices'] : $w['store_devices'];
        $sources = ! empty( $w['order_sources'] ) ? $w['order_sources'] : $w['store_sources'];

        $customer = array();

        if ( $w['customer_types']['new'] > 0 ) {
            $customer[ __( 'Novos', 'flexify-checkout-for-woocommerce' ) ] = $w['customer_types']['new'];
        }

        if ( $w['customer_types']['returning'] > 0 ) {
            $customer[ __( 'Recorrentes', 'flexify-checkout-for-woocommerce' ) ] = $w['customer_types']['returning'];
        }

        return array(
            'device' => self::distribution( self::humanize_devices( $devices ) ),
            'payment_method' => self::distribution( $w['payment_methods'] ),
            'customer_type' => self::distribution( $customer ),
            'traffic_source' => self::distribution( self::humanize_sources( $sources ) ),
        );
    }


    /**
     * Turn a { label => count } map into a sorted distribution with percentages.
     *
     * @since 6.0.0
     * @param array $map Label => count.
     * @return array
     */
    private static function distribution( $map ) {
        $total = array_sum( $map );

        if ( $total <= 0 ) {
            return array();
        }

        arsort( $map );
        $out = array();

        foreach ( $map as $label => $count ) {
            $out[] = array(
                'label' => (string) $label,
                'value' => (int) $count,
                'percent' => (int) round( ( $count / $total ) * 100 ),
            );
        }

        return $out;
    }


    /**
     * Map raw device keys to display labels.
     *
     * @since 6.0.0
     * @param array $devices Device key => count.
     * @return array
     */
    private static function humanize_devices( $devices ) {
        $labels = array(
            'desktop' => __( 'Desktop', 'flexify-checkout-for-woocommerce' ),
            'mobile' => __( 'Celular', 'flexify-checkout-for-woocommerce' ),
            'tablet' => __( 'Tablet', 'flexify-checkout-for-woocommerce' ),
        );

        $out = array();

        foreach ( $devices as $key => $count ) {
            $label = $labels[ $key ] ?? ucfirst( (string) $key );
            $out[ $label ] = ( $out[ $label ] ?? 0 ) + (int) $count;
        }

        return $out;
    }


    /**
     * Map raw source keys to display labels.
     *
     * @since 6.0.0
     * @param array $sources Source key => count.
     * @return array
     */
    private static function humanize_sources( $sources ) {
        $labels = array(
            'typein' => __( 'Direto', 'flexify-checkout-for-woocommerce' ),
            'direct' => __( 'Direto', 'flexify-checkout-for-woocommerce' ),
            'organic' => __( 'Busca orgânica', 'flexify-checkout-for-woocommerce' ),
            'referral' => __( 'Referência', 'flexify-checkout-for-woocommerce' ),
            'utm' => __( 'Campanha', 'flexify-checkout-for-woocommerce' ),
            'admin' => __( 'Administrativo', 'flexify-checkout-for-woocommerce' ),
        );

        $out = array();

        foreach ( $sources as $key => $count ) {
            $label = $labels[ $key ] ?? ucfirst( (string) $key );
            $out[ $label ] = ( $out[ $label ] ?? 0 ) + (int) $count;
        }

        return $out;
    }


    /**
     * Read the device type recorded by WooCommerce Order Attribution.
     *
     * @since 6.0.0
     * @param \WC_Order $order Order.
     * @return string Lowercased device key, or '' when unknown.
     */
    private static function order_device( $order ) {
        $device = $order->get_meta('_wc_order_attribution_device_type');

        return $device ? sanitize_key( $device ) : '';
    }


    /**
     * Read the traffic source recorded by WooCommerce Order Attribution.
     *
     * @since 6.0.0
     * @param \WC_Order $order Order.
     * @return string Source key, or '' when unknown.
     */
    private static function order_source( $order ) {
        $utm = $order->get_meta('_wc_order_attribution_utm_source');

        if ( $utm ) {
            return sanitize_key( $utm );
        }

        $type = $order->get_meta('_wc_order_attribution_source_type');

        return $type ? sanitize_key( $type ) : '';
    }


    /**
     * Decide whether an order belongs to a returning customer.
     *
     * "Returning" means the same customer (by id, or billing email for guests)
     * has at least one earlier paid order. Results are cached per customer key
     * for the duration of the window computation.
     *
     * @since 6.0.0
     * @param \WC_Order $order Order.
     * @param int $window_start Window start timestamp.
     * @param array $cache Reference cache of key => bool.
     * @return bool
     */
    private static function is_returning_customer( $order, $window_start, &$cache ) {
        $customer_id = (int) $order->get_customer_id();
        $email = $order->get_billing_email();
        $key = $customer_id > 0 ? 'id:' . $customer_id : ( $email ? 'em:' . strtolower( $email ) : '' );

        if ( $key === '' ) {
            return false;
        }

        if ( isset( $cache[ $key ] ) ) {
            return $cache[ $key ];
        }

        $created = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : (int) current_time('timestamp', true);

        $args = array(
            'limit' => 1,
            'status' => self::paid_statuses(),
            'date_created' => '<' . $created,
            'return' => 'ids',
            'type' => 'shop_order',
            'exclude' => array( $order->get_id() ),
        );

        if ( $customer_id > 0 ) {
            $args['customer_id'] = $customer_id;
        } else {
            $args['billing_email'] = $email;
        }

        $earlier = function_exists('wc_get_orders') ? wc_get_orders( $args ) : array();
        $cache[ $key ] = ! empty( $earlier );

        return $cache[ $key ];
    }


    /**
     * Count recovery-cart posts created within a window, optionally by status.
     *
     * @since 6.0.0
     * @param array|null $statuses Post statuses, or null for any.
     * @param int $start_ts Window start (exclusive).
     * @param int $end_ts Window end (inclusive).
     * @return int
     */
    private static function count_carts_between( $statuses, $start_ts, $end_ts ) {
        global $wpdb;

        $from = gmdate( 'Y-m-d H:i:s', $start_ts + 1 );
        $to = gmdate( 'Y-m-d H:i:s', $end_ts );

        $sql = "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'fc-recovery-carts' AND post_date >= %s AND post_date <= %s";
        $params = array( $from, $to );

        if ( is_array( $statuses ) && ! empty( $statuses ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
            $sql .= " AND post_status IN ($placeholders)";
            $params = array_merge( $params, $statuses );
        }

        return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
    }


    /**
     * Paid order statuses counted as conversions.
     *
     * @since 6.0.0
     * @return array
     */
    private static function paid_statuses() {
        return apply_filters( 'Flexify_Checkout/Recovery_Carts/Analytics/Paid_Statuses', array( 'processing', 'completed', 'on-hold' ) );
    }


    /**
     * Mean of a list, or 0 for an empty list.
     *
     * @since 6.0.0
     * @param array $values Numbers.
     * @return float
     */
    private static function average( $values ) {
        return ! empty( $values ) ? array_sum( $values ) / count( $values ) : 0.0;
    }


    /**
     * Build a percentage KPI payload with a percentage-point delta.
     *
     * @since 6.0.0
     * @param float $current Current rate (0..1).
     * @param float $previous Previous rate (0..1).
     * @param bool $up_is_good Whether an increase is a positive outcome.
     * @param bool $has_data Whether the card has backing data.
     * @return array
     */
    private static function percent_metric( $current, $previous, $up_is_good, $has_data ) {
        $delta = $has_data ? round( ( $current - $previous ) * 100 ) : null;

        return array(
            'value' => round( $current * 100, 1 ),
            'formatted' => $has_data ? self::format_percent( $current ) : null,
            'delta' => $delta,
            'delta_formatted' => self::format_points( $delta ),
            'up_is_good' => $up_is_good,
        );
    }


    /**
     * Build a money KPI payload with a relative delta.
     *
     * @since 6.0.0
     * @param float $current Current amount.
     * @param float $previous Previous amount.
     * @param bool $has_data Whether the card has backing data.
     * @return array
     */
    private static function money_metric( $current, $previous, $has_data ) {
        $delta = $has_data ? self::relative_delta( $current, $previous ) : null;

        return array(
            'value' => round( $current, 2 ),
            'formatted' => $has_data ? Analytics::format_price( $current ) : null,
            'delta' => $delta,
            'delta_formatted' => self::format_relative( $delta ),
            'up_is_good' => true,
        );
    }


    /**
     * Build a duration KPI payload (seconds) with a relative delta.
     *
     * @since 6.0.0
     * @param float $current Current seconds.
     * @param float $previous Previous seconds.
     * @param bool $has_data Whether the card has backing data.
     * @return array
     */
    private static function duration_metric( $current, $previous, $has_data ) {
        $delta = $has_data ? self::relative_delta( $current, $previous ) : null;

        return array(
            'value' => round( $current ),
            'formatted' => $has_data ? self::format_duration( $current ) : null,
            'delta' => $delta,
            'delta_formatted' => self::format_relative( $delta ),
            'up_is_good' => false,
        );
    }


    /**
     * Relative change between two numbers as a rounded percentage.
     *
     * @since 6.0.0
     * @param float $current Current value.
     * @param float $previous Previous value.
     * @return int|null
     */
    private static function relative_delta( $current, $previous ) {
        if ( $previous <= 0 ) {
            return null;
        }

        return (int) round( ( ( $current - $previous ) / $previous ) * 100 );
    }


    /**
     * Format a 0..1 rate as a localized percentage.
     *
     * @since 6.0.0
     * @param float $rate Rate.
     * @return string
     */
    private static function format_percent( $rate ) {
        return number_format_i18n( $rate * 100, 1 ) . '%';
    }


    /**
     * Format a percentage-point delta with an explicit sign.
     *
     * @since 6.0.0
     * @param int|null $points Delta in points.
     * @return string|null
     */
    private static function format_points( $points ) {
        if ( $points === null ) {
            return null;
        }

        $sign = $points > 0 ? '+' : '';

        return $sign . $points . ' pp';
    }


    /**
     * Format a relative delta percentage with an explicit sign.
     *
     * @since 6.0.0
     * @param int|null $percent Delta percentage.
     * @return string|null
     */
    private static function format_relative( $percent ) {
        if ( $percent === null ) {
            return null;
        }

        $sign = $percent > 0 ? '+' : '';

        return $sign . $percent . '%';
    }


    /**
     * Format a duration in seconds as a compact human string.
     *
     * @since 6.0.0
     * @param float $seconds Seconds.
     * @return string
     */
    private static function format_duration( $seconds ) {
        $seconds = (int) round( $seconds );

        if ( $seconds < 60 ) {
            return sprintf( _n( '%d seg', '%d seg', $seconds, 'flexify-checkout-for-woocommerce' ), $seconds );
        }

        if ( $seconds < HOUR_IN_SECONDS ) {
            return sprintf( __( '%d min', 'flexify-checkout-for-woocommerce' ), (int) round( $seconds / 60 ) );
        }

        $hours = floor( $seconds / HOUR_IN_SECONDS );
        $minutes = (int) round( ( $seconds % HOUR_IN_SECONDS ) / 60 );

        return sprintf( __( '%dh %dmin', 'flexify-checkout-for-woocommerce' ), $hours, $minutes );
    }
}
