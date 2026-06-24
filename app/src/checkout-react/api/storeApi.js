import config from '../config.js';

/**
 * Thin WooCommerce Store API client.
 *
 * Handles the rotating `Nonce` header and the guest `Cart-Token`, both of which
 * the Store API returns on every response and expects back on the next call.
 * Cookies are sent (credentials: 'include') so a logged-in session is reused.
 */

let nonce = config.store_api_nonce || '';
let cartToken = (typeof window !== 'undefined' && window.localStorage)
  ? window.localStorage.getItem('flexify_cart_token') || ''
  : '';

const base = (config.store_api_url || '/wp-json/wc/store/v1/').replace(/\/?$/, '/');

function persistToken(token) {
  cartToken = token;

  if (typeof window !== 'undefined' && window.localStorage) {
    window.localStorage.setItem('flexify_cart_token', token);
  }
}

async function request(path, { method = 'GET', body } = {}) {
  const headers = {
    'Content-Type': 'application/json',
    Nonce: nonce,
    'X-WC-Store-API-Nonce': nonce,
  };

  if (cartToken) {
    headers['Cart-Token'] = cartToken;
  }

  const res = await fetch(base + path.replace(/^\//, ''), {
    method,
    headers,
    credentials: 'include',
    body: body ? JSON.stringify(body) : undefined,
  });

  // Capture rotated nonce / cart token for subsequent calls.
  const freshNonce = res.headers.get('Nonce');
  if (freshNonce) nonce = freshNonce;

  const freshToken = res.headers.get('Cart-Token');
  if (freshToken) persistToken(freshToken);

  const text = await res.text();
  const data = text ? JSON.parse(text) : null;

  if (!res.ok) {
    const message = data && (data.message || (data.data && data.data.message));
    const error = new Error(message || 'Store API request failed');
    error.response = data;
    error.status = res.status;
    throw error;
  }

  return data;
}

export const storeApi = {
  getCart: () => request('cart'),
  addItem: (id, quantity = 1) => request('cart/add-item', { method: 'POST', body: { id: Number(id), quantity: Number(quantity) || 1 } }),
  getReviews: (productId, perPage = 5) =>
    request(`products/reviews?product_id=${Number(productId)}&per_page=${Number(perPage) || 5}&orderby=date`),
  applyCoupon: (code) => request('cart/apply-coupon', { method: 'POST', body: { code } }),
  removeCoupon: (code) => request('cart/remove-coupon', { method: 'POST', body: { code } }),
  updateItem: (key, quantity) => request('cart/update-item', { method: 'POST', body: { key, quantity } }),
  removeItem: (key) => request('cart/remove-item', { method: 'POST', body: { key } }),
  selectShippingRate: (packageId, rateId) =>
    request('cart/select-shipping-rate', { method: 'POST', body: { package_id: packageId, rate_id: rateId } }),
  updateCustomer: (payload) => request('cart/update-customer', { method: 'POST', body: payload }),
  placeOrder: (payload) => request('checkout', { method: 'POST', body: payload }),
};

export default storeApi;
