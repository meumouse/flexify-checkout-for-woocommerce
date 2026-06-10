<?php

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\API\License;

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div id="about" class="nav-content">
  <table class="form-table">
	<tr>
		<th>
			<?php esc_html_e( 'Ativar atualizações automáticas', 'flexify-checkout-for-woocommerce' );
			
			if ( ! License::is_valid() ) : ?>
                <span class="badge pro bg-primary rounded-pill ms-2">
                    <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                    <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                </span>
			<?php endif; ?>

			<span class="flexify-checkout-description"><?php esc_html_e( 'Ative essa opção para que o plugin Flexify Checkout seja atualizado automaticamente sempre que possível.', 'flexify-checkout-for-woocommerce' ); ?></span>
		</th>
		<td>
			<div class="form-check form-switch <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
				<input type="checkbox" class="toggle-switch <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" id="enable_auto_updates" name="enable_auto_updates" value="yes" <?php checked( Admin_Options::get_setting('enable_auto_updates') === 'yes' && License::is_valid() ); ?> />
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php esc_html_e( 'Mostrar notificação de atualização disponível', 'flexify-checkout-for-woocommerce' ); ?>
			<span class="flexify-checkout-description"><?php esc_html_e( 'Ative essa opção para que seja exibido uma notificação de atualização disponível.', 'flexify-checkout-for-woocommerce' ); ?></span>
		</th>
		<td>
			<div class="form-check form-switch">
				<input type="checkbox" class="toggle-switch" id="enable_update_notices" name="enable_update_notices" value="yes" <?php checked( Admin_Options::get_setting('enable_update_notices') === 'yes' ); ?> />
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php esc_html_e( 'Ativar modo depuração', 'flexify-checkout-for-woocommerce' ); ?>
			<span class="flexify-checkout-description"><?php esc_html_e( 'Ative essa opção para ativar o modo depuração e ter acesso a informações no console do navegador, desativar minificação de scripts e estilos e demais detalhes para resolução de problemas.', 'flexify-checkout-for-woocommerce' ); ?></span>
		</th>
		<td>
			<div class="form-check form-switch">
				<input type="checkbox" class="toggle-switch" id="enable_debug_mode" name="enable_debug_mode" value="yes" <?php checked( Admin_Options::get_setting('enable_debug_mode') === 'yes' ); ?> />
			</div>
		</td>
	</tr>

	<tr class="container-separator"></tr>

	<tr>
		<td class="d-grid">
			<h3 class="mb-4"><?php esc_html_e( 'Informações sobre a licença:', 'flexify-checkout-for-woocommerce' ); ?></h3>

			<span class="mb-2 license-details-item"><?php esc_html_e( 'Status da licença:', 'flexify-checkout-for-woocommerce' ) ?>
				<span id="fcw-license-status">
					<?php if ( License::is_valid() ) : ?>
						<span class="badge bg-translucent-success rounded-pill"><?php _e( 'Válida', 'flexify-checkout-for-woocommerce' );?></span>
					<?php elseif ( empty( get_option('flexify_checkout_license_key') ) ) : ?>
						<span class="fs-sm"><?php _e(  'Nenhuma licença informada', 'flexify-checkout-for-woocommerce' );?></span>
					<?php else : ?>
						<span class="badge bg-translucent-danger rounded-pill"><?php _e( 'Inválida', 'flexify-checkout-for-woocommerce' );?></span>
					<?php endif; ?>
				</span>
			</span>

			<span class="mb-2 license-details-item"><?php esc_html_e( 'Recursos:', 'flexify-checkout-for-woocommerce' ) ?>
				<span id="fcw-license-features">
					<?php if ( License::is_valid() ) : ?>
						<span class="badge bg-translucent-primary rounded-pill"><?php esc_html_e(  'Pro', 'flexify-checkout-for-woocommerce' );?></span>
					<?php else : ?>
						<span class="badge bg-translucent-warning rounded-pill"><?php esc_html_e(  'Básicos', 'flexify-checkout-for-woocommerce' );?></span>
					<?php endif; ?>
				</span>
			</span>

			<?php if ( License::is_valid() ) :
				$license_key = get_option('flexify_checkout_license_key');
				$object_query = get_option('flexify_checkout_license_response_object');

				if ( strpos( $license_key, 'CM-' ) === 0 ) : ?>
					<span id="fcw-license-type" class="mb-2 license-details-item"><?php echo sprintf( esc_html__( 'Assinatura: Clube M - %s', 'flexify-checkout-for-woocommerce' ), License::license_title() ) ?></span>
				<?php elseif ( is_object( $object_query ) && ! empty( $object_query ) && isset( $object_query->expire_date ) && $object_query->expire_date !== 'No expiry' ) : ?>
					<span id="fcw-license-type" class="mb-2 license-details-item"><?php echo sprintf( esc_html__( 'Assinatura: %s', 'flexify-checkout-for-woocommerce' ), License::license_title() ) ?></span>
				<?php else : ?>
					<span id="fcw-license-type" class="mb-2 license-details-item"><?php echo sprintf( esc_html__( 'Tipo da licença: %s', 'flexify-checkout-for-woocommerce' ), License::license_title() ) ?></span>
				<?php endif; ?>

				<span id="fcw-license-expiry" class="mb-2 license-details-item"><?php echo sprintf( esc_html__( 'Licença expira em: %s', 'flexify-checkout-for-woocommerce' ), License::license_expire() ) ?></span>
				
				<span class="mb-2 license-details-item"><?php esc_html_e( 'Sua chave de licença:', 'flexify-checkout-for-woocommerce' ) ?>
					<?php if ( ! empty( $license_key ) ) :
						echo esc_html( substr( $license_key, 0, 9 ) . "XXXXXXXX-XXXXXXXX" . substr( $license_key, -9 ) );
					else :
						esc_html_e(  'Não disponível', 'flexify-checkout-for-woocommerce' );
					endif; ?>
				</span>
			<?php endif; ?>
		</td>
	</tr>

	<?php if ( License::is_valid() ) : ?>
		<tr>
			<td>
				<button id="flexify_checkout_deactive_license" name="flexify_checkout_deactive_license" class="btn btn-sm btn-primary"><?php esc_attr_e( 'Desativar licença', 'flexify-checkout-for-woocommerce' ); ?></button>
				<button id="flexify_checkout_sync_license" name="flexify_checkout_sync_license" class="btn btn-sm btn-outline-primary ms-3"><?php esc_attr_e( 'Sincronizar licença', 'flexify-checkout-for-woocommerce' ); ?></button>
			</td>
		</tr>
		
	<?php else : ?>
		<tr>
			<td class="d-grid">
				<a class="btn btn-primary my-4 d-flex align-items-center w-fit" href="https://meumouse.com/plugins/flexify-checkout-para-woocommerce/?utm_source=wordpress&utm_medium=plugins-list&utm_campaign=flexify_checkout" target="_blank">
					<svg class="icon icon-white me-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round"></g><g> <path d="M13.5 16.5854C13.5 17.4138 12.8284 18.0854 12 18.0854C11.1716 18.0854 10.5 17.4138 10.5 16.5854C10.5 15.7569 11.1716 15.0854 12 15.0854C12.8284 15.0854 13.5 15.7569 13.5 16.5854Z"></path> <path fill-rule="evenodd" clip-rule="evenodd" d="M6.33367 10C6.20971 9.64407 6.09518 9.27081 5.99836 8.88671C5.69532 7.68444 5.54485 6.29432 5.89748 4.97439C6.26228 3.60888 7.14664 2.39739 8.74323 1.59523C10.3398 0.793061 11.8397 0.806642 13.153 1.32902C14.4225 1.83396 15.448 2.78443 16.2317 3.7452C16.4302 3.98851 16.6166 4.23669 16.7907 4.48449C17.0806 4.89706 16.9784 5.45918 16.5823 5.7713C16.112 6.14195 15.4266 6.01135 15.0768 5.52533C14.9514 5.35112 14.8197 5.17831 14.6819 5.0094C14.0088 4.18414 13.2423 3.51693 12.4138 3.18741C11.6292 2.87533 10.7252 2.83767 9.64112 3.38234C8.55703 3.92702 8.04765 4.6748 7.82971 5.49059C7.5996 6.35195 7.6774 7.36518 7.93771 8.39788C8.07953 8.96054 8.26936 9.50489 8.47135 10H18C19.6569 10 21 11.3431 21 13V20C21 21.6569 19.6569 23 18 23H6C4.34315 23 3 21.6569 3 20V13C3 11.3431 4.34315 10 6 10H6.33367ZM19 13C19 12.4477 18.5523 12 18 12H6C5.44772 12 5 12.4477 5 13V20C5 20.5523 5.44772 21 6 21H18C18.5523 21 19 20.5523 19 20V13Z"></path></g></svg>	
					<span><?php esc_html_e(  'Comprar licença', 'flexify-checkout-for-woocommerce' );?></span>
				</a>

				<span class="bg-translucent-success fw-medium rounded-2 px-3 py-2 mb-4 d-flex align-items-center w-fit">
					<svg class="icon icon-success me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>
					<?php esc_html_e( 'Informe sua licença abaixo para desbloquear todos os recursos.', 'flexify-checkout-for-woocommerce' ) ?>
				</span>

				<span class="form-label d-block mt-2"><?php esc_html_e( 'Código da licença', 'flexify-checkout-for-woocommerce' ) ?></span>
				
				<div class="input-group" style="width: 550px;">
					<input class="form-control" type="text" placeholder="XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX" id="flexify_checkout_license_key" name="flexify_checkout_license_key" size="50" value="<?php echo get_option( 'flexify_checkout_license_key' ) ?>" />
					<button id="flexify_checkout_active_license" name="flexify_checkout_active_license" class="btn btn-primary"><?php esc_html_e( 'Ativar licença', 'flexify-checkout-for-woocommerce' ); ?></button>
				</div>
			</td>
		</tr>
	<?php endif; ?>

	<?php if ( get_option('flexify_checkout_alternative_license_activation') === 'yes' ) : ?>
		<tr>
			<td>
				<h3><?php esc_attr_e( 'Notamos que teve problemas de conexão ao tentar ativar sua licença', 'flexify-checkout-for-woocommerce' ); ?></h3>
				<span class="d-block text-muted"><?php esc_attr_e( 'Você pode fazer upload do arquivo .key da licença para fazer sua ativação manual.', 'flexify-checkout-for-woocommerce' ); ?></span>
				<a class="fancy-link mt-2 mb-3" href="https://meumouse.com/minha-conta/licenses/?domain=<?php echo urlencode( License::get_domain() ); ?>&license_key=<?php echo urlencode( get_option('flexify_checkout_temp_license_key') ); ?>&app_version=<?php echo urlencode( FLEXIFY_CHECKOUT_VERSION ); ?>&product_id=<?php echo ( strpos( get_option('flexify_checkout_temp_license_key'), 'CM-' ) === 0 ) ? '7' : '3'; ?>&settings_page=<?php echo urlencode( License::get_domain() . '/wp-admin/admin.php?page=flexify-checkout-for-woocommerce' ); ?>" target="_blank"><?php esc_html_e( 'Clique aqui para gerar seu arquivo de licença', 'flexify-checkout-for-woocommerce' ) ?></a>

				<div class="drop-file-license-key">
					<div class="dropzone-license mt-4" id="license_key_zone">
						<div class="drag-text">
							<svg class="drag-and-drop-file-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M19.937 8.68c-.011-.032-.02-.063-.033-.094a.997.997 0 0 0-.196-.293l-6-6a.997.997 0 0 0-.293-.196c-.03-.014-.062-.022-.094-.033a.991.991 0 0 0-.259-.051C13.04 2.011 13.021 2 13 2H6c-1.103 0-2 .897-2 2v16c0 1.103.897 2 2 2h12c1.103 0 2-.897 2-2V9c0-.021-.011-.04-.013-.062a.99.99 0 0 0-.05-.258zM16.586 8H14V5.414L16.586 8zM6 20V4h6v5a1 1 0 0 0 1 1h5l.002 10H6z"></path></svg>
							<?php echo esc_html( 'Arraste e solte o arquivo .key aqui', 'flexify-checkout-for-woocommerce' ); ?>
						</div>

						<div class="file-list"></div>

						<form enctype="multipart/form-data" action="upload.php" class="upload-license-key" method="POST">
							<div class="drag-and-drop-file">
								<div class="custom-file">
									<input type="file" class="custom-file-input" id="upload_license_key" name="upload_license_key" hidden>
									<label class="custom-file-label mb-4" for="upload_license_key"><?php echo esc_html( 'Ou clique para procurar seu arquivo', 'flexify-checkout-for-woocommerce' ); ?></label>
								</div>
							</div>
						</form>
					</div>
				</div>
			</td>
		</tr>
	<?php endif; ?>
	
	<tr class="container-separator"></tr>
	
	<tr class="w-75 mt-5">
		<td>
			<h3 class="h2 mt-0"><?php esc_html_e( 'Status do sistema:', 'flexify-checkout-for-woocommerce' ); ?></h3>

			<h4 class="mt-4"><?php esc_html_e( 'WordPress', 'flexify-checkout-for-woocommerce' ); ?></h4>

			<div class="d-flex mb-2">
				<span><?php esc_html_e( 'Versão do WordPress:', 'flexify-checkout-for-woocommerce' ); ?></span>
				<span class="ms-2"><?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
			</div>

			<div class="d-flex mb-2">
				<span><?php esc_html_e( 'WordPress Multisite:', 'flexify-checkout-for-woocommerce' ); ?></span>
				<span class="ms-2"><?php echo is_multisite() ? esc_html__( 'Sim', 'flexify-checkout-for-woocommerce' ) : esc_html__( 'Não', 'flexify-checkout-for-woocommerce' ); ?></span>
			</div>

			<div class="d-flex mb-2">
				<span><?php esc_html_e( 'Modo de depuração do WordPress:', 'flexify-checkout-for-woocommerce' ); ?></span>
				<span class="ms-2"><?php echo defined( 'WP_DEBUG' ) && WP_DEBUG ? esc_html__( 'Ativo', 'flexify-checkout-for-woocommerce' ) : esc_html__( 'Desativado', 'flexify-checkout-for-woocommerce' ); ?></span>
			</div>

			<h4 class="mt-4"><?php esc_html_e( 'WooCommerce', 'flexify-checkout-for-woocommerce' ); ?></h4>

			<div class="d-flex mb-2">
				<span><?php esc_html_e( 'Versão do WooCommerce:', 'flexify-checkout-for-woocommerce' ); ?></span>
				<span class="ms-2">
					<?php if( version_compare( WC_VERSION, '6.0', '<' ) ) : ?>
						<span class="badge bg-translucent-danger">
							<span>
								<?php echo esc_html( WC_VERSION ); ?>
							</span>
							<span>
								<?php esc_html_e( 'A versão mínima exigida do WooCommerce é 6.0', 'flexify-checkout-for-woocommerce' ); ?>
							</span>
						</span>
					<?php else : ?>
						<span class="badge bg-translucent-success">
							<?php echo esc_html( WC_VERSION ); ?>
						</span>
					<?php endif; ?>
				</span>
			</div>
			<div class="d-flex mb-2">
				<span><?php esc_html_e( 'Versão do Flexify Checkout para WooCommerce:', 'flexify-checkout-for-woocommerce' ); ?></span>
				<span class="ms-2">
					<span class="badge bg-translucent-success">
						<?php $license_status = ( License::is_valid() ) ? __( 'Pro', 'flexify-checkout-for-woocommerce' ) : '';

						echo sprintf( esc_html( FLEXIFY_CHECKOUT_VERSION . ' %s' ), $license_status ); ?>
					</span>
				</span>
			</div>

			<h4 class="mt-4"><?php esc_html_e( 'Servidor', 'flexify-checkout-for-woocommerce' ); ?></h4>
			<div class="d-flex mb-2">
				<span><?php esc_html_e( 'Versão do PHP:', 'flexify-checkout-for-woocommerce' ); ?></span>
				<span class="ms-2">
					<?php if ( version_compare( PHP_VERSION, '7.4', '<' ) ) : ?>
						<span class="badge bg-translucent-danger">
							<span>
								<?php echo esc_html( PHP_VERSION ); ?>
							</span>
							<span>
								<?php esc_html_e( 'A versão mínima exigida do PHP é 7.4', 'flexify-checkout-for-woocommerce' ); ?>
							</span>
						</span>
					<?php else : ?>
						<span class="badge bg-translucent-success">
							<?php echo esc_html( PHP_VERSION ); ?>
						</span>
					<?php endif; ?>
				</span>
			</div>
			<div class="d-flex mb-2">
				<span><?php esc_html_e( 'DOMDocument:', 'flexify-checkout-for-woocommerce' ); ?></span>
				<span class="ms-2">
					<span>
						<?php if ( ! class_exists('DOMDocument') ) : ?>
							<span class="badge bg-translucent-danger">
								<?php esc_html_e( 'Não', 'flexify-checkout-for-woocommerce' ); ?>
							</span>
						<?php else : ?>
							<span class="badge bg-translucent-success">
								<?php esc_html_e( 'Sim', 'flexify-checkout-for-woocommerce' ); ?>
							</span>
						<?php endif; ?>
					</span>
				</span>
			</div>
			<div class="d-flex mb-2">
				<span><?php esc_html_e( 'Extensão cURL:', 'flexify-checkout-for-woocommerce' ); ?></span>
				<span class="ms-2">
					<span>
						<?php if ( ! extension_loaded('curl') ) : ?>
							<span class="badge bg-translucent-danger">
								<?php esc_html_e( 'Não', 'flexify-checkout-for-woocommerce' ); ?>
							</span>
						<?php else : ?>
							<span class="badge bg-translucent-success">
								<?php esc_html_e( 'Sim', 'flexify-checkout-for-woocommerce' ); ?>
							</span>
						<?php endif; ?>
					</span>
				</span>
			</div>

			<?php if ( Admin_Options::get_setting('enable_inter_bank_pix_api') === 'yes' || Admin_Options::get_setting('enable_inter_bank_ticket_api') === 'yes' ) : ?>
				<div class="d-flex mb-2">
					<span><?php esc_html_e( 'Extensão GD:', 'flexify-checkout-for-woocommerce' ); ?></span>
					<span class="ms-2">
						<span>
							<?php if ( ! extension_loaded('gd') ) : ?>
								<span class="badge bg-translucent-danger">
									<?php esc_html_e( 'Não', 'flexify-checkout-for-woocommerce' ); ?>
								</span>
							<?php else : ?>
								<span class="badge bg-translucent-success">
									<?php esc_html_e( 'Sim', 'flexify-checkout-for-woocommerce' ); ?>
								</span>
							<?php endif; ?>
						</span>
					</span>
				</div>
			<?php endif; ?>

			<div class="d-flex mb-2">
				<span><?php esc_html_e( 'Extensão OpenSSL:', 'flexify-checkout-for-woocommerce' ); ?></span>
				<span class="ms-2">
					<span>
						<?php if ( !extension_loaded('openssl') ) : ?>
							<span class="badge bg-translucent-danger">
								<?php esc_html_e( 'Não', 'flexify-checkout-for-woocommerce' ); ?>
							</span>
						<?php else : ?>
							<span class="badge bg-translucent-success">
								<?php esc_html_e( 'Sim', 'flexify-checkout-for-woocommerce' ); ?>
							</span>
						<?php endif; ?>
					</span>
				</span>
			</div>

			<?php if ( function_exists('ini_get') ) : ?>
				<div class="d-flex mb-2">
					<span>
						<?php $post_max_size = ini_get( 'post_max_size' ); ?>

						<?php esc_html_e( 'Tamanho máximo da postagem do PHP:', 'flexify-checkout-for-woocommerce' ); ?>
					</span>
					<span class="ms-2">
						<?php if ( wp_convert_hr_to_bytes( $post_max_size ) < 64000000 ) : ?>
							<span>
								<span class="badge bg-translucent-danger">
									<?php echo esc_html( $post_max_size ); ?>
								</span>
								<span>
									<?php esc_html_e( 'Valor mínimo recomendado é 64M', 'flexify-checkout-for-woocommerce' ); ?>
								</span>
							</span>
						<?php else : ?>
							<span class="badge bg-translucent-success">
								<?php echo esc_html( $post_max_size ); ?>
							</span>
						<?php endif; ?>
					</span>
				</div>
				<div class="d-flex mb-2">
					<span>
						<?php $max_execution_time = ini_get( 'max_execution_time' ); ?>
						<?php esc_html_e( 'Limite de tempo do PHP:', 'flexify-checkout-for-woocommerce' ); ?>
					</span>
					<span class="ms-2">
						<?php if ( $max_execution_time < 180 ) : ?>
							<span>
								<span class="badge bg-translucent-danger">
									<?php echo esc_html( $max_execution_time ); ?>
								</span>
								<span>
									<?php esc_html_e( 'Valor mínimo recomendado é 180', 'flexify-checkout-for-woocommerce' ); ?>
								</span>
							</span>
						<?php else : ?>
							<span class="badge bg-translucent-success">
								<?php echo esc_html( $max_execution_time ); ?>
							</span>
						<?php endif; ?>
					</span>
				</div>
				<div class="d-flex mb-2">
					<span>
						<?php $max_input_vars = ini_get( 'max_input_vars' ); ?>
						<?php esc_html_e( 'Variáveis máximas de entrada do PHP:', 'flexify-checkout-for-woocommerce' ); ?>
					</span>
					<span class="ms-2">
						<?php if ( $max_input_vars < 10000 ) : ?>
							<span>
								<span class="badge bg-translucent-danger">
									<?php echo esc_html( $max_input_vars ); ?>
								</span>
								<span>
									<?php esc_html_e( 'Valor mínimo recomendado é 10000', 'flexify-checkout-for-woocommerce' ); ?>
								</span>
							</span>
						<?php else : ?>
							<span class="badge bg-translucent-success">
								<?php echo esc_html( $max_input_vars ); ?>
							</span>
						<?php endif; ?>
					</span>
				</div>
				<div class="d-flex mb-2">
					<span>
						<?php $memory_limit = ini_get( 'memory_limit' ); ?>
						<?php esc_html_e( 'Limite de memória do PHP:', 'flexify-checkout-for-woocommerce' ); ?>
					</span>
					<span class="ms-2">
						<?php if ( wp_convert_hr_to_bytes( $memory_limit ) < 128000000 ) : ?>
							<span>
								<span class="badge bg-translucent-danger">
									<?php echo esc_html( $memory_limit ); ?>
								</span>
								<span>
									<?php esc_html_e( 'Valor mínimo recomendado é 128M', 'flexify-checkout-for-woocommerce' ); ?>
								</span>
							</span>
						<?php else : ?>
							<span class="badge bg-translucent-success">
								<?php echo esc_html( $memory_limit ); ?>
							</span>
						<?php endif; ?>
					</span>
				</div>
				<div class="d-flex mb-2">
					<span>
						<?php $upload_max_filesize = ini_get( 'upload_max_filesize' ); ?>
						<?php esc_html_e( 'Tamanho máximo de envio do PHP:', 'flexify-checkout-for-woocommerce' ); ?>
					</span>
					<span class="ms-2">
						<?php if ( wp_convert_hr_to_bytes( $upload_max_filesize ) < 64000000 ) : ?>
							<span>
								<span class="badge bg-translucent-danger">
									<?php echo esc_html( $upload_max_filesize ); ?>
								</span>
								<span>
									<?php esc_html_e( 'Valor mínimo recomendado é 64M', 'flexify-checkout-for-woocommerce' ); ?>
								</span>
							</span>
						<?php else : ?>
							<span class="badge bg-translucent-success">
								<?php echo esc_html( $upload_max_filesize ); ?>
							</span>
						<?php endif; ?>
					</span>
				</div>
				<div class="d-flex mb-2">
					<span><?php esc_html_e( 'Função PHP "file_get_content":', 'flexify-checkout-for-woocommerce' ); ?></span>
					<span class="ms-2">
						<?php if ( ! ini_get( 'allow_url_fopen' ) ) : ?>
							<span class="badge bg-translucent-danger">
								<?php esc_html_e( 'Desligado', 'flexify-checkout-for-woocommerce' ); ?>
							</span>
						<?php else : ?>
							<span class="badge bg-translucent-success">
								<?php esc_html_e( 'Ligado', 'flexify-checkout-for-woocommerce' ); ?>
							</span>
						<?php endif; ?>
					</span>
				</div>
			<?php endif; ?>
		</td>

		<tr class="container-separator"></tr>

		<tr>
			<td class="d-flex">
				<button id="fcw_reset_settings_trigger" class="btn btn-sm btn-outline-warning d-flex align-items-center me-3">
					<svg class="icon icon-lg icon-warning me-2" xmlns="http://www.w3.org/2000/svg"><path d="M12 16c1.671 0 3-1.331 3-3s-1.329-3-3-3-3 1.331-3 3 1.329 3 3 3z"></path><path d="M20.817 11.186a8.94 8.94 0 0 0-1.355-3.219 9.053 9.053 0 0 0-2.43-2.43 8.95 8.95 0 0 0-3.219-1.355 9.028 9.028 0 0 0-1.838-.18V2L8 5l3.975 3V6.002c.484-.002.968.044 1.435.14a6.961 6.961 0 0 1 2.502 1.053 7.005 7.005 0 0 1 1.892 1.892A6.967 6.967 0 0 1 19 13a7.032 7.032 0 0 1-.55 2.725 7.11 7.11 0 0 1-.644 1.188 7.2 7.2 0 0 1-.858 1.039 7.028 7.028 0 0 1-3.536 1.907 7.13 7.13 0 0 1-2.822 0 6.961 6.961 0 0 1-2.503-1.054 7.002 7.002 0 0 1-1.89-1.89A6.996 6.996 0 0 1 5 13H3a9.02 9.02 0 0 0 1.539 5.034 9.096 9.096 0 0 0 2.428 2.428A8.95 8.95 0 0 0 12 22a9.09 9.09 0 0 0 1.814-.183 9.014 9.014 0 0 0 3.218-1.355 8.886 8.886 0 0 0 1.331-1.099 9.228 9.228 0 0 0 1.1-1.332A8.952 8.952 0 0 0 21 13a9.09 9.09 0 0 0-.183-1.814z"></path></svg>
					<?php esc_html_e( 'Redefinir configurações', 'flexify-checkout-for-woocommerce' ); ?>
				</button>

				<div id="fcw_reset_settings_container" class="popup-container">
					<div class="popup-content">
						<div class="popup-header border-bottom-0 justify-content-end">
							<button id="fcw_close_reset" class="btn-close" aria-label="<?php esc_html( 'Fechar', 'flexify-checkout-for-woocommerce' ); ?>"></button>
						</div>

						<div class="popup-body">
							<div class="d-flex flex-column align-items-center p-4">
								<div class="btn-icon rounded-circle p-2 mb-3 bg-translucent-danger">
									<svg class="icon icon-lg icon-danger" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M11.953 2C6.465 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.493 2 11.953 2zM12 20c-4.411 0-8-3.589-8-8s3.567-8 7.953-8C16.391 4 20 7.589 20 12s-3.589 8-8 8z"></path><path d="M11 7h2v7h-2zm0 8h2v2h-2z"></path></svg>
								</div>
								<h5 class="popup-title text-center"><?php esc_html_e('Atenção! Você realmente deseja redefinir as configurações?', 'flexify-checkout-for-woocommerce'); ?></h5>
								<span class="title-hightlight bg-danger mt-2 mb-3"></span>
								<span class="text-muted fs-lg p-3"><?php esc_html_e( 'Ao redefinir as configurações do plugin, todas opções serão removidas, voltando ao estado original. Sua licença não será removida.', 'flexify-checkout-for-woocommerce' ) ?></span>
							</div>
							
							<div class="my-4 p-3">
								<button id="confirm_reset_settings" class="btn btn-lg btn-outline-secondary"><?php esc_html_e('Sim, desejo redefinir', 'flexify-checkout-for-woocommerce'); ?></button>
							</div>
						</div>
					</div>
				</div>

				<a class="btn btn-sm btn-outline-danger d-flex align-items-center" target="_blank" href="https://meumouse.com/reportar-problemas/?wpf9053_2=<?php echo urlencode( FLEXIFY_CHECKOUT_ADMIN_EMAIL ); ?>&wpf9053_5=<?php echo urlencode( 'Flexify Checkout para WooCommerce' ) ?>&wpf9053_9=<?php echo urlencode( License::is_valid() ? 'Sim' : 'Não' ) ?>&wpf9053_7=<?php echo urlencode( License::get_domain() ) ?>&wpf9053_6=<?php echo urlencode( wp_get_theme()->get('Name') ) ?>"><?php esc_html_e( 'Reportar problemas', 'flexify-checkout-for-woocommerce' ); ?></a>
			</td>
		</tr>
	</tr>
  </table>
</div>