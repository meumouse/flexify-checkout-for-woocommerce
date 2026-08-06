import { useState } from 'react';
import { useCheckout } from '../../context/CheckoutContext.jsx';
import storeApi from '../../api/storeApi.js';
import { acceptOffer } from '../../lib/offers.js';
import OfferCard from './OfferCard.jsx';

/**
 * Cross-sell: a complementary product suggestion with a single "Add" action.
 * Unlike the upsell it has no decline flow — it's an additive nudge.
 *
 * @param {{offer:object, editor?:boolean}} props
 */
export default function CrossSellOffer({ offer, editor = false }) {
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

  return (
    <OfferCard
      product={product}
      headline={offer.headline}
      description={offer.description}
      badge={offer.discount_label}
      accent={offer.highlight_color || '#0ea5e9'}
      ctaLabel={offer.cta_label || 'Adicionar ao pedido'}
      inCart={inCart}
      pending={pending}
      onAccept={add}
    />
  );
}
