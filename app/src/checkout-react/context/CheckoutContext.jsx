import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import storeApi from '../api/storeApi.js';
import flexifyApi from '../api/flexifyApi.js';
import config, { t } from '../config.js';
import { resolveAvailableGateways } from '../lib/gateways.js';
import {
  hasActiveBlocksGateway,
  runActiveCheckoutFail,
  runActiveCheckoutSuccess,
  runActivePaymentSetup,
  toProcessingResponse,
} from '../lib/blocksPaymentBridge.js';
import { stateOptions } from '../lib/geo.js';
import { isEditor } from '../lib/editorBridge.js';
import { clearFormData, loadFormData, saveFormData } from '../lib/persistence.js';
import { useToast } from './ToastContext.jsx';

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

  const { pushToast } = useToast();

  const [cart, setCart] = useState(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [placingOrder, setPlacingOrder] = useState(false);
  const [billing, setBilling] = useState(() => ({ ...emptyAddress, ...saved.billing }));
  const [extraFields, setExtraFields] = useState(() => saved.extraFields);
  const [selectedGateway, setSelectedGateway] = useState('');
  const [customerNote, setCustomerNote] = useState(() => saved.customerNote);
  // Inline per-field validation errors keyed by field id. Populated when a step
  // navigation is blocked; cleared per field as the customer edits it.
  const [fieldErrors, setFieldErrors] = useState({});
  // Customer address book (Pro): seeded server-side for the logged-in user, kept
  // in sync as addresses are saved/removed from the checkout.
  const [savedAddresses, setSavedAddresses] = useState(() => (Array.isArray(config.saved_addresses) ? config.saved_addresses : []));
  const savedAddressesEnabled = !!(config.flags && config.flags.saved_addresses && config.is_user_logged_in);

  const loadCart = useCallback(async () => {
    setLoading(true);

    try {
      const data = await storeApi.getCart();
      setCart(data);

      if (data && data.billing_address) {
        setBilling((prev) => ({ ...prev, ...stripEmpty(data.billing_address) }));
      }
    } catch (e) {
      pushToast({ type: 'error', message: e.message || config.i18n?.generic_error || '' });
    } finally {
      setLoading(false);
    }
  }, [pushToast]);

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

  // When the country changes, drop a now-invalid state so a code from the
  // previous country is never submitted. Skipped when the new country defines
  // no states (free-text) or the state is already empty/valid.
  useEffect(() => {
    setBilling((prev) => {
      if (!prev.state) {
        return prev;
      }

      const options = stateOptions(prev.country);

      if (!options.length || options.some((opt) => String(opt.value) === String(prev.state))) {
        return prev;
      }

      return { ...prev, state: '' };
    });
  }, [billing.country]);

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

    try {
      return await fn();
    } catch (e) {
      pushToast({ type: 'error', message: e.message || config.i18n?.generic_error || '' });
      throw e;
    } finally {
      setBusy(false);
    }
  }, [pushToast]);

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

  const applyCoupon = useCallback(
    (code) => withBusy(async () => {
      const data = await storeApi.applyCoupon(code);
      setCart(data);
      pushToast({ type: 'success', message: t('coupon_applied', 'Cupom aplicado com sucesso.') });

      return data;
    }),
    [withBusy, pushToast],
  );

  const removeCoupon = useCallback(
    (code) => withBusy(async () => {
      const data = await storeApi.removeCoupon(code);
      setCart(data);
      pushToast({ type: 'success', message: t('coupon_removed', 'Cupom removido.') });

      return data;
    }),
    [withBusy, pushToast],
  );

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

  // Apply a saved address to the form: restore the standard fields plus any
  // extra fields (street number, neighborhood, …) and sync with the Store API
  // so shipping rates recalculate.
  const applySavedAddress = useCallback(
    (address) => withBusy(async () => {
      const nextBilling = { ...emptyAddress, ...stripEmpty(address.billing || {}) };
      setBilling(nextBilling);

      if (address.extra && typeof address.extra === 'object') {
        setExtraFields((prev) => ({ ...prev, ...address.extra }));
      }

      const data = await storeApi.updateCustomer({ billing_address: nextBilling, shipping_address: nextBilling });
      setCart(data);

      return data;
    }),
    [withBusy],
  );

  // Save the address currently in the form to the customer's address book.
  const saveCurrentAddress = useCallback(
    (nickname) => withBusy(async () => {
      const res = await flexifyApi.savedAddressCreate({ nickname, billing, extra: extraFields, is_default: false });

      if (res && Array.isArray(res.addresses)) {
        setSavedAddresses(res.addresses);
      }

      pushToast({ type: 'success', message: t('address_saved', 'Endereço salvo.') });

      return res;
    }),
    [withBusy, billing, extraFields, pushToast],
  );

  const removeSavedAddress = useCallback(
    (id) => withBusy(async () => {
      const res = await flexifyApi.savedAddressDelete(id);

      if (res && Array.isArray(res.addresses)) {
        setSavedAddresses(res.addresses);
      }

      pushToast({ type: 'success', message: t('address_removed', 'Endereço removido.') });

      return res;
    }),
    [withBusy, pushToast],
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

        // For gateways rendered through the Blocks bridge (e.g. Mercado Pago
        // credit card), run their payment-setup step to tokenize the card and
        // collect the fields WooCommerce expects as `payment_data`.
        // Match the render gate in PaymentMethods: only card gateways go through
        // the Blocks bridge; pix / boleto keep Swift's own placement flow.
        const selected = resolveAvailableGateways(cart).find((g) => g.id === selectedGateway);
        const usesBlocks = !!selected?.blocks && selected.kind === 'card';

        if (usesBlocks) {
          // The gateway's payment component must be mounted to tokenize the card.
          if (!hasActiveBlocksGateway()) {
            throw new Error(t('payment_not_ready', 'O formulário de pagamento ainda está carregando. Aguarde um instante e tente novamente.'));
          }

          const paymentData = await runActivePaymentSetup();

          if (Array.isArray(paymentData) && paymentData.length) {
            payload.payment_data = paymentData;
          }
        }

        let result;

        try {
          result = await storeApi.placeOrder(payload);
        } catch (e) {
          // Let the gateway reset its state / surface a message on failure.
          if (usesBlocks) {
            await runActiveCheckoutFail(toProcessingResponse(e?.response));
          }

          throw e;
        }

        // Let the gateway complete any post-placement flow (e.g. 3-D Secure)
        // before we follow the redirect.
        if (usesBlocks) {
          await runActiveCheckoutSuccess(toProcessingResponse(result));
        }

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
    [billing, selectedGateway, customerNote, extraFields, cart, withBusy],
  );

  const value = useMemo(
    () => ({
      cart,
      loading,
      busy,
      placingOrder,
      pushToast,
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
      savedAddresses,
      savedAddressesEnabled,
      applySavedAddress,
      saveCurrentAddress,
      removeSavedAddress,
    }),
    [
      cart, loading, busy, placingOrder, pushToast, billing, extraFields, selectedGateway, customerNote, fieldErrors,
      clearFieldError, loadCart, applyCoupon, removeCoupon, updateItemQuantity, removeItem, updateAddress,
      selectShippingRate, placeOrder, savedAddresses, savedAddressesEnabled, applySavedAddress, saveCurrentAddress,
      removeSavedAddress,
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
