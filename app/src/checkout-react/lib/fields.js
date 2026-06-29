import config from '../config.js';
import { fieldById, itemsForStep } from './layout.js';

/**
 * Standard WooCommerce Store API address keys. Anything else is treated as a
 * Flexify-managed extra field and collected separately (sent via the checkout
 * extension payload).
 */
const STANDARD_ADDRESS_KEYS = new Set([
  'first_name', 'last_name', 'company', 'address_1', 'address_2',
  'city', 'state', 'postcode', 'country', 'email', 'phone',
]);

/**
 * Resolve where a field's value is stored.
 *
 * @param {string} fieldId Field id (e.g. billing_first_name).
 * @returns {{scope: 'billing'|'extra', key: string}}
 */
export function fieldBinding(fieldId) {
  if (fieldId.indexOf('billing_') === 0) {
    const key = fieldId.slice(8);

    if (STANDARD_ADDRESS_KEYS.has(key)) {
      return { scope: 'billing', key };
    }
  }

  return { scope: 'extra', key: fieldId };
}

/**
 * Read a field's current value from the appropriate store (billing or extra).
 *
 * @param {string} fieldId     Field id.
 * @param {object} billing     Standard address values.
 * @param {object} extraFields Flexify-managed extra field values.
 * @returns {*}
 */
export function fieldValue(fieldId, billing, extraFields) {
  const binding = fieldBinding(fieldId);

  return binding.scope === 'billing'
    ? billing[binding.key] ?? ''
    : extraFields[fieldId] ?? '';
}

/**
 * Return the enabled checkout fields for a given step, sorted by priority.
 *
 * @param {number|string} step Step index.
 * @returns {Array<object>}
 */
export function fieldsForStep(step) {
  const all = (config.rules && config.rules.fields) || [];

  return all
    .filter((f) => f.enabled && String(f.step) === String(step))
    .sort((a, b) => Number(a.priority || 0) - Number(b.priority || 0));
}

/**
 * Resolve the enabled field definitions placed on a builder step, in order.
 *
 * @param {object} step Layout step.
 * @returns {Array<object>}
 */
export function builderStepFields(step) {
  return itemsForStep(step)
    .filter((item) => item.kind === 'field')
    .map((item) => fieldById(item.field_id))
    .filter(Boolean);
}
