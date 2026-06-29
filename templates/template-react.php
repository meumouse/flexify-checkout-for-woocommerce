<?php
/**
 * React checkout page template.
 *
 * Renders only the mount root for the React checkout app. The app bootstraps
 * from the localized `flexify_react_checkout` object (see Core\Assets) and
 * talks to the WooCommerce Store API + the flexify-checkout/v1 endpoints.
 *
 * @since 6.0.0
 * @package MeuMouse.com
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js no-svg">
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <?php wp_head(); ?>
    </head>

    <body <?php body_class(); ?>>
        <?php
        /**
         * Before the React checkout layout.
         *
         * @since 6.0.0
         */
        do_action('flexify_checkout_before_layout'); ?>

        <div class="flexify-checkout flexify-checkout--react">
            <div id="flexify-react-checkout">
                <noscript><?php esc_html_e( 'JavaScript must be enabled to complete the purchase.', 'flexify-checkout-for-woocommerce' ); ?></noscript>
            </div>
        </div>

        <?php
        /**
         * After the React checkout layout.
         *
         * @since 6.0.0
         */
        do_action('flexify_checkout_after_layout');

        wp_footer(); ?>
    </body>
</html>
