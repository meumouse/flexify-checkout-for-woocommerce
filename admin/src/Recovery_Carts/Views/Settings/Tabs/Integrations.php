<?php

use MeuMouse\Flexify_Checkout\Recovery_Carts\Integrations\Integrations_Base;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;

/**
 * Template file for integration settings
 * 
 * @since 1.0.0
 * @package MeuMouse.com
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( ! function_exists( 'is_plugin_active' ) ) :
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
endif; ?>

<div id="integrations" class="nav-content">
    <div class="cards-group ps-5 mb-5">
        <?php foreach ( Integrations_Base::integration_tab_items() as $key => $value ) : ?>
            <?php
            // Check if item requires active plugin
            $is_plugin_active = true;

            if ( isset( $value['is_plugin'] ) && $value['is_plugin'] === true ) :
                $is_plugin_active = false;

                if ( ! empty( $value['plugin_active'] ) ) {
                    foreach ( (array) $value['plugin_active'] as $plugin ) {
                        if ( is_plugin_active( $plugin ) ) {
                            $is_plugin_active = true;

                            break;
                        }
                    }
                }
            endif; ?>

            <div class="card text-center p-0 m-xxl-4 m-lg-3 integration-item">
                <?php if ( isset( $value['comming_soon'] ) && $value['comming_soon'] === true ) : ?>
                    <div class="fcrc-comming-soon">
                        <?php esc_html_e( 'Em breve...', 'fc-recovery-carts' ); ?>
                    </div>
                <?php endif; ?>

                <div class="card-header border-bottom">
                    <div class="px-4 py-3">
                        <?php echo $value['icon']; ?>
                    </div>
                </div>
                
                <div class="card-body px-3 py-4 d-flex flex-column align-items-center text-center">
                    <h5 class="card-title"><?php echo esc_html( $value['title'] ); ?></h5>
                    <p class="card-text fs-sm mb-4"><?php echo esc_html( $value['description'] ); ?></p>

                    <?php if ( ! $is_plugin_active ) : ?>
                        <span class="alert alert-info mb-3"><?php esc_html_e( 'Este plugin precisa estar instalado e ativo para ativar esta integração.', 'fc-recovery-carts' ); ?></span>
                    <?php endif; ?>
                    
                    <div class="form-check form-switch w-100 d-flex justify-content-center">
                        <input type="checkbox" class="toggle-switch" id="<?php echo esc_attr( $value['toggle_switch_key'] ); ?>" name="toggle_switchs[<?php echo esc_attr( $value['toggle_switch_key'] ); ?>]" value="yes" <?php disabled( isset( $value['comming_soon'] ) && $value['comming_soon'] === true ); checked( Admin::get_switch( $value['toggle_switch_key'] ) === 'yes' ); ?> <?php echo ! $is_plugin_active ? 'disabled' : ''; ?> />
                    </div>
                </div>

                <?php
                /**
                 * Add hook for each integration service
                 * 
                 * @since 1.0.0
                 */
                do_action( $value['action_hook'] ); ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>