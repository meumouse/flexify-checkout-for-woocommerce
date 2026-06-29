import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import storeApi from '../api/storeApi.js';
import config from '../config.js';
import { resolveAvailableGateways } from '../lib/gateways.js';
import { isEditor } from '../lib/editorBridge.js';
import { clearFormData, loadFormData, saveFormData } from '../lib/persistence.js';

const CheckoutContext = createContext(null);

const emptyAddress = {
  first_name: '',
  last_name: '',
  company: '',
  address_1: '',
  address_2: '',
  city: '',
  state: '',
  postcode: '',
  country: config.base_country || 'BR',
  email: '',
  phone: '',
};

export function CheckoutProvider({ children }) {
  // Persist field data to localStorage on every theme — but never in the live
  // builder preview, where it would leak the admin's typing into the store.
  const persist = !isEditor();
  // Seed the form from any previously saved data (shared with the classic
  // checkout via the flexify_checkout_form_data key). Computed once.
  const saved = useMemo(() => (persist ? loadFormData() : { billing: {}, extraFields: {}, customerNote: '' }), [persist]);

  const [cart, setCart] = useState(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [placingOrder, setPlacingOrder] = useState(false);
  const [error, setError] = useState('');
  const [billing, setBilling] = useState(() => ({ ...emptyAddress, ...saved.billing }));
  const [extraFields, setExtraFields] = useState(() => saved.extraFields);
  const [selectedGateway, setSelectedGateway] = useState('');
  const [customerNote, setCustomerNote] = useState(() => saved.customerNote);
  // Inline per-field validation errors keyed by field id. Populated when a step
  // navigation is blocked; cleared per field as the customer edits it.
  const [fieldErrors, setFieldErrors] = useState({});

  const loadCart = useCallback(async () => {
    setLoading(true);

    try {
      const data = await storeApi.getCart();
      setCart(data);

      if (data && data.billing_address) {
        setBilling((prev) => ({ ...prev, ...stripEmpty(data.billing_address) }));
      }
    } catch (e) {
      setError(e.message || config.i18n?.generic_error || '');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadCart();
  }, [loadCart]);

  // Default the gateway selection from the live Store API availability, and
  // reset it whenever the current pick is no longer available (e.g. a cart
  // change removed a conditional gateway).
  useEffect(() => {
    const gateways = resolveAvailableGateways(cart);

    if (!gateways.length) {
      return;
    }

    const isAvailable = gateways.some((gateway) => gateway.id === selectedGateway);

    if (!selectedGateway || !isAvailable) {
      setSelectedGateway(gateways[0].id);
    }
  }, [cart, selectedGateway]);

  // Persist field data whenever it changes, so a returning shopper finds it
  // pre-filled. Skipped in the builder preview.
  useEffect(() => {
    if (!persist) {
      return;
    }

    saveFormData({ billing, extraFields, customerNote });
  }, [persist, billing, extraFields, customerNote]);

  const withBusy = useCallback(async (fn) => {
    setBusy(true);
    setError('');

    try {
      return await fn();
    } catch (e) {
      setError(e.message || config.i18n?.generic_error || '');
      throw e;
    } finally {
      setBusy(false);
    }
  }, []);

  // Drop a single field's error (called as the customer edits that field).
  const clearFieldError = useCallback((id) => {
    setFieldErrors((prev) => {
      if (!prev[id]) {
        return prev;
      }

      const next = { ...prev };
      delete next[id];

      return next;
    });
  }, []);

  const applyCoupon = useCallback((code) => withBusy(async () => setCart(await storeApi.applyCoupon(code))), [withBusy]);
  const removeCoupon = useCallback((code) => withBusy(async () => setCart(await storeApi.removeCoupon(code))), [withBusy]);

  const updateItemQuantity = useCallback(
    (key, quantity) => withBusy(async () => setCart(await storeApi.updateItem(key, Math.max(1, Number(quantity) || 1)))),
    [withBusy],
  );

  const removeItem = useCallback(
    (key) => withBusy(async () => setCart(await storeApi.removeItem(key))),
    [withBusy],
  );

  const updateAddress = useCallback(
    (patch) => withBusy(async () => {
      const next = { ...billing, ...patch };
      setBilling(next);
      const data = await storeApi.updateCustomer({ billing_address: next, shipping_address: next });
      setCart(data);

      return data;
    }),
    [billing, withBusy],
  );

  const selectShippingRate = useCallback(
    (packageId, rateId) => withBusy(async () => setCart(await storeApi.selectShippingRate(packageId, rateId))),
    [withBusy],
  );

  const placeOrder = useCallback(
    () => withBusy(async () => {
      // Show the purchase animation overlay for the whole submission. It stays up
      // through the redirect; only a non-navigating result or an error clears it.
      setPlacingOrder(true);

      try {
        const payload = {
          billing_address: billing,
          shipping_address: billing,
          payment_method: selectedGateway,
          customer_note: customerNote,
          extensions: { 'flexify-checkout': { fields: extraFields } },
        };

        const result = await storeApi.placeOrder(payload);
        const redirect = result?.payment_result?.redirect_url;

        if (redirect) {
          clearFormData();
          window.location.href = redirect;
        } else if (result?.order_id) {
          clearFormData();
          window.location.href = (config.urls?.order_received || config.urls?.checkout || '/');
        } else {
          setPlacingOrder(false);
        }

        return result;
      } catch (e) {
        setPlacingOrder(false);
        throw e;
      }
    }),
    [billing, selectedGateway, customerNote, extraFields, withBusy],
  );

  const value = useMemo(
    () => ({
      cart,
      loading,
      busy,
      placingOrder,
      error,
      setError,
      billing,
      setBilling,
      extraFields,
      setExtraFields,
      selectedGateway,
      setSelectedGateway,
      customerNote,
      setCustomerNote,
      fieldErrors,
      setFieldErrors,
      clearFieldError,
      loadCart,
      applyCoupon,
      removeCoupon,
      updateItemQuantity,
      removeItem,
      updateAddress,
      selectShippingRate,
      placeOrder,
    }),
    [
      cart, loading, busy, placingOrder, error, billing, extraFields, selectedGateway, customerNote, fieldErrors,
      clearFieldError, loadCart, applyCoupon, removeCoupon, updateItemQuantity, removeItem, updateAddress,
      selectShippingRate, placeOrder,
    ],
  );

  return <CheckoutContext.Provider value={value}>{children}</CheckoutContext.Provider>;
}

function stripEmpty(obj) {
  const out = {};

  Object.keys(obj || {}).forEach((key) => {
    if (obj[key] !== '' && obj[key] !== null && obj[key] !== undefined) {
      out[key] = obj[key];
    }
  });

  return out;
}

export function useCheckout() {
  const ctx = useContext(CheckoutContext);

  if (!ctx) {
    throw new Error('useCheckout must be used inside <CheckoutProvider>');
  }

  return ctx;
}
