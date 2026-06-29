<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Search products, categories, attributes and users for the conditions editor.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Catalog_Search extends Abstract_Route {

    /**
     * Route path for catalog searches.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/search';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'GET';


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $type = sanitize_text_field( (string) $request->get_param('type') );
        $term = sanitize_text_field( (string) $request->get_param('term') );

        switch ( $type ) {
            case 'products':
                $items = $this->search_products( $term );
                break;
            case 'categories':
                $items = $this->search_terms( $term, array( 'product_cat' ) );
                break;
            case 'attributes':
                $items = $this->search_terms( $term, $this->get_attribute_taxonomies() );
                break;
            case 'users':
                $items = $this->search_users( $term );
                break;
            default:
                return $this->error_response( __( 'Invalid search type.', 'flexify-checkout-for-woocommerce' ) );
        }

        return $this->success_response( array(
            'items' => $items,
        ) );
    }


    /**
     * Search published products by title.
     *
     * @since 6.0.0
     * @param string $term Search term.
     * @return array<int,array<string,mixed>>
     */
    private function search_products( $term ) {
        $products = get_posts( array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            's' => $term,
        ) );

        return array_map( static function ( $product ) {
            return array(
                'id' => (int) $product->ID,
                'label' => $product->post_title,
            );
        }, $products );
    }


    /**
     * Search taxonomy terms by name.
     *
     * @since 6.0.0
     * @param string $term Search term.
     * @param array<int,string> $taxonomies Taxonomies to search in.
     * @return array<int,array<string,mixed>>
     */
    private function search_terms( $term, $taxonomies ) {
        if ( empty( $taxonomies ) ) {
            return array();
        }

        $terms = get_terms( array(
            'taxonomy' => $taxonomies,
            'hide_empty' => false,
            'number' => 20,
            'name__like' => $term,
        ) );

        if ( is_wp_error( $terms ) ) {
            return array();
        }

        return array_map( static function ( $found ) {
            return array(
                'id' => (int) $found->term_id,
                'label' => $found->name,
            );
        }, $terms );
    }


    /**
     * Search users by login, email or display name.
     *
     * @since 6.0.0
     * @param string $term Search term.
     * @return array<int,array<string,mixed>>
     */
    private function search_users( $term ) {
        $users = get_users( array(
            'search' => '*' . $term . '*',
            'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
            'number' => 20,
        ) );

        return array_map( static function ( $user ) {
            return array(
                'id' => (int) $user->ID,
                'label' => $user->display_name,
            );
        }, $users );
    }


    /**
     * Get the registered WooCommerce attribute taxonomy names.
     *
     * @since 6.0.0
     * @return array<int,string>
     */
    private function get_attribute_taxonomies() {
        if ( ! function_exists('wc_get_attribute_taxonomies') ) {
            return array();
        }

        $taxonomies = array();

        foreach ( wc_get_attribute_taxonomies() as $attribute ) {
            $taxonomies[] = 'pa_' . $attribute->attribute_name;
        }

        return $taxonomies;
    }
}
