<?php

namespace MeuMouse\Flexify_Checkout\Views\Settings;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Settings handler: registers and renders admin settings tabs, title, description, notices.
 *
 * @since 5.0.0

 * @author MeuMouse.com
 */
class Settings_Panel {

    /**
     * Construct function
     * 
     * @since 5.0.0
     * @return void
     */
    public function __construct() {
        // register the dedicated top-level admin menu
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
    }


    /**
     * Register the dedicated top-level "Flexify Checkout" menu.
     *
     * The plugin previously lived as a submenu under WooCommerce; it is now a
     * top-level menu so the checkout settings, license and the cart recovery
     * pages share a single dedicated section. The main settings keep the
     * historical "flexify-checkout-for-woocommerce" slug so existing links and
     * bookmarks keep working. Recovery pages (Analytics, Carts, Queue) are
     * added under this same parent by the Recovery_Carts feature, after this
     * runs.
     *
     * @since 1.0.0
     * @version 6.0.0
     * @return void
     */
    public function register_admin_menu() {
        $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 1080"><path fill="#a7aaad" d="M513.96,116.38c-234.22,0-424.07,189.86-424.07,424.07c0,234.21,189.86,424.08,424.07,424.08 c234.21,0,424.07-189.86,424.07-424.08C938.03,306.25,748.17,116.38,513.96,116.38z M685.34,542.48 c-141.76,0.37-257.11,117.68-257.41,259.44h-88.21c0-191.79,153.83-347.41,345.62-347.41V542.48z M685.34,365.84 c-141.76,0.2-266.84,69.9-346.06,176.13V410.6c91.73-82.48,212.64-133.1,346.06-133.1V365.84z"/></svg>';

        add_menu_page(
            esc_html__( 'Flexify Checkout para WooCommerce', 'flexify-checkout-for-woocommerce' ), // page title
            esc_html__( 'Flexify Checkout', 'flexify-checkout-for-woocommerce' ), // menu title
            'manage_woocommerce', // capability
            'flexify-checkout-for-woocommerce', // slug (kept for backward compatibility)
            array( $this, 'render_settings_page' ), // callback
            'data:image/svg+xml;base64,' . base64_encode( $icon_svg ), // icon
            7
        );

        // Rename the auto-generated first submenu (defaults to the page title) to
        // "Configurações". Explicit positions order the whole menu across this
        // class and the Recovery_Carts feature (which registers Análise,
        // Carrinhos Abandonados and Fila de Processamentos at positions 1, 2, 3).
        add_submenu_page(
            'flexify-checkout-for-woocommerce', // parent slug
            esc_html__( 'Configurações', 'flexify-checkout-for-woocommerce' ), // page title
            esc_html__( 'Configurações', 'flexify-checkout-for-woocommerce' ), // submenu title
            'manage_woocommerce', // capability
            'flexify-checkout-for-woocommerce', // slug (same as parent)
            array( $this, 'render_settings_page' ), // callback
            5 // position
        );

        // Apps page (Vue SPA, apps route): the former "Integrações" settings tab
        // promoted to its own top-level item.
        add_submenu_page(
            'flexify-checkout-for-woocommerce', // parent slug
            esc_html__( 'Aplicativos', 'flexify-checkout-for-woocommerce' ), // page title
            esc_html__( 'Aplicativos', 'flexify-checkout-for-woocommerce' ), // submenu title
            'manage_woocommerce', // capability
            'flexify-checkout-apps', // slug
            array( $this, 'render_apps_page' ), // callback
            4 // position
        );

        // License page (Vue SPA, license route).
        add_submenu_page(
            'flexify-checkout-for-woocommerce', // parent slug
            esc_html__( 'Licença', 'flexify-checkout-for-woocommerce' ), // page title
            esc_html__( 'Licença', 'flexify-checkout-for-woocommerce' ), // submenu title
            'manage_woocommerce', // capability
            'flexify-checkout-license', // slug
            array( $this, 'render_license_page' ), // callback
            6 // position
        );
    }


    /**
     * Render the settings page (Vue SPA mount).
     *
     * @since 5.0.0
     * @version 6.0.0
     * @return void
     */
    public function render_settings_page() {
        $this->render_app_mount();
    }


    /**
     * Render the License page.
     *
     * Mounts the same Vue SPA; the localized `view` opens it on the license
     * route.
     *
     * @since 6.0.0
     * @return void
     */
    public function render_license_page() {
        $this->render_app_mount();
    }


    /**
     * Render the Apps (Aplicativos) page.
     *
     * Mounts the same Vue SPA; the localized `view` opens it on the apps route,
     * which lists the available integrations/addons.
     *
     * @since 6.0.0
     * @return void
     */
    public function render_apps_page() {
        $this->render_app_mount();
    }


    /**
     * Output the Vue SPA mount point with a loading skeleton.
     *
     * Shared by the Settings and License pages; the active route is decided by
     * the `view` localized in flexifyCheckoutBootstrapConfig (see Settings_Assets).
     *
     * @since 6.0.0
     * @return void
     */
    private function render_app_mount() {
        ?>
        <div class="wrap flexify-checkout-settings-page">
            <div id="flexify-checkout-settings-app" class="flexify-checkout-settings-app">
                <div class="skeleton-content" style="width: 950px; height: 100px;"></div>

                <div class="skeleton-content" style="width: 680px; height: 65px; margin-top: 2rem;"></div>

                <div class="skeleton-content" style="width: 100%; height: 550px; margin-top: 2rem;"></div>
            </div>
        </div>
        <?php
    }


    /**
     * Returns the list of registered themes for checkout
     *
     * @since 5.0.0
     * @return array
     */
    public static function get_registered_themes() {
        return apply_filters( 'Flexify_Checkout/Register_Themes', array(
            'modern' => array(
                'id'        => 'modern',
                'label'     => esc_html__( 'Moderno claro', 'flexify-checkout-for-woocommerce' ),
                'icon'      => '<svg id="flexify-checkout-theme-modern" class="card-img-top" viewBox="0 0 466.75 301.44"><rect x="0.13" y="112.8" width="131.04" height="21.6" rx="5" style="fill:#e5e5e5"/><rect x="141.73" y="112.8" width="126.48" height="21.6" rx="4.91" style="fill:#e5e5e5"/><rect x="0.13" y="147.84" width="268.08" height="21.6" rx="5" style="fill:#e5e5e5"/><rect x="0.13" y="182.6" width="268.08" height="21.6" rx="5" style="fill:#e5e5e5"/><rect x="175.93" y="218.46" width="92.28" height="21.6" rx="5" style="fill:#141d26"/><rect x="412.45" y="147.84" width="54.3" height="21.6" rx="5" style="fill:#141d26"/><rect x="294.85" y="147.84" width="111.84" height="21.6" rx="5" style="fill:#e5e5e5"/><line x1="281.69" y1="301.44" x2="281.35" y2="301.44" style="fill:#e5e5e5"/><line x1="281.35" x2="281.69" style="fill:#e5e5e5"/><rect x="294.85" y="53.76" width="43.44" height="43.44" rx="5" style="fill:#e5e5e5"/><rect x="348.73" y="54.6" width="100.08" height="7.08" rx="2.66" style="fill:#e5e5e5"/><rect x="348.73" y="104.52" width="50.04" height="14.52" rx="3" style="fill:#e5e5e5"/><rect x="435.7" y="104.52" width="31.05" height="14.52" rx="3" style="fill:#e5e5e5"/><rect x="348.73" y="66.84" width="81.96" height="7.08" rx="2.41" style="fill:#e5e5e5"/><rect x="348.73" y="79.32" width="69.84" height="7.08" rx="2.22" style="fill:#e5e5e5"/><rect x="294.85" y="193.4" width="26.34" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="294.85" y="207.76" width="57.84" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="437.62" y="193.4" width="26.34" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="437.62" y="207.77" width="26.34" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="430.6" y="236.43" width="33.36" height="8.67" rx="2" style="fill:#e5e5e5"/><rect x="294.85" y="235.73" width="26.34" height="8.67" rx="2" style="fill:#e5e5e5"/><line x1="463.96" y1="182.52" x2="463.96" y2="182.71" style="fill:#e5e5e5"/><line x1="294.85" y1="182.71" x2="294.85" y2="182.52" style="fill:#e5e5e5"/><line x1="463.96" y1="226.73" x2="463.96" y2="226.92" style="fill:#e5e5e5"/><line x1="294.85" y1="226.92" x2="294.85" y2="226.73" style="fill:#e5e5e5"/><rect x="0.26" y="58.56" width="35.15" height="7.08" rx="1.58" style="fill:#e5e5e5"/><rect x="41.11" y="58.56" width="35.15" height="7.08" rx="1.58" style="fill:#e5e5e5"/><rect x="81.19" y="58.56" width="35.15" height="7.08" rx="1.58" style="fill:#e5e5e5"/><path d="M30,21.07A13.35,13.35,0,1,0,43.32,34.42,13.34,13.34,0,0,0,30,21.07Zm5.4,13.41a8.18,8.18,0,0,0-8.1,8.17H24.49A10.89,10.89,0,0,1,35.37,31.71Zm0-5.56a13.61,13.61,0,0,0-10.89,5.54V30.33a16.18,16.18,0,0,1,10.89-4.19Z" transform="translate(-16.63 -1.38)" style="fill:#141d26"/><circle cx="24.56" cy="23.52" r="3.81" style="fill:#fff"/><path d="M39.24,26c-.21-.86-.32-1.28-.09-1.57s.66-.29,1.54-.29h1.16c.88,0,1.32,0,1.54.29s.12.71-.1,1.57c-.13.54-.2.81-.4,1s-.48.16-1,.16H40.69c-.56,0-.84,0-1-.16S39.38,26.5,39.24,26Z" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-miterlimit:133.33332824707;stroke-width:0.5px"/><path d="M43.14,24.23,43,23.58a1.46,1.46,0,0,0-.17-.47.58.58,0,0,0-.28-.21,1.51,1.51,0,0,0-.49,0M39.4,24.23l.18-.65a1.46,1.46,0,0,1,.17-.47A.6.6,0,0,1,40,22.9a1.55,1.55,0,0,1,.5,0" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-miterlimit:133.33332824707;stroke-width:0.5px"/><path d="M40.52,22.86a.25.25,0,0,1,.25-.25h1a.25.25,0,0,1,.25.25.26.26,0,0,1-.25.25h-1A.25.25,0,0,1,40.52,22.86Z" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-miterlimit:133.33332824707;stroke-width:0.5px"/><path d="M40.27,25.1v1" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-linecap:round;stroke-linejoin:round;stroke-width:0.5px"/><path d="M42.26,25.1v1" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-linecap:round;stroke-linejoin:round;stroke-width:0.5px"/><path d="M41.27,25.1v1" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-linecap:round;stroke-linejoin:round;stroke-width:0.5px"/></svg>',
                'status'    => 'active',
                'classes'   => '',
            ),
            'dark' => array(
                'id'        => 'dark',
                'label'     => esc_html__( 'Moderno escuro', 'flexify-checkout-for-woocommerce' ),
                'icon'      => '<svg id="flexify-checkout-theme-dark" class="card-img-top" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 350"><defs><style>.cls-1{fill:#1a222c;}.cls-2{fill:#2e3c4c;}.cls-3{fill:#f2f2f2;}.cls-4{fill:#e5e5e5;}.cls-5{fill:#fff;}.cls-6,.cls-7{fill:none;stroke:#141d26;stroke-width:0.5px;}.cls-6{stroke-miterlimit:133.33;}.cls-7{stroke-linecap:round;stroke-linejoin:round;}</style></defs><rect class="cls-1" width="500" height="350"/><rect class="cls-2" x="16.75" y="114.18" width="131.04" height="21.6" rx="5"/><rect class="cls-2" x="158.35" y="114.18" width="126.48" height="21.6" rx="4.91"/><rect class="cls-2" x="16.75" y="149.22" width="268.08" height="21.6" rx="5"/><rect class="cls-2" x="16.75" y="183.98" width="268.08" height="21.6" rx="5"/><rect class="cls-3" x="192.55" y="219.84" width="92.28" height="21.6" rx="5"/><rect class="cls-3" x="429.07" y="149.22" width="54.3" height="21.6" rx="5"/><rect class="cls-2" x="311.47" y="149.22" width="111.84" height="21.6" rx="5"/><line class="cls-4" x1="298.32" y1="302.82" x2="297.97" y2="302.82"/><line class="cls-4" x1="297.97" y1="1.38" x2="298.32" y2="1.38"/><rect class="cls-2" x="311.47" y="55.14" width="43.44" height="43.44" rx="5"/><rect class="cls-2" x="365.35" y="55.98" width="100.08" height="7.08" rx="2.66"/><rect class="cls-2" x="365.35" y="105.9" width="50.04" height="14.52" rx="3"/><rect class="cls-2" x="452.32" y="105.9" width="31.05" height="14.52" rx="3"/><rect class="cls-2" x="365.35" y="68.22" width="81.96" height="7.08" rx="2.41"/><rect class="cls-2" x="365.35" y="80.7" width="69.84" height="7.08" rx="2.22"/><rect class="cls-2" x="311.47" y="194.78" width="26.34" height="7.08" rx="2"/><rect class="cls-2" x="311.47" y="209.14" width="57.84" height="7.08" rx="2"/><rect class="cls-2" x="454.24" y="194.78" width="26.34" height="7.08" rx="2"/><rect class="cls-2" x="454.24" y="209.15" width="26.34" height="7.08" rx="2"/><rect class="cls-2" x="447.22" y="237.81" width="33.36" height="8.67" rx="2"/><rect class="cls-2" x="311.47" y="237.11" width="26.34" height="8.67" rx="2"/><line class="cls-4" x1="480.58" y1="183.9" x2="480.58" y2="184.09"/><path class="cls-4" d="M311.47,183.9"/><rect class="cls-2" x="16.88" y="59.94" width="35.15" height="7.08" rx="1.58"/><rect class="cls-2" x="57.74" y="59.94" width="35.15" height="7.08" rx="1.58"/><rect class="cls-2" x="97.82" y="59.94" width="35.15" height="7.08" rx="1.58"/><path class="cls-5" d="M30,21.07A13.35,13.35,0,1,0,43.32,34.42,13.34,13.34,0,0,0,30,21.07Zm5.4,13.41a8.18,8.18,0,0,0-8.1,8.17H24.49A10.89,10.89,0,0,1,35.37,31.71Zm0-5.56a13.61,13.61,0,0,0-10.89,5.54V30.33a16.18,16.18,0,0,1,10.89-4.19Z"/><circle class="cls-5" cx="41.18" cy="24.9" r="3.81"/><path class="cls-6" d="M39.24,26c-.21-.86-.32-1.28-.09-1.57s.66-.29,1.54-.29h1.16c.88,0,1.32,0,1.54.29s.12.71-.1,1.57c-.13.54-.2.81-.4,1s-.48.16-1,.16H40.69c-.56,0-.84,0-1-.16S39.38,26.5,39.24,26Z"/><path class="cls-6" d="M43.14,24.23,43,23.58a1.46,1.46,0,0,0-.17-.47.58.58,0,0,0-.28-.21,1.51,1.51,0,0,0-.49,0M39.4,24.23l.18-.65a1.46,1.46,0,0,1,.17-.47A.6.6,0,0,1,40,22.9a1.55,1.55,0,0,1,.5,0"/><path class="cls-6" d="M40.52,22.86a.25.25,0,0,1,.25-.25h1a.25.25,0,0,1,.25.25.26.26,0,0,1-.25.25h-1A.25.25,0,0,1,40.52,22.86Z"/><path class="cls-7" d="M40.27,25.1v1"/><path class="cls-7" d="M42.26,25.1v1"/><path class="cls-7" d="M41.27,25.1v1"/></svg>',
                'status'    => 'active',
                'classes'   => '',
            ),
            'single' => array(
                'id'        => 'single',
                'label'     => esc_html__( 'Página única', 'flexify-checkout-for-woocommerce' ),
                'icon'      => '',
                'status'    => 'soon',
                'classes'   => '',
            ),
        ));
    }
}
