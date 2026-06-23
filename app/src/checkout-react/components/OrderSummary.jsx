import { t } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import { formatPrice } from '../lib/format.js';
import CouponForm from './CouponForm.jsx';

export default function OrderSummary() {
  const { cart } = useCheckout();

  if (!cart) {
    return null;
  }

  const totals = cart.totals || {};
  const items = cart.items || [];

  return (
    <section className="rounded-xl border border-slate-100 bg-white p-5 shadow-soft">
      <h2 className="mb-4 text-base font-semibold text-slate-800">{t('order_summary', 'Resumo do pedido')}</h2>

      {items.length === 0 ? (
        <p className="text-sm text-slate-500">{t('empty_cart', 'Seu carrinho está vazio.')}</p>
      ) : (
        <ul className="space-y-3">
          {items.map((item) => (
            <li key={item.key} className="flex gap-3">
              {item.images && item.images[0] && (
                <img
                  src={item.images[0].thumbnail}
                  alt={item.name}
                  className="h-12 w-12 rounded-lg object-cover"
                />
              )}
              <div className="flex-1">
                <p className="text-sm font-medium text-slate-800">{item.name}</p>
                <p className="text-xs text-slate-500">× {item.quantity}</p>
              </div>
              <span className="text-sm text-slate-700">
                {formatPrice(item.totals?.line_total, totals)}
              </span>
            </li>
          ))}
        </ul>
      )}

      <CouponForm />

      <div className="mt-4 space-y-1 border-t border-slate-100 pt-4 text-sm">
        <Row label="Subtotal" value={formatPrice(totals.total_items, totals)} />
        {Number(totals.total_discount) > 0 && (
          <Row label="Desconto" value={`- ${formatPrice(totals.total_discount, totals)}`} />
        )}
        {Number(totals.total_shipping) > 0 && (
          <Row label="Entrega" value={formatPrice(totals.total_shipping, totals)} />
        )}
        <div className="mt-2 flex items-center justify-between border-t border-slate-100 pt-2 text-base font-semibold text-slate-900">
          <span>Total</span>
          <span>{formatPrice(totals.total_price, totals)}</span>
        </div>
      </div>
    </section>
  );
}

function Row({ label, value }) {
  return (
    <div className="flex items-center justify-between text-slate-600">
      <span>{label}</span>
      <span>{value}</span>
    </div>
  );
}
