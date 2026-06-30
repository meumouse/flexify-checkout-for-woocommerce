<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

use MeuMouse\Flexify_Checkout\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Serve the optional React checkout frontend.
 *
 * When the React checkout is enabled (Helpers::is_react_checkout_enabled),
 * the checkout page renders a minimal React mount root instead of the classic
 * server-rendered steps. Asset enqueueing is handled in Core\Assets, which
 * branches on the same helper. The legacy checkout remains the fallback for
 * the thank-you / order-pay pages and whenever the React mode is off.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Checkout
 * @author MeuMouse.com
 */
class React_Checkout {

    /**
     * Construct function
     *
     * @since 6.0.0
     * @return void
     */
    public function __construct() {
        // Override the page template after Checkout\Themes (priority 100) so the
        // IS_FLEXIFY_CHECKOUT constant it defines is already set.
        add_filter( 'template_include', array( $this, 'load_react_template' ), 101 );

        // Mark the body so styles can target the React checkout.
        add_filter( 'body_class', array( $this, 'add_body_class' ) );

        // Add a "Edit checkout" shortcut to the admin bar, on the checkout page.
        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_link' ), 100 );

        // Hide the WP admin bar inside the live builder preview so the iframe
        // shows a clean checkout.
        add_action( 'wp', array( $this, 'maybe_hide_admin_bar' ) );
    }


    /**
     * Hide the admin bar when rendering the builder preview iframe.
     *
     * @since 6.0.0
     * @return void
     */
    public function maybe_hide_admin_bar() {
        if ( Helpers::is_builder_preview() ) {
            add_filter( 'show_admin_bar', '__return_false' );
        }
    }


    /**
     * Add a top admin-bar shortcut that opens the live builder, shown only on
     * the checkout page to users who can manage WooCommerce.
     *
     * @since 6.0.0
     * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
     * @return void
     */
    public function add_admin_bar_link( $wp_admin_bar ) {
        if ( is_admin() || ! function_exists('is_flexify_checkout') || ! is_flexify_checkout() ) {
            return;
        }

        if ( ! current_user_can('manage_woocommerce') ) {
            return;
        }

        $url = add_query_arg(
            array(
                'page' => 'flexify-checkout-for-woocommerce',
                'tab' => 'fields',
                'flexify_open_builder' => '1',
            ),
            admin_url('admin.php')
        );

        $wp_admin_bar->add_node( array(
            'id' => 'flexify-checkout-editor',
            'title' => '<span class="ab-icon dashicons dashicons-edit" style="top:2px;"></span>' . esc_html__( 'Flexify Checkout Editor', 'flexify-checkout-for-woocommerce' ),
            'href' => esc_url( $url ),
            'meta' => array(
                'title' => __( 'Open the checkout builder', 'flexify-checkout-for-woocommerce' ),
            ),
        ) );
    }


    /**
     * Swap the checkout template for the React page template.
     *
     * Applies on the checkout step and — when the thank-you template is enabled —
     * on the order-received page, which renders the React thank-you instead of
     * the classic template. The order-pay page keeps the legacy template.
     *
     * @since 6.0.0
     * @param string $template Resolved template path.
     * @return string
     */
    public function load_react_template( $template ) {
        if ( $this->should_render_thankyou() ) {
            $react_template = FLEXIFY_CHECKOUT_PATH . 'templates/template-react-thankyou.php';

            return file_exists( $react_template ) ? $react_template : $template;
        }

        if ( ! $this->should_render() ) {
            return $template;
        }

        $react_template = FLEXIFY_CHECKOUT_PATH . 'templates/template-react.php';

        return file_exists( $react_template ) ? $react_template : $template;
    }


    /**
     * Add a body class on the React checkout.
     *
     * @since 6.0.0
     * @param array $classes Body classes.
     * @return array
     */
    public function add_body_class( $classes ) {
        if ( $this->should_render() ) {
            $classes[] = 'flexify-react-checkout-enabled';
        }

        if ( $this->should_render_thankyou() ) {
            $classes[] = 'flexify-react-checkout-enabled';
            $classes[] = 'flexify-react-thankyou';
        }

        return $classes;
    }


    /**
     * Whether the React thank-you (order-received) page should render.
     *
     * Requires the React (Swift) checkout to be active and the custom thank-you
     * template to be enabled — matching the gating of the classic Flexify
     * thank-you page, so toggling the setting governs both render paths.
     *
     * @since 6.0.0
     * @return bool
     */
    public function should_render_thankyou() {
        return Helpers::is_react_checkout_enabled() && Helpers::is_thankyou_page();
    }


    /**
     * Resolve the order for the current order-received request.
     *
     * @since 6.0.0
     * @return \WC_Order|false
     */
    public static function get_current_order() {
        $order_id = absint( get_query_var('order-received') );

        if ( ! $order_id && isset( $_GET['order-received'] ) ) {
            $order_id = absint( wp_unslash( $_GET['order-received'] ) );
        }

        if ( ! $order_id ) {
            return false;
        }

        $order = wc_get_order( $order_id );

        return $order instanceof \WC_Order ? $order : false;
    }


    /**
     * Whether the React checkout should render for the current request.
     *
     * @since 6.0.0
     * @return bool
     */
    public function should_render() {
        if ( ! function_exists('is_flexify_checkout') || ! is_flexify_checkout() ) {
            return false;
        }

        return Helpers::is_react_checkout_enabled() || Helpers::is_builder_preview();
    }
}
