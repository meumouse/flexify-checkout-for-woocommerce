<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Views;

use WP_List_Table;
use WP_Query;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( ! class_exists('WP_List_Table') ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Queue Cron events table class
 *
 * @since 1.3.0
 * @version 1.3.5
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Views
 * @author MeuMouse.com
 */
class Queue_Table extends WP_List_Table {

    /**
     * Constructor
     * 
     * @since 1.3.0
     * @return void
     */
    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Cron Event', 'fc-recovery-carts' ),
            'plural' => __( 'Cron Events', 'fc-recovery-carts' ),
            'ajax' => false,
        ));
        
        // Process single actions in constructor to handle before output
        $this->process_single_action();
    }


    /**
     * Display the table page
     * 
     * @since 1.3.0
     * @version 1.3.5
     * @return void
     */
    public function display_page() {
        // Process single actions first
        $this->process_single_action();
        
        // Show messages
        if ( isset( $_GET['message'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( urldecode( $_GET['message'] ) ) . '</p></div>';
        }
        
        echo '<div class="wrap"><h1 class="wp-heading-inline">' . __( 'Gerenciar fila de processamentos', 'fc-recovery-carts' ) . '</h1>';

        $cleanup_url = wp_nonce_url(
            add_query_arg( array(
                'action' => 'cleanup_expired',
                'page' => $_REQUEST['page'] ?? '',
            ), admin_url('admin.php') ),
            'fcrc_cleanup_expired_queue'
        );

        printf(
            ' <a href="%s" class="page-title-action" onclick="return confirm(\'%s\');">%s</a>',
            esc_url( $cleanup_url ),
            esc_js( __( 'Remover eventos vencidos e órfãos da fila?', 'fc-recovery-carts' ) ),
            esc_html__( 'Limpar eventos vencidos', 'fc-recovery-carts' )
        );

        echo '<form method="post">';
            wp_nonce_field( 'bulk-' . $this->_args['plural'], '_wpnonce' );
            echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ?? '' ) . '" />';
            echo '<input type="hidden" name="post_status" value="' . esc_attr( $_REQUEST['post_status'] ?? '' ) . '" />';

            $this->search_box( __( 'Buscar eventos', 'fc-recovery-carts' ), 'fcrc_cart_search' );
            $this->display();
        echo '</form></div>';
    }


    /**
     * Render the table
     * 
     * @since 1.3.0
     * @return void
     */
    public function display() {
        parent::display();
    }


    /**
     * Get columns
     *
     * @since 1.3.0
     * @return array
     */
    public function get_columns() {
        return array(
            'cb'            => '<input type="checkbox" />',
            'id'            => __( 'ID do carrinho', 'fc-recovery-carts' ),
            'contact'       => __('Contato', 'fc-recovery-carts'),
            'event_name'    => __( 'Evento', 'fc-recovery-carts' ),
            'scheduled_at'  => __( 'Data e horário do evento', 'fc-recovery-carts' ),
        );
    }


    /**
     * Render checkbox column
     * 
     * @since 1.3.0
     * @version 1.3.5
     * @param object $item | Cron event data
     * @return string
     */
    public function column_cb( $item ) {
        $cart_id = get_post_meta( $item->ID, '_fcrc_cart_id', true );
        $event_key = get_post_meta( $item->ID, '_fcrc_cron_event_key', true );
        
        $title = sprintf(
            __( 'Carrinho #%d - Evento: %s', 'fc-recovery-carts' ),
            $cart_id,
            $this->column_event_name( $item )
        );
        
        return sprintf(
            '<input type="checkbox" name="event_ids[]" value="%d" title="%s" />',
            absint( $item->ID ),
            esc_attr( $title )
        );
    }


    /**
     * Render ID column
     * 
     * @since 1.3.0
     * @param object $item | Cron event data
     * @return string
     */
    public function column_id( $item ) {
        $actions = $this->get_row_actions( $item );
        
        return sprintf( '#%d %s', 
            absint( get_post_meta( $item->ID, '_fcrc_cart_id', true ) ),
            $this->row_actions( $actions )
        );
    }


    /**
     * Render the contact column
     * 
     * @since 1.3.0
     * @param object $item | Cart data
     * @return string
     */
    public function column_contact( $item ) {
        $cart_id = get_post_meta( $item->ID, '_fcrc_cart_id', true );
        $contact_name = get_post_meta( $cart_id, '_fcrc_full_name', true );
        $phone = get_post_meta( $cart_id, '_fcrc_cart_phone', true );
        $email = get_post_meta( $cart_id, '_fcrc_cart_email', true );
        $user_id = get_post_meta( $cart_id, '_fcrc_user_id', true );

        // if is empty user id, but has email, try to get and save
        if ( empty( $user_id ) && $email ) {
            if ( $user = get_user_by( 'email', $email ) ) {
                $user_id = $user->ID;
                update_post_meta( $cart_id, '_fcrc_user_id', $user_id );
            }
        }

        // if is empty full name, but has account, fill with first + last name
        if ( ( empty( $contact_name ) || trim( $contact_name ) === '' ) && $user_id ) {
            $first = get_user_meta( $user_id, 'first_name', true );
            $last = get_user_meta( $user_id, 'last_name', true );
            $full_name = sprintf( '%s %s', $first, $last );

            if ( $full_name ) {
                $contact_name = $full_name;
                update_post_meta( $cart_id, '_fcrc_full_name', $full_name );
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
                update_post_meta( $cart_id, '_fcrc_cart_phone', $use_phone );
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
     * Render event name column
     * 
     * @since 1.3.0
     * @param object $item | Cron event data
     * @return string
     */
    public function column_event_name( $item ) {
        $key = get_post_meta( $item->ID, '_fcrc_cron_event_key', true );

        // Map keys to labels
        $map = array(
            'fcrc_send_follow_up_message'  => __( 'Follow up', 'fc-recovery-carts' ),
            'fcrc_check_final_cart_status' => __( 'Aguardando pagamento', 'fc-recovery-carts' ),
        );

        return esc_html( $map[ $key ] ?? $key );
    }


    /**
     * Render scheduled_at column
     * 
     * @since 1.3.0
     * @version 1.3.2
     * @param object $item | Cron event data
     * @return string
     */
    public function column_scheduled_at( $item ) {
        $timestamp = get_post_meta( $item->ID, '_fcrc_cron_scheduled_at', true );

        if ( empty( $timestamp ) ) {
            return '&mdash;';
        }

        $timestamp = absint( $timestamp );

        return esc_html( wp_date( get_option('date_format') . ' ' . get_option('time_format'), $timestamp ) );
    }
    

    /**
     * Prepare items for display
     * 
     * @since 1.3.0
     * @return void
     */
    public function prepare_items() {
        $this->process_bulk_action();
        $per_page = $this->get_items_per_page( 'cron_events_per_page', 20 );
        $current_page = $this->get_pagenum();

        // Query cron-event posts
        $args = array(
            'post_type' => 'fcrc-cron-event',
            'posts_per_page' => $per_page,
            'paged' => $current_page,
            'post_status' => 'publish',
        );

        $query = new WP_Query( $args );

        $this->items = $query->posts;
        $this->_column_headers = array( $this->get_columns(), array(), array() );

        $this->set_pagination_args( array(
            'total_items' => $query->found_posts,
            'per_page' => $per_page,
            'total_pages' => $query->max_num_pages,
        ));
    }


    /**
     * Define bulk actions available in the table
     * 
     * @since 1.3.0
     * @version 1.3.5
     * @return array
     */
    public function get_bulk_actions() {
        return array(
            'run_now' => __('Disparar agora', 'fc-recovery-carts'),
            'cancel'  => __('Cancelar', 'fc-recovery-carts'),
            'delete' => __('Excluir', 'fc-recovery-carts'),
        );
    }


    /**
     * Get row actions for a specific item
     * 
     * @since 1.3.5
     * @param object $item | The event item
     * @return array
     */
    private function get_row_actions( $item ) {
        $actions = array();
        
        $run_url = wp_nonce_url(
            add_query_arg( array(
                'action' => 'run_now',
                'event_id' => $item->ID,
                'page' => $_REQUEST['page'] ?? '',
            ), admin_url('admin.php') ),
            'run_event_' . $item->ID
        );
        
        $cancel_url = wp_nonce_url(
            add_query_arg( array(
                'action' => 'cancel',
                'event_id' => $item->ID,
                'page' => $_REQUEST['page'] ?? '',
            ), admin_url('admin.php') ),
            'cancel_event_' . $item->ID
        );
        
        $delete_url = wp_nonce_url(
            add_query_arg( array(
                'action' => 'delete',
                'event_id' => $item->ID,
                'page' => $_REQUEST['page'] ?? '',
            ), admin_url('admin.php') ),
            'delete_event_' . $item->ID
        );
        
        $actions['run_now'] = sprintf(
            '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
            esc_url( $run_url ),
            esc_js( __( 'Tem certeza que deseja disparar este evento agora?', 'fc-recovery-carts' ) ),
            __( 'Disparar agora', 'fc-recovery-carts' )
        );
        
        $actions['cancel'] = sprintf(
            '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
            esc_url( $cancel_url ),
            esc_js( __( 'Tem certeza que deseja cancelar este evento?', 'fc-recovery-carts' ) ),
            __( 'Cancelar', 'fc-recovery-carts' )
        );
        
        $actions['delete'] = sprintf(
            '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
            esc_url( $delete_url ),
            esc_js( __( 'Tem certeza que deseja excluir este evento? Esta ação não pode ser desfeita.', 'fc-recovery-carts' ) ),
            __( 'Excluir', 'fc-recovery-carts' )
        );
        
        return $actions;
    }


    /**
     * Process bulk actions
     * 
     * @since 1.3.0
     * @version 1.3.5
     * @return void
     */
    public function process_bulk_action() {
        if ( $this->current_action() && isset( $_POST['event_ids'] ) && is_array( $_POST['event_ids'] ) ) {
            // check nonce for bulk actions
            if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) {
                wp_die( __( 'Não autorizado.', 'fc-recovery-carts' ) );
            }
            
            $action = $this->current_action();
            $event_ids = array_map( 'intval', $_POST['event_ids'] );
            $count = count( $event_ids );
            $message = '';

            switch ( $action ) {
                case 'run_now':
                    foreach ( $event_ids as $event_id ) {
                        $this->run_event_now( $event_id );
                    }

                    $message = sprintf( 
                        _n( '%d evento disparado com sucesso.', '%d eventos disparados com sucesso.', $count, 'fc-recovery-carts' ), 
                        $count 
                    );

                    break;

                case 'cancel':
                    foreach ( $event_ids as $event_id ) {
                        $this->cancel_event( $event_id );
                    }

                    $message = sprintf( 
                        _n( '%d evento cancelado com sucesso.', '%d eventos cancelados com sucesso.', $count, 'fc-recovery-carts' ), 
                        $count 
                    );

                    break;

                case 'delete':
                    foreach ( $event_ids as $event_id ) {
                        wp_delete_post( $event_id, true );
                    }

                    $message = sprintf( 
                        _n( '%d evento excluído com sucesso.', '%d eventos excluídos com sucesso.', $count, 'fc-recovery-carts' ), 
                        $count 
                    );

                    break;
            }
            
            if ( $message ) {
                // redirect with message
                $redirect_url = add_query_arg( 
                    array( 
                        'page' => $_REQUEST['page'] ?? '',
                        'message' => urlencode( $message ),
                    ), 
                    admin_url('admin.php') 
                );

                wp_redirect( $redirect_url );
                exit;
            }
        }
    }


    /**
     * Run a cron event immediately
     * 
     * @since 1.3.5
     * @param int $event_id | The cron event post ID
     * @return bool
     */
    private function run_event_now( $event_id ) {
        $hook = get_post_meta( $event_id, '_fcrc_cron_event_key', true );
        
        if ( ! $hook ) {
            return false;
        }
        
        $cart_id = get_post_meta( $event_id, '_fcrc_cart_id', true );
        $args = get_post_meta( $event_id, '_fcrc_cron_args', true );
        
        if ( ! is_array( $args ) ) {
            $args = array();
        }
        
        $args['cron_post_id'] = $event_id;

        // signal downstream handlers (follow up sender, status check) that this
        // dispatch was triggered manually by the admin and must bypass
        // send window / recent purchase / late purchase guards
        $args['force'] = true;

        // Execute the hook immediately
        do_action_ref_array( $hook, array_values( $args ) );
        
        // Delete the event if it wasn't already deleted by the hook
        if ( get_post_status( $event_id ) === 'publish' ) {
            wp_delete_post( $event_id, true );
        }
        
        // Log the action
        if ( $cart_id ) {
            $log_message = sprintf( 
                __( 'Evento %s disparado manualmente pelo admin.', 'fc-recovery-carts' ),
                $hook
            );
        }
        
        return true;
    }


    /**
     * Cancel a cron event
     * 
     * @since 1.3.5
     * @param int $event_id | The cron event post ID
     * @return bool
     */
    private function cancel_event( $event_id ) {
        $cart_id = get_post_meta( $event_id, '_fcrc_cart_id', true );
        
        // Move to trash or delete permanently
        $result = wp_delete_post( $event_id, true );
        
        // Log the action
        if ( $cart_id && $result ) {
            $hook = get_post_meta( $event_id, '_fcrc_cron_event_key', true );

            $log_message = sprintf( 
                __( 'Evento %s cancelado manualmente pelo admin.', 'fc-recovery-carts' ),
                $hook
            );
        }
        
        return (bool) $result;
    }


    /**
     * Process individual actions (single row actions)
     * 
     * @since 1.3.5
     * @return void
     */
    public function process_single_action() {
        // cleanup_expired does not target a single event_id; handle it separately first
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'cleanup_expired' && isset( $_GET['_wpnonce'] ) ) {
            if ( ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'fcrc_cleanup_expired_queue' ) ) {
                wp_die( __( 'Não autorizado.', 'fc-recovery-carts' ) );
            }

            $removed = \MeuMouse\Flexify_Checkout\Recovery_Carts\Cron\Queue_Processor::cleanup_expired_events();
            $message = sprintf(
                _n( '%d evento vencido removido da fila.', '%d eventos vencidos removidos da fila.', $removed, 'fc-recovery-carts' ),
                $removed
            );

            $redirect_url = remove_query_arg( array( 'action', '_wpnonce' ) );
            $redirect_url = add_query_arg( 'message', urlencode( $message ), $redirect_url );

            wp_safe_redirect( $redirect_url );
            exit;
        }

        if ( ! isset( $_GET['action'] ) || ! isset( $_GET['event_id'] ) || ! isset( $_GET['_wpnonce'] ) ) {
            return;
        }
        
        $action = sanitize_text_field( $_GET['action'] );
        $event_id = intval( $_GET['event_id'] );
        $nonce = sanitize_text_field( $_GET['_wpnonce'] );
        
        // check nonce based on action
        switch ( $action ) {
            case 'run_now':
                if ( ! wp_verify_nonce( $nonce, 'run_event_' . $event_id ) ) {
                    wp_die( __( 'Não autorizado.', 'fc-recovery-carts' ) );
                }

                $this->run_event_now( $event_id );
                $message = __( 'Evento disparado com sucesso.', 'fc-recovery-carts' );

                break;
                
            case 'cancel':
                if ( ! wp_verify_nonce( $nonce, 'cancel_event_' . $event_id ) ) {
                    wp_die( __( 'Não autorizado.', 'fc-recovery-carts' ) );
                }

                $this->cancel_event( $event_id );
                $message = __( 'Evento cancelado com sucesso.', 'fc-recovery-carts' );

                break;
                
            case 'delete':
                if ( ! wp_verify_nonce( $nonce, 'delete_event_' . $event_id ) ) {
                    wp_die( __( 'Não autorizado.', 'fc-recovery-carts' ) );
                }

                wp_delete_post( $event_id, true );
                $message = __( 'Evento excluído com sucesso.', 'fc-recovery-carts' );

                break;
                
            default:
                return;
        }
        
        // redirect with message
        $redirect_url = remove_query_arg( array( 'action', 'event_id', '_wpnonce' ) );
        $redirect_url = add_query_arg( 'message', urlencode( $message ), $redirect_url );
        
        wp_safe_redirect( $redirect_url );
        exit;
    }
}