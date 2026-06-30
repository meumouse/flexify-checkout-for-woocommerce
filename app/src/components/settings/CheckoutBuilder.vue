<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';
import BaseSelect from '../fields/BaseSelect.vue';
import ToggleSwitch from '../toggles/ToggleSwitch.vue';
import SearchMultiSelect from '../fields/SearchMultiSelect.vue';
import MediaPickerField from '../fields/MediaPickerField.vue';
import FontsManager from './FontsManager.vue';

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

const FIELD_TYPES = [
  { value: 'text', label: 'Texto' },
  { value: 'textarea', label: 'Área de texto' },
  { value: 'number', label: 'Número' },
  { value: 'password', label: 'Senha' },
  { value: 'phone', label: 'Telefone' },
  { value: 'url', label: 'URL' },
  { value: 'email', label: 'E-mail' },
  { value: 'select', label: 'Seletor/Lista' },
  { value: 'checkbox', label: 'Caixa de seleção' },
  { value: 'date', label: 'Data' },
];

const POSITIONS = [
  { value: 'left', label: 'Esquerda' },
  { value: 'right', label: 'Direita' },
  { value: 'full', label: 'Largura completa' },
];

const ENABLED_OPTIONS = [
  { value: 'yes', label: 'Ativo' },
  { value: 'no', label: 'Inativo' },
];

const REVIEW_SOURCES = [
  { value: 'product', label: 'Avaliações do produto' },
  { value: 'manual', label: 'Depoimentos manuais' },
];

const REVIEW_LAYOUTS = [
  { value: 'list', label: 'Lista' },
  { value: 'carousel', label: 'Carrossel' },
];

const PAYMENT_LAYOUT_OPTIONS = [
  { value: 'cards', label: 'Cards' },
  { value: 'accordion', label: 'Sanfona' },
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

const PALETTE_FIELDS = [
  { key: 'primary', label: 'Primária' },
  { key: 'primary_hover', label: 'Primária (hover)' },
  { key: 'secondary', label: 'Secundária' },
  { key: 'success', label: 'Sucesso' },
  { key: 'warning', label: 'Aviso' },
  { key: 'danger', label: 'Perigo' },
  { key: 'info', label: 'Informação' },
];

const inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-100';

// --- Draft state ---

const draft = reactive({ version: 1, steps: [] });
const selected = reactive({ stepId: '', itemId: '' });
const addMenuStepId = ref('');

// Field-records draft (id => full record), edited live; committed on Save via
// the field REST endpoints. New/removed ids are tracked for the save sequence.
const fieldsDraft = reactive({});
const newFieldIds = reactive(new Set());
const deletedFieldIds = reactive(new Set());
const expanded = reactive({});

const runtime = computed(() => store.runtime || {});
const fieldCatalog = computed(() => store.fieldCatalog || []);
const fieldOptions = computed(() => fieldCatalog.value.map((field) => ({ value: field.value, label: field.label })));

// --- Theme draft (palette + globals + font), persisted to settings on Save ---

// themeDraft key -> settings key.
const THEME_KEYS = {
  primary: 'set_primary_color',
  primary_hover: 'set_primary_color_on_hover',
  secondary: 'set_secondary_color',
  success: 'set_success_color',
  warning: 'set_warning_color',
  danger: 'set_danger_color',
  info: 'set_info_color',
  radius: 'checkout_border_radius',
  font_size: 'checkout_base_font_size',
  field_height: 'checkout_field_height',
  bg: 'checkout_bg_color',
  text: 'checkout_text_color',
  font: 'set_font_family',
};

const themeDraft = reactive({});

function cloneTheme() {
  const settings = store.settings || {};

  Object.entries(THEME_KEYS).forEach(([key, settingKey]) => {
    themeDraft[key] = settings[settingKey] ?? '';
  });
}

// --- Texts draft (checkout strings), persisted to settings on Save ---

// Each entry binds a settings key to the React checkout i18n key it drives, so
// editing here updates both the live preview and the stored setting. Stepper
// labels (text_check_step_N) are edited as step labels, so they are not listed.
const TEXT_FIELDS = [
  { setting: 'text_header_step_1', i18n: 'contact_title', label: 'Título da etapa de contato' },
  { setting: 'text_header_step_2', i18n: 'shipping_address', label: 'Título da etapa de entrega' },
  { setting: 'text_header_step_3', i18n: 'payment_methods', label: 'Título da etapa de pagamento' },
  { setting: 'text_header_sidebar_right', i18n: 'cart', label: 'Título do resumo do pedido' },
  { setting: 'text_shipping_methods_label', i18n: 'shipping_methods', label: 'Rótulo de formas de entrega' },
  { setting: 'text_previous_step_button', i18n: 'back', label: 'Botão de voltar' },
];

const textsDraft = reactive({});

function cloneTexts() {
  const settings = store.settings || {};

  TEXT_FIELDS.forEach(({ setting }) => {
    textsDraft[setting] = settings[setting] ?? '';
  });
}

// Map the draft into the React i18n shape (i18n key => text) for the preview.
function buildTextsMessage() {
  const texts = {};

  TEXT_FIELDS.forEach(({ setting, i18n }) => {
    texts[i18n] = textsDraft[setting] ?? '';
  });

  return texts;
}

function pushTexts() {
  if (frameReady.value) {
    postToFrame({ type: 'fc-builder:texts', texts: buildTextsMessage() });
  }
}

// --- Checkout settings draft (operator-tunable, persisted to settings on Save) ---

// settings key (also the key read by the React checkout under settings.checkout).
const CHECKOUT_SETTING_DEFAULTS = {
  payment_methods_layout: 'cards',
};

const settingsDraft = reactive({});

function cloneSettings() {
  const settings = store.settings || {};

  Object.entries(CHECKOUT_SETTING_DEFAULTS).forEach(([key, fallbackValue]) => {
    settingsDraft[key] = settings[key] || fallbackValue;
  });
}

function buildSettingsMessage() {
  return { ...settingsDraft };
}

function pushSettings() {
  if (frameReady.value) {
    postToFrame({ type: 'fc-builder:settings', settings: buildSettingsMessage() });
  }
}

const availableFonts = computed(() => {
  const fonts = store.settings?.font_family || store.runtime?.fonts || {};

  return Object.entries(fonts).map(([id, cfg]) => ({ value: id, label: cfg?.font_name || id }));
});

// Build the @import/@font-face CSS + family name for the active font.
function fontPayload(fontId) {
  const fonts = store.settings?.font_family || store.runtime?.fonts || {};
  const cfg = fonts[fontId];

  if (!cfg) {
    return { family: '', css: '' };
  }

  const family = cfg.font_name || fontId;
  let css = '';

  if (cfg.font_url) {
    css = `@import url('${cfg.font_url}');`;
  } else if (Array.isArray(cfg.font_files) && cfg.font_files.length) {
    css = cfg.font_files
      .map((file) => {
        const url = file?.url || file?.file || file;

        return url ? `@font-face{font-family:'${family}';src:url('${url}');font-weight:${cfg.font_weight || 400};font-style:${cfg.font_style || 'normal'};font-display:swap;}` : '';
      })
      .join('');
  }

  return { family, css };
}

function buildThemeMessage() {
  return {
    colors: {
      primary: themeDraft.primary,
      primary_hover: themeDraft.primary_hover,
      secondary: themeDraft.secondary,
      success: themeDraft.success,
      warning: themeDraft.warning,
      danger: themeDraft.danger,
      info: themeDraft.info,
    },
    globals: {
      radius: themeDraft.radius,
      font_size: themeDraft.font_size,
      field_height: themeDraft.field_height,
      bg: themeDraft.bg,
      text: themeDraft.text,
    },
    font: fontPayload(themeDraft.font),
  };
}

function pushTheme() {
  if (frameReady.value) {
    postToFrame({ type: 'fc-builder:theme', theme: buildThemeMessage() });
  }
}

// --- Live preview iframe bridge ---

const previewFrame = ref(null);
const frameReady = ref(false);
let pushTimer = null;
let readyTimer = null;

// The preview iframe is same-site but may differ in scheme/host from the admin
// page (e.g. wc_get_checkout_url() forcing https). Derive the iframe origin so
// postMessage targets and origin checks stay correct.
const previewOrigin = computed(() => {
  const url = runtime.value.builder_preview_url || '';

  try {
    return new URL(url, window.location.href).origin;
  } catch (e) {
    return window.location.origin;
  }
});

function postToFrame(msg) {
  const win = previewFrame.value?.contentWindow;

  if (win) {
    win.postMessage(msg, previewOrigin.value);
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
    postToFrame({ type: 'fc-builder:layout', layout: buildPayload(), fields: buildFieldOverrides() });
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
  // Same-site but possibly different origin (scheme/host) than the admin page.
  if (event.origin !== previewOrigin.value && event.origin !== window.location.origin) {
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
    markReady();
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

function markReady() {
  if (readyTimer) {
    clearTimeout(readyTimer);
    readyTimer = null;
  }

  frameReady.value = true;
  pushLayout();
  pushSelection();
  pushTheme();
  pushTexts();
  pushSettings();
}

function onFrameLoad() {
  // Do NOT reset frameReady here: the iframe loads the full WP page (theme +
  // admin bar), so this `load` event fires AFTER React already emitted `ready`,
  // and resetting would leave the loading overlay stuck. The Save flow clears
  // frameReady explicitly before reloading. As a safety net, if the ready
  // handshake never arrives (e.g. blocked postMessage), clear the overlay.
  if (readyTimer) {
    clearTimeout(readyTimer);
  }

  readyTimer = setTimeout(() => {
    if (!frameReady.value) {
      markReady();
    }
  }, 4000);
}

onMounted(() => window.addEventListener('message', onFrameMessage));
onBeforeUnmount(() => {
  window.removeEventListener('message', onFrameMessage);

  if (pushTimer) {
    clearTimeout(pushTimer);
  }

  if (readyTimer) {
    clearTimeout(readyTimer);
  }

  if (themeTimer) {
    clearTimeout(themeTimer);
  }

  if (textsTimer) {
    clearTimeout(textsTimer);
  }

  if (settingsTimer) {
    clearTimeout(settingsTimer);
  }
});

watch(
  [() => draft, () => fieldsDraft],
  () => {
    if (pushTimer) {
      clearTimeout(pushTimer);
    }

    pushTimer = setTimeout(pushLayout, 250);
  },
  { deep: true },
);

let themeTimer = null;
watch(
  () => themeDraft,
  () => {
    if (themeTimer) {
      clearTimeout(themeTimer);
    }

    themeTimer = setTimeout(pushTheme, 250);
  },
  { deep: true },
);

let textsTimer = null;
watch(
  () => textsDraft,
  () => {
    if (textsTimer) {
      clearTimeout(textsTimer);
    }

    textsTimer = setTimeout(pushTexts, 250);
  },
  { deep: true },
);

let settingsTimer = null;
watch(
  () => settingsDraft,
  () => {
    if (settingsTimer) {
      clearTimeout(settingsTimer);
    }

    settingsTimer = setTimeout(pushSettings, 250);
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

  // Clone the full field records so the inspector can edit them live.
  Object.keys(fieldsDraft).forEach((id) => delete fieldsDraft[id]);
  Object.entries(store.fields || {}).forEach(([id, record]) => {
    fieldsDraft[id] = JSON.parse(JSON.stringify(record));
  });

  newFieldIds.clear();
  deletedFieldIds.clear();

  cloneTheme();
  cloneTexts();
  cloneSettings();

  // Expand every step by default in the layers tree.
  Object.keys(expanded).forEach((id) => delete expanded[id]);
  draft.steps.forEach((step) => {
    expanded[step.id] = true;
  });

  const first = draft.steps[0];
  selected.stepId = first ? first.id : '';
  selected.itemId = '';
  addMenuStepId.value = '';
}

/**
 * Normalize the field-records draft to the React checkout field shape, so the
 * iframe can render label/type/required/options edits live. Disabled fields are
 * included (the editor keeps them visible/selectable).
 */
function buildFieldOverrides() {
  return Object.entries(fieldsDraft).map(([id, f]) => ({
    id,
    type: f.type || 'text',
    label: f.label || '',
    step: String(f.step || '1'),
    position: f.position || 'full',
    priority: String(f.priority || '0'),
    required: f.required === 'yes',
    enabled: f.enabled !== 'no',
    input_mask: f.input_mask || '',
    options: Array.isArray(f.options) ? f.options : [],
  }));
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

// The full field record (from the draft) behind the selected field item.
const selectedFieldRecord = computed(() => {
  const item = selectedItem.value;

  return item && item.kind === 'field' ? (fieldsDraft[item.field_id] || null) : null;
});

const selectedFieldIsNative = computed(() => selectedFieldRecord.value?.source === 'native');
const selectedFieldIsCountry = computed(() => {
  const id = selectedItem.value?.field_id;

  return id === 'billing_country' || id === 'shipping_country';
});

const countryOptions = computed(() => store.runtime?.countries || []);
const newFieldOption = reactive({ value: '', text: '' });

function addFieldOption() {
  const record = selectedFieldRecord.value;

  if (!record || !newFieldOption.value) {
    return;
  }

  if (!Array.isArray(record.options)) {
    record.options = [];
  }

  record.options.push({ value: newFieldOption.value, text: newFieldOption.text });
  newFieldOption.value = '';
  newFieldOption.text = '';
}

function removeFieldOption(index) {
  selectedFieldRecord.value?.options?.splice(index, 1);
}

// Ensure a selected field item has a style object so the inspector can v-model it.
watch(selectedItem, (item) => {
  if (item && item.kind === 'field' && !item.style) {
    item.style = {};
  }
});

const themePanel = ref(false);
const textsPanel = ref(false);

function selectTheme() {
  themePanel.value = true;
  textsPanel.value = false;
  selected.stepId = '';
  selected.itemId = '';
}

function selectTexts() {
  textsPanel.value = true;
  themePanel.value = false;
  selected.stepId = '';
  selected.itemId = '';
}

function selectStep(step) {
  themePanel.value = false;
  textsPanel.value = false;
  selected.stepId = step.id;
  selected.itemId = '';
}

function selectItem(step, item) {
  themePanel.value = false;
  textsPanel.value = false;
  selected.stepId = step.id;
  selected.itemId = item.id;
}

function fieldLabel(fieldId) {
  // Prefer the live draft record so renamed labels show immediately in the tree.
  if (fieldsDraft[fieldId]?.label) {
    return fieldsDraft[fieldId].label;
  }

  const found = fieldCatalog.value.find((field) => field.value === fieldId);

  return found ? found.label : fieldId;
}

function toggleExpand(id) {
  expanded[id] = !expanded[id];
}

// Bucket a step type to the legacy field step ('1' contact, '2' delivery/payment).
function stepFieldBucket(step) {
  if (step.type === 'shipping' || step.type === 'payment') {
    return '2';
  }

  return '1';
}

/**
 * Create a brand-new custom field (billing_<slug>) in a step. The record lives
 * in the draft until Save; shows live via the override push.
 */
function createNewField(step) {
  addMenuStepId.value = '';

  const name = window.prompt('Nome do novo campo (ex.: Apelido):', '');

  if (!name) {
    return;
  }

  const slug = String(name)
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '_')
    .replace(/[^a-z0-9_]/g, '');

  const id = slug ? `billing_${slug}` : '';

  if (!id) {
    store.pushToast('error', 'Informe um nome válido para o campo.');

    return;
  }

  if (fieldsDraft[id] || (store.fields || {})[id]) {
    store.pushToast('error', 'Já existe um campo com esse nome.');

    return;
  }

  fieldsDraft[id] = {
    id,
    type: 'text',
    label: String(name).trim(),
    position: 'full',
    classes: '',
    label_classes: '',
    required: 'no',
    priority: '0',
    source: 'added',
    enabled: 'yes',
    step: stepFieldBucket(step),
    options: [],
    input_mask: '',
  };

  newFieldIds.add(id);

  const item = { id: genId('it'), kind: 'field', field_id: id, order: step.items.length };
  step.items.push(item);
  selectItem(step, item);
}

/**
 * Delete a field entirely (record + every placement across steps). Native Woo
 * fields can only be disabled, not removed.
 */
function deleteField(fieldId) {
  const record = fieldsDraft[fieldId];

  if (!record || record.source === 'native') {
    return;
  }

  if (!window.confirm(`Excluir o campo "${record.label || fieldId}"? Ele será removido de todas as etapas.`)) {
    return;
  }

  // Remove every layout item that references this field.
  draft.steps.forEach((step) => {
    step.items = (step.items || []).filter((item) => !(item.kind === 'field' && item.field_id === fieldId));
  });

  delete fieldsDraft[fieldId];

  if (newFieldIds.has(fieldId)) {
    newFieldIds.delete(fieldId);
  } else {
    deletedFieldIds.add(fieldId);
  }

  selected.itemId = '';
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

const committing = ref(false);
const saving = computed(() => store.savingLayout || committing.value);

// Field props persisted via the field endpoints (NOT step/priority, which the
// layout save owns through sync_fields_from_layout).
const FIELD_SAVE_PROPS = ['enabled', 'required', 'label', 'classes', 'label_classes', 'position', 'input_mask', 'type', 'country'];

function fieldRecordDiffers(id) {
  const draftRecord = fieldsDraft[id];
  const original = (store.fields || {})[id];

  if (!draftRecord || !original) {
    return false;
  }

  if (FIELD_SAVE_PROPS.some((prop) => (draftRecord[prop] ?? '') !== (original[prop] ?? ''))) {
    return true;
  }

  return JSON.stringify(draftRecord.options || null) !== JSON.stringify(original.options || null);
}

function fieldSavePayload(id) {
  const record = fieldsDraft[id];
  const payload = {};

  FIELD_SAVE_PROPS.forEach((prop) => {
    if (record[prop] !== undefined) {
      payload[prop] = record[prop];
    }
  });

  if (record.type === 'select' || record.type === 'checkbox') {
    payload.options = Array.isArray(record.options) ? record.options : [];
  }

  return payload;
}

async function save() {
  if (saving.value) {
    return;
  }

  committing.value = true;

  try {
    // 0. THEME + TEXTS: write the drafts into settings and persist them.
    let settingsChanged = false;

    Object.entries(THEME_KEYS).forEach(([key, settingKey]) => {
      if ((store.settings[settingKey] ?? '') !== (themeDraft[key] ?? '')) {
        store.settings[settingKey] = themeDraft[key];
        settingsChanged = true;
      }
    });

    TEXT_FIELDS.forEach(({ setting }) => {
      if ((store.settings[setting] ?? '') !== (textsDraft[setting] ?? '')) {
        store.settings[setting] = textsDraft[setting];
        settingsChanged = true;
      }
    });

    Object.keys(CHECKOUT_SETTING_DEFAULTS).forEach((key) => {
      if ((store.settings[key] ?? '') !== (settingsDraft[key] ?? '')) {
        store.settings[key] = settingsDraft[key];
        settingsChanged = true;
      }
    });

    if (settingsChanged) {
      const response = await store.save();

      if (response?.status === 'error') {
        committing.value = false;

        return;
      }
    }

    // 1. CREATE new fields (must precede edits — save_fields ignores unknown ids).
    for (const id of newFieldIds) {
      if (deletedFieldIds.has(id)) {
        continue;
      }

      const record = fieldsDraft[id];
      const response = await store.addField({
        id,
        type: record.type || 'text',
        label: record.label || '',
        required: record.required || 'no',
        position: record.position || 'full',
        classes: record.classes || '',
        label_classes: record.label_classes || '',
        input_mask: record.input_mask || '',
        step: record.step || '1',
        options: Array.isArray(record.options) ? record.options : undefined,
      });

      if (response?.status !== 'success') {
        committing.value = false;

        return;
      }
    }

    // 2. EDIT existing fields whose props changed.
    const updates = {};

    Object.keys(fieldsDraft).forEach((id) => {
      if (newFieldIds.has(id) || deletedFieldIds.has(id) || !(store.fields || {})[id]) {
        return;
      }

      if (fieldRecordDiffers(id)) {
        updates[id] = fieldSavePayload(id);
      }
    });

    if (Object.keys(updates).length) {
      const response = await store.saveFields(updates);

      if (response?.status !== 'success') {
        committing.value = false;

        return;
      }
    }

    // 3. DELETE removed (existing) fields.
    for (const id of deletedFieldIds) {
      if (newFieldIds.has(id)) {
        continue;
      }

      const response = await store.removeField(id);

      if (response?.status !== 'success') {
        committing.value = false;

        return;
      }
    }

    // 4. SAVE layout (sync_fields_from_layout rewrites step/priority last).
    const response = await store.saveLayout(buildPayload());

    if (response?.status === 'success') {
      cloneLayout();

      // Reload the preview to pull server-resolved data (order bump product,
      // resolved product reviews) into the live render.
      frameReady.value = false;
      previewFrame.value?.contentWindow?.location?.reload();
    }
  } finally {
    committing.value = false;
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
        <!-- Layers tree (steps -> elements) -->
        <aside class="flex flex-col overflow-y-auto border-r border-slate-200 bg-white">
          <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
            <p class="m-0 text-xs font-semibold uppercase tracking-wide text-muted">Estrutura</p>
            <button
              type="button"
              class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-primary-200 bg-white px-2 py-1 text-xs font-medium text-primary transition hover:bg-primary-50"
              @click="addStep"
            >
              <BoxIcon name="plus" class="h-3.5 w-3.5" />
              Etapa
            </button>
          </div>

          <!-- Theme entry -->
          <div class="px-2 pt-2">
            <button
              type="button"
              class="flex w-full cursor-pointer items-center gap-2 rounded-lg border px-2.5 py-2 text-left transition"
              :class="themePanel ? 'border-primary bg-primary-50/40' : 'border-slate-200 hover:bg-slate-50'"
              @click="selectTheme"
            >
              <BoxIcon name="palette" class="h-4 w-4 shrink-0 text-primary" />
              <span class="text-sm font-medium text-ink">Tema do checkout</span>
            </button>
          </div>

          <!-- Texts entry -->
          <div class="px-2 pt-1">
            <button
              type="button"
              class="flex w-full cursor-pointer items-center gap-2 rounded-lg border px-2.5 py-2 text-left transition"
              :class="textsPanel ? 'border-primary bg-primary-50/40' : 'border-slate-200 hover:bg-slate-50'"
              @click="selectTexts"
            >
              <BoxIcon name="text" class="h-4 w-4 shrink-0 text-primary" />
              <span class="text-sm font-medium text-ink">Textos do checkout</span>
            </button>
          </div>

          <ul class="m-0 flex list-none flex-col gap-1 p-2">
            <li v-for="(step, index) in draft.steps" :key="step.id">
              <!-- Step row -->
              <div
                class="group flex items-center gap-1 rounded-lg border px-1.5 py-1.5 transition"
                :class="[
                  selected.stepId === step.id && !selected.itemId ? 'border-primary bg-primary-50/40' : 'border-transparent hover:bg-slate-50',
                  step.enabled === false ? 'opacity-60' : '',
                ]"
              >
                <button
                  type="button"
                  class="cursor-pointer border-0 bg-transparent p-0.5 text-muted hover:text-ink"
                  :class="(step.items || []).length ? '' : 'invisible'"
                  :aria-label="expanded[step.id] ? 'Recolher' : 'Expandir'"
                  @click.stop="toggleExpand(step.id)"
                >
                  <BoxIcon :name="expanded[step.id] ? 'chevron-down' : 'chevron-right'" class="h-4 w-4" />
                </button>

                <button type="button" class="flex min-w-0 flex-1 cursor-pointer items-center gap-2 border-0 bg-transparent text-left" @click="selectStep(step)">
                  <BoxIcon :name="STEP_META[step.type].icon" class="h-4 w-4 shrink-0 text-slate-400" />
                  <span class="truncate text-sm font-medium text-ink">{{ step.label || STEP_META[step.type].label }}</span>
                </button>

                <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition group-hover:opacity-100" :class="selected.stepId === step.id ? 'opacity-100' : ''">
                  <ToggleSwitch v-model="step.enabled" :true-value="true" :false-value="false" size="sm" aria-label="Etapa ativa" />

                  <button type="button" class="cursor-pointer border-0 bg-transparent p-0.5 text-muted hover:text-ink disabled:opacity-30" :disabled="!canMoveStep(index, -1)" aria-label="Mover para cima" @click.stop="moveStep(index, -1)">
                    <BoxIcon name="chevron-up" class="h-4 w-4" />
                  </button>
                  <button type="button" class="cursor-pointer border-0 bg-transparent p-0.5 text-muted hover:text-ink disabled:opacity-30" :disabled="!canMoveStep(index, 1)" aria-label="Mover para baixo" @click.stop="moveStep(index, 1)">
                    <BoxIcon name="chevron-down" class="h-4 w-4" />
                  </button>

                  <!-- Add element menu -->
                  <div class="relative">
                    <button type="button" class="cursor-pointer border-0 bg-transparent p-0.5 text-primary hover:text-primary-700" aria-label="Adicionar elemento" @click.stop="toggleAddMenu(step)">
                      <BoxIcon name="plus" class="h-4 w-4" />
                    </button>
                    <div v-if="addMenuStepId === step.id" class="absolute right-0 z-20 mt-1 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                      <button type="button" class="flex w-full cursor-pointer items-center gap-2 border-0 bg-transparent px-3 py-2 text-left text-sm text-ink hover:bg-slate-50" @click.stop="createNewField(step)">
                        <BoxIcon name="plus" class="h-4 w-4 text-slate-400" />
                        Novo campo
                      </button>
                      <button type="button" class="flex w-full cursor-pointer items-center gap-2 border-0 bg-transparent px-3 py-2 text-left text-sm text-ink hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!fieldOptions.length" @click.stop="addFieldItem(step)">
                        <BoxIcon name="text" class="h-4 w-4 text-slate-400" />
                        Campo existente
                      </button>
                      <div class="my-1 border-t border-slate-100" />
                      <button v-for="component in ADD_COMPONENTS" :key="component" type="button" class="flex w-full cursor-pointer items-center gap-2 border-0 bg-transparent px-3 py-2 text-left text-sm text-ink hover:bg-slate-50" @click.stop="addComponentItem(step, component)">
                        <BoxIcon :name="COMPONENT_META[component].icon" class="h-4 w-4 text-slate-400" />
                        {{ COMPONENT_META[component].label }}
                      </button>
                    </div>
                  </div>

                  <button v-if="step.type === 'custom'" type="button" class="cursor-pointer border-0 bg-transparent p-0.5 text-danger/70 hover:text-danger" aria-label="Excluir etapa" @click.stop="removeStep(step)">
                    <BoxIcon name="trash" class="h-4 w-4" />
                  </button>
                </div>
              </div>

              <!-- Item children -->
              <ul v-if="expanded[step.id] && (step.items || []).length" class="m-0 ml-4 flex list-none flex-col gap-0.5 border-l border-slate-100 py-0.5 pl-2">
                <li
                  v-for="(item, itemIndex) in step.items"
                  :key="item.id"
                  class="group flex items-center gap-1 rounded-lg px-1.5 py-1 transition"
                  :class="selected.itemId === item.id ? 'bg-primary-50/60 text-primary' : 'hover:bg-slate-50'"
                >
                  <button type="button" class="flex min-w-0 flex-1 cursor-pointer items-center gap-2 border-0 bg-transparent text-left" @click="selectItem(step, item)">
                    <BoxIcon :name="itemMeta(item).icon" class="h-3.5 w-3.5 shrink-0" :class="selected.itemId === item.id ? 'text-primary' : 'text-slate-400'" />
                    <span class="truncate text-[13px] text-ink">{{ itemMeta(item).label }}</span>
                  </button>

                  <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition group-hover:opacity-100" :class="selected.itemId === item.id ? 'opacity-100' : ''">
                    <button type="button" class="cursor-pointer border-0 bg-transparent p-0.5 text-muted hover:text-ink disabled:opacity-30" :disabled="itemIndex === 0" aria-label="Mover para cima" @click.stop="moveItem(step, itemIndex, -1)">
                      <BoxIcon name="chevron-up" class="h-3.5 w-3.5" />
                    </button>
                    <button type="button" class="cursor-pointer border-0 bg-transparent p-0.5 text-muted hover:text-ink disabled:opacity-30" :disabled="itemIndex === step.items.length - 1" aria-label="Mover para baixo" @click.stop="moveItem(step, itemIndex, 1)">
                      <BoxIcon name="chevron-down" class="h-3.5 w-3.5" />
                    </button>
                    <button type="button" class="cursor-pointer border-0 bg-transparent p-0.5 text-danger/70 hover:text-danger" aria-label="Remover" @click.stop="removeItem(step, item)">
                      <BoxIcon name="trash" class="h-3.5 w-3.5" />
                    </button>
                  </div>
                </li>
              </ul>
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

            <!-- Quick add a new field to the selected step -->
            <button
              v-if="selectedStep"
              type="button"
              class="inline-flex cursor-pointer items-center gap-1 rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white transition hover:bg-primary-700"
              @click="createNewField(selectedStep)"
            >
              <BoxIcon name="plus" class="h-3.5 w-3.5" />
              Novo campo
            </button>
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
          <!-- Texts inspector -->
          <div v-if="textsPanel" class="flex flex-col gap-4">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
              <BoxIcon name="text" class="h-4 w-4 text-primary" />
              <p class="m-0 text-sm font-semibold text-ink">Textos do checkout</p>
            </div>

            <p class="m-0 text-xs text-muted">
              Edite os textos exibidos no checkout. As alterações são refletidas na pré-visualização e também atualizam as configurações do plugin ao salvar. Os rótulos do indicador de etapas são editados em cada etapa, no campo “Rótulo da etapa”.
            </p>

            <div v-for="field in TEXT_FIELDS" :key="field.setting">
              <label class="mb-1 block text-xs font-medium text-ink">{{ field.label }}</label>
              <input v-model="textsDraft[field.setting]" type="text" :class="inputClass" />
            </div>
          </div>

          <!-- Theme inspector -->
          <div v-else-if="themePanel" class="flex flex-col gap-5">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
              <BoxIcon name="palette" class="h-4 w-4 text-primary" />
              <p class="m-0 text-sm font-semibold text-ink">Tema do checkout</p>
            </div>

            <!-- Palette -->
            <div>
              <p class="m-0 mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted">Paleta de cores</p>
              <div class="flex flex-col gap-2">
                <div v-for="color in PALETTE_FIELDS" :key="color.key" class="flex items-center justify-between gap-2">
                  <span class="text-xs font-medium text-ink">{{ color.label }}</span>
                  <input v-model="themeDraft[color.key]" type="color" class="h-8 w-14 cursor-pointer rounded border border-slate-300" />
                </div>
              </div>
            </div>

            <!-- Typography -->
            <div>
              <p class="m-0 mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted">Tipografia</p>
              <label class="mb-1 block text-xs font-medium text-ink">Fonte ativa</label>
              <BaseSelect v-model="themeDraft.font" :options="availableFonts" size="sm" placeholder="Selecionar fonte" />
              <label class="mb-1 mt-3 block text-xs font-medium text-ink">Tamanho da fonte base (px)</label>
              <input v-model="themeDraft.font_size" type="number" min="12" max="22" :class="inputClass" />

              <details class="mt-3 rounded-lg border border-slate-200">
                <summary class="cursor-pointer px-3 py-2 text-xs font-medium text-ink">Gerenciar fontes (adicionar / enviar)</summary>
                <div class="border-t border-slate-100 p-3">
                  <FontsManager />
                </div>
              </details>
            </div>

            <!-- Global -->
            <div>
              <p class="m-0 mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted">Global</p>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Raio (px)</label>
                  <input v-model="themeDraft.radius" type="number" min="0" max="40" :class="inputClass" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Altura dos campos (px)</label>
                  <input v-model="themeDraft.field_height" type="number" min="36" max="72" :class="inputClass" />
                </div>
              </div>
              <div class="mt-3 flex items-center justify-between gap-2">
                <span class="text-xs font-medium text-ink">Cor de fundo</span>
                <input v-model="themeDraft.bg" type="color" class="h-8 w-14 cursor-pointer rounded border border-slate-300" />
              </div>
              <div class="mt-2 flex items-center justify-between gap-2">
                <span class="text-xs font-medium text-ink">Cor do texto</span>
                <input v-model="themeDraft.text" type="color" class="h-8 w-14 cursor-pointer rounded border border-slate-300" />
              </div>
            </div>
          </div>

          <!-- Item inspector -->
          <div v-else-if="selectedItem && selectedStep" class="flex flex-col gap-4">
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
            <template v-if="selectedItem.kind === 'field' && selectedFieldRecord && selectedItem.style">
              <!-- Field record (CRUD) -->
              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Rótulo</label>
                <input v-model="selectedFieldRecord.label" type="text" :class="inputClass" />
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Tipo</label>
                  <BaseSelect v-model="selectedFieldRecord.type" :options="FIELD_TYPES" size="sm" :disabled="selectedFieldIsNative" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Estado</label>
                  <BaseSelect v-model="selectedFieldRecord.enabled" :options="ENABLED_OPTIONS" size="sm" />
                </div>
              </div>

              <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-medium text-ink">Obrigatório</span>
                <ToggleSwitch v-model="selectedFieldRecord.required" true-value="yes" false-value="no" />
              </div>

              <div v-if="selectedFieldIsCountry">
                <label class="mb-1 block text-xs font-medium text-ink">País padrão</label>
                <BaseSelect v-model="selectedFieldRecord.country" :options="countryOptions" size="sm" placeholder="Selecionar país" />
              </div>

              <div>
                <label class="mb-1 block text-xs font-medium text-ink">Máscara (opcional)</label>
                <input v-model="selectedFieldRecord.input_mask" type="text" :class="inputClass" placeholder="(00) 00000-0000" />
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Classe CSS</label>
                  <input v-model="selectedFieldRecord.classes" type="text" :class="inputClass" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink">Classe do rótulo</label>
                  <input v-model="selectedFieldRecord.label_classes" type="text" :class="inputClass" />
                </div>
              </div>

              <!-- Options editor (select / checkbox) -->
              <div v-if="(selectedFieldRecord.type === 'select' || selectedFieldRecord.type === 'checkbox') && selectedItem.field_id !== 'billing_country'">
                <label class="mb-1 block text-xs font-medium text-ink">Opções</label>
                <ul class="m-0 mb-2 flex list-none flex-col gap-1.5 p-0">
                  <li v-for="(option, index) in selectedFieldRecord.options" :key="index" class="flex items-center gap-2">
                    <code class="rounded bg-slate-100 px-2 py-1 text-xs">{{ option.value }}</code>
                    <span class="flex-1 truncate text-sm text-ink">{{ option.text }}</span>
                    <button type="button" class="cursor-pointer rounded border border-danger/30 bg-transparent px-1.5 py-1 text-danger hover:bg-danger/10" aria-label="Remover opção" @click="removeFieldOption(index)">
                      <BoxIcon name="x" class="h-3.5 w-3.5" />
                    </button>
                  </li>
                </ul>
                <div class="flex items-center gap-2">
                  <input v-model="newFieldOption.value" type="text" placeholder="Valor" :class="inputClass" class="!w-24" />
                  <input v-model="newFieldOption.text" type="text" placeholder="Título" :class="inputClass" />
                  <button type="button" class="shrink-0 cursor-pointer rounded-lg border border-primary-200 bg-white px-2.5 py-2 text-xs font-medium text-primary hover:bg-primary-50" @click="addFieldOption">
                    Add
                  </button>
                </div>
              </div>

              <button
                v-if="!selectedFieldIsNative"
                type="button"
                class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-danger/30 bg-transparent px-3 py-2 text-xs font-medium text-danger transition hover:bg-danger/10"
                @click="deleteField(selectedItem.field_id)"
              >
                <BoxIcon name="trash" class="h-4 w-4" />
                Excluir campo
              </button>

              <div class="border-t border-slate-100 pt-3">
                <p class="m-0 mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted">Estilo</p>
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

            <div v-if="selectedStep.type === 'payment'" class="border-t border-slate-100 pt-4">
              <label class="mb-1 block text-xs font-medium text-ink">Exibição das formas de pagamento</label>
              <BaseSelect v-model="settingsDraft.payment_methods_layout" :options="PAYMENT_LAYOUT_OPTIONS" size="sm" />
              <p class="m-0 mt-1.5 text-[11px] text-muted">
                Escolha entre exibir as formas de pagamento em cards ou em modo sanfona. Cada forma usa o ícone fornecido pela sua integração.
              </p>
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
