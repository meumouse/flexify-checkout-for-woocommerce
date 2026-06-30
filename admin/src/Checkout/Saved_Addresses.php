<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Data layer for the customer address book (saved addresses with nicknames).
 *
 * Stores a list of named addresses per user in a single user meta entry. Each
 * entry keeps the standard WooCommerce billing fields plus the checkout extra
 * fields (street number, neighborhood, CEP, etc.), because the React checkout
 * reuses one address for both billing and shipping.
 *
 * Shared by the REST controller (React checkout) and the My Account tab so both
 * read and write the same shape.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Checkout
 * @author MeuMouse.com
 */
class Saved_Addresses {

    /**
     * User meta key holding the address list.
     *
     * @since 6.0.0
     * @var string
     */
    const META_KEY = 'flexify_saved_addresses';

    /**
     * Standard WooCommerce address keys kept under the "billing" group.
     *
     * @since 6.0.0
     * @var array<int,string>
     */
    const BILLING_KEYS = array(
        'first_name', 'last_name', 'company', 'address_1', 'address_2',
        'city', 'state', 'postcode', 'country', 'phone', 'email',
    );


    /**
     * Get every saved address for a user.
     *
     * @since 6.0.0
     * @param int $user_id User ID. Defaults to the current user.
     * @return array<int,array<string,mixed>> Normalized address entries.
     */
    public static function get_all( $user_id = 0 ) {
        $user_id = $user_id ? (int) $user_id : get_current_user_id();

        if ( ! $user_id ) {
            return array();
        }

        $stored = get_user_meta( $user_id, self::META_KEY, true );

        if ( ! is_array( $stored ) ) {
            return array();
        }

        $list = array();

        foreach ( $stored as $entry ) {
            if ( is_array( $entry ) && ! empty( $entry['id'] ) ) {
                $list[] = self::normalize_entry( $entry );
            }
        }

        return $list;
    }


    /**
     * Get a single saved address by id.
     *
     * @since 6.0.0
     * @param int    $user_id User ID.
     * @param string $id Address id.
     * @return array<string,mixed>|null Address entry or null when not found.
     */
    public static function get( $user_id, $id ) {
        foreach ( self::get_all( $user_id ) as $entry ) {
            if ( (string) $entry['id'] === (string) $id ) {
                return $entry;
            }
        }

        return null;
    }


    /**
     * Add a new saved address.
     *
     * @since 6.0.0
     * @param int   $user_id User ID.
     * @param array $data Raw address payload (nickname, billing, extra, is_default).
     * @return array<string,mixed> The stored address entry.
     */
    public static function add( $user_id, array $data ) {
        $user_id = (int) $user_id;
        $list = self::get_all( $user_id );

        $entry = self::sanitize_entry( $data );
        $entry['id'] = wp_generate_uuid4();
        $entry['created'] = time();

        $list[] = $entry;
        $list = self::apply_default( $list, $entry['is_default'] ? $entry['id'] : '' );

        self::persist( $user_id, $list );

        return $entry;
    }


    /**
     * Update an existing saved address.
     *
     * @since 6.0.0
     * @param int    $user_id User ID.
     * @param string $id Address id.
     * @param array  $data Raw address payload.
     * @return array<string,mixed>|null The updated entry, or null when not found.
     */
    public static function update( $user_id, $id, array $data ) {
        $user_id = (int) $user_id;
        $list = self::get_all( $user_id );
        $updated = null;

        foreach ( $list as $index => $entry ) {
            if ( (string) $entry['id'] !== (string) $id ) {
                continue;
            }

            $sanitized = self::sanitize_entry( $data );
            $sanitized['id'] = $entry['id'];
            $sanitized['created'] = $entry['created'];

            $list[ $index ] = $sanitized;
            $updated = $sanitized;
            break;
        }

        if ( null === $updated ) {
            return null;
        }

        $list = self::apply_default( $list, $updated['is_default'] ? $updated['id'] : '' );

        self::persist( $user_id, $list );

        return $updated;
    }


    /**
     * Delete a saved address.
     *
     * @since 6.0.0
     * @param int    $user_id User ID.
     * @param string $id Address id.
     * @return bool Whether an address was removed.
     */
    public static function delete( $user_id, $id ) {
        $user_id = (int) $user_id;
        $list = self::get_all( $user_id );
        $remaining = array();
        $removed = false;

        foreach ( $list as $entry ) {
            if ( (string) $entry['id'] === (string) $id ) {
                $removed = true;
                continue;
            }

            $remaining[] = $entry;
        }

        if ( $removed ) {
            self::persist( $user_id, $remaining );
        }

        return $removed;
    }


    /**
     * Persist the address list to user meta.
     *
     * @since 6.0.0
     * @param int   $user_id User ID.
     * @param array $list Address entries.
     * @return void
     */
    protected static function persist( $user_id, array $list ) {
        update_user_meta( $user_id, self::META_KEY, array_values( $list ) );
    }


    /**
     * Ensure at most one entry is flagged as default.
     *
     * @since 6.0.0
     * @param array<int,array<string,mixed>> $list Address entries.
     * @param string                         $default_id Id that should be default ('' to keep none forced).
     * @return array<int,array<string,mixed>>
     */
    protected static function apply_default( array $list, $default_id ) {
        if ( '' === $default_id ) {
            return $list;
        }

        foreach ( $list as $index => $entry ) {
            $list[ $index ]['is_default'] = ( (string) $entry['id'] === (string) $default_id );
        }

        return $list;
    }


    /**
     * Sanitize a raw address payload into the stored shape.
     *
     * @since 6.0.0
     * @param array $data Raw payload (nickname, billing, extra, is_default).
     * @return array<string,mixed>
     */
    public static function sanitize_entry( array $data ) {
        $billing_in = isset( $data['billing'] ) && is_array( $data['billing'] ) ? $data['billing'] : array();
        $extra_in = isset( $data['extra'] ) && is_array( $data['extra'] ) ? $data['extra'] : array();

        $billing = array();

        foreach ( self::BILLING_KEYS as $key ) {
            $value = isset( $billing_in[ $key ] ) ? $billing_in[ $key ] : '';

            if ( 'email' === $key ) {
                $billing[ $key ] = sanitize_email( (string) $value );
            } else {
                $billing[ $key ] = sanitize_text_field( (string) $value );
            }
        }

        $extra = array();

        foreach ( $extra_in as $key => $value ) {
            if ( is_scalar( $value ) ) {
                $extra[ sanitize_key( $key ) ] = sanitize_text_field( (string) $value );
            }
        }

        $nickname = sanitize_text_field( (string) ( $data['nickname'] ?? '' ) );

        if ( '' === $nickname ) {
            // Fall back to a readable label built from the street/city.
            $nickname = trim( $billing['address_1'] . ( $billing['city'] ? ', ' . $billing['city'] : '' ) );
        }

        return array(
            'nickname' => $nickname,
            'billing' => $billing,
            'extra' => $extra,
            'is_default' => ! empty( $data['is_default'] ),
        );
    }


    /**
     * Normalize a stored entry so consumers always get the full shape.
     *
     * @since 6.0.0
     * @param array $entry Stored entry.
     * @return array<string,mixed>
     */
    protected static function normalize_entry( array $entry ) {
        $normalized = self::sanitize_entry( $entry );
        $normalized['id'] = (string) $entry['id'];
        $normalized['created'] = isset( $entry['created'] ) ? (int) $entry['created'] : 0;

        return $normalized;
    }
}
