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

        $post_status = ( $status && in_array( $status, self::STATUSES, true ) ) ? array( $status ) : self::STATUSES;

        $query_args = array(
            'post_type' => 'fc-recovery-carts',
            'post_status' => $post_status,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => false,
        );

        if ( '' !== $search ) {
            $query_args['s'] = $search;
        }

        $query = new WP_Query( $query_args );
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
        ) );
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
            'lead' => __( 'Lead', 'fc-recovery-carts' ),
            'shopping' => __( 'Comprando', 'fc-recovery-carts' ),
            'abandoned' => __( 'Abandonado', 'fc-recovery-carts' ),
            'order_abandoned' => __( 'Pedido abandonado', 'fc-recovery-carts' ),
            'recovered' => __( 'Recuperado', 'fc-recovery-carts' ),
            'lost' => __( 'Perdido', 'fc-recovery-carts' ),
            'purchased' => __( 'Comprou', 'fc-recovery-carts' ),
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
        $options = array( array( 'value' => 'all', 'label' => __( 'Todos os status', 'fc-recovery-carts' ) ) );

        foreach ( self::STATUSES as $status ) {
            $options[] = array( 'value' => $status, 'label' => $this->status_label( $status ) );
        }

        return $options;
    }
}
