/**
 * Toast notifications for the checkout.
 *
 * Thin wrapper over Sonner: ToastProvider mounts the <Toaster /> and exposes the
 * same { pushToast, dismissToast } API the checkout already calls, so Sonner
 * drives the rendering while call sites stay unchanged. Mounted ABOVE
 * CheckoutProvider so checkout actions can surface errors/successes via useToast().
 *
 * @since 6.0.0
 */
import { createContext, useContext, useMemo } from 'react';
import { Toaster, toast } from 'sonner';

const ToastContext = createContext(null);

// Default lifetimes (ms) per type. Errors linger longer so they can be read.
const DURATIONS = { error: 6000, success: 4000, info: 5000 };

// Messages may carry HTML entities / markup from the (server-sanitized)
// WooCommerce Store API, so they are rendered as HTML — mirroring HtmlBlock.
function Message({ html }) {
  return <span dangerouslySetInnerHTML={{ __html: html }} />;
}

export function ToastProvider({ children }) {
  const value = useMemo(() => {
    const pushToast = (input) => {
      const data = typeof input === 'string' ? { message: input } : input || {};
      const message = data.message;

      if (!message) {
        return null;
      }

      const type = data.type || 'info';
      const duration = data.duration ?? DURATIONS[type] ?? 5000;
      const fn = toast[type] || toast;

      return fn(<Message html={message} />, { duration });
    };

    const dismissToast = (id) => toast.dismiss(id);

    return { pushToast, dismissToast };
  }, []);

  return (
    <ToastContext.Provider value={value}>
      {children}
      <Toaster position="top-center" richColors closeButton />
    </ToastContext.Provider>
  );
}

/**
 * Access the toast API. Falls back to no-ops when no provider is mounted, so
 * components stay safe in isolation (e.g. the builder preview).
 *
 * @returns {{pushToast:Function, dismissToast:Function}}
 */
export function useToast() {
  return useContext(ToastContext) || { pushToast: () => null, dismissToast: () => {} };
}
