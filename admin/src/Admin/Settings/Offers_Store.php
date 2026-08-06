<?php

namespace MeuMouse\Flexify_Checkout\Admin\Settings;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Storage helpers for the checkout offers manager.
 *
 * Offers live in the flexify_checkout_offers option as a flat list of offer
 * entities. Each offer pairs an offered product with an optional discount, copy,
 * a cart trigger (the same two-level condition tree used by {@see Conditions_Store})
 * and a type: order_bump, upsell, cross_sell or downsell. Legacy inline
 * order_bump blocks stored in the checkout layout (flexify_checkout_layout) are
 * mirrored into this registry once, on first read.
 *
 * The trigger decides WHETHER an offer shows (evaluated at runtime by
 * {@see \MeuMouse\Flexify_Checkout\Checkout\Offers}); a builder "offer" block or
 * the placement hint decides WHERE it renders.
 *
 * @since 6.0.0
 * @version 6.0.0
 * @package MeuMouse\Flexify_Checkout\Admin\Settings
 * @author MeuMouse.com
 */
class Offers_Store {

    /**
     * Option name holding the offers list.
     *
     * @since 6.0.0
     * @var string
     */
    const OPTION_NAME = 'flexify_checkout_offers';

    /**
     * Option flag marking the one-time migration from inline order_bump blocks.
     *
     * @since 6.0.0
     * @var string
     */
    const MIGRATED_FLAG = 'flexify_checkout_offers_migrated';

    /**
     * Supported offer types.
     *
     * @since 6.0.0
     * @var string[]
     */
    const TYPES = array( 'order_bump', 'upsell', 'cross_sell', 'downsell' );

    /**
     * Placement hints for where an offer renders when not bound to a builder slot.
     *
     * @since 6.0.0
     * @var string[]
     */
    const PLACEMENTS = array( 'auto', 'summary', 'builder_slot' );


    /**
     * Read the stored offers, running the legacy migration once on demand.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>>
     */
    public static function get_offers() {
        self::maybe_migrate_inline_bumps();

        $stored = get_option( self::OPTION_NAME, array() );
        $stored = is_array( $stored ) ? $stored : array();

        if ( empty( $stored ) ) {
            return array();
        }

        $offers = array();

        foreach ( $stored as $offer ) {
            if ( ! is_array( $offer ) ) {
                continue;
            }

            $offers[] = self::sanitize_offer( $offer, isset( $offer['id'] ) ? (string) $offer['id'] : self::generate_id() );
        }

        return $offers;
    }


    /**
     * Persist the full list of offers.
     *
     * @since 6.0.0
     * @param array<int,array<string,mixed>> $offers Offers to store.
     * @return bool
     */
    public static function save_offers( $offers ) {
        return update_option( self::OPTION_NAME, array_values( $offers ) );
    }


    /**
     * Build the admin client payload: offers with resolved product data.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>>
     */
    public static function get_offers_for_client() {
        $items = array();

        foreach ( self::get_offers() as $offer ) {
            // Label the trigger items (product/category/user ids → names) so the
            // Vue editor can seed its pickers, reusing the conditions labeler.
            $trigger = isset( $offer['trigger'] ) && is_array( $offer['trigger'] ) ? $offer['trigger'] : array();
            $trigger['groups'] = Conditions_Store::label_groups( isset( $trigger['groups'] ) ? $trigger['groups'] : array() );

            $items[] = array_merge( $offer, array(
                'trigger' => $trigger,
                'product' => self::resolve_product( $offer ),
            ) );
        }

        return $items;
    }


    /**
     * Build the checkout payload: enabled offers enriched with product data.
     *
     * Trigger eligibility is NOT evaluated here (it depends on the live cart);
     * {@see \MeuMouse\Flexify_Checkout\Checkout\Offers} filters this list against
     * the runtime context before it reaches the React checkout.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>>
     */
    public static function get_offers_for_react() {
        $items = array();

        foreach ( self::get_offers() as $offer ) {
            if ( empty( $offer['enabled'] ) ) {
                continue;
            }

            $items[] = array_merge( $offer, array(
                'product' => self::resolve_product( $offer ),
            ) );
        }

        return $items;
    }


    /**
     * Find a single offer by its id.
     *
     * @since 6.0.0
     * @param string $id Offer id.
     * @return array<string,mixed>|null
     */
    public static function get_offer( $id ) {
        foreach ( self::get_offers() as $offer ) {
            if ( isset( $offer['id'] ) && $offer['id'] === (string) $id ) {
                return $offer;
            }
        }

        return null;
    }


    /**
     * Insert a new offer or update an existing one when an id is provided.
     *
     * @since 6.0.0
     * @param array<string,mixed> $incoming Raw offer payload.
     * @return string|false Stored offer id on success, false on failure.
     */
    public static function upsert_offer( $incoming ) {
        $incoming = is_array( $incoming ) ? $incoming : array();
        $id = isset( $incoming['id'] ) ? (string) $incoming['id'] : '';

        if ( '' !== $id ) {
            return self::update_offer( $id, $incoming ) ? $id : false;
        }

        return self::add_offer( $incoming );
    }


    /**
     * Add a new offer.
     *
     * @since 6.0.0
     * @param array<string,mixed> $incoming Raw offer payload.
     * @return string|false New offer id on success, false on failure.
     */
    public static function add_offer( $incoming ) {
        $offers = self::get_offers();
        $offer = self::sanitize_offer( $incoming, self::generate_id() );
        $offers[] = $offer;

        return self::save_offers( $offers ) ? $offer['id'] : false;
    }


    /**
     * Update an existing offer by its id.
     *
     * @since 6.0.0
     * @param string $id Offer id.
     * @param array<string,mixed> $incoming Raw offer payload.
     * @return bool
     */
    public static function update_offer( $id, $incoming ) {
        $offers = self::get_offers();
        $found = false;

        foreach ( $offers as $index => $offer ) {
            if ( isset( $offer['id'] ) && $offer['id'] === (string) $id ) {
                $offers[ $index ] = self::sanitize_offer( $incoming, (string) $id );
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            return false;
        }

        return self::save_offers( $offers );
    }


    /**
     * Remove an offer by its id. Any downsell linked to it is unlinked.
     *
     * @since 6.0.0
     * @param string $id Offer id.
     * @return bool
     */
    public static function delete_offer( $id ) {
        $offers = self::get_offers();
        $next = array();
        $found = false;

        foreach ( $offers as $offer ) {
            if ( isset( $offer['id'] ) && $offer['id'] === (string) $id ) {
                $found = true;
                continue;
            }

            // Unlink downsells that pointed at the removed offer.
            if ( isset( $offer['downsell_of'] ) && $offer['downsell_of'] === (string) $id ) {
                $offer['downsell_of'] = '';
            }

            $next[] = $offer;
        }

        if ( ! $found ) {
            return false;
        }

        return self::save_offers( $next );
    }


    /**
     * Sanitize an incoming offer payload.
     *
     * @since 6.0.0
     * @param array<string,mixed> $incoming Raw offer data.
     * @param string $id Offer id to assign.
     * @return array<string,mixed>
     */
    public static function sanitize_offer( $incoming, $id ) {
        $incoming = is_array( $incoming ) ? $incoming : array();

        $type = in_array( $incoming['type'] ?? '', self::TYPES, true ) ? $incoming['type'] : 'order_bump';
        $placement = in_array( $incoming['placement'] ?? '', self::PLACEMENTS, true ) ? $incoming['placement'] : 'auto';

        $offer = array(
            'id' => (string) $id,
            'type' => $type,
            'enabled' => ! isset( $incoming['enabled'] ) || ! empty( $incoming['enabled'] ),
            'name' => sanitize_text_field( (string) ( $incoming['name'] ?? '' ) ),
            'priority' => absint( $incoming['priority'] ?? 0 ),
            'product_id' => absint( $incoming['product_id'] ?? 0 ),
            'quantity' => max( 1, absint( $incoming['quantity'] ?? 1 ) ),
            'discount' => self::sanitize_discount( $incoming['discount'] ?? array() ),
            'headline' => sanitize_text_field( (string) ( $incoming['headline'] ?? '' ) ),
            'description' => sanitize_textarea_field( (string) ( $incoming['description'] ?? '' ) ),
            'image_id' => absint( $incoming['image_id'] ?? 0 ),
            'discount_label' => sanitize_text_field( (string) ( $incoming['discount_label'] ?? '' ) ),
            'highlight_color' => sanitize_hex_color( (string) ( $incoming['highlight_color'] ?? '' ) ) ?: '',
            'cta_label' => sanitize_text_field( (string) ( $incoming['cta_label'] ?? '' ) ),
            'placement' => $placement,
            'downsell_of' => ( 'downsell' === $type ) ? sanitize_text_field( (string) ( $incoming['downsell_of'] ?? '' ) ) : '',
            'trigger' => self::sanitize_trigger( $incoming['trigger'] ?? array() ),
        );

        return $offer;
    }


    /**
     * Sanitize the discount block.
     *
     * @since 6.0.0
     * @param mixed $incoming Raw discount data.
     * @return array<string,mixed>
     */
    public static function sanitize_discount( $incoming ) {
        $incoming = is_array( $incoming ) ? $incoming : array();
        $mode = in_array( $incoming['mode'] ?? 'none', array( 'none', 'percent', 'fixed' ), true ) ? $incoming['mode'] : 'none';

        return array(
            'mode' => $mode,
            'value' => max( 0, (float) ( $incoming['value'] ?? 0 ) ),
            'label' => sanitize_text_field( (string) ( $incoming['label'] ?? '' ) ),
        );
    }


    /**
     * Sanitize the cart trigger, reusing the Conditions group/condition contract.
     *
     * The trigger mirrors a Conditions rule minus its action: a two-level tree of
     * groups (joined by all|any) each holding conditions (joined by all|any). An
     * empty trigger means the offer always qualifies.
     *
     * @since 6.0.0
     * @param mixed $incoming Raw trigger data.
     * @return array<string,mixed>
     */
    public static function sanitize_trigger( $incoming ) {
        $incoming = is_array( $incoming ) ? $incoming : array();

        $trigger = array(
            'match' => ( ( $incoming['match'] ?? 'all' ) === 'any' ) ? 'any' : 'all',
            'groups' => array(),
        );

        foreach ( (array) ( $incoming['groups'] ?? array() ) as $group ) {
            $trigger['groups'][] = Conditions_Store::sanitize_group( $group );
        }

        return $trigger;
    }


    /**
     * Resolve product data (title, price, thumbnail) for an offer.
     *
     * Mirrors {@see Layout_Store::resolve_order_bumps()} but honors a per-offer
     * image override and exposes a discounted price preview when a discount is set.
     *
     * @since 6.0.0
     * @param array<string,mixed> $offer Offer data.
     * @return array<string,mixed>|null
     */
    public static function resolve_product( $offer ) {
        $product_id = (int) ( $offer['product_id'] ?? 0 );
        $product = $product_id && function_exists('wc_get_product') ? wc_get_product( $product_id ) : null;

        if ( ! $product || is_wp_error( $product ) ) {
            return null;
        }

        $image_id = (int) ( $offer['image_id'] ?? 0 );
        $image = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : '';

        if ( ! $image ) {
            $image = wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src( 'woocommerce_thumbnail' );
        }

        return array(
            'id' => $product_id,
            'name' => $product->get_name(),
            'price' => (float) wc_get_price_to_display( $product ),
            'price_html' => $product->get_price_html(),
            'image' => $image,
            'purchasable' => $product->is_purchasable() && $product->is_in_stock(),
        );
    }


    /**
     * Mirror inline order_bump blocks from the checkout layout into the registry.
     *
     * Runs at most once (guarded by the MIGRATED_FLAG option). Each inline
     * order_bump becomes an always-on offer of type order_bump with the
     * builder_slot placement. The inline blocks are left untouched so the current
     * checkout keeps rendering them until the builder is migrated to reference
     * offers by id.
     *
     * @since 6.0.0
     * @return void
     */
    protected static function maybe_migrate_inline_bumps() {
        if ( 'yes' === get_option( self::MIGRATED_FLAG, 'no' ) ) {
            return;
        }

        // Mark done up-front so a failure or empty layout never re-triggers a scan.
        update_option( self::MIGRATED_FLAG, 'yes' );

        if ( ! class_exists( Layout_Store::class ) || ! Layout_Store::has_saved_layout() ) {
            return;
        }

        $layout = Layout_Store::get_layout();
        $steps = isset( $layout['steps'] ) && is_array( $layout['steps'] ) ? $layout['steps'] : array();
        $offers = array();

        foreach ( $steps as $step ) {
            $items = isset( $step['items'] ) && is_array( $step['items'] ) ? $step['items'] : array();

            foreach ( $items as $item ) {
                if ( 'component' !== ( $item['kind'] ?? '' ) || 'order_bump' !== ( $item['component'] ?? '' ) ) {
                    continue;
                }

                $config = isset( $item['config'] ) && is_array( $item['config'] ) ? $item['config'] : array();

                if ( empty( $config['product_id'] ) ) {
                    continue;
                }

                $offers[] = self::sanitize_offer( array(
                    'type' => 'order_bump',
                    'enabled' => true,
                    'name' => (string) ( $config['headline'] ?? '' ),
                    'product_id' => $config['product_id'],
                    'quantity' => $config['quantity'] ?? 1,
                    'headline' => $config['headline'] ?? '',
                    'description' => $config['description'] ?? '',
                    'image_id' => $config['image_id'] ?? 0,
                    'discount_label' => $config['discount_label'] ?? '',
                    'highlight_color' => $config['highlight_color'] ?? '',
                    'placement' => 'builder_slot',
                    'trigger' => array(),
                ), self::generate_id() );
            }
        }

        if ( ! empty( $offers ) ) {
            self::save_offers( $offers );
        }
    }


    /**
     * Generate a unique offer id.
     *
     * @since 6.0.0
     * @return string
     */
    public static function generate_id() {
        return 'offer_' . substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 12 );
    }
}
