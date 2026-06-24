import { useState } from 'react';
import config, { t } from '../../config.js';
import { fieldsForStep } from '../../lib/fields.js';
import FieldRenderer from '../FieldRenderer.jsx';
import CheckoutLoginDialog from '../CheckoutLoginDialog.jsx';

export default function ContactStep() {
  const [loginOpen, setLoginOpen] = useState(false);
  const fields = fieldsForStep(1);
  const whatsappEnabled = config.flags && config.flags.whatsapp_login;

  return (
    <div>
      <h2 className="fc-heading mb-5 text-xl text-slate-800">
        {t('contact_title', 'Dados do titular da compra')}
      </h2>

      {whatsappEnabled && !config.is_user_logged_in && (
        <div className="mb-5 flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
          <span className="text-sm text-slate-600">{t('login_whatsapp', 'Entrar com WhatsApp')}</span>
          <button
            type="button"
            className="fc-primary-border fc-primary-text rounded-lg border px-3 py-1.5 text-sm font-semibold hover:bg-primary-50"
            onClick={() => setLoginOpen(true)}
          >
            {t('login_whatsapp', 'Entrar')}
          </button>
        </div>
      )}

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        {fields.map((field) => (
          <FieldRenderer key={field.id} field={field} />
        ))}
      </div>

      <CheckoutLoginDialog open={loginOpen} onClose={() => setLoginOpen(false)} />
    </div>
  );
}
