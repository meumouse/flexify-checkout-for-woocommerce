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

// Shared lucide-style SVG props so the per-type icons sit on the neutral
// shadcn card with a single accent colour (set by each icon below).
const iconProps = {
  width: 18,
  height: 18,
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  strokeWidth: 2,
  strokeLinecap: 'round',
  strokeLinejoin: 'round',
  'aria-hidden': true,
};

// Semantic per-type icons (lucide: circle-check, circle-x, info,
// triangle-alert). Colours are the only tint — the card itself stays neutral,
// matching the shadcn Sonner look.
const TOAST_ICONS = {
  success: (
    <svg {...iconProps} style={{ color: '#16a34a' }}>
      <circle cx="12" cy="12" r="10" />
      <path d="m9 12 2 2 4-4" />
    </svg>
  ),
  error: (
    <svg {...iconProps} style={{ color: '#dc2626' }}>
      <circle cx="12" cy="12" r="10" />
      <path d="m15 9-6 6" />
      <path d="m9 9 6 6" />
    </svg>
  ),
  info: (
    <svg {...iconProps} style={{ color: '#2563eb' }}>
      <circle cx="12" cy="12" r="10" />
      <path d="M12 16v-4" />
      <path d="M12 8h.01" />
    </svg>
  ),
  warning: (
    <svg {...iconProps} style={{ color: '#d97706' }}>
      <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
      <path d="M12 9v4" />
      <path d="M12 17h.01" />
    </svg>
  ),
};

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
      <Toaster position="top-center" closeButton icons={TOAST_ICONS} />
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
