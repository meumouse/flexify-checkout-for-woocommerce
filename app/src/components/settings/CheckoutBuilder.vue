<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';
import BaseSelect from '../fields/BaseSelect.vue';
import ToggleSwitch from '../toggles/ToggleSwitch.vue';
import SearchMultiSelect from '../fields/SearchMultiSelect.vue';
import MediaPickerField from '../fields/MediaPickerField.vue';
import FontsManager from './FontsManager.vue';
import BuilderLayersTree from './builder/BuilderLayersTree.vue';
import BuilderRulesPanel from './builder/BuilderRulesPanel.vue';
import BuilderField from './builder/BuilderField.vue';
import BuilderTextInput from './builder/BuilderTextInput.vue';
import BuilderTextArea from './builder/BuilderTextArea.vue';
import BuilderColor from './builder/BuilderColor.vue';
import BuilderRange from './builder/BuilderRange.vue';
import BuilderSegmented from './builder/BuilderSegmented.vue';
import { loadRule, buildRulePayload, newRule, ruleValid } from './builder/conditionsSchema.js';

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

const REVIEW_SOURCES = [
  { value: 'product', label: 'Avaliações do produto' },
  { value: 'manual', label: 'Depoimentos manuais' },
];

const REVIEW_LAYOUTS = [
  { value: 'list', label: 'Lista' },
  { value: 'carousel', label: 'Carrossel' },
];

const PAYMENT_LAYOUT_OPTIONS = [
  { value: 'accordion', label: 'Sanfona' },
  { value: 'cards', label: 'Cards' },
];

const HTML_VARIANTS = [
  { value: 'raw', label: 'HTML puro' },
  { value: 'banner', label: 'Banner' },
  { value: 'badges', label: 'Selos' },
  { value: 'divider', label: 'Divisor' },
];

const ALIGN_OPTIONS = [
  { value: 'left', label: 'Esq.' },
  { value: 'center', label: 'Centro' },
  { value: 'right', label: 'Dir.' },
];

const WIDTH_OPTIONS = [
  { value: 'left', label: 'Esquerda' },
  { value: 'right', label: 'Direita' },
  { value: 'full', label: 'Inteira' },
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

const DEVICE_OPTIONS = [
  { value: 'desktop', label: 'Desktop' },
  { value: 'mobile', label: 'Mobile' },
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

// --- Draft state ---

const draft = reactive({ version: 1, steps: [] });
const selected = reactive({ stepId: '', itemId: '' });
const addMenuStepId = ref('');
const activeTab = ref('inspector');
const device = ref('desktop');
const savedAt = ref(null);

// Field-records draft (id => full record), edited live; committed on Save via
// the field REST endpoints. New/removed ids are tracked for the save sequence.
const fieldsDraft = reactive({});
const newFieldIds = reactive(new Set());
const deletedFieldIds = reactive(new Set());
const expanded = reactive({});

// Conditions draft (editable rule form shape) + removed ids + baseline map.
const conditionsDraft = ref([]);
const deletedConditionIds = reactive(new Set());
let conditionsBaseline = {};

const runtime = computed(() => store.runtime || {});
const fieldCatalog = computed(() => store.fieldCatalog || []);
const fieldOptions = computed(() => fieldCatalog.value.map((field) => ({ value: field.value, label: field.label })));

const placedFieldIds = computed(() => {
  const set = new Set();

  draft.steps.forEach((step) => (step.items || []).forEach((item) => {
    if (item.kind === 'field') {
      set.add(item.field_id);
    }
  }));

  return set;
});

const availableFields = computed(() => fieldOptions.value.filter((option) => !placedFieldIds.value.has(option.value)));

// Field ids currently targeted by an enabled show/hide rule (tree markers).
const ruleTargets = computed(() => {
  const out = {};

  conditionsDraft.value.forEach((rule) => {
    if (rule.enabled !== false && rule.action && rule.action.type !== 'discount' && rule.action.component === 'field' && rule.action.field) {
      out[rule.action.field] = true;
    }
  });

  return out;
});

const activeDiscountRules = computed(
  () => conditionsDraft.value.filter((rule) => rule.enabled !== false && rule.action?.type === 'discount' && ruleValid(rule)).length,
);

// Condition editor option lists.
const conditionFieldOptions = computed(() =>
  Object.entries(fieldsDraft)
    .filter(([id]) => id.startsWith('billing_'))
    .map(([id, record]) => ({ value: id, label: record?.label || id })),
);
const shippingMethods = computed(() => runtime.value.shipping_methods || []);
const paymentGateways = computed(() => runtime.value.payment_gateways || []);
const userRoles = computed(() => runtime.value.user_roles || []);
const shippingZones = computed(() => runtime.value.shipping_zones || []);
const currencySymbol = computed(() => runtime.value.currency_symbol || 'R$');

// --- Theme draft (palette + globals + font), persisted to settings on Save ---

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

const CHECKOUT_SETTING_DEFAULTS = {
  payment_methods_layout: 'accordion',
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

// --- Live conditions push ---

function buildConditionsMessage() {
  return conditionsDraft.value.filter((rule) => ruleValid(rule)).map((rule) => buildRulePayload(rule));
}

function pushConditions() {
  if (frameReady.value) {
    postToFrame({ type: 'fc-builder:conditions', conditions: buildConditionsMessage() });
  }
}

// --- Live preview iframe bridge ---

const previewFrame = ref(null);
const frameReady = ref(false);
let pushTimer = null;
let readyTimer = null;

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
    win.postMessage(JSON.parse(JSON.stringify(msg)), previewOrigin.value);
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

    if (selected.stepId !== stepId || selected.itemId !== itemId) {
      selected.stepId = stepId;
      selected.itemId = itemId;
      activeTab.value = 'inspector';
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
  pushConditions();
}

function onFrameLoad() {
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

  [pushTimer, readyTimer, themeTimer, textsTimer, settingsTimer, conditionsTimer].forEach((timer) => {
    if (timer) {
      clearTimeout(timer);
    }
  });
});

watch(
  [draft, fieldsDraft],
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
  themeDraft,
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
  textsDraft,
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
  settingsDraft,
  () => {
    if (settingsTimer) {
      clearTimeout(settingsTimer);
    }

    settingsTimer = setTimeout(pushSettings, 250);
  },
  { deep: true },
);

let conditionsTimer = null;
watch(
  conditionsDraft,
  () => {
    if (conditionsTimer) {
      clearTimeout(conditionsTimer);
    }

    conditionsTimer = setTimeout(pushConditions, 250);
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

// --- Dirty tracking ---

const baseSnapshot = ref('');

function snapshot() {
  return JSON.stringify({
    layout: buildPayload(),
    fields: fieldsDraft,
    newFields: [...newFieldIds],
    deletedFields: [...deletedFieldIds],
    theme: themeDraft,
    texts: textsDraft,
    settings: settingsDraft,
    conditions: conditionsDraft.value.map((rule) => ({ id: rule.id, payload: buildRulePayload(rule) })),
    deletedConditions: [...deletedConditionIds],
  });
}

const dirty = computed(() => snapshot() !== baseSnapshot.value);

const saveLabel = computed(() => {
  if (saving.value) {
    return 'Salvando…';
  }

  if (dirty.value) {
    return 'Alterações não salvas';
  }

  if (savedAt.value) {
    const time = savedAt.value.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

    return `Salvo às ${time}`;
  }

  return 'Tudo salvo';
});

function cloneLayout() {
  const source = store.layout && Array.isArray(store.layout.steps) ? store.layout : { version: 1, steps: [] };
  const copy = JSON.parse(JSON.stringify(source));

  draft.version = copy.version || 1;
  draft.steps = Array.isArray(copy.steps) ? copy.steps : [];

  // Default component items to enabled so the "Bloco visível" toggle reflects
  // reality for layouts saved before the flag existed.
  draft.steps.forEach((step) => (step.items || []).forEach((item) => {
    if (item.kind === 'component' && item.enabled === undefined) {
      item.enabled = true;
    }
  }));

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
  cloneConditions();

  // Expand every step by default in the layers tree.
  Object.keys(expanded).forEach((id) => delete expanded[id]);
  draft.steps.forEach((step) => {
    expanded[step.id] = true;
  });

  const first = draft.steps[0];
  selected.stepId = first ? first.id : '';
  selected.itemId = '';
  addMenuStepId.value = '';
  activeTab.value = 'inspector';

  baseSnapshot.value = snapshot();
}

function cloneConditions() {
  conditionsDraft.value = (store.conditions || []).map(loadRule);
  deletedConditionIds.clear();
  conditionsBaseline = {};

  conditionsDraft.value.forEach((rule) => {
    if (rule.id) {
      conditionsBaseline[rule.id] = JSON.stringify(buildRulePayload(rule));
    }
  });
}

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

// --- Inspector header helpers ---

function itemMeta(item) {
  if (!item) {
    return { icon: 'slider-alt', label: '' };
  }

  if (item.kind === 'field') {
    return { icon: 'text', label: fieldLabel(item.field_id) };
  }

  return COMPONENT_META[item.component] || { icon: 'layout', label: item.component };
}

const inspectorHeader = computed(() => {
  if (selectedItem.value) {
    const meta = itemMeta(selectedItem.value);

    return { icon: meta.icon, title: meta.label, sub: selectedItem.value.kind === 'field' ? selectedItem.value.field_id : selectedItem.value.component };
  }

  if (selectedStep.value) {
    return {
      icon: STEP_META[selectedStep.value.type].icon,
      title: selectedStep.value.label || STEP_META[selectedStep.value.type].label,
      sub: selectedStep.value.type,
    };
  }

  return null;
});

// --- Tree event handlers ---

function onTreeSelect({ stepId, itemId }) {
  selected.stepId = stepId;
  selected.itemId = itemId || '';
  activeTab.value = 'inspector';
}

function toggleExpand(id) {
  expanded[id] = !expanded[id];
}

function onToggleStep(step) {
  step.enabled = step.enabled === false;
}

function onToggleItem({ item }) {
  if (item.kind === 'field') {
    const record = fieldsDraft[item.field_id];

    if (record) {
      record.enabled = record.enabled === 'no' ? 'yes' : 'no';
    }

    return;
  }

  item.enabled = !(item.enabled !== false);
}

function fieldLabel(fieldId) {
  if (fieldsDraft[fieldId]?.label) {
    return fieldsDraft[fieldId].label;
  }

  const found = fieldCatalog.value.find((field) => field.value === fieldId);

  return found ? found.label : fieldId;
}

function toggleAddMenu(step) {
  addMenuStepId.value = addMenuStepId.value === step.id ? '' : step.id;
}

function stepFieldBucket(step) {
  if (step.type === 'shipping' || step.type === 'payment') {
    return '2';
  }

  return '1';
}

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
  onTreeSelect({ stepId: step.id, itemId: item.id });
}

function deleteField(fieldId) {
  const record = fieldsDraft[fieldId];

  if (!record || record.source === 'native') {
    return;
  }

  if (!window.confirm(`Excluir o campo "${record.label || fieldId}"? Ele será removido de todas as etapas.`)) {
    return;
  }

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
  expanded[step.id] = true;
  onTreeSelect({ stepId: step.id });
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

// Payment always stays last; nothing may sit below it.
function enforcePaymentLast() {
  const nonPayment = draft.steps.filter((step) => step.type !== 'payment');
  const payment = draft.steps.filter((step) => step.type === 'payment');

  draft.steps = nonPayment.concat(payment);
}

function reorderStep({ fromId, toId }) {
  const fromIndex = draft.steps.findIndex((step) => step.id === fromId);

  if (fromIndex === -1) {
    return;
  }

  const [moved] = draft.steps.splice(fromIndex, 1);
  const toIndex = draft.steps.findIndex((step) => step.id === toId);
  const insertAt = toIndex === -1 ? draft.steps.length : toIndex;

  draft.steps.splice(insertAt, 0, moved);
  enforcePaymentLast();
}

function reorderItem({ fromStepId, itemId, toStepId, toIndex }) {
  const from = draft.steps.find((step) => step.id === fromStepId);
  const to = draft.steps.find((step) => step.id === toStepId);

  if (!from || !to) {
    return;
  }

  const idx = from.items.findIndex((item) => item.id === itemId);

  if (idx === -1) {
    return;
  }

  const [moved] = from.items.splice(idx, 1);
  let at = Number(toIndex);

  if (Number.isNaN(at)) {
    at = to.items.length;
  }

  if (from === to && idx < at) {
    at -= 1;
  }

  at = Math.max(0, Math.min(at, to.items.length));
  to.items.splice(at, 0, moved);
  onTreeSelect({ stepId: toStepId, itemId: moved.id });
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

function addReviewItem(item) {
  if (!Array.isArray(item.config.items)) {
    item.config.items = [];
  }

  item.config.items.push({ author: '', text: '', rating: 5, avatar: '' });
}

function removeReviewItem(item, index) {
  item.config.items.splice(index, 1);
}

function addExistingField({ step, fieldId }) {
  const item = { id: genId('it'), kind: 'field', field_id: fieldId, order: step.items.length };

  step.items.push(item);
  addMenuStepId.value = '';
  onTreeSelect({ stepId: step.id, itemId: item.id });
}

function addComponentItem({ step, component }) {
  const item = {
    id: genId('it'),
    kind: 'component',
    component,
    enabled: true,
    config: defaultComponentConfig(component),
    order: step.items.length,
  };

  step.items.push(item);
  addMenuStepId.value = '';
  onTreeSelect({ stepId: step.id, itemId: item.id });
}

function removeItem({ step, item }) {
  const index = step.items.findIndex((current) => current.id === item.id);

  if (index !== -1) {
    step.items.splice(index, 1);
  }

  if (selected.itemId === item.id) {
    selected.itemId = '';
  }
}

// --- Order bump / reviews product picker ---

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

// --- Conditions panel events ---

function addRule() {
  conditionsDraft.value.push(newRule());
  activeTab.value = 'rules';
}

function deleteRule(rule) {
  if (rule.id) {
    deletedConditionIds.add(rule.id);
  }

  conditionsDraft.value = conditionsDraft.value.filter((current) => current._localId !== rule._localId);
}

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
          enabled: item.enabled !== false,
          order: itemIndex,
        };
      }),
    })),
  };
}

const committing = ref(false);
const saving = computed(() => store.savingLayout || committing.value);

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

async function saveConditions() {
  for (const rule of conditionsDraft.value) {
    if (!ruleValid(rule)) {
      continue;
    }

    const payload = buildRulePayload(rule);

    if (rule.id) {
      if (conditionsBaseline[rule.id] === JSON.stringify(payload)) {
        continue;
      }

      const response = await store.saveCondition(payload, rule.id);

      if (response?.status !== 'success') {
        return false;
      }
    } else {
      const response = await store.saveCondition(payload);

      if (response?.status !== 'success') {
        return false;
      }
    }
  }

  for (const id of deletedConditionIds) {
    const response = await store.removeCondition(id);

    if (response?.status !== 'success') {
      return false;
    }
  }

  return true;
}

async function save() {
  if (saving.value) {
    return;
  }

  committing.value = true;

  try {
    // 0. THEME + TEXTS + SETTINGS: write the drafts into settings and persist.
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

    // 4. CONDITIONS: upsert changed/new rules, delete removed ones.
    if (!(await saveConditions())) {
      committing.value = false;

      return;
    }

    // 5. SAVE layout (sync_fields_from_layout rewrites step/priority last).
    const response = await store.saveLayout(buildPayload());

    if (response?.status === 'success') {
      savedAt.value = new Date();
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

function discard() {
  if (!dirty.value) {
    return;
  }

  cloneLayout();
}
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[99999] flex flex-col bg-slate-50 text-[13px] text-ink">
      <!-- Header -->
      <header class="flex h-[52px] shrink-0 items-center gap-3.5 border-b border-slate-200 bg-white px-4">
        <div class="flex items-center gap-2.5">
          <span class="flex h-6 w-6 items-center justify-center rounded-[7px] bg-primary text-white">
            <BoxIcon name="layout" class="h-[15px] w-[15px]" />
          </span>
          <span class="text-[13.5px] font-semibold tracking-tight text-ink">Construtor de Checkout</span>
          <span
            v-if="store.isPro"
            class="rounded-md border border-primary-200 bg-primary-50 px-1.5 py-0.5 text-[9.5px] font-semibold uppercase tracking-wider text-primary"
          >Pro</span>
        </div>

        <div class="flex-1"></div>

        <span class="text-[11.5px]" :class="dirty ? 'text-primary' : 'text-slate-400'">{{ saveLabel }}</span>

        <BaseButton variant="secondary" size="sm" @click="emit('close')">Voltar</BaseButton>
        <BaseButton variant="secondary" size="sm" :disabled="!dirty || saving" @click="discard">Descartar</BaseButton>
        <BaseButton size="sm" :loading="saving" :disabled="!dirty" @click="save">Salvar alterações</BaseButton>
      </header>

      <!-- Body: 3 regions -->
      <div class="flex min-h-0 flex-1">
        <!-- Layers tree -->
        <BuilderLayersTree
          :steps="draft.steps"
          :selected="selected"
          :expanded="expanded"
          :add-menu-step-id="addMenuStepId"
          :step-meta="STEP_META"
          :component-meta="COMPONENT_META"
          :add-components="ADD_COMPONENTS"
          :available-fields="availableFields"
          :field-label="fieldLabel"
          :field-records="fieldsDraft"
          :rule-targets="ruleTargets"
          @select="onTreeSelect"
          @toggle-expand="toggleExpand"
          @toggle-step="onToggleStep"
          @toggle-item="onToggleItem"
          @delete-step="removeStep"
          @delete-item="removeItem"
          @add-step="addStep"
          @open-add="toggleAddMenu"
          @add-component="addComponentItem"
          @add-field="addExistingField"
          @new-field="createNewField"
          @reorder-step="reorderStep"
          @reorder-item="reorderItem"
        />

        <!-- Live preview -->
        <main class="flex min-w-0 flex-1 flex-col overflow-hidden bg-slate-100">
          <div class="flex h-10 shrink-0 items-center gap-2.5 border-b border-slate-200 bg-white px-4">
            <span class="text-[10.5px] font-semibold uppercase tracking-[0.08em] text-slate-400">Preview</span>

            <BuilderSegmented v-model="device" :options="DEVICE_OPTIONS" class="w-40" />

            <div class="flex-1"></div>

            <span
              v-if="activeDiscountRules"
              class="flex items-center gap-1.5 rounded-md border border-violet-200 bg-violet-50 px-2 py-0.5 text-[11px] text-violet-600"
            >
              <BoxIcon name="filter-alt" class="h-2.5 w-2.5" />
              {{ activeDiscountRules }} regra(s) de desconto
            </span>

            <span class="font-mono text-[11px] text-slate-400">{{ device === 'mobile' ? '390 px' : 'auto' }}</span>
          </div>

          <div class="relative flex-1 overflow-auto p-4">
            <div
              class="mx-auto h-full transition-all"
              :class="device === 'mobile' ? 'w-[390px]' : 'w-full'"
            >
              <iframe
                v-if="runtime.builder_preview_url"
                ref="previewFrame"
                :src="runtime.builder_preview_url"
                class="h-full w-full rounded-xl border border-slate-200 bg-white shadow-sm"
                title="Pré-visualização do checkout"
                @load="onFrameLoad"
              />

              <div
                v-else
                class="flex h-full items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-6 text-center text-sm text-muted"
              >
                A pré-visualização ao vivo requer o WooCommerce ativo com uma página de checkout.
              </div>
            </div>

            <div
              v-if="runtime.builder_preview_url && !frameReady"
              class="pointer-events-none absolute inset-0 flex items-center justify-center text-sm text-muted"
            >
              Carregando pré-visualização…
            </div>
          </div>
        </main>

        <!-- Inspector -->
        <aside class="flex w-[334px] shrink-0 flex-col overflow-hidden border-l border-slate-200 bg-white">
          <!-- Tabs -->
          <div class="flex gap-0.5 border-b border-slate-200 px-2 pt-2">
            <button
              v-for="tab in [
                { id: 'inspector', icon: 'slider-alt', label: 'Inspetor' },
                { id: 'theme', icon: 'palette', label: 'Tema' },
                { id: 'rules', icon: 'filter-alt', label: 'Condições' },
              ]"
              :key="tab.id"
              type="button"
              class="-mb-px flex flex-1 cursor-pointer items-center justify-center gap-1.5 border-0 border-b-2 bg-transparent px-1 py-2 text-[12px] transition"
              :class="activeTab === tab.id ? 'border-primary font-semibold text-ink' : 'border-transparent font-medium text-slate-400 hover:text-ink'"
              @click="activeTab = tab.id"
            >
              <BoxIcon :name="tab.icon" class="h-3.5 w-3.5" />
              {{ tab.label }}
            </button>
          </div>

          <div class="flex-1 overflow-y-auto">
            <!-- Conditions tab -->
            <BuilderRulesPanel
              v-if="activeTab === 'rules'"
              :rules="conditionsDraft"
              :field-options="conditionFieldOptions"
              :shipping-methods="shippingMethods"
              :payment-gateways="paymentGateways"
              :countries="countryOptions"
              :user-roles="userRoles"
              :shipping-zones="shippingZones"
              :currency-symbol="currencySymbol"
              @add-rule="addRule"
              @delete-rule="deleteRule"
            />

            <!-- Theme tab -->
            <div v-else-if="activeTab === 'theme'" class="flex flex-col">
              <div class="flex items-center gap-2.5 border-b border-slate-100 px-3.5 py-3">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary-50 text-primary">
                  <BoxIcon name="palette" class="h-3.5 w-3.5" />
                </span>
                <span class="text-[13.5px] font-semibold text-ink">Tema e textos</span>
              </div>

              <!-- Palette -->
              <section class="flex flex-col gap-3 border-b border-slate-100 px-3.5 py-3.5">
                <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400">Paleta</span>
                <BuilderField v-for="color in PALETTE_FIELDS" :key="color.key" :label="color.label">
                  <BuilderColor v-model="themeDraft[color.key]" />
                </BuilderField>
              </section>

              <!-- Appearance -->
              <section class="flex flex-col gap-3 border-b border-slate-100 px-3.5 py-3.5">
                <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400">Aparência</span>
                <BuilderField label="Fundo do checkout">
                  <BuilderColor v-model="themeDraft.bg" />
                </BuilderField>
                <BuilderField label="Cor do texto">
                  <BuilderColor v-model="themeDraft.text" />
                </BuilderField>
                <BuilderField label="Fonte">
                  <BaseSelect v-model="themeDraft.font" :options="availableFonts" size="sm" placeholder="Selecionar fonte" />
                </BuilderField>
                <BuilderField label="Raio das bordas" :suffix="`${themeDraft.radius || 0}px`">
                  <BuilderRange v-model="themeDraft.radius" :min="0" :max="40" />
                </BuilderField>
                <BuilderField label="Tamanho base" :suffix="`${themeDraft.font_size || 0}px`">
                  <BuilderRange v-model="themeDraft.font_size" :min="12" :max="22" />
                </BuilderField>
                <BuilderField label="Altura dos campos" :suffix="`${themeDraft.field_height || 0}px`">
                  <BuilderRange v-model="themeDraft.field_height" :min="34" :max="72" />
                </BuilderField>

                <details class="rounded-lg border border-slate-200">
                  <summary class="cursor-pointer px-3 py-2 text-[11.5px] font-medium text-ink">Gerenciar fontes (adicionar / enviar)</summary>
                  <div class="border-t border-slate-100 p-3">
                    <FontsManager />
                  </div>
                </details>
              </section>

              <!-- Texts -->
              <section class="flex flex-col gap-3 border-b border-slate-100 px-3.5 py-3.5">
                <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400">Textos</span>
                <BuilderField v-for="field in TEXT_FIELDS" :key="field.setting" :label="field.label">
                  <BuilderTextInput v-model="textsDraft[field.setting]" />
                </BuilderField>
              </section>

              <!-- Settings -->
              <section class="flex flex-col gap-3 px-3.5 py-3.5">
                <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400">Configurações</span>
                <BuilderField label="Formas de pagamento" hint="Exiba as formas de pagamento em sanfona ou em cards.">
                  <BuilderSegmented v-model="settingsDraft.payment_methods_layout" :options="PAYMENT_LAYOUT_OPTIONS" />
                </BuilderField>
              </section>
            </div>

            <!-- Inspector tab -->
            <div v-else>
              <!-- Contextual header -->
              <div v-if="inspectorHeader" class="flex items-center gap-2.5 border-b border-slate-100 px-3.5 py-3">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary-50 text-primary">
                  <BoxIcon :name="inspectorHeader.icon" class="h-3.5 w-3.5" />
                </span>
                <span class="flex min-w-0 flex-col">
                  <span class="truncate text-[13.5px] font-semibold text-ink">{{ inspectorHeader.title }}</span>
                  <span class="truncate font-mono text-[10.5px] text-slate-400">{{ inspectorHeader.sub }}</span>
                </span>
              </div>

              <!-- Field item -->
              <div
                v-if="selectedItem && selectedItem.kind === 'field' && selectedFieldRecord && selectedItem.style"
                class="flex flex-col"
              >
                <section class="flex flex-col gap-3 border-b border-slate-100 px-3.5 py-3.5">
                  <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400">Campo</span>

                  <label class="flex items-center gap-2.5 text-[12.5px] text-ink">
                    <ToggleSwitch v-model="selectedFieldRecord.enabled" true-value="yes" false-value="no" size="sm" />
                    Campo ativo
                  </label>
                  <label class="flex items-center gap-2.5 text-[12.5px] text-ink">
                    <ToggleSwitch v-model="selectedFieldRecord.required" true-value="yes" false-value="no" size="sm" />
                    Obrigatório
                  </label>

                  <BuilderField label="Rótulo">
                    <BuilderTextInput v-model="selectedFieldRecord.label" />
                  </BuilderField>
                  <BuilderField label="Tipo">
                    <BaseSelect v-model="selectedFieldRecord.type" :options="FIELD_TYPES" size="sm" :disabled="selectedFieldIsNative" />
                  </BuilderField>
                  <BuilderField v-if="selectedFieldIsCountry" label="País padrão">
                    <BaseSelect v-model="selectedFieldRecord.country" :options="countryOptions" size="sm" placeholder="Selecionar país" />
                  </BuilderField>
                  <BuilderField label="Máscara" hint="Ex.: (00) 00000-0000">
                    <BuilderTextInput v-model="selectedFieldRecord.input_mask" placeholder="000.000.000-00" />
                  </BuilderField>
                  <BuilderField label="Classe CSS">
                    <BuilderTextInput v-model="selectedFieldRecord.classes" placeholder="ex.: col-destaque" />
                  </BuilderField>
                  <BuilderField label="Classe do rótulo">
                    <BuilderTextInput v-model="selectedFieldRecord.label_classes" />
                  </BuilderField>
                </section>

                <!-- Options editor -->
                <section
                  v-if="(selectedFieldRecord.type === 'select' || selectedFieldRecord.type === 'checkbox') && selectedItem.field_id !== 'billing_country'"
                  class="flex flex-col gap-2 border-b border-slate-100 px-3.5 py-3.5"
                >
                  <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400">Opções</span>
                  <div
                    v-for="(option, index) in selectedFieldRecord.options"
                    :key="index"
                    class="flex items-center gap-2"
                  >
                    <code class="rounded bg-slate-100 px-1.5 py-1 font-mono text-[11px]">{{ option.value }}</code>
                    <span class="flex-1 truncate text-[12.5px] text-ink">{{ option.text }}</span>
                    <button
                      type="button"
                      class="flex cursor-pointer rounded border-0 bg-transparent p-1 text-slate-400 transition hover:bg-danger/10 hover:text-danger"
                      @click="removeFieldOption(index)"
                    >
                      <BoxIcon name="x" class="h-3 w-3" />
                    </button>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <div class="w-20"><BuilderTextInput v-model="newFieldOption.value" placeholder="valor" /></div>
                    <BuilderTextInput v-model="newFieldOption.text" placeholder="título" />
                    <button
                      type="button"
                      class="shrink-0 cursor-pointer rounded-lg border border-primary-200 bg-white px-2 py-2 text-[11.5px] font-medium text-primary transition hover:bg-primary-50"
                      @click="addFieldOption"
                    >
                      Add
                    </button>
                  </div>
                </section>

                <!-- Style -->
                <section class="flex flex-col gap-3 border-b border-slate-100 px-3.5 py-3.5">
                  <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400">Estilo por posicionamento</span>
                  <BuilderField label="Largura">
                    <BuilderSegmented v-model="selectedItem.style.width" :options="WIDTH_OPTIONS" />
                  </BuilderField>
                  <BuilderField label="Ícone">
                    <BaseSelect v-model="selectedItem.style.icon" :options="FIELD_ICON_OPTIONS" size="sm" />
                  </BuilderField>
                  <BuilderField label="Placeholder">
                    <BuilderTextInput v-model="selectedItem.style.placeholder" placeholder="Texto de exemplo" />
                  </BuilderField>

                  <div class="grid grid-cols-2 gap-2">
                    <BuilderField label="Cor do rótulo">
                      <BuilderColor v-model="selectedItem.style.label_color" fallback="#57534e" />
                    </BuilderField>
                    <BuilderField label="Tam. rótulo" :suffix="`${selectedItem.style.label_size || 13}px`">
                      <BuilderRange v-model="selectedItem.style.label_size" :min="8" :max="40" />
                    </BuilderField>
                  </div>
                  <BuilderField label="Peso do rótulo" :suffix="String(selectedItem.style.label_weight || 500)">
                    <BuilderRange v-model="selectedItem.style.label_weight" :min="100" :max="900" :step="100" />
                  </BuilderField>

                  <div class="grid grid-cols-2 gap-2">
                    <BuilderField label="Fundo do campo">
                      <BuilderColor v-model="selectedItem.style.input_bg" fallback="#ffffff" />
                    </BuilderField>
                    <BuilderField label="Borda">
                      <BuilderColor v-model="selectedItem.style.input_border" fallback="#e2e0dd" />
                    </BuilderField>
                  </div>
                  <BuilderField label="Cor do texto">
                    <BuilderColor v-model="selectedItem.style.input_color" fallback="#1c1917" />
                  </BuilderField>
                  <div class="grid grid-cols-2 gap-2">
                    <BuilderField label="Raio do campo" :suffix="`${selectedItem.style.input_radius || 0}px`">
                      <BuilderRange v-model="selectedItem.style.input_radius" :min="0" :max="40" />
                    </BuilderField>
                    <BuilderField label="Margem abaixo" :suffix="`${selectedItem.style.margin_bottom || 0}px`">
                      <BuilderRange v-model="selectedItem.style.margin_bottom" :min="0" :max="80" />
                    </BuilderField>
                  </div>
                </section>

                <section v-if="!selectedFieldIsNative" class="px-3.5 py-3.5">
                  <button
                    type="button"
                    class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-danger/30 bg-transparent px-3 py-2 text-[12px] font-medium text-danger transition hover:bg-danger/10"
                    @click="deleteField(selectedItem.field_id)"
                  >
                    <BoxIcon name="trash" class="h-3.5 w-3.5" />
                    Excluir campo
                  </button>
                </section>
              </div>

              <!-- Component item -->
              <div v-else-if="selectedItem && selectedItem.kind === 'component'" class="flex flex-col">
                <section class="flex flex-col gap-3 border-b border-slate-100 px-3.5 py-3.5">
                  <label class="flex items-center gap-2.5 text-[12.5px] text-ink">
                    <ToggleSwitch v-model="selectedItem.enabled" :true-value="true" :false-value="false" size="sm" />
                    Bloco visível
                  </label>

                  <!-- Order bump -->
                  <template v-if="selectedItem.component === 'order_bump'">
                    <BuilderField label="Produto da oferta">
                      <SearchMultiSelect v-model="bumpSelection" type="products" placeholder="Buscar produto..." />
                    </BuilderField>
                    <BuilderField label="Título da oferta">
                      <BuilderTextInput v-model="selectedItem.config.headline" placeholder="Adicione e economize" />
                    </BuilderField>
                    <BuilderField label="Descrição">
                      <BuilderTextArea v-model="selectedItem.config.description" :rows="3" />
                    </BuilderField>
                    <BuilderField label="Selo de desconto">
                      <BuilderTextInput v-model="selectedItem.config.discount_label" placeholder="-20%" />
                    </BuilderField>
                    <BuilderField label="Cor de destaque">
                      <BuilderColor v-model="selectedItem.config.highlight_color" />
                    </BuilderField>
                    <label class="flex items-center gap-2.5 text-[12.5px] text-ink">
                      <ToggleSwitch v-model="selectedItem.config.default_checked" :true-value="true" :false-value="false" size="sm" />
                      Marcado por padrão
                    </label>
                  </template>

                  <!-- HTML block -->
                  <template v-else-if="selectedItem.component === 'html'">
                    <BuilderField label="Variante">
                      <BaseSelect v-model="selectedItem.config.variant" :options="HTML_VARIANTS" size="sm" />
                    </BuilderField>
                    <BuilderField label="Alinhamento">
                      <BuilderSegmented v-model="selectedItem.config.align" :options="ALIGN_OPTIONS" />
                    </BuilderField>
                    <BuilderField label="Conteúdo HTML (sanitizado)">
                      <BuilderTextArea v-model="selectedItem.config.html" :rows="6" mono />
                    </BuilderField>
                  </template>

                  <!-- Coupon -->
                  <template v-else-if="selectedItem.component === 'coupon'">
                    <BuilderField label="Título (opcional)">
                      <BuilderTextInput v-model="selectedItem.config.title" placeholder="Tem um cupom?" />
                    </BuilderField>
                  </template>

                  <!-- Summary -->
                  <template v-else-if="selectedItem.component === 'summary'">
                    <BuilderField label="Título (opcional)">
                      <BuilderTextInput v-model="selectedItem.config.title" placeholder="Resumo do pedido" />
                    </BuilderField>
                    <label class="flex items-center gap-2.5 text-[12.5px] text-ink">
                      <ToggleSwitch v-model="selectedItem.config.collapsible" :true-value="true" :false-value="false" size="sm" />
                      Recolhível
                    </label>
                    <label class="flex items-center gap-2.5 text-[12.5px] text-ink">
                      <ToggleSwitch v-model="selectedItem.config.hide_coupon" :true-value="true" :false-value="false" size="sm" />
                      Ocultar cupom no resumo
                    </label>
                  </template>

                  <!-- Notes -->
                  <template v-else-if="selectedItem.component === 'notes'">
                    <BuilderField label="Rótulo">
                      <BuilderTextInput v-model="selectedItem.config.label" />
                    </BuilderField>
                    <BuilderField label="Placeholder">
                      <BuilderTextInput v-model="selectedItem.config.placeholder" />
                    </BuilderField>
                    <label class="flex items-center gap-2.5 text-[12.5px] text-ink">
                      <ToggleSwitch v-model="selectedItem.config.required" :true-value="true" :false-value="false" size="sm" />
                      Obrigatório
                    </label>
                  </template>

                  <!-- Banner -->
                  <template v-else-if="selectedItem.component === 'banner'">
                    <BuilderField label="Imagem de fundo">
                      <MediaPickerField v-model="selectedItem.config.image" :field="{ label: 'Imagem do banner' }" />
                    </BuilderField>
                    <div class="grid grid-cols-2 gap-2">
                      <BuilderField label="Cor de fundo">
                        <BuilderColor v-model="selectedItem.config.bg_color" />
                      </BuilderField>
                      <BuilderField label="Cor do título">
                        <BuilderColor v-model="selectedItem.config.title_color" />
                      </BuilderField>
                    </div>
                    <BuilderField label="Título">
                      <BuilderTextInput v-model="selectedItem.config.title" />
                    </BuilderField>
                    <BuilderField label="Subtítulo">
                      <BuilderTextInput v-model="selectedItem.config.subtitle" />
                    </BuilderField>
                    <BuilderField label="Texto do botão">
                      <BuilderTextInput v-model="selectedItem.config.button_text" />
                    </BuilderField>
                    <BuilderField label="Alinhamento">
                      <BuilderSegmented v-model="selectedItem.config.align" :options="ALIGN_OPTIONS" />
                    </BuilderField>
                    <BuilderField label="Link (opcional)">
                      <BuilderTextInput v-model="selectedItem.config.link" type="url" placeholder="https://" />
                    </BuilderField>
                    <BuilderField label="Contador regressivo até">
                      <BuilderTextInput v-model="selectedItem.config.countdown" type="datetime-local" />
                    </BuilderField>
                  </template>

                  <!-- Reviews -->
                  <template v-else-if="selectedItem.component === 'reviews'">
                    <BuilderField label="Origem das avaliações">
                      <BaseSelect v-model="selectedItem.config.source" :options="REVIEW_SOURCES" size="sm" />
                    </BuilderField>

                    <BuilderField v-if="selectedItem.config.source === 'product'" label="Produto">
                      <SearchMultiSelect v-model="bumpSelection" type="products" placeholder="Buscar produto..." />
                    </BuilderField>

                    <div v-else class="flex flex-col gap-2">
                      <div
                        v-for="(review, index) in selectedItem.config.items"
                        :key="index"
                        class="rounded-lg border border-slate-200 p-2.5"
                      >
                        <div class="mb-2 flex items-center justify-between">
                          <span class="text-[11px] font-semibold text-slate-400">Depoimento {{ index + 1 }}</span>
                          <button
                            type="button"
                            class="flex cursor-pointer rounded border-0 bg-transparent p-0.5 text-slate-400 transition hover:bg-danger/10 hover:text-danger"
                            @click="removeReviewItem(selectedItem, index)"
                          >
                            <BoxIcon name="trash" class="h-3.5 w-3.5" />
                          </button>
                        </div>
                        <div class="mb-2"><BuilderTextInput v-model="review.author" placeholder="Autor" /></div>
                        <div class="mb-2"><BuilderTextArea v-model="review.text" :rows="2" placeholder="Depoimento" /></div>
                        <div class="grid grid-cols-2 gap-2">
                          <BuilderTextInput v-model.number="review.rating" type="number" placeholder="Nota" />
                          <MediaPickerField v-model="review.avatar" :field="{ label: 'Avatar' }" />
                        </div>
                      </div>

                      <button
                        type="button"
                        class="inline-flex cursor-pointer items-center justify-center gap-1 rounded-lg border border-primary-200 bg-white px-3 py-2 text-[11.5px] font-medium text-primary transition hover:bg-primary-50"
                        @click="addReviewItem(selectedItem)"
                      >
                        <BoxIcon name="plus" class="h-3.5 w-3.5" />
                        Adicionar depoimento
                      </button>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                      <BuilderField label="Quantidade" :suffix="String(selectedItem.config.limit || 4)">
                        <BuilderRange v-model="selectedItem.config.limit" :min="1" :max="20" />
                      </BuilderField>
                      <BuilderField label="Layout">
                        <BaseSelect v-model="selectedItem.config.layout" :options="REVIEW_LAYOUTS" size="sm" />
                      </BuilderField>
                    </div>
                  </template>
                </section>
              </div>

              <!-- Step -->
              <div v-else-if="selectedStep" class="flex flex-col">
                <section class="flex flex-col gap-3 border-b border-slate-100 px-3.5 py-3.5">
                  <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400">Etapa</span>

                  <BuilderField
                    label="Rótulo"
                    :hint="selectedStep.type !== 'custom' ? 'Espelha a configuração text_check_step.' : ''"
                  >
                    <BuilderTextInput v-model="selectedStep.label" />
                  </BuilderField>

                  <label class="flex items-center gap-2.5 text-[12.5px] text-ink">
                    <ToggleSwitch v-model="selectedStep.enabled" :true-value="true" :false-value="false" size="sm" />
                    Etapa ativa
                  </label>

                  <BuilderField
                    label="Tipo"
                    :hint="selectedStep.type !== 'custom'
                      ? ('Etapa semântica — não pode ser excluída.' + (selectedStep.type === 'payment' ? ' Sempre a última.' : ''))
                      : 'Etapa personalizada — removível e reordenável.'"
                  >
                    <p class="m-0 rounded-lg bg-slate-50 px-2.5 py-2 text-[12.5px] text-ink">{{ STEP_META[selectedStep.type].label }}</p>
                  </BuilderField>
                </section>

                <section v-if="selectedStep.type === 'payment'" class="border-b border-slate-100 px-3.5 py-3.5">
                  <BuilderField label="Formas de pagamento" hint="Sanfona ou cards. Cada forma usa o ícone da integração.">
                    <BuilderSegmented v-model="settingsDraft.payment_methods_layout" :options="PAYMENT_LAYOUT_OPTIONS" />
                  </BuilderField>
                </section>

                <section v-if="selectedStep.type === 'custom'" class="px-3.5 py-3.5">
                  <button
                    type="button"
                    class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-danger/30 bg-transparent px-3 py-2 text-[12px] font-medium text-danger transition hover:bg-danger/10"
                    @click="removeStep(selectedStep)"
                  >
                    <BoxIcon name="trash" class="h-3.5 w-3.5" />
                    Remover etapa
                  </button>
                </section>
              </div>

              <!-- Empty state -->
              <div v-else class="flex flex-col items-center gap-2 px-6 py-16 text-center">
                <BoxIcon name="slider-alt" class="h-6 w-6 text-slate-300" />
                <span class="text-[12.5px] leading-relaxed text-slate-400">
                  Selecione uma etapa, campo ou bloco — na árvore de camadas ou direto no preview.
                </span>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </Teleport>
</template>
