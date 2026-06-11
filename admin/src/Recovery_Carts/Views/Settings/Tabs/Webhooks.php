<?php
/**
 * Tab file for webhooks on settings page
 *
 * @since 1.3.2
 */

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Webhooks;

// Exit if accessed directly.
defined('ABSPATH') || exit;

$webhook_settings = Admin::get_setting('webhooks');

if ( ! is_array( $webhook_settings ) ) {
    $webhook_settings = array();
}

$webhook_events = Webhooks::get_registered_events(); ?>

<div id="webhooks" class="nav-content">
    <div class="ps-5">
        <div class="mb-4">
            <h3 class="mb-1"><?php esc_html_e( 'Webhooks', 'fc-recovery-carts' ); ?></h3>
            <span class="fc-recovery-carts-description mb-0 d-block"><?php esc_html_e( 'Envie dados dos eventos do plugin para serviços externos através de requisições HTTP com suporte a cabeçalhos personalizados.', 'fc-recovery-carts' ); ?></span>
        </div>

        <ul class="list-group fcrc-webhooks-events-list mb-4 w-fit">
            <?php foreach ( $webhook_events as $event_key => $event_data ) :
                $event_webhooks = $webhook_settings[ $event_key ] ?? array();

                if ( ! is_array( $event_webhooks ) ) {
                    $event_webhooks = array();
                }

                $next_index = count( $event_webhooks );
                $webhook_count = count( $event_webhooks );
                $count_text = sprintf( _n( '%d webhook configurado', '%d webhooks configurados', $webhook_count, 'fc-recovery-carts' ), $webhook_count ); ?>

                <li class="list-group-item px-4 py-3 fcrc-webhook-event" data-event-key="<?php echo esc_attr( $event_key ); ?>">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center w-100">
                        <div class="text-center text-lg-start mb-3 mb-lg-0">
                            <span class="fw-semibold d-block fs-6"><?php echo esc_html( $event_data['label'] ?? ucfirst( $event_key ) ); ?></span>

                            <?php if ( ! empty( $event_data['description'] ) ) : ?>
                                <span class="fc-recovery-carts-description mb-0"><?php echo esc_html( $event_data['description'] ); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 ms-5">
                            <span class="badge bg-primary text-white rounded-pill px-3 py-2 fcrc-webhook-count" data-count-singular="<?php echo esc_attr__( '%d webhook configurado', 'fc-recovery-carts' ); ?>" data-count-plural="<?php echo esc_attr__( '%d webhooks configurados', 'fc-recovery-carts' ); ?>" data-event="<?php echo esc_attr( $event_key ); ?>"><?php echo esc_html( $count_text ); ?></span>

                            <button type="button" class="btn btn-outline-primary fcrc-configure-webhook" data-event="<?php echo esc_attr( $event_key ); ?>" data-target="#fcrc_webhook_modal_<?php echo esc_attr( $event_key ); ?>">
                                <?php esc_html_e( 'Configurar', 'fc-recovery-carts' ); ?>
                            </button>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php foreach ( $webhook_events as $event_key => $event_data ) :
            $event_webhooks = $webhook_settings[ $event_key ] ?? array();

            if ( ! is_array( $event_webhooks ) ) {
                $event_webhooks = array();
            }

            $next_index = count( $event_webhooks ); ?>

            <div id="fcrc_webhook_modal_<?php echo esc_attr( $event_key ); ?>" class="fcrc-popup-container fcrc-webhook-modal" data-event-key="<?php echo esc_attr( $event_key ); ?>">
                <div class="fcrc-popup-content popup-lg">
                    <div class="fcrc-popup-header">
                        <h5 class="fcrc-popup-title mb-0"><?php echo esc_html( $event_data['label'] ?? ucfirst( $event_key ) ); ?></h5>
                        <button type="button" class="btn-close fs-5 fcrc-webhook-modal-close" aria-label="<?php esc_attr_e( 'Fechar', 'fc-recovery-carts' ); ?>"></button>
                    </div>

                    <div class="fcrc-popup-body">
                        <?php if ( ! empty( $event_data['description'] ) ) : ?>
                            <p class="fc-recovery-carts-description mb-4"><?php echo esc_html( $event_data['description'] ); ?></p>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center flex-column flex-lg-row gap-3 mb-4">
                            <span class="fw-semibold text-center text-lg-start"><?php esc_html_e( 'Gerencie os webhooks cadastrados para este evento.', 'fc-recovery-carts' ); ?></span>

                            <button type="button" class="btn btn-outline-primary btn-sm fcrc-add-webhook" data-event="<?php echo esc_attr( $event_key ); ?>">
                                <?php esc_html_e( 'Adicionar webhook', 'fc-recovery-carts' ); ?>
                            </button>
                        </div>

                        <div class="alert alert-info fcrc-webhook-empty<?php echo ! empty( $event_webhooks ) ? ' d-none' : ''; ?>">
                            <?php esc_html_e( 'Nenhum webhook configurado para este evento ainda.', 'fc-recovery-carts' ); ?>
                        </div>

                        <div class="fcrc-webhook-list" data-event="<?php echo esc_attr( $event_key ); ?>" data-next-index="<?php echo esc_attr( $next_index ); ?>">
                            <?php foreach ( $event_webhooks as $index => $webhook ) :
                                $enabled = $webhook['enabled'] ?? 'yes';
                                $name = $webhook['name'] ?? '';
                                $url = $webhook['url'] ?? '';
                                $headers = isset( $webhook['headers'] ) && is_array( $webhook['headers'] ) ? $webhook['headers'] : array();
                                $next_header_index = count( $headers ); ?>

                                <div class="card mb-4 mw-100 fcrc-webhook-item" data-index="<?php echo esc_attr( $index ); ?>">
                                    <div class="card-body w-100 p-3">
                                        <div class="row g-3 align-items-center mb-4">
                                            <div class="col-lg-8">
                                                <label class="form-label text-left"><?php esc_html_e( 'Nome interno', 'fc-recovery-carts' ); ?></label>
                                                <input type="text" class="form-control" name="webhooks[<?php echo esc_attr( $event_key ); ?>][<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php esc_attr_e( 'Identificação opcional para o webhook', 'fc-recovery-carts' ); ?>">
                                            </div>
                                            <div class="col-lg-4 d-flex align-items-center justify-content-lg-end">
                                                <label class="form-label text-left me-3 mb-0"><?php esc_html_e( 'Ativar webhook', 'fc-recovery-carts' ); ?></label>
                                                <input type="checkbox" class="toggle-switch" name="webhooks[<?php echo esc_attr( $event_key ); ?>][<?php echo esc_attr( $index ); ?>][enabled]" value="yes" <?php checked( $enabled === 'yes' ); ?> />
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label text-left"><?php esc_html_e( 'URL de entrega *', 'fc-recovery-carts' ); ?></label>
                                            <input type="url" class="form-control" name="webhooks[<?php echo esc_attr( $event_key ); ?>][<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_attr( $url ); ?>" placeholder="<?php esc_attr_e( 'https://exemplo.com/webhook', 'fc-recovery-carts' ); ?>">
                                        </div>

                                        <div class="fcrc-webhook-headers-wrapper">
                                            <div class="alert alert-secondary fcrc-no-headers<?php echo ! empty( $headers ) ? ' d-none' : ''; ?>">
                                                <?php esc_html_e( 'Nenhum cabeçalho personalizado adicionado.', 'fc-recovery-carts' ); ?>
                                            </div>

                                            <div class="fcrc-webhook-headers" data-event="<?php echo esc_attr( $event_key ); ?>" data-index="<?php echo esc_attr( $index ); ?>" data-next-header-index="<?php echo esc_attr( $next_header_index ); ?>">
                                                <?php foreach ( $headers as $header_index => $header ) :
                                                    $header_name = $header['name'] ?? '';
                                                    $header_value = $header['value'] ?? ''; ?>

                                                    <div class="row g-3 align-items-end mb-3 fcrc-webhook-header-row" data-header-index="<?php echo esc_attr( $header_index ); ?>">
                                                        <div class="col-md-5">
                                                            <label class="form-label text-left"><?php esc_html_e( 'Chave do cabeçalho', 'fc-recovery-carts' ); ?></label>
                                                            <input type="text" class="form-control" name="webhooks[<?php echo esc_attr( $event_key ); ?>][<?php echo esc_attr( $index ); ?>][headers][<?php echo esc_attr( $header_index ); ?>][name]" value="<?php echo esc_attr( $header_name ); ?>" placeholder="<?php esc_attr_e( 'Ex: Authorization', 'fc-recovery-carts' ); ?>">
                                                        </div>
                                                        <div class="col-md-5">
                                                            <label class="form-label text-left"><?php esc_html_e( 'Valor do cabeçalho', 'fc-recovery-carts' ); ?></label>
                                                            <input type="text" class="form-control" name="webhooks[<?php echo esc_attr( $event_key ); ?>][<?php echo esc_attr( $index ); ?>][headers][<?php echo esc_attr( $header_index ); ?>][value]" value="<?php echo esc_attr( $header_value ); ?>" placeholder="<?php esc_attr_e( 'Ex: Bearer 123456', 'fc-recovery-carts' ); ?>">
                                                        </div>
                                                        <div class="col-md-2 text-md-end">
                                                            <button type="button" class="btn btn-outline-danger btn-sm fcrc-remove-webhook-header">
                                                                <?php esc_html_e( 'Remover', 'fc-recovery-carts' ); ?>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <button type="button" class="btn btn-outline-secondary btn-sm mt-3 fcrc-add-webhook-header" data-event="<?php echo esc_attr( $event_key ); ?>" data-index="<?php echo esc_attr( $index ); ?>">
                                                <?php esc_html_e( 'Adicionar cabeçalho', 'fc-recovery-carts' ); ?>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="card-footer d-flex justify-content-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm fcrc-remove-webhook">
                                            <?php esc_html_e( 'Remover webhook', 'fc-recovery-carts' ); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script type="text/template" id="fcrc-webhook-template">
        <div class="card mb-4 mw-100 fcrc-webhook-item" data-index="__index__">
            <div class="card-body w-100">
                <div class="row g-3 align-items-center mb-4">
                    <div class="col-lg-8">
                        <label class="form-label text-left"><?php echo esc_html__( 'Nome interno', 'fc-recovery-carts' ); ?></label>
                        <input type="text" class="form-control" name="webhooks[__event__][__index__][name]" placeholder="<?php echo esc_attr__( 'Identificação opcional para o webhook', 'fc-recovery-carts' ); ?>">
                    </div>
                    <div class="col-lg-4 d-flex align-items-center justify-content-lg-end">
                        <label class="form-label text-left me-3 mb-0"><?php echo esc_html__( 'Ativar webhook', 'fc-recovery-carts' ); ?></label>
                        <input type="checkbox" class="toggle-switch" name="webhooks[__event__][__index__][enabled]" value="yes" checked />
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-left"><?php echo esc_html__( 'URL de entrega *', 'fc-recovery-carts' ); ?></label>
                    <input type="url" class="form-control" name="webhooks[__event__][__index__][url]" placeholder="<?php echo esc_attr__( 'https://exemplo.com/webhook', 'fc-recovery-carts' ); ?>">
                </div>

                <div class="fcrc-webhook-headers-wrapper">
                    <div class="alert alert-secondary fcrc-no-headers">
                        <?php echo esc_html__( 'Nenhum cabeçalho personalizado adicionado.', 'fc-recovery-carts' ); ?>
                    </div>

                    <div class="fcrc-webhook-headers" data-event="__event__" data-index="__index__" data-next-header-index="0"></div>

                    <button type="button" class="btn btn-outline-secondary btn-sm mt-3 fcrc-add-webhook-header" data-event="__event__" data-index="__index__">
                        <?php echo esc_html__( 'Adicionar cabeçalho', 'fc-recovery-carts' ); ?>
                    </button>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end">
                <button type="button" class="btn btn-outline-danger btn-sm fcrc-remove-webhook">
                    <?php echo esc_html__( 'Remover webhook', 'fc-recovery-carts' ); ?>
                </button>
            </div>
        </div>
    </script>

    <script type="text/template" id="fcrc-webhook-header-template">
        <div class="row g-3 align-items-end mb-3 fcrc-webhook-header-row" data-header-index="__header_index__">
            <div class="col-md-5">
                <label class="form-label text-left"><?php echo esc_html__( 'Chave do cabeçalho', 'fc-recovery-carts' ); ?></label>
                <input type="text" class="form-control" name="webhooks[__event__][__index__][headers][__header_index__][name]" placeholder="<?php echo esc_attr__( 'Ex: Authorization', 'fc-recovery-carts' ); ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label text-left"><?php echo esc_html__( 'Valor do cabeçalho', 'fc-recovery-carts' ); ?></label>
                <input type="text" class="form-control" name="webhooks[__event__][__index__][headers][__header_index__][value]" placeholder="<?php echo esc_attr__( 'Ex: Bearer 123456', 'fc-recovery-carts' ); ?>">
            </div>
            <div class="col-md-2 text-md-end">
                <button type="button" class="btn btn-outline-danger btn-sm fcrc-remove-webhook-header">
                    <?php echo esc_html__( 'Remover', 'fc-recovery-carts' ); ?>
                </button>
            </div>
        </div>
    </script>
</div>