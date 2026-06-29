/**
 * Toast stack for checkout notifications.
 *
 * Rendered once by ToastProvider, fixed to the top of the viewport. Messages may
 * carry HTML entities / markup from the (server-sanitized) WooCommerce Store API,
 * so they are rendered as HTML — mirroring HtmlBlock.
 *
 * @param {{toasts:Array, onDismiss:Function}} props
 */
import { AlertTriangleIcon, CheckIcon, CloseIcon, InfoIcon } from './ui/Icons.jsx';

const STYLES = {
  success: { wrap: 'border-success bg-success/10 text-slate-800', icon: 'text-success', Icon: CheckIcon },
  error: { wrap: 'border-danger bg-red-50 text-danger', icon: 'text-danger', Icon: AlertTriangleIcon },
  info: { wrap: 'border-slate-200 bg-white text-slate-700', icon: 'text-primary', Icon: InfoIcon },
};

export default function ToastContainer({ toasts, onDismiss }) {
  if (!toasts.length) {
    return null;
  }

  return (
    <div className="pointer-events-none fixed inset-x-0 top-4 z-[1100] flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:right-4 sm:items-end">
      {toasts.map((toast) => {
        const style = STYLES[toast.type] || STYLES.info;
        const Icon = style.Icon;

        return (
          <div
            key={toast.id}
            role="alert"
            className={`fc-toast pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-lg ${style.wrap}`}
          >
            <Icon className={`mt-0.5 h-5 w-5 flex-shrink-0 ${style.icon}`} />
            <div className="flex-1 leading-snug" dangerouslySetInnerHTML={{ __html: toast.message }} />
            <button
              type="button"
              className="-mr-1 flex-shrink-0 opacity-60 transition hover:opacity-100"
              onClick={() => onDismiss(toast.id)}
              aria-label="Fechar"
            >
              <CloseIcon className="h-4 w-4" />
            </button>
          </div>
        );
      })}
    </div>
  );
}
