import config from '../config.js';
import { fieldValue } from './fields.js';

/**
 * Client-side evaluation of the checkout field conditions exported by the server
 * (Conditions::export_field_rules_for_js). Drives show/hide of dependent fields
 * — most importantly the person-type flow (CPF/RG for individuals vs CNPJ/IE for
 * companies) — mirroring the classic checkout, whose PHP counterpart lives in
 * Checkout\Conditions.
 *
 * Field conditions are evaluated live against the current form values; every
 * other condition kind (cart, country, user, …) was pre-evaluated server-side
 * and rides along as `server_pass`.
 */

/**
 * Apply a comparison operator, matching PHP Conditions::check_condition.
 *
 * @param {string} operator Operator id.
 * @param {*}      value    Current field value.
 * @param {*}      compare  Value to compare against.
 * @returns {boolean}
 */
function checkOperator(operator, value, compare) {
  const val = value == null ? '' : String(value);
  const cmp = compare == null ? '' : String(compare);
  const checkedValues = ['1', 'on', 'yes', 'true'];

  switch (operator) {
    case 'is':
      return val === cmp;
    case 'is_not':
      return val !== cmp;
    case 'empty':
      return val === '';
    case 'not_empty':
      return val !== '';
    case 'contains':
      return cmp !== '' && val.indexOf(cmp) !== -1;
    case 'not_contain':
      return val.indexOf(cmp) === -1;
    case 'start_with':
      return val.startsWith(cmp);
    case 'finish_with':
      return val.endsWith(cmp);
    case 'bigger_then':
      return parseFloat(val) > parseFloat(cmp);
    case 'less_than':
      return parseFloat(val) < parseFloat(cmp);
    case 'checked':
      return checkedValues.indexOf(val) !== -1;
    case 'not_checked':
      return checkedValues.indexOf(val) === -1;
    default:
      return false;
  }
}

/**
 * Evaluate a single condition against the current form values.
 *
 * @param {object} condition   Condition entry.
 * @param {object} billing     Standard address values.
 * @param {object} extraFields Flexify-managed extra field values.
 * @returns {boolean}
 */
function evaluateCondition(condition, billing, extraFields) {
  if (condition.subject === 'field') {
    const current = fieldValue(condition.field, billing, extraFields);

    if (condition.operator === 'checked' || condition.operator === 'not_checked') {
      return checkOperator(condition.operator, current);
    }

    return checkOperator(condition.operator, current, condition.value);
  }

  // Non-field conditions were resolved server-side.
  return !!condition.server_pass;
}

/**
 * Evaluate a group (conditions joined by all|any).
 *
 * @param {object} group       Group entry.
 * @param {object} billing     Standard address values.
 * @param {object} extraFields Flexify-managed extra field values.
 * @returns {boolean}
 */
function evaluateGroup(group, billing, extraFields) {
  const conditions = (group && group.conditions) || [];

  if (!conditions.length) {
    return true;
  }

  const any = group.match === 'any';

  for (let i = 0; i < conditions.length; i += 1) {
    const passed = evaluateCondition(conditions[i], billing, extraFields);

    if (any && passed) {
      return true;
    }

    if (!any && !passed) {
      return false;
    }
  }

  return !any;
}

/**
 * Evaluate a full rule (groups joined by all|any).
 *
 * @param {object} rule        Rule entry.
 * @param {object} billing     Standard address values.
 * @param {object} extraFields Flexify-managed extra field values.
 * @returns {boolean}
 */
function evaluateRule(rule, billing, extraFields) {
  const groups = (rule && rule.groups) || [];

  if (!groups.length) {
    return true;
  }

  const any = rule.match === 'any';

  for (let i = 0; i < groups.length; i += 1) {
    const passed = evaluateGroup(groups[i], billing, extraFields);

    if (any && passed) {
      return true;
    }

    if (!any && !passed) {
      return false;
    }
  }

  return !any;
}

// Live rules pushed by the admin builder (editor mode). When set, they take the
// place of the server-exported rules so field show/hide reflects unsaved edits.
// Only field-subject conditions evaluate live; other subjects (cart, country, …)
// are treated as passing here and become accurate again after the Save reload.
let overrideRules = null;

/**
 * Override the field-visibility rules with a live builder payload, or clear it.
 *
 * @param {Array|null} rules Editor rule payloads, or null to restore server rules.
 */
export function setConditionsOverride(rules) {
  if (!Array.isArray(rules)) {
    overrideRules = null;

    return;
  }

  overrideRules = rules
    .filter((rule) => rule && rule.enabled !== false && rule.action && rule.action.type !== 'discount')
    .map((rule) => ({
      action: rule.action || {},
      match: rule.match === 'any' ? 'any' : 'all',
      groups: (rule.groups || []).map((group) => ({
        match: group.match === 'any' ? 'any' : 'all',
        conditions: (group.conditions || []).map((condition) =>
          condition.subject === 'field'
            ? { subject: 'field', field: condition.field, operator: condition.operator, value: condition.value }
            : { subject: condition.subject, operator: condition.operator, server_pass: true },
        ),
      })),
    }));
}

/**
 * Whether a field is currently visible given the live form values.
 *
 * A field with no targeting rule is always visible. When rules target it, a
 * `show` rule hides it until matched, and a `hide` rule hides it once matched.
 *
 * @param {string} fieldId     Field id.
 * @param {object} billing     Standard address values.
 * @param {object} extraFields Flexify-managed extra field values.
 * @returns {boolean}
 */
export function isFieldVisible(fieldId, billing, extraFields) {
  const rules = overrideRules || (config.rules && config.rules.conditions) || [];

  if (!rules.length) {
    return true;
  }

  for (let i = 0; i < rules.length; i += 1) {
    const rule = rules[i];

    if (!rule || !rule.action || rule.action.field !== fieldId) {
      continue;
    }

    const matched = evaluateRule(rule, billing, extraFields);
    const type = rule.action.type;

    if ((type === 'show' && !matched) || (type === 'hide' && matched)) {
      return false;
    }
  }

  return true;
}
