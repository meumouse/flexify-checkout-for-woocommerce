<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Views;

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div class="fc-recovery-carts-admin-title-container">
    <svg id="fc-recovery-carts-logo" x="0px" y="0px" viewBox="0 0 1080 1080" xml:space="preserve"><g><path fill="#141D26" d="M513.96,116.38c-234.22,0-424.07,189.86-424.07,424.07c0,234.21,189.86,424.08,424.07,424.08 c234.21,0,424.07-189.86,424.07-424.08C938.03,306.25,748.17,116.38,513.96,116.38z M685.34,542.48 c-141.76,0.37-257.11,117.68-257.41,259.44h-88.21c0-191.79,153.83-347.41,345.62-347.41V542.48z M685.34,365.84 c-141.76,0.2-266.84,69.9-346.06,176.13V410.6c91.73-82.48,212.64-133.1,346.06-133.1V365.84z"/><circle fill="#fff" cx="870.13" cy="237.99" r="120.99"/></g><g><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-miterlimit: 133.3333;" d="M808.53,271.68c-6.78-27.14-10.18-40.71-3.05-49.83c7.12-9.12,21.11-9.12,49.08-9.12h36.62 c27.97,0,41.96,0,49.08,9.12c7.12,9.12,3.73,22.69-3.05,49.83c-4.32,17.26-6.47,25.89-12.91,30.91 c-6.44,5.02-15.33,5.02-33.12,5.02h-36.62c-17.79,0-26.69,0-33.12-5.02C815,297.57,812.84,288.94,808.53,271.68z"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-miterlimit: 133.3333;" d="M932.17,216.68l-5.62-20.6c-2.17-7.94-3.25-11.92-5.47-14.91c-2.21-2.98-5.22-5.28-8.67-6.63 c-3.47-1.36-7.59-1.36-15.82-1.36 M813.56,216.68l5.62-20.6c2.17-7.94,3.25-11.92,5.47-14.91c2.21-2.98,5.22-5.28,8.67-6.63 c3.47-1.36,7.59-1.36,15.82-1.36"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-miterlimit: 133.3333;" d="M849.14,173.19c0-4.37,3.54-7.91,7.91-7.91h31.63c4.37,0,7.91,3.54,7.91,7.91c0,4.37-3.54,7.91-7.91,7.91 h-31.63C852.68,181.1,849.14,177.56,849.14,173.19z"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-linecap: round;  stroke-linejoin: round; stroke-miterlimit: 133.3333;" d="M841.24,244.36v31.63"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-linecap: round;  stroke-linejoin: round; stroke-miterlimit: 133.3333;" d="M904.5,244.36v31.63"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-linecap: round;  stroke-linejoin: round; stroke-miterlimit: 133.3333;" d="M872.87,244.36v31.63"/></g></svg>
    <h1 class="fc-recovery-carts-admin-section-tile mb-0"><?php echo esc_html( 'Flexify Checkout - Recuperação de carrinhos abandonados.', 'fc-recovery-carts' ) ?></h1>
</div>

<div class="fc-recovery-carts-admin-title-description">
    <p><?php esc_html_e( 'Recupere carrinhos e pedidos abandonados com follow up cadenciado. Se precisar de ajuda para configurar, acesse nossa', 'fc-recovery-carts' ) ?>
        <a class="fancy-link" href="<?php esc_attr_e( FC_RECOVERY_CARTS_DOCS_URL ) ?>" target="_blank"><?php esc_html_e( 'Central de ajuda', 'fc-recovery-carts' ) ?></a>
    </p>
</div>

<?php
/**
 * Display admin notices
 * 
 * @since 1.0.0
 */
do_action('Flexify_Checkout/Recovery_Carts/Settings/Display_Notices'); ?>

<div class="fc-recovery-carts-wrapper">
    <div class="alert alert-info d-flex align-items-center w-fit fs-6">
        <svg class="icon icon-lg icon-info me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>
        <?php esc_html_e( 'Ative a versão Pro do Flexify Checkout para liberar as configurações deste plugin', 'fc-recovery-carts' ); ?>
    </div>
</div>