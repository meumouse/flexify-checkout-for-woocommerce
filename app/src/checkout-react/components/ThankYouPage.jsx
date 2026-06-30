import { useEffect, useRef, useState } from 'react';
import config, { t } from '../config.js';
import { fireConfetti } from '../lib/confetti.js';
import HtmlContent from './HtmlContent.jsx';
import {
  CheckIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  MapPinIcon,
  PackageIcon,
  CalendarIcon,
} from './ui/Icons.jsx';

/**
 * Whether a server-rendered HTML fragment has anything worth showing in a card
 * (visible text or media). Pure-script fragments — e.g. tracking pixels — return
 * false so they don't produce an empty box (they still execute via HtmlContent).
 *
 * @param {string} html
 * @returns {boolean}
 */
function hasVisibleContent(html) {
  if (!html || typeof document === 'undefined') {
    return false;
  }

  const tmp = document.createElement('div');

  tmp.innerHTML = html;
  tmp.querySelectorAll('script, style').forEach((el) => el.remove());

  return tmp.textContent.trim().length > 0 || !!tmp.querySelector('img, iframe, table, form, svg, canvas, a');
}

/**
 * Logo header above the thank-you page (mirrors the checkout header).
 */
function ThankYouHeader() {
  if (!config.logo) {
    return null;
  }

  return (
    <div className="mx-auto max-w-[640px] px-4 pt-8 text-center">
      <a href={config.urls?.shop || config.urls?.cart || '/'} className="inline-block">
        <img src={config.logo} alt="" className="h-10 w-auto md:h-12" />
      </a>
    </div>
  );
}

/**
 * Success hero: success check, personalized greeting, plain order number.
 */
function Hero({ ty }) {
  const name = (ty.first_name || '').trim();
  const greeting = name
    ? t('thankyou_greeting', 'Thank you, %s!').replace('%s', name)
    : t('thankyou_title', 'Thank you for your order!');

  return (
    <div className="text-center">
      <span
        className="mb-5 inline-flex h-16 w-16 items-center justify-center rounded-full"
        style={{
          backgroundColor: 'color-mix(in srgb, var(--fc-success) 15%, #ffffff)',
          color: 'var(--fc-success)',
        }}
      >
        <CheckIcon className="h-8 w-8" />
      </span>

      <h1 className="mb-2 text-2xl font-bold text-slate-900 sm:text-3xl">{greeting}</h1>

      <p className="text-[13px] uppercase tracking-wider text-slate-400">
        {t('order_number', 'Order number')}:{' '}
        <span className="font-semibold text-slate-600">{ty.order_number}</span>
      </p>
    </div>
  );
}

/**
 * A bordered card with an optional collapsible header.
 */
function Card({ title, children, collapsible = false, defaultOpen = true }) {
  const [open, setOpen] = useState(defaultOpen);

  return (
    <div className="rounded-2xl border border-slate-200 bg-white">
      <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <span className="text-base font-bold text-slate-900">{title}</span>
        {collapsible && (
          <button
            type="button"
            onClick={() => setOpen((v) => !v)}
            className="text-slate-400 transition-colors hover:text-slate-600"
            aria-expanded={open}
          >
            {open ? <ChevronUpIcon className="h-5 w-5" /> : <ChevronDownIcon className="h-5 w-5" />}
          </button>
        )}
      </div>

      {(!collapsible || open) && <div className="px-5 py-5">{children}</div>}
    </div>
  );
}

/**
 * Order summary: line items + totals.
 */
function OrderSummaryCard({ ty }) {
  return (
    <Card title={t('order_summary', 'Resumo do pedido')} collapsible>
      <div className="flex flex-col gap-4">
        {ty.items.map((item, i) => (
          <div key={i} className="flex items-start gap-3.5">
            {item.image && (
              <img
                src={item.image}
                alt=""
                className="h-14 w-14 shrink-0 rounded-xl object-cover"
              />
            )}
            <div className="flex min-w-0 flex-1 flex-col gap-0.5">
              <span className="text-sm font-semibold text-slate-900">{item.name}</span>
              {item.meta_html && (
                <span
                  className="text-[13px] text-slate-500 [&_p]:m-0"
                  dangerouslySetInnerHTML={{ __html: item.meta_html }}
                />
              )}
              <span className="text-[13px] text-slate-400">
                {t('qty', 'Qtd')}: {item.quantity}
              </span>
            </div>
            <span
              className="whitespace-nowrap text-sm font-semibold text-slate-900"
              dangerouslySetInnerHTML={{ __html: item.price_html }}
            />
          </div>
        ))}
      </div>

      <div className="mt-5 flex flex-col gap-2.5 border-t border-slate-100 pt-4">
        {ty.totals.map((total) => {
          const grand = total.key === 'order_total';

          return (
            <div
              key={total.key}
              className={[
                'flex items-center justify-between',
                grand
                  ? 'mt-1.5 border-t border-slate-100 pt-3.5 text-[17px] font-bold text-slate-900'
                  : 'text-sm text-slate-500',
              ].join(' ')}
            >
              <span className={grand ? '' : ''}>{total.label}</span>
              <span
                className={grand ? '' : 'font-medium text-slate-700'}
                dangerouslySetInnerHTML={{ __html: total.value_html }}
              />
            </div>
          );
        })}
      </div>
    </Card>
  );
}

/**
 * Delivery details: address, method, and the estimated delivery callout.
 */
function DeliveryCard({ ty }) {
  const { shipping, estimated_delivery: estimate } = ty;
  const hasAddress = shipping && shipping.address_html;
  const hasMethod = shipping && shipping.method;

  if (!hasAddress && !hasMethod && !estimate) {
    return null;
  }

  return (
    <Card title={t('delivery_details', 'Detalhes da entrega')}>
      <div className="flex flex-col gap-4">
        {hasAddress && (
          <div className="flex items-start gap-3.5 border-b border-slate-100 pb-4">
            <MapPinIcon className="mt-0.5 h-5 w-5 text-slate-500" />
            <div className="flex flex-col gap-0.5">
              <span className="text-[13px] font-bold text-slate-900">
                {t('shipping_to', 'Enviar para')}
              </span>
              {shipping.name && <span className="text-sm text-slate-500">{shipping.name}</span>}
              <span
                className="text-sm not-italic text-slate-500"
                dangerouslySetInnerHTML={{ __html: shipping.address_html }}
              />
            </div>
          </div>
        )}

        {hasMethod && (
          <div className="flex items-start gap-3.5">
            <PackageIcon className="mt-0.5 h-5 w-5 text-slate-500" />
            <div className="flex flex-col gap-0.5">
              <span className="text-[13px] font-bold text-slate-900">
                {t('shipping_method', 'Método de envio')}
              </span>
              <span className="text-sm text-slate-500">{shipping.method}</span>
            </div>
          </div>
        )}

        {estimate && (
          <div
            className="mt-1 flex items-start gap-3.5 rounded-xl p-4"
            style={{ backgroundColor: '#eef2ff', border: '1px solid #c7d2fe' }}
          >
            <CalendarIcon className="mt-0.5 h-5 w-5" style={{ color: '#6366f1' }} />
            <div className="flex flex-col gap-0.5">
              <span className="text-[13px] font-bold" style={{ color: '#6366f1' }}>
                {t('estimated_delivery', 'Entrega estimada')}
              </span>
              <span className="text-sm font-semibold" style={{ color: '#4338ca' }}>
                {estimate}
              </span>
              <span className="text-[13px] text-slate-500">
                {t('estimated_delivery_note', 'Enviaremos o rastreio assim que seu pedido for despachado')}
              </span>
            </div>
          </div>
        )}
      </div>
    </Card>
  );
}

/**
 * "What happens next" status timeline.
 */
function TimelineCard({ ty }) {
  if (!ty.progress || !ty.progress.length) {
    return null;
  }

  return (
    <Card title={t('what_happens_next', 'O que acontece a seguir')}>
      <ol className="relative">
        {ty.progress.map((step, i) => {
          const last = i === ty.progress.length - 1;
          const done = step.state === 'done';
          const current = step.state === 'current';

          return (
            <li key={step.key || i} className="relative flex gap-3.5 pb-5 last:pb-0">
              {!last && (
                <span
                  className={`absolute left-[13px] top-7 bottom-0 w-0.5 ${done ? 'fc-primary-bg' : 'bg-slate-200'}`}
                  aria-hidden="true"
                />
              )}

              <span
                className={[
                  'relative z-[1] flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2',
                  done ? 'fc-primary-bg fc-primary-border text-white' : '',
                  current ? 'border-slate-900 bg-slate-900' : '',
                  !done && !current ? 'border-slate-200 bg-white' : '',
                ].join(' ')}
              >
                {done && <CheckIcon className="h-4 w-4" />}
                {current && <span className="h-2 w-2 rounded-full bg-white" />}
              </span>

              <div className="flex flex-col gap-0.5 pt-0.5">
                <span
                  className={`text-sm font-semibold ${step.state === 'upcoming' ? 'text-slate-400' : 'text-slate-900'}`}
                >
                  {step.label}
                </span>
                {step.description && (
                  <span className="text-[13px] text-slate-500">{step.description}</span>
                )}
              </div>
            </li>
          );
        })}
      </ol>
    </Card>
  );
}

/**
 * "Need help" footer with track + support actions.
 */
function HelpCard({ ty }) {
  if (!ty.view_order_url && !ty.support_url) {
    return null;
  }

  return (
    <Card title={t('need_help', 'Precisa de ajuda com seu pedido?')}>
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        {ty.view_order_url && (
          <a
            href={ty.view_order_url}
            className="flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-3.5 text-sm font-semibold text-slate-800 transition-colors hover:border-slate-300 hover:bg-slate-50"
          >
            <PackageIcon className="h-[18px] w-[18px] text-slate-500" />
            {t('track_order', 'Rastrear pedido')}
          </a>
        )}
        {ty.support_url && (
          <a
            href={ty.support_url}
            className="flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-3.5 text-sm font-semibold text-slate-800 transition-colors hover:border-slate-300 hover:bg-slate-50"
          >
            <HelpGlyph />
            {t('contact_support', 'Falar com suporte')}
          </a>
        )}
      </div>
    </Card>
  );
}

/**
 * React thank-you (order-received) page. Reads the localized order payload,
 * fires the confetti once, and renders the captured WooCommerce thank-you hooks
 * (payment instructions, tracking) alongside the order details.
 */
export default function ThankYouPage() {
  const ty = config.thankyou;
  const confettiDone = useRef(false);

  useEffect(() => {
    if (confettiDone.current || !ty || !ty.confetti || !ty.confetti.enabled) {
      return undefined;
    }

    confettiDone.current = true;

    // Only celebrate once per order, even across reloads in the same session.
    const key = `flexify_ty_confetti_${ty.order_id}`;

    try {
      if (window.sessionStorage.getItem(key)) {
        return undefined;
      }

      window.sessionStorage.setItem(key, '1');
    } catch (e) {
      // sessionStorage unavailable — fall through and still celebrate.
    }

    const stop = fireConfetti({ colors: ty.confetti.colors });

    return stop;
  }, [ty]);

  if (!ty) {
    return null;
  }

  const hooks = ty.hooks || {};

  return (
    <div className="pb-12">
      <ThankYouHeader />

      <div className="mx-auto flex max-w-[640px] flex-col gap-4 px-4 pt-8">
        <Hero ty={ty} />

        {!!hooks.before && <HtmlContent html={hooks.before} />}

        {hasVisibleContent(hooks.payment) ? (
          <div className="rounded-2xl border border-slate-200 bg-white px-5 py-5">
            <HtmlContent html={hooks.payment} className="flexify-ty-hook woocommerce" />
          </div>
        ) : (
          !!hooks.payment && <HtmlContent html={hooks.payment} />
        )}

        <OrderSummaryCard ty={ty} />
        <DeliveryCard ty={ty} />
        <TimelineCard ty={ty} />

        {!!ty.downloads_html && (
          <div className="rounded-2xl border border-slate-200 bg-white px-5 py-5">
            <HtmlContent html={ty.downloads_html} className="flexify-ty-hook woocommerce" />
          </div>
        )}

        {hasVisibleContent(hooks.thankyou) ? (
          <div className="rounded-2xl border border-slate-200 bg-white px-5 py-5">
            <HtmlContent html={hooks.thankyou} className="flexify-ty-hook woocommerce" />
          </div>
        ) : (
          !!hooks.thankyou && <HtmlContent html={hooks.thankyou} />
        )}

        <HelpCard ty={ty} />
      </div>
    </div>
  );
}

/* Inline glyphs for icons not in the shared set. */

function HelpGlyph() {
  return (
    <svg viewBox="0 0 24 24" fill="none" className="h-[18px] w-[18px] text-slate-500" aria-hidden="true">
      <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.6" />
      <path d="M9.5 9.5a2.5 2.5 0 0 1 4.8.9c0 1.7-2.5 2.1-2.5 3.6" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
      <circle cx="12" cy="17" r="1" fill="currentColor" />
    </svg>
  );
}
