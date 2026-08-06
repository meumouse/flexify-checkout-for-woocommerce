import { useState } from 'react';
import { useCheckout } from '../../context/CheckoutContext.jsx';
import storeApi from '../../api/storeApi.js';
import { acceptOffer, isDeclined, useDeclineVersion } from '../../lib/offers.js';
import OfferCard from './OfferCard.jsx';

/**
 * Downsell: a cheaper alternative that only appears once its parent offer
 * (downsell_of) has been declined within the checkout. In the builder preview it
 * always renders so the operator can position it.
 *
 * @param {{offer:object, editor?:boolean}} props
 */
export default function DownsellOffer({ offer, editor = false }) {
  // Re-render when the decline store changes.
  useDeclineVersion();

  const { cart, loadCart } = useCheckout();
  const [pending, setPending] = useState(false);

  const product = offer.product || null;
  const productId = Number(offer.product_id || 0);
  const quantity = Number(offer.quantity || 1) || 1;

  const visible = editor || (offer.downsell_of && isDeclined(offer.downsell_of));

  if (!visible || !productId || (product && product.purchasable === false)) {
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
      accent={offer.highlight_color || '#f59e0b'}
      ctaLabel={offer.cta_label || 'Quero esta oferta'}
      inCart={inCart}
      pending={pending}
      onAccept={add}
    />
  );
}
