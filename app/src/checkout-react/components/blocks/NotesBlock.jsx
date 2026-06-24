import { useCheckout } from '../../context/CheckoutContext.jsx';

/**
 * Order notes block, bound to the checkout customer note.
 *
 * @param {{config:object}} props Block config.
 */
export default function NotesBlock({ config = {} }) {
  const { customerNote, setCustomerNote } = useCheckout();
  const label = config.label || 'Observações do pedido';

  return (
    <div>
      <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="fc-customer-note">
        {label}
        {config.required && <span className="text-danger"> *</span>}
      </label>
      <textarea
        id="fc-customer-note"
        className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary-100"
        rows={3}
        required={!!config.required}
        placeholder={config.placeholder || ''}
        value={customerNote}
        onChange={(e) => setCustomerNote(e.target.value)}
      />
    </div>
  );
}
