<?php

namespace MeuMouse\Flexify_Checkout\Assets;

use MeuMouse\Flexify_Checkout\Core\Scripts;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Load Vite-built admin assets for the Flexify Checkout settings page.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Assets
 * @author MeuMouse.com
 */
class Settings_Assets {

    /**
     * Page-to-entry map for the Vite build.
     *
     * @since 6.0.0
     * @var array<string,string>
     */
    private $entries = array(
        'flexify-checkout-for-woocommerce' => 'src/entries/settings.js',
        'flexify-checkout-apps' => 'src/entries/settings.js',
        'flexify-checkout-offers' => 'src/entries/settings.js',
        'flexify-checkout-license' => 'src/entries/settings.js',
        'fc-recovery-carts' => 'src/entries/settings.js',
        'fc-recovery-carts-list' => 'src/entries/settings.js',
        'fc-recovery-carts-queue' => 'src/entries/settings.js',
    );


    /**
     * Map of page slug to the initial SPA route ("view") opened on mount.
     *
     * @since 6.0.0
     * @var array<string,string>
     */
    private $views = array(
        'flexify-checkout-for-woocommerce' => 'settings',
        'flexify-checkout-apps' => 'apps',
        'flexify-checkout-offers' => 'offers',
        'flexify-checkout-license' => 'license',
        'fc-recovery-carts' => 'analytics',
        'fc-recovery-carts-list' => 'carts',
        'fc-recovery-carts-queue' => 'queue',
    );


    /**
     * Register hooks for the Vite-driven admin pages.
     *
     * @since 6.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 100 );
        add_filter( 'script_loader_tag', array( $this, 'add_module_type_attribute' ), 10, 3 );
    }


    /**
     * Enqueue the assets produced by Vite for the current admin page.
     *
     * @since 6.0.0
     * @return void
     */
    public function enqueue_assets() {
        $page = $this->get_current_page();

        if ( empty( $page ) || ! isset( $this->entries[ $page ] ) ) {
            return;
        }

        $assets = Scripts::get_entry_assets( $this->entries[ $page ] );

        if ( empty( $assets['script'] ) ) {
            return;
        }

        // Media pickers (header image, Lottie animations) rely on the WP media modal.
        wp_enqueue_media();

        $asset_version = ! empty( $assets['version'] ) ? $assets['version'] : null;

        if ( ! empty( $assets['styles'] ) && is_array( $assets['styles'] ) ) {
            foreach ( $assets['styles'] as $index => $style_url ) {
                wp_enqueue_style(
                    'flexify-checkout-vue-' . sanitize_key( $page ) . '-' . $index,
                    $style_url,
                    array(),
                    $asset_version
                );
            }
        }

        $handle = 'flexify-checkout-settings-app';

        wp_enqueue_script( $handle, $assets['script'], array(), $asset_version, true );

        $view = isset( $this->views[ $page ] ) ? $this->views[ $page ] : 'settings';

        wp_localize_script( $handle, 'flexifyCheckoutBootstrapConfig', array(
            'restUrl' => esc_url_raw( rest_url('flexify-checkout/v1') ),
            'nonce' => wp_create_nonce('wp_rest'),
            'page' => 'settings',
            'view' => $view,
            'endpoint' => 'admin/settings',
        ));
    }


    /**
     * Resolve the current admin page slug.
     *
     * @since 6.0.0
     * @return string
     */
    private function get_current_page() {
        if ( ! is_admin() || ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return '';
        }

        return sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }


    /**
     * Mark Vite entry scripts as ES modules.
     *
     * @since 6.0.0
     * @param string $tag Script tag HTML.
     * @param string $handle Script handle.
     * @param string $src Script URL.
     * @return string
     */
    public function add_module_type_attribute( $tag, $handle, $src ) {
        if ( 'flexify-checkout-settings-app' !== $handle ) {
            return $tag;
        }

        $tag = is_scalar( $tag ) ? (string) $tag : '';

        if ( false !== strpos( $tag, 'type=' ) ) {
            return $tag;
        }

        return sprintf(
            '<script type="module" src="%s" id="%s-js"></script>' . "\n",
            esc_url( $src ),
            esc_attr( $handle )
        );
    }
}
