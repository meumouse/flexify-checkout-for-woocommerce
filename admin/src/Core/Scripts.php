<?php

namespace MeuMouse\Flexify_Checkout\Core;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Resolve Vite build assets through the build manifest.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Core
 * @author MeuMouse.com
 */
class Scripts {

    /**
     * Relative path to the Vite manifest.
     *
     * @since 6.0.0
     * @var string
     */
    const MANIFEST_PATH = 'app/dist/.vite/manifest.json';

    /**
     * Relative URL path to the Vite build output.
     *
     * @since 6.0.0
     * @var string
     */
    const DIST_URL_PATH = 'app/dist/';


    /**
     * Get all assets for a Vite entry source.
     *
     * @since 6.0.0
     * @param string $entry_source Relative entry source, e.g. src/entries/settings.js.
     * @return array<string,mixed> Script URL, style URLs and cache-busting version.
     */
    public static function get_entry_assets( $entry_source ) {
        $manifest = self::get_manifest();

        if ( empty( $entry_source ) || ! isset( $manifest[ $entry_source ] ) ) {
            return array();
        }

        $entry = $manifest[ $entry_source ];
        $dist_url = self::get_dist_url();
        $script_file = isset( $entry['file'] ) ? (string) $entry['file'] : '';

        if ( '' === $script_file ) {
            return array();
        }

        $styles = array();

        foreach ( self::collect_entry_css( $entry_source, $manifest ) as $css_file ) {
            $styles[] = $dist_url . $css_file;
        }

        return array(
            'script' => $dist_url . $script_file,
            'styles' => $styles,
            'version' => self::get_asset_version( $script_file ),
        );
    }


    /**
     * Build a cache-busting version for a dist-relative asset path.
     *
     * Vite emits fixed entry/style file names, so without a version query
     * browsers would serve a stale bundle after every rebuild. Use the file
     * modification time so each rebuild produces a fresh URL.
     *
     * @since 6.0.0
     * @param string $relative_path Manifest asset path, relative to the dist root.
     * @return string|null
     */
    public static function get_asset_version( $relative_path ) {
        if ( empty( $relative_path ) || ! is_string( $relative_path ) ) {
            return null;
        }

        $absolute = trailingslashit( FLEXIFY_CHECKOUT_PATH ) . self::DIST_URL_PATH . $relative_path;

        if ( is_readable( $absolute ) ) {
            $mtime = filemtime( $absolute );

            if ( $mtime ) {
                return (string) $mtime;
            }
        }

        return defined('FLEXIFY_CHECKOUT_VERSION') ? FLEXIFY_CHECKOUT_VERSION : null;
    }


    /**
     * Collect the CSS files of a manifest entry, including imported chunks.
     *
     * @since 6.0.0
     * @param string $entry_key Manifest entry key.
     * @param array<string,mixed> $manifest Decoded manifest.
     * @param array<string,bool> $visited Already-visited entries (cycle guard).
     * @return array<int,string>
     */
    private static function collect_entry_css( $entry_key, $manifest, &$visited = array() ) {
        if ( isset( $visited[ $entry_key ] ) || ! isset( $manifest[ $entry_key ] ) ) {
            return array();
        }

        $visited[ $entry_key ] = true;
        $entry = $manifest[ $entry_key ];
        $css = array();

        if ( ! empty( $entry['imports'] ) && is_array( $entry['imports'] ) ) {
            foreach ( $entry['imports'] as $import_key ) {
                $css = array_merge( $css, self::collect_entry_css( $import_key, $manifest, $visited ) );
            }
        }

        if ( ! empty( $entry['css'] ) && is_array( $entry['css'] ) ) {
            $css = array_merge( $css, $entry['css'] );
        }

        return array_values( array_unique( $css ) );
    }


    /**
     * Decode the manifest once and cache it for the request.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function get_manifest() {
        static $manifest = null;

        if ( null !== $manifest ) {
            return $manifest;
        }

        $manifest = array();
        $manifest_path = trailingslashit( FLEXIFY_CHECKOUT_PATH ) . self::MANIFEST_PATH;

        if ( is_readable( $manifest_path ) ) {
            $decoded = json_decode( (string) file_get_contents( $manifest_path ), true );

            if ( is_array( $decoded ) ) {
                $manifest = $decoded;
            }
        }

        return $manifest;
    }


    /**
     * Get the base URL of the dist directory.
     *
     * @since 6.0.0
     * @return string
     */
    private static function get_dist_url() {
        return trailingslashit( FLEXIFY_CHECKOUT_URL ) . self::DIST_URL_PATH;
    }
}
