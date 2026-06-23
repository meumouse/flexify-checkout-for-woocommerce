<script setup>
import { computed, reactive, ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';
import ModalDialog from '../modals/ModalDialog.vue';
import SearchMultiSelect from '../fields/SearchMultiSelect.vue';

const store = useSettingsStore();

const TYPE_RULES = [
  { value: 'show', label: 'Mostrar' },
  { value: 'hide', label: 'Ocultar' },
];

const COMPONENTS = [
  { value: 'field', label: 'Campo' },
  { value: 'payment', label: 'Forma de pagamento' },
  { value: 'shipping', label: 'Forma de entrega' },
];

const VERIFICATIONS = [
  { value: 'field', label: 'Campo' },
  { value: 'qtd_cart_total', label: 'Quantidade total do carrinho' },
  { value: 'cart_total_value', label: 'Valor total do carrinho' },
];

const OPERATORS = [
  { value: 'is', label: 'É' },
  { value: 'is_not', label: 'Não é' },
  { value: 'empty', label: 'Vazio' },
  { value: 'not_empty', label: 'Não está vazio' },
  { value: 'contains', label: 'Contém' },
  { value: 'not_contain', label: 'Não contém' },
  { value: 'start_with', label: 'Começa com' },
  { value: 'finish_with', label: 'Termina com' },
  { value: 'bigger_then', label: 'Maior que' },
  { value: 'less_than', label: 'Menor que' },
  { value: 'checked', label: 'Marcado' },
  { value: 'not_checked', label: 'Desmarcado' },
];

const USER_FILTERS = [
  { value: 'all_users', label: 'Todos os usuários (Padrão)' },
  { value: 'all_roles', label: 'Todas as funções' },
  { value: 'specific_user', label: 'Usuários específicos' },
  { value: 'specific_role', label: 'Função de usuário específica' },
];

const PRODUCT_FILTERS = [
  { value: 'all_products', label: 'Todos os produtos (Padrão)' },
  { value: 'all_categories', label: 'Todas as categorias' },
  { value: 'all_attributes', label: 'Todos os atributos' },
  { value: 'specific_products', label: 'Produtos específicos' },
  { value: 'specific_categories', label: 'Categorias específicas' },
  { value: 'specific_attributes', label: 'Atributos específicos' },
];

const NO_VALUE_OPERATORS = ['empty', 'not_empty', 'checked', 'not_checked'];

const billingFieldOptions = computed(() => {
  return Object.entries(store.fields)
    .filter(([id]) => id.startsWith('billing_'))
    .map(([id, field]) => ({ value: id, label: field?.label || id }));
});

// --- Editor state ---

const editorOpen = ref(false);
const editorSaving = ref(false);
const editingIndex = ref(null);

const form = reactive({
  type_rule: 'show',
  component: 'field',
  component_field: '',
  shipping_method: '',
  payment_method: '',
  verification_condition: 'field',
  verification_condition_field: '',
  condition: 'is',
  condition_value: '',
  filter_user: 'all_users',
  specific_role: '',
  product_filter: 'all_products',
  specificUsers: [],
  specificProducts: [],
  specificCategories: [],
  specificAttributes: [],
});

function resetForm() {
  Object.assign(form, {
    type_rule: 'show',
    component: 'field',
    component_field: '',
    shipping_method: '',
    payment_method: '',
    verification_condition: 'field',
    verification_condition_field: '',
    condition: 'is',
    condition_value: '',
    filter_user: 'all_users',
    specific_role: '',
    product_filter: 'all_products',
    specificUsers: [],
    specificProducts: [],
    specificCategories: [],
    specificAttributes: [],
  });
}

function openCreate() {
  resetForm();
  editingIndex.value = null;
  editorOpen.value = true;
}

function openEdit(item) {
  resetForm();
  editingIndex.value = item.index;

  const condition = item.condition || {};

  Object.assign(form, {
    type_rule: condition.type_rule || 'show',
    component: condition.component || 'field',
    component_field: condition.component_field || '',
    shipping_method: condition.shipping_method || '',
    payment_method: condition.payment_method || '',
    verification_condition: condition.verification_condition || 'field',
    verification_condition_field: condition.verification_condition_field || '',
    condition: condition.condition || 'is',
    condition_value: condition.condition_value || '',
    filter_user: condition.filter_user || 'all_users',
    specific_role: condition.specific_role || '',
    product_filter: condition.product_filter || 'all_products',
    specificUsers: item.selected_items?.specific_users || [],
    specificProducts: item.selected_items?.specific_products || [],
    specificCategories: item.selected_items?.specific_categories || [],
    specificAttributes: item.selected_items?.specific_attributes || [],
  });

  editorOpen.value = true;
}

const requiresValue = computed(() => !NO_VALUE_OPERATORS.includes(form.condition));

const isValid = computed(() => {
  if (form.component === 'field' && !form.component_field) {
    return false;
  }

  if (form.component === 'shipping' && !form.shipping_method) {
    return false;
  }

  if (form.component === 'payment' && !form.payment_method) {
    return false;
  }

  if (form.verification_condition === 'field' && !form.verification_condition_field) {
    return false;
  }

  if (requiresValue.value && form.condition_value === '') {
    return false;
  }

  return true;
});

function buildPayload() {
  return {
    type_rule: form.type_rule,
    component: form.component,
    component_field: form.component === 'field' ? form.component_field : null,
    shipping_method: form.component === 'shipping' ? form.shipping_method : null,
    payment_method: form.component === 'payment' ? form.payment_method : null,
    verification_condition: form.verification_condition,
    verification_condition_field: form.verification_condition === 'field' ? form.verification_condition_field : null,
    condition: form.condition,
    condition_value: requiresValue.value ? form.condition_value : '',
    filter_user: form.filter_user,
    specific_role: form.filter_user === 'specific_role' ? form.specific_role : null,
    specific_user: form.filter_user === 'specific_user' ? form.specificUsers.map((item) => item.id) : null,
    product_filter: form.product_filter,
    specific_products: form.product_filter === 'specific_products' ? form.specificProducts.map((item) => item.id) : null,
    specific_categories: form.product_filter === 'specific_categories' ? form.specificCategories.map((item) => item.id) : null,
    specific_attributes: form.product_filter === 'specific_attributes' ? form.specificAttributes.map((item) => item.id) : null,
  };
}

async function submit() {
  if (!isValid.value || editorSaving.value) {
    return;
  }

  editorSaving.value = true;

  try {
    const response = editingIndex.value === null
      ? await store.addCondition(buildPayload())
      : await store.updateCondition(editingIndex.value, buildPayload());

    if (response?.status === 'success') {
      editorOpen.value = false;
    }
  } finally {
    editorSaving.value = false;
  }
}

async function remove(item) {
  if (window.confirm('Tem certeza que deseja excluir esta condição?')) {
    await store.removeCondition(item.index);
  }
}

const inputClass = 'flexify-field-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100';
</script>

<template>
  <div>
    <div
      v-if="!store.isPro"
      class="mb-4 rounded-xl border border-warning/40 bg-warning/10 px-4 py-3 text-sm text-ink"
    >
      O gerenciador de condições requer uma licença Pro ativa. Ative sua licença na aba Sobre.
    </div>

    <div :class="!store.isPro ? 'pointer-events-none opacity-50' : ''">
      <div
        v-if="!store.conditions.length"
        class="mb-4 rounded-xl border border-info/30 bg-info/5 px-4 py-3 text-sm text-muted"
      >
        Ainda não existem condições.
      </div>

      <ul v-else class="m-0 mb-4 flex list-none flex-col gap-2 p-0">
        <li
          v-for="item in store.conditions"
          :key="item.index"
          class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3"
        >
          <div class="min-w-0">
            <p class="m-0 truncate text-sm font-medium text-ink">{{ item.summary?.line_1 }}</p>
            <p class="m-0 mt-0.5 truncate text-xs text-muted">{{ item.summary?.line_2 }}</p>
          </div>

          <div class="flex shrink-0 items-center gap-1.5">
            <button
              type="button"
              class="cursor-pointer rounded-lg border border-slate-300 bg-transparent px-2.5 py-1 text-xs font-medium text-ink transition-colors hover:bg-slate-100"
              @click="openEdit(item)"
            >
              Editar
            </button>

            <button
              type="button"
              class="cursor-pointer rounded-lg border border-danger/30 bg-transparent px-2 py-1 text-danger transition-colors hover:bg-danger/10"
              aria-label="Excluir condição"
              @click="remove(item)"
            >
              <BoxIcon name="trash" class="h-4 w-4" />
            </button>
          </div>
        </li>
      </ul>

      <BaseButton @click="openCreate">Criar uma nova condição</BaseButton>
    </div>

    <ModalDialog
      :open="editorOpen"
      :title="editingIndex === null ? 'Criar uma nova condição' : 'Editar condição'"
      size="lg"
      @close="editorOpen = false"
    >
      <div class="flex flex-col gap-5">
        <section>
          <p class="mb-2 mt-0 text-xs font-semibold uppercase tracking-wide text-muted">Regra</p>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-ink">Tipo da regra *</label>
              <select v-model="form.type_rule" :class="inputClass">
                <option v-for="option in TYPE_RULES" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-ink">Componente *</label>
              <select v-model="form.component" :class="inputClass">
                <option v-for="option in COMPONENTS" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>

            <div v-if="form.component === 'field'">
              <label class="mb-1 block text-sm font-medium text-ink">Campo do checkout *</label>
              <select v-model="form.component_field" :class="inputClass">
                <option value="">Selecione um campo</option>
                <option v-for="option in billingFieldOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>

            <div v-if="form.component === 'shipping'">
              <label class="mb-1 block text-sm font-medium text-ink">Forma de entrega *</label>
              <select v-model="form.shipping_method" :class="inputClass">
                <option value="">Selecione uma forma de entrega</option>
                <option v-for="option in store.runtime?.shipping_methods || []" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>

            <div v-if="form.component === 'payment'">
              <label class="mb-1 block text-sm font-medium text-ink">Forma de pagamento *</label>
              <select v-model="form.payment_method" :class="inputClass">
                <option value="">Selecione uma forma de pagamento</option>
                <option v-for="option in store.runtime?.payment_gateways || []" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>
          </div>
        </section>

        <section>
          <p class="mb-2 mt-0 text-xs font-semibold uppercase tracking-wide text-muted">Verificação</p>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-ink">Condição de verificação *</label>
              <select v-model="form.verification_condition" :class="inputClass">
                <option v-for="option in VERIFICATIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>

            <div v-if="form.verification_condition === 'field'">
              <label class="mb-1 block text-sm font-medium text-ink">Campo do checkout *</label>
              <select v-model="form.verification_condition_field" :class="inputClass">
                <option value="">Selecione um campo</option>
                <option v-for="option in billingFieldOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-ink">Condição *</label>
              <select v-model="form.condition" :class="inputClass">
                <option v-for="option in OPERATORS" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>

            <div v-if="requiresValue">
              <label class="mb-1 block text-sm font-medium text-ink">
                Valor da condição *
                <span v-if="form.verification_condition === 'cart_total_value'" class="font-normal text-muted">({{ store.runtime?.currency_symbol }})</span>
              </label>
              <input
                v-model="form.condition_value"
                :type="form.verification_condition === 'field' ? 'text' : 'number'"
                :class="inputClass"
                placeholder="Valor"
              />
            </div>
          </div>
        </section>

        <section>
          <p class="mb-2 mt-0 text-xs font-semibold uppercase tracking-wide text-muted">Filtros</p>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-ink">Selecionar Usuário/Função</label>
              <select v-model="form.filter_user" :class="inputClass">
                <option v-for="option in USER_FILTERS" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-ink">Filtro de produtos</label>
              <select v-model="form.product_filter" :class="inputClass">
                <option v-for="option in PRODUCT_FILTERS" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>

            <div v-if="form.filter_user === 'specific_role'">
              <label class="mb-1 block text-sm font-medium text-ink">Função de usuário específica</label>
              <select v-model="form.specific_role" :class="inputClass">
                <option value="">Selecione uma função</option>
                <option v-for="option in store.runtime?.user_roles || []" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>

            <div v-if="form.filter_user === 'specific_user'">
              <label class="mb-1 block text-sm font-medium text-ink">Usuários específicos</label>
              <SearchMultiSelect v-model="form.specificUsers" type="users" />
            </div>

            <div v-if="form.product_filter === 'specific_products'">
              <label class="mb-1 block text-sm font-medium text-ink">Produtos específicos</label>
              <SearchMultiSelect v-model="form.specificProducts" type="products" />
            </div>

            <div v-if="form.product_filter === 'specific_categories'">
              <label class="mb-1 block text-sm font-medium text-ink">Categorias específicas</label>
              <SearchMultiSelect v-model="form.specificCategories" type="categories" />
            </div>

            <div v-if="form.product_filter === 'specific_attributes'">
              <label class="mb-1 block text-sm font-medium text-ink">Atributos específicos</label>
              <SearchMultiSelect v-model="form.specificAttributes" type="attributes" />
            </div>
          </div>
        </section>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <BaseButton variant="secondary" @click="editorOpen = false">Cancelar</BaseButton>
          <BaseButton :loading="editorSaving" :disabled="!isValid" @click="submit">
            {{ editingIndex === null ? 'Criar condição' : 'Atualizar condição' }}
          </BaseButton>
        </div>
      </template>
    </ModalDialog>
  </div>
</template>
