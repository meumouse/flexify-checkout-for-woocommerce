import { t } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import { formatPrice } from '../lib/format.js';

/**
 * Read-only summary rows ("Contato", "Entrega", "Frete") shown at the top of the
 * shipping and payment steps, each with an "Editar" link that jumps back.
 *
 * @param {{sections:string[], onEdit:(target:string)=>void}} props
 */
export default function StepSummary({ sections = [], onEdit }) {
  const { billing, extraFields, cart } = useCheckout();

  const rows = [];

  if (sections.includes('contact')) {
    const name = [billing.first_name, billing.last_name].filter(Boolean).join(' ');

    rows.push({
      key: 'contact',
      label: t('contact', 'Contato'),
      target: 'contact',
      lines: [name, billing.phone, billing.email].filter(Boolean),
      strongFirst: true,
    });
  }

  if (sections.includes('shipping')) {
    const line = formatAddress(billing, extraFields);

    if (line) {
      rows.push({ key: 'shipping', label: t('shipping', 'Entrega'), target: 'shipping', lines: [line] });
    }
  }

  if (sections.includes('frete')) {
    const rate = selectedRate(cart);

    if (rate) {
      const totals = (cart && cart.totals) || {};
      const price = Number(rate.price) > 0 ? formatPrice(rate.price, totals) : formatPrice(0, totals);

      rows.push({
        key: 'frete',
        label: 'Frete',
        target: 'shipping',
        lines: [`${rate.name} — ${price}`],
      });
    }
  }

  if (!rows.length) {
    return null;
  }

  return (
    <div className="divide-y divide-slate-100 rounded-xl border border-slate-200">
      {rows.map((row) => (
        <div key={row.key} className="flex items-start gap-4 p-4">
          <span className="w-16 shrink-0 text-sm text-slate-500">{row.label}</span>
          <div className="min-w-0 flex-1 break-words text-sm">
            {row.lines.map((text, i) => (
              <p key={i} className={i === 0 && row.strongFirst ? 'font-medium text-slate-800' : 'text-slate-500'}>
                {text}
              </p>
            ))}
          </div>
          {onEdit && (
            <button
              type="button"
              onClick={() => onEdit(row.target)}
              className="fc-primary-text shrink-0 text-sm font-medium hover:underline"
            >
              {t('edit', 'Editar')}
            </button>
          )}
        </div>
      ))}
    </div>
  );
}

function formatAddress(billing, extraFields) {
  const number = extraFields?.billing_number || extraFields?.number || '';
  const street = [billing.address_1, number].filter(Boolean).join(', ');
  const cityState = [billing.city, billing.state].filter(Boolean).join(' - ');
  const main = [street, cityState].filter(Boolean).join(', ');

  if (!main) {
    return '';
  }

  return billing.postcode ? `${main} (CEP: ${billing.postcode})` : main;
}

function selectedRate(cart) {
  const packages = (cart && cart.shipping_rates) || [];

  for (const pkg of packages) {
    const rate = (pkg.shipping_rates || []).find((r) => r.selected);

    if (rate) {
      return rate;
    }
  }

  return null;
}
