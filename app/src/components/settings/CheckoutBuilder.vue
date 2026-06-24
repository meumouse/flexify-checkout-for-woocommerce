<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';
import BaseSelect from '../fields/BaseSelect.vue';
import ToggleSwitch from '../toggles/ToggleSwitch.vue';
import SearchMultiSelect from '../fields/SearchMultiSelect.vue';

const props = defineProps({
  open: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const store = useSettingsStore();

const STEP_META = {
  contact: { icon: 'user', label: 'Contato' },
  shipping: { icon: 'package', label: 'Entrega' },
  payment: { icon: 'credit-card', label: 'Pagamento' },
  custom: { icon: 'layout', label: 'Etapa personalizada' },
};

const COMPONENT_META = {
  order_bump: { icon: 'purchase-tag', label: 'Order bump' },
  html: { icon: 'code', label: 'Bloco de conteúdo' },
  coupon: { icon: 'gift', label: 'Cupom de desconto' },
  summary: { icon: 'receipt', label: 'Resumo do pedido' },
  notes: { icon: 'note', label: 'Observações' },
};

const ADD_COMPONENTS = ['order_bump', 'html', 'coupon', 'summary', 'notes'];

const HTML_VARIANTS = [
  { value: 'raw', label: 'HTML puro' },
  { value: 'banner', label: 'Banner' },
  { value: 'badges', label: 'Selos de segurança' },
  { value: 'divider', label: 'Divisor' },
];

const ALIGN_OPTIONS = [
  { value: 'left', label: 'Esquerda' },
  { value: 'center', label: 'Centralizado' },
  { value: 'right', label: 'Direita' },
];

const inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-100';

// --- Draft state ---

const draft = reactive({ version: 1, steps: [] });
const selected = reactive({ stepId: '', itemId: '' });
const addMenuStepId = ref('');

const fieldCatalog = computed(() => store.fieldCatalog || []);
const fieldOptions = computed(() => fieldCatalog.value.map((field) => ({ value: field.value, label: field.label })));

function genId(prefix) {
  const rand = (window.crypto?.randomUUID?.() || Math.random().toString(36).slice(2)).replace(/-/g, '').slice(0, 12);

  return `${prefix}_${rand}`;
}

function cloneLayout() {
  const source = store.layout && Array.isArray(store.layout.steps) ? store.layout : { version: 1, steps: [] };
  const copy = JSON.parse(JSON.stringify(source));

  draft.version = copy.version || 1;
  draft.steps = Array.isArray(copy.steps) ? copy.steps : [];

  const first = draft.steps[0];
  selected.stepId = first ? first.id : '';
  selected.itemId = '';
  addMenuStepId.value = '';
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      cloneLayout();
    }
  },
  { immediate: true },
);

// --- Selection ---

const selectedStep = computed(() => draft.steps.find((step) => step.id === selected.stepId) || null);

const selectedItem = computed(() => {
  if (!selectedStep.value || !selected.itemId) {
    return null;
  }

  return (selectedStep.value.items || []).find((item) => item.id === selected.itemId) || null;
});

function selectStep(step) {
  selected.stepId = step.id;
  selected.itemId = '';
}

function selectItem(step, item) {
  selected.stepId = step.id;
  selected.itemId = item.id;
}

function fieldLabel(fieldId) {
  const found = fieldCatalog.value.find((field) => field.value === fieldId);

  return found ? found.label : fieldId;
}

// --- Steps ---

function paymentIndex() {
  return draft.steps.findIndex((step) => step.type === 'payment');
}

function addStep() {
  const step = {
    id: genId('step'),
    type: 'custom',
    label: 'Nova etapa',
    enabled: true,
    order: 0,
    items: [],
  };

  const payAt = paymentIndex();
  const insertAt = payAt === -1 ? draft.steps.length : payAt;

  draft.steps.splice(insertAt, 0, step);
  selectStep(step);
}

function removeStep(step) {
  if (step.type !== 'custom') {
    return;
  }

  if (!window.confirm(`Remover a etapa "${step.label}"? Os campos dentro dela voltam para a etapa semântica mais próxima.`)) {
    return;
  }

  const index = draft.steps.findIndex((current) => current.id === step.id);

  if (index !== -1) {
    draft.steps.splice(index, 1);
  }

  if (selected.stepId === step.id) {
    const fallback = draft.steps[0];
    selected.stepId = fallback ? fallback.id : '';
    selected.itemId = '';
  }
}

function canMoveStep(index, dir) {
  const target = index + dir;

  if (target < 0 || target >= draft.steps.length) {
    return false;
  }

  // Payment stays last; nothing can move below it.
  const payAt = paymentIndex();

  if (draft.steps[index].type === 'payment') {
    return false;
  }

  if (payAt !== -1 && target >= payAt && dir > 0) {
    return false;
  }

  return true;
}

function moveStep(index, dir) {
  if (!canMoveStep(index, dir)) {
    return;
  }

  const target = index + dir;
  const [moved] = draft.steps.splice(index, 1);
  draft.steps.splice(target, 0, moved);
}

// --- Items ---

function defaultComponentConfig(component) {
  switch (component) {
    case 'order_bump':
      return { product_id: 0, quantity: 1, headline: '', description: '', image_id: 0, discount_label: '', default_checked: false, highlight_color: '' };
    case 'html':
      return { html: '', variant: 'raw', align: 'left' };
    case 'coupon':
      return { title: '' };
    case 'summary':
      return { title: '', collapsible: false, hide_coupon: false };
    case 'notes':
      return { label: 'Observações do pedido', placeholder: '', required: false };
    default:
      return {};
  }
}

function toggleAddMenu(step) {
  addMenuStepId.value = addMenuStepId.value === step.id ? '' : step.id;
}

function addFieldItem(step) {
  const firstField = fieldOptions.value[0];

  const item = {
    id: genId('it'),
    kind: 'field',
    field_id: firstField ? firstField.value : '',
    order: step.items.length,
  };

  step.items.push(item);
  addMenuStepId.value = '';
  selectItem(step, item);
}

function addComponentItem(step, component) {
  const item = {
    id: genId('it'),
    kind: 'component',
    component,
    config: defaultComponentConfig(component),
    order: step.items.length,
  };

  step.items.push(item);
  addMenuStepId.value = '';
  selectItem(step, item);
}

function removeItem(step, item) {
  const index = step.items.findIndex((current) => current.id === item.id);

  if (index !== -1) {
    step.items.splice(index, 1);
  }

  if (selected.itemId === item.id) {
    selected.itemId = '';
  }
}

function moveItem(step, index, dir) {
  const target = index + dir;

  if (target < 0 || target >= step.items.length) {
    return;
  }

  const [moved] = step.items.splice(index, 1);
  step.items.splice(target, 0, moved);
}

function itemMeta(item) {
  if (item.kind === 'field') {
    return { icon: 'text', label: fieldLabel(item.field_id) };
  }

  return COMPONENT_META[item.component] || { icon: 'layout', label: item.component };
}

// --- Order bump product picker ---

const bumpSelection = computed({
  get() {
    const item = selectedItem.value;

    if (!item || item.component !== 'order_bump' || !item.config.product_id) {
      return [];
    }

    const label = item.product?.name || `#${item.config.product_id}`;

    return [{ id: item.config.product_id, label }];
  },
  set(value) {
    const item = selectedItem.value;

    if (!item) {
      return;
    }

    const last = Array.isArray(value) && value.length ? value[value.length - 1] : null;

    item.config.product_id = last ? Number(last.id) : 0;
    item.product = last ? { id: Number(last.id), name: last.label } : null;
  },
});

// --- Save ---

function buildPayload() {
  return {
    version: draft.version || 1,
    steps: draft.steps.map((step, stepIndex) => ({
      id: step.id,
      type: step.type,
      label: step.label,
      enabled: step.enabled !== false,
      order: stepIndex,
      items: (step.items || []).map((item, itemIndex) => {
        if (item.kind === 'field') {
          return { id: item.id, kind: 'field', field_id: item.field_id, order: itemIndex };
        }

        return {
          id: item.id,
          kind: 'component',
          component: item.component,
          config: item.config,
          order: itemIndex,
        };
      }),
    })),
  };
}

const saving = computed(() => store.savingLayout);

async function save() {
  if (saving.value) {
    return;
  }

  const response = await store.saveLayout(buildPayload());

  if (response?.status === 'success') {
    cloneLayout();
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
            <h2 class="m-0 text-[15px] font-semibold text-ink">Construtor de checkout</h2>
            <p class="m-0 text-[13px] text-slate-500">Monte as etapas, campos e componentes do checkout React.</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <BaseButton variant="secondary" size="sm" @click="emit('close')">Cancelar</BaseButton>
          <BaseButton size="sm" :loading="saving" @click="save">Salvar</BaseButton>
        </div>
      </header>

      <!-- Body: 3 regions -->
      <div class="grid flex-1 grid-cols-1 overflow-hidden lg:grid-cols-[300px_minmax(0,1fr)_340px]">
        <!-- Steps rail -->
        <aside class="flex flex-col overflow-y-auto border-r border-slate-200 bg-white">
          <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
            <p class="m-0 text-xs font-semibold uppercase tracking-wide text-muted">Etapas</p>
            <button
              type="button"
              class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-primary-200 bg-white px-2 py-1 text-xs font-medium text-primary transition hover:bg-primary-50"
              @click="addStep"
            >
              <BoxIcon name="plus" class="h-3.5 w-3.5" />
              Etapa
            </button>
          </div>

          <ul class="m-0 flex list-none flex-col gap-2 p-3">
            <li v-for="(step, index) in draft.steps" :key="step.id">
              <div
                class="cursor-pointer rounded-xl border bg-white px-3 py-2.5 transition"
                :class="[
                  selected.stepId === step.id && !selected.itemId ? 'border-primary ring-2 ring-primary-100' : 'border-slate-200 hover:border-slate-300',
                  step.enabled === false ? 'opacity-60' : '',
                ]"
                @click="selectStep(step)"
              >
                <div class="flex items-center justify-between gap-2">
                  <div class="flex min-w-0 items-center gap-2">
                    <BoxIcon :name="STEP_META[step.type].icon" class="h-4 w-4 shrink-0 text-slate-400" />
                    <span class="truncate text-sm font-medium text-ink">{{ step.label || STEP_META[step.type].label }}</span>
                  </div>

                  <div class="flex shrink-0 items-center gap-0.5">
                    <button
                      type="button"
                      class="cursor-pointer border-0 bg-transparent p-0.5 text-muted hover:text-ink disabled:cursor-not-allowed disabled:opacity-30"
                      :disabled="!canMoveStep(index, -1)"
                      aria-label="Mover etapa para cima"
                      @click.stop="moveStep(index, -1)"
                    >
                      <BoxIcon name="chevron-up" class="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      class="cursor-pointer border-0 bg-transparent p-0.5 text-muted hover:text-ink disabled:cursor-not-allowed disabled:opacity-30"
                      :disabled="!canMoveStep(index, 1)"
                      aria-label="Mover etapa para baixo"
                      @click.stop="moveStep(index, 1)"
                    >
                      <BoxIcon name="chevron-down" class="h-4 w-4" />
                    </button>
                  </div>
                </div>

                <p class="m-0 mt-1 pl-6 text-[11px] text-muted">
                  {{ (step.items || []).length }} {{ (step.items || []).length === 1 ? 'item' : 'itens' }}
                  <span v-if="step.type !== 'custom'"> · semântica</span>
                </p>
              </div>
            </li>
          </ul>
        </aside>

        <!-- Preview canvas -->
        <main class="overflow-y-auto bg-slate-100 px-6 py-8">
          <div v-if="selectedStep" class="mx-auto max-w-md">
            <div class="mb-4 flex items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-xs font-semibold text-white">
                  {{ draft.steps.indexOf(selectedStep) + 1 }}
                </span>
                <h3 class="m-0 text-base font-semibold text-ink">{{ selectedStep.label || STEP_META[selectedStep.type].label }}</h3>
              </div>

              <!-- Add item menu -->
              <div class="relative">
                <button
                  type="button"
                  class="inline-flex cursor-pointer items-center gap-1 rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white transition hover:bg-primary-700"
                  @click="toggleAddMenu(selectedStep)"
                >
                  <BoxIcon name="plus" class="h-3.5 w-3.5" />
                  Adicionar
                </button>

                <div
                  v-if="addMenuStepId === selectedStep.id"
                  class="absolute right-0 z-10 mt-1 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg"
                >
                  <button
                    type="button"
                    class="flex w-full cursor-pointer items-center gap-2 border-0 bg-transparent px-3 py-2 text-left text-sm text-ink hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="!fieldOptions.length"
                    @click="addFieldItem(selectedStep)"
                  >
                    <BoxIcon name="text" class="h-4 w-4 text-slate-400" />
                    Campo do formulário
                  </button>
                  <div class="my-1 border-t border-slate-100" />
                  <button
                    v-for="component in ADD_COMPONENTS"
                    :key="component"
                    type="button"
                    class="flex w-full cursor-pointer items-center gap-2 border-0 bg-transparent px-3 py-2 text-left text-sm text-ink hover:bg-slate-50"
                    @click="addComponentItem(selectedStep, component)"
                  >
                    <BoxIcon :name="COMPONENT_META[component].icon" class="h-4 w-4 text-slate-400" />
                    {{ COMPONENT_META[component].label }}
                  </button>
                </div>
              </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <p v-if="!(selectedStep.items || []).length" class="m-0 py-8 text-center text-sm text-muted">
                Nenhum item nesta etapa. Use “Adicionar” para incluir campos e componentes.
              </p>

              <ul v-else class="m-0 flex list-none flex-col gap-2.5 p-0">
                <li
                  v-for="(item, index) in selectedStep.items"
                  :key="item.id"
                  class="group cursor-pointer rounded-xl border p-3 transition"
                  :class="selected.itemId === item.id ? 'border-primary ring-2 ring-primary-100' : 'border-slate-200 hover:border-slate-300'"
                  @click="selectItem(selectedStep, item)"
                >
                  <div class="flex items-center justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-2">
                      <BoxIcon :name="itemMeta(item).icon" class="h-4 w-4 shrink-0 text-primary" />
                      <span class="truncate text-sm font-medium text-ink">{{ itemMeta(item).label }}</span>
                      <span
                        v-if="item.kind === 'component'"
                        class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-muted"
                      >
                        {{ COMPONENT_META[item.component]?.label }}
                      </span>
                    </div>

                    <div class="flex shrink-0 items-center gap-0.5">
                      <button
                        type="button"
                        class="cursor-pointer border-0 bg-transparent p-0.5 text-muted hover:text-ink disabled:opacity-30"
                        :disabled="index === 0"
                        aria-label="Mover para cima"
                        @click.stop="moveItem(selectedStep, index, -1)"
                      >
                        <BoxIcon name="chevron-up" class="h-4 w-4" />
                      </button>
                      <button
                        type="button"
                        class="cursor-pointer border-0 bg-transparent p-0.5 text-muted hover:text-ink disabled:opacity-30"
                        :disabled="index === selectedStep.items.length - 1"
                        aria-label="Mover para baixo"
                        @click.stop="moveItem(selectedStep, index, 1)"
                      >
                        <BoxIcon name="chevron-down" class="h-4 w-4" />
                      </button>
                      <button
                        type="button"
                        class="cursor-pointer border-0 bg-transparent p-0.5 text-danger/70 hover:text-danger"
                        aria-label="Remover item"
                        @click.stop="removeItem(selectedStep, item)"
                      >
                        <BoxIcon name="trash" class="h-4 w-4" />
                      </button>
                    </div>
                  </div>

                  <!-- Field placeholder preview -->
                  <div v-if="item.kind === 'field'" class="mt-2 h-8 rounded-lg border border-dashed border-slate-200 bg-slate-50" />

                  <!-- Component previews -->
                  <div v-else-if="item.component === 'order_bump'" class="mt-2 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50/60 p-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-md border border-emerald-300 bg-white">
                      <BoxIcon name="purchase-tag" class="h-4 w-4 text-emerald-600" />
                    </span>
                    <div class="min-w-0">
                      <p class="m-0 truncate text-xs font-semibold text-ink">{{ item.config.headline || item.product?.name || 'Oferta especial' }}</p>
                      <p class="m-0 truncate text-[11px] text-muted">{{ item.product?.name || 'Selecione um produto' }}</p>
                    </div>
                  </div>

                  <div v-else-if="item.component === 'html'" class="mt-2 rounded-lg border border-dashed border-slate-200 bg-slate-50 p-2 text-[11px] text-muted">
                    <span v-if="item.config.html">Conteúdo HTML ({{ item.config.variant }})</span>
                    <span v-else>Bloco de conteúdo vazio</span>
                  </div>

                  <div v-else class="mt-2 h-6 rounded-lg border border-dashed border-slate-200 bg-slate-50" />
                </li>
              </ul>
            </div>
          </div>

          <p v-else class="mt-10 text-center text-sm text-muted">Selecione uma etapa para começar.</p>
        </main>

        <!-- Inspector -->
        <aside class="overflow-y-auto border-l border-slate-200 bg-white px-4 py-5">
          <!-- Item inspector -->
          <div v-if="selectedItem" class="flex flex-col gap-4">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
              <BoxIcon :name="itemMeta(selectedItem).icon" class="h-4 w-4 text-primary" />
              <p class="m-0 text-sm font-semibold text-ink">{{ itemMeta(selectedItem).label }}</p>
            </div>

            <!-- Field item -->
            <template v-if="selectedItem.kind === 'field'">
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Campo</label>
                <BaseSelect v-model="selectedItem.field_id" :options="fieldOptions" placeholder="Selecionar campo" />
              </div>
              <p class="m-0 rounded-lg bg-slate-50 px-3 py-2 text-[11px] text-muted">
                As propriedades do campo (rótulo, obrigatoriedade, máscara, posição) são editadas no <strong>Gerenciador de campos</strong>.
              </p>
            </template>

            <!-- Order bump -->
            <template v-else-if="selectedItem.component === 'order_bump'">
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Produto da oferta</label>
                <SearchMultiSelect v-model="bumpSelection" type="products" placeholder="Buscar produto..." />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Título da oferta</label>
                <input v-model="selectedItem.config.headline" type="text" :class="inputClass" placeholder="Adicione e economize" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Descrição</label>
                <textarea v-model="selectedItem.config.description" rows="3" :class="inputClass" />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Selo de desconto</label>
                  <input v-model="selectedItem.config.discount_label" type="text" :class="inputClass" placeholder="-20%" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Cor de destaque</label>
                  <input v-model="selectedItem.config.highlight_color" type="color" class="h-9 w-full cursor-pointer rounded-lg border border-slate-300" />
                </div>
              </div>
              <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-medium text-ink">Marcado por padrão</span>
                <ToggleSwitch v-model="selectedItem.config.default_checked" :true-value="true" :false-value="false" />
              </div>
            </template>

            <!-- HTML block -->
            <template v-else-if="selectedItem.component === 'html'">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Variação</label>
                  <BaseSelect v-model="selectedItem.config.variant" :options="HTML_VARIANTS" size="sm" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Alinhamento</label>
                  <BaseSelect v-model="selectedItem.config.align" :options="ALIGN_OPTIONS" size="sm" />
                </div>
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Conteúdo HTML</label>
                <textarea v-model="selectedItem.config.html" rows="6" :class="inputClass" class="font-mono text-xs" />
              </div>
            </template>

            <!-- Coupon -->
            <template v-else-if="selectedItem.component === 'coupon'">
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Título (opcional)</label>
                <input v-model="selectedItem.config.title" type="text" :class="inputClass" placeholder="Tem um cupom?" />
              </div>
            </template>

            <!-- Summary -->
            <template v-else-if="selectedItem.component === 'summary'">
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Título (opcional)</label>
                <input v-model="selectedItem.config.title" type="text" :class="inputClass" placeholder="Resumo do pedido" />
              </div>
              <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-medium text-ink">Recolhível</span>
                <ToggleSwitch v-model="selectedItem.config.collapsible" :true-value="true" :false-value="false" />
              </div>
              <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-medium text-ink">Ocultar cupom no resumo</span>
                <ToggleSwitch v-model="selectedItem.config.hide_coupon" :true-value="true" :false-value="false" />
              </div>
            </template>

            <!-- Notes -->
            <template v-else-if="selectedItem.component === 'notes'">
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Rótulo</label>
                <input v-model="selectedItem.config.label" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Placeholder</label>
                <input v-model="selectedItem.config.placeholder" type="text" :class="inputClass" />
              </div>
              <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-medium text-ink">Obrigatório</span>
                <ToggleSwitch v-model="selectedItem.config.required" :true-value="true" :false-value="false" />
              </div>
            </template>
          </div>

          <!-- Step inspector -->
          <div v-else-if="selectedStep" class="flex flex-col gap-4">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
              <BoxIcon :name="STEP_META[selectedStep.type].icon" class="h-4 w-4 text-primary" />
              <p class="m-0 text-sm font-semibold text-ink">Configurar etapa</p>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-ink">Rótulo da etapa</label>
              <input v-model="selectedStep.label" type="text" :class="inputClass" />
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-ink">Tipo</label>
              <p class="m-0 rounded-lg bg-slate-50 px-3 py-2 text-sm text-ink">{{ STEP_META[selectedStep.type].label }}</p>
            </div>

            <div class="flex items-center justify-between gap-2">
              <span class="text-xs font-medium text-ink">Etapa ativa</span>
              <ToggleSwitch v-model="selectedStep.enabled" :true-value="true" :false-value="false" />
            </div>

            <div v-if="selectedStep.type === 'custom'" class="border-t border-slate-100 pt-4">
              <button
                type="button"
                class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-danger/30 bg-transparent px-3 py-2 text-sm font-medium text-danger transition hover:bg-danger/10"
                @click="removeStep(selectedStep)"
              >
                <BoxIcon name="trash" class="h-4 w-4" />
                Remover etapa
              </button>
            </div>
          </div>

          <p v-else class="text-center text-sm text-muted">Selecione uma etapa ou item.</p>
        </aside>
      </div>
    </div>
  </Teleport>
</template>
