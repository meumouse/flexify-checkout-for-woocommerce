/**
 * Bootstrap config localized by Core\Assets (flexify_react_checkout).
 *
 * Centralizes access so components never read the global directly and tests
 * can stub it.
 */

const fallback = {
  rest_url: '/wp-json/flexify-checkout/v1/',
  store_api_url: '/wp-json/wc/store/v1/',
  wp_rest_nonce: '',
  store_api_nonce: '',
  ajax_url: '',
  is_user_logged_in: false,
  session_hash: '',
  base_country: 'BR',
  geo: { countries: [], states: {} },
  localstorage_fields: [],
  currency: 'BRL',
  currency_symbol: 'R$',
  logo: '',
  reservation: { enabled: false, minutes: 15, title: '' },
  urls: {},
  config: { gateways: [], required_fields: [], split_payment: false },
  rules: { fields: [], steps: [] },
  settings: {},
  flags: { whatsapp_login: false, address_search: false, split_payment: false, optimize_digital: false },
  i18n: {},
  editor: false,
  builder_nonce: '',
  mode: 'checkout',
  thankyou: null,
};

const config = { ...fallback, ...(typeof window !== 'undefined' ? window.flexify_react_checkout || {} : {}) };

export default config;

/**
 * Live text overrides pushed by the builder preview (postMessage). Only ever
 * set by EditorApp so the operator sees text edits instantly; the real
 * storefront reads config.i18n directly (seeded server-side from the settings).
 *
 * @type {Object<string,string>|null}
 */
let textOverrides = null;

/**
 * Set (or clear) the live text overrides.
 *
 * @param {Object<string,string>|null} map i18n key => text, or null to clear.
 * @returns {void}
 */
export function setTextOverrides(map) {
  textOverrides = map && typeof map === 'object' ? map : null;
}

/**
 * Translate a key from the localized i18n map, falling back to the given text.
 *
 * In builder preview, a non-empty override for the key wins so text edits show
 * live without a reload.
 *
 * @param {string} key Lookup key.
 * @param {string} fallbackText Default text.
 * @returns {string}
 */
export function t(key, fallbackText = '') {
  if (textOverrides && typeof textOverrides[key] === 'string' && textOverrides[key] !== '') {
    return textOverrides[key];
  }

  return (config.i18n && config.i18n[key]) || fallbackText || key;
}
