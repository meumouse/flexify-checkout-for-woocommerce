import config from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import SplitPaymentSlot from './SplitPaymentSlot.jsx';

/**
 * Payment gateway selector + split payment slot. Semantic chrome rendered
 * inside a payment-type step.
 */
export default function PaymentMethods() {
  const ctx = useCheckout();
  const { selectedGateway, setSelectedGateway } = ctx;
  const gateways = (config.config && config.config.gateways) || [];

  return (
    <div className="space-y-5">
      {gateways.length === 0 ? (
        <p className="text-sm text-slate-500">Nenhuma forma de pagamento disponível.</p>
      ) : (
        <div className="space-y-2">
          {gateways.map((gateway) => (
            <label
              key={gateway.id}
              className={`block cursor-pointer rounded-lg border px-4 py-3 ${
                selectedGateway === gateway.id ? 'border-primary ring-2 ring-primary-100' : 'border-slate-200'
              }`}
            >
              <span className="flex items-center gap-2">
                <input
                  type="radio"
                  name="payment-method"
                  checked={selectedGateway === gateway.id}
                  onChange={() => setSelectedGateway(gateway.id)}
                />
                {gateway.icon_url && <img src={gateway.icon_url} alt="" className="h-5" />}
                <span className="text-sm font-medium text-slate-800">{gateway.title}</span>
              </span>
              {gateway.description && (
                <span
                  className="mt-1 block pl-6 text-xs text-slate-500"
                  dangerouslySetInnerHTML={{ __html: gateway.description }}
                />
              )}
            </label>
          ))}
        </div>
      )}

      <SplitPaymentSlot ctx={ctx} />
    </div>
  );
}
