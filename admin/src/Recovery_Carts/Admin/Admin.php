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

        // inject the recovery settings card into the "Carrinho" tab of the Vue settings
        add_filter( 'Flexify_Checkout/Admin/Settings_Schema', array( $this, 'register_recovery_settings_tab' ) );
    }


    /**
     * Inject the recovery settings card into the "Carrinho" tab of the Vue settings.
     *
     * The card renders the custom "recovery-settings" component, which manages
     * the cart recovery settings through its own REST endpoint. It lives under
     * the existing "Carrinho" tab instead of a dedicated tab; the data pages
     * (Análise, Carrinhos, Fila) remain as their own top-level submenus.
     *
     * @since 6.0.0
     * @param array $schema Settings schema (list of tabs).
     * @return array
     */
    public function register_recovery_settings_tab( $schema ) {
        if ( ! is_array( $schema ) || ! Helpers::is_pro() ) {
            return $schema;
        }

        $card = array(
            'id' => 'cart-recovery',
            'title' => esc_html__( 'Recuperação de carrinhos', 'flexify-checkout-for-woocommerce' ),
            'description' => esc_html__( 'Configure o rastreamento de carrinhos abandonados, mensagens de follow-up e o modal de captura de leads.', 'flexify-checkout-for-woocommerce' ),
            'component' => 'recovery-settings',
        );

        foreach ( $schema as $index => $tab ) {
            if ( isset( $tab['id'] ) && 'cart' === $tab['id'] ) {
                if ( ! isset( $schema[ $index ]['cards'] ) || ! is_array( $schema[ $index ]['cards'] ) ) {
                    $schema[ $index ]['cards'] = array();
                }

                $schema[ $index ]['cards'][] = $card;

                return $schema;
            }
        }

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

            // Positions order the whole top-level menu together with the core
            // Settings_Panel items (Aplicativos=4, Configurações=5, Licença=6).
            // Page slugs are kept for backward compatibility; only labels change.
            add_submenu_page(
                $parent, // parent page slug
                esc_html__( 'Análise', 'flexify-checkout-for-woocommerce' ), // page title
                esc_html__( 'Análise', 'flexify-checkout-for-woocommerce' ), // submenu title
                'manage_woocommerce', // user capabilities
                'fc-recovery-carts', // page slug
                array( $this, 'analytics_page' ), // callback
                1 // position
            );

            // all carts list page
            add_submenu_page(
                $parent,
                esc_html__( 'Carrinhos Abandonados', 'flexify-checkout-for-woocommerce' ),
                esc_html__( 'Carrinhos Abandonados', 'flexify-checkout-for-woocommerce' ),
                'manage_woocommerce',
                'fc-recovery-carts-list',
                array( $this, 'carts_table_page' ),
                2 // position
            );

            // processing queue page
            add_submenu_page(
                $parent,
                esc_html__( 'Fila de Processamentos', 'flexify-checkout-for-woocommerce' ),
                esc_html__( 'Fila de Processamentos', 'flexify-checkout-for-woocommerce' ),
                'manage_woocommerce',
                'fc-recovery-carts-queue',
                array( $this, 'queue_table_page' ),
                3 // position
            );

            // Analytics, Carts and Queue stay registered as visible submenu items
            // of the Flexify Checkout top-level menu (positions 1-3). They are also
            // reachable as sub-views of the Vue "Recuperação" settings tab, but the
            // dedicated menu entries remain so each page has its own slug-based deep
            // link in the WordPress menu.
        }

        // Advanced recovery settings (follow-up events, coupons, payment delays,
        // webhooks). Registered so it stays reachable by URL and from the
        // "Editor avançado" link inside the Vue "Recuperação" tab, but hidden
        // from the menu so only the five requested items show. The common
        // settings now live in Configurações > Recuperação.
        add_submenu_page(
            $parent,
            esc_html__( 'Recuperação de carrinhos', 'flexify-checkout-for-woocommerce' ),
            esc_html__( 'Recuperação de carrinhos', 'flexify-checkout-for-woocommerce' ),
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
        // Forward-compat read-through: recovery settings are migrating into the
        // unified main option under a "recovery" namespace. Prefer the unified
        // value when present, then fall back to the legacy recovery option.
        // Writes still target the legacy option until a later migration phase,
        // so today this simply falls through to the legacy read unchanged.
        $unified = get_option('flexify_checkout_settings', array());

        if ( is_array( $unified ) && isset( $unified['recovery'][$key] ) ) {
            return $unified['recovery'][$key];
        }

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
        // Forward-compat read-through (see get_setting): prefer the unified
        // option's recovery namespace, then fall back to the legacy option.
        $unified = get_option('flexify_checkout_settings', array());

        if ( is_array( $unified ) && isset( $unified['recovery']['toggle_switchs'][$key] ) ) {
            return $unified['recovery']['toggle_switchs'][$key];
        }

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
            'name'               => _x( 'Carrinhos', 'post type general name', 'flexify-checkout-for-woocommerce' ),
            'singular_name'      => _x( 'Carrinho', 'post type singular name', 'flexify-checkout-for-woocommerce' ),
            'menu_name'          => _x( 'Carrinhos', 'admin menu', 'flexify-checkout-for-woocommerce' ),
            'name_admin_bar'     => _x( 'Carrinho', 'add new on admin bar', 'flexify-checkout-for-woocommerce' ),
            'add_new'            => _x( 'Adicionar novo', 'carrinho', 'flexify-checkout-for-woocommerce' ),
            'add_new_item'       => __( 'Adicionar novo carrinho', 'flexify-checkout-for-woocommerce' ),
            'new_item'           => __( 'Novo carrinho', 'flexify-checkout-for-woocommerce' ),
            'edit_item'          => __( 'Editar carrinho', 'flexify-checkout-for-woocommerce' ),
            'view_item'          => __( 'Ver carrinho', 'flexify-checkout-for-woocommerce' ),
            'all_items'          => __( 'Todos os carrinhos', 'flexify-checkout-for-woocommerce' ),
            'search_items'       => __( 'Pesquisar carrinhos', 'flexify-checkout-for-woocommerce' ),
            'parent_item_colon'  => __( 'Carrinho pai:', 'flexify-checkout-for-woocommerce' ),
            'not_found'          => __( 'Nenhum carrinho encontrado.', 'flexify-checkout-for-woocommerce' ),
            'not_found_in_trash' => __( 'Nenhum carrinho encontrado na lixeira.', 'flexify-checkout-for-woocommerce' )
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
            'description'         => __( 'Registros internos de carrinhos de recuperação.', 'flexify-checkout-for-woocommerce' ),
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
            'name'               => __( 'Cron Events', 'flexify-checkout-for-woocommerce' ),
            'singular_name'      => __( 'Cron Event', 'flexify-checkout-for-woocommerce' ),
            'menu_name'          => __( 'Cron Events', 'flexify-checkout-for-woocommerce' ),
            'name_admin_bar'     => __( 'Cron Event', 'flexify-checkout-for-woocommerce' ),
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
        $message = __( 'O modo PHP-Cron requer o WP-CLI instalado no servidor. Instale o WP-CLI ou altere o agendador para WP-Cron.', 'flexify-checkout-for-woocommerce' );

        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
    }
}