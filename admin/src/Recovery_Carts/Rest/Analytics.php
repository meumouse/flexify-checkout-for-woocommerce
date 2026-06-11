<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Rest;

use MeuMouse\Flexify_Checkout\Rest\Abstract_Route;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Components;
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
            'recovered_total_formatted' => function_exists('wc_price') ? wp_strip_all_tags( wc_price( $recovered_total ) ) : number_format_i18n( $recovered_total, 2 ),
            'recovered_chart' => $recovered_chart,
            'notifications_chart' => $notifications_chart,
        ) );
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
