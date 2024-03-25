<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="general" class="nav-content">
   <table class="form-table">
      <tr>
         <th>
            <?php echo esc_html__( 'Ativar Flexify Checkout', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para carregar o modelo de finalização de compra Flexify Checkout.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch">
               <input type="checkbox" class="toggle-switch" id="enable_flexify_checkout" name="enable_flexify_checkout" value="yes" <?php checked( self::get_setting( 'enable_flexify_checkout') === 'yes' ); ?> />
            </div>
         </td>
      </tr>
      <tr>
         <th>
            <?php echo esc_html__( 'Mostrar botão Voltar à loja', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para exibir o botão "Voltar à loja" na primeira etapa de finalização de compra.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch">
               <input type="checkbox" class="toggle-switch" id="enable_back_to_shop_button" name="enable_back_to_shop_button" value="yes" <?php checked( self::get_setting('enable_back_to_shop_button') === 'yes' ); ?> />
            </div>
         </td>
      </tr>
      <tr>
         <th>
            <?php echo esc_html__( 'Tornar imagem de produtos clicáveis', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para permitir que o usuário acesse o produto ao clicar na imagem do produto.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch">
               <input type="checkbox" class="toggle-switch" id="enable_link_image_products" name="enable_link_image_products" value="yes" <?php checked( self::get_setting('enable_link_image_products') === 'yes' ); ?> />
            </div>
         </td>
      </tr>
      <tr>
         <th>
            <?php echo esc_html__( 'Pular página do carrinho', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para redirecionar o usuário da página de carrinho para a finalização de compra automaticamente.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch">
               <input type="checkbox" class="toggle-switch" id="enable_skip_cart_page" name="enable_skip_cart_page" value="yes" <?php checked( self::get_setting('enable_skip_cart_page') === 'yes' ); ?> />
            </div>
         </td>
      </tr>

      <tr class="container-separator"></tr>

      <?php
      // Brazilian Market on WooCommerce settings
      $wcbcf_active = class_exists('Extra_Checkout_Fields_For_Brazil');
      $wcbcf_settings = get_option('wcbcf_settings');
      $person_type = intval( $wcbcf_settings['person_type'] );

      // check if option contains CNPJ
      if ( $wcbcf_active && $person_type == 1 || $wcbcf_active && $person_type == 3 || WC()->countries->get_base_country() === 'BR' ) {
         ?>
         <tr>
            <th>
               <?php echo esc_html__( 'Preencher informações da empresa automaticamente', 'flexify-checkout-for-woocommerce' ); 

               if ( ! self::license_valid() ) {
                  ?>
                  <span class="badge pro bg-primary rounded-pill ms-2">
                     <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                     <?php echo esc_html__( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                  </span>
                  <?php
               }
               ?>
               <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para preencher as informações da empresa automaticamente ao digitar o CNPJ (Disponível apenas no Brasil).', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <div class="form-check form-switch <?php echo ( ! self::license_valid() ) ? 'require-pro' : ''; ?>">
                  <input type="checkbox" class="toggle-switch <?php echo ( ! self::license_valid() ) ? 'pro-version' : ''; ?>" id="enable_autofill_company_info" name="enable_autofill_company_info" value="yes" <?php checked( self::get_setting('enable_autofill_company_info') === 'yes' && self::license_valid() ); ?> />
               </div>
            </td>
         </tr>
         <?php
      }

      if ( WC()->countries->get_base_country() === 'BR' ) {
         ?>
         <tr>
            <th>
               <?php echo esc_html__( 'Preencher endereço automaticamente', 'flexify-checkout-for-woocommerce' );
               
               if ( ! self::license_valid() ) {
                  ?>
                  <span class="badge pro bg-primary rounded-pill ms-2">
                     <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                     <?php echo esc_html__( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                  </span>
                  <?php
               }
               ?>
               <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para preencher os campos de entrega ao digitar o CEP (Recomendado), (Disponível apenas no Brasil).', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <div class="form-check form-switch <?php echo ( ! self::license_valid() ) ? 'require-pro' : ''; ?>">
                  <input type="checkbox" class="toggle-switch <?php echo ( ! self::license_valid() ) ? 'pro-version' : ''; ?>" id="enable_fill_address" name="enable_fill_address" value="yes" <?php checked( self::get_setting('enable_fill_address') === 'yes' && self::license_valid() ); ?> />
               </div>
            </td>
         </tr>
         <?php
      }
      ?>

      <tr>
         <th>
            <?php echo esc_html__( 'Selecionar país do usuário automaticamente através do seu IP', 'flexify-checkout-for-woocommerce' );
            
            if ( ! self::license_valid() ) {
               ?>
               <span class="badge pro bg-primary rounded-pill ms-2">
                  <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                  <?php echo esc_html__( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
               <?php
            }
            ?>
            <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para se conectar à ip-api e obter o país do usuário através do seu IP. Útil se você vende para outros países.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch <?php echo ( ! self::license_valid() ) ? 'require-pro' : ''; ?>">
               <input type="checkbox" class="toggle-switch <?php echo ( ! self::license_valid() ) ? 'pro-version' : ''; ?>" id="enable_set_country_from_ip" name="enable_set_country_from_ip" value="yes" <?php checked( self::get_setting('enable_set_country_from_ip') === 'yes' && self::license_valid() ); ?> />
            </div>
         </td>
         <td class="require-set-country-from-ip">
            <button id="set_ip_api_service_trigger" class="btn btn-outline-primary ms-2"><?php echo esc_html__( 'Configurar API', 'flexify-checkout-for-woocommerce' ) ?></button>

            <div class="set-api-service-container">
               <div class="popup-content">
                  <div class="popup-header">
                     <h5 class="popup-title"><?php echo esc_html__('Configurar API de busca de IP e país', 'flexify-checkout-for-woocommerce') ?></h5>
                     <button class="set-api-service-close btn-close fs-lg" aria-label="<?php esc_html( 'Fechar', 'flexify-checkout-for-woocommerce' ); ?>"></button>
                  </div>
                  <div class="popup-body">
                     <table class="form-table">
                        <tr>
                           <th class="w-50">
                              <?php echo esc_html__( 'Serviço de API para obter IP do usuário', 'flexify-checkout-for-woocommerce' ) ?>
                              <span class="flexify-checkout-description"><?php echo esc_html__( 'Informe o endereço da API para obter o IP do usuário em formato JSON.', 'flexify-checkout-for-woocommerce' ) ?></span>
                           </th>
                           <td class="w-50">
                              <input type="text" class="form-control" id="get_user_ip_service" name="get_user_ip_service" value="<?php echo self::get_setting( 'get_user_ip_service') ?>"/>
                           </td>
                        </tr>
                        <tr>
                           <th class="w-50">
                              <?php echo esc_html__( 'Propriedade de retorno de obtenção do IP', 'flexify-checkout-for-woocommerce' ) ?>
                              <span class="flexify-checkout-description"><?php echo esc_html__( 'Informe a propriedade para obter o IP que é retornado pelo serviço da API.', 'flexify-checkout-for-woocommerce' ) ?></span>
                           </th>
                           <td class="w-50">
                              <input type="text" class="form-control" id="api_ip_param" name="api_ip_param" value="<?php echo self::get_setting( 'api_ip_param') ?>"/>
                           </td>
                        </tr>
                        <tr>
                           <th class="w-50">
                              <?php echo esc_html__( 'Serviço de API para obter o país através do IP', 'flexify-checkout-for-woocommerce' ) ?>
                              <span class="flexify-checkout-description"><?php echo esc_html__( 'Informe o endereço da API para obter o país do usuário em formato JSON.', 'flexify-checkout-for-woocommerce' ) ?></span>
                           </th>
                           <td class="w-50">
                              <input type="text" class="form-control" id="get_country_from_ip_service" name="get_country_from_ip_service" value="<?php echo self::get_setting( 'get_country_from_ip_service') ?>"/>
                           </td>
                        </tr>
                        <tr>
                           <th class="w-50">
                              <?php echo esc_html__( 'Propriedade de retorno de obtenção do código do país', 'flexify-checkout-for-woocommerce' ) ?>
                              <span class="flexify-checkout-description"><?php echo esc_html__( 'Informe a propriedade para obter o código do país que é retornado pelo serviço da API.', 'flexify-checkout-for-woocommerce' ) ?></span>
                           </th>
                           <td class="w-50">
                              <input type="text" class="form-control" id="api_country_code_param" name="api_country_code_param" value="<?php echo self::get_setting( 'api_country_code_param') ?>"/>
                           </td>
                        </tr>
                     </table>
                  </div>
               </div>
            </div>
         </td>
      </tr>

      <tr class="container-separator"></tr>
      
      <tr>
         <th>
            <?php echo esc_html__( 'Termos e condições ativo por padrão', 'flexify-checkout-for-woocommerce' );
            
            if ( ! self::license_valid() ) {
               ?>
               <span class="badge pro bg-primary rounded-pill ms-2">
                  <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                  <?php echo esc_html__( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
               <?php
            }
            ?>
            <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para a opção de termos e condições da última etapa ficar ativa por padrão, caso exista uma página de termos e condições configurada.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch <?php echo ( ! self::license_valid() ) ? 'require-pro' : ''; ?>">
               <input type="checkbox" class="toggle-switch <?php echo ( ! self::license_valid() ) ? 'pro-version' : ''; ?>" id="enable_terms_is_checked_default" name="enable_terms_is_checked_default" value="yes" <?php checked( self::get_setting('enable_terms_is_checked_default') === 'yes' && self::license_valid() ); ?> />
            </div>
         </td>
      </tr>
      <tr>
         <th>
            <?php echo esc_html__( 'Permitir adicionar e remover produtos', 'flexify-checkout-for-woocommerce' );
            
            if ( ! self::license_valid() ) {
               ?>
               <span class="badge pro bg-primary rounded-pill ms-2">
                  <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                  <?php echo esc_html__( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
               <?php
            }
            ?>
            <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para exibir botões de alterar quantidade e exclusão do produto na finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch <?php echo ( ! self::license_valid() ) ? 'require-pro' : ''; ?>">
               <input type="checkbox" class="toggle-switch <?php echo ( ! self::license_valid() ) ? 'pro-version' : ''; ?>" id="enable_add_remove_products" name="enable_add_remove_products" value="yes" <?php checked( self::get_setting('enable_add_remove_products') === 'yes' && self::license_valid() ); ?> />
            </div>
         </td>
      </tr>
      <tr>
         <th>
            <?php echo esc_html__( 'Ativar seletor de país em número de telefone', 'flexify-checkout-for-woocommerce' );
            
            if ( ! self::license_valid() ) {
               ?>
               <span class="badge pro bg-primary rounded-pill ms-2">
                  <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                  <?php echo esc_html__( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
               <?php
            }
            ?>
            <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para exibir o seletor de país no campo de número de telefone. Útil se você vende para outros países.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch <?php echo ( ! self::license_valid() ) ? 'require-pro' : ''; ?>">
               <input type="checkbox" class="toggle-switch <?php echo ( ! self::license_valid() ) ? 'pro-version' : ''; ?>" id="enable_ddi_phone_field" name="enable_ddi_phone_field" value="yes" <?php checked( self::get_setting('enable_ddi_phone_field') === 'yes' && self::license_valid() ); ?> />
            </div>
         </td>
      </tr>
      <tr>
         <th>
            <?php echo esc_html__( 'Aplicar cupom de desconto automaticamente', 'flexify-checkout-for-woocommerce' );
            
            if ( ! self::license_valid() ) {
               ?>
               <span class="badge pro bg-primary rounded-pill ms-2">
                  <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                  <?php echo esc_html__( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
               <?php
            }
            ?>
            <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para informar um cupom de desconto para ser aplicado automaticamente na finalização de compra.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch <?php echo ( ! self::license_valid() ) ? 'require-pro' : ''; ?>">
               <input type="checkbox" class="toggle-switch <?php echo ( ! self::license_valid() ) ? 'pro-version' : ''; ?>" id="enable_auto_apply_coupon_code" name="enable_auto_apply_coupon_code" value="yes" <?php checked( self::get_setting('enable_auto_apply_coupon_code') === 'yes' && self::license_valid() ); ?> />
            </div>
         </td>
      </tr>
      <tr class="show-coupon-code-enabled">
         <th>
            <?php echo esc_html__( 'Código do cupom de desconto', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php echo esc_html__( 'Informe o código do cupom de desconto que será aplicado automaticamente na finalização de compra.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <input type="text" name="coupon_code_for_auto_apply" class="form-control" placeholder="<?php echo esc_html__( 'CUPOMDEDESCONTO', 'flexify-checkout-for-woocommerce' ) ?>" value="<?php echo self::get_setting( 'coupon_code_for_auto_apply' ) ?>"/>
         </td>
      </tr>
      <tr>
         <th>
            <?php echo esc_html__( 'Atribuir pedidos de usuários convidados', 'flexify-checkout-for-woocommerce' );
            
            if ( ! self::license_valid() ) {
               ?>
               <span class="badge pro bg-primary rounded-pill ms-2">
                  <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                  <?php echo esc_html__( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
               </span>
               <?php
            }
            ?>
            <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para que pedidos de usuários convidados na finalização de compra sejam atribuídos a usuários existentes.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch <?php echo ( ! self::license_valid() ) ? 'require-pro' : ''; ?>">
               <input type="checkbox" class="toggle-switch <?php echo ( ! self::license_valid() ) ? 'pro-version' : ''; ?>" id="enable_assign_guest_orders" name="enable_assign_guest_orders" value="yes" <?php checked( self::get_setting('enable_assign_guest_orders') === 'yes' && self::license_valid() ); ?> />
            </div>
         </td>
      </tr>

      <tr class="container-separator"></tr>

      <tr>
         <th>
            <?php echo esc_html__( 'Ativar página de agradecimento do Flexify Checkout', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para carregar o modelo de página de agradecimento do Flexify Checkout.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
            <div class="form-check form-switch">
               <input type="checkbox" class="toggle-switch" id="enable_thankyou_page_template" name="enable_thankyou_page_template" value="yes" <?php checked( self::get_setting( 'enable_thankyou_page_template') === 'yes' ); ?> />
            </div>
         </td>
      </tr>
      <tr>
         <th>
            <?php echo esc_html__( 'Página de contato', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php echo esc_html__( 'Selecione a página de contato que será exibida aos clientes na finalização de compra.', 'flexify-checkout-for-woocommerce' ) ?></span>
         </th>
         <td>
         <select name="contact_page_thankyou" class="form-select">
            <?php
            $pages = get_pages();

            foreach ($pages as $page) {
               $selected = ( self::get_setting( 'contact_page_thankyou' ) == esc_attr($page->ID) ) ? 'selected="selected"' : '';
               echo '<option value="'. esc_attr($page->ID) .'" ' . $selected . '>' . esc_html($page->post_title) . '</option>';
            }
            ?>
         </select>
         </td>
      </tr>
   </table>
</div>