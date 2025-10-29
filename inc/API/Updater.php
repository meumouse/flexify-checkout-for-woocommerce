<?php

namespace MeuMouse\Flexify_Checkout\API;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;

use WP_Upgrader;
use Plugin_Upgrader;
use WP_Ajax_Upgrader_Skin;
use WP_Error;
use WP_Filesystem_Direct;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Class to make requests to a remote server to get plugin versions and updates
 *
 * @since 1.0.0
 * @version 5.3.2
 * @package MeuMouse.com
 */
class Updater {

    public $update_checker_file = 'https://packages.meumouse.com/v1/updates/flexify-checkout-for-woocommerce?path=dist&file=update-checker.json';
    public $plugin_slug = FLEXIFY_CHECKOUT_SLUG;
    public $version = FLEXIFY_CHECKOUT_VERSION;
    public $cache_key = 'flexify_checkout_check_updates';
    public $cache_data_base_key = 'flexify_checkout_remote_data';
    public $cache_allowed = true;
    public $time_cache = DAY_IN_SECONDS;
    public $update_available;
    public $download_url;


    /**
     * Construct function
     *
     * @since 1.0.0
     * @version 5.3.0
     * @return void
     */
    public function __construct() {
        if ( defined('FLEXIFY_CHECKOUT_DEBUG_MODE') && FLEXIFY_CHECKOUT_DEBUG_MODE === true ) {
            add_filter( 'https_ssl_verify', '__return_false' );
            add_filter( 'https_local_ssl_verify', '__return_false' );
            add_filter( 'http_request_host_is_external', '__return_true' );
        }

        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
        add_filter( 'site_transient_update_plugins', array( $this, 'update_plugin' ) );
        add_action( 'upgrader_process_complete', array( $this, 'purge_cache' ), 10, 2 );
        add_filter( 'plugin_row_meta', array( $this, 'add_check_updates_link' ), 10, 2 );
        add_filter( 'all_admin_notices', array( $this, 'check_manual_update_query_arg' ) );

        if ( Admin_Options::get_setting('enable_update_notices') === 'yes' && License::is_valid() ) {
            // enable auto updates
            add_filter( 'auto_update_plugin', array( $this, 'enable_auto_update' ), 10, 2 );

            // display new update on plugins list
            add_action( 'admin_notices', array( $this, 'admin_update_notice' ) );
        }
    }


    /**
     * Request on remote server
     * 
     * @since 1.0.0
     * @version 5.0.0
     * @return array
     */
    public function request() {
        $cached_data = wp_cache_get( $this->cache_key );
    
        if ( false === $cached_data ) {
            $remote = get_transient( $this->cache_data_base_key );
    
            if ( false === $remote ) {
                $url = $this->update_checker_file;
                $params = array(
                    'timeout' => 10,
                    'headers' => array(
                        'Accept' => 'application/json',
                    ),
                );

                $remote = wp_remote_get( $url, $params );
    
                if ( ! is_wp_error( $remote ) && 200 === wp_remote_retrieve_response_code( $remote ) ) {
                    $remote_data = json_decode( wp_remote_retrieve_body( $remote ) );
    
                    // set cache remote data for 1 day
                    set_transient( $this->cache_data_base_key, $remote_data, $this->time_cache );
                } else {
                    return false;
                }
            } else {
                $remote_data = $remote;
            }
    
            // set cache remote data for 1 day
            wp_cache_set( $this->cache_key, $remote_data, $this->time_cache );
        } else {
            $remote_data = $cached_data;
        }
    
        return $remote_data;
    }


    /**
     * Get plugin info
     * 
     * @since 1.0.0
     * @version 5.0.0
     * @param array|object $response | Response from request update
     * @param string $action | API action to perform: 'query_plugins', 'plugin_information', 'hot_tags' or 'hot_categories'
     * @param array|object $args | (optional) Array or object of arguments to serialize for the Plugin Info API
     * @return array
     */
    public function plugin_info( $response, $action, $args = array() ) {
        // do nothing if you're not getting plugin information right now
        if ( 'plugin_information' !== $action ) {
            return $response;
        }

        // do nothing if it is not our plugin
        if ( empty( $args->slug ) || $this->plugin_slug !== $args->slug ) {
            return $response;
        }

        // get updates
        $remote = $this->request();

        if ( ! $remote ) {
            return $response;
        }

        $response = new \stdClass();

        $response->name = $remote->name;
        $response->slug = $remote->slug;
        $response->version = $remote->version;
        $response->tested = $remote->tested;
        $response->requires = $remote->requires;
        $response->author = $remote->author;
        $response->author_profile = $remote->author_profile;
        $response->homepage = $remote->homepage;
        $response->download_link = $remote->download_url;
        $response->trunk = $remote->download_url;
        $response->requires_php = $remote->requires_php;
        $response->last_updated = $remote->last_updated;

        $response->sections = array(
            'description' => $remote->sections->description,
            'installation' => $remote->sections->installation,
            'changelog' => $remote->sections->changelog,
        );

        if ( ! empty( $remote->banners ) ) {
            $response->banners = array(
                'low' => $remote->banners->low,
                'high' => $remote->banners->high,
            );
        }

        return $response;
    }


    /**
     * Update plugin details in the WordPress update system
     *
     * @since 1.0.0
     * @param object $transient
     * @return object
     */
    public function update_plugin( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        // get request data
        $cached_data = $this->request();

        if ( $cached_data && isset( $cached_data->version ) && version_compare( $this->version, $cached_data->version, '<' ) ) {
            $this->update_available = $cached_data;
    
            $response = new \stdClass();
            $response->slug = $this->plugin_slug;
            $response->plugin = "{$this->plugin_slug}/{$this->plugin_slug}.php";
            $response->new_version = $cached_data->version;
            $response->tested = $cached_data->tested;
            $response->package = $cached_data->download_url;
    
            $transient->response[$response->plugin] = $response;
        }
    
        return $transient;
    }


    /**
     * Check manual updates
     * 
     * @since 1.0.0
     * @return void
     */
    public function check_manual_update_query_arg() {
        if ( isset( $_GET['flexify_checkout_check_updates'] ) && $_GET['flexify_checkout_check_updates'] === '1' ) {
            // purge cache before request on server
            delete_transient('flexify_checkout_api_request_cache');
            delete_transient('flexify_checkout_api_response_cache');
            delete_transient( $this->cache_key );
            delete_transient( $this->cache_data_base_key );
    
            $remote_data = $this->request();
    
            if ( $remote_data ) {
                $current_version = $this->version;
                $latest_version = $remote_data->version;
    
                // if the current version is lower than that of the remote server
                if ( version_compare( $current_version, $latest_version, '<' )) {
                    $message = __('Uma nova versão do plugin <strong>Flexify Checkout</strong> está disponível.', 'flexify-checkout-for-woocommerce');
                    $class = 'notice is-dismissible notice-success';
    
                    // Display notice
                    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message ); ?>
                    
                    <script type="text/javascript">
                        if ( ! sessionStorage.getItem('reload_flexify_checkout_update' ) ) {
                            sessionStorage.setItem('reload_flexify_checkout_update', 'true');
                            window.location.reload();
                        }
                    </script>
                    <?php
                } elseif ( version_compare( $current_version, $latest_version, '>=' ) ) {
                    $message = __('A versão do plugin <strong>Flexify Checkout</strong> é a mais recente.', 'flexify-checkout-for-woocommerce');
                    $class = 'notice is-dismissible notice-success';
    
                    // Display notice
                    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
                }
            } else {
                $message = __('Não foi possível verificar atualizações para o plugin <strong>Flexify Checkout.</strong>', 'flexify-checkout-for-woocommerce');
                $class = 'notice is-dismissible notice-error';
    
                // Display notice
                printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
            }
        }
    }


    /**
     * Purge cache on update plugin
     * 
     * @since 1.0.0
     * @param $upgrader | WP_Upgrader instance
     * @param array $options | Array of bulk item update data
     * @see https://developer.wordpress.org/reference/hooks/upgrader_process_complete/
     * @return void
     */
    public function purge_cache( $upgrader, $options ) {
        if ( $this->cache_allowed && 'update' === $options['action'] && 'plugin' === $options['type'] ) {
            delete_transient('flexify_checkout_api_request_cache');
            delete_transient('flexify_checkout_api_response_cache');
            delete_transient( $this->cache_key );
            delete_transient( $this->cache_data_base_key );
        }
    }


    /**
     * Add check updates link in the plugin_row_meta
     * 
     * @since 1.0.0
     * @param string $plugin_meta | An array of the plugin’s metadata, including the version, author, author URI, and plugin URI
     * @param string $plugin_file | Path to the plugin file relative to the plugins directory
     * @return array
     */
    public function add_check_updates_link( $plugin_meta, $plugin_file ) {
        if ( $plugin_file === $this->plugin_slug . '/' . $this->plugin_slug . '.php' ) {
            $check_updates_link = '<a href="' . esc_url( add_query_arg( 'flexify_checkout_check_updates', '1' ) ) . '">' . esc_html__( 'Verificar atualizações', 'flexify-checkout-for-woocommerce' ) . '</a>';
            $plugin_meta['flexify_checkout_check_updates'] = $check_updates_link;
        }
        
        return $plugin_meta;
    }


    /**
     * Download and extract plugin ZIP file
     *
     * @since 5.0.0
     * @param string $download_url | Plugin link for download RAW
     * @return bool
     */
    private function download_and_extract( $download_url ) {
        global $wp_filesystem;

        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        $temp_file = download_url( $download_url );

        if ( is_wp_error( $temp_file ) ) {
            error_log( '[AUTO UPDATE] Fail on download plugin: ' . $temp_file->get_error_message() );
            return false;
        }

        $plugin_dir = WP_PLUGIN_DIR . '/' . $this->plugin_slug;

        if ( $wp_filesystem->is_dir( $plugin_dir ) ) {
            $wp_filesystem->delete( $plugin_dir, true );
        }

        $unzip_result = unzip_file( $temp_file, WP_PLUGIN_DIR );

        unlink( $temp_file ); // remove temp file

        if ( is_wp_error( $unzip_result ) ) {
            error_log( '[AUTO UPDATE] Error on extract plugin: ' . $unzip_result->get_error_message() );
            return false;
        }

        return true;
    }


    /**
     * Enable auto-update only for this plugin
     *
     * @since 5.0.0
     * @param bool $update | Whether to enable automatic update
     * @param object $item | The plugin object being checked
     * @return bool
     */
    public function enable_auto_update( $update, $item ) {
        if ( isset( $item->plugin ) && $item->plugin === 'flexify-checkout-for-woocommerce/flexify-checkout-for-woocommerce.php' ) {
            return true; // enable only this plugin
        }

        return $update;
    }


    /**
     * Perform automatic update
     *
     * @since 5.0.0
     * @return void
     */
    public function auto_update_plugin() {
        delete_transient('flexify_checkout_api_request_cache');
        delete_transient('flexify_checkout_api_response_cache');
        delete_transient( $this->cache_key );
        delete_transient( $this->cache_data_base_key );

        $update_data = $this->request();

        if ( ! $update_data || ! isset( $update_data->download_url ) || version_compare( $this->version, $update_data->version, '>=' ) ) {
            return;
        }

        error_log( '[AUTO UPDATE] Starting Flexify Checkout update.' );

        $download_url = esc_url_raw( $update_data->download_url );

        error_log( "[AUTO UPDATE] Downloading update from remote repository." );

        if ( ! $this->download_and_extract( $download_url ) ) {
            error_log( '[AUTO UPDATE] Falha na extração do plugin Flexify Checkout.' );
            return;
        }

        activate_plugin("{$this->plugin_slug}/{$this->plugin_slug}.php");

        error_log( "[AUTO UPDATE] Flexify Checkout plugin updated to version {$update_data->version}" );

        // Check and remove .maintenance file to avoid maintenance screen
        $maintenance_file = ABSPATH . '.maintenance';

        if ( file_exists( $maintenance_file ) ) {
            unlink( $maintenance_file );
        }
    }


    /**
     * Check if has a new update one time per day
     *
     * @since 5.0.0
     * @return void
     */
    public static function check_daily_updates() {
        delete_transient('flexify_checkout_check_updates');
        delete_transient('flexify_checkout_remote_data');

        $updater = new self();
        $remote_data = $updater->request();

        if ( ! $remote_data ) {
            return;
        }

        // compare versions
        $current_version = $updater->version;
        $latest_version = $remote_data->version;

        if ( version_compare( $current_version, $latest_version, '<' ) ) {
            // storage the information in the database for later display
            update_option( 'flexify_checkout_update_available', $latest_version );
        } else {
            // remove option if it's already updated
            delete_option('flexify_checkout_update_available');
        }
    }


    /**
     * Display update notice in the admin panel
     *
     * @since 5.0.0
     * @return void
     */
    public function admin_update_notice() {
        $latest_version = get_option('flexify_checkout_update_available');
        $current_version = $this->version;

        // check if update is available
        if ( ! $latest_version || version_compare( $current_version, $latest_version, '>=' ) ) {
            return;
        }

        $plugin_file = 'flexify-checkout-for-woocommerce/flexify-checkout-for-woocommerce.php';
        $nonce = wp_create_nonce( 'upgrade-plugin_' . $plugin_file );

        $update_url = add_query_arg(
            array(
                'action' => 'upgrade-plugin',
                'plugin' => $plugin_file,
                '_wpnonce' => $nonce,
            ),
            admin_url('update.php')
        );

        $message = sprintf(
            __( 'Uma nova versão do plugin <strong>Flexify Checkout</strong> (%s) está disponível. <a href="%s">Atualize agora</a>.', 'flexify-checkout-for-woocommerce' ),
            esc_html( $latest_version ),
            esc_url( $update_url )
        );

        echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
    }
}