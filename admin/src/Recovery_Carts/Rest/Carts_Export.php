<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Rest;

use MeuMouse\Flexify_Checkout\Rest\Abstract_Route;
use WP_REST_Request;
use WP_Query;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Cart recovery — CSV export endpoint.
 *
 * GET flexify-checkout/v1/recovery/carts/export. Streams the recovery carts
 * matching the current list filters (status, search, date range) as a
 * text/csv download. Reuses the same query args and per-cart formatting scope
 * as Rest\Carts so the export mirrors exactly what the table shows.
 *
 * The response is streamed directly (headers + echo + exit) instead of the
 * usual JSON envelope so the browser receives a real file. The Vue side fetches
 * it as a blob (carrying the REST nonce) and triggers the download.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Rest
 * @author MeuMouse.com
 */
class Carts_Export extends Abstract_Route {

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/recovery/carts/export';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'GET';

    /**
     * Argument schema (mirrors the list filters).
     *
     * @since 6.0.0
     * @var array
     */
    protected $args = array(
        'status'    => array( 'type' => 'string', 'default' => 'all', 'sanitize_callback' => 'sanitize_key' ),
        'search'    => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
        'date_from' => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
        'date_to'   => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
    );

    /**
     * Maximum rows exported in a single request.
     *
     * @since 6.0.0
     * @var int
     */
    const MAX_ROWS = 10000;


    /**
     * Handle the request — stream a CSV download and exit.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return void
     */
    public function handle( WP_REST_Request $request ) {
        $status = sanitize_key( (string) $request->get_param('status') );
        $search = sanitize_text_field( (string) $request->get_param('search') );
        $date_from = sanitize_text_field( (string) $request->get_param('date_from') );
        $date_to = sanitize_text_field( (string) $request->get_param('date_to') );

        $base_args = Carts::base_query_args( $search, $date_from, $date_to );
        $post_status = ( $status && in_array( $status, Carts::STATUSES, true ) ) ? array( $status ) : Carts::STATUSES;

        $query = new WP_Query( array_merge( $base_args, array(
            'post_status'    => $post_status,
            'posts_per_page' => self::MAX_ROWS,
            'no_found_rows'  => true,
        ) ) );

        $filename = 'recovery-carts-' . gmdate( 'Y-m-d', (int) current_time('timestamp', true) ) . '.csv';

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

        $output = fopen( 'php://output', 'w' );

        // UTF-8 BOM so Excel opens accented characters correctly.
        fwrite( $output, "\xEF\xBB\xBF" );

        fputcsv( $output, array(
            __( 'ID', 'flexify-checkout-for-woocommerce' ),
            __( 'Status', 'flexify-checkout-for-woocommerce' ),
            __( 'Name', 'flexify-checkout-for-woocommerce' ),
            __( 'Phone', 'flexify-checkout-for-woocommerce' ),
            __( 'Email', 'flexify-checkout-for-woocommerce' ),
            __( 'Location', 'flexify-checkout-for-woocommerce' ),
            __( 'Products', 'flexify-checkout-for-woocommerce' ),
            __( 'Total', 'flexify-checkout-for-woocommerce' ),
            __( 'Coupon', 'flexify-checkout-for-woocommerce' ),
            __( 'Notifications', 'flexify-checkout-for-woocommerce' ),
            __( 'Event date', 'flexify-checkout-for-woocommerce' ),
        ) );

        foreach ( $query->posts as $post ) {
            fputcsv( $output, $this->row( $post->ID, $post->post_status, $post->post_date ) );
        }

        fclose( $output );

        exit;
    }


    /**
     * Build a single CSV row for a cart.
     *
     * @since 6.0.0
     * @param int    $id | Cart post ID.
     * @param string $status | Post status.
     * @param string $post_date | Post date (site local).
     * @return array<int,string>
     */
    private function row( $id, $status, $post_date ) {
        $items = get_post_meta( $id, '_fcrc_cart_items', true );
        $items = is_array( $items ) ? $items : array();

        $products = array();

        foreach ( $items as $item ) {
            $name = isset( $item['name'] ) ? (string) $item['name'] : '';
            $qty = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;

            if ( '' !== $name ) {
                $products[] = $name . ' x' . $qty;
            }
        }

        $notifications = get_post_meta( $id, '_fcrc_notifications_sent', true );
        $total = get_post_meta( $id, '_fcrc_cart_total', true );

        $location = array_filter( array(
            (string) get_post_meta( $id, '_fcrc_location_city', true ),
            (string) get_post_meta( $id, '_fcrc_location_state', true ),
            (string) get_post_meta( $id, '_fcrc_location_country_code', true ),
        ) );

        return array(
            (string) $id,
            Cart_Timeline::status_label( $status ),
            (string) get_post_meta( $id, '_fcrc_full_name', true ),
            (string) get_post_meta( $id, '_fcrc_cart_phone', true ),
            (string) get_post_meta( $id, '_fcrc_cart_email', true ),
            implode( ', ', $location ),
            implode( '; ', $products ),
            ( 'lead' !== $status && $total ) ? (string) $total : '',
            (string) get_post_meta( $id, '_fcrc_coupon_code', true ),
            is_array( $notifications ) ? (string) count( $notifications ) : '0',
            $post_date ? wp_date( 'Y-m-d H:i', strtotime( $post_date ) ) : '',
        );
    }
}
