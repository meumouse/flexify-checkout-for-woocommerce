import { CheckIcon } from './Icons.jsx';

/**
 * Modern checkbox: a custom-styled box with an animated check, backed by a
 * visually-hidden native input so it stays keyboard- and screen-reader friendly.
 *
 * @param {object}   props
 * @param {string}   [props.id]
 * @param {boolean}  props.checked
 * @param {Function} props.onChange   Receives the next checked boolean.
 * @param {import('react').ReactNode} [props.label]
 * @param {boolean}  [props.required]
 * @param {boolean}  [props.disabled]
 */
export default function Checkbox({
  id,
  checked = false,
  onChange,
  label = null,
  required = false,
  disabled = false,
}) {
  return (
    <label
      htmlFor={id}
      className={`flex items-start gap-2.5 text-sm text-slate-700 ${
        disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'
      }`}
    >
      <span className="relative mt-0.5 inline-flex h-5 w-5 shrink-0">
        <input
          id={id}
          type="checkbox"
          className="peer absolute inset-0 h-full w-full cursor-pointer opacity-0 disabled:cursor-not-allowed"
          checked={checked}
          required={required}
          disabled={disabled}
          onChange={(event) => onChange(event.target.checked)}
        />
        <span
          aria-hidden="true"
          className={`pointer-events-none flex h-5 w-5 items-center justify-center rounded-md border transition peer-focus-visible:ring-2 peer-focus-visible:ring-primary-100 ${
            checked
              ? 'fc-primary-bg fc-primary-border text-white'
              : 'border-slate-300 bg-white text-transparent'
          }`}
        >
          <CheckIcon className="h-3.5 w-3.5" />
        </span>
      </span>

      {label && (
        <span className="leading-5">
          {label}
          {required && <span className="text-danger"> *</span>}
        </span>
      )}
    </label>
  );
}
