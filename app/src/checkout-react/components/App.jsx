import { useEffect, useState } from 'react';
import config, { t } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import { hasLayout, layoutSteps } from '../lib/layout.js';
import { isEditor, onParentMessage, emitReady, emitSelect } from '../lib/editorBridge.js';
import { MESSAGE_PREFIX } from '../lib/editorBridge.js';
import OrderSummary from './OrderSummary.jsx';
import StepRenderer from './StepRenderer.jsx';
import ContactStep from './steps/ContactStep.jsx';
import ShippingStep from './steps/ShippingStep.jsx';
import PaymentStep from './steps/PaymentStep.jsx';

const STEP_LABELS = [
  t('contact', 'Contato'),
  t('shipping', 'Entrega'),
  t('payment', 'Pagamento'),
];

/**
 * Root checkout. Uses the visual builder layout when one is published,
 * otherwise renders the default hardcoded three-step flow.
 */
export default function App() {
  if (isEditor()) {
    return <EditorApp />;
  }

  return hasLayout() ? <BuilderCheckout /> : <DefaultCheckout />;
}

/**
 * Live builder editor: renders the real checkout driven by the layout pushed
 * from the admin builder (postMessage), with selectable fields/components and
 * no real order placement. Cart-dependent blocks degrade gracefully.
 */
function EditorApp() {
  const [layout, setLayout] = useState(() => config.rules?.layout || { version: 1, steps: [] });
  const [activeStepId, setActiveStepId] = useState('');
  const [selected, setSelected] = useState(null);

  useEffect(() => {
    const off = onParentMessage((msg) => {
      const kind = msg.type.slice(MESSAGE_PREFIX.length);

      if (kind === 'layout' && msg.layout) {
        setLayout(msg.layout);
      } else if (kind === 'step') {
        setActiveStepId(msg.stepId || '');
      } else if (kind === 'select') {
        setSelected(msg.target || null);
      }
    });

    emitReady();

    return off;
  }, []);

  const steps = (layout.steps || [])
    .filter((step) => step.enabled !== false)
    .slice()
    .sort((a, b) => Number(a.order || 0) - Number(b.order || 0));

  if (!steps.length) {
    return <div className="p-10 text-center text-slate-500">Adicione uma etapa para começar.</div>;
  }

  const active = steps.find((step) => step.id === activeStepId) || steps[0];
  const activeIndex = steps.indexOf(active);
  const isLast = activeIndex === steps.length - 1;

  const goTo = (index) => {
    const next = steps[Math.max(0, Math.min(index, steps.length - 1))];

    if (next) {
      setActiveStepId(next.id);
    }
  };

  return (
    <div className="fc-editor-mode">
      <CheckoutShell
        stepLabels={steps.map((step, i) => step.label || STEP_LABELS[i] || `Etapa ${i + 1}`)}
        activeIndex={activeIndex}
        isLast={isLast}
        busy={false}
        error=""
        onBack={() => goTo(activeIndex - 1)}
        onNext={() => goTo(activeIndex + 1)}
        onPlaceOrder={() => {}}
      >
        <div onClick={() => emitSelect({ scope: 'step', stepId: active.id })}>
          <StepRenderer step={active} editor selected={selected} />
        </div>
      </CheckoutShell>
    </div>
  );
}

/**
 * Shared shell: stepper + step body + nav buttons + sticky order summary.
 */
function CheckoutShell({ stepLabels, activeIndex, isLast, busy, error, onBack, onNext, onPlaceOrder, children }) {
  return (
    <div className="mx-auto grid max-w-6xl grid-cols-1 gap-8 p-4 lg:grid-cols-[minmax(0,1fr)_380px] lg:p-8">
      <div>
        <ol className="mb-6 flex items-center gap-2 text-sm">
          {stepLabels.map((label, i) => (
            <li key={label + i} className="flex items-center gap-2">
              <span
                className={`flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold ${
                  i === activeIndex ? 'fc-primary-bg text-white' : 'bg-slate-100 text-slate-500'
                }`}
              >
                {i + 1}
              </span>
              <span className={i === activeIndex ? 'font-semibold text-slate-800' : 'text-slate-500'}>{label}</span>
              {i < stepLabels.length - 1 && <span className="mx-1 text-slate-300">→</span>}
            </li>
          ))}
        </ol>

        <div className="rounded-xl border border-slate-100 bg-white p-5 shadow-soft sm:p-6">
          {children}

          {error && <p className="mt-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-danger">{error}</p>}

          <div className="mt-6 flex items-center justify-between">
            {activeIndex !== 0 ? (
              <button type="button" className="text-sm font-medium text-slate-500 hover:underline" onClick={onBack}>
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
                onClick={onPlaceOrder}
              >
                {busy ? t('loading', 'Carregando…') : t('place_order', 'Finalizar compra')}
              </button>
            ) : (
              <button
                type="button"
                disabled={busy}
                className="rounded-lg fc-primary-bg px-6 py-3 text-sm font-semibold text-white disabled:opacity-60"
                onClick={onNext}
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

function LoadingOrEmpty({ loading, cart }) {
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

  return null;
}

/**
 * Builder-driven checkout: steps + items come from config.rules.layout.
 */
function BuilderCheckout() {
  const { loading, busy, error, cart, updateAddress, placeOrder } = useCheckout();
  const [index, setIndex] = useState(0);

  const guard = <LoadingOrEmpty loading={loading} cart={cart} />;

  const needsShipping = !cart || cart.needs_shipping;
  let steps = layoutSteps();

  if (!needsShipping) {
    steps = steps.filter((step) => step.type !== 'shipping');
  }

  if (!steps.length) {
    return <DefaultCheckout />;
  }

  const activeIndex = Math.min(index, steps.length - 1);
  const current = steps[activeIndex];
  const isLast = activeIndex === steps.length - 1;

  const goNext = async () => {
    const next = steps[Math.min(activeIndex + 1, steps.length - 1)];

    // Sync the typed address so totals/shipping reflect it before payment.
    if (next && next.type === 'payment') {
      try {
        await updateAddress({});
      } catch (e) {
        return;
      }
    }

    setIndex(Math.min(activeIndex + 1, steps.length - 1));
  };

  const goBack = () => setIndex(Math.max(activeIndex - 1, 0));

  if (loading || (cart && (cart.items || []).length === 0)) {
    return guard;
  }

  return (
    <CheckoutShell
      stepLabels={steps.map((step, i) => step.label || STEP_LABELS[i] || `Etapa ${i + 1}`)}
      activeIndex={activeIndex}
      isLast={isLast}
      busy={busy}
      error={error}
      onBack={goBack}
      onNext={goNext}
      onPlaceOrder={() => placeOrder()}
    >
      <StepRenderer step={current} />
    </CheckoutShell>
  );
}

/**
 * Default checkout: the original hardcoded three-step flow (fallback when no
 * builder layout is published).
 */
function DefaultCheckout() {
  const { loading, busy, error, cart, updateAddress, placeOrder } = useCheckout();
  const [step, setStep] = useState(1);

  const needsShipping = !cart || cart.needs_shipping;
  const steps = needsShipping ? [1, 2, 3] : [1, 3];
  const activeIndex = steps.indexOf(step);
  const isLast = step === 3;

  const goNext = async () => {
    if (step === 2) {
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

  if (loading || (cart && (cart.items || []).length === 0)) {
    return <LoadingOrEmpty loading={loading} cart={cart} />;
  }

  return (
    <CheckoutShell
      stepLabels={steps.map((s) => STEP_LABELS[s - 1])}
      activeIndex={activeIndex}
      isLast={isLast}
      busy={busy}
      error={error}
      onBack={goBack}
      onNext={goNext}
      onPlaceOrder={() => placeOrder()}
    >
      {step === 1 && <ContactStep />}
      {step === 2 && <ShippingStep />}
      {step === 3 && <PaymentStep />}
    </CheckoutShell>
  );
}
