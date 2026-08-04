import { useEffect, useState } from 'react';
import config, { t, setTextOverrides, setSettingsOverrides } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import { builderStepFields, fieldsForStep } from '../lib/fields.js';
import { validateFields } from '../lib/validation.js';
import { hasLayout, layoutSteps, setFieldOverrides } from '../lib/layout.js';
import { useStepRouter } from '../lib/stepUrl.js';
import { useFunnelBeacon } from '../lib/funnelBeacon.js';
import { isEditor, onParentMessage, emitReady, emitSelect } from '../lib/editorBridge.js';
import { MESSAGE_PREFIX } from '../lib/editorBridge.js';
import { applyTheme } from '../lib/theme.js';
import { formatPrice } from '../lib/format.js';
import OrderSummary from './OrderSummary.jsx';
import CheckoutSkeleton from './CheckoutSkeleton.jsx';
import PurchaseAnimation from './PurchaseAnimation.jsx';
import OrderLoadingOverlay from './OrderLoadingOverlay.jsx';
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

// URL `?step` slug for each default-checkout step number (contact/shipping/payment).
const DEFAULT_STEP_SLUGS = { 1: 'contact', 2: 'shipping', 3: 'payment' };

// URL `?step` slug for a builder step (its semantic type, with the id as fallback).
const builderStepSlug = (step) => step.type || step.id;

/**
 * Validate a step's fields before advancing. On failure, publishes the inline
 * errors, scrolls/focuses the first invalid field, and returns false so the
 * caller can abort navigation.
 *
 * @param {Array<object>} fields         Field definitions to validate.
 * @param {object}        billing        Standard address values.
 * @param {object}        extraFields    Flexify-managed extra field values.
 * @param {Function}      setFieldErrors Context setter for the error map.
 * @returns {boolean} True when the step is valid.
 */
function gateStep(fields, billing, extraFields, setFieldErrors) {
  const errors = validateFields(fields, billing, extraFields);
  setFieldErrors(errors);

  const firstInvalid = Object.keys(errors)[0];

  if (firstInvalid) {
    const el = typeof document !== 'undefined' && document.getElementById(`fc-${firstInvalid}`);

    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });

      if (typeof el.focus === 'function') {
        el.focus({ preventScroll: true });
      }
    }

    return false;
  }

  return true;
}

/**
 * Whether the checkout should collect a delivery address for this cart.
 *
 * WooCommerce's cart-level `needs_shipping` flips to false whenever no shipping
 * method is configured — even for physical products — which would wrongly drop
 * the delivery step. Mirror the plugin's legacy semantics instead: only hide
 * shipping when the operator opted into digital-product optimization (Pro), and
 * in that case defer to WooCommerce's flag (false for virtual-only carts).
 * Otherwise the delivery step always shows, regardless of shipping methods.
 *
 * @param {object|null} cart Store API cart (null while loading).
 * @returns {boolean}
 */
function cartNeedsShipping(cart) {
  if (!cart) {
    return true;
  }

  if (config.flags && config.flags.optimize_digital) {
    return !!cart.needs_shipping;
  }

  return true;
}

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
    <div className="fc-shell mx-auto px-4 pt-8 lg:px-8">
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
                  done ? 'bg-success text-white' : '',
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
        className="fc-mobile-cart-bar fixed inset-x-0 bottom-0 z-40 flex items-center justify-between gap-3 rounded-t-2xl px-5 pb-[max(1rem,env(safe-area-inset-bottom))] pt-4 text-base font-medium text-slate-900 lg:hidden"
      >
        <span className="flex items-center gap-2">
          <BasketIcon className="h-5 w-5 fc-primary-text" />
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
      } else if (kind === 'theme') {
        applyTheme(msg.theme || null);
      } else if (kind === 'texts') {
        // Live text edits: override the i18n strings and force a re-render so
        // headings / labels / buttons reflect instantly in the preview.
        setTextOverrides(msg.texts || null);
        setFieldsRev((n) => n + 1);
      } else if (kind === 'settings') {
        // Live checkout-setting edits (e.g. payment methods layout): override
        // and force a re-render so the change reflects instantly in the preview.
        setSettingsOverrides(msg.settings || null);
        setFieldsRev((n) => n + 1);
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
function CheckoutShell({ stepLabels, activeIndex, isLast, busy, onBack, onNext, onPlaceOrder, children }) {
  const { cart, placingOrder } = useCheckout();
  const totals = (cart && cart.totals) || {};
  const nextStepLabel = stepLabels[activeIndex + 1] || '';
  const nextLabel = nextStepLabel
    ? `${t('continue', 'Continuar')} para ${nextStepLabel.toLowerCase()}`
    : t('continue', 'Continuar');
  const atFirstStep = activeIndex === 0;
  const backLabel = atFirstStep ? t('back_to_shop', 'Voltar à loja') : t('back', 'Voltar');
  const shopUrl = config.urls?.shop || config.urls?.cart || '/';
  const payLabel = `${t('pay', 'Pagar')} ${formatPrice(totals.total_price, totals)}`;

  return (
    <div className="pb-10">
      <ReservationBar />
      <CheckoutHeader />

      <div className="fc-shell mx-auto grid grid-cols-1 gap-8 px-4 pt-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:px-8">
        {/*
         * Real checkout form. Gateway runtimes (e.g. Mercado Pago's custom-checkout
         * script) locate the checkout by form[name=checkout] at page load and error
         * ("No checkout form found") without it. Exposing a persistent form here —
         * mirroring the classic WooCommerce checkout — keeps them compatible
         * regardless of step or selected gateway. Native submission is neutralized;
         * order placement is driven by the place-order button below.
         */}
        <form
          name="checkout"
          id="checkout"
          className="fc-checkout-form min-w-0"
          onSubmit={(e) => e.preventDefault()}
          noValidate
        >
          <Stepper stepLabels={stepLabels} activeIndex={activeIndex} />

          <div>
            {children}

            <div className="mt-8 flex items-center justify-between border-t border-slate-100 pt-6">
              {atFirstStep ? (
                <a
                  href={shopUrl}
                  className="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition-colors hover:text-slate-800"
                >
                  <ArrowLeftIcon className="h-4 w-4" />
                  {backLabel}
                </a>
              ) : (
                <button
                  type="button"
                  className="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition-colors hover:text-slate-800"
                  onClick={onBack}
                >
                  <ArrowLeftIcon className="h-4 w-4" />
                  {backLabel}
                </button>
              )}

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
        </form>

        <aside className="hidden lg:sticky lg:top-8 lg:block lg:self-start">
          <div className="rounded-2xl border border-slate-200 bg-white p-6">
            <OrderSummary />
          </div>
        </aside>
      </div>

      <MobileCartSheet />

      {/*
       * Order-finalization loader. The modern overlay is the default; the legacy
       * Lordicon animation only takes over when the operator explicitly enables it.
       */}
      {config.purchase_animation && config.purchase_animation.enabled ? (
        <PurchaseAnimation active={placingOrder} />
      ) : (
        <OrderLoadingOverlay active={placingOrder} />
      )}
    </div>
  );
}

function LoadingOrEmpty({ loading, cart }) {
  if (loading) {
    return <CheckoutSkeleton />;
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
  const { loading, busy, cart, billing, extraFields, setFieldErrors, updateAddress, placeOrder } = useCheckout();

  const guard = <LoadingOrEmpty loading={loading} cart={cart} />;

  const needsShipping = cartNeedsShipping(cart);
  let steps = layoutSteps();

  if (!needsShipping) {
    steps = steps.filter((step) => step.type !== 'shipping');
  }

  // Active step persists in the URL (?step=…) so a reload reopens it. Called
  // unconditionally — before any early return — to satisfy the rules of hooks.
  const [activeIndex, setIndex] = useStepRouter(steps.map(builderStepSlug));

  // Report funnel progress (no-op until the active step is a funnel step).
  useFunnelBeacon(steps[activeIndex] ? builderStepSlug(steps[activeIndex]) : '');

  if (!steps.length) {
    return <DefaultCheckout />;
  }

  const current = steps[activeIndex];
  const isLast = activeIndex === steps.length - 1;

  const goNext = async () => {
    // Block navigation while the current step has empty/invalid required fields.
    if (!gateStep(builderStepFields(current), billing, extraFields, setFieldErrors)) {
      return;
    }

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
  const { loading, busy, cart, billing, extraFields, setFieldErrors, updateAddress, placeOrder } = useCheckout();

  const needsShipping = cartNeedsShipping(cart);
  const steps = needsShipping ? [1, 2, 3] : [1, 3];

  // Active step persists in the URL (?step=…) so a reload reopens it.
  const [activeIndex, setIndex] = useStepRouter(steps.map((s) => DEFAULT_STEP_SLUGS[s]));
  const step = steps[activeIndex];
  const isLast = step === 3;

  // Report funnel progress as the shopper advances through the steps.
  useFunnelBeacon(DEFAULT_STEP_SLUGS[step] || '');

  const goNext = async () => {
    // Block navigation while the current step has empty/invalid required fields.
    if (!gateStep(fieldsForStep(step), billing, extraFields, setFieldErrors)) {
      return;
    }

    if (step === 2) {
      try {
        await updateAddress({});
      } catch (e) {
        return;
      }
    }

    setIndex(Math.min(activeIndex + 1, steps.length - 1));
  };

  const goBack = () => setIndex(Math.max(activeIndex - 1, 0));

  const onEdit = (target) => {
    const idx = steps.indexOf(target === 'contact' ? 1 : 2);

    if (idx >= 0) {
      setIndex(idx);
    }
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
