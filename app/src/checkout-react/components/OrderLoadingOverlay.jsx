import { t } from '../config.js';
import { MessageLoading } from './ui/MessageLoading.jsx';

/**
 * Modern, default full-screen loader shown while the order is being placed.
 *
 * Replaces the legacy Lordicon `PurchaseAnimation` as the out-of-the-box
 * experience for the Swift checkout: a clean blurred backdrop with the
 * three-dot brand-colored spinner and a reassuring message. The legacy
 * animation only takes over when the operator explicitly enables it.
 *
 * Hidden by default — only `.is-active` reveals it — so the markup never leaks.
 *
 * @param {{ active: boolean }} props Whether the order submission is in flight.
 */
export default function OrderLoadingOverlay({ active }) {
  return (
    <div
      className={`fc-order-loading${active ? ' is-active' : ''}`}
      aria-hidden={!active}
      role="status"
      aria-live="polite"
    >
      <div className="fc-order-loading__content">
        <MessageLoading size={48} className="fc-primary-text fc-order-loading__spinner" />

        <div className="fc-order-loading__text">
          <h5 className="fc-order-loading__title">
            {t('finishing_order', 'Finalizando seu pedido')}
          </h5>
          <span className="fc-order-loading__hint">
            {t('please_wait', 'Aguarde alguns instantes')}
          </span>
        </div>
      </div>
    </div>
  );
}
