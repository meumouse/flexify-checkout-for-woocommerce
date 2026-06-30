<?php
/**
 * React thank-you (order-received) page template.
 *
 * Renders the mount root for the React checkout app in thank-you mode. The app
 * reads the localized `flexify_react_checkout` object (mode + order payload +
 * captured WooCommerce thank-you hooks; see Core\Assets and Checkout\Headless_Data)
 * and renders the Swift thank-you experience.
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
         * Strip the checkout `?step` parameter from the order-received URL before
         * anything paints, so the thank-you page never displays it (it can linger
         * from the checkout step or a gateway that preserves query args).
         */
        ?>
        <script>
        ( function() {
            try {
                var url = new URL( window.location.href );

                if ( url.searchParams.has('step') ) {
                    url.searchParams.delete('step');
                    window.history.replaceState( window.history.state, '', url.toString() );
                }
            } catch ( e ) {}
        } )();
        </script>

        <?php
        /**
         * Before the React checkout layout.
         *
         * @since 6.0.0
         */
        do_action('flexify_checkout_before_layout'); ?>

        <div class="flexify-checkout flexify-checkout--react flexify-checkout--react-thankyou">
            <div id="flexify-react-checkout">
                <noscript><?php esc_html_e( 'JavaScript must be enabled to view your order confirmation.', 'flexify-checkout-for-woocommerce' ); ?></noscript>

                <?php
                /**
                 * Loading skeleton shown before the React app mounts. React
                 * replaces it on mount. Mirrors the thank-you column layout.
                 */
                ?>
                <div class="mx-auto max-w-[640px] px-4 pt-10" aria-hidden="true">
                    <div class="flex flex-col items-center gap-4">
                        <div class="fc-skeleton h-16 w-16 !rounded-full"></div>
                        <div class="fc-skeleton h-6 w-64"></div>
                        <div class="fc-skeleton h-4 w-80"></div>
                        <div class="fc-skeleton mt-2 h-12 w-56"></div>
                    </div>

                    <div class="mt-8 flex flex-col gap-4">
                        <div class="fc-skeleton h-40 w-full !rounded-2xl"></div>
                        <div class="fc-skeleton h-48 w-full !rounded-2xl"></div>
                        <div class="fc-skeleton h-56 w-full !rounded-2xl"></div>
                    </div>
                </div>
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
