import { useEffect, useRef } from 'react';
import config, { getCheckoutSetting } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import { resolveAvailableGateways } from '../lib/gateways.js';
import { isEditor } from '../lib/editorBridge.js';
import {
  blocksRuntimeReady,
  mountBlocksGateway,
  refreshActiveBilling,
  unmountBlocksGateway,
} from '../lib/blocksPaymentBridge.js';
import SplitPaymentSlot from './SplitPaymentSlot.jsx';
import { PixIcon, CreditCardIcon, BarcodeIcon, ChevronDownIcon } from './ui/Icons.jsx';

/**
 * Payment method selector. Renders the available gateways either as a card grid
 * (Pix / Cartão / Boleto…) or as an accordion ("sanfona"), based on the
 * operator-tunable `payment_methods_layout` setting. Each method shows the icon
 * supplied by its WooCommerce integration when available. Rendered inside the
 * payment step.
 *
 * The methods shown come from the live Store API cart availability, not the
 * static page-load catalog, so conditional gateways update as the cart changes.
 */
export default function PaymentMethods() {
  const ctx = useCheckout();
  const { cart, selectedGateway, setSelectedGateway } = ctx;
  const gateways = resolveAvailableGateways(cart);
  const pixDiscount = Number(config.settings?.pix_discount_percent || 0);
  const layout = getCheckoutSetting('payment_methods_layout', 'cards');

  if (gateways.length === 0) {
    return <p className="text-sm text-slate-500">Nenhuma forma de pagamento disponível.</p>;
  }

  const active = gateways.find((g) => g.id === selectedGateway) || gateways[0];

  return (
    <div className="space-y-5">
      {layout === 'accordion' ? (
        <AccordionLayout
          gateways={gateways}
          active={active}
          pixDiscount={pixDiscount}
          onSelect={setSelectedGateway}
        />
      ) : (
        <CardsLayout
          gateways={gateways}
          active={active}
          pixDiscount={pixDiscount}
          onSelect={setSelectedGateway}
        />
      )}

      <SplitPaymentSlot ctx={ctx} />
    </div>
  );
}

function CardsLayout({ gateways, active, pixDiscount, onSelect }) {
  const gridCols = gateways.length >= 3 ? 'grid-cols-3' : gateways.length === 2 ? 'grid-cols-2' : 'grid-cols-1';

  return (
    <>
      <div className={`grid gap-3 ${gridCols}`}>
        {gateways.map((gateway) => {
          const isActive = active && gateway.id === active.id;

          return (
            <button
              key={gateway.id}
              type="button"
              onClick={() => onSelect(gateway.id)}
              className={`relative flex min-h-[5.5rem] flex-col items-center justify-center gap-2 rounded-xl border px-2 py-3 text-center transition-colors ${
                isActive
                  ? 'border-slate-800 bg-white ring-1 ring-slate-800'
                  : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
              }`}
            >
              <span className="flex h-7 items-center justify-center">
                <GatewayIcon gateway={gateway} />
              </span>
              <span className="text-sm font-medium leading-tight text-slate-800">{gateway.title}</span>
              {gateway.kind === 'pix' && pixDiscount > 0 && (
                <span className="fc-soft-bg fc-primary-text rounded-full px-2 py-0.5 text-[11px] font-semibold">
                  {pixDiscount}% off
                </span>
              )}
            </button>
          );
        })}
      </div>

      {active && <GatewayDetail gateway={active} />}
    </>
  );
}

function AccordionLayout({ gateways, active, pixDiscount, onSelect }) {
  return (
    <div className="divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200">
      {gateways.map((gateway) => {
        const isActive = active && gateway.id === active.id;

        return (
          <div key={gateway.id} className="bg-white">
            <button
              type="button"
              onClick={() => onSelect(gateway.id)}
              aria-expanded={isActive}
              className={`flex w-full items-center gap-3 px-4 py-3.5 text-left transition-colors ${
                isActive ? 'bg-slate-50' : 'hover:bg-slate-50'
              }`}
            >
              <span
                className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full border ${
                  isActive ? 'border-slate-800' : 'border-slate-300'
                }`}
              >
                {isActive && <span className="h-2.5 w-2.5 rounded-full bg-slate-800" />}
              </span>
              <span className="flex h-7 w-9 shrink-0 items-center justify-center">
                <GatewayIcon gateway={gateway} />
              </span>
              <span className="flex-1 text-sm font-medium leading-tight text-slate-800">{gateway.title}</span>
              {gateway.kind === 'pix' && pixDiscount > 0 && (
                <span className="fc-soft-bg fc-primary-text rounded-full px-2 py-0.5 text-[11px] font-semibold">
                  {pixDiscount}% off
                </span>
              )}
              <ChevronDownIcon
                className={`h-4 w-4 shrink-0 text-slate-400 transition-transform ${isActive ? 'rotate-180' : ''}`}
              />
            </button>

            {isActive && (
              <div className="px-4 pb-4">
                <GatewayDetail gateway={gateway} />
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}

function GatewayIcon({ gateway }) {
  if (gateway.icon_url) {
    return <img src={gateway.icon_url} alt="" className="h-6 w-auto max-w-full object-contain" />;
  }

  if (gateway.kind === 'pix') {
    return <PixIcon className="h-6 w-6 fc-primary-text" />;
  }

  if (gateway.kind === 'boleto') {
    return <BarcodeIcon className="h-6 w-6 text-slate-800" />;
  }

  return <CreditCardIcon className="h-6 w-6 text-slate-800" />;
}

// Larger icon for the detail panel: prefer the integration-supplied icon, fall
// back to the built-in kind glyph passed by the caller.
function DetailIcon({ gateway, fallback }) {
  if (gateway.icon_url) {
    return <img src={gateway.icon_url} alt="" className="mx-auto block h-10 w-auto max-w-[8rem] object-contain" />;
  }

  return fallback;
}

function GatewayDetail({ gateway }) {
  // Card gateways with a WooCommerce Blocks integration (e.g. Mercado Pago
  // credit card) render their own payment component — card fields, SDK,
  // installments — through the Blocks bridge instead of a plain description.
  // Pix / boleto keep Swift's curated panels, which already drive their async flow.
  if (gateway.blocks && gateway.blocks_name && gateway.kind === 'card') {
    return <BlockPaymentSlot gateway={gateway} />;
  }

  if (gateway.kind === 'pix') {
    return (
      <div className="rounded-xl border border-slate-200 px-6 py-6 text-center">
        <DetailIcon gateway={gateway} fallback={<PixIcon className="mx-auto block h-10 w-10 fc-primary-text" />} />
        <p className="mt-2 text-sm font-medium text-slate-800">Pague de forma segura e instantânea</p>
        <p className="mt-1 text-sm text-slate-500">
          Ao confirmar a compra, mostraremos o código para fazer o pagamento.
        </p>
      </div>
    );
  }

  if (gateway.kind === 'boleto') {
    return (
      <div className="rounded-xl border border-slate-200 px-6 py-6 text-center">
        <DetailIcon gateway={gateway} fallback={<BarcodeIcon className="mx-auto block h-10 w-10 text-slate-800" />} />
        <p className="mt-2 text-sm font-medium text-slate-800">Pagamento via boleto</p>
        <p className="mt-1 text-sm text-slate-500">
          Ao confirmar a compra, você receberá o código do boleto por e-mail.
        </p>
      </div>
    );
  }

  if (gateway.description) {
    return (
      <div
        className="rounded-xl border border-slate-200 px-4 py-4 text-sm text-slate-500"
        dangerouslySetInnerHTML={{ __html: gateway.description }}
      />
    );
  }

  return null;
}

/**
 * Host for a gateway's WooCommerce Blocks payment component. The component is
 * rendered with WordPress's React (`wp.element`) into a DOM node this React tree
 * only provides — see lib/blocksPaymentBridge.js. We mount on select, refresh the
 * billing prop on cart-total changes (so amount-dependent fields re-init), and
 * unmount on cleanup. In the live builder preview we never load a real gateway.
 */
function BlockPaymentSlot({ gateway }) {
  const { cart } = useCheckout();
  const containerRef = useRef(null);
  // Keep the latest cart available to the bridge without remounting the gateway.
  const cartRef = useRef(cart);
  cartRef.current = cart;

  const total = cart?.totals?.total_price;

  useEffect(() => {
    if (isEditor()) {
      return undefined;
    }

    const container = containerRef.current;

    if (!container || !blocksRuntimeReady()) {
      return undefined;
    }

    mountBlocksGateway(container, gateway.blocks_name, {
      getCart: () => cartRef.current,
    }).catch(() => {});

    return () => {
      unmountBlocksGateway();
    };
    // Remount when the selected gateway changes.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [gateway.blocks_name]);

  // Re-render the gateway with the new total so it can recompute installments /
  // re-initialize its card form for the updated amount.
  useEffect(() => {
    if (!isEditor()) {
      refreshActiveBilling();
    }
  }, [total]);

  if (isEditor()) {
    return (
      <div className="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-400">
        {gateway.title}
      </div>
    );
  }

  // Render the mount node as a form carrying the WooCommerce Blocks checkout-form
  // classes. Gateway runtimes (e.g. Mercado Pago) locate the checkout form by that
  // exact selector to attach their card form; without it they fail with
  // "No checkout form found". onSubmit is neutralized — submission is driven by the
  // Swift place-order button, not a native form submit.
  return (
    <form
      ref={containerRef}
      className="wc-block-components-form wc-block-checkout__form fc-blocks-payment"
      onSubmit={(e) => e.preventDefault()}
      noValidate
    />
  );
}
