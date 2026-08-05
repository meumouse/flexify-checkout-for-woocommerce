/**
 * Shared schema + normalization helpers for the checkout builder conditions
 * editor. Mirrors the option sets and payload shape used by ConditionBuilder.vue
 * / the Conditions_Store REST endpoints, so the inline rules panel and the
 * builder orchestrator agree on how a rule is loaded and saved.
 *
 * @since 6.0.0
 */

export const ACTION_TYPES = [
  { value: 'show', label: 'Mostrar' },
  { value: 'hide', label: 'Ocultar' },
  { value: 'discount', label: 'Aplicar desconto' },
];

export const COMPONENTS = [
  { value: 'field', label: 'Campo' },
  { value: 'shipping', label: 'Forma de entrega' },
  { value: 'payment', label: 'Forma de pagamento' },
];

export const SUBJECTS = [
  { value: 'field', label: 'Campo do checkout' },
  { value: 'cart_qty', label: 'Qtd. no carrinho' },
  { value: 'cart_total', label: 'Valor do carrinho' },
  { value: 'product', label: 'Produto no carrinho' },
  { value: 'category', label: 'Categoria' },
  { value: 'attribute', label: 'Atributo' },
  { value: 'user', label: 'Usuário' },
  { value: 'user_role', label: 'Função de usuário' },
  { value: 'country', label: 'País' },
  { value: 'shipping_region', label: 'Região de entrega' },
];

export const FIELD_OPERATORS = [
  { value: 'is', label: 'é' },
  { value: 'is_not', label: 'não é' },
  { value: 'contains', label: 'contém' },
  { value: 'not_contain', label: 'não contém' },
  { value: 'start_with', label: 'começa com' },
  { value: 'finish_with', label: 'termina com' },
  { value: 'empty', label: 'está vazio' },
  { value: 'not_empty', label: 'não está vazio' },
  { value: 'checked', label: 'marcado' },
  { value: 'not_checked', label: 'não marcado' },
];

export const NUMERIC_OPERATORS = [
  { value: 'is', label: 'é igual a' },
  { value: 'is_not', label: 'é diferente de' },
  { value: 'bigger_then', label: 'maior que' },
  { value: 'less_than', label: 'menor que' },
];

export const LIST_OPERATORS = [
  { value: 'is_one_of', label: 'é um de' },
  { value: 'is_not_one_of', label: 'não é um de' },
];

export const RULE_MATCH_OPTIONS = [
  { value: 'all', label: 'todos os grupos' },
  { value: 'any', label: 'qualquer grupo' },
];

export const GROUP_MATCH_OPTIONS = [
  { value: 'all', label: 'todas as condições' },
  { value: 'any', label: 'qualquer condição' },
];

export const DISCOUNT_MODES = [
  { value: 'percent', label: 'percentual' },
  { value: 'fixed', label: 'valor fixo' },
];

export const NO_VALUE_OPERATORS = ['empty', 'not_empty', 'checked', 'not_checked'];
export const ID_SUBJECTS = ['user', 'product', 'category', 'attribute'];
export const STATIC_SUBJECTS = ['country', 'user_role', 'shipping_region'];
export const SEARCH_TYPE = { user: 'users', product: 'products', category: 'categories', attribute: 'attributes' };

let counter = 0;

function localId() {
  counter += 1;

  return `rl_${Date.now().toString(36)}_${counter}`;
}

export function operatorsFor(subject) {
  if (subject === 'field') {
    return FIELD_OPERATORS;
  }

  if (subject === 'cart_qty' || subject === 'cart_total') {
    return NUMERIC_OPERATORS;
  }

  return LIST_OPERATORS;
}

export function valueKind(condition) {
  if (ID_SUBJECTS.includes(condition.subject)) {
    return 'search';
  }

  if (STATIC_SUBJECTS.includes(condition.subject)) {
    return 'tags';
  }

  if (condition.subject === 'cart_qty' || condition.subject === 'cart_total') {
    return 'number';
  }

  if (NO_VALUE_OPERATORS.includes(condition.operator)) {
    return 'none';
  }

  return 'text';
}

export function newCondition() {
  return { subject: 'field', field: '', operator: 'is', value: '', selected: [], values: [] };
}

export function newGroup() {
  return { match: 'all', conditions: [newCondition()] };
}

export function newRule() {
  return {
    id: null,
    _localId: localId(),
    name: 'Nova regra',
    enabled: true,
    action: {
      type: 'show',
      component: 'field',
      field: '',
      shipping_method: '',
      payment_method: '',
      discount: { mode: 'percent', value: 10, label: '' },
    },
    match: 'all',
    groups: [newGroup()],
  };
}

/**
 * Reset a condition's operator/value fields when its subject changes.
 *
 * @param {object} condition Condition to mutate in place.
 */
export function resetCondition(condition) {
  condition.operator = operatorsFor(condition.subject)[0].value;
  condition.value = '';
  condition.field = '';
  condition.selected = [];
  condition.values = [];
}

/**
 * Normalize a server condition into the editable form shape.
 *
 * @param {object} serverCondition Condition from the REST payload.
 * @returns {object}
 */
function loadCondition(serverCondition) {
  const subject = serverCondition.subject || 'field';

  return {
    subject,
    field: serverCondition.field || '',
    operator: serverCondition.operator || 'is',
    value: serverCondition.value || '',
    selected: ID_SUBJECTS.includes(subject) ? (serverCondition.items_labeled || []).map((item) => ({ ...item })) : [],
    values: STATIC_SUBJECTS.includes(subject) ? (serverCondition.items || []).map((item) => String(item)) : [],
  };
}

/**
 * Normalize a stored rule into the editable form shape used by the panel.
 *
 * @param {object} rule Rule from store.conditions.
 * @returns {object}
 */
export function loadRule(rule) {
  const action = rule.action || {};
  const groups = Array.isArray(rule.groups) ? rule.groups : [];

  return {
    id: rule.id || null,
    _localId: localId(),
    name: rule.name || '',
    enabled: rule.enabled !== false,
    action: {
      type: action.type || 'show',
      component: action.component || 'field',
      field: action.field || '',
      shipping_method: action.shipping_method || '',
      payment_method: action.payment_method || '',
      discount: {
        mode: action.discount?.mode || 'percent',
        value: action.discount?.value ?? 0,
        label: action.discount?.label || '',
      },
    },
    match: rule.match === 'any' ? 'any' : 'all',
    groups: groups.length
      ? groups.map((group) => ({
          match: group.match === 'any' ? 'any' : 'all',
          conditions: (group.conditions || []).map(loadCondition),
        }))
      : [newGroup()],
  };
}

function buildConditionPayload(condition) {
  const payload = { subject: condition.subject, operator: condition.operator, value: '', field: '', items: [] };

  if (condition.subject === 'field') {
    payload.field = condition.field;
    payload.value = NO_VALUE_OPERATORS.includes(condition.operator) ? '' : condition.value;
  } else if (condition.subject === 'cart_qty' || condition.subject === 'cart_total') {
    payload.value = condition.value;
  } else if (ID_SUBJECTS.includes(condition.subject)) {
    payload.items = condition.selected.map((item) => item.id);
  } else {
    payload.items = condition.values;
  }

  return payload;
}

/**
 * Denormalize an editable rule back into the REST payload shape.
 *
 * @param {object} rule Editable rule.
 * @returns {object}
 */
export function buildRulePayload(rule) {
  const action = { type: rule.action.type };

  if (action.type === 'discount') {
    action.discount = {
      mode: rule.action.discount.mode,
      value: Number(rule.action.discount.value) || 0,
      label: rule.action.discount.label,
    };
  } else {
    action.component = rule.action.component;

    if (action.component === 'field') {
      action.field = rule.action.field;
    } else if (action.component === 'shipping') {
      action.shipping_method = rule.action.shipping_method;
    } else if (action.component === 'payment') {
      action.payment_method = rule.action.payment_method;
    }
  }

  return {
    name: rule.name,
    enabled: rule.enabled,
    action,
    match: rule.match,
    groups: rule.groups.map((group) => ({
      match: group.match,
      conditions: group.conditions.map(buildConditionPayload),
    })),
  };
}

/**
 * Whether a rule is fully addressable (valid target + values). Drives the
 * inline status line and blocks saving incomplete rules.
 *
 * @param {object} rule Editable rule.
 * @returns {boolean}
 */
export function ruleValid(rule) {
  const action = rule.action;

  if (action.type === 'discount') {
    if (!(Number(action.discount.value) > 0)) {
      return false;
    }
  } else if (action.component === 'field' && !action.field) {
    return false;
  } else if (action.component === 'shipping' && !action.shipping_method) {
    return false;
  } else if (action.component === 'payment' && !action.payment_method) {
    return false;
  }

  return rule.groups.every((group) =>
    group.conditions.every((condition) => {
      if (condition.subject === 'field' && !condition.field) {
        return false;
      }

      if (ID_SUBJECTS.includes(condition.subject) && !condition.selected.length) {
        return false;
      }

      if (STATIC_SUBJECTS.includes(condition.subject) && !condition.values.length) {
        return false;
      }

      const kind = valueKind(condition);

      if ((kind === 'text' || kind === 'number') && condition.value === '') {
        return false;
      }

      return true;
    }),
  );
}
