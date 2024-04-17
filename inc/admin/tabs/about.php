<?php

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div id="about" class="nav-content">
  <table class="form-table">
	<tr>
		<td class="d-grid">
			<h3 class="mb-4"><?php esc_html_e( 'Informações sobre a licença:', 'flexify-checkout-for-woocommerce' ); ?></h3>
			<span class="mb-2"><?php echo esc_html__( 'Status da licença:', 'flexify-checkout-for-woocommerce' ) ?>
				<?php if ( self::license_valid() ) : ?>
					<span class="badge bg-translucent-success rounded-pill"><?php _e(  'Válida', 'flexify-checkout-for-woocommerce' );?></span>
				<?php elseif ( empty( get_option('flexify_checkout_license_key') ) ) : ?>
					<span class="fs-sm"><?php _e(  'Nenhuma licença informada', 'flexify-checkout-for-woocommerce' );?></span>
				<?php else : ?>
				<span class="badge bg-translucent-danger rounded-pill"><?php _e(  'Inválida', 'flexify-checkout-for-woocommerce' );?></span>
				<?php endif; ?>
			</span>

			<span class="mb-2"><?php echo esc_html__( 'Recursos:', 'flexify-checkout-for-woocommerce' ) ?>
				<?php if ( self::license_valid() ) : ?>
					<span class="badge bg-translucent-primary rounded-pill"><?php _e(  'Pro', 'flexify-checkout-for-woocommerce' );?></span>
				<?php else : ?>
					<span class="badge bg-translucent-warning rounded-pill"><?php _e(  'Grátis', 'flexify-checkout-for-woocommerce' );?></span>
				<?php endif; ?>
			</span>

			<?php

			if ( self::license_valid() ) {
				?>
				<span class="mb-2"><?php echo sprintf( esc_html__( 'Tipo da licença: %s', 'flexify-checkout-for-woocommerce' ), self::license_title() ) ?></span>
				<span class="mb-2"><?php echo sprintf( esc_html__( 'Licença expira em: %s', 'flexify-checkout-for-woocommerce' ), self::license_expire() ) ?></span>
				
				<span class="mb-2"><?php echo esc_html__( 'Sua chave de licença:', 'flexify-checkout-for-woocommerce' ) ?>
					<?php 
					if ( ! empty( get_option('flexify_checkout_license_key') ) ) {
						echo esc_attr( substr( get_option('flexify_checkout_license_key'), 0, 9 ) . "XXXXXXXX-XXXXXXXX" . substr( get_option('flexify_checkout_license_key'), -9 ) );
					} else {
						echo __(  'Não disponível', 'flexify-checkout-for-woocommerce' );
					}
					?>
				</span>
				<?php
			}

			?>
		</td>
	</tr>
	<?php
	if ( self::license_valid() ) {
		?>
		<tr>
			<td>
				<button id="flexify_checkout_deactive_license" name="flexify_checkout_deactive_license" class="btn btn-sm btn-primary button-loading" type="submit">
					<span><?php esc_attr_e( 'Desativar licença', 'flexify-checkout-for-woocommerce' ); ?></span>
				</button>
			</td>
		</tr>
		<?php
	} else {
		if ( get_option('flexify_checkout_alternative_license_activation') === 'yes' ) {
			?>
			<tr>
				<td>
					<span class="h4 d-block"><?php esc_attr_e( 'Notamos que teve problemas de conexão ao tentar ativar sua licença', 'flexify-checkout-for-woocommerce' ); ?></span>
					<span class="d-block text-muted"><?php esc_attr_e( 'Você pode fazer upload do arquivo .key da licença para fazer sua ativação manual.', 'flexify-checkout-for-woocommerce' ); ?></span>
					<a class="fancy-link mt-2 mb-3" href="https://meumouse.com/minha-conta/licenses/?domain=<?php echo urlencode( Flexify_Checkout_Api::get_domain() ); ?>&license_key=<?php echo urlencode( get_option('flexify_checkout_temp_license_key') ); ?>&app_version=<?php echo urlencode( FLEXIFY_CHECKOUT_VERSION ); ?>&product_id=3&settings_page=<?php echo urlencode( Flexify_Checkout_Api::get_domain() . '/wp-admin/admin.php?page=flexify-checkout-for-woocommerce' ); ?>" target="_blank"><?php echo esc_html__( 'Clique aqui para gerar seu arquivo de licença', 'flexify-checkout-for-woocommerce' ) ?></a>

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
			<?php
		} else {
			?>
			<tr>
				<td class="d-grid">
					<a class="btn btn-primary my-4 d-inline-flex w-fit" href="https://meumouse.com/plugins/flexify-checkout-para-woocommerce/?utm_source=wordpress&utm_medium=plugins-list&utm_campaign=flexify_checkout" target="_blank">
						<svg class="flexify-license-key-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/><g> <path d="M12.3212 10.6852L4 19L6 21M7 16L9 18M20 7.5C20 9.98528 17.9853 12 15.5 12C13.0147 12 11 9.98528 11 7.5C11 5.01472 13.0147 3 15.5 3C17.9853 3 20 5.01472 20 7.5Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/> </g></svg>
						<span><?php _e(  'Comprar licença', 'flexify-checkout-for-woocommerce' );?></span>
					</a>
					<span class="bg-translucent-success fw-medium rounded-2 px-3 py-2 mb-4 d-flex align-items-center w-fit">
						<svg class="icon icon-success me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>
						<?php echo esc_html__( 'Informe sua licença abaixo para desbloquear todos os recursos.', 'flexify-checkout-for-woocommerce' ) ?>
					</span>
					<span class="form-label d-block mt-2"><?php echo esc_html__( 'Código da licença', 'flexify-checkout-for-woocommerce' ) ?></span>
					<div class="input-group" style="width: 550px;">
						<input class="form-control" type="text" placeholder="XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX" id="flexify_checkout_license_key" name="flexify_checkout_license_key" size="50" value="<?php echo get_option( 'flexify_checkout_license_key' ) ?>" />
						<button id="flexify_checkout_active_license" name="flexify_checkout_active_license" class="btn btn-primary button-loading" type="submit">
							<span class="span-inside-button-loader"><?php esc_attr_e( 'Ativar licença', 'flexify-checkout-for-woocommerce' ); ?></span>
						</button>
					</div>
				</td>
			</tr>
			<?php
		}
	}
	?>
	
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
						<?php
						$license_status = ( self::license_valid() ) ? __( 'Pro', 'flexify-checkout-for-woocommerce' ) : '';

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
						<?php if ( ! class_exists( 'DOMDocument' ) ) : ?>
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
						<?php if ( !extension_loaded('curl') ) : ?>
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
			<?php
			if ( self::get_setting( 'enable_inter_bank_pix_api') === 'yes' || self::get_setting( 'enable_inter_bank_ticket_api') === 'yes' ) {
				?>
				<div class="d-flex mb-2">
					<span><?php esc_html_e( 'Extensão GD:', 'flexify-checkout-for-woocommerce' ); ?></span>
					<span class="ms-2">
						<span>
							<?php if ( !extension_loaded('gd') ) : ?>
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
				<?php
			}
			?>
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
			<?php if ( function_exists( 'ini_get' ) ) : ?>
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
		<tr>
			<td class="d-flex">
				<a class="btn btn-sm btn-outline-danger" target="_blank" href="https://meumouse.com/reportar-problemas/"><?php esc_html_e( 'Reportar problemas', 'flexify-checkout-for-woocommerce' ); ?></a>
				<button class="btn btn-sm btn-outline-primary ms-2 button-loading" name="flexify_checkout_clear_activation_cache"><?php esc_html_e( 'Limpar cache de ativação', 'flexify-checkout-for-woocommerce' ); ?></button>
			</td>
		</tr>
	</tr>
  </table>
</div>