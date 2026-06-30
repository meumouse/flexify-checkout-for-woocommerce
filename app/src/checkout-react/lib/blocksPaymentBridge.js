/**
 * Bridge a WooCommerce Blocks payment method into the Swift (React) checkout.
 *
 * Gateways like Mercado Pago ship a WooCommerce Blocks payment integration: a
 * React component (built against `wp.element`) registered with
 * `wc.wcBlocksRegistry`, plus the runtime scripts (SDK, tokenization, 3DS) it
 * needs. The Swift checkout is a separate React app, so it cannot host that
 * component as a child of its own tree. Instead we render the gateway component
 * with WordPress's React (`wp.element`) into a plain DOM node, providing the same
 * props the native Checkout block would, and reconstructing the Blocks payment
 * "emitter" contract (eventRegistration / emitResponse).
 *
 * On submit, the Swift checkout calls `runActivePaymentSetup()` to run the
 * gateway's `onPaymentSetup` observers, which create the card token and return
 * the data to send to the Store API as `payment_data`. After the order is placed
 * we run `runActiveCheckoutSuccess()` so flows like 3-D Secure can complete.
 *
 * The PHP side (Blocks_Payment_Bridge) enqueues the gateway scripts and publishes
 * the `getSetting('<name>_data')` payload these components read on registration.
 */

// WooCommerce Blocks payment extensibility constants. Stable, documented values
// from the @woocommerce/types emitResponse contract.
const RESPONSE_TYPES = { SUCCESS: 'success', ERROR: 'error', FAIL: 'failure' };
const NOTICE_CONTEXTS = { PAYMENTS: 'wc/checkout/payments', EXPRESS_PAYMENTS: 'wc/checkout/express-payments' };
const emitResponse = { responseTypes: RESPONSE_TYPES, noticeContexts: NOTICE_CONTEXTS };

// The single currently-mounted gateway. Only one payment method is selected at a
// time in the checkout, so a single active slot is enough.
let active = null;

function wpElement() {
  return typeof window !== 'undefined' ? window.wp?.element : undefined;
}

function blocksRegistry() {
  return typeof window !== 'undefined' ? window.wc?.wcBlocksRegistry : undefined;
}

/**
 * Whether the WooCommerce Blocks runtime needed to mount a gateway is present.
 */
export function blocksRuntimeReady() {
  return !!(wpElement()?.cloneElement && blocksRegistry()?.getPaymentMethods);
}

// Poll for a value until it resolves or the timeout elapses. Gateway scripts and
// their registration land asynchronously, after the Swift bundle boots.
async function waitFor(getter, timeout = 15000, interval = 100) {
  const start = Date.now();
  let value = getter();

  while (!value) {
    if (Date.now() - start >= timeout) {
      return null;
    }

    await new Promise((resolve) => setTimeout(resolve, interval));
    value = getter();
  }

  return value;
}

function getRegisteredMethod(name) {
  const methods = blocksRegistry()?.getPaymentMethods?.() || {};

  return methods[name] || null;
}

// A minimal Blocks event emitter: each registration returns an unsubscribe fn,
// matching what `eventRegistration` provides inside the native Checkout block.
function createEmitter() {
  const make = () => {
    const observers = new Set();
    const subscribe = (cb) => {
      observers.add(cb);

      return () => observers.delete(cb);
    };

    return { observers, subscribe };
  };

  const paymentSetup = make();
  const checkoutSuccess = make();
  const checkoutFail = make();
  const checkoutValidation = make();

  return {
    sets: { paymentSetup, checkoutSuccess, checkoutFail, checkoutValidation },
    registration: {
      onPaymentSetup: paymentSetup.subscribe,
      // Legacy alias still used by some integrations.
      onPaymentProcessing: paymentSetup.subscribe,
      onCheckoutValidation: checkoutValidation.subscribe,
      onCheckoutSuccess: checkoutSuccess.subscribe,
      onCheckoutFail: checkoutFail.subscribe,
      onCheckoutAfterProcessingWithSuccess: checkoutSuccess.subscribe,
      onCheckoutAfterProcessingWithError: checkoutFail.subscribe,
    },
  };
}

// Build the `billing` prop the gateway component reads (cart total in minor units
// + currency metadata). Shape mirrors the native Checkout block's billing prop.
function buildBilling(cart) {
  const totals = cart?.totals || {};
  const minorUnit = Number(totals.currency_minor_unit ?? 2);

  return {
    cartTotal: { value: String(totals.total_price ?? '0'), label: '' },
    cartTotalItems: [],
    currency: {
      code: totals.currency_code || 'BRL',
      symbol: totals.currency_symbol || '',
      minorUnit,
      decimalSeparator: totals.currency_decimal_separator || '.',
      thousandSeparator: totals.currency_thousand_separator || '',
      prefix: totals.currency_prefix || '',
      suffix: totals.currency_suffix || '',
    },
    billingData: {},
    billingAddress: {},
    customerId: 0,
  };
}

/**
 * Mount a Blocks gateway component into a DOM container.
 *
 * @param {HTMLElement} container Target node (owned by the bridge, not React).
 * @param {string} name Registered payment method name (e.g. woo-mercado-pago-custom).
 * @param {object} opts
 * @param {() => object} opts.getCart Returns the current Store API cart.
 * @param {() => void} [opts.onSubmit] Triggers order placement (e.g. wallet button).
 * @returns {Promise<boolean>} Whether the component mounted.
 */
export async function mountBlocksGateway(container, name, { getCart, onSubmit } = {}) {
  await unmountBlocksGateway();

  const element = wpElement();

  if (!element || !container) {
    return false;
  }

  const method = await waitFor(() => getRegisteredMethod(name));

  if (!method || !method.content) {
    return false;
  }

  const emitter = createEmitter();
  const root = element.createRoot ? element.createRoot(container) : null;

  const render = () => {
    const cart = getCart?.();
    const props = {
      eventRegistration: emitter.registration,
      emitResponse,
      billing: buildBilling(cart),
      shippingData: { shippingRates: [], needsShipping: false, shippingAddress: {} },
      onSubmit: onSubmit || (() => {}),
      components: window.wc?.blocksComponents || {},
      activePaymentMethod: name,
      paymentStatus: {},
      shouldSavePayment: false,
      setShouldSavePayment: () => {},
    };

    // The component is registered as a ready-made element with no props; clone it
    // to inject the props the native Checkout block would pass.
    const el = element.cloneElement(method.content, props);

    if (root) {
      root.render(el);
    } else if (element.render) {
      element.render(el, container);
    }
  };

  active = { name, root, container, emitter, render };
  render();

  return true;
}

/**
 * Re-render the active gateway with a fresh billing prop (e.g. cart total change),
 * so the gateway can re-initialize its amount-dependent card form / installments.
 */
export function refreshActiveBilling() {
  if (active) {
    active.render();
  }
}

/**
 * Unmount the active gateway and release its React root.
 */
export async function unmountBlocksGateway() {
  if (!active) {
    return;
  }

  const current = active;
  active = null;

  try {
    if (current.root) {
      current.root.unmount();
    } else {
      wpElement()?.unmountComponentAtNode?.(current.container);
    }
  } catch (e) {
    // Ignore teardown errors; the container is discarded by React either way.
  }
}

/**
 * Whether a Blocks gateway is currently mounted.
 */
export function hasActiveBlocksGateway() {
  return !!active;
}

// Run a set of emitter observers in order, awaiting each. Throws on the first
// non-success response so the caller can surface the gateway's message.
async function runObservers(observers, arg, fallbackMessage) {
  for (const cb of [...observers]) {
    const res = await cb(arg);

    if (res && res.type && res.type !== RESPONSE_TYPES.SUCCESS) {
      const error = new Error(res.message || fallbackMessage);
      error.blocksResponse = res;
      throw error;
    }
  }
}

/**
 * Run the active gateway's validation + payment-setup observers and collect the
 * data to send to the Store API as `payment_data`.
 *
 * @returns {Promise<Array<{key:string,value:string}>|null>} payment_data array, or
 *          null when no Blocks gateway is mounted.
 */
export async function runActivePaymentSetup() {
  if (!active) {
    return null;
  }

  await runObservers(active.emitter.sets.checkoutValidation.observers, undefined, 'Verifique os dados de pagamento.');

  let paymentData = {};

  for (const cb of [...active.emitter.sets.paymentSetup.observers]) {
    const res = await cb();

    if (!res) {
      continue;
    }

    if (res.type && res.type !== RESPONSE_TYPES.SUCCESS) {
      const error = new Error(res.message || 'Não foi possível processar o pagamento.');
      error.blocksResponse = res;
      throw error;
    }

    const data = res?.meta?.paymentMethodData;

    if (data && typeof data === 'object') {
      paymentData = { ...paymentData, ...data };
    }
  }

  return Object.entries(paymentData).map(([key, value]) => ({
    key,
    value: value === null || value === undefined ? '' : String(value),
  }));
}

/**
 * Translate a Store API checkout response into the `processingResponse` shape the
 * Blocks success/fail observers expect.
 */
export function toProcessingResponse(result) {
  const pr = result?.payment_result || {};
  const details = {};

  (pr.payment_details || []).forEach((entry) => {
    if (entry && entry.key !== undefined && entry.key !== null) {
      details[entry.key] = entry.value;
    }
  });

  return {
    paymentStatus: pr.payment_status || '',
    paymentDetails: details,
    redirectUrl: pr.redirect_url || '',
  };
}

/**
 * Run the active gateway's checkout-success observers (e.g. 3-D Secure flow).
 * Resolves once they complete; throws if a gateway reports failure.
 */
export async function runActiveCheckoutSuccess(processingResponse) {
  if (!active) {
    return;
  }

  await runObservers(
    active.emitter.sets.checkoutSuccess.observers,
    { processingResponse },
    'Falha ao confirmar o pagamento.',
  );
}

/**
 * Run the active gateway's checkout-fail observers, so it can reset its state and
 * surface a message after a failed order placement.
 */
export async function runActiveCheckoutFail(processingResponse) {
  if (!active) {
    return;
  }

  for (const cb of [...active.emitter.sets.checkoutFail.observers]) {
    try {
      await cb({ processingResponse });
    } catch (e) {
      // A failing fail-handler must not mask the original error.
    }
  }
}
