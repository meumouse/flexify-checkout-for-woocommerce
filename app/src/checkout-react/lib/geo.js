import config from '../config.js';

/**
 * Country/state reference data localized by PHP (Headless_Data::get_geo_data).
 *
 * `countries` is a flat option list; `states` maps an ISO country code to its
 * own option list. A country missing from `states` (or with an empty list) has
 * no registered states, so its state field should render as free text.
 */
const geo = config.geo && typeof config.geo === 'object' ? config.geo : { countries: [], states: {} };

const FIELD_COUNTRY = new Set(['billing_country', 'shipping_country']);
const FIELD_STATE = new Set(['billing_state', 'shipping_state']);

/**
 * Allowed countries as Select options ([{ value, text }]).
 *
 * @returns {Array<{value:string,text:string}>}
 */
export function countryOptions() {
  return Array.isArray(geo.countries) ? geo.countries : [];
}

/**
 * States for a country as Select options. Empty when the country registers no
 * states (the caller should then fall back to a free-text input).
 *
 * @param {string} country ISO country code.
 * @returns {Array<{value:string,text:string}>}
 */
export function stateOptions(country) {
  const states = geo.states && geo.states[country];

  return Array.isArray(states) ? states : [];
}

/**
 * Resolve the dynamic option list for a native country/state field, or null
 * when the field is not a geo field (the caller keeps the field's own options).
 *
 * @param {string} fieldId Field id.
 * @param {string} country Currently selected country code.
 * @returns {Array<{value:string,text:string}>|null}
 */
export function geoOptions(fieldId, country) {
  if (FIELD_COUNTRY.has(fieldId)) {
    return countryOptions();
  }

  if (FIELD_STATE.has(fieldId)) {
    return stateOptions(country);
  }

  return null;
}
