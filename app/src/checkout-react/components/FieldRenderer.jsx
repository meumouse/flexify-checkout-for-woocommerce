import { useCheckout } from '../context/CheckoutContext.jsx';
import { emitSelect } from '../lib/editorBridge.js';
import { sameTarget } from './editor/Selectable.jsx';
import fieldIcon from '../lib/fieldIcons.jsx';

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

/**
 * Render a single checkout field, applying per-placement style overrides and,
 * in editor mode, making the field selectable.
 *
 * @param {{field:object, style?:object, editor?:boolean, selected?:object|null, selectTarget?:object}} props
 */
export default function FieldRenderer({ field, style = null, editor = false, selected = null, selectTarget = null }) {
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

  // Width: style override wins over the field's stored position.
  const width = style?.width || field.position;
  const colSpan = width === 'left' || width === 'right' ? 'sm:col-span-1' : 'sm:col-span-2';

  const labelStyle = {};
  const inputStyle = {};

  if (style) {
    if (style.label_color) labelStyle.color = style.label_color;
    if (style.label_size) labelStyle.fontSize = `${style.label_size}px`;
    if (style.label_weight) labelStyle.fontWeight = style.label_weight;
    if (style.input_bg) inputStyle.backgroundColor = style.input_bg;
    if (style.input_border) inputStyle.borderColor = style.input_border;
    if (style.input_radius !== undefined && style.input_radius !== '') inputStyle.borderRadius = `${style.input_radius}px`;
    if (style.input_color) inputStyle.color = style.input_color;
  }

  const containerStyle = {};

  if (style && style.margin_bottom !== undefined && style.margin_bottom !== '') {
    containerStyle.marginBottom = `${style.margin_bottom}px`;
  }

  const icon = style?.icon ? fieldIcon(style.icon) : null;
  const placeholder = style?.placeholder || '';

  const isSelected = editor && sameTarget(selected, selectTarget);
  const selectableClass = editor ? `fc-editor-selectable ${isSelected ? 'is-selected' : ''}` : '';

  const onSelect = editor
    ? (event) => {
        event.stopPropagation();
        emitSelect(selectTarget);
      }
    : undefined;

  return (
    <div
      className={`${colSpan} ${selectableClass}`.trim()}
      style={containerStyle}
      data-fc-label={editor ? field.label || field.id : undefined}
      onClick={onSelect}
    >
      <label className={labelClass} style={labelStyle} htmlFor={`fc-${field.id}`}>
        {field.label}
        {field.required && <span className="text-danger"> *</span>}
      </label>

      {isSelect ? (
        <select
          id={`fc-${field.id}`}
          className={inputClass}
          style={inputStyle}
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
        <div className="relative">
          {icon && (
            <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
              {icon}
            </span>
          )}
          <input
            id={`fc-${field.id}`}
            className={inputClass}
            style={{ ...inputStyle, ...(icon ? { paddingLeft: '2.25rem' } : null) }}
            type={field.type === 'email' ? 'email' : 'text'}
            inputMode={field.type === 'tel' ? 'tel' : undefined}
            placeholder={placeholder}
            value={value}
            required={field.required}
            onChange={(e) => onChange(e.target.value)}
          />
        </div>
      )}
    </div>
  );
}
