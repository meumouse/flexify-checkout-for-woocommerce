import { useEffect, useState } from 'react';
import config, { t } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import { hasLayout, layoutSteps, setFieldOverrides } from '../lib/layout.js';
import { isEditor, onParentMessage, emitReady, emitSelect } from '../lib/editorBridge.js';
import { MESSAGE_PREFIX } from '../lib/editorBridge.js';
import { formatPrice } from '../lib/format.js';
import OrderSummary from './OrderSummary.jsx';
import StepRenderer from './StepRenderer.jsx';
import ContactStep from './steps/ContactStep.jsx';
import ShippingStep from './steps/ShippingStep.jsx';
import PaymentStep from './steps/PaymentStep.jsx';
import { BasketIcon, CheckIcon, ArrowLeftIcon, ChevronUpIcon, CloseIcon } from './ui/Icons.jsx';

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
 * Top reservation / session countdown bar (driven by the checkout countdown
 * settings). Hidden when disabled or when the timer runs out.
 */
function ReservationBar() {
  const reservation = config.reservation || {};
  const [seconds, setSeconds] = useState(() => Math.max(0, Number(reservation.minutes || 0)) * 60);

  useEffect(() => {
    if (!reservation.enabled || seconds <= 0) {
      return undefined;
    }

    const id = setInterval(() => setSeconds((s) => Math.max(0, s - 1)), 1000);

    return () => clearInterval(id);
  }, [reservation.enabled]);

  if (!reservation.enabled || seconds <= 0) {
    return null;
  }

  const mm = String(Math.floor(seconds / 60)).padStart(2, '0');
  const ss = String(seconds % 60).padStart(2, '0');

  return (
    <div className="fc-reservation-bar py-2.5 text-center text-sm sm:text-base">
      {reservation.title || t('reserved_for', 'Seus produtos foram reservados por:')}{' '}
      <span className="ml-1 font-semibold tabular-nums">
        {mm}:{ss}
      </span>
    </div>
  );
}

/**
 * Logo header above the checkout.
 */
function CheckoutHeader() {
  if (!config.logo) {
    return null;
  }

  return (
    <div className="mx-auto max-w-6xl px-4 pt-8 lg:px-8">
      <a href={config.urls?.shop || config.urls?.cart || '/'} className="inline-block">
        <img src={config.logo} alt="" className="h-10 w-auto md:h-12" />
      </a>
    </div>
  );
}

/**
 * Three-state stepper: completed (check), active (dot), upcoming, with dashed
 * connectors. Mirrors the reference storefront design.
 */
function Stepper({ stepLabels, activeIndex }) {
  return (
    <div className="mb-10 flex w-full max-w-[520px] items-start">
      {stepLabels.map((label, i) => {
        const done = i < activeIndex;
        const active = i === activeIndex;

        return (
          <div key={label + i} className="flex min-w-0 flex-1 items-start">
            <div className="flex shrink-0 items-center gap-3">
              <span
                className={[
                  'flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition-colors',
                  done ? 'fc-primary-bg text-white' : '',
                  active ? 'fc-primary-border fc-soft-bg border-2' : '',
                  !done && !active ? 'border-2 border-slate-200 bg-white' : '',
                ].join(' ')}
              >
                {done && <CheckIcon className="h-4 w-4" />}
                {active && <span className="fc-primary-bg h-3 w-3 rounded-full" />}
              </span>

              <span className="flex flex-col leading-tight">
                <span className="text-[10px] uppercase tracking-wider text-slate-400">
                  {t('step', 'Etapa')} {i + 1}
                </span>
                <span className={`text-sm font-semibold ${done || active ? 'text-slate-800' : 'text-slate-400'}`}>
                  {label}
                </span>
              </span>
            </div>

            {i < stepLabels.length - 1 && (
              <span className="mx-3 mt-4 min-w-[1rem] flex-1 border-t border-dashed border-slate-200" />
            )}
          </div>
        );
      })}
    </div>
  );
}

/**
 * Fixed bottom bar + slide-up sheet showing the order summary on mobile.
 */
function MobileCartSheet() {
  const { cart } = useCheckout();
  const [open, setOpen] = useState(false);
  const totals = (cart && cart.totals) || {};

  if (!cart) {
    return null;
  }

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        className="fc-primary-bg fixed inset-x-0 bottom-0 z-40 flex items-center justify-between gap-3 rounded-t-2xl px-5 pb-[max(1rem,env(safe-area-inset-bottom))] pt-4 text-base font-medium text-white lg:hidden"
      >
        <span className="flex items-center gap-2">
          <BasketIcon className="h-5 w-5" />
          {t('view_summary', 'Ver resumo do pedido')}
        </span>
        <span className="flex items-center gap-1.5 font-semibold">
          {formatPrice(totals.total_price, totals)}
          <ChevronUpIcon className="h-4 w-4" />
        </span>
      </button>

      <div aria-hidden="true" className="h-20 lg:hidden" />

      {open && (
        <div className="fixed inset-0 z-50 flex flex-col justify-end lg:hidden">
          <div className="fc-sheet-backdrop absolute inset-0" onClick={() => setOpen(false)} />
          <div className="fc-sheet-panel relative max-h-[82vh] overflow-y-auto rounded-t-2xl bg-white px-5 pb-8 pt-3">
            <div className="mx-auto mb-3 h-1.5 w-10 rounded-full bg-slate-200" />
            <button
              type="button"
              onClick={() => setOpen(false)}
              className="absolute right-4 top-4 text-slate-400 hover:text-slate-600"
              aria-label="Fechar"
            >
              <CloseIcon className="h-5 w-5" />
            </button>
            <OrderSummary />
          </div>
        </div>
      )}
    </>
  );
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
  // Bumped whenever live field overrides change (the overrides live in a module
  // variable, which is not reactive, so we force a re-render explicitly).
  const [, setFieldsRev] = useState(0);

  useEffect(() => {
    const off = onParentMessage((msg) => {
      const kind = msg.type.slice(MESSAGE_PREFIX.length);

      if (kind === 'layout' && msg.layout) {
        setLayout(msg.layout);
        setFieldOverrides(msg.fields || null);
        setFieldsRev((n) => n + 1);
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
 * Shared shell: reservation bar + logo + stepper + step body + nav buttons +
 * sticky order summary (desktop) / bottom sheet (mobile).
 */
function CheckoutShell({ stepLabels, activeIndex, isLast, busy, error, onBack, onNext, onPlaceOrder, children }) {
  const { cart } = useCheckout();
  const totals = (cart && cart.totals) || {};
  const nextStepLabel = stepLabels[activeIndex + 1] || '';
  const nextLabel = nextStepLabel
    ? `${t('continue', 'Continuar')} para ${nextStepLabel.toLowerCase()}`
    : t('continue', 'Continuar');
  const backLabel = activeIndex === 0 ? t('back_to_shop', 'Voltar à loja') : t('back', 'Voltar');
  const payLabel = `${t('pay', 'Pagar')} ${formatPrice(totals.total_price, totals)}`;

  return (
    <div className="pb-10">
      <ReservationBar />
      <CheckoutHeader />

      <div className="mx-auto grid max-w-6xl grid-cols-1 gap-8 px-4 pt-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:px-8">
        <div>
          <Stepper stepLabels={stepLabels} activeIndex={activeIndex} />

          <div>
            {children}

            {error && <p className="mt-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-danger">{error}</p>}

            <div className="mt-8 flex items-center justify-between border-t border-slate-100 pt-6">
              <button
                type="button"
                className="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition-colors hover:text-slate-800"
                onClick={onBack}
              >
                <ArrowLeftIcon className="h-4 w-4" />
                {backLabel}
              </button>

              <button
                type="button"
                disabled={busy}
                className="fc-primary-bg rounded-lg px-6 py-3 text-sm font-semibold text-white transition-colors disabled:opacity-60"
                onClick={isLast ? onPlaceOrder : onNext}
              >
                {busy
                  ? t('loading', 'Carregando…')
                  : isLast
                    ? payLabel
                    : nextLabel}
              </button>
            </div>
          </div>
        </div>

        <aside className="hidden lg:sticky lg:top-8 lg:block lg:self-start">
          <div className="rounded-2xl border border-slate-200 bg-white p-6">
            <OrderSummary />
          </div>
        </aside>
      </div>

      <MobileCartSheet />
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
        <a
          href={config.urls?.shop || '/'}
          className="fc-primary-bg mt-4 inline-block rounded-lg px-5 py-2.5 text-sm font-semibold text-white"
        >
          {t('back_to_shop', 'Voltar à loja')}
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

  const goToType = (type) => {
    const idx = steps.findIndex((step) => step.type === type);

    if (idx >= 0) {
      setIndex(idx);
    }
  };

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
      <StepRenderer step={current} onEdit={goToType} />
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

  const onEdit = (target) => setStep(target === 'contact' ? 1 : 2);

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
      {step === 2 && <ShippingStep onEdit={onEdit} />}
      {step === 3 && <PaymentStep onEdit={onEdit} />}
    </CheckoutShell>
  );
}
