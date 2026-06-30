/**
 * Coupon field placement, mirroring the classic checkout's "Coupon field
 * position" setting (render_coupon_field_hook). The localized config exposes
 * `coupon_enabled` and `coupon_position`; this module turns them into the two
 * boolean placements the checkout cares about: sidebar and before payment.
 */

import config from '../config.js';

const POSITION = {
  sidebar: 'sidebar',
  beforePayment: 'before_payment_title',
  both: 'before_payment_and_sidebar',
};

function couponConfig() {
  return (config && config.config) || {};
}

/**
 * Whether the coupon field is enabled at all. Defaults to true so the field
 * keeps showing when the flag is absent (older localized payloads).
 *
 * @returns {boolean}
 */
export function couponEnabled() {
  return couponConfig().coupon_enabled !== false;
}

/**
 * Whether the coupon field should render in the order summary sidebar.
 *
 * @returns {boolean}
 */
export function couponInSidebar() {
  if (!couponEnabled()) {
    return false;
  }

  // Sidebar is the default placement when no position is configured.
  const position = couponConfig().coupon_position || POSITION.sidebar;

  return position === POSITION.sidebar || position === POSITION.both;
}

/**
 * Whether the coupon field should render before the payment methods.
 *
 * @returns {boolean}
 */
export function couponBeforePayment() {
  if (!couponEnabled()) {
    return false;
  }

  const position = couponConfig().coupon_position;

  return position === POSITION.beforePayment || position === POSITION.both;
}
