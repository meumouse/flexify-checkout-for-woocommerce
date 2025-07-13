<?php

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\API\License;

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div id="styles" class="nav-content">
   <table class="form-table">
      <?php
      /**
       * Hook for display custom design options
       * 
       * @since 3.6.0
       */
      do_action('flexify_checkout_before_design_options'); ?>

      <tr>
         <th>
            <?php esc_html_e( 'Selecione um tema', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Selecione um tema que será carregado na página de finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
      </tr>

      <tr>
         <td>
            <div class="card card-theme-item active">
               <svg id="flexify-checkout-theme-modern" class="card-img-top" viewBox="0 0 466.75 301.44"><rect x="0.13" y="112.8" width="131.04" height="21.6" rx="5" style="fill:#e5e5e5"/><rect x="141.73" y="112.8" width="126.48" height="21.6" rx="4.91" style="fill:#e5e5e5"/><rect x="0.13" y="147.84" width="268.08" height="21.6" rx="5" style="fill:#e5e5e5"/><rect x="0.13" y="182.6" width="268.08" height="21.6" rx="5" style="fill:#e5e5e5"/><rect x="175.93" y="218.46" width="92.28" height="21.6" rx="5" style="fill:#141d26"/><rect x="412.45" y="147.84" width="54.3" height="21.6" rx="5" style="fill:#141d26"/><rect x="294.85" y="147.84" width="111.84" height="21.6" rx="5" style="fill:#e5e5e5"/><line x1="281.69" y1="301.44" x2="281.35" y2="301.44" style="fill:#e5e5e5"/><line x1="281.35" x2="281.69" style="fill:#e5e5e5"/><rect x="294.85" y="53.76" width="43.44" height="43.44" rx="5" style="fill:#e5e5e5"/><rect x="348.73" y="54.6" width="100.08" height="7.08" rx="2.66" style="fill:#e5e5e5"/><rect x="348.73" y="104.52" width="50.04" height="14.52" rx="3" style="fill:#e5e5e5"/><rect x="435.7" y="104.52" width="31.05" height="14.52" rx="3" style="fill:#e5e5e5"/><rect x="348.73" y="66.84" width="81.96" height="7.08" rx="2.41" style="fill:#e5e5e5"/><rect x="348.73" y="79.32" width="69.84" height="7.08" rx="2.22" style="fill:#e5e5e5"/><rect x="294.85" y="193.4" width="26.34" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="294.85" y="207.76" width="57.84" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="437.62" y="193.4" width="26.34" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="437.62" y="207.77" width="26.34" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="430.6" y="236.43" width="33.36" height="8.67" rx="2" style="fill:#e5e5e5"/><rect x="294.85" y="235.73" width="26.34" height="8.67" rx="2" style="fill:#e5e5e5"/><line x1="463.96" y1="182.52" x2="463.96" y2="182.71" style="fill:#e5e5e5"/><line x1="294.85" y1="182.71" x2="294.85" y2="182.52" style="fill:#e5e5e5"/><line x1="463.96" y1="226.73" x2="463.96" y2="226.92" style="fill:#e5e5e5"/><line x1="294.85" y1="226.92" x2="294.85" y2="226.73" style="fill:#e5e5e5"/><rect x="0.26" y="58.56" width="35.15" height="7.08" rx="1.58" style="fill:#e5e5e5"/><rect x="41.11" y="58.56" width="35.15" height="7.08" rx="1.58" style="fill:#e5e5e5"/><rect x="81.19" y="58.56" width="35.15" height="7.08" rx="1.58" style="fill:#e5e5e5"/><path d="M30,21.07A13.35,13.35,0,1,0,43.32,34.42,13.34,13.34,0,0,0,30,21.07Zm5.4,13.41a8.18,8.18,0,0,0-8.1,8.17H24.49A10.89,10.89,0,0,1,35.37,31.71Zm0-5.56a13.61,13.61,0,0,0-10.89,5.54V30.33a16.18,16.18,0,0,1,10.89-4.19Z" transform="translate(-16.63 -1.38)" style="fill:#141d26"/><circle cx="24.56" cy="23.52" r="3.81" style="fill:#fff"/><path d="M39.24,26c-.21-.86-.32-1.28-.09-1.57s.66-.29,1.54-.29h1.16c.88,0,1.32,0,1.54.29s.12.71-.1,1.57c-.13.54-.2.81-.4,1s-.48.16-1,.16H40.69c-.56,0-.84,0-1-.16S39.38,26.5,39.24,26Z" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-miterlimit:133.33332824707;stroke-width:0.5px"/><path d="M43.14,24.23,43,23.58a1.46,1.46,0,0,0-.17-.47.58.58,0,0,0-.28-.21,1.51,1.51,0,0,0-.49,0M39.4,24.23l.18-.65a1.46,1.46,0,0,1,.17-.47A.6.6,0,0,1,40,22.9a1.55,1.55,0,0,1,.5,0" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-miterlimit:133.33332824707;stroke-width:0.5px"/><path d="M40.52,22.86a.25.25,0,0,1,.25-.25h1a.25.25,0,0,1,.25.25.26.26,0,0,1-.25.25h-1A.25.25,0,0,1,40.52,22.86Z" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-miterlimit:133.33332824707;stroke-width:0.5px"/><path d="M40.27,25.1v1" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-linecap:round;stroke-linejoin:round;stroke-width:0.5px"/><path d="M42.26,25.1v1" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-linecap:round;stroke-linejoin:round;stroke-width:0.5px"/><path d="M41.27,25.1v1" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-linecap:round;stroke-linejoin:round;stroke-width:0.5px"/></svg>
               <div class="card-body">
                  <h5 class="card-title"><?php esc_html_e( 'Moderno', 'flexify-checkout-for-woocommerce' ) ?></h5>
                  <button id="modern_theme" class="btn btn-sm btn-primary"><?php esc_html_e( 'Ativado', 'flexify-checkout-for-woocommerce' ) ?></button>
                  <input type="hidden" name="flexify_checkout_theme_modern" value="<?php echo Admin_Options::get_setting('flexify_checkout_theme_modern') ?>"/>
               </div>
            </div>
         </td>

         <td>
            <div class="card card-theme-item coming-soon">
               <div class="coming-soon-message">
                  <svg class="flexify-checkout-coming-soon-theme" viewBox="0 0 512 512"  xml:space="preserve"><style type="text/css">.st0{fill:#000000;}</style><g><path class="st0" d="M315.883,231.15l82.752-115.13c7.152-9.942,11.039-21.784,11.039-33.93V46.13h23.911V0H78.415v46.13h23.912 v35.96c0,12.145,3.886,23.988,11.039,33.93l82.752,115.13c2.963,4.136,4.472,8.857,4.483,13.665v22.36 c-0.011,4.808-1.52,9.53-4.483,13.665l-82.752,115.141c-7.154,9.942-11.039,21.783-11.039,33.918v35.971H78.415V512h355.169 v-46.129h-23.911V429.9c0-12.135-3.887-23.976-11.039-33.918L315.883,280.84c-2.963-4.136-4.482-8.857-4.482-13.665v-22.36 C311.401,240.007,312.92,235.286,315.883,231.15z M386.609,461.257H125.393V429.9c0-7.229,2.291-14.317,6.696-20.46l82.753-115.141 c5.708-7.934,8.824-17.41,8.824-27.124v-22.36c0-9.714-3.115-19.202-8.824-27.124L132.1,102.561  c-4.417-6.155-6.708-13.232-6.708-20.471V50.743h261.216V82.09c-0.011,7.239-2.291,14.316-6.709,20.471l-82.752,115.13 c-5.698,7.922-8.813,17.41-8.813,27.124v22.36c0,9.714,3.114,19.19,8.813,27.124l82.763,115.141 c4.407,6.143,6.686,13.231,6.698,20.46V461.257z"/><path class="st0" d="M236.268,232.929h39.466c1.672-8.314,5.091-16.237,10.181-23.314l59.491-82.774H166.595l59.492,82.774 C231.177,216.692,234.585,224.616,236.268,232.929z"/><path class="st0" d="M246.753,381.588l-65.82,65.831h150.134l-65.82-65.831C260.137,376.487,251.865,376.487,246.753,381.588z"/><path class="st0" d="M255.632,247.995c-5.688,0-10.301,4.614-10.301,10.312c0,5.688,4.614,10.3,10.301,10.3 c5.687,0,10.311-4.612,10.311-10.3C265.943,252.609,261.319,247.995,255.632,247.995z"/><path class="st0" d="M255.632,289.513c-5.688,0-10.301,4.613-10.301,10.3c0,5.698,4.614,10.312,10.301,10.312 c5.687,0,10.311-4.614,10.311-10.312C265.943,294.126,261.319,289.513,255.632,289.513z"/><path class="st0" d="M255.632,332.245c-5.688,0-10.301,4.613-10.301,10.311c0,5.687,4.614,10.312,10.301,10.312 c5.687,0,10.311-4.625,10.311-10.312C265.943,336.858,261.319,332.245,255.632,332.245z"/> </g></svg>
                     <span class="coming-soon-title"><?php esc_html_e( 'Em breve...', 'flexify-checkout-for-woocommerce' ) ?></span>
                  </div>
               <svg class="card-img-top" viewBox="0 0 466.75 301.44"><rect x="0.13" y="112.8" width="131.04" height="21.6" rx="5" style="fill:#e5e5e5"/><rect x="141.73" y="112.8" width="126.48" height="21.6" rx="4.91" style="fill:#e5e5e5"/><rect x="0.13" y="147.84" width="268.08" height="21.6" rx="5" style="fill:#e5e5e5"/><rect x="0.13" y="182.6" width="268.08" height="21.6" rx="5" style="fill:#e5e5e5"/><rect x="175.93" y="218.46" width="92.28" height="21.6" rx="5" style="fill:#141d26"/><rect x="412.45" y="147.84" width="54.3" height="21.6" rx="5" style="fill:#141d26"/><rect x="294.85" y="147.84" width="111.84" height="21.6" rx="5" style="fill:#e5e5e5"/><line x1="281.69" y1="301.44" x2="281.35" y2="301.44" style="fill:#e5e5e5"/><line x1="281.35" x2="281.69" style="fill:#e5e5e5"/><rect x="294.85" y="53.76" width="43.44" height="43.44" rx="5" style="fill:#e5e5e5"/><rect x="348.73" y="54.6" width="100.08" height="7.08" rx="2.66" style="fill:#e5e5e5"/><rect x="348.73" y="104.52" width="50.04" height="14.52" rx="3" style="fill:#e5e5e5"/><rect x="435.7" y="104.52" width="31.05" height="14.52" rx="3" style="fill:#e5e5e5"/><rect x="348.73" y="66.84" width="81.96" height="7.08" rx="2.41" style="fill:#e5e5e5"/><rect x="348.73" y="79.32" width="69.84" height="7.08" rx="2.22" style="fill:#e5e5e5"/><rect x="294.85" y="193.4" width="26.34" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="294.85" y="207.76" width="57.84" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="437.62" y="193.4" width="26.34" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="437.62" y="207.77" width="26.34" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="430.6" y="236.43" width="33.36" height="8.67" rx="2" style="fill:#e5e5e5"/><rect x="294.85" y="235.73" width="26.34" height="8.67" rx="2" style="fill:#e5e5e5"/><line x1="463.96" y1="182.52" x2="463.96" y2="182.71" style="fill:#e5e5e5"/><line x1="294.85" y1="182.71" x2="294.85" y2="182.52" style="fill:#e5e5e5"/><line x1="463.96" y1="226.73" x2="463.96" y2="226.92" style="fill:#e5e5e5"/><line x1="294.85" y1="226.92" x2="294.85" y2="226.73" style="fill:#e5e5e5"/><rect x="0.26" y="58.56" width="35.15" height="7.08" rx="1.58" style="fill:#e5e5e5"/><rect x="41.11" y="58.56" width="35.15" height="7.08" rx="1.58" style="fill:#e5e5e5"/><rect x="81.19" y="58.56" width="35.15" height="7.08" rx="1.58" style="fill:#e5e5e5"/><path d="M30,21.07A13.35,13.35,0,1,0,43.32,34.42,13.34,13.34,0,0,0,30,21.07Zm5.4,13.41a8.18,8.18,0,0,0-8.1,8.17H24.49A10.89,10.89,0,0,1,35.37,31.71Zm0-5.56a13.61,13.61,0,0,0-10.89,5.54V30.33a16.18,16.18,0,0,1,10.89-4.19Z" transform="translate(-16.63 -1.38)" style="fill:#141d26"/><circle cx="24.56" cy="23.52" r="3.81" style="fill:#fff"/><path d="M39.24,26c-.21-.86-.32-1.28-.09-1.57s.66-.29,1.54-.29h1.16c.88,0,1.32,0,1.54.29s.12.71-.1,1.57c-.13.54-.2.81-.4,1s-.48.16-1,.16H40.69c-.56,0-.84,0-1-.16S39.38,26.5,39.24,26Z" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-miterlimit:133.33332824707;stroke-width:0.5px"/><path d="M43.14,24.23,43,23.58a1.46,1.46,0,0,0-.17-.47.58.58,0,0,0-.28-.21,1.51,1.51,0,0,0-.49,0M39.4,24.23l.18-.65a1.46,1.46,0,0,1,.17-.47A.6.6,0,0,1,40,22.9a1.55,1.55,0,0,1,.5,0" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-miterlimit:133.33332824707;stroke-width:0.5px"/><path d="M40.52,22.86a.25.25,0,0,1,.25-.25h1a.25.25,0,0,1,.25.25.26.26,0,0,1-.25.25h-1A.25.25,0,0,1,40.52,22.86Z" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-miterlimit:133.33332824707;stroke-width:0.5px"/><path d="M40.27,25.1v1" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-linecap:round;stroke-linejoin:round;stroke-width:0.5px"/><path d="M42.26,25.1v1" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-linecap:round;stroke-linejoin:round;stroke-width:0.5px"/><path d="M41.27,25.1v1" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-linecap:round;stroke-linejoin:round;stroke-width:0.5px"/></svg>
               <div class="card-body">
                  <h5 class="card-title"><h5 class="card-title"><?php esc_html_e( 'Escuro', 'flexify-checkout-for-woocommerce' ) ?></h5></h5>
                  <button class="btn btn-sm btn-outline-primary" name="dark_theme" disabled><?php esc_html_e( 'Ativar', 'flexify-checkout-for-woocommerce' ) ?></button>
               </div>
            </div>
         </td>

         <td>
            <div class="card card-theme-item coming-soon">
               <div class="coming-soon-message">
                  <svg class="flexify-checkout-coming-soon-theme" viewBox="0 0 512 512"  xml:space="preserve"><style type="text/css">.st0{fill:#000000;}</style><g><path class="st0" d="M315.883,231.15l82.752-115.13c7.152-9.942,11.039-21.784,11.039-33.93V46.13h23.911V0H78.415v46.13h23.912 v35.96c0,12.145,3.886,23.988,11.039,33.93l82.752,115.13c2.963,4.136,4.472,8.857,4.483,13.665v22.36 c-0.011,4.808-1.52,9.53-4.483,13.665l-82.752,115.141c-7.154,9.942-11.039,21.783-11.039,33.918v35.971H78.415V512h355.169 v-46.129h-23.911V429.9c0-12.135-3.887-23.976-11.039-33.918L315.883,280.84c-2.963-4.136-4.482-8.857-4.482-13.665v-22.36 C311.401,240.007,312.92,235.286,315.883,231.15z M386.609,461.257H125.393V429.9c0-7.229,2.291-14.317,6.696-20.46l82.753-115.141 c5.708-7.934,8.824-17.41,8.824-27.124v-22.36c0-9.714-3.115-19.202-8.824-27.124L132.1,102.561  c-4.417-6.155-6.708-13.232-6.708-20.471V50.743h261.216V82.09c-0.011,7.239-2.291,14.316-6.709,20.471l-82.752,115.13 c-5.698,7.922-8.813,17.41-8.813,27.124v22.36c0,9.714,3.114,19.19,8.813,27.124l82.763,115.141 c4.407,6.143,6.686,13.231,6.698,20.46V461.257z"/><path class="st0" d="M236.268,232.929h39.466c1.672-8.314,5.091-16.237,10.181-23.314l59.491-82.774H166.595l59.492,82.774 C231.177,216.692,234.585,224.616,236.268,232.929z"/><path class="st0" d="M246.753,381.588l-65.82,65.831h150.134l-65.82-65.831C260.137,376.487,251.865,376.487,246.753,381.588z"/><path class="st0" d="M255.632,247.995c-5.688,0-10.301,4.614-10.301,10.312c0,5.688,4.614,10.3,10.301,10.3 c5.687,0,10.311-4.612,10.311-10.3C265.943,252.609,261.319,247.995,255.632,247.995z"/><path class="st0" d="M255.632,289.513c-5.688,0-10.301,4.613-10.301,10.3c0,5.698,4.614,10.312,10.301,10.312 c5.687,0,10.311-4.614,10.311-10.312C265.943,294.126,261.319,289.513,255.632,289.513z"/><path class="st0" d="M255.632,332.245c-5.688,0-10.301,4.613-10.301,10.311c0,5.687,4.614,10.312,10.301,10.312 c5.687,0,10.311-4.625,10.311-10.312C265.943,336.858,261.319,332.245,255.632,332.245z"/> </g></svg>
                     <span class="coming-soon-title"><?php esc_html_e( 'Em breve...', 'flexify-checkout-for-woocommerce' ) ?></span>
                  </div>
               <svg class="card-img-top" viewBox="0 0 466.75 301.44"><rect x="0.13" y="112.8" width="131.04" height="21.6" rx="5" style="fill:#e5e5e5"/><rect x="141.73" y="112.8" width="126.48" height="21.6" rx="4.91" style="fill:#e5e5e5"/><rect x="0.13" y="147.84" width="268.08" height="21.6" rx="5" style="fill:#e5e5e5"/><rect x="0.13" y="182.6" width="268.08" height="21.6" rx="5" style="fill:#e5e5e5"/><rect x="175.93" y="218.46" width="92.28" height="21.6" rx="5" style="fill:#141d26"/><rect x="412.45" y="147.84" width="54.3" height="21.6" rx="5" style="fill:#141d26"/><rect x="294.85" y="147.84" width="111.84" height="21.6" rx="5" style="fill:#e5e5e5"/><line x1="281.69" y1="301.44" x2="281.35" y2="301.44" style="fill:#e5e5e5"/><line x1="281.35" x2="281.69" style="fill:#e5e5e5"/><rect x="294.85" y="53.76" width="43.44" height="43.44" rx="5" style="fill:#e5e5e5"/><rect x="348.73" y="54.6" width="100.08" height="7.08" rx="2.66" style="fill:#e5e5e5"/><rect x="348.73" y="104.52" width="50.04" height="14.52" rx="3" style="fill:#e5e5e5"/><rect x="435.7" y="104.52" width="31.05" height="14.52" rx="3" style="fill:#e5e5e5"/><rect x="348.73" y="66.84" width="81.96" height="7.08" rx="2.41" style="fill:#e5e5e5"/><rect x="348.73" y="79.32" width="69.84" height="7.08" rx="2.22" style="fill:#e5e5e5"/><rect x="294.85" y="193.4" width="26.34" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="294.85" y="207.76" width="57.84" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="437.62" y="193.4" width="26.34" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="437.62" y="207.77" width="26.34" height="7.08" rx="2" style="fill:#e5e5e5"/><rect x="430.6" y="236.43" width="33.36" height="8.67" rx="2" style="fill:#e5e5e5"/><rect x="294.85" y="235.73" width="26.34" height="8.67" rx="2" style="fill:#e5e5e5"/><line x1="463.96" y1="182.52" x2="463.96" y2="182.71" style="fill:#e5e5e5"/><line x1="294.85" y1="182.71" x2="294.85" y2="182.52" style="fill:#e5e5e5"/><line x1="463.96" y1="226.73" x2="463.96" y2="226.92" style="fill:#e5e5e5"/><line x1="294.85" y1="226.92" x2="294.85" y2="226.73" style="fill:#e5e5e5"/><rect x="0.26" y="58.56" width="35.15" height="7.08" rx="1.58" style="fill:#e5e5e5"/><rect x="41.11" y="58.56" width="35.15" height="7.08" rx="1.58" style="fill:#e5e5e5"/><rect x="81.19" y="58.56" width="35.15" height="7.08" rx="1.58" style="fill:#e5e5e5"/><path d="M30,21.07A13.35,13.35,0,1,0,43.32,34.42,13.34,13.34,0,0,0,30,21.07Zm5.4,13.41a8.18,8.18,0,0,0-8.1,8.17H24.49A10.89,10.89,0,0,1,35.37,31.71Zm0-5.56a13.61,13.61,0,0,0-10.89,5.54V30.33a16.18,16.18,0,0,1,10.89-4.19Z" transform="translate(-16.63 -1.38)" style="fill:#141d26"/><circle cx="24.56" cy="23.52" r="3.81" style="fill:#fff"/><path d="M39.24,26c-.21-.86-.32-1.28-.09-1.57s.66-.29,1.54-.29h1.16c.88,0,1.32,0,1.54.29s.12.71-.1,1.57c-.13.54-.2.81-.4,1s-.48.16-1,.16H40.69c-.56,0-.84,0-1-.16S39.38,26.5,39.24,26Z" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-miterlimit:133.33332824707;stroke-width:0.5px"/><path d="M43.14,24.23,43,23.58a1.46,1.46,0,0,0-.17-.47.58.58,0,0,0-.28-.21,1.51,1.51,0,0,0-.49,0M39.4,24.23l.18-.65a1.46,1.46,0,0,1,.17-.47A.6.6,0,0,1,40,22.9a1.55,1.55,0,0,1,.5,0" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-miterlimit:133.33332824707;stroke-width:0.5px"/><path d="M40.52,22.86a.25.25,0,0,1,.25-.25h1a.25.25,0,0,1,.25.25.26.26,0,0,1-.25.25h-1A.25.25,0,0,1,40.52,22.86Z" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-miterlimit:133.33332824707;stroke-width:0.5px"/><path d="M40.27,25.1v1" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-linecap:round;stroke-linejoin:round;stroke-width:0.5px"/><path d="M42.26,25.1v1" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-linecap:round;stroke-linejoin:round;stroke-width:0.5px"/><path d="M41.27,25.1v1" transform="translate(-16.63 -1.38)" style="fill:none;stroke:#141d26;stroke-linecap:round;stroke-linejoin:round;stroke-width:0.5px"/></svg>
               <div class="card-body">
                  <h5 class="card-title"><h5 class="card-title"><?php esc_html_e( 'Página única', 'flexify-checkout-for-woocommerce' ) ?></h5></h5>
                  <button class="btn btn-sm btn-outline-primary" name="one_page_theme" disabled><?php esc_html_e( 'Ativar', 'flexify-checkout-for-woocommerce' ) ?></button>
               </div>
            </div>
         </td>
      </tr>

      <tr class="container-separator"></tr>

      <tr>
         <th>
            <?php esc_html_e( 'Tipo de marca no cabeçalho', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Selecione o tipo de marca que será exibida no cabeçalho da página de finalização de compra.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <select id="checkout_header_type" name="checkout_header_type" class="form-select">
               <option value="logo" <?php echo ( Admin_Options::get_setting('checkout_header_type') === 'logo' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'Imagem (Padrão)', 'flexify-checkout-for-woocommerce' ) ?></option>
               <option value="text" <?php echo ( Admin_Options::get_setting('checkout_header_type') === 'text' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'Texto', 'flexify-checkout-for-woocommerce' ) ?></option>
            </select>
         </td>
      </tr>

      <tr class="header-styles-option-logo">
         <th>
            <?php esc_html_e( 'Imagem de cabeçalho', 'flexify-checkout-for-woocommerce' ) ?>
         </th>
         <td>
            <div class="input-group">
               <input type="text" name="search_image_header_checkout" class="form-control" value="<?php echo Admin_Options::get_setting('search_image_header_checkout') ?>"/>
               <button id="flexify-checkout-search-header-logo" class="input-group-button btn btn-outline-secondary"><?php esc_html_e( 'Procurar', 'flexify-checkout-for-woocommerce' ) ?></button>
            </div>
         </td>
      </tr>

      <tr class="header-styles-option-logo">
         <th>
            <?php esc_html_e( 'Link da imagem de cabeçalho', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Informe o link da imagem do cabeçalho.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <input type="text" name="logo_header_link" class="form-control input-control-wd-20" value="<?php echo esc_attr( Admin_Options::get_setting('logo_header_link') ) ?>"/>
         </td>
      </tr>

      <tr class="header-styles-option-logo">
         <th>
            <?php esc_html_e( 'Largura da imagem de cabeçalho', 'flexify-checkout-for-woocommerce' ) ?>
         </th>
         <td>
            <div class="input-group">
               <input type="text" name="header_width_image_checkout" class="form-control input-control-wd-5 allow-numbers-be-1" value="<?php echo Admin_Options::get_setting('header_width_image_checkout') ?>"/>
               <select id="unit_header_width_image_checkout" class="form-select" name="unit_header_width_image_checkout">
                  <option value="px" <?php echo ( Admin_Options::get_setting('unit_header_width_image_checkout') == 'px' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'px', 'flexify-checkout-for-woocommerce' ) ?></option>
                  <option value="em" <?php echo ( Admin_Options::get_setting('unit_header_width_image_checkout') == 'em' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'em', 'flexify-checkout-for-woocommerce' ) ?></option>
                  <option value="rem" <?php echo ( Admin_Options::get_setting('unit_header_width_image_checkout') == 'rem' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'rem', 'flexify-checkout-for-woocommerce' ) ?></option>
               </select>
            </div>
         </td>
      </tr>

      <tr class="header-styles-option-text">
         <th>
            <?php esc_html_e( 'Texto do cabeçalho', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Informe o texto que será exibido no cabeçalho da página de finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <input type="text" name="text_brand_checkout_header" class="form-control" placeholder="<?php esc_html_e( 'CHECKOUT', 'flexify-checkout-for-woocommerce' ) ?>" value="<?php echo Admin_Options::get_setting('text_brand_checkout_header') ?>"/>
         </td>
      </tr>
      
      <tr class="container-separator"></tr>

      <tr>
         <th>
            <?php esc_html_e( 'Cabeçalho personalizado', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Adicione seu cabeçalho personalizado informando o shortcode.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <input type="text" name="shortcode_header" class="form-control input-control-wd-20" placeholder="<?php esc_html_e( '[shortcode id="100"]', 'flexify-checkout-for-woocommerce' ) ?>" value="<?php echo esc_attr( Admin_Options::get_setting('shortcode_header') ) ?>"/>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Rodapé personalizado', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Adicione seu rodapé personalizado informando o shortcode.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <input type="text" name="shortcode_footer" class="form-control input-control-wd-20" placeholder="<?php esc_html_e( '[shortcode id="101"]', 'flexify-checkout-for-woocommerce' ) ?>" value="<?php echo esc_attr( Admin_Options::get_setting('shortcode_footer') ) ?>"/>
         </td>
      </tr>
      
      <tr class="container-separator"></tr>

      <tr>
         <th>
            <?php esc_html_e( 'Cor primária', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'A cor primária define a cor dos elementos que terão ações ou informações na página de finalização de compras.' ) ?></span>
         </th>
         <td>
            <div class="color-container input-group">
               <input type="color" name="set_primary_color" class="form-control-color" value="<?php echo Admin_Options::get_setting('set_primary_color') ?>"/>
               <input type="text" class="get-color-selected form-control input-control-wd-10" value="<?php echo Admin_Options::get_setting('set_primary_color') ?>"/>
               <button class="btn btn-outline-secondary btn-icon reset-color fcw-tooltip" data-color="#141D26" data-text="<?php esc_html_e( 'Redefinir para cor padrão', 'flexify-checkout-for-woocommerce' ) ?>">
                  <svg class="icon-button" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 16c1.671 0 3-1.331 3-3s-1.329-3-3-3-3 1.331-3 3 1.329 3 3 3z"></path><path d="M20.817 11.186a8.94 8.94 0 0 0-1.355-3.219 9.053 9.053 0 0 0-2.43-2.43 8.95 8.95 0 0 0-3.219-1.355 9.028 9.028 0 0 0-1.838-.18V2L8 5l3.975 3V6.002c.484-.002.968.044 1.435.14a6.961 6.961 0 0 1 2.502 1.053 7.005 7.005 0 0 1 1.892 1.892A6.967 6.967 0 0 1 19 13a7.032 7.032 0 0 1-.55 2.725 7.11 7.11 0 0 1-.644 1.188 7.2 7.2 0 0 1-.858 1.039 7.028 7.028 0 0 1-3.536 1.907 7.13 7.13 0 0 1-2.822 0 6.961 6.961 0 0 1-2.503-1.054 7.002 7.002 0 0 1-1.89-1.89A6.996 6.996 0 0 1 5 13H3a9.02 9.02 0 0 0 1.539 5.034 9.096 9.096 0 0 0 2.428 2.428A8.95 8.95 0 0 0 12 22a9.09 9.09 0 0 0 1.814-.183 9.014 9.014 0 0 0 3.218-1.355 8.886 8.886 0 0 0 1.331-1.099 9.228 9.228 0 0 0 1.1-1.332A8.952 8.952 0 0 0 21 13a9.09 9.09 0 0 0-.183-1.814z"></path></svg>
               </button>
            </div>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Cor secundára', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'A cor secundária define a cor dos elementos que terão ações ou informações na página de finalização de compras.' ) ?></span>
         </th>
         <td>
            <div class="color-container input-group">
               <input type="color" name="set_primary_color_on_hover" class="form-control-color" value="<?php echo Admin_Options::get_setting('set_primary_color_on_hover') ?>"/>
               <input type="text" class="get-color-selected form-control input-control-wd-10" value="<?php echo Admin_Options::get_setting('set_primary_color_on_hover') ?>"/>
               <button class="btn btn-outline-secondary btn-icon reset-color fcw-tooltip" data-color="#33404d" data-text="<?php esc_html_e( 'Redefinir para cor padrão', 'flexify-checkout-for-woocommerce' ) ?>">
                  <svg class="icon-button" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 16c1.671 0 3-1.331 3-3s-1.329-3-3-3-3 1.331-3 3 1.329 3 3 3z"></path><path d="M20.817 11.186a8.94 8.94 0 0 0-1.355-3.219 9.053 9.053 0 0 0-2.43-2.43 8.95 8.95 0 0 0-3.219-1.355 9.028 9.028 0 0 0-1.838-.18V2L8 5l3.975 3V6.002c.484-.002.968.044 1.435.14a6.961 6.961 0 0 1 2.502 1.053 7.005 7.005 0 0 1 1.892 1.892A6.967 6.967 0 0 1 19 13a7.032 7.032 0 0 1-.55 2.725 7.11 7.11 0 0 1-.644 1.188 7.2 7.2 0 0 1-.858 1.039 7.028 7.028 0 0 1-3.536 1.907 7.13 7.13 0 0 1-2.822 0 6.961 6.961 0 0 1-2.503-1.054 7.002 7.002 0 0 1-1.89-1.89A6.996 6.996 0 0 1 5 13H3a9.02 9.02 0 0 0 1.539 5.034 9.096 9.096 0 0 0 2.428 2.428A8.95 8.95 0 0 0 12 22a9.09 9.09 0 0 0 1.814-.183 9.014 9.014 0 0 0 3.218-1.355 8.886 8.886 0 0 0 1.331-1.099 9.228 9.228 0 0 0 1.1-1.332A8.952 8.952 0 0 0 21 13a9.09 9.09 0 0 0-.183-1.814z"></path></svg>
               </button>
            </div>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Cor do título dos campos', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Informe a cor do título dos campos da finalização de compras.' ) ?></span>
         </th>
         <td>
            <div class="color-container input-group">
               <input type="color" name="set_placeholder_color" class="form-control-color" value="<?php echo Admin_Options::get_setting('set_placeholder_color') ?>"/>
               <input type="text" class="get-color-selected form-control input-control-wd-10" value="<?php echo Admin_Options::get_setting('set_placeholder_color') ?>"/>
               <button class="btn btn-outline-secondary btn-icon reset-color fcw-tooltip" data-color="#33404d" data-text="<?php esc_html_e( 'Redefinir para cor padrão', 'flexify-checkout-for-woocommerce' ) ?>">
                  <svg class="icon-button" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 16c1.671 0 3-1.331 3-3s-1.329-3-3-3-3 1.331-3 3 1.329 3 3 3z"></path><path d="M20.817 11.186a8.94 8.94 0 0 0-1.355-3.219 9.053 9.053 0 0 0-2.43-2.43 8.95 8.95 0 0 0-3.219-1.355 9.028 9.028 0 0 0-1.838-.18V2L8 5l3.975 3V6.002c.484-.002.968.044 1.435.14a6.961 6.961 0 0 1 2.502 1.053 7.005 7.005 0 0 1 1.892 1.892A6.967 6.967 0 0 1 19 13a7.032 7.032 0 0 1-.55 2.725 7.11 7.11 0 0 1-.644 1.188 7.2 7.2 0 0 1-.858 1.039 7.028 7.028 0 0 1-3.536 1.907 7.13 7.13 0 0 1-2.822 0 6.961 6.961 0 0 1-2.503-1.054 7.002 7.002 0 0 1-1.89-1.89A6.996 6.996 0 0 1 5 13H3a9.02 9.02 0 0 0 1.539 5.034 9.096 9.096 0 0 0 2.428 2.428A8.95 8.95 0 0 0 12 22a9.09 9.09 0 0 0 1.814-.183 9.014 9.014 0 0 0 3.218-1.355 8.886 8.886 0 0 0 1.331-1.099 9.228 9.228 0 0 0 1.1-1.332A8.952 8.952 0 0 0 21 13a9.09 9.09 0 0 0-.183-1.814z"></path></svg>
               </button>
            </div>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Raio da borda dos elementos', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Define o raio da borda dos campos, botões e elementos da finalização de compra.' ) ?></span>
         </th>
         <td>
            <div class="input-group">
               <input type="text" name="input_border_radius" class="form-control input-control-wd-5 design-parameters" value="<?php echo Admin_Options::get_setting('input_border_radius') ?>"/>
               <select id="unit_input_border_radius" class="form-select" name="unit_input_border_radius">
                  <option value="px" <?php echo ( Admin_Options::get_setting('unit_input_border_radius') == 'px' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'px', 'flexify-checkout-for-woocommerce' ) ?></option>
                  <option value="em" <?php echo ( Admin_Options::get_setting('unit_input_border_radius') == 'em' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'em', 'flexify-checkout-for-woocommerce' ) ?></option>
                  <option value="rem" <?php echo ( Admin_Options::get_setting('unit_input_border_radius') == 'rem' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'rem', 'flexify-checkout-for-woocommerce' ) ?></option>
               </select>
            </div>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Fontes do Google', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Define a família de fontes do Google que serão utilizadas na página de finalização de compra.' ) ?></span>
         </th>
         <td>
            <div class="input-group">
               <select id="set_font_family" class="form-select" name="set_font_family">
                  <?php $options = get_option('flexify_checkout_settings', array());

                  foreach ( $options['font_family'] as $font => $value ) : ?>
                     <option value="<?php echo esc_attr( $font ) ?>" <?php echo ( Admin_Options::get_setting('set_font_family') === $font ) ? "selected=selected" : ""; ?>><?php echo esc_html( $value['font_name'] ) ?></option>
                  <?php endforeach; ?>
               </select>

               <button id="set_new_font_family_trigger" class="btn btn-outline-secondary input-group-button ms-2"><?php esc_html_e( 'Adicionar nova fonte', 'flexify-checkout-for-woocommerce' ) ?></button>
            </div>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Tamanho do h2', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Define o tamanho da fonte para tags h2 de subtítulos (Heading 2).' ) ?></span>
         </th>
         <td>
            <div class="input-group">
               <input type="text" name="h2_size" class="form-control input-control-wd-5 design-parameters" value="<?php echo Admin_Options::get_setting('h2_size') ?>"/>
               <select id="h2_size_unit" class="form-select" name="h2_size_unit">
                  <option value="px" <?php echo ( Admin_Options::get_setting( 'h2_size_unit' ) == 'px' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'px', 'flexify-checkout-for-woocommerce' ) ?></option>
                  <option value="em" <?php echo ( Admin_Options::get_setting( 'h2_size_unit' ) == 'em' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'em', 'flexify-checkout-for-woocommerce' ) ?></option>
                  <option value="rem" <?php echo ( Admin_Options::get_setting( 'h2_size_unit' ) == 'rem' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'rem', 'flexify-checkout-for-woocommerce' ) ?></option>
               </select>
            </div>
         </td>
      </tr>

      <?php
      /**
       * Hook for display custom design options
       * 
       * @since 3.6.0
       */
      do_action('flexify_checkout_after_design_options'); ?>

   </table>
</div>

<!-- Add new font modal -->
<div id="set_new_font_family_container" class="popup-container">
   <div class="popup-content">
      <div class="popup-header">
         <h5 class="popup-title"><?php esc_html_e('Adicione uma nova fonte à biblioteca', 'flexify-checkout-for-woocommerce') ?></h5>
         <button id="close_new_font_family" class="btn-close" aria-label="<?php esc_html( 'Fechar', 'flexify-checkout-for-woocommerce' ); ?>"></button>
      </div>
      <div class="popup-body">
         <table class="form-table">
            <tr>
               <th class="w-50">
                  <?php esc_html_e( 'Nome da fonte', 'flexify-checkout-for-woocommerce' ) ?>
                  <span class="flexify-checkout-description"><?php esc_html_e( 'Informe o nome da fonte do Google que deseja adicionar à biblioteca de opções.', 'flexify-checkout-for-woocommerce' ) ?></span>
               </th>
               <td class="w-50">
                  <input type="text" class="form-control " id="set_new_font_family_name" name="set_new_font_family_name" value=""/>
               </td>
            </tr>
            <tr>
               <th class="w-50">
                  <?php esc_html_e( 'URL da fonte', 'flexify-checkout-for-woocommerce' ) ?>
                  <span class="flexify-checkout-description"><?php esc_html_e( 'Informe o link da fonte para ser usado no formato @import na folha de estilos da finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
               </th>
               <td class="w-50">
                  <input type="text" class="form-control" id="set_new_font_family_url" name="set_new_font_family_url" value=""/>
               </td>
            </tr>
            <tr>
               <td class="w-100 d-flex justify-content-end">
                  <button id="add_new_font_to_lib" class="btn btn-primary d-flex align-items-center justify-content-center mt-2" disabled>
                     <svg class="me-2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: #fff;"><path d="M19 15v-3h-2v3h-3v2h3v3h2v-3h3v-2h-.937zM4 7h11v2H4zm0 4h11v2H4zm0 4h8v2H4z"></path></svg>
                     <?php esc_html_e('Adicionar fonte', 'flexify-checkout-for-woocommerce') ?>
                  </button>
               </td>
            </tr>
         </table>
      </div>
   </div>
</div>