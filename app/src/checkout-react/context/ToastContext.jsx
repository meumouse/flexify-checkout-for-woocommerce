/**
 * Toast notifications for the checkout.
 *
 * Holds the active toast queue, auto-dismisses each after a type-based timeout,
 * and renders the ToastContainer. Mounted ABOVE CheckoutProvider so checkout
 * actions can surface errors/successes via useToast().
 *
 * @since 6.0.0
 */
import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import ToastContainer from '../components/ToastContainer.jsx';

const ToastContext = createContext(null);

// Default lifetimes (ms) per type. Errors linger longer so they can be read.
const DURATIONS = { error: 6000, success: 4000, info: 5000 };

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([]);
  const counter = useRef(0);
  const timers = useRef(new Map());

  const dismissToast = useCallback((id) => {
    setToasts((prev) => prev.filter((toast) => toast.id !== id));

    const timer = timers.current.get(id);

    if (timer) {
      clearTimeout(timer);
      timers.current.delete(id);
    }
  }, []);

  const pushToast = useCallback(
    (input) => {
      const toast = typeof input === 'string' ? { message: input } : input || {};
      const message = toast.message;

      if (!message) {
        return null;
      }

      const id = (counter.current += 1);
      const type = toast.type || 'info';

      setToasts((prev) => [...prev, { id, type, message }]);

      const duration = toast.duration ?? DURATIONS[type] ?? 5000;

      if (duration > 0) {
        timers.current.set(id, setTimeout(() => dismissToast(id), duration));
      }

      return id;
    },
    [dismissToast],
  );

  // Clear any pending timers on unmount.
  useEffect(() => {
    const pending = timers.current;

    return () => {
      pending.forEach((timer) => clearTimeout(timer));
      pending.clear();
    };
  }, []);

  const value = useMemo(() => ({ pushToast, dismissToast }), [pushToast, dismissToast]);

  return (
    <ToastContext.Provider value={value}>
      {children}
      <ToastContainer toasts={toasts} onDismiss={dismissToast} />
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
