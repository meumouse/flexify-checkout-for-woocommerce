<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';
import BaseSelect from '../fields/BaseSelect.vue';
import ToggleSwitch from '../toggles/ToggleSwitch.vue';
import SearchMultiSelect from '../fields/SearchMultiSelect.vue';
import MediaPickerField from '../fields/MediaPickerField.vue';

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
  banner: { icon: 'image', label: 'Banner' },
  reviews: { icon: 'star', label: 'Avaliações' },
};

const ADD_COMPONENTS = ['order_bump', 'banner', 'reviews', 'html', 'coupon', 'summary', 'notes'];

const REVIEW_SOURCES = [
  { value: 'product', label: 'Avaliações do produto' },
  { value: 'manual', label: 'Depoimentos manuais' },
];

const REVIEW_LAYOUTS = [
  { value: 'list', label: 'Lista' },
  { value: 'carousel', label: 'Carrossel' },
];

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

const WIDTH_OPTIONS = [
  { value: 'left', label: 'Metade (esquerda)' },
  { value: 'right', label: 'Metade (direita)' },
  { value: 'full', label: 'Largura total' },
];

const FIELD_ICON_OPTIONS = [
  { value: '', label: 'Nenhum' },
  { value: 'user', label: 'Usuário' },
  { value: 'envelope', label: 'E-mail' },
  { value: 'phone', label: 'Telefone' },
  { value: 'map', label: 'Localização' },
  { value: 'home', label: 'Endereço' },
  { value: 'id-card', label: 'Documento' },
  { value: 'credit-card', label: 'Cartão' },
  { value: 'calendar', label: 'Data' },
  { value: 'search', label: 'Busca' },
];

const inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-100';

// --- Draft state ---

const draft = reactive({ version: 1, steps: [] });
const selected = reactive({ stepId: '', itemId: '' });
const addMenuStepId = ref('');

const runtime = computed(() => store.runtime || {});
const fieldCatalog = computed(() => store.fieldCatalog || []);
const fieldOptions = computed(() => fieldCatalog.value.map((field) => ({ value: field.value, label: field.label })));

// --- Live preview iframe bridge ---

const previewFrame = ref(null);
const frameReady = ref(false);
let pushTimer = null;

function postToFrame(msg) {
  const win = previewFrame.value?.contentWindow;

  if (win) {
    win.postMessage(msg, window.location.origin);
  }
}

function targetFromSelection() {
  if (!selected.stepId) {
    return null;
  }

  return selected.itemId
    ? { scope: 'item', stepId: selected.stepId, itemId: selected.itemId }
    : { scope: 'step', stepId: selected.stepId };
}

function pushLayout() {
  if (frameReady.value) {
    postToFrame({ type: 'fc-builder:layout', layout: buildPayload() });
  }
}

function pushSelection() {
  if (!frameReady.value) {
    return;
  }

  if (selected.stepId) {
    postToFrame({ type: 'fc-builder:step', stepId: selected.stepId });
  }

  postToFrame({ type: 'fc-builder:select', target: targetFromSelection() });
}

function onFrameMessage(event) {
  if (event.origin !== window.location.origin) {
    return;
  }

  if (event.source !== previewFrame.value?.contentWindow) {
    return;
  }

  const data = event.data;

  if (!data || typeof data.type !== 'string') {
    return;
  }

  if (data.type === 'fc-builder:ready') {
    frameReady.value = true;
    pushLayout();
    pushSelection();
  } else if (data.type === 'fc-builder:select') {
    const target = data.target || {};
    const stepId = target.stepId || '';
    const itemId = target.scope === 'item' ? target.itemId || '' : '';

    // Dedupe to avoid bouncing the selection back to the iframe.
    if (selected.stepId !== stepId || selected.itemId !== itemId) {
      selected.stepId = stepId;
      selected.itemId = itemId;
    }
  }
}

function onFrameLoad() {
  // A reload (e.g. after Save) re-emits ready; reset until then.
  frameReady.value = false;
}

onMounted(() => window.addEventListener('message', onFrameMessage));
onBeforeUnmount(() => {
  window.removeEventListener('message', onFrameMessage);

  if (pushTimer) {
    clearTimeout(pushTimer);
  }
});

watch(
  () => draft,
  () => {
    if (pushTimer) {
      clearTimeout(pushTimer);
    }

    pushTimer = setTimeout(pushLayout, 250);
  },
  { deep: true },
);

watch(
  () => [selected.stepId, selected.itemId],
  () => pushSelection(),
);

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

// Ensure a selected field item has a style object so the inspector can v-model it.
watch(selectedItem, (item) => {
  if (item && item.kind === 'field' && !item.style) {
    item.style = {};
  }
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
    case 'banner':
      return { image: '', bg_color: '#0f172a', link: '', title: 'Oferta por tempo limitado', subtitle: '', button_text: '', title_color: '#ffffff', align: 'center', countdown: '' };
    case 'reviews':
      return { source: 'product', product_id: 0, items: [], limit: 4, layout: 'list' };
    default:
      return {};
  }
}

// --- Reviews manual list helpers ---

function addReviewItem(item) {
  if (!Array.isArray(item.config.items)) {
    item.config.items = [];
  }

  item.config.items.push({ author: '', text: '', rating: 5, avatar: '' });
}

function removeReviewItem(item, index) {
  item.config.items.splice(index, 1);
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

// Single-product picker shared by the order bump and the product reviews block.
const bumpSelection = computed({
  get() {
    const item = selectedItem.value;

    if (!item || !item.config || !item.config.product_id) {
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
          const field = { id: item.id, kind: 'field', field_id: item.field_id, order: itemIndex };

          if (item.style && Object.keys(item.style).length) {
            field.style = item.style;
          }

          return field;
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

    // Reload the preview to pull server-resolved data (order bump product,
    // resolved product reviews) into the live render.
    frameReady.value = false;
    previewFrame.value?.contentWindow?.location?.reload();
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

        <!-- Live preview (real React checkout in an iframe) -->
        <main class="relative flex flex-col overflow-hidden bg-slate-100">
          <div class="flex items-center justify-between gap-2 border-b border-slate-200 bg-white px-4 py-2.5">
            <div class="flex min-w-0 items-center gap-2">
              <span v-if="selectedStep" class="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-xs font-semibold text-white">
                {{ draft.steps.indexOf(selectedStep) + 1 }}
              </span>
              <h3 class="m-0 truncate text-sm font-semibold text-ink">
                {{ selectedStep ? (selectedStep.label || STEP_META[selectedStep.type].label) : 'Pré-visualização ao vivo' }}
              </h3>
            </div>

            <!-- Add item menu -->
            <div v-if="selectedStep" class="relative">
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

          <div class="relative flex-1">
            <iframe
              v-if="runtime.builder_preview_url"
              ref="previewFrame"
              :src="runtime.builder_preview_url"
              class="absolute inset-0 h-full w-full border-0 bg-white"
              title="Pré-visualização do checkout"
              @load="onFrameLoad"
            />

            <div
              v-else
              class="absolute inset-0 flex items-center justify-center px-6 text-center text-sm text-muted"
            >
              A pré-visualização ao vivo requer o WooCommerce ativo com uma página de checkout.
            </div>

            <div
              v-if="runtime.builder_preview_url && !frameReady"
              class="pointer-events-none absolute inset-0 flex items-center justify-center bg-slate-100/60 text-sm text-muted"
            >
              Carregando pré-visualização…
            </div>
          </div>
        </main>

        <!-- Inspector -->
        <aside class="overflow-y-auto border-l border-slate-200 bg-white px-4 py-5">
          <!-- Item inspector -->
          <div v-if="selectedItem && selectedStep" class="flex flex-col gap-4">
            <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-3">
              <div class="flex min-w-0 items-center gap-2">
                <BoxIcon :name="itemMeta(selectedItem).icon" class="h-4 w-4 shrink-0 text-primary" />
                <p class="m-0 truncate text-sm font-semibold text-ink">{{ itemMeta(selectedItem).label }}</p>
              </div>

              <div class="flex shrink-0 items-center gap-0.5">
                <button
                  type="button"
                  class="cursor-pointer rounded border-0 bg-transparent p-1 text-muted hover:text-ink disabled:opacity-30"
                  :disabled="selectedStep.items.indexOf(selectedItem) === 0"
                  aria-label="Mover para cima"
                  @click="moveItem(selectedStep, selectedStep.items.indexOf(selectedItem), -1)"
                >
                  <BoxIcon name="chevron-up" class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  class="cursor-pointer rounded border-0 bg-transparent p-1 text-muted hover:text-ink disabled:opacity-30"
                  :disabled="selectedStep.items.indexOf(selectedItem) === selectedStep.items.length - 1"
                  aria-label="Mover para baixo"
                  @click="moveItem(selectedStep, selectedStep.items.indexOf(selectedItem), 1)"
                >
                  <BoxIcon name="chevron-down" class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  class="cursor-pointer rounded border-0 bg-transparent p-1 text-danger/70 hover:text-danger"
                  aria-label="Remover item"
                  @click="removeItem(selectedStep, selectedItem)"
                >
                  <BoxIcon name="trash" class="h-4 w-4" />
                </button>
              </div>
            </div>

            <!-- Field item -->
            <template v-if="selectedItem.kind === 'field' && selectedItem.style">
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Campo</label>
                <BaseSelect v-model="selectedItem.field_id" :options="fieldOptions" placeholder="Selecionar campo" />
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Largura</label>
                  <BaseSelect v-model="selectedItem.style.width" :options="WIDTH_OPTIONS" size="sm" placeholder="Padrão" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Ícone</label>
                  <BaseSelect v-model="selectedItem.style.icon" :options="FIELD_ICON_OPTIONS" size="sm" />
                </div>
              </div>

              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Placeholder</label>
                <input v-model="selectedItem.style.placeholder" type="text" :class="inputClass" placeholder="Texto de exemplo" />
              </div>

              <p class="m-0 text-[11px] font-semibold uppercase tracking-wide text-muted">Rótulo</p>
              <div class="grid grid-cols-3 gap-3">
                <div>
                  <label class="mb-1 block text-[11px] text-muted">Cor</label>
                  <input v-model="selectedItem.style.label_color" type="color" class="h-9 w-full cursor-pointer rounded-lg border border-slate-300" />
                </div>
                <div>
                  <label class="mb-1 block text-[11px] text-muted">Tamanho</label>
                  <input v-model.number="selectedItem.style.label_size" type="number" min="8" max="40" :class="inputClass" />
                </div>
                <div>
                  <label class="mb-1 block text-[11px] text-muted">Peso</label>
                  <input v-model.number="selectedItem.style.label_weight" type="number" min="100" max="900" step="100" :class="inputClass" />
                </div>
              </div>

              <p class="m-0 text-[11px] font-semibold uppercase tracking-wide text-muted">Campo de entrada</p>
              <div class="grid grid-cols-3 gap-3">
                <div>
                  <label class="mb-1 block text-[11px] text-muted">Fundo</label>
                  <input v-model="selectedItem.style.input_bg" type="color" class="h-9 w-full cursor-pointer rounded-lg border border-slate-300" />
                </div>
                <div>
                  <label class="mb-1 block text-[11px] text-muted">Borda</label>
                  <input v-model="selectedItem.style.input_border" type="color" class="h-9 w-full cursor-pointer rounded-lg border border-slate-300" />
                </div>
                <div>
                  <label class="mb-1 block text-[11px] text-muted">Texto</label>
                  <input v-model="selectedItem.style.input_color" type="color" class="h-9 w-full cursor-pointer rounded-lg border border-slate-300" />
                </div>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="mb-1 block text-[11px] text-muted">Raio (px)</label>
                  <input v-model.number="selectedItem.style.input_radius" type="number" min="0" max="40" :class="inputClass" />
                </div>
                <div>
                  <label class="mb-1 block text-[11px] text-muted">Espaço abaixo (px)</label>
                  <input v-model.number="selectedItem.style.margin_bottom" type="number" min="0" max="80" :class="inputClass" />
                </div>
              </div>

              <p class="m-0 rounded-lg bg-slate-50 px-3 py-2 text-[11px] text-muted">
                Rótulo, obrigatoriedade e máscara são editados no <strong>Gerenciador de campos</strong>.
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

            <!-- Banner -->
            <template v-else-if="selectedItem.component === 'banner'">
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Imagem de fundo</label>
                <MediaPickerField v-model="selectedItem.config.image" :field="{ label: 'Imagem do banner' }" />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Cor de fundo</label>
                  <input v-model="selectedItem.config.bg_color" type="color" class="h-9 w-full cursor-pointer rounded-lg border border-slate-300" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Cor do texto</label>
                  <input v-model="selectedItem.config.title_color" type="color" class="h-9 w-full cursor-pointer rounded-lg border border-slate-300" />
                </div>
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Título</label>
                <input v-model="selectedItem.config.title" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Subtítulo</label>
                <input v-model="selectedItem.config.subtitle" type="text" :class="inputClass" />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Texto do botão</label>
                  <input v-model="selectedItem.config.button_text" type="text" :class="inputClass" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Alinhamento</label>
                  <BaseSelect v-model="selectedItem.config.align" :options="ALIGN_OPTIONS" size="sm" />
                </div>
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Link (opcional)</label>
                <input v-model="selectedItem.config.link" type="url" :class="inputClass" placeholder="https://" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Contador regressivo até</label>
                <input v-model="selectedItem.config.countdown" type="datetime-local" :class="inputClass" />
              </div>
            </template>

            <!-- Reviews -->
            <template v-else-if="selectedItem.component === 'reviews'">
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Origem das avaliações</label>
                <BaseSelect v-model="selectedItem.config.source" :options="REVIEW_SOURCES" size="sm" />
              </div>

              <div v-if="selectedItem.config.source === 'product'">
                <label class="mb-1 block text-xs font-medium text-ink">Produto</label>
                <SearchMultiSelect v-model="bumpSelection" type="products" placeholder="Buscar produto..." />
              </div>

              <div v-else class="flex flex-col gap-3">
                <div
                  v-for="(review, index) in selectedItem.config.items"
                  :key="index"
                  class="rounded-lg border border-slate-200 p-3"
                >
                  <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs font-semibold text-muted">Depoimento {{ index + 1 }}</span>
                    <button
                      type="button"
                      class="cursor-pointer rounded border-0 bg-transparent p-0.5 text-danger/70 hover:text-danger"
                      aria-label="Remover depoimento"
                      @click="removeReviewItem(selectedItem, index)"
                    >
                      <BoxIcon name="trash" class="h-4 w-4" />
                    </button>
                  </div>
                  <input v-model="review.author" type="text" :class="inputClass" class="mb-2" placeholder="Autor" />
                  <textarea v-model="review.text" rows="2" :class="inputClass" class="mb-2" placeholder="Depoimento" />
                  <div class="grid grid-cols-2 gap-2">
                    <input v-model.number="review.rating" type="number" min="0" max="5" :class="inputClass" placeholder="Nota" />
                    <MediaPickerField v-model="review.avatar" :field="{ label: 'Avatar' }" />
                  </div>
                </div>

                <button
                  type="button"
                  class="inline-flex cursor-pointer items-center justify-center gap-1 rounded-lg border border-primary-200 bg-white px-3 py-2 text-xs font-medium text-primary transition hover:bg-primary-50"
                  @click="addReviewItem(selectedItem)"
                >
                  <BoxIcon name="plus" class="h-3.5 w-3.5" />
                  Adicionar depoimento
                </button>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Quantidade</label>
                  <input v-model.number="selectedItem.config.limit" type="number" min="1" max="20" :class="inputClass" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Layout</label>
                  <BaseSelect v-model="selectedItem.config.layout" :options="REVIEW_LAYOUTS" size="sm" />
                </div>
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
