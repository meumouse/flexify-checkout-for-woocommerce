<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Admin;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Cron\Scheduler_Manager;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Admin actions class
 * 
 * @since 1.0.0
 * @version 1.3.4
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Admin
 * @author MeuMouse.com
 */
class Admin {

    /**
     * Construct function
     * 
     * @since 1.0.0
     * @version 1.3.2
     * @return void
     */
    public function __construct() {
        // add admin menu (priority 11: after the core Flexify Checkout top-level
        // menu is registered at priority 10, so these submenus attach to it)
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 11 );

        // update default options on admin_init
        add_action( 'admin_init', array( $this, 'update_default_options' ) );

        // render settings tabs
        add_action( 'Flexify_Checkout/Recovery_Carts/Settings/Nav_Tabs', array( $this, 'render_settings_tabs' ) );

        // Register the recovery custom post types / statuses. This class is
        // booted from Init at init:99, so a plain add_action('init', …, 10)
        // would be queued behind a priority that already ran and never fire.
        // Register immediately when init is already underway; otherwise hook it.
        if ( did_action('init') ) {
            $this->register_post_type();
            $this->register_cron_event_cpt();
        } else {
            add_action( 'init', array( $this, 'register_post_type' ) );
            add_action( 'init', array( $this, 'register_cron_event_cpt' ) );
        }

        // flush rewrite rules once per version (never on every request)
        add_action( 'wp_loaded', array( $this, 'maybe_flush_rewrite_rules' ) );

        // display notices on settings pages
        add_action( 'admin_notices', array( $this, 'display_settings_notices' ) );

        // add the "Recuperação" tab to the Vue settings schema
        add_filter( 'Flexify_Checkout/Admin/Settings_Schema', array( $this, 'register_recovery_settings_tab' ) );
    }


    /**
     * Append the "Recuperação" tab to the Vue settings schema.
     *
     * The tab renders the custom "recovery-settings" component, which manages
     * the common recovery settings through its own REST endpoint. Advanced
     * editors (follow-ups, coupons, webhooks) stay on the legacy screen, linked
     * from inside the component.
     *
     * @since 6.0.0
     * @param array $schema Settings schema (list of tabs).
     * @return array
     */
    public function register_recovery_settings_tab( $schema ) {
        if ( ! is_array( $schema ) || ! Helpers::is_pro() ) {
            return $schema;
        }

        $schema[] = array(
            'id' => 'recovery',
            'title' => esc_html__( 'Recuperação', 'fc-recovery-carts' ),
            'icon' => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6a7 7 0 1 1 2.05 4.95l-1.42 1.42A9 9 0 1 0 13 3z"></path><path d="M12 8v5l4 2 .75-1.23-3.25-1.92V8z"></path></svg>',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'recovery-main',
                    'component' => 'recovery-settings',
                ),
            ),
        );

        return $schema;
    }

    
    /**
     * Add the cart recovery pages as submenus of the dedicated Flexify Checkout
     * top-level menu (registered by core Settings_Panel at priority 10).
     *
     * Analytics, Carts and Queue are shown only when the recovery feature is
     * enabled (master toggle) and the license is Pro. The recovery settings
     * screen is a transitional legacy page that will fold into the Vue settings
     * as a "Recuperação" tab in a later phase.
     *
     * @since 1.0.0
     * @version 6.0.0
     * @return void
     */
    public function add_admin_menu() {
        $parent = 'flexify-checkout-for-woocommerce';

        // Recovery requires Pro; the core "Licença" page handles licensing UX.
        if ( ! Helpers::is_pro() ) {
            return;
        }

        // Master toggle: only surface the data pages when the feature is on.
        if ( self::get_switch('enable_cart_recovery') !== 'no' ) {
            global $fc_recovery_carts_hook;

            add_submenu_page(
                $parent, // parent page slug
                esc_html__( 'Análises', 'fc-recovery-carts' ), // page title
                esc_html__( 'Análises', 'fc-recovery-carts' ), // submenu title
                'manage_woocommerce', // user capabilities
                'fc-recovery-carts', // page slug
                array( $this, 'analytics_page' ) // callback
            );

            // all carts list page
            add_submenu_page(
                $parent,
                esc_html__( 'Todos os carrinhos', 'fc-recovery-carts' ),
                esc_html__( 'Todos os carrinhos', 'fc-recovery-carts' ),
                'manage_woocommerce',
                'fc-recovery-carts-list',
                array( $this, 'carts_table_page' )
            );

            // processing queue page
            add_submenu_page(
                $parent,
                esc_html__( 'Fila de processamentos', 'fc-recovery-carts' ),
                esc_html__( 'Fila de processamentos', 'fc-recovery-carts' ),
                'manage_woocommerce',
                'fc-recovery-carts-queue',
                array( $this, 'queue_table_page' )
            );
        }

        // Advanced recovery settings (follow-up events, coupons, payment delays,
        // webhooks). Registered so it stays reachable by URL and from the
        // "Editor avançado" link inside the Vue "Recuperação" tab, but hidden
        // from the menu so only the five requested items show. The common
        // settings now live in Configurações > Recuperação.
        add_submenu_page(
            $parent,
            esc_html__( 'Recuperação de carrinhos', 'fc-recovery-carts' ),
            esc_html__( 'Recuperação de carrinhos', 'fc-recovery-carts' ),
            'manage_woocommerce',
            'fc-recovery-carts-settings',
            array( $this, 'render_settings_page' )
        );

        remove_submenu_page( $parent, 'fc-recovery-carts-settings' );
    }


    /**
     * Render analytics page content
     * 
     * @since 1.3.0
     * @return void
     */
    public function analytics_page() {
        // Analytics is now a Vue SPA route (/analytics). Output the same mount
        // point the settings app uses; Settings_Assets enqueues the bundle on
        // this page slug and opens it on the analytics view.
        ?>
        <div class="wrap flexify-checkout-settings-page">
            <div id="flexify-checkout-settings-app" class="flexify-checkout-settings-app">
                <div class="skeleton-content" style="width: 950px; height: 100px;"></div>
                <div class="skeleton-content" style="width: 100%; height: 360px; margin-top: 2rem;"></div>
            </div>
        </div>
        <?php
    }


    /**
     * Render queue table page
     * 
     * @since 1.3.0
     * @return void
     */
    public function queue_table_page() {
        // "Fila de processamentos" is now a Vue SPA route (/queue) backed by REST.
        ?>
        <div class="wrap flexify-checkout-settings-page">
            <div id="flexify-checkout-settings-app" class="flexify-checkout-settings-app">
                <div class="skeleton-content" style="width: 950px; height: 100px;"></div>
                <div class="skeleton-content" style="width: 100%; height: 420px; margin-top: 2rem;"></div>
            </div>
        </div>
        <?php
    }


    /**
     * Render menu page settings
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_settings_page() {
        include_once( FC_RECOVERY_CARTS_INC . 'Views/Settings.php' );
    }


    /**
     * Render settings page for not Pro users
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_settings_page_required_license() {
        include_once( FC_RECOVERY_CARTS_INC . 'Views/Settings_Info.php' );
    }


    /**
     * Display table with all carts
     * 
     * @since 1.0.0
     * @version 1.3.0
     * @return void
     */
    public function carts_table_page() {
        // "Todos os carrinhos" is now a Vue SPA route (/carts) backed by REST.
        ?>
        <div class="wrap flexify-checkout-settings-page">
            <div id="flexify-checkout-settings-app" class="flexify-checkout-settings-app">
                <div class="skeleton-content" style="width: 950px; height: 100px;"></div>
                <div class="skeleton-content" style="width: 100%; height: 420px; margin-top: 2rem;"></div>
            </div>
        </div>
        <?php
    }


    /**
     * Gets the items from the array and inserts them into the option if it is empty,
     * or adds new items with default value to the option
     * 
     * @since 1.0.0
     * @version 1.3.4
     * @return void
     */
    public function update_default_options() {
        $default_options = ( new Default_Options() )->set_default_options();
        $existing_options = get_option( 'flexify_checkout_recovery_carts_settings', array() );
        
        if ( empty( $existing_options ) ) {
            update_option( 'flexify_checkout_recovery_carts_settings', $default_options );
            return;
        }
        
        $needs_update = false;

        foreach ( $default_options as $key => $default_value ) {
            if ( ! array_key_exists( $key, $existing_options ) ) {
                $existing_options[$key] = $default_value;
                $needs_update = true;
            }
        }
        
        if ( $needs_update ) {
            update_option( 'flexify_checkout_recovery_carts_settings', $existing_options );
        }
    }


    /**
     * Checks if the option exists and returns the indicated array item
     * 
     * @since 1.0.0
     * @param string $key | Option key
     * @return mixed | string or false
     */
    public static function get_setting( $key ) {
        $options = get_option('flexify_checkout_recovery_carts_settings', array());

        // check if array key exists and return key
        if ( isset( $options[$key] ) ) {
            return $options[$key];
        }

        return false;
    }


    /**
     * Get switch option value
     * 
     * @since 1.0.0
     * @param string $key | Option key
     * @return string
     */
    public static function get_switch( $key ) {
        $options = get_option('flexify_checkout_recovery_carts_settings', array());

        // check if array key exists and return key
        if ( isset( $options['toggle_switchs'][$key] ) ) {
            return $options['toggle_switchs'][$key];
        }

        return false;
    }


    /**
     * Render settings nav tabs
     *
     * @since 1.0.0
     */
    public function render_settings_tabs() {
        $tabs = Components::get_settings_tabs();

        foreach ( $tabs as $tab ) {
            printf( '<a href="#%1$s" class="nav-tab">%2$s %3$s</a>', esc_attr( $tab['id'] ), $tab['icon'], $tab['label'] );
        }
    }


    /**
     * Register "fc-recovery-carts" post type
     *
     * @since 1.0.0
     * @version 1.4.1
     * @return void
     */
    public function register_post_type() {
        $labels = array(
            'name'               => _x( 'Carrinhos', 'post type general name', 'fc-recovery-carts' ),
            'singular_name'      => _x( 'Carrinho', 'post type singular name', 'fc-recovery-carts' ),
            'menu_name'          => _x( 'Carrinhos', 'admin menu', 'fc-recovery-carts' ),
            'name_admin_bar'     => _x( 'Carrinho', 'add new on admin bar', 'fc-recovery-carts' ),
            'add_new'            => _x( 'Adicionar novo', 'carrinho', 'fc-recovery-carts' ),
            'add_new_item'       => __( 'Adicionar novo carrinho', 'fc-recovery-carts' ),
            'new_item'           => __( 'Novo carrinho', 'fc-recovery-carts' ),
            'edit_item'          => __( 'Editar carrinho', 'fc-recovery-carts' ),
            'view_item'          => __( 'Ver carrinho', 'fc-recovery-carts' ),
            'all_items'          => __( 'Todos os carrinhos', 'fc-recovery-carts' ),
            'search_items'       => __( 'Pesquisar carrinhos', 'fc-recovery-carts' ),
            'parent_item_colon'  => __( 'Carrinho pai:', 'fc-recovery-carts' ),
            'not_found'          => __( 'Nenhum carrinho encontrado.', 'fc-recovery-carts' ),
            'not_found_in_trash' => __( 'Nenhum carrinho encontrado na lixeira.', 'fc-recovery-carts' )
        );
    
        // This CPT stores internal recovery-cart records only. It is never shown
        // on the frontend — recovery links are query args on the checkout page
        // (?recovery_cart=ID), handled by Helpers::maybe_restore_cart(), and the
        // record is always looked up by ID. So it must not be public, queryable,
        // searchable, or own any rewrite rules. Keeping it public previously
        // leaked its custom statuses into the global WP_Query and registered a
        // malformed "/fc-recovery-carts" archive rule, breaking WooCommerce
        // product/category listings.
        $args = array(
            'labels'              => $labels,
            'description'         => __( 'Registros internos de carrinhos de recuperação.', 'fc-recovery-carts' ),
            'public'              => false,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'show_in_nav_menus'   => false,
            'show_in_rest'        => false,
            'query_var'           => false,
            'capability_type'     => 'post',
            'rewrite'             => false,
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'custom-fields' ),
        );

        register_post_type( 'fc-recovery-carts', $args );

        $custom_statuses = array( 'lead', 'shopping', 'abandoned', 'order_abandoned', 'recovered', 'lost', 'purchased' );

        foreach ( $custom_statuses as $status ) {
            register_post_status( $status, array(
                'label'                     => ucfirst( $status ),
                // Must NOT be public. WordPress appends every public status to
                // the default WP_Query via get_post_stati( array( 'public' => true ) ),
                // so a public status here leaks into WooCommerce product/category
                // queries (lead, shopping, abandoned, ...) and empties the catalog.
                // These statuses are always queried explicitly by post_type, so
                // they don't need to be public to work.
                'public'                    => false,
                'internal'                  => false,
                // Kept searchable so explicit "post_status => 'any'" queries
                // (e.g. fcrc_get_notifications_chart_data) still match them.
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( ucfirst( $status ) . ' <span class="count">(%s)</span>', ucfirst( $status ) . ' <span class="count">(%s)</span>' ),
            ));
        }
    }


    /**
     * Flush rewrite rules once after a plugin version change.
     *
     * Replaces the previous flush_rewrite_rules() call that ran on every `init`
     * request — a documented anti-pattern that, depending on plugin/theme load
     * order, could write an incomplete rule set and drop the WooCommerce
     * product / product_cat archives. Running on `wp_loaded` guarantees every
     * post type and taxonomy is already registered, and the version guard makes
     * it run only once per release: enough to drop the stale "/fc-recovery-carts"
     * archive rules left behind by previous versions.
     *
     * @since 1.4.1
     * @return void
     */
    public function maybe_flush_rewrite_rules() {
        $option_key = 'fcrc_rewrite_rules_version';
        $current_version = defined('FC_RECOVERY_CARTS_VERSION') ? FC_RECOVERY_CARTS_VERSION : '';

        if ( get_option( $option_key ) === $current_version ) {
            return;
        }

        // soft flush: regenerate the rewrite_rules option without touching .htaccess
        flush_rewrite_rules( false );

        update_option( $option_key, $current_version );
    }


    /**
     * Register the Cron Event custom post type
     *
     * @since 1.3.0
     * @return void
     */
    public function register_cron_event_cpt() {
        $labels = array(
            'name'               => __( 'Cron Events', 'fc-recovery-carts' ),
            'singular_name'      => __( 'Cron Event', 'fc-recovery-carts' ),
            'menu_name'          => __( 'Cron Events', 'fc-recovery-carts' ),
            'name_admin_bar'     => __( 'Cron Event', 'fc-recovery-carts' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'capability_type'    => 'post',
            'hierarchical'       => false,
            'supports'           => array( 'title' ),
            'has_archive'        => false,
            'show_in_rest'       => false,
        );
        
        register_post_type( 'fcrc-cron-event', $args );
    }


    /**
     * Display admin notices on plugin settings pages
     *
     * @since 1.3.2
     * @return void
     */
    public function display_settings_notices() {
        $scheduler = self::get_setting('task_scheduler');

        if ( Scheduler_Manager::TYPE_PHP_CRON !== $scheduler || Helpers::has_wp_cli() ) {
            return;
        }

        $class = 'notice notice-warning is-dismissible';
        $message = __( 'O modo PHP-Cron requer o WP-CLI instalado no servidor. Instale o WP-CLI ou altere o agendador para WP-Cron.', 'fc-recovery-carts' );

        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
    }
}