import { useState } from 'react';
import { t } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import { CheckIcon, CloseIcon, MapPinIcon, PlusIcon } from './ui/Icons.jsx';

/**
 * Address book picker shown to logged-in users (Pro). Lists the customer's
 * saved addresses as selectable cards; choosing one fills the address form
 * below and recalculates shipping. "Use a new address" clears the active
 * selection so the shopper can type a fresh one.
 *
 * Rendered only when the saved-addresses flag is on and the user has at least
 * one saved address (ShippingStep gates it).
 */
export default function SavedAddressList() {
  const { savedAddresses, applySavedAddress, removeSavedAddress, busy } = useCheckout();
  const [activeId, setActiveId] = useState('');

  const select = (address) => {
    setActiveId(address.id);
    applySavedAddress(address).catch(() => {});
  };

  const remove = (e, id) => {
    e.stopPropagation();

    if (activeId === id) {
      setActiveId('');
    }

    removeSavedAddress(id).catch(() => {});
  };

  return (
    <div className="space-y-3">
      <div className="flex items-center gap-2 text-sm font-medium text-slate-800">
        <MapPinIcon className="h-4 w-4 fc-primary-text" />
        {t('saved_addresses', 'Endereços salvos')}
      </div>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        {savedAddresses.map((address) => {
          const active = activeId === address.id;
          const b = address.billing || {};
          const extra = address.extra || {};
          const line = [b.address_1, extra.billing_number].filter(Boolean).join(', ');
          const cityLine = [b.city, b.state].filter(Boolean).join(' - ');

          return (
            <button
              key={address.id}
              type="button"
              onClick={() => select(address)}
              className={`relative rounded-xl border p-3 text-left text-sm transition ${
                active ? 'fc-primary-border fc-soft-bg' : 'border-slate-200 hover:border-slate-300'
              }`}
            >
              <span className="flex items-center justify-between gap-2">
                <span className="font-medium text-slate-800">{address.nickname}</span>
                {active && <CheckIcon className="h-4 w-4 fc-primary-text" />}
              </span>

              {line && <span className="mt-1 block text-slate-600">{line}</span>}
              {cityLine && <span className="block text-slate-500">{cityLine}</span>}
              {b.postcode && <span className="block text-slate-400">{b.postcode}</span>}

              <span
                role="button"
                tabIndex={0}
                aria-label={t('remove', 'Remover')}
                onClick={(e) => remove(e, address.id)}
                onKeyDown={(e) => (e.key === 'Enter' || e.key === ' ') && remove(e, address.id)}
                className="absolute right-2 top-2 inline-flex h-6 w-6 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600"
              >
                <CloseIcon className="h-3.5 w-3.5" />
              </span>
            </button>
          );
        })}

        <button
          type="button"
          disabled={busy}
          onClick={() => setActiveId('')}
          className={`flex items-center justify-center gap-2 rounded-xl border border-dashed p-3 text-sm font-medium transition ${
            activeId === '' ? 'fc-primary-border fc-primary-text' : 'border-slate-300 text-slate-600 hover:border-slate-400'
          }`}
        >
          <PlusIcon className="h-4 w-4" />
          {t('use_new_address', 'Usar um novo endereço')}
        </button>
      </div>
    </div>
  );
}
