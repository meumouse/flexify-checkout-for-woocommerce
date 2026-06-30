import { useState } from 'react';
import { t } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';

/**
 * "Save this address" control shown to logged-in users (Pro) under the address
 * form. Toggling it on reveals a nickname input; saving stores the address
 * currently typed in the form to the customer's address book.
 *
 * Rendered only when the saved-addresses flag is on (ShippingStep gates it).
 */
export default function SaveAddressControl() {
  const { billing, saveCurrentAddress, busy } = useCheckout();
  const [open, setOpen] = useState(false);
  const [nickname, setNickname] = useState('');

  const canSave = !!(billing.address_1 && billing.city);

  const save = async () => {
    try {
      await saveCurrentAddress(nickname.trim());
      setNickname('');
      setOpen(false);
    } catch (e) {
      /* toast already surfaced by the context */
    }
  };

  return (
    <div className="rounded-xl border border-slate-200 p-4">
      <label className="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
        <input
          type="checkbox"
          checked={open}
          onChange={(e) => setOpen(e.target.checked)}
          className="h-4 w-4 rounded border-slate-300"
        />
        {t('save_this_address', 'Salvar este endereço para a próxima vez')}
      </label>

      {open && (
        <div className="mt-3 flex flex-col gap-2 sm:flex-row">
          <input
            type="text"
            value={nickname}
            onChange={(e) => setNickname(e.target.value)}
            placeholder={t('address_nickname', 'Nome do endereço (ex.: Casa, Trabalho)')}
            className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary-100 transition"
          />
          <button
            type="button"
            disabled={busy || !canSave}
            onClick={save}
            className="shrink-0 rounded-lg fc-primary-bg px-4 py-2 text-sm font-medium text-white transition disabled:opacity-50"
          >
            {t('save_address', 'Salvar endereço')}
          </button>
        </div>
      )}
    </div>
  );
}
