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
 * Live field-record overrides pushed by the admin builder (editor mode only).
 * Stays null on the real storefront, so live rendering never sees overrides.
 *
 * @type {Object<string,object>|null}
 */
let fieldOverrides = null;

/**
 * Set (or clear) the live field overrides. Only ever called by EditorApp.
 *
 * @param {Array<object>|null} arr Normalized field records, or null to clear.
 * @returns {void}
 */
export function setFieldOverrides(arr) {
  if (Array.isArray(arr)) {
    const map = {};

    arr.forEach((f) => {
      if (f && f.id) {
        map[f.id] = f;
      }
    });

    fieldOverrides = map;
  } else {
    fieldOverrides = null;
  }
}

/**
 * Resolve a field definition by its id.
 *
 * On the real checkout, reads `config.rules.fields` and returns null when the
 * field is missing or disabled (so condition-driven visibility keeps working).
 * In the builder editor, live overrides win and disabled fields are still
 * returned (kept visible/selectable, greyed by FieldRenderer).
 *
 * @param {string} fieldId Field id.
 * @param {{editor?:boolean}} [opts] Resolution options.
 * @returns {object|null}
 */
export function fieldById(fieldId, { editor = false } = {}) {
  if (fieldOverrides && fieldOverrides[fieldId]) {
    const field = fieldOverrides[fieldId];

    return editor || field.enabled ? field : null;
  }

  const fields = (config.rules && config.rules.fields) || [];
  const field = fields.find((f) => f.id === fieldId);

  if (!field) {
    return null;
  }

  return editor || field.enabled ? field : null;
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
