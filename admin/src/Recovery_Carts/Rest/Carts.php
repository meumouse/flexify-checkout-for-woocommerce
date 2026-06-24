<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Rest;

use MeuMouse\Flexify_Checkout\Rest\Abstract_Route;
use WP_REST_Request;
use WP_Query;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Cart recovery — carts listing endpoint.
 *
 * Paginated, filterable list of recovery carts backing the "Todos os carrinhos"
 * Vue page, replacing the legacy WP_List_Table. Returns the same fields the
 * legacy table rendered (contact, location, products, total, event date,
 * notifications) read from the _fcrc_* post meta.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Rest
 * @author MeuMouse.com
 */
class Carts extends Abstract_Route {

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/recovery/carts';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'GET';

    /**
     * Cart statuses, in display order.
     *
     * @since 6.0.0
     * @var array<int,string>
     */
    const STATUSES = array( 'lead', 'shopping', 'abandoned', 'order_abandoned', 'recovered', 'lost', 'purchased' );

    /**
     * Argument schema.
     *
     * @since 6.0.0
     * @var array
     */
    protected $args = array(
        'page' => array( 'type' => 'integer', 'default' => 1, 'sanitize_callback' => 'absint' ),
        'per_page' => array( 'type' => 'integer', 'default' => 20, 'sanitize_callback' => 'absint' ),
        'status' => array( 'type' => 'string', 'default' => 'all', 'sanitize_callback' => 'sanitize_key' ),
        'search' => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
        'date_from' => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
        'date_to' => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
    );


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $page = max( 1, absint( $request->get_param('page') ) );
        $per_page = min( 100, max( 1, absint( $request->get_param('per_page') ) ) );
        $status = sanitize_key( (string) $request->get_param('status') );
        $search = sanitize_text_field( (string) $request->get_param('search') );
        $date_from = sanitize_text_field( (string) $request->get_param('date_from') );
        $date_to = sanitize_text_field( (string) $request->get_param('date_to') );

        // Filters shared by the list query and the per-status counts, so the tab
        // badges reflect the same search/date scope as the rows shown below.
        $base_args = self::base_query_args( $search, $date_from, $date_to );

        $post_status = ( $status && in_array( $status, self::STATUSES, true ) ) ? array( $status ) : self::STATUSES;

        $query = new WP_Query( array_merge( $base_args, array(
            'post_status' => $post_status,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'no_found_rows' => false,
        ) ) );

        $items = array();

        foreach ( $query->posts as $post ) {
            $items[] = $this->format_cart( $post->ID, $post->post_status, $post->post_date );
        }

        return $this->success_response( array(
            'items' => $items,
            'total' => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
            'page' => $page,
            'per_page' => $per_page,
            'statuses' => $this->status_options(),
            'counts' => $this->count_by_status( $base_args ),
        ) );
    }


    /**
     * Build the shared WP_Query args (post type + search + date range) reused by
     * the list query, the per-status counts and the bulk-delete resolver.
     *
     * @since 6.0.0
     * @param string $search Free-text search term.
     * @param string $date_from Start date (Y-m-d) or empty.
     * @param string $date_to End date (Y-m-d) or empty.
     * @return array
     */
    public static function base_query_args( $search, $date_from, $date_to ) {
        $args = array(
            'post_type' => 'fc-recovery-carts',
            'orderby' => 'date',
            'order' => 'DESC',
        );

        if ( '' !== $search ) {
            $args['s'] = $search;
        }

        $date_query = self::build_date_query( $date_from, $date_to );

        if ( $date_query ) {
            $args['date_query'] = $date_query;
        }

        return $args;
    }


    /**
     * Build a post_date date_query clause from the from/to range.
     *
     * @since 6.0.0
     * @param string $from Start date (Y-m-d) or empty.
     * @param string $to End date (Y-m-d) or empty.
     * @return array Empty when no valid bound is given.
     */
    public static function build_date_query( $from, $to ) {
        $clause = array( 'inclusive' => true );

        if ( $from && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ) {
            $clause['after'] = $from . ' 00:00:00';
        }

        if ( $to && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
            $clause['before'] = $to . ' 23:59:59';
        }

        return count( $clause ) > 1 ? array( $clause ) : array();
    }


    /**
     * Count carts per status within the current search/date scope.
     *
     * Powers the filter tab badges ("Todos N", "Abandonado N", …). The "all"
     * key is the sum across every status.
     *
     * @since 6.0.0
     * @param array $base_args Shared query args from {@see base_query_args()}.
     * @return array<string,int>
     */
    private function count_by_status( $base_args ) {
        $counts = array( 'all' => 0 );

        foreach ( self::STATUSES as $status ) {
            $query = new WP_Query( array_merge( $base_args, array(
                'post_status' => array( $status ),
                'posts_per_page' => 1,
                'fields' => 'ids',
                'no_found_rows' => false,
            ) ) );

            $counts[ $status ] = (int) $query->found_posts;
            $counts['all'] += (int) $query->found_posts;
        }

        return $counts;
    }


    /**
     * Build the API representation of a single cart.
     *
     * @since 6.0.0
     * @param int    $id Cart post ID.
     * @param string $status Post status.
     * @param string $post_date Post date.
     * @return array
     */
    private function format_cart( $id, $status, $post_date ) {
        $items = get_post_meta( $id, '_fcrc_cart_items', true );
        $items = is_array( $items ) ? $items : array();

        $products = array();

        foreach ( $items as $item ) {
            $products[] = array(
                'name' => isset( $item['name'] ) ? (string) $item['name'] : '',
                'quantity' => isset( $item['quantity'] ) ? (int) $item['quantity'] : 1,
                'image' => isset( $item['image'] ) ? esc_url_raw( $item['image'] ) : '',
            );
        }

        $notifications = get_post_meta( $id, '_fcrc_notifications_sent', true );
        $total = get_post_meta( $id, '_fcrc_cart_total', true );

        return array(
            'id' => (int) $id,
            'status' => $status,
            'status_label' => $this->status_label( $status ),
            'contact' => array(
                'name' => (string) get_post_meta( $id, '_fcrc_full_name', true ),
                'phone' => (string) get_post_meta( $id, '_fcrc_cart_phone', true ),
                'email' => (string) get_post_meta( $id, '_fcrc_cart_email', true ),
            ),
            'location' => $this->format_location( $id ),
            'products' => $products,
            'total' => $total ? (float) $total : 0,
            'total_formatted' => ( 'lead' !== $status && $total && function_exists('wc_price') ) ? wp_strip_all_tags( wc_price( $total ) ) : '—',
            'event_date' => $post_date ? wp_date( get_option('date_format') . ' ' . get_option('time_format'), strtotime( $post_date ) ) : '',
            'notifications_count' => is_array( $notifications ) ? count( $notifications ) : 0,
        );
    }


    /**
     * Build a formatted location string from stored geolocation meta.
     *
     * @since 6.0.0
     * @param int $id Cart post ID.
     * @return string
     */
    private function format_location( $id ) {
        $parts = array_filter( array(
            (string) get_post_meta( $id, '_fcrc_location_city', true ),
            (string) get_post_meta( $id, '_fcrc_location_state', true ),
            (string) get_post_meta( $id, '_fcrc_location_country_code', true ),
        ) );

        return implode( ', ', $parts );
    }


    /**
     * Human label for a status.
     *
     * @since 6.0.0
     * @param string $status Status key.
     * @return string
     */
    private function status_label( $status ) {
        $labels = array(
            'lead' => __( 'Lead', 'flexify-checkout-for-woocommerce' ),
            'shopping' => __( 'Comprando', 'flexify-checkout-for-woocommerce' ),
            'abandoned' => __( 'Abandonado', 'flexify-checkout-for-woocommerce' ),
            'order_abandoned' => __( 'Pedido abandonado', 'flexify-checkout-for-woocommerce' ),
            'recovered' => __( 'Recuperado', 'flexify-checkout-for-woocommerce' ),
            'lost' => __( 'Perdido', 'flexify-checkout-for-woocommerce' ),
            'purchased' => __( 'Comprou', 'flexify-checkout-for-woocommerce' ),
        );

        return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
    }


    /**
     * Status filter options for the UI ("all" first).
     *
     * @since 6.0.0
     * @return array<int,array{value:string,label:string}>
     */
    private function status_options() {
        $options = array( array( 'value' => 'all', 'label' => __( 'Todos os status', 'flexify-checkout-for-woocommerce' ) ) );

        foreach ( self::STATUSES as $status ) {
            $options[] = array( 'value' => $status, 'label' => $this->status_label( $status ) );
        }

        return $options;
    }
}
