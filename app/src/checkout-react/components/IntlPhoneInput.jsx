import { useEffect, useRef } from 'react';
import config from '../config.js';

/**
 * International phone field (Pro), backed by the intl-tel-input library which
 * Core\Assets loads globally as window.intlTelInput when the feature is enabled.
 *
 * Shows a large-flag country selector with a separate dial code and translated
 * labels (config.iti_i18n). The library owns the input's DOM (it wraps it and
 * injects the dropdown), so the input is uncontrolled here: we seed it once and
 * push the full E.164 number (iti.getNumber()) upward, so the order stores an
 * international-format billing phone. Falls back to a plain input if the library
 * isn't present (e.g. license lapsed after the page was cached).
 *
 * @param {{
 *   value?: string,
 *   onChange: (value: string) => void,
 *   id?: string,
 *   placeholder?: string,
 *   required?: boolean,
 *   className?: string,
 *   style?: object,
 *   ariaInvalid?: boolean,
 *   ariaDescribedby?: string,
 * }} props
 */
export default function IntlPhoneInput({
  value = '',
  onChange,
  id = 'fc-phone',
  placeholder,
  required = false,
  className = '',
  style,
  ariaInvalid,
  ariaDescribedby,
}) {
  const inputRef = useRef(null);
  // Keep the latest onChange without re-running the init effect.
  const onChangeRef = useRef(onChange);
  onChangeRef.current = onChange;
  // Seed value only on first mount; later external changes don't fight the lib.
  const initialValueRef = useRef(value);

  useEffect(() => {
    const el = inputRef.current;

    if (!el || typeof window === 'undefined' || typeof window.intlTelInput !== 'function') {
      return undefined;
    }

    const onlyCountries =
      Array.isArray(config.allowed_countries) && config.allowed_countries.length
        ? config.allowed_countries
        : ['br'];

    const iti = window.intlTelInput(el, {
      autoPlaceholder: 'polite',
      containerClass: 'flexify-intl-phone--init',
      nationalMode: true,
      separateDialCode: true,
      initialCountry: (config.base_country || 'br').toLowerCase(),
      onlyCountries,
      i18n: config.iti_i18n || {},
      loadUtils: config.path_to_utils
        ? () => import(/* @vite-ignore */ config.path_to_utils)
        : undefined,
    });

    // Seed the existing value (E.164 or national) once the instance is ready.
    if (initialValueRef.current) {
      iti.setNumber(initialValueRef.current);
    }

    const commit = () => onChangeRef.current(iti.getNumber() || el.value || '');

    // Push the full number on init so the stored value matches what's shown.
    commit();

    el.addEventListener('countrychange', commit);
    el.addEventListener('input', commit);
    el.addEventListener('blur', commit);

    return () => {
      el.removeEventListener('countrychange', commit);
      el.removeEventListener('input', commit);
      el.removeEventListener('blur', commit);
      iti.destroy();
    };
    // Initialize once: config is static for the page's lifetime.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div className="flexify-intl-phone">
      <input
        ref={inputRef}
        id={id}
        className={className}
        style={style}
        type="tel"
        inputMode="tel"
        autoComplete="tel"
        placeholder={placeholder}
        required={required}
        defaultValue={value}
        aria-invalid={ariaInvalid ? 'true' : undefined}
        aria-describedby={ariaDescribedby}
      />
    </div>
  );
}
