import config from '../config.js';

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
