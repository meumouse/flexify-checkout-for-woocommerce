import { useState } from 'react';
import flexifyApi from '../api/flexifyApi.js';
import { t } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import PhoneInput from './PhoneInput.jsx';

/**
 * WhatsApp OTP login/identification dialog. Rendered only when the WhatsApp
 * login flag is on (the trigger is hidden otherwise).
 */
export default function CheckoutLoginDialog({ open, onClose }) {
  const { setBilling, loadCart } = useCheckout();
  const [step, setStep] = useState('phone');
  const [phone, setPhone] = useState('');
  const [code, setCode] = useState('');
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  if (!open) {
    return null;
  }

  const sendCode = async () => {
    setError('');
    setBusy(true);

    try {
      const res = await flexifyApi.whatsappSend(phone);
      setMessage(res.message || '');
      setStep('code');
    } catch (e) {
      setError(e.message || t('generic_error', ''));
    } finally {
      setBusy(false);
    }
  };

  const verify = async () => {
    setError('');
    setBusy(true);

    try {
      const res = await flexifyApi.whatsappVerify(phone, code);

      if (res.wp_rest_nonce) {
        flexifyApi.setRestNonce(res.wp_rest_nonce);
      }

      if (res.logged_in) {
        // Re-sync the cart under the now-authenticated session.
        await loadCart();
      } else if (res.phone) {
        setBilling((prev) => ({ ...prev, phone: res.phone }));
      }

      onClose();
    } catch (e) {
      setError(e.message || t('generic_error', ''));
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" onClick={onClose}>
      <div className="w-full max-w-sm rounded-xl bg-white p-6 shadow-soft" onClick={(e) => e.stopPropagation()}>
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-base font-semibold text-slate-800">{t('login_whatsapp', 'Entrar com WhatsApp')}</h3>
          <button type="button" className="text-slate-400 hover:text-slate-600" onClick={onClose}>
            ✕
          </button>
        </div>

        {step === 'phone' && (
          <div className="space-y-3">
            <PhoneInput value={phone} onChange={setPhone} />
            <button
              type="button"
              disabled={busy || phone.replace(/\D/g, '').length < 10}
              className="w-full rounded-lg fc-primary-bg px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
              onClick={sendCode}
            >
              {t('send_code', 'Enviar código')}
            </button>
          </div>
        )}

        {step === 'code' && (
          <div className="space-y-3">
            {message && <p className="text-sm text-slate-500">{message}</p>}
            <input
              className="w-full rounded-lg border border-slate-200 px-3 py-2 text-center text-lg tracking-[0.4em] outline-none focus:border-primary focus:ring-4 focus:ring-primary-100"
              type="text"
              inputMode="numeric"
              maxLength={6}
              placeholder="000000"
              value={code}
              onChange={(e) => setCode(e.target.value.replace(/\D/g, ''))}
            />
            <button
              type="button"
              disabled={busy || code.length < 6}
              className="w-full rounded-lg fc-primary-bg px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
              onClick={verify}
            >
              {t('verify_code', 'Verificar código')}
            </button>
            <button type="button" className="w-full text-xs text-slate-500 hover:underline" onClick={() => setStep('phone')}>
              {t('back', 'Voltar')}
            </button>
          </div>
        )}

        {error && <p className="mt-3 text-sm text-danger">{error}</p>}
      </div>
    </div>
  );
}
