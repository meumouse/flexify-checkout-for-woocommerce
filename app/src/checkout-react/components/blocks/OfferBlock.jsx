import { getOffer } from '../../lib/offers.js';
import OrderBump from './OrderBump.jsx';
import UpsellOffer from './UpsellOffer.jsx';
import CrossSellOffer from './CrossSellOffer.jsx';
import DownsellOffer from './DownsellOffer.jsx';

/**
 * Builder "offer" block: references a registry offer by id and renders the
 * component matching its type. The order_bump type reuses the existing
 * OrderBump card (adapting the offer entity to its config/product props); the
 * other types have dedicated cards.
 *
 * @param {{config?:object, editor?:boolean}} props
 */
export default function OfferBlock({ config = {}, editor = false }) {
  const offer = getOffer(config.offer_id);

  if (!offer) {
    if (editor) {
      return (
        <div className="rounded-xl border-2 border-dashed border-slate-300 p-4 text-center text-sm text-slate-400">
          Selecione uma oferta para este bloco.
        </div>
      );
    }

    return null;
  }

  if (offer.type === 'order_bump') {
    const bumpConfig = {
      product_id: offer.product_id,
      quantity: offer.quantity,
      headline: offer.headline,
      description: offer.description,
      discount_label: offer.discount_label,
      highlight_color: offer.highlight_color,
      default_checked: false,
    };

    return <OrderBump config={bumpConfig} product={offer.product || null} editor={editor} />;
  }

  if (offer.type === 'upsell') {
    return <UpsellOffer offer={offer} editor={editor} />;
  }

  if (offer.type === 'cross_sell') {
    return <CrossSellOffer offer={offer} editor={editor} />;
  }

  if (offer.type === 'downsell') {
    return <DownsellOffer offer={offer} editor={editor} />;
  }

  return null;
}
