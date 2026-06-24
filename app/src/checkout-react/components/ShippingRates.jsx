import config from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import { formatPrice } from '../lib/format.js';

/**
 * Shipping rate selector. Semantic chrome rendered inside a shipping-type step.
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
      <h3 className="mb-2 text-sm font-semibold text-slate-700">{config.i18n?.shipping || 'Entrega'}</h3>
      {packages.map((pkg) => (
        <div key={pkg.package_id} className="space-y-2">
          {(pkg.shipping_rates || []).map((rate) => (
            <label
              key={rate.rate_id}
              className={`flex cursor-pointer items-center justify-between rounded-lg border px-4 py-3 text-sm ${
                rate.selected ? 'border-primary ring-2 ring-primary-100' : 'border-slate-200'
              }`}
            >
              <span className="flex items-center gap-2">
                <input
                  type="radio"
                  name={`shipping-${pkg.package_id}`}
                  checked={!!rate.selected}
                  disabled={busy}
                  onChange={() => selectShippingRate(pkg.package_id, rate.rate_id)}
                />
                <span className="font-medium text-slate-700">{rate.name}</span>
              </span>
              <span className="text-slate-600">{formatPrice(rate.price, totals)}</span>
            </label>
          ))}
        </div>
      ))}
    </div>
  );
}
