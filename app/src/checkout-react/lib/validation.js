import { t } from '../config.js';
import { fieldValue } from './fields.js';
import { isFieldVisible } from './conditions.js';

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/**
 * Whether a field value counts as empty (covers text/select/date strings and
 * the '1' | '' convention used for checkboxes).
 *
 * @param {*} value
 * @returns {boolean}
 */
function isEmpty(value) {
  if (value === null || value === undefined || value === false) {
    return true;
  }

  return String(value).trim() === '';
}

/**
 * Validate a single field against its value. Returns an error message, or an
 * empty string when the field is valid.
 *
 * Only required-presence and format checks that can run client-side live here;
 * deeper rules (CPF/CNPJ, phone) are still enforced server-side on order
 * placement.
 *
 * @param {object} field Field definition.
 * @param {*}      value Current value.
 * @returns {string}
 */
export function validateField(field, value) {
  if (field.required && isEmpty(value)) {
    return t('field_required', 'Este campo é obrigatório.');
  }

  if (!isEmpty(value) && field.type === 'email' && !EMAIL_RE.test(String(value).trim())) {
    return t('invalid_email', 'Informe um e-mail válido.');
  }

  return '';
}

/**
 * Validate every (enabled) field in a step against the current values.
 *
 * @param {Array<object>} fields      Field definitions to validate.
 * @param {object}        billing     Standard address values.
 * @param {object}        extraFields Flexify-managed extra field values.
 * @returns {Object<string,string>} Map of field id to error message (only for invalid fields).
 */
export function validateFields(fields, billing, extraFields) {
  const errors = {};

  (fields || []).forEach((field) => {
    if (!field || field.enabled === false) {
      return;
    }

    // Skip fields hidden by a condition (e.g. CNPJ while "Individual" is
    // selected) so their required flag never blocks the step.
    if (!isFieldVisible(field.id, billing, extraFields)) {
      return;
    }

    const message = validateField(field, fieldValue(field.id, billing, extraFields));

    if (message) {
      errors[field.id] = message;
    }
  });

  return errors;
}
