<?php

namespace MeuMouse\Flexify_Checkout\Account;

use MeuMouse\Flexify_Checkout\Checkout\Headless_Data;
use MeuMouse\Flexify_Checkout\Checkout\Saved_Addresses;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * "Saved addresses" tab in the WooCommerce My Account area (Pro).
 *
 * Registers a custom account endpoint, adds the menu item, renders the address
 * list with an add/edit form, and processes the form submissions. Reads and
 * writes through the shared Saved_Addresses data layer so addresses created
 * here and in the React checkout stay interchangeable.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Account
 * @author MeuMouse.com
 */
class Address_Book {

    /**
     * Account endpoint slug.
     *
     * @since 6.0.0
     * @var string
     */
    const ENDPOINT = 'enderecos-salvos';


    /**
     * Bootstrap the account tab hooks.
     *
     * @since 6.0.0
     * @return void
     */
    public function init() {
        if ( ! Headless_Data::is_saved_addresses_available() ) {
            return;
        }

        add_action( 'init', array( $this, 'register_endpoint' ) );
        add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_var' ) );
        add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ) );
        add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render' ) );
        add_action( 'template_redirect', array( $this, 'maybe_handle_submission' ) );
    }


    /**
     * Register the rewrite endpoint, flushing rules once when it first appears.
     *
     * @since 6.0.0
     * @return void
     */
    public function register_endpoint() {
        add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );

        if ( get_option('flexify_saved_addresses_endpoint') !== self::ENDPOINT ) {
            flush_rewrite_rules( false );
            update_option( 'flexify_saved_addresses_endpoint', self::ENDPOINT );
        }
    }


    /**
     * Expose the endpoint to WooCommerce's query var handling.
     *
     * @since 6.0.0
     * @param array $vars Existing query vars.
     * @return array
     */
    public function add_query_var( $vars ) {
        $vars[ self::ENDPOINT ] = self::ENDPOINT;

        return $vars;
    }


    /**
     * Insert the menu item before "Logout".
     *
     * @since 6.0.0
     * @param array $items Account menu items.
     * @return array
     */
    public function add_menu_item( $items ) {
        $logout = isset( $items['customer-logout'] ) ? array( 'customer-logout' => $items['customer-logout'] ) : array();
        unset( $items['customer-logout'] );

        $items[ self::ENDPOINT ] = __( 'Saved addresses', 'flexify-checkout-for-woocommerce' );

        return array_merge( $items, $logout );
    }


    /**
     * Render the account tab content.
     *
     * @since 6.0.0
     * @return void
     */
    public function render() {
        $user_id = get_current_user_id();
        $addresses = Saved_Addresses::get_all( $user_id );

        $editing_id = isset( $_GET['edit'] ) ? sanitize_text_field( wp_unslash( $_GET['edit'] ) ) : '';
        $editing = $editing_id ? Saved_Addresses::get( $user_id, $editing_id ) : null;

        echo '<div class="flexify-address-book">';

        if ( empty( $addresses ) ) {
            echo '<p>' . esc_html__( 'You have no saved addresses yet.', 'flexify-checkout-for-woocommerce' ) . '</p>';
        } else {
            echo '<ul class="flexify-address-book__list">';

            foreach ( $addresses as $address ) {
                $this->render_card( $address );
            }

            echo '</ul>';
        }

        echo '<h3>' . ( $editing ? esc_html__( 'Edit address', 'flexify-checkout-for-woocommerce' ) : esc_html__( 'Add a new address', 'flexify-checkout-for-woocommerce' ) ) . '</h3>';

        $this->render_form( $editing );

        echo '</div>';
    }


    /**
     * Render a single saved-address card with edit/delete controls.
     *
     * @since 6.0.0
     * @param array $address Address entry.
     * @return void
     */
    protected function render_card( array $address ) {
        $b = $address['billing'];
        $extra = $address['extra'];
        $edit_url = add_query_arg( 'edit', $address['id'], wc_get_account_endpoint_url( self::ENDPOINT ) );

        echo '<li class="flexify-address-book__item">';
        echo '<div class="flexify-address-book__head">';
        echo '<strong>' . esc_html( $address['nickname'] ) . '</strong>';

        if ( ! empty( $address['is_default'] ) ) {
            echo ' <span class="flexify-address-book__badge">' . esc_html__( 'Default', 'flexify-checkout-for-woocommerce' ) . '</span>';
        }

        echo '</div>';

        $line = trim( $b['address_1'] . ' ' . ( $extra['billing_number'] ?? '' ) );
        $line2 = trim( ( $extra['billing_neighborhood'] ?? '' ) );
        $line3 = trim( $b['city'] . ( $b['state'] ? ' - ' . $b['state'] : '' ) . ( $b['postcode'] ? ', ' . $b['postcode'] : '' ) );

        echo '<address>';
        echo esc_html( trim( $b['first_name'] . ' ' . $b['last_name'] ) ) . '<br>';

        if ( $line ) {
            echo esc_html( $line ) . '<br>';
        }

        if ( $line2 ) {
            echo esc_html( $line2 ) . '<br>';
        }

        if ( $line3 ) {
            echo esc_html( $line3 );
        }

        echo '</address>';

        echo '<div class="flexify-address-book__actions">';
        echo '<a class="button" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'flexify-checkout-for-woocommerce' ) . '</a> ';

        echo '<form method="post" class="flexify-address-book__delete" style="display:inline">';
        wp_nonce_field( 'flexify_address_book', 'flexify_address_nonce' );
        echo '<input type="hidden" name="flexify_address_action" value="delete">';
        echo '<input type="hidden" name="address_id" value="' . esc_attr( $address['id'] ) . '">';
        echo '<button type="submit" class="button">' . esc_html__( 'Delete', 'flexify-checkout-for-woocommerce' ) . '</button>';
        echo '</form>';

        echo '</div>';
        echo '</li>';
    }


    /**
     * Render the add/edit form.
     *
     * @since 6.0.0
     * @param array|null $editing Address being edited, or null for a new one.
     * @return void
     */
    protected function render_form( $editing ) {
        $b = $editing ? $editing['billing'] : array();
        $extra = $editing ? $editing['extra'] : array();
        $countries = WC()->countries ? WC()->countries->get_allowed_countries() : array();
        $current_country = $b['country'] ?? '';

        echo '<form method="post" class="flexify-address-book__form woocommerce-Address">';
        wp_nonce_field( 'flexify_address_book', 'flexify_address_nonce' );
        echo '<input type="hidden" name="flexify_address_action" value="save">';

        if ( $editing ) {
            echo '<input type="hidden" name="address_id" value="' . esc_attr( $editing['id'] ) . '">';
        }

        $this->text_row( 'nickname', __( 'Nickname', 'flexify-checkout-for-woocommerce' ), $editing ? $editing['nickname'] : '', true );
        $this->text_row( 'first_name', __( 'First name', 'flexify-checkout-for-woocommerce' ), $b['first_name'] ?? '' );
        $this->text_row( 'last_name', __( 'Last name', 'flexify-checkout-for-woocommerce' ), $b['last_name'] ?? '' );
        $this->text_row( 'company', __( 'Company', 'flexify-checkout-for-woocommerce' ), $b['company'] ?? '' );
        $this->text_row( 'postcode', __( 'Postcode / ZIP', 'flexify-checkout-for-woocommerce' ), $b['postcode'] ?? '' );
        $this->text_row( 'address_1', __( 'Street address', 'flexify-checkout-for-woocommerce' ), $b['address_1'] ?? '' );
        $this->text_row( 'billing_number', __( 'Number', 'flexify-checkout-for-woocommerce' ), $extra['billing_number'] ?? '' );
        $this->text_row( 'address_2', __( 'Apartment, suite, etc.', 'flexify-checkout-for-woocommerce' ), $b['address_2'] ?? '' );
        $this->text_row( 'billing_neighborhood', __( 'Neighborhood', 'flexify-checkout-for-woocommerce' ), $extra['billing_neighborhood'] ?? '' );
        $this->text_row( 'city', __( 'City', 'flexify-checkout-for-woocommerce' ), $b['city'] ?? '' );
        $this->text_row( 'state', __( 'State', 'flexify-checkout-for-woocommerce' ), $b['state'] ?? '' );

        // Country select.
        echo '<p class="form-row form-row-wide">';
        echo '<label for="flexify_country">' . esc_html__( 'Country', 'flexify-checkout-for-woocommerce' ) . '</label>';
        echo '<select id="flexify_country" name="country" class="select">';

        foreach ( $countries as $code => $name ) {
            echo '<option value="' . esc_attr( $code ) . '"' . selected( $current_country, $code, false ) . '>' . esc_html( $name ) . '</option>';
        }

        echo '</select></p>';

        $this->text_row( 'phone', __( 'Phone', 'flexify-checkout-for-woocommerce' ), $b['phone'] ?? '' );
        $this->text_row( 'email', __( 'Email address', 'flexify-checkout-for-woocommerce' ), $b['email'] ?? '' );

        echo '<p class="form-row form-row-wide">';
        echo '<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">';
        echo '<input type="checkbox" name="is_default" value="1"' . checked( ! empty( $editing['is_default'] ), true, false ) . '> ';
        echo esc_html__( 'Set as default address', 'flexify-checkout-for-woocommerce' );
        echo '</label></p>';

        echo '<p><button type="submit" class="button">' . esc_html__( 'Save address', 'flexify-checkout-for-woocommerce' ) . '</button></p>';
        echo '</form>';
    }


    /**
     * Render a labelled text input form row.
     *
     * @since 6.0.0
     * @param string $name Field name.
     * @param string $label Field label.
     * @param string $value Current value.
     * @param bool   $required Whether the field is required.
     * @return void
     */
    protected function text_row( $name, $label, $value, $required = false ) {
        echo '<p class="form-row form-row-wide">';
        echo '<label for="flexify_' . esc_attr( $name ) . '">' . esc_html( $label );

        if ( $required ) {
            echo ' <abbr class="required" title="' . esc_attr__( 'required', 'flexify-checkout-for-woocommerce' ) . '">*</abbr>';
        }

        echo '</label>';
        echo '<input type="text" id="flexify_' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="input-text"' . ( $required ? ' required' : '' ) . '>';
        echo '</p>';
    }


    /**
     * Process add/update/delete submissions on the account page.
     *
     * @since 6.0.0
     * @return void
     */
    public function maybe_handle_submission() {
        if ( ! is_user_logged_in() || empty( $_POST['flexify_address_action'] ) ) {
            return;
        }

        if ( ! isset( $_POST['flexify_address_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['flexify_address_nonce'] ) ), 'flexify_address_book' ) ) {
            return;
        }

        $user_id = get_current_user_id();
        $action = sanitize_text_field( wp_unslash( $_POST['flexify_address_action'] ) );

        if ( 'delete' === $action ) {
            $id = isset( $_POST['address_id'] ) ? sanitize_text_field( wp_unslash( $_POST['address_id'] ) ) : '';

            if ( $id && Saved_Addresses::delete( $user_id, $id ) ) {
                wc_add_notice( __( 'Address removed.', 'flexify-checkout-for-woocommerce' ), 'success' );
            }
        } elseif ( 'save' === $action ) {
            $payload = $this->read_post_payload();
            $id = isset( $_POST['address_id'] ) ? sanitize_text_field( wp_unslash( $_POST['address_id'] ) ) : '';

            if ( $id ) {
                Saved_Addresses::update( $user_id, $id, $payload );
                wc_add_notice( __( 'Address updated.', 'flexify-checkout-for-woocommerce' ), 'success' );
            } else {
                Saved_Addresses::add( $user_id, $payload );
                wc_add_notice( __( 'Address saved.', 'flexify-checkout-for-woocommerce' ), 'success' );
            }
        }

        wp_safe_redirect( wc_get_account_endpoint_url( self::ENDPOINT ) );
        exit;
    }


    /**
     * Build a Saved_Addresses payload from the posted form.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    protected function read_post_payload() {
        $post = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked by caller.

        $billing = array();

        foreach ( Saved_Addresses::BILLING_KEYS as $key ) {
            $billing[ $key ] = isset( $post[ $key ] ) ? $post[ $key ] : '';
        }

        $extra = array(
            'billing_number' => isset( $post['billing_number'] ) ? $post['billing_number'] : '',
            'billing_neighborhood' => isset( $post['billing_neighborhood'] ) ? $post['billing_neighborhood'] : '',
        );

        return array(
            'nickname' => isset( $post['nickname'] ) ? $post['nickname'] : '',
            'billing' => $billing,
            'extra' => $extra,
            'is_default' => ! empty( $post['is_default'] ),
        );
    }
}
