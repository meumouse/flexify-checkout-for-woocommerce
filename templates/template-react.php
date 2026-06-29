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

                <?php
                /**
                 * Loading skeleton shown before the React app mounts (the bundle is
                 * deferred to the footer, while main.css loads in the head, so this
                 * paints immediately with its shimmer). React replaces it on mount.
                 * Mirrors CheckoutSkeleton.jsx — keep the two visually in sync.
                 */
                ?>
                <div class="pb-10" aria-hidden="true">
                    <div class="mx-auto grid max-w-6xl grid-cols-1 gap-8 px-4 pt-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:px-8">
                        <div>
                            <div class="mb-10 flex w-full max-w-[520px] items-center">
                                <div class="flex min-w-0 flex-1 items-center">
                                    <div class="flex shrink-0 items-center gap-3">
                                        <div class="fc-skeleton h-8 w-8 shrink-0 !rounded-full"></div>
                                        <div class="flex flex-col gap-1.5">
                                            <div class="fc-skeleton h-2 w-10"></div>
                                            <div class="fc-skeleton h-3 w-16"></div>
                                        </div>
                                    </div>
                                    <span class="mx-3 min-w-[1rem] flex-1 border-t border-dashed border-slate-200"></span>
                                </div>
                                <div class="flex min-w-0 flex-1 items-center">
                                    <div class="flex shrink-0 items-center gap-3">
                                        <div class="fc-skeleton h-8 w-8 shrink-0 !rounded-full"></div>
                                        <div class="flex flex-col gap-1.5">
                                            <div class="fc-skeleton h-2 w-10"></div>
                                            <div class="fc-skeleton h-3 w-16"></div>
                                        </div>
                                    </div>
                                    <span class="mx-3 min-w-[1rem] flex-1 border-t border-dashed border-slate-200"></span>
                                </div>
                                <div class="flex min-w-0 flex-1 items-center">
                                    <div class="flex shrink-0 items-center gap-3">
                                        <div class="fc-skeleton h-8 w-8 shrink-0 !rounded-full"></div>
                                        <div class="flex flex-col gap-1.5">
                                            <div class="fc-skeleton h-2 w-10"></div>
                                            <div class="fc-skeleton h-3 w-16"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-5">
                                <div class="flex flex-col gap-2">
                                    <div class="fc-skeleton h-3 w-24"></div>
                                    <div class="fc-skeleton h-12 w-full"></div>
                                </div>
                                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    <div class="flex flex-col gap-2">
                                        <div class="fc-skeleton h-3 w-24"></div>
                                        <div class="fc-skeleton h-12 w-full"></div>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <div class="fc-skeleton h-3 w-24"></div>
                                        <div class="fc-skeleton h-12 w-full"></div>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <div class="fc-skeleton h-3 w-24"></div>
                                    <div class="fc-skeleton h-12 w-full"></div>
                                </div>
                                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                                    <div class="flex flex-col gap-2">
                                        <div class="fc-skeleton h-3 w-24"></div>
                                        <div class="fc-skeleton h-12 w-full"></div>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <div class="fc-skeleton h-3 w-24"></div>
                                        <div class="fc-skeleton h-12 w-full"></div>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <div class="fc-skeleton h-3 w-24"></div>
                                        <div class="fc-skeleton h-12 w-full"></div>
                                    </div>
                                </div>

                                <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-6">
                                    <div class="fc-skeleton h-4 w-24"></div>
                                    <div class="fc-skeleton h-11 w-40"></div>
                                </div>
                            </div>
                        </div>

                        <aside class="hidden lg:block">
                            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                                <div class="fc-skeleton mb-6 h-5 w-40"></div>
                                <div class="mb-4 flex items-center gap-3">
                                    <div class="fc-skeleton h-14 w-14 shrink-0 !rounded-xl"></div>
                                    <div class="flex flex-1 flex-col gap-2">
                                        <div class="fc-skeleton h-3 w-3/4"></div>
                                        <div class="fc-skeleton h-3 w-1/3"></div>
                                    </div>
                                    <div class="fc-skeleton h-3 w-12"></div>
                                </div>
                                <div class="mb-4 flex items-center gap-3">
                                    <div class="fc-skeleton h-14 w-14 shrink-0 !rounded-xl"></div>
                                    <div class="flex flex-1 flex-col gap-2">
                                        <div class="fc-skeleton h-3 w-3/4"></div>
                                        <div class="fc-skeleton h-3 w-1/3"></div>
                                    </div>
                                    <div class="fc-skeleton h-3 w-12"></div>
                                </div>
                                <div class="mt-6 flex flex-col gap-3 border-t border-slate-100 pt-6">
                                    <div class="flex justify-between">
                                        <div class="fc-skeleton h-3 w-20"></div>
                                        <div class="fc-skeleton h-3 w-14"></div>
                                    </div>
                                    <div class="flex justify-between">
                                        <div class="fc-skeleton h-3 w-24"></div>
                                        <div class="fc-skeleton h-3 w-16"></div>
                                    </div>
                                    <div class="mt-1 flex justify-between">
                                        <div class="fc-skeleton h-4 w-16"></div>
                                        <div class="fc-skeleton h-4 w-20"></div>
                                    </div>
                                </div>
                            </div>
                        </aside>
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
