<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Rest;

use MeuMouse\Flexify_Checkout\Rest\Abstract_Route;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Components;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Funnel_Analytics;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\AB_Testing;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Cart recovery analytics endpoint.
 *
 * Returns the raw data the Analytics Vue page renders (KPI counts, recovered
 * total and the two chart datasets), replacing the legacy admin-ajax
 * "fcrc_get_analytics_data" action. Reuses the existing fcrc_* aggregation
 * helpers so the numbers match the legacy screen exactly.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Rest
 * @author MeuMouse.com
 */
class Analytics extends Abstract_Route {

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/recovery/analytics';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'GET';

    /**
     * Argument schema.
     *
     * @since 6.0.0
     * @var array
     */
    protected $args = array(
        'period' => array(
            'type' => 'integer',
            'default' => 7,
            'sanitize_callback' => 'absint',
        ),
    );


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $periods = Components::period_filter();
        $period = absint( $request->get_param('period') );

        if ( ! array_key_exists( $period, $periods ) ) {
            $period = 7;
        }

        $statuses = array( 'lead', 'shopping', 'abandoned', 'order_abandoned', 'recovered', 'lost', 'purchased' );
        $counts = array();

        foreach ( $statuses as $status ) {
            $counts[ $status ] = function_exists('fcrc_get_carts_count_by_status') ? fcrc_get_carts_count_by_status( $status, $period ) : 0;
        }

        $recovered_chart = function_exists('fcrc_get_daily_recovered_totals') ? fcrc_get_daily_recovered_totals( $period ) : array( 'labels' => array(), 'series' => array() );
        $recovered_total = array_sum( $recovered_chart['series'] );

        $notifications_chart = function_exists('fcrc_get_notifications_chart_data') ? fcrc_get_notifications_chart_data( $period ) : array( 'categories' => array(), 'series' => array() );

        return $this->success_response( array(
            'period' => $period,
            'periods' => $this->format_periods( $periods ),
            'counts' => $counts,
            'recovered_total' => $recovered_total,
            'recovered_total_formatted' => self::format_price( $recovered_total ),
            'recovered_chart' => $recovered_chart,
            'notifications_chart' => $notifications_chart,
            'funnel' => Funnel_Analytics::get_report( $period ),
            'ab_tests' => AB_Testing::get_report( $period ),
        ) );
    }


    /**
     * Format a monetary amount as a plain (entity-decoded) string.
     *
     * wc_price() returns HTML with numeric entities for the currency symbol
     * (e.g. "&#82;&#36;&nbsp;0,00"). wp_strip_all_tags() removes the tags but
     * leaves those entities untouched, and the Vue page renders the value as
     * text — so the raw entities leak to the screen. Decoding them yields the
     * intended "R$ 0,00".
     *
     * @since 6.0.0
     * @param float $amount Amount to format.
     * @return string
     */
    public static function format_price( $amount ) {
        if ( function_exists('wc_price') ) {
            return trim( html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' ) );
        }

        return number_format_i18n( (float) $amount, 2 );
    }


    /**
     * Normalise the period filter map into an ordered list for the UI.
     *
     * @since 6.0.0
     * @param array $periods Map of days => label.
     * @return array<int,array{value:int,label:string}>
     */
    private function format_periods( $periods ) {
        $out = array();

        foreach ( $periods as $value => $label ) {
            $out[] = array(
                'value' => (int) $value,
                'label' => $label,
            );
        }

        return $out;
    }
}
