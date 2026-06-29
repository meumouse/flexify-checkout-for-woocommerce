import config from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import { resolveAvailableGateways } from '../lib/gateways.js';
import SplitPaymentSlot from './SplitPaymentSlot.jsx';
import { PixIcon, CreditCardIcon, BarcodeIcon } from './ui/Icons.jsx';

/**
 * Payment method selector rendered as a card grid (Pix / Cartão / Boleto…) with
 * a detail panel for the selected method. Rendered inside the payment step.
 *
 * The methods shown come from the live Store API cart availability, not the
 * static page-load catalog, so conditional gateways update as the cart changes.
 */
export default function PaymentMethods() {
  const ctx = useCheckout();
  const { cart, selectedGateway, setSelectedGateway } = ctx;
  const gateways = resolveAvailableGateways(cart);
  const pixDiscount = Number(config.settings?.pix_discount_percent || 0);

  if (gateways.length === 0) {
    return <p className="text-sm text-slate-500">Nenhuma forma de pagamento disponível.</p>;
  }

  const gridCols = gateways.length >= 3 ? 'grid-cols-3' : gateways.length === 2 ? 'grid-cols-2' : 'grid-cols-1';
  const active = gateways.find((g) => g.id === selectedGateway) || gateways[0];

  return (
    <div className="space-y-5">
      <div className={`grid gap-3 ${gridCols}`}>
        {gateways.map((gateway) => {
          const isActive = active && gateway.id === active.id;

          return (
            <button
              key={gateway.id}
              type="button"
              onClick={() => setSelectedGateway(gateway.id)}
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

      <SplitPaymentSlot ctx={ctx} />
    </div>
  );
}

function GatewayIcon({ gateway }) {
  if (gateway.icon_url) {
    return <img src={gateway.icon_url} alt="" className="h-6 w-auto" />;
  }

  if (gateway.kind === 'pix') {
    return <PixIcon className="h-6 w-6 fc-primary-text" />;
  }

  if (gateway.kind === 'boleto') {
    return <BarcodeIcon className="h-6 w-6 text-slate-800" />;
  }

  return <CreditCardIcon className="h-6 w-6 text-slate-800" />;
}

function GatewayDetail({ gateway }) {
  if (gateway.kind === 'pix') {
    return (
      <div className="rounded-xl border border-slate-200 px-6 py-6 text-center">
        <PixIcon className="mx-auto block h-10 w-10 fc-primary-text" />
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
        <BarcodeIcon className="mx-auto block h-10 w-10 text-slate-800" />
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
