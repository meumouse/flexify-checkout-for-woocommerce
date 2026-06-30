/**
 * React checkout entry point.
 *
 * Mounts the React checkout into #flexify-react-checkout (rendered by
 * templates/template-react.php). Bootstraps from the localized
 * flexify_react_checkout object. Built as an IIFE bundle by
 * vite.checkout-react.config.js.
 *
 * @since 6.0.0
 */
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import './styles.css';
import config from './config.js';
import { ToastProvider } from './context/ToastContext.jsx';
import { CheckoutProvider } from './context/CheckoutContext.jsx';
import App from './components/App.jsx';
import ThankYouPage from './components/ThankYouPage.jsx';

function mount() {
  const root = document.getElementById('flexify-react-checkout');

  if (!root) {
    return;
  }

  // Thank-you (order-received) mode renders a standalone confirmation page —
  // no cart/checkout context needed, the order payload is localized.
  if (config.mode === 'thankyou') {
    createRoot(root).render(
      <StrictMode>
        <ToastProvider>
          <ThankYouPage />
        </ToastProvider>
      </StrictMode>,
    );

    return;
  }

  createRoot(root).render(
    <StrictMode>
      <ToastProvider>
        <CheckoutProvider>
          <App />
        </CheckoutProvider>
      </ToastProvider>
    </StrictMode>,
  );
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mount);
} else {
  mount();
}
