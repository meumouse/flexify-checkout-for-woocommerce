import config from '../../config.js';
import { fieldsForStep } from '../../lib/fields.js';
import { useCheckout } from '../../context/CheckoutContext.jsx';
import { formatPrice } from '../../lib/format.js';
import FieldRenderer from '../FieldRenderer.jsx';
import AddressSearch from '../AddressSearch.jsx';

export default function ShippingStep() {
  const { cart, selectShippingRate, busy } = useCheckout();
  const fields = fieldsForStep(2);
  const addressSearch = config.flags && config.flags.address_search;
  const totals = (cart && cart.totals) || {};
  const packages = (cart && cart.shipping_rates) || [];

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        {addressSearch && <AddressSearch />}
        {fields.map((field) => (
          <FieldRenderer key={field.id} field={field} />
        ))}
      </div>

      {cart && cart.needs_shipping && packages.length > 0 && (
        <div>
          <h3 className="mb-2 text-sm font-semibold text-slate-700">
            {config.i18n?.shipping || 'Entrega'}
          </h3>
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
      )}
    </div>
  );
}
