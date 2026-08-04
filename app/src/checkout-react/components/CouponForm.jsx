import { useState } from 'react';
import { t } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';

export default function CouponForm() {
  const { cart, applyCoupon, removeCoupon, busy } = useCheckout();
  const [code, setCode] = useState('');
  const coupons = (cart && cart.coupons) || [];

  const submit = async () => {
    if (!code.trim()) return;

    try {
      await applyCoupon(code.trim());
      setCode('');
    } catch (err) {
      /* error surfaced by context */
    }
  };

  // Not a <form>: the whole checkout is wrapped in a single form[name=checkout]
  // (for gateway-script compatibility) and HTML forbids nested forms. Apply on
  // button click or Enter, swallowing Enter so it does not submit the outer form.
  return (
    <div>
      <div className="flex gap-2">
        <input
          className="h-12 flex-1 rounded-lg border border-slate-200 px-4 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary-100"
          type="text"
          placeholder={t('coupon_placeholder', 'Cupom de desconto')}
          value={code}
          onChange={(e) => setCode(e.target.value)}
          onKeyDown={(e) => {
            if (e.key === 'Enter') {
              e.preventDefault();
              submit();
            }
          }}
        />
        <button
          type="button"
          disabled={busy || !code.trim()}
          onClick={submit}
          className="fc-soft-bg fc-primary-text rounded-lg px-6 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-60"
        >
          {t('apply', 'Aplicar')}
        </button>
      </div>

      {coupons.length > 0 && (
        <ul className="mt-3 space-y-2">
          {coupons.map((c) => (
            <li
              key={c.code}
              className="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2 text-sm"
            >
              <span className="font-medium text-slate-700">{c.code}</span>
              <button
                type="button"
                className="text-slate-400 transition-colors hover:text-danger"
                onClick={() => removeCoupon(c.code)}
              >
                Remover
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
