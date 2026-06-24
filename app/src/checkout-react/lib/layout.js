import config from '../config.js';

/**
 * The visual builder layout, when the operator opted in and saved one.
 *
 * @returns {{version:number, steps:Array}|null}
 */
export function getLayout() {
  const layout = config.rules && config.rules.layout;

  if (layout && Array.isArray(layout.steps) && layout.steps.length) {
    return layout;
  }

  return null;
}

/**
 * Whether the builder layout drives rendering (vs. the default hardcoded steps).
 *
 * @returns {boolean}
 */
export function hasLayout() {
  return getLayout() !== null;
}

/**
 * Enabled steps from the layout, in order.
 *
 * @returns {Array<object>}
 */
export function layoutSteps() {
  const layout = getLayout();

  if (!layout) {
    return [];
  }

  return layout.steps
    .filter((step) => step.enabled !== false)
    .slice()
    .sort((a, b) => Number(a.order || 0) - Number(b.order || 0));
}

/**
 * Resolve a field definition (from rules.fields) by its id.
 *
 * Returns null when the field is missing or disabled, so condition-driven
 * visibility (which flips `enabled`) keeps working in the builder path.
 *
 * @param {string} fieldId Field id.
 * @returns {object|null}
 */
export function fieldById(fieldId) {
  const fields = (config.rules && config.rules.fields) || [];
  const field = fields.find((f) => f.id === fieldId);

  return field && field.enabled ? field : null;
}

/**
 * Ordered items for a step.
 *
 * @param {object} step Layout step.
 * @returns {Array<object>}
 */
export function itemsForStep(step) {
  return (step.items || [])
    .slice()
    .sort((a, b) => Number(a.order || 0) - Number(b.order || 0));
}
