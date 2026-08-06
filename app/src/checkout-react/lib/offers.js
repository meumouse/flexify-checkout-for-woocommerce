import { useSyncExternalStore } from 'react';
import config from '../config.js';

/**
 * Checkout offers (order bump / upsell / cross-sell / downsell).
 *
 * Offers are seeded server-side into config.rules.offers (already filtered to
 * the ones whose cart trigger qualifies, and Pro-gated). The builder preview may
 * push a live override so an operator sees offer placement without a reload.
 *
 * A tiny decline store tracks which offers the shopper dismissed within the
 * single checkout page, so a downsell linked to an upsell/bump reveals the moment
 * its parent is declined — no server round-trip, coherent with pre-purchase-only.
 */

let offersOverride = null;

/**
 * Set (or clear) the live offers override pushed by the builder preview.
 *
 * @param {Array<object>|null} arr Offers array, or null to clear.
 * @returns {void}
 */
export function setOffersOverride(arr) {
  offersOverride = Array.isArray(arr) ? arr : null;
}

/**
 * All active offers available to the checkout.
 *
 * @returns {Array<object>}
 */
export function getOffers() {
  if (offersOverride) {
    return offersOverride;
  }

  const offers = config.rules && config.rules.offers;

  return Array.isArray(offers) ? offers : [];
}

/**
 * Resolve a single offer by id.
 *
 * @param {string} id Offer id.
 * @returns {object|null}
 */
export function getOffer(id) {
  if (!id) {
    return null;
  }

  return getOffers().find((offer) => String(offer.id) === String(id)) || null;
}

// --- Decline store (client-side, single page) ---

const declined = new Set();
const listeners = new Set();
let version = 0;

function emit() {
  version += 1;
  listeners.forEach((listener) => listener());
}

/**
 * Mark an offer declined (reveals any downsell linked to it).
 *
 * @param {string} id Offer id.
 * @returns {void}
 */
export function declineOffer(id) {
  if (id && !declined.has(String(id))) {
    declined.add(String(id));
    emit();
  }
}

/**
 * Clear an offer's declined state (e.g. the shopper re-checked the bump).
 *
 * @param {string} id Offer id.
 * @returns {void}
 */
export function acceptOffer(id) {
  if (id && declined.delete(String(id))) {
    emit();
  }
}

/**
 * Whether an offer is currently declined.
 *
 * @param {string} id Offer id.
 * @returns {boolean}
 */
export function isDeclined(id) {
  return declined.has(String(id));
}

function subscribe(listener) {
  listeners.add(listener);

  return () => {
    listeners.delete(listener);
  };
}

function getSnapshot() {
  return version;
}

/**
 * Subscribe a component to decline-store changes so it re-renders on decline.
 *
 * @returns {number} Opaque version that changes on every decline mutation.
 */
export function useDeclineVersion() {
  return useSyncExternalStore(subscribe, getSnapshot, getSnapshot);
}
