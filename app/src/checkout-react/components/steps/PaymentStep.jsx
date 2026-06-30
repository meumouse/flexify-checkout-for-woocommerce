import { t } from '../../config.js';
import { useCheckout } from '../../context/CheckoutContext.jsx';
import { couponBeforePayment } from '../../lib/coupon.js';
import CouponForm from '../CouponForm.jsx';
import PaymentMethods from '../PaymentMethods.jsx';
import StepSummary from '../StepSummary.jsx';

export default function PaymentStep({ onEdit }) {
  const { customerNote, setCustomerNote } = useCheckout();

  return (
    <div className="space-y-6">
      <StepSummary sections={['contact', 'shipping', 'frete']} onEdit={onEdit} />

      {couponBeforePayment() && <CouponForm />}

      <h2 className="fc-heading text-xl text-slate-800">
        {t('payment_methods', 'Formas de pagamento')}
      </h2>

      <PaymentMethods />

      <div>
        <label className="mb-1.5 block text-sm font-medium text-slate-700" htmlFor="fc-customer-note">
          {t('order_notes', 'Observações do pedido')}
        </label>
        <textarea
          id="fc-customer-note"
          className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary-100"
          rows={3}
          value={customerNote}
          onChange={(e) => setCustomerNote(e.target.value)}
        />
      </div>
    </div>
  );
}
