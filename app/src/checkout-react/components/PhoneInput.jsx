import config from '../config.js';

/**
 * Minimal phone field. Keeps a country prefix hint for Brazil and emits the
 * raw typed value (digits/symbols) — normalization happens server-side.
 */
export default function PhoneInput({ value, onChange, id = 'fc-phone', placeholder }) {
  return (
    <div className="flex items-stretch overflow-hidden rounded-lg border border-slate-200 focus-within:border-primary focus-within:ring-4 focus-within:ring-primary-100">
      <span className="flex items-center bg-slate-50 px-3 text-sm text-slate-500">
        {config.base_country === 'BR' ? '🇧🇷 +55' : '+'}
      </span>
      <input
        id={id}
        className="w-full px-3 py-2 text-sm outline-none"
        type="tel"
        inputMode="tel"
        autoComplete="tel"
        placeholder={placeholder || '(11) 99999-9999'}
        value={value}
        onChange={(e) => onChange(e.target.value)}
      />
    </div>
  );
}
