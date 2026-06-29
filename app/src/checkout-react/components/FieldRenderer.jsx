import { useCheckout } from '../context/CheckoutContext.jsx';
import { emitSelect } from '../lib/editorBridge.js';
import { sameTarget } from './editor/Selectable.jsx';
import { fieldBinding } from '../lib/fields.js';
import fieldIcon from '../lib/fieldIcons.jsx';
import Select from './ui/Select.jsx';
import Checkbox from './ui/Checkbox.jsx';
import DatePicker from './ui/DatePicker.jsx';

const labelClass = 'block text-sm font-medium text-slate-700 mb-1.5';
const inputClass =
  'w-full h-12 rounded-lg border border-slate-200 px-3 text-sm text-slate-800 outline-none focus:border-primary focus:ring-2 focus:ring-primary-100 transition';

/**
 * Render a single checkout field, applying per-placement style overrides and,
 * in editor mode, making the field selectable.
 *
 * @param {{field:object, style?:object, editor?:boolean, selected?:object|null, selectTarget?:object}} props
 */
export default function FieldRenderer({ field, style = null, editor = false, selected = null, selectTarget = null }) {
  const { billing, setBilling, extraFields, setExtraFields, fieldErrors, clearFieldError } = useCheckout();
  const binding = fieldBinding(field.id);
  const value = binding.scope === 'billing' ? billing[binding.key] ?? '' : extraFields[field.id] ?? '';
  // Inline validation error from a blocked step navigation (never in the editor).
  const error = editor ? '' : (fieldErrors && fieldErrors[field.id]) || '';

  const onChange = (next) => {
    if (error) {
      clearFieldError(field.id);
    }

    if (binding.scope === 'billing') {
      setBilling((prev) => ({ ...prev, [binding.key]: next }));
    } else {
      setExtraFields((prev) => ({ ...prev, [field.id]: next }));
    }
  };

  const isSelect = field.type === 'select' && Array.isArray(field.options) && field.options.length > 0;
  const isCheckbox = field.type === 'checkbox';
  const isDate = field.type === 'date';

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
  // Override the resting + focus border/ring to the danger color when invalid.
  const errorBorder = error ? '!border-danger focus:!border-danger focus:!ring-danger/30' : '';
  const errorId = error ? `fc-${field.id}-error` : undefined;

  const isSelected = editor && sameTarget(selected, selectTarget);
  const disabledInEditor = editor && field.enabled === false;
  const selectableClass = editor
    ? `fc-editor-selectable ${isSelected ? 'is-selected' : ''} ${disabledInEditor ? 'opacity-50' : ''}`
    : '';

  const onSelect = editor
    ? (event) => {
        event.stopPropagation();
        emitSelect(selectTarget);
      }
    : undefined;

  const isChecked = !!value && value !== '0' && value !== 'false';

  return (
    <div
      className={`${colSpan} ${selectableClass}`.trim()}
      style={containerStyle}
      data-fc-label={editor ? field.label || field.id : undefined}
      onClick={onSelect}
    >
      {!isCheckbox && (
        <label className={labelClass} style={labelStyle} htmlFor={`fc-${field.id}`}>
          {field.label}
          {field.required && <span className="text-danger"> *</span>}
        </label>
      )}

      {isCheckbox ? (
        <Checkbox
          id={`fc-${field.id}`}
          checked={isChecked}
          required={field.required}
          label={field.label}
          onChange={(next) => onChange(next ? '1' : '')}
        />
      ) : isSelect ? (
        <Select
          id={`fc-${field.id}`}
          value={value}
          options={field.options}
          required={field.required}
          placeholder={placeholder || '—'}
          className={errorBorder}
          style={inputStyle}
          onChange={onChange}
        />
      ) : isDate ? (
        <DatePicker
          id={`fc-${field.id}`}
          value={value}
          required={field.required}
          placeholder={placeholder || 'dd/mm/aaaa'}
          className={errorBorder}
          style={inputStyle}
          onChange={onChange}
        />
      ) : (
        <div className="relative">
          {icon && (
            <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
              {icon}
            </span>
          )}
          <input
            id={`fc-${field.id}`}
            className={`${inputClass} ${errorBorder}`.trim()}
            style={{ ...inputStyle, ...(icon ? { paddingLeft: '2.25rem' } : null) }}
            type={field.type === 'email' ? 'email' : 'text'}
            inputMode={field.type === 'tel' ? 'tel' : undefined}
            placeholder={placeholder}
            value={value}
            required={field.required}
            aria-invalid={error ? 'true' : undefined}
            aria-describedby={errorId}
            onChange={(e) => onChange(e.target.value)}
          />
        </div>
      )}

      {error && (
        <p id={errorId} className="mt-1.5 text-sm text-danger">
          {error}
        </p>
      )}
    </div>
  );
}
