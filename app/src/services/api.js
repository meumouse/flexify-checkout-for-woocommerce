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
 * Perform a DELETE request against the plugin REST namespace.
 *
 * @since 6.0.0
 * @param {string} endpoint - Endpoint path relative to the REST root.
 * @return {Promise<Object>} Parsed JSON response.
 */
export async function apiDelete(endpoint) {
  const response = await fetch(buildUrl(endpoint), {
    method: 'DELETE',
    headers: buildHeaders(),
    credentials: 'same-origin',
  });

  if (!response.ok) {
    throw new Error(`DELETE ${endpoint} failed (${response.status}).`);
  }

  return response.json();
}

/**
 * Perform a multipart/form-data POST request against the plugin REST namespace.
 *
 * @since 6.0.0
 * @param {string} endpoint - Endpoint path relative to the REST root.
 * @param {FormData} formData - Form data payload (may contain files).
 * @return {Promise<Object>} Parsed JSON response.
 */
export async function apiPostForm(endpoint, formData) {
  const config = readBootstrapConfig() || {};

  const response = await fetch(buildUrl(endpoint), {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      ...(config.nonce ? { 'X-WP-Nonce': config.nonce } : {}),
    },
    credentials: 'same-origin',
    body: formData,
  });

  if (!response.ok) {
    throw new Error(`POST ${endpoint} failed (${response.status}).`);
  }

  return response.json();
}

/**
 * Fetch an endpoint as a binary blob and trigger a browser download.
 *
 * Used for file exports (e.g. CSV) that the REST endpoint streams directly
 * instead of wrapping in JSON. The REST nonce still travels in the headers, so
 * the download stays authenticated.
 *
 * @since 6.0.0
 * @param {string} endpoint - Endpoint path relative to the REST root.
 * @param {string} filename - Fallback filename for the saved file.
 * @return {Promise<void>}
 */
export async function apiDownload(endpoint, filename = 'export.csv') {
  const config = readBootstrapConfig() || {};

  const response = await fetch(buildUrl(endpoint), {
    method: 'GET',
    headers: {
      ...(config.nonce ? { 'X-WP-Nonce': config.nonce } : {}),
    },
    credentials: 'same-origin',
  });

  if (!response.ok) {
    throw new Error(`GET ${endpoint} failed (${response.status}).`);
  }

  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');

  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(url);
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
