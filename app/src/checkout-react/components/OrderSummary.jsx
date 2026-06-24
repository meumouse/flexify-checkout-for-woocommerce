import { t } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import { formatPrice } from '../lib/format.js';
import CouponForm from './CouponForm.jsx';
import { BasketIcon, MinusIcon, PlusIcon, CloseIcon } from './ui/Icons.jsx';

/**
 * Cart / order summary panel ("Carrinho"): line items with quantity steppers,
 * coupon form and totals. Used both in the sticky sidebar and the mobile sheet.
 */
export default function OrderSummary({ hideCoupon = false, title } = {}) {
  const { cart, busy, updateItemQuantity, removeItem } = useCheckout();

  if (!cart) {
    return null;
  }

  const totals = cart.totals || {};
  const items = cart.items || [];
  const fees = cart.fees || [];
  const count = cart.items_count || items.reduce((sum, it) => sum + (it.quantity || 0), 0);

  return (
    <section>
      <h2 className="flex items-center gap-2 text-lg font-medium text-slate-800">
        <BasketIcon className="h-6 w-6 fc-primary-text" />
        {title || t('cart', 'Carrinho')}
        {count > 0 && (
          <span className="ml-1 inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full fc-primary-bg px-2 text-sm font-semibold text-white">
            {count}
          </span>
        )}
      </h2>

      {items.length === 0 ? (
        <p className="mt-4 text-sm text-slate-500">{t('empty_cart', 'Seu carrinho está vazio.')}</p>
      ) : (
        <ul className="mt-5 max-h-[420px] space-y-5 overflow-y-auto pr-1">
          {items.map((item) => (
            <CartLine
              key={item.key}
              item={item}
              totals={totals}
              busy={busy}
              onQty={(qty) => updateItemQuantity(item.key, qty)}
              onRemove={() => removeItem(item.key)}
            />
          ))}
        </ul>
      )}

      {!hideCoupon && <div className="mt-5"><CouponForm /></div>}

      <div className="mt-5 space-y-3 border-t border-slate-100 pt-5 text-sm">
        <Row label={t('subtotal', 'Subtotal')} value={formatPrice(totals.total_items, totals)} />

        {Number(totals.total_discount) > 0 && (
          <Row
            label={t('discount', 'Desconto')}
            value={`-${formatPrice(totals.total_discount, totals)}`}
            accent
          />
        )}

        {fees.map((fee) => {
          const amount = Number(fee.totals?.total || 0);
          const isDiscount = amount < 0;

          return (
            <Row
              key={fee.id || fee.name}
              label={fee.name}
              value={`${isDiscount ? '-' : ''}${formatPrice(Math.abs(amount), totals)}`}
              accent={isDiscount}
            />
          );
        })}

        {cart.needs_shipping && (
          <Row label="Frete" value={formatPrice(totals.total_shipping, totals)} />
        )}

        <div className="mt-1 flex items-center justify-between border-t border-slate-100 pt-4">
          <span className="text-base font-semibold text-slate-900">{t('total', 'Total')}</span>
          <span className="text-2xl font-bold text-slate-900">{formatPrice(totals.total_price, totals)}</span>
        </div>
      </div>
    </section>
  );
}

function CartLine({ item, totals, busy, onQty, onRemove }) {
  const image = item.images && item.images[0];
  const meta = (item.item_data || []).filter((entry) => !entry.hidden);

  return (
    <li className="flex gap-4">
      <div className="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-slate-100">
        {image && <img src={image.thumbnail || image.src} alt={item.name} className="h-full w-full object-cover" />}
      </div>

      <div className="min-w-0 flex-1">
        <div className="flex items-start gap-2">
          <p className="flex-1 text-sm font-medium leading-snug text-slate-800">{item.name}</p>
          <button
            type="button"
            disabled={busy}
            onClick={onRemove}
            className="text-slate-400 transition-colors hover:text-danger disabled:opacity-50"
            aria-label="Remover"
          >
            <CloseIcon className="h-4 w-4" />
          </button>
        </div>

        {meta.length > 0 && (
          <div className="mt-1 space-y-0.5">
            {meta.map((entry, i) => (
              <p key={i} className="flex flex-wrap items-center gap-1 text-xs text-slate-500">
                <span>{entry.key || entry.name}:</span>
                <span
                  className="text-slate-600 [&_img]:inline [&_img]:h-4 [&_img]:w-auto"
                  dangerouslySetInnerHTML={{ __html: entry.display || entry.value }}
                />
              </p>
            ))}
          </div>
        )}

        <div className="mt-3 flex items-center justify-between">
          <div className="inline-flex items-center rounded-md border border-slate-200">
            <button
              type="button"
              disabled={busy || item.quantity <= 1}
              onClick={() => onQty(item.quantity - 1)}
              className="p-1.5 text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-40"
              aria-label="Diminuir"
            >
              <MinusIcon className="h-4 w-4" />
            </button>
            <span className="w-9 text-center text-sm text-slate-800">{item.quantity}</span>
            <button
              type="button"
              disabled={busy}
              onClick={() => onQty(item.quantity + 1)}
              className="p-1.5 text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-40"
              aria-label="Aumentar"
            >
              <PlusIcon className="h-4 w-4" />
            </button>
          </div>

          <span className="text-sm font-semibold text-slate-800">
            {formatPrice(item.totals?.line_total, totals)}
          </span>
        </div>
      </div>
    </li>
  );
}

function Row({ label, value, accent = false }) {
  return (
    <div className="flex items-center justify-between">
      <span className="text-slate-500">{label}</span>
      <span className={accent ? 'fc-primary-text font-medium' : 'text-slate-700'}>{value}</span>
    </div>
  );
}
