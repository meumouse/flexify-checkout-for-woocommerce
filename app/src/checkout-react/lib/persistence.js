/**
 * Persist checkout field values to localStorage so a returning shopper finds
 * their data pre-filled — regardless of the active theme (classic or Swift).
 *
 * Shares the `flexify_checkout_form_data` key and the WooCommerce field-name
 * convention with the classic checkout (app/src/checkout/main.js), so data
 * typed on one theme carries over to the other. Only fields whitelisted by
 * Fields::get_localstorage_fields() (localized as config.localstorage_fields)
 * are stored.
 */

import config from '../config.js';
import { fieldBinding } from './fields.js';

const STORAGE_KEY = 'flexify_checkout_form_data';
const NOTE_FIELD = 'order_comments';

/**
 * Safe handle to window.localStorage (null when unavailable / privacy mode).
 *
 * @returns {Storage|null}
 */
function storage() {
  try {
    return typeof window !== 'undefined' ? window.localStorage : null;
  } catch {
    return null;
  }
}

/**
 * Field names allowed to be persisted.
 *
 * @returns {string[]}
 */
function whitelist() {
  return Array.isArray(config.localstorage_fields) ? config.localstorage_fields : [];
}

/**
 * Ids of the extra (non-address) fields declared by the checkout rules. Used to
 * keep stray keys (e.g. classic shipping_* fields the Swift theme doesn't use)
 * out of the extra-fields payload.
 *
 * @returns {Set<string>}
 */
function knownFieldIds() {
  const fields = (config.rules && config.rules.fields) || [];

  return new Set(fields.map((f) => f.id));
}

/**
 * Read persisted field data and split it back into the React state shape.
 *
 * @returns {{billing: object, extraFields: object, customerNote: string}}
 */
export function loadFormData() {
  const result = { billing: {}, extraFields: {}, customerNote: '' };
  const store = storage();

  if (!store) {
    return result;
  }

  let data;

  try {
    data = JSON.parse(store.getItem(STORAGE_KEY) || 'null');
  } catch {
    return result;
  }

  if (!data || typeof data !== 'object') {
    return result;
  }

  const allowed = whitelist();
  const knownIds = knownFieldIds();

  Object.entries(data).forEach(([name, value]) => {
    if (!allowed.includes(name)) {
      return;
    }

    if (typeof value !== 'string' && typeof value !== 'number') {
      return;
    }

    const str = String(value);

    if (name === NOTE_FIELD) {
      result.customerNote = str;
      return;
    }

    const binding = fieldBinding(name);

    if (binding.scope === 'billing') {
      result.billing[binding.key] = str;
    } else if (knownIds.has(name)) {
      result.extraFields[name] = str;
    }
  });

  return result;
}

/**
 * Persist the current field values, keyed by WooCommerce field name and
 * filtered to the whitelist.
 *
 * @param {{billing?: object, extraFields?: object, customerNote?: string}} state
 * @returns {void}
 */
export function saveFormData({ billing = {}, extraFields = {}, customerNote = '' } = {}) {
  const store = storage();

  if (!store) {
    return;
  }

  const allowed = whitelist();
  const data = {};

  const put = (name, value) => {
    if (!allowed.includes(name)) {
      return;
    }

    if (value == null || (typeof value !== 'string' && typeof value !== 'number')) {
      return;
    }

    data[name] = String(value);
  };

  Object.entries(billing).forEach(([key, value]) => put(`billing_${key}`, value));
  Object.entries(extraFields).forEach(([name, value]) => put(name, value));
  put(NOTE_FIELD, customerNote);

  try {
    store.setItem(STORAGE_KEY, JSON.stringify(data));
  } catch {
    // ignore quota / privacy-mode write errors
  }
}

/**
 * Clear persisted field data (e.g. after a successful order).
 *
 * @returns {void}
 */
export function clearFormData() {
  const store = storage();

  if (!store) {
    return;
  }

  try {
    store.removeItem(STORAGE_KEY);
  } catch {
    // ignore
  }
}
