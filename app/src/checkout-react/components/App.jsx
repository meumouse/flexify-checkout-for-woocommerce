import { useState } from 'react';
import config, { t } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import OrderSummary from './OrderSummary.jsx';
import ContactStep from './steps/ContactStep.jsx';
import ShippingStep from './steps/ShippingStep.jsx';
import PaymentStep from './steps/PaymentStep.jsx';

const STEP_LABELS = [
  t('contact', 'Contato'),
  t('shipping', 'Entrega'),
  t('payment', 'Pagamento'),
];

export default function App() {
  const { loading, busy, error, cart, updateAddress, placeOrder } = useCheckout();
  const [step, setStep] = useState(1);

  const needsShipping = !cart || cart.needs_shipping;
  const steps = needsShipping ? [1, 2, 3] : [1, 3];
  const isLast = step === 3;

  const goNext = async () => {
    if (step === 2) {
      // Sync the typed address so totals/shipping reflect it before payment.
      try {
        await updateAddress({});
      } catch (e) {
        return;
      }
    }

    const idx = steps.indexOf(step);
    setStep(steps[Math.min(idx + 1, steps.length - 1)]);
  };

  const goBack = () => {
    const idx = steps.indexOf(step);
    setStep(steps[Math.max(idx - 1, 0)]);
  };

  if (loading) {
    return <div className="p-10 text-center text-slate-500">{t('loading', 'Carregando…')}</div>;
  }

  if (cart && (cart.items || []).length === 0) {
    return (
      <div className="mx-auto max-w-md p-10 text-center">
        <p className="text-slate-600">{t('empty_cart', 'Seu carrinho está vazio.')}</p>
        <a href={config.urls?.shop || '/'} className="mt-4 inline-block rounded-lg fc-primary-bg px-5 py-2.5 text-sm font-semibold text-white">
          {t('back', 'Voltar à loja')}
        </a>
      </div>
    );
  }

  return (
    <div className="mx-auto grid max-w-6xl grid-cols-1 gap-8 p-4 lg:grid-cols-[minmax(0,1fr)_380px] lg:p-8">
      <div>
        <ol className="mb-6 flex items-center gap-2 text-sm">
          {steps.map((s, i) => (
            <li key={s} className="flex items-center gap-2">
              <span
                className={`flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold ${
                  s === step ? 'fc-primary-bg text-white' : 'bg-slate-100 text-slate-500'
                }`}
              >
                {i + 1}
              </span>
              <span className={s === step ? 'font-semibold text-slate-800' : 'text-slate-500'}>
                {STEP_LABELS[s - 1]}
              </span>
              {i < steps.length - 1 && <span className="mx-1 text-slate-300">→</span>}
            </li>
          ))}
        </ol>

        <div className="rounded-xl border border-slate-100 bg-white p-5 shadow-soft sm:p-6">
          {step === 1 && <ContactStep />}
          {step === 2 && <ShippingStep />}
          {step === 3 && <PaymentStep />}

          {error && <p className="mt-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-danger">{error}</p>}

          <div className="mt-6 flex items-center justify-between">
            {step !== steps[0] ? (
              <button type="button" className="text-sm font-medium text-slate-500 hover:underline" onClick={goBack}>
                ← {t('back', 'Voltar')}
              </button>
            ) : (
              <span />
            )}

            {isLast ? (
              <button
                type="button"
                disabled={busy}
                className="rounded-lg fc-primary-bg px-6 py-3 text-sm font-semibold text-white disabled:opacity-60"
                onClick={() => placeOrder()}
              >
                {busy ? t('loading', 'Carregando…') : t('place_order', 'Finalizar compra')}
              </button>
            ) : (
              <button
                type="button"
                disabled={busy}
                className="rounded-lg fc-primary-bg px-6 py-3 text-sm font-semibold text-white disabled:opacity-60"
                onClick={goNext}
              >
                {t('continue', 'Continuar')}
              </button>
            )}
          </div>
        </div>
      </div>

      <aside className="lg:sticky lg:top-8 lg:self-start">
        <OrderSummary />
      </aside>
    </div>
  );
}
