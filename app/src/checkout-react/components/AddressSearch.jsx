import { useEffect, useRef, useState } from 'react';
import flexifyApi from '../api/flexifyApi.js';
import config, { t } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';

/**
 * Google Places address search. Only rendered when the address search flag is
 * on (Core\Assets gates it). Picking a suggestion fills the address fields and
 * recalculates shipping. Falls back silently if the proxy errors — the manual
 * CEP/address fields remain usable.
 */
export default function AddressSearch() {
  const { setBilling, updateAddress, setExtraFields } = useCheckout();
  const [query, setQuery] = useState('');
  const [suggestions, setSuggestions] = useState([]);
  const [open, setOpen] = useState(false);
  const sessionToken = useRef(makeToken());
  const timer = useRef(null);

  useEffect(() => {
    if (query.trim().length < 3) {
      setSuggestions([]);

      return undefined;
    }

    clearTimeout(timer.current);
    timer.current = setTimeout(async () => {
      try {
        const res = await flexifyApi.addressAutocomplete(query, sessionToken.current);
        setSuggestions(res.suggestions || []);
        setOpen(true);
      } catch (e) {
        setSuggestions([]);
      }
    }, 300);

    return () => clearTimeout(timer.current);
  }, [query]);

  const pick = async (suggestion) => {
    setOpen(false);
    setQuery(suggestion.primary);

    try {
      const details = await flexifyApi.addressDetails(suggestion.place_id, sessionToken.current);
      sessionToken.current = makeToken();

      const patch = {
        address_1: details.address_1 || '',
        city: details.city || '',
        state: details.state || '',
        postcode: details.cep || '',
      };

      setBilling((prev) => ({ ...prev, ...patch }));

      if (details.number || details.neighborhood) {
        setExtraFields((prev) => ({
          ...prev,
          ...(details.number ? { billing_number: details.number } : {}),
          ...(details.neighborhood ? { billing_neighborhood: details.neighborhood } : {}),
        }));
      }

      // Recalculate shipping with the new postcode.
      if (patch.postcode) {
        updateAddress(patch).catch(() => {});
      }
    } catch (e) {
      /* keep manual fields usable */
    }
  };

  return (
    <div className="relative sm:col-span-2">
      <label className="block text-sm font-medium text-slate-700 mb-1" htmlFor="fc-address-search">
        {t('search_address', 'Pesquisar endereço')}
      </label>
      <input
        id="fc-address-search"
        className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary-100 transition"
        type="text"
        autoComplete="off"
        placeholder={config.i18n?.search_address || 'Rua, número, cidade…'}
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        onFocus={() => suggestions.length && setOpen(true)}
      />

      {open && suggestions.length > 0 && (
        <ul className="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-soft">
          {suggestions.map((s) => (
            <li key={s.place_id}>
              <button
                type="button"
                className="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50"
                onClick={() => pick(s)}
              >
                <span className="font-medium text-slate-800">{s.primary}</span>
                {s.secondary && <span className="block text-xs text-slate-500">{s.secondary}</span>}
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

function makeToken() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;

    return v.toString(16);
  });
}
