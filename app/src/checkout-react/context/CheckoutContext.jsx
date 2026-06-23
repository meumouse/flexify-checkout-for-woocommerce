import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import storeApi from '../api/storeApi.js';
import config from '../config.js';

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
  const [cart, setCart] = useState(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [billing, setBilling] = useState(emptyAddress);
  const [extraFields, setExtraFields] = useState({});
  const [selectedGateway, setSelectedGateway] = useState('');
  const [customerNote, setCustomerNote] = useState('');

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

  // Default the gateway selection once config + cart are ready.
  useEffect(() => {
    const gateways = config.config?.gateways || [];

    if (!selectedGateway && gateways.length) {
      setSelectedGateway(gateways[0].id);
    }
  }, [selectedGateway]);

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

  const applyCoupon = useCallback((code) => withBusy(async () => setCart(await storeApi.applyCoupon(code))), [withBusy]);
  const removeCoupon = useCallback((code) => withBusy(async () => setCart(await storeApi.removeCoupon(code))), [withBusy]);

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
        window.location.href = redirect;
      } else if (result?.order_id) {
        window.location.href = (config.urls?.order_received || config.urls?.checkout || '/');
      }

      return result;
    }),
    [billing, selectedGateway, customerNote, extraFields, withBusy],
  );

  const value = useMemo(
    () => ({
      cart,
      loading,
      busy,
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
      loadCart,
      applyCoupon,
      removeCoupon,
      updateAddress,
      selectShippingRate,
      placeOrder,
    }),
    [
      cart, loading, busy, error, billing, extraFields, selectedGateway, customerNote,
      loadCart, applyCoupon, removeCoupon, updateAddress, selectShippingRate, placeOrder,
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
