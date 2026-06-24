import { t } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import { formatPrice } from '../lib/format.js';
import { PackageIcon, CheckIcon } from './ui/Icons.jsx';

/**
 * Shipping rate selector. Rendered inside the shipping step (default + builder).
 */
export default function ShippingRates() {
  const { cart, selectShippingRate, busy } = useCheckout();
  const totals = (cart && cart.totals) || {};
  const packages = (cart && cart.shipping_rates) || [];

  if (!cart || !cart.needs_shipping || packages.length === 0) {
    return null;
  }

  return (
    <div>
      <h3 className="mb-3 flex items-center gap-2 text-sm font-medium text-slate-800">
        <PackageIcon className="h-4 w-4 fc-primary-text" />
        {t('shipping_methods', 'Formas de entrega')}
      </h3>

      {packages.map((pkg) => (
        <div key={pkg.package_id} className="space-y-3">
          {(pkg.shipping_rates || []).map((rate) => {
            const selected = !!rate.selected;
            const free = Number(rate.price) === 0;

            return (
              <label
                key={rate.rate_id}
                className={`flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition-colors ${
                  selected ? 'fc-primary-border' : 'border-slate-200 hover:border-slate-300'
                }`}
              >
                <input
                  type="radio"
                  className="sr-only"
                  name={`shipping-${pkg.package_id}`}
                  checked={selected}
                  disabled={busy}
                  onChange={() => selectShippingRate(pkg.package_id, rate.rate_id)}
                />

                {selected && (
                  <span className="fc-primary-bg flex h-6 w-6 shrink-0 items-center justify-center rounded-full">
                    <CheckIcon className="h-3.5 w-3.5 text-white" />
                  </span>
                )}

                <p className="flex-1 text-sm">
                  <span className="font-semibold text-slate-800">{rate.name}</span>
                  {!free && <span className="text-slate-700">: {formatPrice(rate.price, totals)}</span>}
                </p>
              </label>
            );
          })}
        </div>
      ))}
    </div>
  );
}
