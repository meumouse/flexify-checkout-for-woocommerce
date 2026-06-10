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
		$ext = strtolower( (string) $format );
		$safe = sanitize_file_name( "{$font_id}-{$weight}-{$style}.{$ext}" );

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$mimes = array(
			'woff2' => 'font/woff2',
			'woff' => 'font/woff',
			'ttf' => 'font/ttf',
			'otf' => 'font/otf',
		);

		$overrides = array(
			'test_form' => false,
			'mimes' => $mimes,
			'test_type' => false,
			'unique_filename_callback' => function( $dir, $name, $ext2 ) use ( $safe ) {
				return $safe;
			},
		);

		$uploaded = wp_handle_upload( $file, $overrides );

		if ( isset( $uploaded['error'] ) ) {
			return new \WP_Error( 'upload_error', $uploaded['error'] );
		}

		$uploads = wp_upload_dir();
		$base_dir = trailingslashit( $uploads['basedir'] ) . 'flexify-checkout/fonts/';
		$base_url = trailingslashit( $uploads['baseurl'] ) . 'flexify-checkout/fonts/';

		if ( ! wp_mkdir_p( $base_dir ) ) {
			return new \WP_Error( 'mkdir_failed', __( 'Não foi possível criar o diretório de fontes.', 'flexify-checkout-for-woocommerce' ) );
		}

		$dest_path = $base_dir . $safe;

		if ( ! @rename( $uploaded['file'], $dest_path ) ) {
			if ( ! @copy( $uploaded['file'], $dest_path ) ) {
				return new \WP_Error( 'move_failed', __( 'Falha ao mover o arquivo de fonte.', 'flexify-checkout-for-woocommerce' ) );
			}
			
			@unlink( $uploaded['file'] );
		}

		return $base_url . $safe;
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