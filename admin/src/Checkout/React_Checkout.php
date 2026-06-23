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
    }


    /**
     * Swap the checkout template for the React page template.
     *
     * Only applies on the checkout step itself — the thank-you and order-pay
     * pages keep the legacy template.
     *
     * @since 6.0.0
     * @param string $template Resolved template path.
     * @return string
     */
    public function load_react_template( $template ) {
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

        return $classes;
    }


    /**
     * Whether the React checkout should render for the current request.
     *
     * @since 6.0.0
     * @return bool
     */
    public function should_render() {
        return function_exists('is_flexify_checkout')
            && is_flexify_checkout()
            && Helpers::is_react_checkout_enabled();
    }
}
