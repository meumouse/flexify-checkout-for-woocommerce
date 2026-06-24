import { useState } from 'react';
import config, { t } from '../config.js';
import CheckoutLoginDialog from './CheckoutLoginDialog.jsx';

/**
 * WhatsApp login affordance. Semantic chrome rendered atop a contact-type step.
 */
export default function ContactLogin() {
  const [loginOpen, setLoginOpen] = useState(false);
  const whatsappEnabled = config.flags && config.flags.whatsapp_login;

  if (!whatsappEnabled || config.is_user_logged_in) {
    return null;
  }

  return (
    <>
      <div className="mb-4 flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
        <span className="text-sm text-slate-600">{t('login_whatsapp', 'Entrar com WhatsApp')}</span>
        <button
          type="button"
          className="rounded-lg border border-primary fc-primary-text px-3 py-1.5 text-sm font-semibold hover:bg-primary-50"
          onClick={() => setLoginOpen(true)}
        >
          {t('login_whatsapp', 'Entrar')}
        </button>
      </div>

      <CheckoutLoginDialog open={loginOpen} onClose={() => setLoginOpen(false)} />
    </>
  );
}
