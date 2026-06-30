import { t } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import { CheckIcon, CloseIcon, MapPinIcon, PlusIcon } from './ui/Icons.jsx';

/**
 * Address book picker shown to logged-in users (Pro). Lists the customer's
 * saved addresses as selectable cards using the same pattern as the shipping
 * method selector. Selection and form visibility are owned by ShippingStep;
 * this component is controlled via props.
 *
 * @param {object}   props
 * @param {string}   props.selectedId Currently selected address id ('' = new address).
 * @param {Function} props.onSelect   Called with the address when a card is picked.
 * @param {Function} props.onEdit     Called with the address when its "Edit" link is clicked.
 * @param {Function} props.onUseNew   Called when the "Use a new address" card is picked.
 */
export default function SavedAddressList({ selectedId, onSelect, onEdit, onUseNew }) {
  const { savedAddresses, removeSavedAddress, busy } = useCheckout();

  const remove = (e, id) => {
    e.stopPropagation();

    if (selectedId === id) {
      onUseNew();
    }

    removeSavedAddress(id).catch(() => {});
  };

  const onKey = (e, fn) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      fn();
    }
  };

  return (
    <div className="space-y-3">
      <div className="flex items-center gap-2 text-sm font-medium text-slate-800">
        <MapPinIcon className="h-4 w-4 fc-primary-text" />
        {t('saved_addresses', 'Endereços salvos')}
      </div>

      <div className="space-y-3">
        {savedAddresses.map((address) => {
          const selected = selectedId === address.id;
          const b = address.billing || {};
          const extra = address.extra || {};
          const line = [b.address_1, extra.billing_number].filter(Boolean).join(', ');
          const cityLine = [b.city, b.state].filter(Boolean).join(' - ');

          return (
            <div
              key={address.id}
              role="radio"
              aria-checked={selected}
              tabIndex={0}
              onClick={() => onSelect(address)}
              onKeyDown={(e) => onKey(e, () => onSelect(address))}
              className={`flex cursor-pointer items-center gap-3 rounded-xl border p-4 text-sm transition-colors ${
                selected ? 'fc-primary-border' : 'border-slate-200 hover:border-slate-300'
              }`}
            >
              {selected && (
                <span className="fc-primary-bg flex h-6 w-6 shrink-0 items-center justify-center rounded-full">
                  <CheckIcon className="h-3.5 w-3.5 text-white" />
                </span>
              )}

              <div className="flex-1">
                <div className="flex items-center justify-between gap-3">
                  <span className="font-semibold text-slate-800">{address.nickname}</span>

                  <span className="flex items-center gap-3">
                    {selected && (
                      <button
                        type="button"
                        onClick={(e) => {
                          e.stopPropagation();
                          onEdit(address);
                        }}
                        className="fc-primary-text text-xs font-medium underline-offset-2 hover:underline"
                      >
                        {t('edit', 'Editar')}
                      </button>
                    )}

                    <button
                      type="button"
                      disabled={busy}
                      aria-label={t('remove', 'Remover')}
                      onClick={(e) => remove(e, address.id)}
                      className="inline-flex h-6 w-6 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    >
                      <CloseIcon className="h-3.5 w-3.5" />
                    </button>
                  </span>
                </div>

                {line && <span className="mt-0.5 block text-slate-600">{line}</span>}
                {cityLine && <span className="block text-slate-500">{cityLine}</span>}
                {b.postcode && <span className="block text-slate-400">{b.postcode}</span>}
              </div>
            </div>
          );
        })}

        <div
          role="radio"
          aria-checked={selectedId === ''}
          tabIndex={0}
          onClick={onUseNew}
          onKeyDown={(e) => onKey(e, onUseNew)}
          className={`flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-dashed p-4 text-sm font-medium transition-colors ${
            selectedId === '' ? 'fc-primary-border fc-primary-text' : 'border-slate-300 text-slate-600 hover:border-slate-400'
          }`}
        >
          <PlusIcon className="h-4 w-4" />
          {t('use_new_address', 'Usar um novo endereço')}
        </div>
      </div>
    </div>
  );
}
