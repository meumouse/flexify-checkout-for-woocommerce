<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$inter_module_active = is_plugin_active( 'module-inter-bank-for-flexify-checkout/module-inter-bank-for-flexify-checkout.php' ); ?>

<div id="integrations" class="nav-content">
  <table class="form-table">
      <?php
         if ( WC()->countries->get_base_country() === 'BR' ) {
            ?>
               <tr>
                  <th>
                     <?php echo esc_html__( 'Ativar recebimento de pagamentos com Pix via Banco Inter', 'flexify-checkout-for-woocommerce' );
                     
                     if ( ! self::license_valid() ) {
                        ?>
                        <span class="badge pro bg-primary rounded-pill ms-2">
                           <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                           <?php echo esc_html__( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                        </span>
                        <?php
                     }
                     ?>
                     <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para configurar recibimentos via Pix com aprovação automática gratuitamente (Disponível apenas no Brasil).', 'flexify-checkout-for-woocommerce' ) ?></span>
                  </th>
                  <td class="d-flex align-items-center">
                     <div class="form-check form-switch <?php echo ( ! self::license_valid() ) ? 'require-pro' : ''; ?>">
                        <input type="checkbox" class="toggle-switch <?php echo ( ! self::license_valid() ) ? 'pro-version' : ''; ?>" id="<?php echo ! class_exists('Module_Inter_Bank') ? 'require_inter_bank_module_trigger' : 'enable_inter_bank_pix_api'; ?>" name="enable_inter_bank_pix_api" value="yes" <?php checked( self::get_setting( 'enable_inter_bank_pix_api') === 'yes' && class_exists('Module_Inter_Bank') && self::license_valid() ); ?>/>
                     </div>
                     
                     <?php
                     if ( $inter_module_active ) {
                        ?>
                        <button id="inter_bank_pix_settings" class="btn btn-outline-primary ms-3 inter-bank-pix input-control-wd-12"><?php echo esc_html__( 'Configurar Pix', 'flexify-checkout-for-woocommerce' ) ?></button>
                           <div id="inter_bank_pix_container">
                              <div class="popup-content">
                                 <div class="popup-header">
                                    <h5 class="popup-title"><?php echo esc_html__( 'Configure a forma de pagamento Pix', 'flexify-checkout-for-woocommerce' ); ?></h5>
                                    <button id="inter_bank_pix_close" class=" btn-close fs-lg" aria-label="Fechar"></button>
                                 </div>
                                 <div class="popup-body">
                                    <table class="form-table">
                                       <tr>
                                          <th>
                                             <?php echo esc_html( 'Título da forma de pagamento Pix', 'flexify-checkout-for-woocommerce' ); ?>
                                             <span class="flexify-checkout-description"><?php echo esc_html__( 'Título que o usuário verá na finalização de compra (Disponível apenas no Brasil).', 'flexify-checkout-for-woocommerce' ) ?></span>
                                          </th>
                                          <td>
                                             <input type="text" class="form-control input-control-wd-20" name="pix_gateway_title" value="<?php echo self::get_setting( 'pix_gateway_title' ) ?>"/>
                                          </td>
                                       </tr>
                                       <tr>
                                          <th>
                                             <?php echo esc_html( 'Descrição da forma de pagamento Pix', 'flexify-checkout-for-woocommerce' ); ?>
                                             <span class="flexify-checkout-description"><?php echo esc_html__( 'Descrição da forma de pagamento que o usuário verá na finalização de compra.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                          </th>
                                          <td>
                                             <input type="text" class="form-control input-control-wd-20" name="pix_gateway_description" value="<?php echo self::get_setting( 'pix_gateway_description' ) ?>"/>
                                          </td>
                                       </tr>
                                       <tr>
                                          <th>
                                             <?php echo esc_html( 'Instruções por e-mail da forma de pagamento Pix', 'flexify-checkout-for-woocommerce' ); ?>
                                             <span class="flexify-checkout-description"><?php echo esc_html__( 'Texto exibido no e-mail junto do botão de copiar código Copia e Cola do Pix.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                          </th>
                                          <td>
                                             <input type="text" class="form-control input-control-wd-20" name="pix_gateway_email_instructions" value="<?php echo self::get_setting( 'pix_gateway_email_instructions' ) ?>"/>
                                          </td>
                                       </tr>
                                       <tr>
                                          <th>
                                             <?php echo esc_html( 'Chave Pix', 'flexify-checkout-for-woocommerce' ); ?>
                                             <span class="flexify-checkout-description"><?php echo esc_html__( 'Chave Pix associada ao banco Inter que receberá o pagamento. Para chaves do tipo celular ou CNPJ, utilize apenas números.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                          </th>
                                          <td>
                                             <input type="text" class="form-control input-control-wd-20" name="pix_gateway_receipt_key" value="<?php echo self::get_setting( 'pix_gateway_receipt_key' ) ?>"/>
                                          </td>
                                       </tr>
                                       <tr>
                                          <th>
                                             <?php echo esc_html( 'Validade do Pix', 'flexify-checkout-for-woocommerce' ); ?>
                                             <span class="flexify-checkout-description"><?php echo esc_html__( 'Prazo máximo para pagamento do Pix em minutos.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                          </th>
                                          <td>
                                             <input type="number" class="form-control input-control-wd-5" name="pix_gateway_expires" value="<?php echo self::get_setting( 'pix_gateway_expires' ) ?>"/>
                                          </td>
                                       </tr>
                                    </table>
                                 </div>
                              </div>
                           </div>
                        <?php
                     }
                     ?>
                  </td>
               </tr>

               <tr>
                  <th>
                     <?php echo esc_html__( 'Ativar recebimento de pagamentos com boleto bancário via Banco Inter', 'flexify-checkout-for-woocommerce' );
                     
                     if ( ! self::license_valid() ) {
                        ?>
                        <span class="badge pro bg-primary rounded-pill ms-2">
                           <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                           <?php echo esc_html__( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                        </span>
                        <?php
                     }
                     ?>
                     <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para configurar recibimentos via boleto bancário com aprovação automática gratuitamente.', 'flexify-checkout-for-woocommerce' ) ?></span>
                  </th>
                  <td class="d-flex align-items-center">
                     <div class="form-check form-switch <?php echo ( ! self::license_valid() ) ? 'require-pro' : ''; ?>">
                        <input type="checkbox" class="toggle-switch <?php echo ( ! self::license_valid() ) ? 'pro-version' : ''; ?>" id="<?php echo ! class_exists('Module_Inter_Bank') ? 'require_inter_bank_module_trigger_2' : 'enable_inter_bank_ticket_api'; ?>" name="enable_inter_bank_ticket_api" value="yes" <?php checked( self::get_setting( 'enable_inter_bank_ticket_api') === 'yes' && class_exists('Module_Inter_Bank') && self::license_valid() ); ?>/>
                     </div>
                     <?php
                     if ( $inter_module_active ) {
                        ?>
                        <button id="inter_bank_slip_settings" class="btn btn-outline-primary ms-3 inter-bank-slip input-control-wd-12"><?php echo esc_html__( 'Configurar Boleto', 'flexify-checkout-for-woocommerce' ) ?></button>
                        <div id="inter_bank_slip_container">
                           <div class="popup-content">
                              <div class="popup-header">
                                 <h5 class="popup-title"><?php echo esc_html__( 'Configure a forma de pagamento Boleto bancário', 'flexify-checkout-for-woocommerce' ); ?></h5>
                                 <button id="inter_bank_slip_close" class=" btn-close fs-lg" aria-label="Fechar"></button>
                              </div>
                              <div class="popup-body">
                                 <table class="form-table">
                                    <tr>
                                       <th>
                                          <?php echo esc_html( 'Título da forma de pagamento Boleto', 'flexify-checkout-for-woocommerce' ); ?>
                                          <span class="flexify-checkout-description"><?php echo esc_html__( 'Título que o usuário verá na finalização de compra.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                       </th>
                                       <td>
                                          <input type="text" class="form-control input-control-wd-20" name="bank_slip_gateway_title" value="<?php echo self::get_setting( 'bank_slip_gateway_title' ) ?>"/>
                                       </td>
                                    </tr>
                                    <tr>
                                       <th>
                                          <?php echo esc_html( 'Descrição da forma de pagamento Boleto', 'flexify-checkout-for-woocommerce' ); ?>
                                          <span class="flexify-checkout-description"><?php echo esc_html__( 'Descrição da forma de pagamento que o usuário verá na finalização de compra.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                       </th>
                                       <td>
                                          <input type="text" class="form-control input-control-wd-20" name="bank_slip_gateway_description" value="<?php echo self::get_setting( 'bank_slip_gateway_description' ) ?>"/>
                                       </td>
                                    </tr>
                                    <tr>
                                       <th>
                                          <?php echo esc_html( 'Instruções por e-mail da forma de pagamento Boleto', 'flexify-checkout-for-woocommerce' ); ?>
                                          <span class="flexify-checkout-description"><?php echo esc_html__( 'Texto exibido no e-mail junto do botão de copiar código Copia e Cola do Pix.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                       </th>
                                       <td>
                                          <input type="text" class="form-control input-control-wd-20" name="bank_slip_gateway_email_instructions" value="<?php echo self::get_setting( 'bank_slip_gateway_email_instructions' ) ?>"/>
                                       </td>
                                    </tr>
                                    <tr>
                                       <th>
                                          <?php echo esc_html( 'Mensagem do rodapé', 'flexify-checkout-for-woocommerce' ); ?>
                                          <span class="flexify-checkout-description"><?php echo esc_html__( 'Mensagem do rodapé do boleto bancário. Use a variável {order_id} para inserir o número do pedido.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                       </th>
                                       <td>
                                          <input type="text" class="form-control input-control-wd-20" name="bank_slip_gateway_footer_message" value="<?php echo self::get_setting( 'bank_slip_gateway_footer_message' ) ?>"/>
                                       </td>
                                    </tr>
                                    <tr>
                                       <th>
                                          <?php echo esc_html( 'Validade do Pix', 'flexify-checkout-for-woocommerce' ); ?>
                                          <span class="flexify-checkout-description"><?php echo esc_html__( 'Prazo máximo para pagamento do Pix em minutos.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                       </th>
                                       <td>
                                          <input type="number" class="form-control input-control-wd-5" name="bank_slip_gateway_expires" value="<?php echo self::get_setting( 'bank_slip_gateway_expires' ) ?>"/>
                                       </td>
                                    </tr>
                                 </table>
                              </div>
                           </div>
                        </div>
                     <?php
                     }
                     ?>
                  </td>
               </tr>


            <?php
            if ( $inter_module_active ) {
               ?>
               <tr>
                  <th>
                     <?php echo esc_html__( 'Configurar integração com banco Inter', 'flexify-checkout-for-woocommerce' ) ?>
                     <span class="flexify-checkout-description"><?php echo esc_html__( 'Configure suas credenciais para habilitar o recebimento via Pix ou boleto usando o banco Inter.', 'flexify-checkout-for-woocommerce' ) ?></span>
                  </th>
                  <td>
                     <button id="inter_bank_credencials_settings" class="btn btn-outline-primary"><?php echo esc_html__( 'Configurar credenciais', 'flexify-checkout-for-woocommerce' ) ?></button>
                     <div id="inter_bank_credendials_container">
                        <div class="popup-content">
                           <div class="popup-header">
                              <h5 class="popup-title"><?php echo esc_html__( 'Informe suas credenciais para integração', 'flexify-checkout-for-woocommerce' ); ?></h5>
                              <button id="inter_bank_credendials_close" class="btn-close fs-lg" aria-label="Fechar"></button>
                           </div>
                           <div class="popup-body">
                              <table class="form-table">
                                 <tr>
                                    <th>
                                       <?php echo esc_html( 'Ativar modo depuração', 'flexify-checkout-for-woocommerce' ); ?>
                                       <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative o modo depuração para salvar o registro de requisições da API.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                    </th>
                                    <td>
                                       <div class="form-check form-switch">
                                          <input type="checkbox" class="toggle-switch" id="inter_bank_debug_mode" name="inter_bank_debug_mode" value="yes" <?php checked( self::get_setting( 'inter_bank_debug_mode') === 'yes' ); ?>/>
                                       </div>
                                    </td>
                                 </tr>
                                 <tr>
                                    <th>
                                       <?php echo esc_html( 'ClientID', 'flexify-checkout-for-woocommerce' ); ?>
                                       <span class="flexify-checkout-description"><?php echo esc_html__( 'Chave aleatória ClientID da API do banco Inter.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                    </th>
                                    <td>
                                       <input type="text" class="form-control input-control-wd-20" name="inter_bank_client_id" value="<?php echo self::get_setting( 'inter_bank_client_id' ) ?>"/>
                                    </td>
                                 </tr>
                                 <tr>
                                    <th>
                                       <?php echo esc_html( 'ClientSecret', 'flexify-checkout-for-woocommerce' ); ?>
                                       <span class="flexify-checkout-description"><?php echo esc_html__( 'Chave aleatória ClientSecret da API do banco Inter.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                    </th>
                                    <td>
                                       <input type="text" class="form-control input-control-wd-20" name="inter_bank_client_secret" value="<?php echo self::get_setting( 'inter_bank_client_secret' ) ?>"/>
                                    </td>
                                 </tr>
                                 <tr>
                                    <th>
                                       <?php echo esc_html( 'Envie sua chave e certificado', 'flexify-checkout-for-woocommerce' ); ?>
                                       <span class="flexify-checkout-description"><?php echo esc_html__( 'Envie sua chave e certificado que você recebeu do banco Inter ao criar a aplicação.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                    </th>
                                 </tr>
                              </table>
                              <div class="drop-file-inter-bank">
                                 <?php
                                 $crt_file = get_option('flexify_checkout_inter_bank_crt_file');
                                 $key_file = get_option('flexify_checkout_inter_bank_key_file');

                                 if ( empty( $crt_file ) ) {
                                    ?>
                                    <div class="dropzone me-2" id="dropzone-crt">
                                       <div class="drag-text">
                                          <svg class="drag-and-drop-file-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M19.937 8.68c-.011-.032-.02-.063-.033-.094a.997.997 0 0 0-.196-.293l-6-6a.997.997 0 0 0-.293-.196c-.03-.014-.062-.022-.094-.033a.991.991 0 0 0-.259-.051C13.04 2.011 13.021 2 13 2H6c-1.103 0-2 .897-2 2v16c0 1.103.897 2 2 2h12c1.103 0 2-.897 2-2V9c0-.021-.011-.04-.013-.062a.99.99 0 0 0-.05-.258zM16.586 8H14V5.414L16.586 8zM6 20V4h6v5a1 1 0 0 0 1 1h5l.002 10H6z"></path></svg>
                                          <?php echo esc_html( 'Arraste e solte o arquivo .crt aqui', 'flexify-checkout-for-woocommerce' ); ?>
                                       </div>
                                       <div class="file-list"></div>
                                       <form enctype="multipart/form-data" action="upload.php" class="form-inter-bank-files" method="POST">
                                          <div class="drag-and-drop-file">
                                                <div class="custom-file">
                                                   <input type="file" class="custom-file-input" id="upload-file-crt" name="crt_file" hidden>
                                                   <label class="custom-file-label mb-4" for="upload-file-crt"><?php echo esc_html( 'Ou clique para procurar seu arquivo', 'flexify-checkout-for-woocommerce' ); ?></label>
                                                </div>
                                          </div>
                                       </form>
                                    </div>
                                    <?php
                                 }

                                 if ( empty( $key_file ) ) {
                                    ?>
                                    <div class="dropzone ms-2" id="dropzone-key">
                                       <div class="drag-text">
                                       <svg class="drag-and-drop-file-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M19.937 8.68c-.011-.032-.02-.063-.033-.094a.997.997 0 0 0-.196-.293l-6-6a.997.997 0 0 0-.293-.196c-.03-.014-.062-.022-.094-.033a.991.991 0 0 0-.259-.051C13.04 2.011 13.021 2 13 2H6c-1.103 0-2 .897-2 2v16c0 1.103.897 2 2 2h12c1.103 0 2-.897 2-2V9c0-.021-.011-.04-.013-.062a.99.99 0 0 0-.05-.258zM16.586 8H14V5.414L16.586 8zM6 20V4h6v5a1 1 0 0 0 1 1h5l.002 10H6z"></path></svg>
                                       <?php echo esc_html( 'Arraste e solte o arquivo .key aqui', 'flexify-checkout-for-woocommerce' ); ?>
                                       </div>
                                       <div class="file-list"></div>
                                       <form enctype="multipart/form-data" action="upload.php" class="form-inter-bank-files" method="POST">
                                          <div class="drag-and-drop-file">
                                                <div class="custom-file">
                                                   <input type="file" class="custom-file-input" id="upload-file-key" name="key_file" hidden>
                                                   <label class="custom-file-label mb-4" for="upload-file-key"><?php echo esc_html( 'Ou clique para procurar seu arquivo', 'flexify-checkout-for-woocommerce' ); ?></label>
                                                </div>
                                          </div>
                                       </form>
                                    </div>
                                    <?php
                                 }
                                 ?>
                              </div>
                              <?php
                              if ( ! empty( $key_file ) && ! empty( $crt_file ) ) {
                                 ?>
                                 <div class="file-uploaded-info my-3">
                                    <div class="d-flex flex-collumn align-items-start me-3">
                                       <span class="fs-lg"><?php echo esc_html( 'Sua chave e certificado já foram enviados.', 'flexify-checkout-for-woocommerce' ); ?></span>
                                       <span class="text-muted"><?php echo esc_html( 'Sua chave e certificado já foram enviados.', 'flexify-checkout-for-woocommerce' ); ?></span>
                                    </div>
                                    <button class="btn btn-icon btn-outline-danger button-loading" name="exclude_inter_bank_crt_key_files">
                                       <svg class="delete-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
                                    </button>
                                 </div>

                                 <?php
                                 if ( get_option('flexify_checkout_inter_bank_webhook') === 'enabled' ) {
                                    ?>
                                    <div class="webhook-state my-3 d-flex align-items-center">
                                       <div class="ping me-3">
                                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: #fff"><path d="m10 15.586-3.293-3.293-1.414 1.414L10 18.414l9.707-9.707-1.414-1.414z"></path></svg>
                                       </div>
                                       <div class="d-flex flex-collumn text-left">
                                          <span class="fs-normal"><?php echo esc_html( 'Ouvindo Webhook do banco Inter.', 'flexify-checkout-for-woocommerce' ); ?></span>
                                          <span class="text-muted"><?php echo esc_html( 'Aprovação automática de pedidos ativada.', 'flexify-checkout-for-woocommerce' ); ?></span>
                                       </div>
                                    </div>
                                    <?php
                                 }
                              }
                              ?>
                           </div>
                        </div>
                     </div>
                  </td>
               </tr>
               <?php
            }
         }
      ?>
  </table>
</div>

<!-- Require Inter bank module popup -->
<div id="require_inter_bank_module_container">
   <div class="popup-content">
      <div class="popup-header">
         <h5 class="popup-title"><?php echo esc_html__( 'Instalar módulo adicional do banco Inter', 'flexify-checkout-for-woocommerce' ); ?></h5>
         <button id="require_inter_bank_module_close" class="btn-close fs-lg" aria-label="Fechar"></button>
      </div>
      <div class="popup-body text-left">
         <span class="fs-normal inter-bank-require-instruction"><?php echo __( 'Observação: Para usar este módulo adicional é necessário que seja um cliente PJ do banco Inter. <strong>Não pode ser MEI.</strong>', 'flexify-checkout-for-woocommerce' ); ?></span>
         <span class="d-block fs-normal mt-3 inter-bank-require-instruction"><?php echo __( 'Se preencher o requisito acima, por favor, siga para a instalação clicando no botão <strong>Instalar módulo</strong> abaixo.', 'flexify-checkout-for-woocommerce' ); ?></span>
      </div>
      <div class="popup-footer">
         <button type="button" class="btn btn-primary" id="install_inter_bank_module"><?php echo esc_html__( 'Instalar módulo', 'flexify-checkout-for-woocommerce' ); ?></button>
      </div>
   </div>
</div>