import config from '../config.js';

/**
 * Client for the flexify-checkout/v1 endpoints (WhatsApp login, address search,
 * and the headless config/rules — though config/rules also arrive inline).
 */

const base = (config.rest_url || '/wp-json/flexify-checkout/v1/').replace(/\/?$/, '/');

async function request(path, { method = 'GET', body, params } = {}) {
  let url = base + path.replace(/^\//, '');

  if (params) {
    const search = new URLSearchParams(params).toString();
    url += (url.includes('?') ? '&' : '?') + search;
  }

  const res = await fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': config.wp_rest_nonce || '',
    },
    credentials: 'include',
    body: body ? JSON.stringify(body) : undefined,
  });

  const text = await res.text();
  const data = text ? JSON.parse(text) : null;

  if (!res.ok || (data && data.status === 'error')) {
    const error = new Error((data && data.message) || 'Request failed');
    error.response = data;
    throw error;
  }

  return data;
}

export const flexifyApi = {
  whatsappSend: (phone) => request('auth/whatsapp/send', { method: 'POST', body: { phone } }),
  whatsappVerify: (phone, code) => request('auth/whatsapp/verify', { method: 'POST', body: { phone, code } }),
  addressAutocomplete: (q, sessionToken) =>
    request('address/autocomplete', { params: { q, session_token: sessionToken || '' } }),
  addressDetails: (placeId, sessionToken) =>
    request('address/details', { params: { place_id: placeId, session_token: sessionToken || '' } }),
  setRestNonce: (value) => {
    if (value) config.wp_rest_nonce = value;
  },
};

export default flexifyApi;
