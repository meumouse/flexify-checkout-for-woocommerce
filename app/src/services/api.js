import { readBootstrapConfig } from '../utils/bootstrap';

/**
 * Minimal REST client for the Flexify Checkout admin endpoints.
 *
 * Resolves the REST root and nonce from the localized bootstrap config.
 *
 * @since 6.0.0
 */
function buildUrl(endpoint) {
  const config = readBootstrapConfig() || {};
  const root = String(config.restUrl || '').replace(/\/$/, '');
  const path = String(endpoint || '').replace(/^\//, '');

  return `${root}/${path}`;
}

function buildHeaders() {
  const config = readBootstrapConfig() || {};

  return {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...(config.nonce ? { 'X-WP-Nonce': config.nonce } : {}),
  };
}

/**
 * Perform a GET request against the plugin REST namespace.
 *
 * @since 6.0.0
 * @param {string} endpoint - Endpoint path relative to the REST root.
 * @return {Promise<Object>} Parsed JSON response.
 */
export async function apiGet(endpoint) {
  const response = await fetch(buildUrl(endpoint), {
    method: 'GET',
    headers: buildHeaders(),
    credentials: 'same-origin',
  });

  if (!response.ok) {
    throw new Error(`GET ${endpoint} failed (${response.status}).`);
  }

  return response.json();
}

/**
 * Perform a POST request against the plugin REST namespace.
 *
 * @since 6.0.0
 * @param {string} endpoint - Endpoint path relative to the REST root.
 * @param {Object} body - JSON-serializable request body.
 * @return {Promise<Object>} Parsed JSON response.
 */
export async function apiPost(endpoint, body = {}) {
  const response = await fetch(buildUrl(endpoint), {
    method: 'POST',
    headers: buildHeaders(),
    credentials: 'same-origin',
    body: JSON.stringify(body),
  });

  if (!response.ok) {
    throw new Error(`POST ${endpoint} failed (${response.status}).`);
  }

  return response.json();
}
