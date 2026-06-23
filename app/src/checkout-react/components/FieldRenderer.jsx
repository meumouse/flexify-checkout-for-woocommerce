import { useCheckout } from '../context/CheckoutContext.jsx';

/**
 * Standard WooCommerce Store API address keys. Anything else is treated as a
 * Flexify-managed extra field and collected separately (sent via the checkout
 * extension payload).
 */
const STANDARD_ADDRESS_KEYS = new Set([
  'first_name', 'last_name', 'company', 'address_1', 'address_2',
  'city', 'state', 'postcode', 'country', 'email', 'phone',
]);

/**
 * Resolve where a field's value is stored.
 *
 * @param {string} fieldId Field id (e.g. billing_first_name).
 * @returns {{scope: 'billing'|'extra', key: string}}
 */
export function fieldBinding(fieldId) {
  if (fieldId.indexOf('billing_') === 0) {
    const key = fieldId.slice(8);

    if (STANDARD_ADDRESS_KEYS.has(key)) {
      return { scope: 'billing', key };
    }
  }

  return { scope: 'extra', key: fieldId };
}

const labelClass = 'block text-sm font-medium text-slate-700 mb-1';
const inputClass =
  'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary-100 transition';

export default function FieldRenderer({ field }) {
  const { billing, setBilling, extraFields, setExtraFields } = useCheckout();
  const binding = fieldBinding(field.id);
  const value = binding.scope === 'billing' ? billing[binding.key] ?? '' : extraFields[field.id] ?? '';

  const onChange = (next) => {
    if (binding.scope === 'billing') {
      setBilling((prev) => ({ ...prev, [binding.key]: next }));
    } else {
      setExtraFields((prev) => ({ ...prev, [field.id]: next }));
    }
  };

  const isSelect = field.type === 'select' && Array.isArray(field.options) && field.options.length > 0;
  const colSpan = field.position === 'left' || field.position === 'right' ? 'sm:col-span-1' : 'sm:col-span-2';

  return (
    <div className={colSpan}>
      <label className={labelClass} htmlFor={`fc-${field.id}`}>
        {field.label}
        {field.required && <span className="text-danger"> *</span>}
      </label>

      {isSelect ? (
        <select
          id={`fc-${field.id}`}
          className={inputClass}
          value={value}
          required={field.required}
          onChange={(e) => onChange(e.target.value)}
        >
          <option value="">—</option>
          {field.options.map((opt) => (
            <option key={opt.value} value={opt.value}>
              {opt.text || opt.label || opt.value}
            </option>
          ))}
        </select>
      ) : (
        <input
          id={`fc-${field.id}`}
          className={inputClass}
          type={field.type === 'email' ? 'email' : 'text'}
          inputMode={field.type === 'tel' ? 'tel' : undefined}
          value={value}
          required={field.required}
          onChange={(e) => onChange(e.target.value)}
        />
      )}
    </div>
  );
}
