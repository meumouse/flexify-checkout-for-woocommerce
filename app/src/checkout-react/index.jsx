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
import { CheckoutProvider } from './context/CheckoutContext.jsx';
import App from './components/App.jsx';

function mount() {
  const root = document.getElementById('flexify-react-checkout');

  if (!root) {
    return;
  }

  createRoot(root).render(
    <StrictMode>
      <CheckoutProvider>
        <App />
      </CheckoutProvider>
    </StrictMode>,
  );
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mount);
} else {
  mount();
}
