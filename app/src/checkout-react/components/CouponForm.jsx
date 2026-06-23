import { useState } from 'react';
import { t } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';

export default function CouponForm() {
  const { cart, applyCoupon, removeCoupon, busy } = useCheckout();
  const [code, setCode] = useState('');
  const coupons = (cart && cart.coupons) || [];

  const submit = async (e) => {
    e.preventDefault();

    if (!code.trim()) return;

    try {
      await applyCoupon(code.trim());
      setCode('');
    } catch (err) {
      /* error surfaced by context */
    }
  };

  return (
    <div className="mt-4">
      <form className="flex gap-2" onSubmit={submit}>
        <input
          className="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary-100"
          type="text"
          placeholder={t('coupon_placeholder', 'Cupom de desconto')}
          value={code}
          onChange={(e) => setCode(e.target.value)}
        />
        <button
          type="submit"
          disabled={busy}
          className="rounded-lg fc-primary-bg px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
        >
          {t('apply', 'Aplicar')}
        </button>
      </form>

      {coupons.length > 0 && (
        <ul className="mt-2 space-y-1">
          {coupons.map((c) => (
            <li key={c.code} className="flex items-center justify-between text-sm text-slate-600">
              <span>🏷️ {c.code}</span>
              <button type="button" className="text-danger hover:underline" onClick={() => removeCoupon(c.code)}>
                ✕
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
