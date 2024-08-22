<?php

namespace MeuMouse\Flexify_Checkout;

use MeuMouse\Flexify_Checkout\Init;
use MeuMouse\Flexify_Checkout\Helpers;
use MeuMouse\Flexify_Checkout\License;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Class to handle plugin admin panel objects and functions
 * 
 * @since 1.0.0
 * @version 3.8.0
 * @package MeuMouse.com
 */
class Admin_Options extends Init {

  /**
   * Admin constructor
   *
   * @since 1.0.0
   * @version 3.8.0
   * @package MeuMouse.com
   */
  public function __construct() {
    parent::__construct();

    // add submenu on WooCommerce
    add_action( 'admin_menu', array( $this, 'add_woo_submenu' ) );

    // handle for billing country admin notice
    add_action( 'woocommerce_checkout_init', array( __CLASS__, 'check_billing_country_field' ) );
    add_action( 'admin_notices', array( __CLASS__, 'show_billing_country_warning' ) );
    add_action( 'admin_footer', array( __CLASS__, 'dismiss_billing_country_warning_script' ) );

    // display notice when not has [woocommerce_checkout] shortcode
    add_action( 'admin_notices', array( __CLASS__, 'check_for_checkout_shortcode' ) );

    // display notice when not has PHP gd extension
    add_action( 'admin_notices', array( __CLASS__, 'missing_gd_extension_notice' ) );
  }
  

  /**
   * Function for create submenu in WooCommerce
   * 
   * @since 1.0.0
   * @version 3.8.0
   * @return array
   */
  public function add_woo_submenu() {
    add_submenu_page(
      'woocommerce', // parent page slug
      esc_html__( 'Flexify Checkout para WooCommerce', 'flexify-checkout-for-woocommerce' ), // page title
      esc_html__( 'Flexify Checkout', 'flexify-checkout-for-woocommerce' ), // submenu title
      'manage_woocommerce', // user capabilities
      'flexify-checkout-for-woocommerce', // page slug
      array( $this, 'render_settings_page' ), // public function for print content page
    );
  }


  /**
   * Plugin general setting page and save options
   * 
   * @since 1.0.0
   * @return void
   */
  public function render_settings_page() {
    include_once FLEXIFY_CHECKOUT_PATH . 'inc/admin/settings.php';
  }


  /**
   * Check if billing country is disabled on checkout
   * 
   * @since 3.7.3
   * @return void
   */
  public static function check_billing_country_field() {
    $checkout_fields = WC()->checkout()->get_checkout_fields();
    $is_disabled = empty( $checkout_fields['billing']['billing_country'] ) || $checkout_fields['billing']['billing_country']['required'] === false;

    update_option( 'billing_country_field_disabled', $is_disabled );
  }


  /**
  * Display admin notice when billing country field is disabled
  * 
  * @since 3.7.3
  * @return void
  */
  public static function show_billing_country_warning() {
    $is_disabled = get_option('billing_country_field_disabled');
    $hide_notice = get_user_meta( get_current_user_id(), 'hide_billing_country_notice', true );

    if ( $is_disabled && ! $hide_notice ) {
        $class = 'notice notice-error is-dismissible';
        $message = esc_html__( 'O campo País na finalização de compras está desativado, verifique se seu gateway de pagamentos depende deste campo para não receber o erro "Informe um endereço para continuar com sua compra."', 'flexify-checkout-for-woocommerce' );
        
        printf( '<div id="billing-country-warning" class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
    }
  }


  /**
   * Send action on dismiss notice for not display
   * 
   * @since 3.7.3
   * @return void
   */
  public static function dismiss_billing_country_warning_script() {
    ?>
    <script type="text/javascript">
        jQuery(document).on('click', '#billing-country-warning .notice-dismiss', function() {
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dismiss_billing_country_warning',
                }
            });
        });
    </script>
    <?php
  }


  /**
	 * Display error message on WooCommerce checkout page if shortcode is missing
	 * 
	 * @since 4.5.0
	 * @return void
	 */
	public static function check_for_checkout_shortcode() {
		if ( ! Helpers::has_shortcode_checkout() ) {
			$class = 'notice notice-error is-dismissible';
			$message = __( 'O Flexify Checkout depende do shortcode [woocommerce_checkout] na página de finalização de compras para funcionar corretamente.', 'flexify-checkout-for-woocommerce' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
		}
	}
  

  /**
	 * Display error message when PHP extensionn gd is missing
	 * 
	 * @since 4.5.0
	 * @return void
	 */
	public static function missing_gd_extension_notice() {
		if ( ! extension_loaded('gd') && Init::get_setting('enable_inter_bank_pix_api') === 'yes' ) {
			$class = 'notice notice-error is-dismissible';
			$message = __( 'A extensão GD está desativada, e é necessária para gerar o QR Code do Pix. Ative-a em sua hospedagem para habilitar esse recurso.', 'flexify-checkout-for-woocommerce' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
		}
	}


  /**
   * Render each checkout field for panel settings
   * 
   * @since 3.8.0
   * @param string $index | Field ID
   * @param array $value | Field values (ID, type, label, class, etc)
   * @param string $step | Step for render field (1 or 2)
   * @return void
   */
  public static function render_field( $index, $value, $step ) {
    // Checks if the field belongs to the correct step
    if ( isset( $value['step'] ) && $value['step'] !== $step ) {
        return;
    }

    $current_field_step_position = $value['position'] ?? 'full';
    $current_field_step_label = $value['label'] ?? '';
    $current_field_step_classes = $value['classes'] ?? '';
    $current_field_step_label_classes = $value['label_classes'] ?? '';
    $current_field_step_country = $value['country'] ?? 'none';
    $current_field_step_input_mask = $value['input_mask'] ?? ''; ?>

    <div id="<?php echo esc_attr( $index ); ?>" class="field-item d-flex align-items-center justify-content-between <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; echo $value['enabled'] === 'no' ? 'inactive' : ''; ?>">
        <input type="hidden" class="change-priority" name="checkout_step[<?php echo $index; ?>][priority]" value="<?php echo esc_attr( $value['priority'] ?? '' ); ?>">
        <input type="hidden" class="change-step" name="checkout_step[<?php echo $index; ?>][step]" value="<?php echo esc_attr( $value['step'] ?? '' ); ?>">

        <span class="field-name"><?php echo esc_html( $value['label'] ); ?></span>

        <div class="d-flex justify-content-end">
            <button class="btn btn-sm btn-outline-primary ms-auto rounded-3 <?php echo ( ! License::is_valid() ) ? 'require-pro' : 'flexify-checkout-step-trigger'; ?>" data-trigger="<?php echo esc_html($index); ?>">
                <?php echo esc_html__('Editar', 'flexify-checkout-for-woocommerce'); ?>
            </button>

            <?php if ( isset( $value['source'] ) && $value['source'] !== 'native' ) : ?>
                <button class="btn btn-outline-danger btn-icon ms-3 rounded-3 exclude-field" data-exclude="<?php echo esc_html( $index ); ?>">
                    <svg class="icon icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
                </button>
            <?php endif; ?>
        </div>

        <div class="flexify-checkout-step-container popup-container">
            <div class="popup-content popup-lg">
                <div class="popup-header">
                    <h5 class="popup-title"><?php echo sprintf( __('Configurar campo <strong class="field-name">%s</strong>', 'flexify-checkout-for-woocommerce'), esc_html( $value['label'] ) ); ?></h5>
                    <button class="flexify-checkout-step-close-popup btn-close fs-lg" aria-label="<?php esc_html__('Fechar', 'flexify-checkout-for-woocommerce'); ?>"></button>
                </div>
                <div class="popup-body">
                    <table class="form-table">
                        <?php self::render_field_options( $index, $value ); ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php
  }


  /**
  * Render checkout field options for settings panel
  * 
  * @since 3.8.0
  * @param string $index | Field ID
  * @param array $value | Field configuration array
  * @return void
  */
  public static function render_field_options( $index, $value ) {
    $field_type = $value['type'] ?? 'text'; ?>

    <tr>
        <th class="w-50"><?php echo esc_html__('Ativar/Desativar este campo', 'flexify-checkout-for-woocommerce'); ?>
            <span class="flexify-checkout-description"><?php echo esc_html__('Este é um campo nativo do WooCommerce e não pode ser removido, apenas desativado.', 'flexify-checkout-for-woocommerce'); ?></span>
        </th>
        <td class="w-50">
            <div class="form-check form-switch">
                <input type="checkbox" class="toggle-switch toggle-active-field" name="checkout_step[<?php echo esc_attr( $index ); ?>][enabled]" value="yes" <?php checked( $value['enabled'] === 'yes' ); ?> />
            </div>
        </td>
    </tr>

    <tr>
        <th class="w-50"><?php echo esc_html__('Obrigatoriedade do campo', 'flexify-checkout-for-woocommerce'); ?>
            <span class="flexify-checkout-description"><?php echo esc_html__('Ao desativar, este campo se tornará não obrigatório.', 'flexify-checkout-for-woocommerce'); ?></span>
        </th>
        <td class="w-50">
            <div class="form-check form-switch">
                <input type="checkbox" class="toggle-switch toggle-active-field" name="checkout_step[<?php echo esc_attr( $index ); ?>][required]" value="yes" <?php checked( $value['required'] === 'yes' ); ?> />
            </div>
        </td>
    </tr>
    
    <?php if ( $index === 'billing_country' && $field_type === 'select' ) : ?>
        <tr>
            <th class="w-50">
                <?php echo esc_html__('Definir país padrão', 'flexify-checkout-for-woocommerce'); ?>
                <span class="flexify-checkout-description"><?php echo esc_html__('Define a posição deste campo na finalização de compras.', 'flexify-checkout-for-woocommerce'); ?></span>
            </th>
            <td class="w-50">
                <select class="form-select" name="checkout_step[<?php echo esc_attr( $index ); ?>][country]">
                    <?php include_once FLEXIFY_CHECKOUT_INC_PATH . 'admin/tabs/parts/iso3166.php';

                    foreach ( $country_codes as $code_index => $code_value ) : ?>
                        <option value="<?php echo esc_attr( $code_index ); ?>" <?php echo $value['country'] === esc_attr( $code_index ) ? "selected=selected" : ""; ?>><?php echo esc_html( $code_value ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    <?php endif; ?>

    <tr>
        <th class="w-50">
            <?php echo esc_html__( 'Nome do campo', 'flexify-checkout-for-woocommerce' ) ?>
            <span class="flexify-checkout-description"><?php echo esc_html__('Define o título que será exibido para este campo.', 'flexify-checkout-for-woocommerce' ) ?></span>
        </th>
        <td class="w-50">
            <input type="text" class="get-name-field form-control" name="checkout_step[<?php echo $index; ?>][label]" value="<?php echo esc_attr( $value['label'] ?? '' ); ?>"/>
        </td>
    </tr>

    <?php if ( $index !== 'billing_country' && $field_type === 'select' && isset( $value['options'] ) ) : ?>
      <tr class="d-flex align-items-start">
        <th class="w-50">
            <?php echo esc_html__('Opções ', 'flexify-checkout-for-woocommerce'); ?>
            <span class="flexify-checkout-description"><?php echo esc_html__('Define a posição deste campo na finalização de compras.', 'flexify-checkout-for-woocommerce'); ?></span>
        </th>
        <td class="w-50">
          <div class="d-grid">
            <div class="mb-3 options-container-live">
              <?php foreach ( $value['options'] as $option ) : ?>
                <div class="d-flex align-items-center mb-3 option-container-live" data-option="<?php echo esc_attr( $option['value'] ) ?>">
                  <div class="input-group me-3">
                    <span class="input-group-text d-flex align-items-center justify-content-center py-2 w-25"><?php echo esc_attr( $option['value'] ) ?></span>
                    <span class="input-group-text d-flex align-items-center justify-content-center w-75"><?php echo esc_html( $option['text'] ) ?></span>
                  </div>

                  <button class="btn btn-outline-danger btn-icon rounded-3 exclude-option-select-live" data-field-id="<?php echo esc_attr( $value['id'] ) ?>" data-option="<?php echo esc_attr( $option['value'] ) ?>">
                    <svg class="icon icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
                  </button>
                </div>
              <?php endforeach; ?>
            </div>

            <button id="add_new_select_option_live" class="btn btn-outline-secondary"><?php echo esc_html__('Adicionar nova opção', 'flexify-checkout-for-woocommerce'); ?></button>
          </div>
        </td>
      </tr>
    <?php endif; ?>

    <tr>
        <th class="w-50">
            <?php echo esc_html__('Posição do campo', 'flexify-checkout-for-woocommerce'); ?>
            <span class="flexify-checkout-description"><?php echo esc_html__('Define a posição deste campo na finalização de compras.', 'flexify-checkout-for-woocommerce'); ?></span>
        </th>
        <td class="w-50">
            <select class="form-select" name="checkout_step[<?php echo esc_attr( $index ); ?>][position]">
                <option value="left" <?php echo $value['position'] === 'left' ? "selected=selected" : ""; ?>><?php echo esc_html__('Esquerda', 'flexify-checkout-for-woocommerce'); ?></option>
                <option value="right" <?php echo $value['position'] === 'right' ? "selected=selected" : ""; ?>><?php echo esc_html__('Direita', 'flexify-checkout-for-woocommerce'); ?></option>
                <option value="full" <?php echo $value['position'] === 'full' ? "selected=selected" : ""; ?>><?php echo esc_html__('Largura completa', 'flexify-checkout-for-woocommerce'); ?></option>
            </select>
        </td>
    </tr>

    <tr>
        <th class="w-50">
            <?php echo esc_html__('Classe CSS personalizada do campo (Opcional)', 'flexify-checkout-for-woocommerce'); ?>
            <span class="flexify-checkout-description"><?php echo esc_html__('Informe a(s) classe(s) CSS personalizadas para este campo. (Opcional)', 'flexify-checkout-for-woocommerce'); ?></span>
        </th>
        <td class="w-50">
            <input type="text" class="form-control" name="checkout_step[<?php echo esc_attr( $index ); ?>][classes]" value="<?php echo esc_attr( $value['classes'] ?? '' ); ?>"/>
        </td>
    </tr>

    <tr>
        <th class="w-50">
            <?php echo esc_html__('Classe CSS personalizada do título (Opcional)', 'flexify-checkout-for-woocommerce'); ?>
            <span class="flexify-checkout-description"><?php echo esc_html__('Informe a(s) classe(s) CSS personalizadas para o título (label) deste campo. (Opcional)', 'flexify-checkout-for-woocommerce'); ?></span>
        </th>
        <td class="w-50">
            <input type="text" class="form-control" name="checkout_step[<?php echo esc_attr( $index ); ?>][label_classes]" value="<?php echo esc_attr( $value['label_classes'] ?? '' ); ?>"/>
        </td>
    </tr>

    <?php if ( isset( $value['input_mask'] ) ) : ?>
        <tr class="require-input-mask">
            <th class="w-50">
                <?php echo esc_html__('Máscara do campo (Opcional)', 'flexify-checkout-for-woocommerce'); ?>
                <span class="flexify-checkout-description"><?php echo esc_html__('Adicione uma máscara de preenchimento para este campo, seguindo o padrão informado pela documentação. (Opcional)', 'flexify-checkout-for-woocommerce'); ?></span>
            </th>
            <td class="w-50">
                <input type="text" class="form-control" name="checkout_step[<?php echo esc_attr( $index ); ?>][input_mask]" value="<?php echo esc_attr( $value['input_mask'] ?? '' ); ?>"/>
            </td>
        </tr>
    <?php endif;
  }


  /**
  * Render step container for settings panel
  * 
  * @since 3.8.0
  * @param string $step | Steps - 1 or 2
  * @param string $title | Title from step
  * @param array $fields | 
  */
  public static function render_step( $step, $title, $fields ) {
    ?>
    <td class="step-container">
        <span class="step-title"><?php echo $title; ?></span>

        <div id="flexify_checkout_step_<?php echo esc_attr( $step ); ?>" data-step="<?php echo esc_attr( $step ); ?>">
            <?php foreach ( $fields as $index => $value ) : 
                self::render_field( $index, $value, $step );
            endforeach; ?>
        </div>
    </td>
    <?php
  }
}

new Admin_Options();

if ( ! class_exists('MeuMouse\Flexify_Checkout\Admin_Options\Admin_Options') ) {
  class_alias( 'MeuMouse\Flexify_Checkout\Admin_Options', 'MeuMouse\Flexify_Checkout\Admin_Options\Admin_Options' );
}