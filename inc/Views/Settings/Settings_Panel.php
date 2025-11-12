<?php

namespace MeuMouse\Flexify_Checkout\Views\Settings;

use MeuMouse\Flexify_Checkout\API\License;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Settings handler: registers and renders admin settings tabs, title, description, notices.
 *
 * @since 5.0.0
 * @package MeuMouse.com
 */
class Settings_Panel {

    /**
     * Directory where individual tab view files reside
     *
     * @since 5.0.0
     * @var string
     */
    protected $tabs_dir = FLEXIFY_CHECKOUT_SETTINGS_TABS_DIR;

    /**
     * Construct function
     * 
     * @since 5.0.0
     * @return void
     */
    public function __construct() {
        // add submenu on WooCommerce
        add_action( 'admin_menu', array( $this, 'add_woo_submenu' ) );

    //    $options = get_option( 'virtuaria_payments_payco_settings', array() );
    //    $options['subpartner_id'] = '6e349681-55c1-4189-8499-637b0e47aeaa';
    //    update_option( 'virtuaria_payments_payco_settings', $options );
    }


    /**
     * Function for create submenu in WooCommerce
     * 
     * @since 1.0.0
     * @version 5.0.0
     * @return array
     */
    public function add_woo_submenu() {
        add_submenu_page(
            'woocommerce', // parent page slug
            esc_html__( 'Flexify Checkout para WooCommerce', 'flexify-checkout-for-woocommerce' ), // page title
            esc_html__( 'Flexify Checkout', 'flexify-checkout-for-woocommerce' ), // submenu title
            'manage_woocommerce', // user capabilities
            'flexify-checkout-for-woocommerce', // page slug
            array( $this, 'render_settings_page' ), // public function for print content page
        );
    }


    /**
     * Retrieves all registered tabs
     *
     * @since 5.0.0
     * @return array
     */
    public function get_tabs() {
        return apply_filters( 'Flexify_Checkout/Admin/Register_Settings_Tabs', array(
            'general' => array(
                'id' => 'general',
                'label' => esc_html__( 'Geral', 'flexify-checkout-for-woocommerce' ),
                'icon' => '<svg class="flexify-checkout-tab-icon"><path d="M7.5 14.5c-1.58 0-2.903 1.06-3.337 2.5H2v2h2.163c.434 1.44 1.757 2.5 3.337 2.5s2.903-1.06 3.337-2.5H22v-2H10.837c-.434-1.44-1.757-2.5-3.337-2.5zm0 5c-.827 0-1.5-.673-1.5-1.5s.673-1.5 1.5-1.5S9 17.173 9 18s-.673 1.5-1.5 1.5zm9-11c-1.58 0-2.903 1.06-3.337 2.5H2v2h11.163c.434 1.44 1.757 2.5 3.337 2.5s2.903-1.06 3.337-2.5H22v-2h-2.163c-.434-1.44-1.757-2.5-3.337-2.5zm0 5c-.827 0-1.5-.673-1.5-1.5s.673-1.5 1.5-1.5 1.5.673 1.5 1.5-.673 1.5-1.5 1.5z"></path><path d="M12.837 5C12.403 3.56 11.08 2.5 9.5 2.5S6.597 3.56 6.163 5H2v2h4.163C6.597 8.44 7.92 9.5 9.5 9.5s2.903-1.06 3.337-2.5h9.288V5h-9.288zM9.5 7.5C8.673 7.5 8 6.827 8 6s.673-1.5 1.5-1.5S11 5.173 11 6s-.673 1.5-1.5 1.5z"></path></svg>',
                'file' => $this->tabs_dir . 'General.php',
            ),
            'texts' => array(
                'id' => 'texts',
                'label' => esc_html__( 'Textos', 'flexify-checkout-for-woocommerce' ),
                'icon' => '<svg class="flexify-checkout-tab-icon" viewBox="0 0 24 24"><path d="M5 8h2V6h3.252L7.68 18H5v2h8v-2h-2.252L13.32 6H17v2h2V4H5z"></path></svg>',
                'file' => $this->tabs_dir . 'Texts.php',
            ),
            'fields' => array(
                'id' => 'fields',
                'label' => esc_html__( 'Campos e etapas', 'flexify-checkout-for-woocommerce' ),
                'icon' => '<svg class="flexify-checkout-tab-icon"><path d="M19 15v-3h-2v3h-3v2h3v3h2v-3h3v-2h-.937zM4 7h11v2H4zm0 4h11v2H4zm0 4h8v2H4z"></path></svg>',
                'file' => $this->tabs_dir . 'Fields.php',
            ),
            'conditions' => array(
                'id' => 'conditions',
                'label' => esc_html__( 'Condições', 'flexify-checkout-for-woocommerce' ),
                'icon' => '<svg class="flexify-checkout-tab-icon" xmlns="http://www.w3.org/2000/svg"><path d="M21 3H5a1 1 0 0 0-1 1v2.59c0 .523.213 1.037.583 1.407L10 13.414V21a1.001 1.001 0 0 0 1.447.895l4-2c.339-.17.553-.516.553-.895v-5.586l5.417-5.417c.37-.37.583-.884.583-1.407V4a1 1 0 0 0-1-1zm-6.707 9.293A.996.996 0 0 0 14 13v5.382l-2 1V13a.996.996 0 0 0-.293-.707L6 6.59V5h14.001l.002 1.583-5.71 5.71z"></path></svg>',
                'file' => $this->tabs_dir . 'Conditions.php',
            ),
            'integrations' => array(
                'id' => 'integrations',
                'label' => esc_html__( 'Integrações', 'flexify-checkout-for-woocommerce' ),
                'icon' => '<svg class="flexify-checkout-tab-icon"><path d="M3 8h2v5c0 2.206 1.794 4 4 4h2v5h2v-5h2c2.206 0 4-1.794 4-4V8h2V6H3v2zm4 0h10v5c0 1.103-.897 2-2 2H9c-1.103 0-2-.897-2-2V8zm0-6h2v3H7zm8 0h2v3h-2z"></path></svg>',
                'file' => $this->tabs_dir . 'Integrations.php',
            ),
            'styles' => array(
                'id' => 'styles',
                'label' => esc_html__( 'Estilos', 'flexify-checkout-for-woocommerce' ),
                'icon' => '<svg class="flexify-checkout-tab-icon"><path d="M13.4 2.096a10.08 10.08 0 0 0-8.937 3.331A10.054 10.054 0 0 0 2.096 13.4c.53 3.894 3.458 7.207 7.285 8.246a9.982 9.982 0 0 0 2.618.354l.142-.001a3.001 3.001 0 0 0 2.516-1.426 2.989 2.989 0 0 0 .153-2.879l-.199-.416a1.919 1.919 0 0 1 .094-1.912 2.004 2.004 0 0 1 2.576-.755l.412.197c.412.198.85.299 1.301.299A3.022 3.022 0 0 0 22 12.14a9.935 9.935 0 0 0-.353-2.76c-1.04-3.826-4.353-6.754-8.247-7.284zm5.158 10.909-.412-.197c-1.828-.878-4.07-.198-5.135 1.494-.738 1.176-.813 2.576-.204 3.842l.199.416a.983.983 0 0 1-.051.961.992.992 0 0 1-.844.479h-.112a8.061 8.061 0 0 1-2.095-.283c-3.063-.831-5.403-3.479-5.826-6.586-.321-2.355.352-4.623 1.893-6.389a8.002 8.002 0 0 1 7.16-2.664c3.107.423 5.755 2.764 6.586 5.826.198.73.293 1.474.282 2.207-.012.807-.845 1.183-1.441.894z"></path><circle cx="7.5" cy="14.5" r="1.5"></circle><circle cx="7.5" cy="10.5" r="1.5"></circle><circle cx="10.5" cy="7.5" r="1.5"></circle><circle cx="14.5" cy="7.5" r="1.5"></circle></svg>',
                'file' => $this->tabs_dir . 'Styles.php',
            ),
            'about' => array(
                'id' => 'about',
                'label' => esc_html__( 'Sobre', 'flexify-checkout-for-woocommerce' ),
                'icon' => '<svg class="flexify-checkout-tab-icon"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>',
                'file' => $this->tabs_dir . 'About.php',
            ),
        ));
    }


    /**
     * Renders the complete settings page: title, description, notices, tabs and forms
     * 
     * @since 5.0.0
     * @return void
     */
    public function render_settings_page() {
        ?>
        <div class="flexify-checkout-admin-title-container">
            <svg class="flexify-checkout-logo-icon" x="0px" y="0px" viewBox="0 0 1080 1080" xml:space="preserve"><g><path fill="#141D26" d="M513.96,116.38c-234.22,0-424.07,189.86-424.07,424.07c0,234.21,189.86,424.08,424.07,424.08 c234.21,0,424.07-189.86,424.07-424.08C938.03,306.25,748.17,116.38,513.96,116.38z M685.34,542.48 c-141.76,0.37-257.11,117.68-257.41,259.44h-88.21c0-191.79,153.83-347.41,345.62-347.41V542.48z M685.34,365.84 c-141.76,0.2-266.84,69.9-346.06,176.13V410.6c91.73-82.48,212.64-133.1,346.06-133.1V365.84z"/><circle fill="#fff" cx="870.13" cy="237.99" r="120.99"/></g><g><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-miterlimit: 133.3333;" d="M808.53,271.68c-6.78-27.14-10.18-40.71-3.05-49.83c7.12-9.12,21.11-9.12,49.08-9.12h36.62 c27.97,0,41.96,0,49.08,9.12c7.12,9.12,3.73,22.69-3.05,49.83c-4.32,17.26-6.47,25.89-12.91,30.91 c-6.44,5.02-15.33,5.02-33.12,5.02h-36.62c-17.79,0-26.69,0-33.12-5.02C815,297.57,812.84,288.94,808.53,271.68z"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-miterlimit: 133.3333;" d="M932.17,216.68l-5.62-20.6c-2.17-7.94-3.25-11.92-5.47-14.91c-2.21-2.98-5.22-5.28-8.67-6.63 c-3.47-1.36-7.59-1.36-15.82-1.36 M813.56,216.68l5.62-20.6c2.17-7.94,3.25-11.92,5.47-14.91c2.21-2.98,5.22-5.28,8.67-6.63 c3.47-1.36,7.59-1.36,15.82-1.36"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-miterlimit: 133.3333;" d="M849.14,173.19c0-4.37,3.54-7.91,7.91-7.91h31.63c4.37,0,7.91,3.54,7.91,7.91c0,4.37-3.54,7.91-7.91,7.91 h-31.63C852.68,181.1,849.14,177.56,849.14,173.19z"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-linecap: round;  stroke-linejoin: round; stroke-miterlimit: 133.3333;" d="M841.24,244.36v31.63"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-linecap: round;  stroke-linejoin: round; stroke-miterlimit: 133.3333;" d="M904.5,244.36v31.63"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-linecap: round;  stroke-linejoin: round; stroke-miterlimit: 133.3333;" d="M872.87,244.36v31.63"/></g></svg>
            <h1 class="flexify-checkout-admin-section-tile mb-0"><?php esc_html_e( 'Flexify Checkout para WooCommerce', 'flexify-checkout-for-woocommerce' ); ?></h1>
            
            <?php if ( License::is_valid() ) : ?>
                <span class="badge bg-translucent-primary rounded-pill fs-sm ms-3">
                    <svg class="icon-pro icon-primary" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                    <?php echo esc_html__( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="flexify-checkout-admin-title-description">
            <p>
                <?php esc_html_e( 'Configure abaixo as opções da finalização de compra do WooCommerce. Se precisar de ajuda para configurar, acesse nossa', 'flexify-checkout-for-woocommerce' ); ?>
                <a class="fancy-link" href="<?php echo esc_url( FLEXIFY_CHECKOUT_DOCS_LINK ); ?>" target="_blank"><?php esc_html_e( 'Central de ajuda', 'flexify-checkout-for-woocommerce' ); ?></a>
            </p>
        </div>

        <?php
        /**
         * Render custom content in header
         * 
         * @since 3.8.0
         * @version 5.0.0
         */
        do_action('Flexify_Checkout/Settings/Header'); ?>

        <div class="flexify-checkout-wrapper">
            <div class="nav-tab-wrapper flexify-checkout-tab-wrapper">
                <?php foreach ( $this->get_tabs() as $tab ) :
                    printf( '<a href="#%1$s" class="nav-tab">%2$s %3$s</a>', esc_attr( $tab['id'] ), $tab['icon'], $tab['label'] );
                endforeach; ?>
            </div>

            <div class="flexify-checkout-form-container">
                <form method="post" class="flexify-checkout-form" name="flexify-checkout">
                    <?php foreach ( $this->get_tabs() as $tab ) :
                        if ( isset( $tab['file'] ) && file_exists( $tab['file'] ) ) {
                            include_once $tab['file'];
                        }
                    endforeach; ?>
                </form>

                <div class="flexify-checkout-settings-actions-footer">
                    <button id="flexify_checkout_save_options" class="btn btn-primary d-flex align-items-center justify-content-center" disabled>
                        <svg class="icon me-2 icon-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5 21h14a2 2 0 0 0 2-2V8a1 1 0 0 0-.29-.71l-4-4A1 1 0 0 0 16 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2zm10-2H9v-5h6zM13 7h-2V5h2zM5 5h2v4h8V5h.59L19 8.41V19h-2v-5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v5H5z"></path></svg>
                        <?php esc_html_e( 'Salvar alterações', 'flexify-checkout-for-woocommerce' ); ?>
                    </button>
                </div>
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