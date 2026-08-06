<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

use MeuMouse\Flexify_Checkout\Admin\Settings\Offers_Store;
use MeuMouse\Flexify_Checkout\API\License;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Runtime engine for checkout offers (order bump, upsell, cross-sell, downsell).
 *
 * Two responsibilities, both Pro-gated:
 *  - Expose the offers whose cart trigger qualifies for the current cart to the
 *    React checkout ({@see get_runtime_offers()}), reusing the Conditions
 *    evaluator so triggers behave exactly like checkout conditions.
 *  - Apply each offer's optional discount as a negative cart fee, mirroring
 *    {@see Conditions::apply_discount_fees()} — the offered product is added to
 *    the cart at catalog price through the Store API, and the discount rides as a
 *    labeled fee so it never fights the Store API line price.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Checkout
 * @author MeuMouse.com
 */
class Offers {

    /**
     * Construct function.
     *
     * @since 6.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'woocommerce_cart_calculate_fees', array( $this, 'apply_offer_discounts' ), 25, 1 );
    }


    /**
     * Get enabled offers whose trigger qualifies for the current cart, enriched
     * with resolved product data for the React checkout.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>>
     */
    public static function get_runtime_offers() {
        if ( ! License::is_valid() ) {
            return array();
        }

        $context = Conditions::build_context();
        $offers = array();

        foreach ( Offers_Store::get_offers_for_react() as $offer ) {
            if ( ! self::offer_qualifies( $offer, $context ) ) {
                continue;
            }

            $offers[] = $offer;
        }

        // Stable order: lower priority first, keeping insertion order on ties.
        usort( $offers, static function ( $a, $b ) {
            return ( (int) ( $a['priority'] ?? 0 ) ) <=> ( (int) ( $b['priority'] ?? 0 ) );
        } );

        return $offers;
    }


    /**
     * Whether an offer's trigger qualifies against a prebuilt context.
     *
     * An empty trigger (no groups) always qualifies.
     *
     * @since 6.0.0
     * @param array<string,mixed> $offer Offer data.
     * @param array<string,mixed> $context Evaluation context from Conditions::build_context().
     * @return bool
     */
    public static function offer_qualifies( $offer, $context ) {
        $trigger = isset( $offer['trigger'] ) && is_array( $offer['trigger'] ) ? $offer['trigger'] : array();

        if ( empty( $trigger['groups'] ) ) {
            return true;
        }

        return Conditions::evaluate_rule( $trigger, $context );
    }


    /**
     * Apply offer discounts as negative cart fees.
     *
     * For each enabled offer with a discount whose trigger qualifies and whose
     * offered product is in the cart, add a labeled negative fee equal to the
     * discount over that product's line.
     *
     * @since 6.0.0
     * @param \WC_Cart $cart Cart object.
     * @return void
     */
    public function apply_offer_discounts( $cart ) {
        if ( ! $cart instanceof \WC_Cart || ! License::is_valid() ) {
            return;
        }

        $offers = Offers_Store::get_offers();

        if ( empty( $offers ) ) {
            return;
        }

        $context = Conditions::build_context();
        $context['cart'] = $cart;

        foreach ( $offers as $offer ) {
            if ( empty( $offer['enabled'] ) ) {
                continue;
            }

            $discount = isset( $offer['discount'] ) && is_array( $offer['discount'] ) ? $offer['discount'] : array();
            $mode = $discount['mode'] ?? 'none';
            $value = (float) ( $discount['value'] ?? 0 );

            if ( 'none' === $mode || $value <= 0 ) {
                continue;
            }

            $product_id = (int) ( $offer['product_id'] ?? 0 );

            if ( ! $product_id || ! self::offer_qualifies( $offer, $context ) ) {
                continue;
            }

            $line_total = self::product_line_total( $cart, $product_id );

            if ( $line_total <= 0 ) {
                continue;
            }

            $amount = ( 'fixed' === $mode ) ? min( $value, $line_total ) : ( $line_total * $value / 100 );

            if ( $amount <= 0 ) {
                continue;
            }

            $label = ( isset( $discount['label'] ) && '' !== $discount['label'] )
                ? $discount['label']
                : __( 'Offer discount', 'flexify-checkout-for-woocommerce' );

            $cart->add_fee( $label, -1 * $amount, false );
        }
    }


    /**
     * Sum the pre-tax line total of a product across the cart.
     *
     * @since 6.0.0
     * @param \WC_Cart $cart Cart object.
     * @param int $product_id Product id to match.
     * @return float
     */
    protected static function product_line_total( $cart, $product_id ) {
        $total = 0.0;

        foreach ( $cart->get_cart() as $line ) {
            $line_product_id = isset( $line['product_id'] ) ? (int) $line['product_id'] : 0;

            if ( $line_product_id !== $product_id ) {
                continue;
            }

            $product = isset( $line['data'] ) ? $line['data'] : null;
            $quantity = isset( $line['quantity'] ) ? (int) $line['quantity'] : 0;

            if ( $product instanceof \WC_Product && $quantity > 0 ) {
                $total += (float) wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) );
            }
        }

        return $total;
    }
}
