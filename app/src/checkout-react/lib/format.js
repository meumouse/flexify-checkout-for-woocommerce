import config from '../config.js';

/**
 * Format a Store API minor-unit amount into a localized currency string.
 *
 * Store API returns prices as integer strings in the currency's minor unit
 * with a `currency_minor_unit` describing the number of decimals.
 *
 * @param {string|number} amount Minor-unit amount.
 * @param {object} totals Store API totals object (for currency metadata).
 * @returns {string}
 */
export function formatPrice(amount, totals = {}) {
  const minorUnit = Number(totals.currency_minor_unit ?? 2);
  const value = Number(amount ?? 0) / Math.pow(10, minorUnit);
  const code = totals.currency_code || config.currency || 'BRL';

  try {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: code }).format(value);
  } catch (e) {
    const symbol = totals.currency_symbol || config.currency_symbol || 'R$';

    return `${symbol} ${value.toFixed(minorUnit)}`;
  }
}
