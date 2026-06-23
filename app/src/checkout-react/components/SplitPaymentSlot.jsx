import { useEffect, useState } from 'react';
import config from '../config.js';

/**
 * Extension point for a future Split-payments addon.
 *
 * When the split flag is on, the addon registers a renderer with
 * `window.flexifyCheckout.registerSplitUI(fn)`; `fn` receives the checkout
 * context and returns a React element (or DOM-ready markup). Without an addon
 * the slot stays hidden, so enabling the flag alone shows nothing broken.
 */
export default function SplitPaymentSlot({ ctx }) {
  const [renderer, setRenderer] = useState(() => getRegisteredRenderer());

  useEffect(() => {
    if (!config.flags?.split_payment) {
      return undefined;
    }

    const handler = (fn) => setRenderer(() => fn);

    // Expose a tiny registration API for the addon.
    window.flexifyCheckout = window.flexifyCheckout || {};
    window.flexifyCheckout.registerSplitUI = handler;

    // Pick up an addon that registered before this mounted.
    const pre = getRegisteredRenderer();
    if (pre) setRenderer(() => pre);

    return () => {
      if (window.flexifyCheckout && window.flexifyCheckout.registerSplitUI === handler) {
        delete window.flexifyCheckout.registerSplitUI;
      }
    };
  }, []);

  if (!config.flags?.split_payment || typeof renderer !== 'function') {
    return null;
  }

  let content = null;

  try {
    content = renderer(ctx);
  } catch (e) {
    content = null;
  }

  if (!content) {
    return null;
  }

  return (
    <div className="mt-4 rounded-lg border border-dashed border-slate-200 p-4" data-flexify-split-slot>
      {content}
    </div>
  );
}

function getRegisteredRenderer() {
  return (typeof window !== 'undefined' && window.flexifyCheckout && window.flexifyCheckout.splitRenderer) || null;
}
