<?php

use MeuMouse\Flexify_Checkout\API\License;

/**
 * Template for display integrations options
 * 
 * @since 3.6.0
 * @version 4.1.0
 * @package MeuMouse.com
 */

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div id="integrations" class="nav-content">
   <?php
   /**
    * Hook for display custom fields options
    * 
    * @since 3.6.0
    */
   do_action('flexify_checkout_before_integrations_options'); ?>

   <div class="cards-group ps-5 mb-5">
      <div class="card text-center p-0 m-4">
         <div class="card-header border-bottom w-100">
               <div class="integration-item p-4 rounded-circle">
                  <svg version="1.1" id="inter_empresas_logo" xmlns="http://www.w3.org/2000/svg" x="0" y="0" viewBox="0 0 1920 1080" style="max-width: 10rem;" xml:space="preserve"><style>.inter-logo{fill:#ea7100}</style><g id="Inter_Empresas"><g id="Empresas"><path d="M795.7 865.8c-10.2-5.7-18.1-13.9-23.7-24.6-5.6-10.6-8.5-23-8.5-37 0-13.8 2.8-26.1 8.5-37 5.6-10.8 13.6-19.3 23.9-25.4 10.3-6.1 22.1-9.1 35.3-9.1 12.9 0 24.3 3 34.1 8.9s17.4 14 22.8 24.2c5.4 10.2 8.1 21.4 8.1 33.6v3.3h-119c.2 19.1 5.1 33.7 14.7 43.9 9.6 10.2 22.6 15.3 39 15.3 12.9 0 23.9-3.4 33-10.1 9.1-6.7 15.2-15.3 18.3-25.7h13.6c-2 9.3-6 17.6-11.9 24.8-5.9 7.3-13.5 13-22.6 17.2-9.2 4.2-19.3 6.3-30.4 6.3-13.3 0-25-2.9-35.2-8.6zm86-75.4c-2-13.1-7.5-23.8-16.5-32.2-9-8.4-20.3-12.6-34-12.6-14 0-26 4.1-35.9 12.1-9.9 8.1-15.8 19-17.6 32.6h104zM920.1 735.2h13.4v13.4h.8c4.4-4.9 9.6-8.8 15.8-11.7 6.2-2.9 12.7-4.4 19.6-4.4 6.4 0 12.3 1.2 17.9 3.5 5.5 2.4 10.2 6 14.1 10.9h.8c3.8-4.4 8.7-7.9 14.6-10.5 5.9-2.6 12.7-4 20.3-4 13.1 0 23.1 3.4 30 10.1 6.9 6.7 10.4 16.6 10.4 29.7v99.3h-13.6V769.6c0-9.1-2.4-15.5-7.2-19.1-4.8-3.6-12.1-5.5-21.7-5.5-13.5 0-23.3 3.7-29.5 11.2v115.4h-13.6V766.9c0-7.8-2.2-13.4-6.7-16.8-4.5-3.4-11-5-19.5-5-11.8 0-22.6 3.7-32.2 11.2v115.4h-13.6V735.2zM1115.4 862.4v52.9h-13.6V735.2h12.5v13.9h.5c6-4.9 13-8.8 20.9-11.7 7.9-2.9 15.9-4.4 23.9-4.4 14.2 0 26.4 3.1 36.6 9.4 10.2 6.3 17.9 14.8 23.1 25.5 5.2 10.7 7.8 22.9 7.8 36.6 0 12.7-2.7 24.5-8.2 35.2-5.5 10.7-13.4 19.3-23.9 25.7-10.5 6.4-22.9 9.6-37.2 9.6-16.2 0-30.3-4.2-42.4-12.6zm20.1-3.3c7.5 2.2 14.8 3.3 22.1 3.3 11.6 0 21.6-2.6 30-7.9 8.4-5.3 14.6-12.2 18.8-20.9 4.2-8.6 6.3-18.1 6.3-28.2 0-17.6-4.7-32-14.1-43.1-9.4-11.1-22.4-16.6-39.2-16.6-8.2 0-15.9 1.3-23.1 4-7.2 2.6-14.2 6.4-21.1 11.3v88.7c6.2 4.1 12.9 7.2 20.3 9.4zM1249.9 735.2h11.5v17.5h.8c2.2-6.9 5.9-11.9 11.1-15 5.2-3.1 11.9-4.6 20.1-4.6h12.6v13.6h-19.9c-7.3 0-12.8 1.8-16.6 5.3-3.8 3.5-5.7 8.8-5.7 15.7v104h-13.6V735.2zM1340.2 865.8c-10.2-5.7-18.1-13.9-23.7-24.6-5.6-10.6-8.5-23-8.5-37 0-13.8 2.8-26.1 8.5-37 5.6-10.8 13.6-19.3 23.9-25.4 10.3-6.1 22.1-9.1 35.3-9.1 12.9 0 24.3 3 34.1 8.9s17.4 14 22.8 24.2c5.4 10.2 8.1 21.4 8.1 33.6v3.3h-119c.2 19.1 5.1 33.7 14.7 43.9 9.6 10.2 22.6 15.3 39 15.3 12.9 0 23.9-3.4 33-10.1 9.1-6.7 15.2-15.3 18.3-25.7h13.6c-2 9.3-6 17.6-11.9 24.8-5.9 7.3-13.5 13-22.6 17.2-9.2 4.2-19.3 6.3-30.4 6.3-13.3 0-25-2.9-35.2-8.6zm86-75.4c-2-13.1-7.5-23.8-16.5-32.2-9-8.4-20.3-12.6-34-12.6-14 0-26 4.1-35.9 12.1-9.9 8.1-15.8 19-17.6 32.6h104zM1455.1 872.5v-11.2h21c24.6 0 36.8-7.4 36.8-22.1 0-5.6-2.1-10.6-6.4-15-4.3-4.4-10.6-9.6-19-15.8-6.9-4.9-12.2-8.9-16-12-3.7-3.1-7.3-7.3-10.6-12.7-3.4-5.4-5-11.4-5-18.1 0-10.4 4.1-18.5 12.4-24.3 8.3-5.8 19.6-8.7 34-8.7 8.6 0 15.3.4 20.2 1.1v12h-15.8c-12.6 0-21.9 1.4-28.1 4.1-6.2 2.7-9.3 8-9.3 15.8 0 4.4 1.2 8.3 3.6 11.7 2.4 3.5 4.9 6.3 7.5 8.5 2.6 2.2 8 6.2 16 12 9.1 6.4 16.4 12.6 22 18.8 5.5 6.2 8.3 13.4 8.3 21.6 0 10.7-3.8 19.5-11.3 26.2-7.5 6.7-19.4 10.1-35.6 10.1-10.2-.1-18.3-.7-24.7-2zM1556.3 863.5c-8.5-7.3-12.7-17.5-12.7-30.6 0-8.7 2.2-16.4 6.7-22.9s10.4-11.5 17.7-15c7.4-3.5 15.4-5.2 24.1-5.2 6.9 0 13.4 1 19.4 3 6 2 11 5 15 9h1.1v-21c0-23.3-10.7-34.9-32.2-34.9-10 0-18.1 2.7-24.2 8-6.1 5.4-9.1 12.9-9.1 22.5h-13.6c0-7.5 2-14.5 6-21.1 4-6.6 9.6-12 16.8-16.1 7.2-4.1 15.4-6.1 24.7-6.1 14.2 0 25.3 4.2 33.4 12.5 8.1 8.4 12.1 20.1 12.1 35.2v90.9h-12.3v-15.8h-1.1c-3.6 5.5-9.2 9.9-16.6 13.4-7.5 3.5-15.5 5.2-24 5.2-12.3-.1-22.8-3.7-31.2-11zm55.1-6.1c5.7-3.2 11.2-7.6 16.5-13.2V810c-4.4-2.5-9.6-4.5-15.8-5.9-6.2-1.4-12.6-2-19.1-2-10.6 0-19.1 2.5-25.5 7.6-6.5 5.1-9.7 12.6-9.7 22.6 0 10 2.7 17.5 8.2 22.4 5.5 4.9 13.3 7.4 23.5 7.4 8.8 0 16.2-1.6 21.9-4.7zM1660.5 872.5v-11.2h21c24.6 0 36.8-7.4 36.8-22.1 0-5.6-2.1-10.6-6.4-15-4.3-4.4-10.6-9.6-19-15.8-6.9-4.9-12.2-8.9-16-12-3.7-3.1-7.3-7.3-10.6-12.7-3.4-5.4-5-11.4-5-18.1 0-10.4 4.1-18.5 12.4-24.3 8.3-5.8 19.6-8.7 34-8.7 8.6 0 15.3.4 20.2 1.1v12h-15.8c-12.6 0-21.9 1.4-28.1 4.1-6.2 2.7-9.3 8-9.3 15.8 0 4.4 1.2 8.3 3.6 11.7 2.4 3.5 4.9 6.3 7.5 8.5 2.6 2.2 8 6.2 16 12 9.1 6.4 16.4 12.6 22 18.8 5.5 6.2 8.3 13.4 8.3 21.6 0 10.7-3.8 19.5-11.3 26.2-7.5 6.7-19.4 10.1-35.6 10.1-10.2-.1-18.4-.7-24.7-2z"/></g><g id="Principal"><path class="inter-logo" d="M1152.8 513.8h70.8V590h-58.9c-61.9 0-100-21.4-100-100V319.7h-42.1c-7.4 0-11.2-8.8-6.2-14.2L1138 167.4c5.2-5.7 14.7-2 14.7 5.7v70.4h70.2v76.2h-70.2v194.1zM900.3 238.2c-42.3 0-73.8 24.4-89.3 62.5h-3v-57.2h-83.9V590h88.1V321.5h58.9c25.6 0 35.1 5.4 35.1 32.7V590h88.1V337c.1-69.7-33.2-98.8-94-98.8zM571.7 590h88.1V243.5h-88.1V590zm1153.8-285.2h-3.6v-61.3h-87.5V590h93.5V326.3h82.2v-88.1h-11.3c-43.6 0-62.6 19-73.3 66.6zm-132.2 103V434H1329c4.7 51.5 37.1 85.7 86.9 85.7 44.6 0 76.8-23.8 83.3-53h94.1C1573.7 540 1506.4 596 1414.7 596c-107.2 0-179.8-76.2-179.8-176.2s70.2-181.6 182.2-181.6c105.4 0 176.2 72.6 176.2 169.6zm-261.7-37.5h170.6c-13.4-34.8-41.5-59.5-85.1-59.5-38.2 0-71.7 20.1-85.5 59.5zM110 575.2h181.2l1-5-178.7-37.1 3.5-19.8 178.7 38.6 1.5-6.9-168.8-73.3 7.4-20.3L305 522.7l3.5-5-150-111.4 15.3-24.3 149 111.4 4.5-5.9L210 338.6l28.7-23.3L355 457.4l4.9-4.5-67.8-172.3 41.1-17.3 63.9 168.8 5-1.5V243.5h65.8V590H110v-14.8z"/></g></g></svg>
               </div>
         </div>
         
         <div class="card-body px-3 py-4 d-flex flex-column align-items-center text-center">
            <h5 class="card-title pt-0 border-top-0"><?php esc_html_e( 'Flexify Checkout - Inter addon', 'flexify-checkout-for-woocommerce' ) ?></h5>
            <?php if ( ! License::is_valid() ) : ?>
               <span class="badge pro bg-primary rounded-pill ms-2 mb-3">
                  <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                  <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
            <?php endif; ?>

            <p class="card-text fs-sm mb-4"><?php esc_html_e( 'Comece a receber via Pix e Boleto com aprovação imediata e sem cobrança de taxas. Exclusivo para clientes Inter Empresas.', 'flexify-checkout-for-woocommerce' ) ?></p>
            
            <?php if ( is_plugin_active('module-inter-bank-for-flexify-checkout/module-inter-bank-for-flexify-checkout.php') ) : ?>
               <button id="require_inter_bank_module_trigger" class="btn btn-sm btn-outline-primary <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>"><?php esc_html_e( 'Configurar', 'flexify-checkout-for-woocommerce' ) ?></button>
            <?php elseif ( array_key_exists( 'module-inter-bank-for-flexify-checkout/module-inter-bank-for-flexify-checkout.php', get_plugins() ) ) : ?>
               <button class="btn btn-sm btn-primary activate-plugin <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" data-plugin-slug="<?php echo esc_attr('module-inter-bank-for-flexify-checkout/module-inter-bank-for-flexify-checkout.php'); ?>"><?php esc_html_e( 'Ativar módulo', 'flexify-checkout-for-woocommerce' ) ?></button>
            <?php else : ?>
               <button class="btn btn-sm btn-primary install-module <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" data-plugin-url="https://github.com/meumouse/module-inter-bank-for-flexify-checkout/raw/main/dist/module-inter-bank-for-flexify-checkout.zip" data-plugin-slug="<?php echo esc_attr('module-inter-bank-for-flexify-checkout/module-inter-bank-for-flexify-checkout.php'); ?>"><?php esc_html_e( 'Instalar módulo', 'flexify-checkout-for-woocommerce' ) ?></button>
            <?php endif; ?>
         </div>

         <?php
         /**
          * Add module settings for Inter bank
          * 
          * @since 3.8.0
          */
         do_action('flexify_checkout_inter_module'); ?>
      </div>

      <div class="card text-center p-0 m-4">
         <div class="card-header border-bottom w-100">
            <div class="integration-item p-4 rounded-circle">
               <svg x="0px" y="0px" viewBox="0 0 1080 1080" xml:space="preserve"><g><path fill="#141D26" d="M513.96,116.38c-234.22,0-424.07,189.86-424.07,424.07c0,234.21,189.86,424.08,424.07,424.08 c234.21,0,424.07-189.86,424.07-424.08C938.03,306.25,748.17,116.38,513.96,116.38z M685.34,542.48 c-141.76,0.37-257.11,117.68-257.41,259.44h-88.21c0-191.79,153.83-347.41,345.62-347.41V542.48z M685.34,365.84 c-141.76,0.2-266.84,69.9-346.06,176.13V410.6c91.73-82.48,212.64-133.1,346.06-133.1V365.84z"/><circle fill="#fff" cx="870.13" cy="237.99" r="120.99"/></g><g><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-miterlimit: 133.3333;" d="M808.53,271.68c-6.78-27.14-10.18-40.71-3.05-49.83c7.12-9.12,21.11-9.12,49.08-9.12h36.62 c27.97,0,41.96,0,49.08,9.12c7.12,9.12,3.73,22.69-3.05,49.83c-4.32,17.26-6.47,25.89-12.91,30.91 c-6.44,5.02-15.33,5.02-33.12,5.02h-36.62c-17.79,0-26.69,0-33.12-5.02C815,297.57,812.84,288.94,808.53,271.68z"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-miterlimit: 133.3333;" d="M932.17,216.68l-5.62-20.6c-2.17-7.94-3.25-11.92-5.47-14.91c-2.21-2.98-5.22-5.28-8.67-6.63 c-3.47-1.36-7.59-1.36-15.82-1.36 M813.56,216.68l5.62-20.6c2.17-7.94,3.25-11.92,5.47-14.91c2.21-2.98,5.22-5.28,8.67-6.63 c3.47-1.36,7.59-1.36,15.82-1.36"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-miterlimit: 133.3333;" d="M849.14,173.19c0-4.37,3.54-7.91,7.91-7.91h31.63c4.37,0,7.91,3.54,7.91,7.91c0,4.37-3.54,7.91-7.91,7.91 h-31.63C852.68,181.1,849.14,177.56,849.14,173.19z"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-linecap: round;  stroke-linejoin: round; stroke-miterlimit: 133.3333;" d="M841.24,244.36v31.63"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-linecap: round;  stroke-linejoin: round; stroke-miterlimit: 133.3333;" d="M904.5,244.36v31.63"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-linecap: round;  stroke-linejoin: round; stroke-miterlimit: 133.3333;" d="M872.87,244.36v31.63"/></g></svg>
            </div>
         </div>
         
         <div class="card-body px-3 py-4 d-flex flex-column align-items-center text-center">
            <h5 class="card-title pt-0 border-top-0"><?php esc_html_e( 'Flexify Checkout - Recuperação de carrinhos abandonados', 'flexify-checkout-for-woocommerce' ) ?></h5>
            <?php if ( ! License::is_valid() ) : ?>
               <span class="badge pro bg-primary rounded-pill ms-2 mb-3">
					<svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
					<?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
            <?php endif; ?>

            <p class="card-text fs-sm mb-4"><?php esc_html_e( 'Recupere carrinhos e pedidos abandonados com follow up cadenciado. Envie notificações via WhatsApp de forma automática e muito mais!', 'flexify-checkout-for-woocommerce' ) ?></p>
            
            <?php if ( is_plugin_active('flexify-checkout-recovery-carts-addon/flexify-checkout-recovery-carts-addon.php') ) : ?>
               	<a class="btn btn-sm btn-outline-primary <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" href="<?php echo admin_url('admin.php?page=fc-recovery-carts-settings') ?>"><?php esc_html_e( 'Configurar', 'flexify-checkout-for-woocommerce' ) ?></a>
            <?php elseif ( array_key_exists( 'flexify-checkout-recovery-carts-addon/flexify-checkout-recovery-carts-addon.php', get_plugins() ) ) : ?>
               	<button class="btn btn-sm btn-primary activate-plugin <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" data-plugin-slug="<?php echo esc_attr('flexify-checkout-recovery-carts-addon/flexify-checkout-recovery-carts-addon.php'); ?>"><?php esc_html_e( 'Ativar módulo', 'flexify-checkout-for-woocommerce' ) ?></button>
            <?php else : ?>
               	<button class="btn btn-sm btn-primary install-module <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" data-plugin-url="https://github.com/meumouse/flexify-checkout-recovery-carts-addon/raw/refs/heads/main/dist/flexify-checkout-recovery-carts-addon.zip" data-plugin-slug="<?php echo esc_attr('flexify-checkout-recovery-carts-addon/flexify-checkout-recovery-carts-addon.php'); ?>"><?php esc_html_e( 'Instalar módulo', 'flexify-checkout-for-woocommerce' ) ?></button>
            <?php endif; ?>
         </div>

         <?php
         /**
          * Add module settings for Inter bank
          * 
          * @since 4.1.0
          */
         do_action('flexify_checkout_recovery_carts_settings'); ?>
      </div>

      <div class="card text-center p-0 m-4">
         <div class="card-header border-bottom w-100">
               <div class="integration-item p-4 rounded-circle">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 703 882.5"><path d="M908.66,248V666a126.5,126.5,0,0,1-207.21,97.41l-16.7-16.7L434.08,496.07l-62-62a47.19,47.19,0,0,0-72,30.86V843.36a47.52,47.52,0,0,0,69.57,35.22l19.3-19.3,56-56,81.19-81.19,10.44-10.44a47.65,47.65,0,0,1,67.63,65.05l-13,13L428.84,952.12l-9.59,9.59a128,128,0,0,1-213.59-95.18V413.17a124.52,124.52,0,0,1,199.78-82.54l22.13,22.13L674.45,599.64l46.22,46.22,17,17a47.8,47.8,0,0,0,71-31.44V270.19a48.19,48.19,0,0,0-75-40.05L720.43,243.4l-68.09,68.09L575.7,388.13a48.39,48.39,0,0,1-67.43-67.93L680,148.46A136,136,0,0,1,908.66,248Z" transform="translate(-205.66 -112.03)" style="fill:#22c55e"/></svg>
               </div>
         </div>
         
         <div class="card-body px-3 py-4 d-flex flex-column align-items-center text-center">
               <h5 class="card-title pt-0 border-top-0"><?php esc_html_e( 'Joinotify', 'flexify-checkout-for-woocommerce' ) ?></h5>
               <p class="card-text fs-sm mb-4"><?php esc_html_e( 'Automatize o envio de mensagens via WhatsApp ao receber eventos no Flexify Checkout, recupere vendas de maneira mais assertiva. Deixe tudo no fluxo certo sem esforço!', 'flexify-checkout-for-woocommerce' ) ?></p>

               <?php if ( is_plugin_active('joinotify/joinotify.php') ) : ?>
                  <a class="btn btn-sm btn-outline-primary <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" href="<?php echo admin_url('admin.php?page=joinotify-settings') ?>"><?php esc_html_e( 'Configurar', 'flexify-checkout-for-woocommerce' ) ?></a>
               <?php elseif ( array_key_exists( 'joinotify/joinotify.php', get_plugins() ) ) : ?>
                  <button class="btn btn-sm btn-primary activate-plugin <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" data-plugin-slug="<?php echo esc_attr('joinotify/joinotify.php'); ?>"><?php esc_html_e( 'Ativar módulo', 'flexify-checkout-for-woocommerce' ) ?></button>
               <?php else : ?>
                  <button class="btn btn-sm btn-primary install-module <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" data-plugin-url="https://github.com/meumouse/joinotify/raw/refs/heads/main/dist/joinotify.zip" data-plugin-slug="<?php echo esc_attr('joinotify/joinotify.php'); ?>"><?php esc_html_e( 'Instalar módulo', 'flexify-checkout-for-woocommerce' ) ?></button>
               <?php endif; ?>
         </div>

         <?php
         /**
          * Add module settings for Joinotify
          * 
          * @since 3.8.0
          */
         do_action('flexify_checkout_joinotify'); ?>
      </div>

      <div class="card text-center p-0 m-4">
         <div class="card-header border-bottom w-100">
               <div class="integration-item p-4 rounded-circle">
                  <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-label="Google Maps" role="img" viewBox="0 0 512 512" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <rect id="a" width="512" height="512" x="0" y="0" rx="15%" fill="#ffffff"></rect> <clipPath id="b"> <use xlink:href="#a"></use> </clipPath> <g clip-path="url(#b)"> <path fill="#35a85b" d="M0 512V0h512z"></path> <path fill="#5881ca" d="M256 288L32 512h448z"></path> <path fill="#c1c0be" d="M288 256L512 32v448z"></path> <path stroke="#fadb2a" stroke-width="71" d="M0 512L512 0"></path> <path fill="none" stroke="#f2f2f2" stroke-width="22" d="M175 173h50a50 54 0 1 1-15-41"></path> <path fill="#de3738" d="M353 85a70 70 0 0 1 140 0c0 70-70 70-70 157 0-87-70-87-70-157"></path> <circle cx="423" cy="89" r="25" fill="#7d2426"></circle> </g> </g></svg>
               </div>
         </div>
         
         <div class="card-body px-3 py-4 d-flex flex-column align-items-center text-center">
               <h5 class="card-title pt-0 border-top-0"><?php esc_html_e( 'Google Maps', 'flexify-checkout-for-woocommerce' ) ?></h5>
               <p class="card-text fs-sm mb-4"><?php esc_html_e( 'Facilite o preenchimento do endereço de entrega aos usuários, permitindo pesquisar seu endereço ou usando recursos de geolocalização com Google Maps.', 'flexify-checkout-for-woocommerce' ) ?></p>

               <div class="badge bg-translucent-primary rounded-pill py-2 px-3 fs-md"><?php esc_html_e( 'Em breve', 'flexify-checkout-for-woocommerce' ) ?></div>
         </div>

         <?php
         /**
          * Add module settings for Google Maps
          * 
          * @since 3.8.0
          */
         do_action('flexify_checkout_google_maps'); ?>
      </div>
   </div>

   <?php
   /**
    * Hook for display custom fields options
    * 
    * @since 3.6.0
    */
   do_action('flexify_checkout_after_integrations_options'); ?>
</div>