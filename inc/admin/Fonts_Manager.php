<?php

namespace MeuMouse\Flexify_Checkout\Admin;

use WP_Error;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Manage custom fonts registered in the plugin settings.
 * 
 * @since 5.3.0
 */
class Fonts_Manager {

	/**
	 * Built-in fonts delivered with the plugin.
	 * 
	 * @since 5.3.0
	 * @return string[]
	 */
	public static function builtin_fonts() {
		return array(
			'inter',
			'poppins',
			'montserrat',
			'open_sans',
			'rubik',
			'roboto',
			'lato',
			'raleway',
			'nunito',
			'quicksand',
			'urbanist',
		);
	}


	/**
	 * Default font ID used by the plugin
	 * 
	 * @since 5.3.0
	 * @return string
	 */
	public static function default_font_id() {
		return apply_filters( 'Flexify_Checkout/Set_Default_Font_ID', 'inter' );
	}


	/**
	 * Retrieve all fonts registered in the settings and ensure they are normalized.
	 * 
	 * @since 5.3.0
	 * @return array
	 */
	public static function get_fonts() {
		$options = get_option( 'flexify_checkout_settings', array() );
		$fonts = isset( $options['font_family'] ) && is_array( $options['font_family'] ) ? $options['font_family'] : array();
		$normalized = self::normalize_fonts( $fonts );

		if ( $normalized['changed'] ) {
			$options['font_family'] = $normalized['fonts'];
			update_option( 'flexify_checkout_settings', $options );
		}

		return $normalized['fonts'];
	}


	/**
	 * Save (create/update) a font configuration
	 * 
	 * @since 5.3.0
	 * @param string $font_id | Font identifier
	 * @param array  $font_data | Font configuration data
	 * @return bool
	 */
	public static function save_font( $font_id, $font_data ) {
		$options = get_option( 'flexify_checkout_settings', array() );

		if ( ! isset( $options['font_family'] ) || ! is_array( $options['font_family'] ) ) {
			$options['font_family'] = array();
		}

		$options['font_family'][ $font_id ] = $font_data;

		return update_option( 'flexify_checkout_settings', $options );
	}


	/**
	 * Delete a font configuration and remove uploaded assets.
	 * 
	 * @since 5.3.0
	 * @param string $font_id | Font identifier.
	 * @return bool
	 */
	public static function delete_font( $font_id ) {
		$options = get_option( 'flexify_checkout_settings', array() );

		if ( empty( $options['font_family'][ $font_id ] ) ) {
			return false;
		}

		$font = $options['font_family'][ $font_id ];

		if ( isset( $font['type'] ) && 'upload' === $font['type'] && ! empty( $font['font_files'] ) && is_array( $font['font_files'] ) ) {
			foreach ( $font['font_files'] as $file_url ) {
				self::delete_file_by_url( $file_url );
			}
		}

		unset( $options['font_family'][ $font_id ] );

		return update_option( 'flexify_checkout_settings', $options );
	}


	/**
	 * Determine whether a font is bundled with the plugin.
	 * 
	 * @since 5.3.0
	 * @param string $font_id Font identifier.
	 * @return bool
	 */
	public static function is_builtin_font( $font_id ) {
		return in_array( $font_id, self::builtin_fonts(), true );
	}


	/**
	 * Normalize fonts structure with defaults.
	 * 
	 * @since 5.3.0
	 * @param array $fonts Fonts array to normalize.
	 * @return array{fonts:array,changed:bool}
	 */
	protected static function normalize_fonts( $fonts ) {
		$changed = false;

		if ( ! is_array( $fonts ) ) {
			return array(
				'fonts'   => array(),
				'changed' => false,
			);
		}

		foreach ( $fonts as $font_id => &$font ) {
			if ( ! is_array( $font ) ) {
				$font = array();
				$changed = true;
			}

			if ( empty( $font['font_name'] ) ) {
				$font['font_name'] = ucwords( str_replace( array( '-', '_' ), ' ', (string) $font_id ) );
				$changed = true;
			}

			if ( empty( $font['type'] ) ) {
				$font['type'] = ! empty( $font['font_url'] ) ? 'google' : 'upload';
				$changed = true;
			}

			if ( empty( $font['source'] ) ) {
				$font['source'] = self::is_builtin_font( (string) $font_id ) ? 'default' : 'custom';
				$changed = true;
			}

			if ( 'upload' === $font['type'] ) {
				if ( empty( $font['font_style'] ) ) {
					$font['font_style'] = 'normal';
					$changed = true;
				}

				if ( empty( $font['font_weight'] ) ) {
					$font['font_weight'] = '400';
					$changed = true;
				}

				if ( empty( $font['font_files'] ) || ! is_array( $font['font_files'] ) ) {
					$font['font_files'] = array();
					$changed = true;
				}
			}
		}

		unset( $font );

		return array(
			'fonts' => $fonts,
			'changed' => $changed,
		);
	}


	/**
	 * Ensure the upload directory exists.
	 * 
	 * @since 5.3.0
	 * @return array|WP_Error
	 */
	protected static function ensure_upload_dir() {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			return new WP_Error( 'font_upload_dir_error', $upload_dir['error'] );
		}

		$fonts_path = trailingslashit( $upload_dir['basedir'] ) . 'flexify-checkout/fonts/';
		$fonts_url = trailingslashit( $upload_dir['baseurl'] ) . 'flexify-checkout/fonts/';

		if ( ! wp_mkdir_p( $fonts_path ) ) {
			return new WP_Error( 'font_upload_dir_error', __( 'Não foi possível criar a pasta para armazenar a fonte.', 'flexify-checkout-for-woocommerce' ) );
		}

		return array(
			'path' => $fonts_path,
			'url' => $fonts_url,
		);
	}


	/**
	 * Handle the upload of a font file.
	 * 
	 * @since 5.3.0
	 * @param array  $file | File array from $_FILES.
	 * @param string $font_id | Font identifier.
	 * @param string $weight | Font weight.
	 * @param string $style | Font style.
	 * @param string $format | Expected format (woff|woff2|ttf|otf).
	 * @return string|WP_Error URL of the stored file or WP_Error on failure.
	 */
	public static function handle_font_upload( $file, $font_id, $weight, $style, $format ) {
		if ( empty( $file['name'] ) ) {
			return new WP_Error( 'font_upload_missing', __( 'Nenhum arquivo de fonte foi enviado.', 'flexify-checkout-for-woocommerce' ) );
		}

		if ( ! isset( $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new WP_Error( 'font_upload_error', __( 'Não foi possível enviar o arquivo da fonte.', 'flexify-checkout-for-woocommerce' ) );
		}

		$allowed = self::allowed_mimes();

		if ( ! isset( $allowed[ $format ] ) ) {
			return new WP_Error( 'font_upload_invalid_format', __( 'Formato de fonte não suportado.', 'flexify-checkout-for-woocommerce' ) );
		}

		$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed );

		if ( empty( $checked['ext'] ) || $format !== $checked['ext'] ) {
			return new WP_Error( 'font_upload_invalid_format', __( 'Formato de fonte não suportado.', 'flexify-checkout-for-woocommerce' ) );
		}

		$upload_dir = self::ensure_upload_dir();

		if ( is_wp_error( $upload_dir ) ) {
			return $upload_dir;
		}

		$upload = wp_handle_upload( $file, array(
			'test_form' => false,
			'mimes' => $allowed,
		));

		if ( isset( $upload['error'] ) ) {
			return new WP_Error( 'font_upload_error', $upload['error'] );
		}

		$filename = sanitize_file_name( $font_id . '-' . $weight . '-' . $style . '.' . $format );
		$destination = trailingslashit( $upload_dir['path'] ) . $filename;

		if ( ! @rename( $upload['file'], $destination ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( isset( $upload['file'] ) ) {
				wp_delete_file( $upload['file'] );
			}

			return new WP_Error( 'font_upload_error', __( 'Não foi possível mover o arquivo de fonte enviado.', 'flexify-checkout-for-woocommerce' ) );
		}

		return $upload_dir['url'] . $filename;
	}


	/**
	 * Delete a file stored by URL if it belongs to the uploads directory.
	 * 
	 * @since 5.3.0
	 * @param string $file_url File URL.
	 * @return void
	 */
	public static function delete_file_by_url( $file_url ) {
		if ( empty( $file_url ) ) {
			return;
		}

		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			return;
		}

		$baseurl = trailingslashit( $upload_dir['baseurl'] );

		if ( strpos( $file_url, $baseurl ) !== 0 ) {
			return;
		}

		$relative_path = substr( $file_url, strlen( $baseurl ) );
		$file_path = trailingslashit( $upload_dir['basedir'] ) . $relative_path;

		if ( file_exists( $file_path ) ) {
			@unlink( $file_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}


	/**
	 * Update selected font if the current option references a removed font.
	 * 
	 * @since 5.3.0
	 * @param string $removed_font Font identifier removed.
	 * @return void
	 */
	public static function maybe_reset_selected_font( $removed_font ) {
		$current = Admin_Options::get_setting( 'set_font_family' );

		if ( $current !== $removed_font ) {
			return;
		}

		$fonts = self::get_fonts();
		$fallback = self::default_font_id();
		$new_option = $fallback;

		if ( empty( $fonts[ $fallback ] ) ) {
			$keys = array_keys( $fonts );

			if ( ! empty( $keys ) ) {
				$new_option = $keys[0];
			}
		}

		$options = get_option( 'flexify_checkout_settings', array() );
		$options['set_font_family'] = $new_option;
		update_option( 'flexify_checkout_settings', $options );
	}
    

	/**
	 * Allowed mime types for font upload.
	 * 
	 * @since 5.3.0
	 * @return array
	 */
	public static function allowed_mimes() {
		return array(
			'woff' => 'font/woff',
			'woff2' => 'font/woff2',
			'ttf' => 'font/ttf',
			'otf' => 'font/otf',
		);
	}
}