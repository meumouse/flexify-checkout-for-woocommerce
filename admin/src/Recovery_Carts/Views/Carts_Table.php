<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Views;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers;

use WP_List_Table;
use WP_Query;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( ! class_exists('WP_List_Table') ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Carts table class
 * 
 * @since 1.0.0
 * @version 1.3.5
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Views
 * @author MeuMouse.com
 */
class Carts_Table extends WP_List_Table {

    /**
     * Construct function
     * 
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        parent::__construct( array(
            'singular' => __('Carrinho', 'fc-recovery-carts'),
            'plural' => __('Carrinhos', 'fc-recovery-carts'),
            'ajax' => false,
        ));
    }

    
    /**
     * Display the admin page content
     * 
     * @since 1.1.0
     * @return void
     */
    public function display_page() {
        echo '<div class="wrap"><h1 class="wp-heading-inline">' . __( 'Gerenciar carrinhos', 'fc-recovery-carts' ) . '</h1>';

        $last_change = (int) get_option( 'fcrc_carts_table_last_change', 0 );

        printf( '<div id="fcrc-carts-table-wrapper" data-last-change="%d">', $last_change );
        $this->render_table_inner();
        echo '</div></div>';
    }


    /**
     * Render the inner table markup (form + search + list).
     * Extracted so it can be reused by the AJAX refresh endpoint.
     *
     * @since 1.4.0
     * @return void
     */
    public function render_table_inner() {
        echo '<form method="post">';
            echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ?? '' ) . '" />';
            echo '<input type="hidden" name="post_status" value="' . esc_attr( $_REQUEST['post_status'] ?? '' ) . '" />';

            $this->search_box( __( 'Buscar carrinhos', 'fc-recovery-carts' ), 'fcrc_cart_search' );
            $this->display();
        echo '</form>';
    }


    /**
     * Display navigation tabs for filtering carts by status
     * 
     * @since 1.1.0
     * @version 1.3.5
     * @return void
     */
    public function display_navigation_tabs() {
        global $wpdb;

        $statuses = array(
            'all' => __( 'Todos', 'fc-recovery-carts' ),
            'shopping' => __( 'Comprando', 'fc-recovery-carts' ),
            'abandoned' => __( 'Abandonado', 'fc-recovery-carts' ),
            'order_abandoned' => __( 'Pedido Abandonado', 'fc-recovery-carts' ),
            'recovered' => __( 'Recuperado', 'fc-recovery-carts' ),
            'lead' => __( 'Lead', 'fc-recovery-carts' ),
            'lost' => __( 'Perdido', 'fc-recovery-carts' ),
        );

        $current_status = isset( $_GET['post_status'] ) ? sanitize_text_field( $_GET['post_status'] ) : 'all';

        // count the number of carts for each status
        $counts = array();

        foreach ( $statuses as $status => $label ) {
            if ( $status === 'all' ) {
                $counts[$status] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'fc-recovery-carts'");
            } else {
                $counts[$status] = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'fc-recovery-carts' AND post_status = %s",
                    $status
                ));
            }
        }

        echo '<ul class="subsubsub">';

        $links = array();

        foreach ( $statuses as $status => $label ) {
            $url = add_query_arg( 'post_status', $status, admin_url('admin.php?page=fc-recovery-carts-list') );
            $class = ( $status === $current_status ) ? 'current' : '';
    
            $links[] = sprintf( '<li><a href="%s" class="%s">%s <span class="count">(%d)</span></a></li>', esc_url( $url ), esc_attr( $class ), esc_html( $label ), intval( $counts[$status] ?? 0 ) );
        }

        echo implode(' | ', $links);
        echo '</ul>';
    }


    /**
     * Render the navigation tabs and the table
     * 
     * @since 1.1.0
     * @return void
     */
    public function display() {
        $this->display_navigation_tabs();

        parent::display();
    }


    /**
     * Define table columns
     * 
     * @since 1.0.0
     * @version 1.3.0
     * @return array
     */
    public function get_columns() {
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'id'            => __('ID', 'fc-recovery-carts'),
            'notifications' => __( 'Notificações enviadas', 'fc-recovery-carts' ),
            'contact'       => __('Contato', 'fc-recovery-carts'),
            'location'      => __('Localização', 'fc-recovery-carts'),
            'products'      => __('Produtos', 'fc-recovery-carts'),
            'total'         => __('Valor do carrinho', 'fc-recovery-carts'),
            'abandoned'     => __('Data do evento', 'fc-recovery-carts'),
            'status'        => __('Status', 'fc-recovery-carts'),
        );

        return $columns;
    }


    /**
     * Render the checkbox column for bulk selection
     * 
     * @since 1.0.0
     * @param object $item | Cart data
     * @return string
     */
    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="cart_ids[]" value="%s" />', esc_attr( $item->ID ) );
    }


    /**
     * Render the ID column
     * 
     * @since 1.0.0
     * @param object $item | Cart data
     * @return string
     */
    public function column_id( $item ) {
        return sprintf( '#%s', esc_html( $item->ID ) );
    }


    /**
     * Render the notifications column
     *
     * @since 1.3.0
     * @version 1.3.4
     * @param object $item | Cart data
     * @return string
     */
    public function column_notifications( $item ) {
        $notes = get_post_meta( $item->ID, '_fcrc_notifications_sent', true );

        // check if the cart has notifications
        if ( ! is_array( $notes ) || empty( $notes ) ) {
            return '&mdash;';
        }

        $output = '<ul class="fcrc-notifications-list" style="margin: 0;">';

        foreach ( $notes as $note ) {
            if ( ! isset( $note['event_key'] ) || empty( $note['event_key'] ) ) {
                continue;
            }

            $follow_up_events = Admin::get_setting('follow_up_events');
            
            if ( ! is_array( $follow_up_events ) || empty( $follow_up_events ) ) {
                continue;
            }
            
            if ( ! isset( $follow_up_events[$note['event_key']] ) ) {
                $event_title = sprintf( __( 'Evento: %s', 'fc-recovery-carts' ), esc_html( $note['event_key'] ) );
            } else {
                $event_title = isset( $follow_up_events[$note['event_key']]['title'] ) 
                    ? $follow_up_events[$note['event_key']]['title'] 
                    : sprintf( __( 'Evento: %s', 'fc-recovery-carts' ), esc_html( $note['event_key'] ) );
            }
            
            $channel = Helpers::get_formatted_channel_label( $note['channel'] ?? '' );
            $formatted_date = sprintf( __( '%s - %s', 'fc-recovery-carts' ), get_option('date_format'), get_option('time_format') );
            $sent_at = isset( $note['sent_at'] ) && is_numeric( $note['sent_at'] ) 
                ? wp_date( $formatted_date, absint( $note['sent_at'] ) ) 
                : __( 'Data inválida', 'fc-recovery-carts' );
            
            $output .= sprintf(
                __( '<li><strong>%s</strong> via %s: <small>%s</small></li>', 'fc-recovery-carts' ),
                $event_title,
                $channel,
                $sent_at
            );
        }

        $output .= '</ul>';

        return $output;
    }


    /**
     * Render the contact column
     * 
     * @since 1.0.0
     * @version 1.3.0
     * @param object $item | Cart data
     * @return void
     */
    public function column_contact( $item ) {
        $post_id = $item->ID;
        $contact_name = get_post_meta( $post_id, '_fcrc_full_name', true );
        $phone = get_post_meta( $post_id, '_fcrc_cart_phone', true );
        $email = get_post_meta( $post_id, '_fcrc_cart_email', true );
        $user_id = get_post_meta( $post_id, '_fcrc_user_id', true );

        // if is empty user id, but has email, try to get and save
        if ( empty( $user_id ) && $email ) {
            if ( $user = get_user_by( 'email', $email ) ) {
                $user_id = $user->ID;
                update_post_meta( $post_id, '_fcrc_user_id', $user_id );
            }
        }

        // if is empty full name, but has account, fill with first + last name
        if ( ( empty( $contact_name ) || trim( $contact_name ) === '' ) && $user_id ) {
            $first = get_user_meta( $user_id, 'first_name', true );
            $last = get_user_meta( $user_id, 'last_name', true );
            $full_name = sprintf( '%s %s', $first, $last );

            if ( $full_name ) {
                $contact_name = $full_name;
                update_post_meta( $post_id, '_fcrc_full_name', $full_name );
            }
        }

        // set default label
        if ( empty( $contact_name ) || trim( $contact_name ) === '' ) {
            $contact_name = esc_html__( 'Visitante', 'fc-recovery-carts' );
        }

        // if is empty phone but has account, try to get billing_phone or shipping_phone user meta's
        if ( empty( $phone ) && $user_id ) {
            $billing_phone = get_user_meta( $user_id, 'billing_phone', true );
            $shipping_phone = get_user_meta( $user_id, 'shipping_phone', true );
            $use_phone = $billing_phone ?: $shipping_phone;

            if ( $use_phone ) {
                $phone = $use_phone;
                update_post_meta( $post_id, '_fcrc_cart_phone', $use_phone );
            }
        }

        $output = esc_html( $contact_name );

        if ( $phone ) {
            $output .= '<br>' . esc_html( $phone );
        }

        if ( $email ) {
            $output .= '<br><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
        }

        if ( $user_id ) {
            $profile_link = get_edit_user_link( $user_id );
            $output .= sprintf(
                '<br><small><a href="%s" class="button button-small" style="margin-top:1rem;">%s</a></small>',
                esc_url( $profile_link ),
                esc_html__( 'Ver usuário', 'fc-recovery-carts' )
            );
        }

        return $output;
    }


    /**
     * Render the location column from user meta data
     *
     * @since 1.0.0
     * @version 1.3.0
     * @param object $item | Cart data
     * @return string
     */
    public function column_location( $item ) {
        $post_id = $item->ID;
        $city = get_post_meta( $post_id, '_fcrc_location_city', true );
        $state = get_post_meta( $post_id, '_fcrc_location_state', true );
        $zipcode = get_post_meta( $post_id, '_fcrc_location_zipcode', true );
        $country = get_post_meta( $post_id, '_fcrc_location_country_code', true );
        $ip = get_post_meta( $post_id, '_fcrc_location_ip', true );

        // if is empty location data
        if ( empty( $city ) && empty( $state ) && empty( $country ) ) {
            // try to get user if from meta or email
            $user_id = get_post_meta( $post_id, '_fcrc_user_id', true );
            $email = get_post_meta( $post_id, '_fcrc_cart_email', true );

            if ( empty( $user_id ) && $email ) {
                if ( $user = get_user_by( 'email', $email ) ) {
                    $user_id = $user->ID;
                    update_post_meta( $post_id, '_fcrc_user_id', $user_id );
                }
            }

            // if has user, try to get location data from profile
            if ( $user_id ) {
                $billing_city = get_user_meta( $user_id, 'billing_city', true );
                $billing_state = get_user_meta( $user_id, 'billing_state', true );
                $billing_postal = get_user_meta( $user_id, 'billing_postcode', true );
                $billing_country = get_user_meta( $user_id, 'billing_country', true );

                if ( $billing_city ) {
                    $city = $billing_city;
                    update_post_meta( $post_id, '_fcrc_location_city', $billing_city );
                }

                if ( $billing_state ) {
                    $state = $billing_state;
                    update_post_meta( $post_id, '_fcrc_location_state', $billing_state );
                }

                if ( $billing_postal ) {
                    $zipcode = $billing_postal;
                    update_post_meta( $post_id, '_fcrc_location_zipcode', $billing_postal );
                }

                if ( $billing_country ) {
                    $country = $billing_country;
                    update_post_meta( $post_id, '_fcrc_location_country_code', $billing_country );
                }
            }
        }

        // return dash if empty data
        if ( empty( $city ) && empty( $state ) && empty( $country ) ) {
            return '&mdash;';
        }

        // build location string
        if ( $zipcode ) {
            $location = sprintf(
                '%s - %s (%s) - %s',
                esc_html( $city ?: 'N/A' ),
                esc_html( $state ?: 'N/A' ),
                esc_html( $zipcode ),
                esc_html( $country )
            );
        } else {
            $location = sprintf(
                '%s - %s - %s',
                esc_html( $city ?: 'N/A' ),
                esc_html( $state ?: 'N/A' ),
                esc_html( $country )
            );
        }

        // add IP if exists
        if ( $ip ) {
            $location .= sprintf( '<br><small>IP: %s</small>', esc_html( $ip ) );
        }

        return $location;
    }


    /**
     * Render the products column
     *
     * @since 1.0.0
     * @version 1.3.0
     * @param object $item | Cart data
     * @return string
     */
    public function column_products( $item ) {
        $cart_items = get_post_meta( $item->ID, '_fcrc_cart_items', true );
        $cart_items = is_array( $cart_items ) ? $cart_items : array();

        if ( empty( $cart_items ) ) {
            // dash string html
            return '&mdash;';
        }

        $output = '<div class="fcrc-cart-products">';

        foreach ( $cart_items as $item_data ) {
            $image = ! empty( $item_data['image'] ) ? esc_url( $item_data['image'] ) : wc_placeholder_img_src();
            $product_name = isset( $item_data['name'] ) ? esc_html( $item_data['name'] ) : __('Produto desconhecido', 'fc-recovery-carts');
            $quantity = isset( $item_data['quantity'] ) ? intval( $item_data['quantity'] ) : 1;
            $formatted_product = sprintf( __('%s - Qtd: %d', 'fc-recovery-carts'), $product_name, $quantity );

            $output .= sprintf(
                '<div class="fcrc-cart-product fcrc-tooltip" data-text="%s">
                    <img src="%s" alt="%s"/>
                </div>',
                esc_attr( $formatted_product ),
                esc_url( $image ),
                esc_attr( $product_name )
            );
        }

        $output .= '</div>';

        return $output;
    }


    /**
     * Render the cart total column
     * 
     * @since 1.0.0
     * @version 1.3.0
     * @param object $item | Cart data
     * @return string
     */
    public function column_total( $item ) {
        $total = get_post_meta( $item->ID, '_fcrc_cart_total', true );
        $status = get_post_status( $item->ID );

        if ( $status === 'lead' ) {
            // dash string html
            return '&mdash;';
        }

        $output = '<div class="fcrc-cart-total">';
            $output .= $total ? wc_price( $total ) : __('N/A', 'fc-recovery-carts');
        $output .= '</div>';

        return $output;
    }


    /**
     * Render the abandoned date column
     * 
     * @since 1.0.0
     * @version 1.3.2
     * @param object $item | Cart data
     * @return string
     */
    public function column_abandoned( $item ) {
        if ( get_post_status( $item->ID ) === 'shopping' ) {
            return esc_html__( 'Carrinho ainda ativo', 'fc-recovery-carts' );
        }

        $purchased = get_post_meta( $item->ID, '_fcrc_purchased', true );
        $order_date = get_post_meta( $item->ID, '_fcrc_order_date_created', true );
        $order_id = get_post_meta( $item->ID, '_fcrc_order_id', true );
    
        if ( isset( $purchased ) && $purchased ) {
            if ( isset( $order_date ) && isset( $order_id ) ) {
                $order_edit_link = get_edit_post_link( $order_id );
    
                // convert the date to the WordPress timezone
                $formatted_date = wp_date( get_option('date_format') . ' - ' . get_option('time_format'), strtotime( $order_date ) );
    
                $order_text = sprintf( __( 'Pedido #%s', 'fc-recovery-carts' ), esc_html( $order_id ) );
    
                if ( $order_edit_link ) {
                    return sprintf( '<div><a href="%s">%s</a><p>%s</p></div>', esc_url( $order_edit_link ), $order_text, esc_html( $formatted_date ), );
                }
    
                return $order_text;
            } else {
                return esc_html__( 'Pedido realizado', 'fc-recovery-carts' );
            }
        }

        $abandoned_time = get_post_meta( $item->ID, '_fcrc_abandoned_time', true );
    
        if ( ! empty( $abandoned_time ) ) {
            if ( is_numeric( $abandoned_time ) ) {
                $timestamp = absint( $abandoned_time );
            } else {
                $timestamp = strtotime( $abandoned_time );
            }

            if ( $timestamp ) {
                $date_format = get_option( 'date_format' ) . ' - ' . get_option( 'time_format' );

                return esc_html( wp_date( $date_format, $timestamp ) );
            }
        }
    
        // dash string html
        return '&mdash;';
    }


    /**
     * Render the status column
     * 
     * @since 1.0.0
     * @version 1.0.1
     * @param object $item | Cart data
     * @return string
     */
    public function column_status( $item ) {
        $status = get_post_status( $item->ID );

        $statuses = array(
            'lead' => __('Lead', 'fc-recovery-carts'),
            'shopping' => __('Comprando', 'fc-recovery-carts'),
            'abandoned' => __('Abandonado', 'fc-recovery-carts'),
            'order_abandoned' => __('Pedido abandonado', 'fc-recovery-carts'),
            'purchased' => __('Comprou', 'fc-recovery-carts'),
            'recovered' => __('Recuperado', 'fc-recovery-carts'),
            'lost' => __('Perdido', 'fc-recovery-carts'),
        );

        return sprintf( '<span class="status-label %s">%s</span>', esc_attr( $status ), esc_html( $statuses[$status] ?? ucfirst( $status ) ) );
    }


    /**
     * Define bulk actions available in the table
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_bulk_actions() {
        return array(
            'delete' => __('Excluir', 'fc-recovery-carts'),
            'mark_lost' => __('Marcar como perdido', 'fc-recovery-carts'),
            'mark_abandoned' => __('Marcar como abandonado', 'fc-recovery-carts'),
            'mark_recovered' => __('Marcar como recuperado', 'fc-recovery-carts'),
        );
    }


    /**
     * Process bulk actions
     * 
     * @since 1.0.0
     * @version 1.1.0
     * @return void
     */
    public function process_bulk_action() {
        if ( isset( $_POST['cart_ids'] ) && is_array( $_POST['cart_ids'] ) ) {
            $cart_ids = array_map( 'intval', $_POST['cart_ids'] );

            // check current bulk action
            switch ( $this->current_action() ) {
                case 'delete':
                    foreach ( $cart_ids as $cart_id ) {
                        wp_delete_post( $cart_id, true );
                    }

                    break;
                case 'mark_lost':
                    foreach ( $cart_ids as $cart_id ) {
                        wp_update_post( array(
                            'ID' => $cart_id,
                            'post_status' => 'lost',
                        ));

                        /**
                         * Fire a hook when an order is considered lost manually
                         *
                         * @since 1.1.0
                         * @param int $cart_id | The abandoned cart ID
                         */
                        do_action( 'Flexify_Checkout/Recovery_Carts/Cart_Lost_Manually', $cart_id );
                    }

                    break;
                case 'mark_abandoned':
                    foreach ( $cart_ids as $cart_id ) {
                        wp_update_post( array(
                            'ID' => $cart_id,
                            'post_status' => 'abandoned',
                        ));

                        /**
                         * Fire hook when cart is abandoned manually
                         * 
                         * @since 1.1.0
                         * @param int $cart_id | Cart ID | Post ID
                         */
                        do_action( 'Flexify_Checkout/Recovery_Carts/Cart_Abandoned_Manually', $cart_id );
                    }

                    break;
                case 'mark_recovered':
                    foreach ( $cart_ids as $cart_id ) {
                        wp_update_post( array(
                            'ID' => $cart_id,
                            'post_status' => 'recovered',
                        ));

                        /**
                         * Fire a hook when a cart is recovered manually
                         *
                         * @since 1.0.0
                         * @param int $cart_id | The cart ID
                         */
                        do_action( 'Flexify_Checkout/Recovery_Carts/Cart_Recovered_Manually', $cart_id );
                    }
                    
                    break;
            }
        }
    }


    /**
     * Prepare items for display in the table
     * 
     * @since 1.0.0
     * @version 1.4.0
     * @return void
     */
    public function prepare_items() {
        $this->process_bulk_action();
        $per_page = $per_page = $this->get_items_per_page( 'fc_recovery_carts_per_page', 20 );        ;
        $current_page  = $this->get_pagenum();
        $post_status = isset( $_GET['post_status'] ) ? sanitize_key( $_GET['post_status'] ) : 'all';

        // Sets query arguments based on the selected tab
        $args = array(
            'post_type' => 'fc-recovery-carts',
            'posts_per_page' => $per_page,
            'paged' => $current_page,
        );

        // Sets the status of posts based on the selected tab
        if ( $post_status !== 'all' ) {
            $args['post_status'] = $post_status;
        } else {
            $args['post_status'] = array( 'lead', 'shopping', 'abandoned', 'order_abandoned', 'recovered', 'lost', 'purchased' );
        }

        // filter posts based on search
        $search = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';

        if ( $search ) {
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => '_fcrc_full_name',
                    'value' => $search,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => '_fcrc_cart_email',
                    'value' => $search,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => '_fcrc_cart_phone',
                    'value' => $search,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => '_fcrc_cart_items',
                    'value' => $search,
                    'compare' => 'LIKE',
                ),
            );

            // if is numeric, also search by post ID and order ID
            if ( is_numeric( $search ) ) {
                $meta_query[] = array(
                    'key' => '_fcrc_order_id',
                    'value' => $search,
                    'compare' => '=',
                );
                
                $id_query_args = array(
                    'post_type' => 'fc-recovery-carts',
                    'post_status' => $args['post_status'],
                    'fields' => 'ids',
                    'posts_per_page' => -1,
                    'meta_query' => $meta_query,
                );

                $matched_ids = ( new WP_Query( $id_query_args ) )->posts;
                $matched_ids[] = absint( $search );

                $matched_ids = array_values( array_unique( array_filter( $matched_ids ) ) );
                $args['post__in'] = $matched_ids ? $matched_ids : array( 0 );
            } else {
                $args['meta_query'] = $meta_query;
            }
        }

        $query = new WP_Query( $args );
        $total_items = $query->found_posts;
        $this->items = $query->posts;
        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ));
    }
}