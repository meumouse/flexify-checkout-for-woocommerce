import config from '../config.js';

/**
 * Resolve which payment gateways to display, intersecting the localized catalog
 * with the live availability the WooCommerce Store API reports for the cart.
 *
 * `cart.payment_methods` is an array of gateway IDs the Store API recomputes per
 * request, so it reflects conditional availability (address / totals / coupon
 * rules applied through the `woocommerce_available_payment_gateways` filter).
 * The localized `config.config.gateways` catalog is only a page-load snapshot,
 * but it carries the canonical presentation metadata (kind, title, icon). We
 * therefore trust the Store API for *which* gateways are available and the
 * catalog for *how* to render each one, keeping the Store API ordering.
 *
 * Falls back to the full catalog only when the Store API omits the field
 * entirely (older WooCommerce), so the checkout never loses its payment step. An
 * explicit empty array is honored: it means the cart genuinely has no available
 * method and the caller should show the empty state.
 *
 * @param {object|null} cart Store API cart response.
 * @returns {Array<object>} Gateways to render, in Store API order.
 */
export function resolveAvailableGateways(cart) {
  const catalog = (config.config && config.config.gateways) || [];
  const available = cart && Array.isArray(cart.payment_methods) ? cart.payment_methods : null;

  if (!available) {
    return catalog;
  }

  const byId = new Map(catalog.map((gateway) => [gateway.id, gateway]));

  // Honor the Store API ordering; synthesize a minimal entry for any available
  // ID the catalog snapshot does not know about (e.g. gateway enabled after load).
  return available.map((id) => byId.get(id) || { id, kind: 'offline', title: id, description: '' });
}

export default resolveAvailableGateways;
