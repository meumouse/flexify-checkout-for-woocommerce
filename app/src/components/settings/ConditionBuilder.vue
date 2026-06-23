<script setup>
import { computed, reactive, watch } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';
import SearchMultiSelect from '../fields/SearchMultiSelect.vue';
import TagSelect from '../fields/TagSelect.vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  rule: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const store = useSettingsStore();

const ACTION_TYPES = [
  { value: 'show', label: 'Mostrar' },
  { value: 'hide', label: 'Ocultar' },
  { value: 'discount', label: 'Aplicar desconto' },
];

const COMPONENTS = [
  { value: 'field', label: 'Campo do checkout' },
  { value: 'shipping', label: 'Forma de entrega' },
  { value: 'payment', label: 'Forma de pagamento' },
];

const SUBJECTS = [
  { value: 'field', label: 'Campo do checkout' },
  { value: 'cart_qty', label: 'Quantidade do carrinho' },
  { value: 'cart_total', label: 'Valor total do carrinho' },
  { value: 'product', label: 'Produto no carrinho' },
  { value: 'category', label: 'Categoria no carrinho' },
  { value: 'attribute', label: 'Atributo no carrinho' },
  { value: 'user', label: 'Usuário' },
  { value: 'user_role', label: 'Função de usuário' },
  { value: 'country', label: 'País' },
  { value: 'shipping_region', label: 'Região de entrega' },
];

const FIELD_OPERATORS = [
  { value: 'is', label: 'É' },
  { value: 'is_not', label: 'Não é' },
  { value: 'contains', label: 'Contém' },
  { value: 'not_contain', label: 'Não contém' },
  { value: 'start_with', label: 'Começa com' },
  { value: 'finish_with', label: 'Termina com' },
  { value: 'empty', label: 'Está vazio' },
  { value: 'not_empty', label: 'Não está vazio' },
  { value: 'checked', label: 'Marcado' },
  { value: 'not_checked', label: 'Desmarcado' },
];

const NUMERIC_OPERATORS = [
  { value: 'is', label: 'É igual a' },
  { value: 'is_not', label: 'É diferente de' },
  { value: 'bigger_then', label: 'Maior que' },
  { value: 'less_than', label: 'Menor que' },
];

const LIST_OPERATORS = [
  { value: 'is_one_of', label: 'É um de' },
  { value: 'is_not_one_of', label: 'Não é um de' },
];

const NO_VALUE_OPERATORS = ['empty', 'not_empty', 'checked', 'not_checked'];
const ID_SUBJECTS = ['user', 'product', 'category', 'attribute'];
const STATIC_SUBJECTS = ['country', 'user_role', 'shipping_region'];
const SEARCH_TYPE = { user: 'users', product: 'products', category: 'categories', attribute: 'attributes' };

const inputClass = 'flexify-field-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100';

const billingFieldOptions = computed(() =>
  Object.entries(store.fields || {})
    .filter(([id]) => id.startsWith('billing_'))
    .map(([id, field]) => ({ value: id, label: field?.label || id })),
);

const staticOptions = {
  country: () => store.runtime?.countries || [],
  user_role: () => store.runtime?.user_roles || [],
  shipping_region: () => store.runtime?.shipping_zones || [],
};

// --- Form state ---

function newCondition() {
  return { subject: 'field', field: '', operator: 'is', value: '', selected: [], values: [] };
}

function newGroup() {
  return { match: 'all', conditions: [newCondition()] };
}

const form = reactive({
  name: '',
  enabled: true,
  action: {
    type: 'show',
    component: 'field',
    field: '',
    shipping_method: '',
    payment_method: '',
    discount: { mode: 'percent', value: 0, label: '' },
  },
  match: 'all',
  groups: [newGroup()],
});

const editingId = computed(() => (props.rule?.id ? props.rule.id : null));
const saving = computed(() => store.savingCondition);

function reset() {
  form.name = '';
  form.enabled = true;
  Object.assign(form.action, {
    type: 'show',
    component: 'field',
    field: '',
    shipping_method: '',
    payment_method: '',
    discount: { mode: 'percent', value: 0, label: '' },
  });
  form.match = 'all';
  form.groups = [newGroup()];
}

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

function loadRule(rule) {
  const action = rule.action || {};

  form.name = rule.name || '';
  form.enabled = rule.enabled !== false;
  Object.assign(form.action, {
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
  });
  form.match = rule.match === 'any' ? 'any' : 'all';

  const groups = Array.isArray(rule.groups) ? rule.groups : [];

  form.groups = groups.length
    ? groups.map((group) => ({
        match: group.match === 'any' ? 'any' : 'all',
        conditions: (group.conditions || []).map(loadCondition),
      }))
    : [newGroup()];
}

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      return;
    }

    if (props.rule) {
      loadRule(props.rule);
    } else {
      reset();
    }
  },
  { immediate: true },
);

// --- Condition helpers ---

function operatorsFor(subject) {
  if (subject === 'field') {
    return FIELD_OPERATORS;
  }

  if (subject === 'cart_qty' || subject === 'cart_total') {
    return NUMERIC_OPERATORS;
  }

  return LIST_OPERATORS;
}

function onSubjectChange(condition) {
  condition.operator = operatorsFor(condition.subject)[0].value;
  condition.value = '';
  condition.field = '';
  condition.selected = [];
  condition.values = [];
}

function valueKind(condition) {
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

function addCondition(group) {
  group.conditions.push(newCondition());
}

function removeCondition(group, index) {
  group.conditions.splice(index, 1);

  if (!group.conditions.length) {
    group.conditions.push(newCondition());
  }
}

function addGroup() {
  form.groups.push(newGroup());
}

function removeGroup(index) {
  form.groups.splice(index, 1);

  if (!form.groups.length) {
    form.groups.push(newGroup());
  }
}

// --- Validation & save ---

const isValid = computed(() => {
  const action = form.action;

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

  // Every present condition must be addressable.
  return form.groups.every((group) =>
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

      if (valueKind(condition) === 'text' && condition.value === '') {
        return false;
      }

      if (valueKind(condition) === 'number' && condition.value === '') {
        return false;
      }

      return true;
    }),
  );
});

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

function buildPayload() {
  const action = { type: form.action.type };

  if (action.type === 'discount') {
    action.discount = {
      mode: form.action.discount.mode,
      value: Number(form.action.discount.value) || 0,
      label: form.action.discount.label,
    };
  } else {
    action.component = form.action.component;

    if (action.component === 'field') {
      action.field = form.action.field;
    } else if (action.component === 'shipping') {
      action.shipping_method = form.action.shipping_method;
    } else if (action.component === 'payment') {
      action.payment_method = form.action.payment_method;
    }
  }

  return {
    name: form.name,
    enabled: form.enabled,
    action,
    match: form.match,
    groups: form.groups.map((group) => ({
      match: group.match,
      conditions: group.conditions.map(buildConditionPayload),
    })),
  };
}

async function submit() {
  if (!isValid.value || saving.value) {
    return;
  }

  const response = await store.saveCondition(buildPayload(), editingId.value);

  if (response?.status === 'success') {
    emit('saved');
    emit('close');
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[99999] flex flex-col bg-slate-50">
      <!-- Header -->
      <header class="flex shrink-0 items-center justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
        <div class="flex items-center gap-3">
          <button
            type="button"
            class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
            @click="emit('close')"
          >
            <BoxIcon name="chevron-left" class="h-4 w-4" />
            Voltar
          </button>

          <div>
            <h2 class="m-0 text-[15px] font-semibold text-ink">{{ editingId ? 'Editar regra' : 'Nova regra' }}</h2>
            <p class="m-0 text-[13px] text-slate-500">Defina a ação e as condições que a disparam.</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <BaseButton variant="secondary" size="sm" @click="emit('close')">Cancelar</BaseButton>
          <BaseButton size="sm" :loading="saving" :disabled="!isValid" @click="submit">
            {{ editingId ? 'Salvar alterações' : 'Criar regra' }}
          </BaseButton>
        </div>
      </header>

      <!-- Body -->
      <div class="flex-1 overflow-y-auto">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-6 py-8">
          <!-- Identification -->
          <section class="rounded-[12px] border border-slate-200 bg-white p-5">
            <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Nome da regra</label>
                <input v-model="form.name" type="text" :class="inputClass" placeholder="Opcional — ex.: Ocultar PIX fora de SP" />
              </div>

              <label class="flex cursor-pointer items-center gap-2 pb-2 text-sm font-medium text-ink">
                <input v-model="form.enabled" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary-100" />
                Ativa
              </label>
            </div>
          </section>

          <!-- Action -->
          <section class="rounded-[12px] border border-slate-200 bg-white p-5">
            <div class="mb-4 flex items-center gap-2">
              <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary-100 text-primary">
                <BoxIcon name="slider-alt" class="h-4 w-4" />
              </span>
              <h3 class="m-0 text-sm font-semibold text-ink">Faça isto</h3>
            </div>

            <div class="mb-4 inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1">
              <button
                v-for="option in ACTION_TYPES"
                :key="option.value"
                type="button"
                class="cursor-pointer rounded-md px-4 py-1.5 text-sm font-medium transition"
                :class="form.action.type === option.value ? 'bg-white text-primary shadow-sm' : 'text-slate-500 hover:text-ink'"
                @click="form.action.type = option.value"
              >
                {{ option.label }}
              </button>
            </div>

            <!-- show / hide target -->
            <div v-if="form.action.type !== 'discount'" class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Componente</label>
                <select v-model="form.action.component" :class="inputClass">
                  <option v-for="option in COMPONENTS" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
              </div>

              <div v-if="form.action.component === 'field'">
                <label class="mb-1 block text-sm font-medium text-ink">Campo</label>
                <select v-model="form.action.field" :class="inputClass">
                  <option value="">Selecione um campo</option>
                  <option v-for="option in billingFieldOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
              </div>

              <div v-else-if="form.action.component === 'shipping'">
                <label class="mb-1 block text-sm font-medium text-ink">Forma de entrega</label>
                <select v-model="form.action.shipping_method" :class="inputClass">
                  <option value="">Selecione uma forma de entrega</option>
                  <option v-for="option in store.runtime?.shipping_methods || []" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
              </div>

              <div v-else>
                <label class="mb-1 block text-sm font-medium text-ink">Forma de pagamento</label>
                <select v-model="form.action.payment_method" :class="inputClass">
                  <option value="">Selecione uma forma de pagamento</option>
                  <option v-for="option in store.runtime?.payment_gateways || []" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
              </div>
            </div>

            <!-- discount -->
            <div v-else class="grid gap-4 sm:grid-cols-3">
              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Tipo</label>
                <select v-model="form.action.discount.mode" :class="inputClass">
                  <option value="percent">Percentual (%)</option>
                  <option value="fixed">Valor fixo ({{ store.runtime?.currency_symbol || 'R$' }})</option>
                </select>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Valor</label>
                <input v-model="form.action.discount.value" type="number" min="0" step="0.01" :class="inputClass" placeholder="0" />
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Rótulo</label>
                <input v-model="form.action.discount.label" type="text" :class="inputClass" placeholder="Desconto" />
              </div>
            </div>
          </section>

          <!-- Conditions -->
          <section class="rounded-[12px] border border-slate-200 bg-white p-5">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
              <div class="flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary-100 text-primary">
                  <BoxIcon name="filter-alt" class="h-4 w-4" />
                </span>
                <h3 class="m-0 text-sm font-semibold text-ink">Quando isto for verdadeiro</h3>
              </div>

              <label v-if="form.groups.length > 1" class="flex items-center gap-2 text-sm text-slate-500">
                Atender
                <select v-model="form.match" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-sm text-ink">
                  <option value="all">todos os grupos</option>
                  <option value="any">qualquer grupo</option>
                </select>
              </label>
            </div>

            <div class="flex flex-col gap-3">
              <template v-for="(group, groupIndex) in form.groups" :key="groupIndex">
                <div v-if="groupIndex > 0" class="flex items-center justify-center">
                  <span class="rounded-full bg-slate-100 px-3 py-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    {{ form.match === 'any' ? 'OU' : 'E' }}
                  </span>
                </div>

                <div class="rounded-[10px] border border-slate-200 bg-slate-50/60 p-4">
                  <div class="mb-3 flex items-center justify-between gap-2">
                    <label class="flex items-center gap-2 text-sm text-slate-500">
                      Atender
                      <select v-model="group.match" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-sm text-ink">
                        <option value="all">todas as condições</option>
                        <option value="any">qualquer condição</option>
                      </select>
                    </label>

                    <button
                      v-if="form.groups.length > 1"
                      type="button"
                      class="cursor-pointer rounded-lg border border-danger/30 bg-white px-2 py-1 text-danger transition hover:bg-danger/10"
                      aria-label="Remover grupo"
                      @click="removeGroup(groupIndex)"
                    >
                      <BoxIcon name="trash" class="h-4 w-4" />
                    </button>
                  </div>

                  <div class="flex flex-col gap-2">
                    <template v-for="(condition, condIndex) in group.conditions" :key="condIndex">
                      <div v-if="condIndex > 0" class="text-center text-xs font-semibold uppercase tracking-wide text-slate-400">
                        {{ group.match === 'any' ? 'OU' : 'E' }}
                      </div>

                      <div class="flex flex-wrap items-start gap-2 rounded-lg border border-slate-200 bg-white p-2.5">
                        <select
                          v-model="condition.subject"
                          class="min-w-[150px] flex-1 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100"
                          @change="onSubjectChange(condition)"
                        >
                          <option v-for="option in SUBJECTS" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>

                        <select
                          v-if="condition.subject === 'field'"
                          v-model="condition.field"
                          class="min-w-[140px] flex-1 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100"
                        >
                          <option value="">Campo...</option>
                          <option v-for="option in billingFieldOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>

                        <select
                          v-model="condition.operator"
                          class="min-w-[120px] flex-1 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100"
                        >
                          <option v-for="option in operatorsFor(condition.subject)" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>

                        <div class="min-w-[160px] flex-[2]">
                          <SearchMultiSelect
                            v-if="valueKind(condition) === 'search'"
                            v-model="condition.selected"
                            :type="SEARCH_TYPE[condition.subject]"
                          />

                          <TagSelect
                            v-else-if="valueKind(condition) === 'tags'"
                            v-model="condition.values"
                            :options="staticOptions[condition.subject]()"
                          />

                          <input
                            v-else-if="valueKind(condition) === 'number'"
                            v-model="condition.value"
                            type="number"
                            step="0.01"
                            class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100"
                            placeholder="Valor"
                          />

                          <input
                            v-else-if="valueKind(condition) === 'text'"
                            v-model="condition.value"
                            type="text"
                            class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100"
                            placeholder="Valor"
                          />

                          <p v-else class="px-1 py-2 text-xs text-slate-400">Sem valor</p>
                        </div>

                        <button
                          type="button"
                          class="cursor-pointer rounded-lg border border-slate-200 bg-white px-2 py-2 text-slate-400 transition hover:border-danger/30 hover:text-danger"
                          aria-label="Remover condição"
                          @click="removeCondition(group, condIndex)"
                        >
                          <BoxIcon name="x" class="h-4 w-4" />
                        </button>
                      </div>
                    </template>
                  </div>

                  <button
                    type="button"
                    class="mt-3 inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-dashed border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:border-primary hover:text-primary"
                    @click="addCondition(group)"
                  >
                    <BoxIcon name="plus" class="h-4 w-4" />
                    Adicionar condição
                  </button>
                </div>
              </template>
            </div>

            <button
              type="button"
              class="mt-4 inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:border-primary hover:text-primary"
              @click="addGroup"
            >
              <BoxIcon name="plus" class="h-4 w-4" />
              Adicionar grupo
            </button>
          </section>
        </div>
      </div>
    </div>
  </Teleport>
</template>
