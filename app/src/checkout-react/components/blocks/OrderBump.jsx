import { useEffect, useRef, useState } from 'react';
import { useCheckout } from '../../context/CheckoutContext.jsx';
import storeApi from '../../api/storeApi.js';

/**
 * Order bump: an optional offer card that adds a product to the cart via the
 * Store API when checked. The cart is the source of truth for the checked
 * state, so concurrent refreshes never desync the toggle; mutations are guarded
 * by a local pending flag to avoid double add/remove.
 *
 * @param {{config:object, product:object}} props Block config + resolved product.
 */
export default function OrderBump({ config = {}, product = null, editor = false }) {
  const { cart, loadCart } = useCheckout();
  const [pending, setPending] = useState(false);
  const autoAdded = useRef(false);

  const productId = Number(config.product_id || 0);
  const quantity = Number(config.quantity || 1) || 1;

  // Cart is the source of truth: find the matching line (if any).
  const line = (cart && cart.items ? cart.items : []).find((item) => Number(item.id) === productId);
  const inCart = !!line;

  const add = async () => {
    if (pending || !productId) {
      return;
    }

    setPending(true);

    try {
      await storeApi.addItem(productId, quantity);
      await loadCart();
    } catch (e) {
      /* surfaced by the cart state on next load */
    } finally {
      setPending(false);
    }
  };

  const remove = async () => {
    if (pending || !line) {
      return;
    }

    setPending(true);

    try {
      await storeApi.removeItem(line.key);
      await loadCart();
    } catch (e) {
      /* no-op */
    } finally {
      setPending(false);
    }
  };

  // Honor default_checked once, only if the product isn't already in the cart.
  // Never mutate the cart while previewing inside the builder.
  useEffect(() => {
    if (editor || autoAdded.current || !config.default_checked || !productId) {
      return;
    }

    autoAdded.current = true;

    if (!inCart && (!product || product.purchasable)) {
      add();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  if (!productId || (product && product.purchasable === false)) {
    return null;
  }

  const toggle = () => {
    if (editor) {
      return;
    }

    return inCart ? remove() : add();
  };
  const accent = config.highlight_color || '#16a34a';

  return (
    <div
      className="rounded-xl border-2 p-4 transition"
      style={{ borderColor: inCart ? accent : '#e2e8f0', backgroundColor: inCart ? `${accent}10` : 'transparent' }}
    >
      <label className="flex cursor-pointer items-start gap-3">
        <input
          type="checkbox"
          className="mt-1 h-4 w-4 shrink-0"
          checked={inCart}
          disabled={pending}
          onChange={toggle}
          style={{ accentColor: accent }}
        />

        <span className="flex min-w-0 flex-1 items-center gap-3">
          {product && product.image && (
            <img src={product.image} alt="" className="h-12 w-12 shrink-0 rounded-lg object-cover" />
          )}

          <span className="min-w-0 flex-1">
            <span className="flex items-center gap-2">
              <span className="text-sm font-semibold text-slate-800">
                {config.headline || (product && product.name) || 'Oferta especial'}
              </span>
              {config.discount_label && (
                <span
                  className="rounded-full px-2 py-0.5 text-[11px] font-semibold text-white"
                  style={{ backgroundColor: accent }}
                >
                  {config.discount_label}
                </span>
              )}
            </span>

            {config.description && <span className="mt-0.5 block text-xs text-slate-500">{config.description}</span>}

            {product && product.price_html && (
              <span
                className="mt-1 block text-sm font-medium text-slate-700"
                dangerouslySetInnerHTML={{ __html: product.price_html }}
              />
            )}
          </span>
        </span>
      </label>
    </div>
  );
}
