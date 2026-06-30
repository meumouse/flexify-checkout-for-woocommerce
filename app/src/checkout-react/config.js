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
 * Translate a key from the localized i18n map, falling back to the given text.
 *
 * @param {string} key Lookup key.
 * @param {string} fallbackText Default text.
 * @returns {string}
 */
export function t(key, fallbackText = '') {
  return (config.i18n && config.i18n[key]) || fallbackText || key;
}
