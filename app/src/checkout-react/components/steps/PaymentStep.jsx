import config from '../../config.js';
import { useCheckout } from '../../context/CheckoutContext.jsx';
import SplitPaymentSlot from '../SplitPaymentSlot.jsx';

export default function PaymentStep() {
  const ctx = useCheckout();
  const { selectedGateway, setSelectedGateway, customerNote, setCustomerNote } = ctx;
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

      <div>
        <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="fc-customer-note">
          Observações do pedido
        </label>
        <textarea
          id="fc-customer-note"
          className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary-100"
          rows={3}
          value={customerNote}
          onChange={(e) => setCustomerNote(e.target.value)}
        />
      </div>
    </div>
  );
}
