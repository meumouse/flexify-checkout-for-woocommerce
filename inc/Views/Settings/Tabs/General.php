<?php

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\API\License;

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div id="general" class="nav-content">
   <table class="form-table">
      <?php
      /**
      * Hook for display custom generals option
      * 
      * @since 3.6.0
      */
      do_action('flexify_checkout_before_general_options'); ?>

      <tr>
         <th>
            <?php esc_html_e( 'Ativar Flexify Checkout', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para que o Flexify Checkout possa ser instanciado.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch">
               <input type="checkbox" class="toggle-switch" id="enable_flexify_checkout" name="enable_flexify_checkout" value="yes" <?php checked( Admin_Options::get_setting( 'enable_flexify_checkout') === 'yes' ); ?> />
            </div>
         </td>
      </tr>
      <tr>
         <th>
            <?php esc_html_e( 'Mostrar botão Voltar à loja', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para exibir o botão "Voltar à loja" na primeira etapa de finalização de compra.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch">
               <input type="checkbox" class="toggle-switch" id="enable_back_to_shop_button" name="enable_back_to_shop_button" value="yes" <?php checked( Admin_Options::get_setting('enable_back_to_shop_button') === 'yes' ); ?> />
            </div>
         </td>
      </tr>
      <tr>
         <th>
            <?php esc_html_e( 'Tornar imagem de produtos clicáveis', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para permitir que o usuário acesse o produto ao clicar na imagem do produto.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch">
               <input type="checkbox" class="toggle-switch" id="enable_link_image_products" name="enable_link_image_products" value="yes" <?php checked( Admin_Options::get_setting('enable_link_image_products') === 'yes' ); ?> />
            </div>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Pular página do carrinho', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para redirecionar o usuário da página de carrinho para a finalização de compra automaticamente.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch">
               <input type="checkbox" class="toggle-switch" id="enable_skip_cart_page" name="enable_skip_cart_page" value="yes" <?php checked( Admin_Options::get_setting('enable_skip_cart_page') === 'yes' ); ?> />
            </div>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Abrir popup de login automaticamente', 'flexify-checkout-for-woocommerce' );
            
            if ( ! License::is_valid() ) : ?>
               <span class="badge pro bg-primary rounded-pill ms-2">
                  <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                  <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
            <?php endif; ?>

            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para que o popup de login seja aberto automaticamente ao reconhecer uma conta existente com o e-mail informado.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>

         <td>
            <div class="form-check form-switch <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
               <input type="checkbox" class="toggle-switch <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" id="auto_display_login_modal" name="auto_display_login_modal" value="yes" <?php checked( Admin_Options::get_setting('auto_display_login_modal') === 'yes' && License::is_valid() ); ?> />
            </div>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Ativar verificação de força da senha do usuário', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para forçar a verificação da força de senha na criação da conta do usuário, na finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch">
               <input type="checkbox" class="toggle-switch" id="check_password_strenght" name="check_password_strenght" value="yes" <?php checked( Admin_Options::get_setting('check_password_strenght') === 'yes' ); ?> />
            </div>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Ativar sugestão de preenchimento do e-mail', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para exibir a sugestão do provedor de e-mail, na finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch">
               <input type="checkbox" class="toggle-switch" id="email_providers_suggestion" name="email_providers_suggestion" value="yes" <?php checked( Admin_Options::get_setting('email_providers_suggestion') === 'yes' ); ?> />
            </div>
         </td>
         <td class="require-email-suggestions-enabled">
            <button id="set_email_providers_trigger" class="btn btn-outline-primary ms-2"><?php esc_html_e( 'Configurar provedores', 'flexify-checkout-for-woocommerce' ) ?></button>

            <div id="set_email_providers_container" class="popup-container">
               <div class="popup-content">
                  <div class="popup-header">
                     <h5 class="popup-title"><?php esc_html_e('Configurar sugestão de e-mails', 'flexify-checkout-for-woocommerce') ?></h5>
                     <button id="close_set_email_providers" class="btn-close fs-lg" aria-label="<?php esc_html( 'Fechar', 'flexify-checkout-for-woocommerce' ); ?>"></button>
                  </div>
                  <div class="popup-body">
                     <table class="form-table">
                        <tr class="mb-4">
                           <th class="w-50">
                              <?php esc_html_e( 'Nome e extensão do novo provedor', 'flexify-checkout-for-woocommerce' ) ?>
                              <span class="flexify-checkout-description"><?php esc_html_e( 'Informe o nome do novo provedor incluindo a extensão, por exemplo, meumouse.com', 'flexify-checkout-for-woocommerce' ) ?></span>
                           </th>
                           <td class="w-50">
                              <div class="input-group">
                                 <input type="text" class="form-control" id="get_new_email_provider" value="" placeholder="<?php esc_html_e( 'meumouse.com', 'flexify-checkout-for-woocommerce' ) ?>"/>
                                 <button id="add_new_email_provider" class="btn btn-outline-secondary" disabled><?php esc_html_e( 'Adicionar', 'flexify-checkout-for-woocommerce' ) ?></button>
                              </div>
                           </td>
                        </tr>
                        <tr>
                           <td class="w-50">
                              <ul id="flexify_checkout_email_providers" class="list-group">
                                 <?php foreach ( Admin_Options::get_setting('set_email_providers') as $provider ) : ?>
                                    <li class="list-group-item d-flex align-items-center justify-content-between" data-provider="<?php echo esc_attr( $provider ) ?>">
                                       <span><?php echo esc_html( $provider ) ?></span>
                                       <button class="exclude-provider btn btn-icon btn-sm btn-outline-danger rounded-3 ms-3">
                                          <svg class="icon icon-sm icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
                                       </button>
                                    </li>
                                 <?php endforeach; ?>
                              </ul>
                           </td>
                        </tr>
                     </table>
                  </div>
               </div>
            </div>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Mostrar resumo do pedido aberto por padrão', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para mostrar o resumo do pedido aberto por padrão em celulares.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch">
               <input type="checkbox" class="toggle-switch" id="display_opened_order_review_mobile" name="display_opened_order_review_mobile" value="yes" <?php checked( Admin_Options::get_setting('display_opened_order_review_mobile') === 'yes' ); ?> />
            </div>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Remover controles de quantidade em produtos vendidos individualmente', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para remover os controles de quantidade na finalização de compras em produtos que são vendidos individualmente.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch">
               <input type="checkbox" class="toggle-switch" id="enable_remove_quantity_select" name="enable_remove_quantity_select" value="yes" <?php checked( Admin_Options::get_setting('enable_remove_quantity_select') === 'yes' ); ?> />
            </div>
         </td>
      </tr>

      <?php if ( class_exists('Kangu_Shipping_Method') ) : ?>
         <tr>
            <th>
               <?php esc_html_e( 'Mostrar endereço da loja física para retirada da encomenda Kangu', 'flexify-checkout-for-woocommerce' ) ?>
               <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para mostrar o endereço da sua loja como ponto de retirada da encomenda Kangu.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <div class="form-check form-switch">
                  <input type="checkbox" class="toggle-switch" id="enable_display_local_pickup_kangu" name="enable_display_local_pickup_kangu" value="yes" <?php checked( Admin_Options::get_setting('enable_display_local_pickup_kangu') === 'yes' ); ?> />
               </div>
            </td>
         </tr>
      <?php endif; ?>

      <tr class="container-separator"></tr>

      <?php
      // Brazilian Market on WooCommerce settings
      $wcbcf_active = class_exists('Extra_Checkout_Fields_For_Brazil');
      $wcbcf_settings = get_option('wcbcf_settings');
      $person_type = isset( $wcbcf_settings['person_type'] ) ? intval( $wcbcf_settings['person_type'] ) : null;

      // check if option contains CNPJ
      if ( $wcbcf_active && $person_type == 1 || $wcbcf_active && $person_type == 3 || WC()->countries->get_base_country() === 'BR' ) : ?>
         <tr>
            <th>
               <?php esc_html_e( 'Preencher informações da empresa automaticamente', 'flexify-checkout-for-woocommerce' ); 

               if ( ! License::is_valid() ) : ?>
                  <span class="badge pro bg-primary rounded-pill ms-2">
                     <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                     <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                  </span>
               <?php endif; ?>
               <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para preencher as informações da empresa automaticamente ao digitar o CNPJ (Disponível apenas no Brasil).', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <div class="form-check form-switch <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
                  <input type="checkbox" class="toggle-switch <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" id="enable_autofill_company_info" name="enable_autofill_company_info" value="yes" <?php checked( Admin_Options::get_setting('enable_autofill_company_info') === 'yes' && License::is_valid() ); ?> />
               </div>
            </td>
         </tr>
      <?php endif;

      if ( WC()->countries->get_base_country() === 'BR' ) : ?>
         <tr>
            <th>
               <?php esc_html_e( 'Preencher endereço automaticamente', 'flexify-checkout-for-woocommerce' );
               
               if ( ! License::is_valid() ) : ?>
                  <span class="badge pro bg-primary rounded-pill ms-2">
                     <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                     <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                  </span>
               <?php endif; ?>

               <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para preencher os campos de entrega ao digitar o CEP (Recomendado), (Disponível apenas no Brasil).', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <div class="form-check form-switch <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
                  <input type="checkbox" class="toggle-switch <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" id="enable_fill_address" name="enable_fill_address" value="yes" <?php checked( Admin_Options::get_setting('enable_fill_address') === 'yes' && License::is_valid() ); ?> />
               </div>
            </td>
            <td class="require-auto-fill-address">
               <button id="auto_fill_address_api_trigger" class="btn btn-outline-primary ms-2"><?php esc_html_e( 'Configurar API', 'flexify-checkout-for-woocommerce' ) ?></button>

               <div class="auto-fill-address-api-container popup-container">
                  <div class="popup-content">
                     <div class="popup-header">
                        <h5 class="popup-title"><?php esc_html_e('Configurar API de preenchimento de endereço', 'flexify-checkout-for-woocommerce') ?></h5>
                        <button class="auto-fill-address-api-close btn-close fs-lg" aria-label="<?php esc_html( 'Fechar', 'flexify-checkout-for-woocommerce' ); ?>"></button>
                     </div>
                     <div class="popup-body">
                        <table class="form-table">
                           <tr>
                              <th class="w-50">
                                 <?php esc_html_e( 'Serviço de API para busca de endereço', 'flexify-checkout-for-woocommerce' ) ?>
                                 <span class="flexify-checkout-description"><?php esc_html_e( 'Informe o endereço da API para obter o endereço do usuário através do seu CEP em formato JSON. Use a variável {postcode} para informar o CEP.', 'flexify-checkout-for-woocommerce' ) ?></span>
                              </th>
                              <td class="w-50">
                                 <input type="text" class="form-control" id="get_address_api_service" name="get_address_api_service" value="<?php echo Admin_Options::get_setting( 'get_address_api_service') ?>"/>
                              </td>
                           </tr>
                           <tr>
                              <th class="w-50">
                                 <?php esc_html_e( 'Propriedade de obtenção de endereço', 'flexify-checkout-for-woocommerce' ) ?>
                                 <span class="flexify-checkout-description"><?php esc_html_e( 'Informe a propriedade para obter o endereço que é retornado pelo serviço da API.', 'flexify-checkout-for-woocommerce' ) ?></span>
                              </th>
                              <td class="w-50">
                                 <input type="text" class="form-control" id="api_auto_fill_address_param" name="api_auto_fill_address_param" value="<?php echo Admin_Options::get_setting( 'api_auto_fill_address_param') ?>"/>
                              </td>
                           </tr>
                           <tr>
                              <th class="w-50">
                                 <?php esc_html_e( 'Propriedade de obtenção do bairro', 'flexify-checkout-for-woocommerce' ) ?>
                                 <span class="flexify-checkout-description"><?php esc_html_e( 'Informe a propriedade para obter o bairro que é retornado pelo serviço da API.', 'flexify-checkout-for-woocommerce' ) ?></span>
                              </th>
                              <td class="w-50">
                                 <input type="text" class="form-control" id="api_auto_fill_address_neightborhood_param" name="api_auto_fill_address_neightborhood_param" value="<?php echo Admin_Options::get_setting( 'api_auto_fill_address_neightborhood_param') ?>"/>
                              </td>
                           </tr>
                           <tr>
                              <th class="w-50">
                                 <?php esc_html_e( 'Propriedade de obtenção de cidade', 'flexify-checkout-for-woocommerce' ) ?>
                                 <span class="flexify-checkout-description"><?php esc_html_e( 'Informe a propriedade para obter a cidade que é retornado pelo serviço da API.', 'flexify-checkout-for-woocommerce' ) ?></span>
                              </th>
                              <td class="w-50">
                                 <input type="text" class="form-control" id="api_auto_fill_address_city_param" name="api_auto_fill_address_city_param" value="<?php echo Admin_Options::get_setting( 'api_auto_fill_address_city_param') ?>"/>
                              </td>
                           </tr>
                           <tr>
                              <th class="w-50">
                                 <?php esc_html_e( 'Propriedade de obtenção de estado', 'flexify-checkout-for-woocommerce' ) ?>
                                 <span class="flexify-checkout-description"><?php esc_html_e( 'Informe a propriedade para obter o estado que é retornado pelo serviço da API.', 'flexify-checkout-for-woocommerce' ) ?></span>
                              </th>
                              <td class="w-50">
                                 <input type="text" class="form-control" id="api_auto_fill_address_state_param" name="api_auto_fill_address_state_param" value="<?php echo Admin_Options::get_setting( 'api_auto_fill_address_state_param') ?>"/>
                              </td>
                           </tr>
                        </table>
                     </div>
                  </div>
               </div>
            </td>
         </tr>
      <?php endif; ?>

      <tr>
         <th>
            <?php esc_html_e( 'Permitir envio para um endereço diferente', 'flexify-checkout-for-woocommerce' ); 

            if ( ! License::is_valid() ) : ?>
               <span class="badge pro bg-primary rounded-pill ms-2">
                  <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                  <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
            <?php endif; ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para permitir que o usuário possa enviar seu pedido para um endereço diferente do endereço de faturamento.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
               <input type="checkbox" class="toggle-switch <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" id="enable_shipping_to_different_address" name="enable_shipping_to_different_address" value="yes" <?php checked( Admin_Options::get_setting('enable_shipping_to_different_address') === 'yes' && License::is_valid() ); ?> />
            </div>
         </td>
      </tr>

      <tr class="container-separator"></tr>
      
      <tr>
         <th>
            <?php esc_html_e( 'Termos e condições ativo por padrão', 'flexify-checkout-for-woocommerce' );
            
            if ( ! License::is_valid() ) : ?>
               <span class="badge pro bg-primary rounded-pill ms-2">
                  <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                  <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
            <?php endif; ?>

            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para a opção de termos e condições da última etapa ficar ativa por padrão, caso exista uma página de termos e condições configurada.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
               <input type="checkbox" class="toggle-switch <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" id="enable_terms_is_checked_default" name="enable_terms_is_checked_default" value="yes" <?php checked( Admin_Options::get_setting('enable_terms_is_checked_default') === 'yes' && License::is_valid() ); ?> />
            </div>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Permitir alterar quantidade de produtos', 'flexify-checkout-for-woocommerce' );
            
            if ( ! License::is_valid() ) : ?>
               <span class="badge pro bg-primary rounded-pill ms-2">
                  <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                  <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
            <?php endif; ?>

            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para exibir os seletores de quantidades do produto na finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
               <input type="checkbox" class="toggle-switch <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" id="enable_change_product_quantity" name="enable_change_product_quantity" value="yes" <?php checked( Admin_Options::get_setting('enable_change_product_quantity') === 'yes' && License::is_valid() ); ?> />
            </div>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Permitir remover produtos do carrinho', 'flexify-checkout-for-woocommerce' );
            
            if ( ! License::is_valid() ) : ?>
               <span class="badge pro bg-primary rounded-pill ms-2">
                  <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                  <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
            <?php endif; ?>

            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para exibir o botão de remoção do produto do carrinho na finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
               <input type="checkbox" class="toggle-switch <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" id="enable_remove_product_cart" name="enable_remove_product_cart" value="yes" <?php checked( Admin_Options::get_setting('enable_remove_product_cart') === 'yes' && License::is_valid() ); ?> />
            </div>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Ativar telefone internacional', 'flexify-checkout-for-woocommerce' );
            
            if ( ! License::is_valid() ) : ?>
                  <span class="badge pro bg-primary rounded-pill ms-2">
                     <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                     <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                  </span>
            <?php endif; ?>

            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para exibir o seletor de país no campo de número de telefone. Útil se você vende para outros países.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
               <input type="checkbox" class="toggle-switch <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" id="enable_ddi_phone_field" name="enable_ddi_phone_field" value="yes" <?php checked( Admin_Options::get_setting('enable_ddi_phone_field') === 'yes' && License::is_valid() ); ?> />
            </div>
         </td>
      </tr>

      <tr>
         <th>
            <?php esc_html_e( 'Aplicar cupom de desconto automaticamente', 'flexify-checkout-for-woocommerce' );
            
            if ( ! License::is_valid() ) : ?>
               <span class="badge pro bg-primary rounded-pill ms-2">
                  <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                  <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
            <?php endif; ?>

            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para informar um cupom de desconto para ser aplicado automaticamente na finalização de compra.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
               <input type="checkbox" class="toggle-switch <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" id="enable_auto_apply_coupon_code" name="enable_auto_apply_coupon_code" value="yes" <?php checked( Admin_Options::get_setting('enable_auto_apply_coupon_code') === 'yes' && License::is_valid() ); ?> />
            </div>
         </td>
      </tr>

      <tr class="show-coupon-code-enabled">
         <th>
            <?php esc_html_e( 'Código do cupom de desconto', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Informe o código do cupom de desconto que será aplicado automaticamente na finalização de compra.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <input type="text" name="coupon_code_for_auto_apply" class="form-control" placeholder="<?php esc_html_e( 'CUPOMDEDESCONTO', 'flexify-checkout-for-woocommerce' ) ?>" value="<?php echo Admin_Options::get_setting( 'coupon_code_for_auto_apply' ) ?>"/>
         </td>
      </tr>
      
      <tr>
         <th>
            <?php esc_html_e( 'Atribuir pedidos de usuários convidados', 'flexify-checkout-for-woocommerce' );
            
            if ( ! License::is_valid() ) : ?>
               <span class="badge pro bg-primary rounded-pill ms-2">
                  <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                  <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
            <?php endif; ?>
            
            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para que pedidos de usuários convidados na finalização de compra sejam atribuídos a usuários existentes.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
               <input type="checkbox" class="toggle-switch <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" id="enable_assign_guest_orders" name="enable_assign_guest_orders" value="yes" <?php checked( Admin_Options::get_setting('enable_assign_guest_orders') === 'yes' && License::is_valid() ); ?> />
            </div>
         </td>
      </tr>

      <tr class="container-separator"></tr>

      <tr>
         <th>
            <?php esc_html_e( 'Ativar animações de processamento de compra', 'flexify-checkout-for-woocommerce' );
            
            if ( ! License::is_valid() ) : ?>
               <span class="badge pro bg-primary rounded-pill ms-2">
                  <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                  <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
            <?php endif; ?>

            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para personalizar a animação de processamento da compra.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>

         <td>
            <div class="form-check form-switch <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
               <input type="checkbox" class="toggle-switch <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" id="enable_animation_process_purchase" name="enable_animation_process_purchase" value="yes" <?php checked( Admin_Options::get_setting('enable_animation_process_purchase') === 'yes' && License::is_valid() ); ?> />
            </div>
         </td>

         <td class="require-process-animations-enabled">
            <button id="set_process_purchase_animation_trigger" class="btn btn-outline-primary ms-2"><?php esc_html_e( 'Configurar animação', 'flexify-checkout-for-woocommerce' ) ?></button>

            <div id="set_process_purchase_animation_container" class="popup-container">
               <div class="popup-content">
                  <div class="popup-header">
                     <h5 class="popup-title"><?php esc_html_e('Configurar animação de processamento', 'flexify-checkout-for-woocommerce') ?></h5>
                     <button id="close_set_process_purchase_animation" class="btn-close fs-lg" aria-label="<?php esc_html( 'Fechar', 'flexify-checkout-for-woocommerce' ); ?>"></button>
                  </div>
                  <div class="popup-body">
                     <table class="popup-table">
                        <tbody>
                           <tr>
                              <th>
                                 <?php esc_html_e( 'Texto da animação 1', 'flexify-checkout-for-woocommerce' ) ?>
                                 <span class="flexify-checkout-description"><?php esc_html_e( 'Informe o texto que será exibido na animação 1 de processamento.', 'flexify-checkout-for-woocommerce' ) ?></span>
                              </th>
                              <td>
                                 <input type="text" name="text_animation_process_purchase_1" class="form-control input-control-wd-20" value="<?php echo esc_attr( Admin_Options::get_setting('text_animation_process_purchase_1') ) ?>"/>
                              </td>
                           </tr>

                           <tr>
                              <th>
                                 <?php esc_html_e( 'Arquivo da animação 1', 'flexify-checkout-for-woocommerce' ) ?>
                                 <span class="flexify-checkout-description"><?php esc_html_e( 'Anexe o link ou arquivo da animação Lottie em formato .json', 'flexify-checkout-for-woocommerce' ) ?></span>
                              </th>
                              <td>
                                 <div class="input-group">
                                    <input type="text" name="animation_process_purchase_file_1" class="form-control" value="<?php echo Admin_Options::get_setting('animation_process_purchase_file_1') ?>"/>
                                    <button id="animation_process_purchase_file_1_trigger" class="input-group-button btn btn-outline-secondary"><?php esc_html_e( 'Procurar', 'flexify-checkout-for-woocommerce' ) ?></button>
                                 </div>
                              </td>
                           </tr>

                           <tr class="container-separator"></tr>

                           <tr>
                              <th>
                                 <?php esc_html_e( 'Texto da animação 2', 'flexify-checkout-for-woocommerce' ) ?>
                                 <span class="flexify-checkout-description"><?php esc_html_e( 'Informe o texto que será exibido na animação 1 de processamento.', 'flexify-checkout-for-woocommerce' ) ?></span>
                              </th>
                              <td>
                                 <input type="text" name="text_animation_process_purchase_2" class="form-control input-control-wd-20" value="<?php echo esc_attr( Admin_Options::get_setting('text_animation_process_purchase_2') ) ?>"/>
                              </td>
                           </tr>

                           <tr>
                              <th>
                                 <?php esc_html_e( 'Arquivo da animação 2', 'flexify-checkout-for-woocommerce' ) ?>
                                 <span class="flexify-checkout-description"><?php esc_html_e( 'Anexe o link ou arquivo da animação Lottie em formato .json', 'flexify-checkout-for-woocommerce' ) ?></span>
                              </th>
                              <td>
                                 <div class="input-group">
                                    <input type="text" name="animation_process_purchase_file_2" class="form-control" value="<?php echo Admin_Options::get_setting('animation_process_purchase_file_2') ?>"/>
                                    <button id="animation_process_purchase_file_2_trigger" class="input-group-button btn btn-outline-secondary"><?php esc_html_e( 'Procurar', 'flexify-checkout-for-woocommerce' ) ?></button>
                                 </div>
                              </td>
                           </tr>

                           <tr class="container-separator"></tr>

                           <tr>
                              <th>
                                 <?php esc_html_e( 'Texto da animação 3', 'flexify-checkout-for-woocommerce' ) ?>
                                 <span class="flexify-checkout-description"><?php esc_html_e( 'Informe o texto que será exibido na animação 1 de processamento.', 'flexify-checkout-for-woocommerce' ) ?></span>
                              </th>
                              <td>
                                 <input type="text" name="text_animation_process_purchase_3" class="form-control input-control-wd-20" value="<?php echo esc_attr( Admin_Options::get_setting('text_animation_process_purchase_3') ) ?>"/>
                              </td>
                           </tr>

                           <tr>
                              <th>
                                 <?php esc_html_e( 'Arquivo da animação 3', 'flexify-checkout-for-woocommerce' ) ?>
                                 <span class="flexify-checkout-description"><?php esc_html_e( 'Anexe o link ou arquivo da animação Lottie em formato .json', 'flexify-checkout-for-woocommerce' ) ?></span>
                              </th>
                              <td>
                                 <div class="input-group">
                                    <input type="text" name="animation_process_purchase_file_3" class="form-control" value="<?php echo Admin_Options::get_setting('animation_process_purchase_file_3') ?>"/>
                                    <button id="animation_process_purchase_file_3_trigger" class="input-group-button btn btn-outline-secondary"><?php esc_html_e( 'Procurar', 'flexify-checkout-for-woocommerce' ) ?></button>
                                 </div>
                              </td>
                           </tr>
                        </tbody>
                     </table>
                  </div>
               </div>
            </div>
         </td>
      </tr>

      <tr class="container-separator"></tr>

      <tr>
         <th>
            <?php esc_html_e( 'Ativar página de agradecimento do Flexify Checkout', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para carregar o modelo de página de agradecimento do Flexify Checkout.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch">
               <input type="checkbox" class="toggle-switch" id="enable_thankyou_page_template" name="enable_thankyou_page_template" value="yes" <?php checked( Admin_Options::get_setting( 'enable_thankyou_page_template') === 'yes' ); ?> />
            </div>
         </td>
      </tr>
      <tr>
			<th>
				<?php esc_html_e( 'Página de contato', 'flexify-checkout-for-woocommerce' ) ?>
				<span class="flexify-checkout-description"><?php esc_html_e( 'Selecione a página de contato que será exibida aos clientes na finalização de compra.', 'flexify-checkout-for-woocommerce' ) ?></span>
			</th>
			<td>
				<select name="contact_page_thankyou" class="form-select">
					<?php foreach ( get_pages() as $page ) :
						$selected = ( Admin_Options::get_setting( 'contact_page_thankyou' ) === esc_attr( $page->ID ) ) ? 'selected="selected"' : '';
						echo '<option value="'. esc_attr( $page->ID ) .'" ' . $selected . '>' . esc_html( $page->post_title ) . '</option>';
					endforeach; ?>
				</select>
			</td>
      </tr>

      <?php
      /**
      * Hook for display custom general options
      * 
      * @since 3.6.0
      */
      do_action('flexify_checkout_after_general_options'); ?>

   </table>
</div>