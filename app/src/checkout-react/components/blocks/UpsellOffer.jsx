import { useState } from 'react';
import { useCheckout } from '../../context/CheckoutContext.jsx';
import storeApi from '../../api/storeApi.js';
import { acceptOffer, declineOffer } from '../../lib/offers.js';
import OfferCard from './OfferCard.jsx';

/**
 * Upsell: a prominent card offering a higher-value product. Accepting adds it to
 * the cart via the Store API; declining marks the offer declined so a linked
 * downsell can reveal. No-op while previewing inside the builder.
 *
 * @param {{offer:object, editor?:boolean}} props
 */
export default function UpsellOffer({ offer, editor = false }) {
  const { cart, loadCart } = useCheckout();
  const [pending, setPending] = useState(false);

  const product = offer.product || null;
  const productId = Number(offer.product_id || 0);
  const quantity = Number(offer.quantity || 1) || 1;

  if (!productId || (product && product.purchasable === false)) {
    return null;
  }

  const line = (cart && cart.items ? cart.items : []).find((item) => Number(item.id) === productId);
  const inCart = !!line;

  const add = async () => {
    if (editor || pending) {
      return;
    }

    setPending(true);

    try {
      await storeApi.addItem(productId, quantity);
      acceptOffer(offer.id);
      await loadCart();
    } catch (e) {
      /* surfaced by the cart state on next load */
    } finally {
      setPending(false);
    }
  };

  const decline = () => {
    if (editor) {
      return;
    }

    declineOffer(offer.id);
  };

  return (
    <OfferCard
      product={product}
      headline={offer.headline}
      description={offer.description}
      badge={offer.discount_label}
      accent={offer.highlight_color || '#4f46e5'}
      ctaLabel={offer.cta_label || 'Aproveitar oferta'}
      inCart={inCart}
      pending={pending}
      onAccept={add}
      onDecline={decline}
    />
  );
}
