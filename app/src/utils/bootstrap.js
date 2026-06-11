import { createApp } from 'vue';
import { createPinia } from 'pinia';

/**
 * Read the minimal bootstrap config exposed by WordPress via wp_localize_script.
 *
 * WordPress localizes only what the client needs to fetch the page payload:
 * the REST root, a nonce, the page slug, and the endpoint to request.
 *
 * @since 6.0.0
 * @return {Object|null} Bootstrap config or null when it is unavailable.
 */
export function readBootstrapConfig() {
  const config = globalThis.flexifyCheckoutBootstrapConfig;

  return config && typeof config === 'object' ? config : null;
}

/**
 * Fetch the page bootstrap payload from the REST API.
 *
 * @since 6.0.0
 * @param {Object} config - Bootstrap config from readBootstrapConfig().
 * @return {Promise<Object>} Parsed bootstrap payload.
 */
async function fetchBootstrap(config) {
  const root = String(config.restUrl || '').replace(/\/$/, '');
  const endpoint = String(config.endpoint || '').replace(/^\//, '');
  const url = `${root}/${endpoint}`;

  const response = await fetch(url, {
    method: 'GET',
    headers: {
      Accept: 'application/json',
      ...(config.nonce ? { 'X-WP-Nonce': config.nonce } : {}),
    },
    credentials: 'same-origin',
  });

  if (!response.ok) {
    throw new Error(`Bootstrap request failed (${response.status}).`);
  }

  return response.json();
}

/**
 * Render a minimal error message into the mount element.
 *
 * @since 6.0.0
 * @param {HTMLElement} mount - Mount element.
 * @param {string} message - Message to display.
 * @return {void}
 */
function renderBootstrapError(mount, message) {
  mount.innerHTML = '';

  const notice = document.createElement('div');
  notice.className = 'flexify-checkout-bootstrap-error';
  notice.setAttribute('role', 'alert');
  notice.textContent = message;

  mount.appendChild(notice);
}

/**
 * Mount a Vue page component, resolving its bootstrap payload via a GET request.
 *
 * The skeleton already rendered inside the mount element stays visible until
 * the request resolves; Vue replaces it once the component mounts.
 *
 * @since 6.0.0
 * @param {string} mountId - DOM id of the mount point.
 * @param {Object} component - Vue component to mount.
 * @return {Promise<import('vue').App | null>} Mounted Vue application instance or null when unavailable.
 */
export async function mountPage(mountId, component) {
  const mount = document.getElementById(mountId);

  if (!mount) {
    return null;
  }

  const config = readBootstrapConfig();
  let bootstrap = {};

  if (config) {
    try {
      bootstrap = await fetchBootstrap(config);
    } catch (error) {
      renderBootstrapError(mount, 'Não foi possível carregar esta página. Recarregue e tente novamente.');

      return null;
    }
  }

  const app = createApp(component, {
    bootstrap,
  });

  app.use(createPinia());

  return app.mount(mount);
}

/**
 * Mount a routed Vue application (vue-router), resolving its bootstrap payload
 * via a GET request first. Mirrors mountPage() but installs a router so the
 * admin SPA can switch between routes (Settings, License) client-side.
 *
 * @since 6.0.0
 * @param {string} mountId - DOM id of the mount point.
 * @param {Object} rootComponent - Root component rendering <router-view>.
 * @param {import('vue-router').Router} router - Configured router instance.
 * @return {Promise<import('vue').App | null>} Mounted app instance or null when unavailable.
 */
export async function mountRoutedPage(mountId, rootComponent, router) {
  const mount = document.getElementById(mountId);

  if (!mount) {
    return null;
  }

  const config = readBootstrapConfig();
  let bootstrap = {};

  if (config) {
    try {
      bootstrap = await fetchBootstrap(config);
    } catch (error) {
      renderBootstrapError(mount, 'Não foi possível carregar esta página. Recarregue e tente novamente.');

      return null;
    }
  }

  const app = createApp(rootComponent, {
    bootstrap,
  });

  app.use(createPinia());
  app.use(router);

  await router.isReady();

  return app.mount(mount);
}
