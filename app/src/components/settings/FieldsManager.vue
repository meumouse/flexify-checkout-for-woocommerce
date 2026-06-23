<script setup>
import { computed, reactive, ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';
import ModalDialog from '../modals/ModalDialog.vue';
import ToggleSwitch from '../toggles/ToggleSwitch.vue';

const store = useSettingsStore();

const STEPS = [
  { id: '1', title: 'Etapa 1 (Contato)' },
  { id: '2', title: 'Etapa 2 (Entrega)' },
];

const FIELD_TYPES = [
  { value: 'text', label: 'Texto' },
  { value: 'textarea', label: 'Área de texto' },
  { value: 'number', label: 'Número' },
  { value: 'password', label: 'Senha' },
  { value: 'phone', label: 'Telefone' },
  { value: 'url', label: 'URL' },
  { value: 'select', label: 'Seletor/Lista suspensa' },
  { value: 'checkbox', label: 'Caixa de seleção' },
];

const POSITIONS = [
  { value: 'left', label: 'Esquerda' },
  { value: 'right', label: 'Direita' },
  { value: 'full', label: 'Largura completa' },
];

function sortedStepFields(step) {
  return Object.entries(store.fields)
    .filter(([id, field]) => id.startsWith('billing_') && String(field?.step || '1') === step)
    .sort(([, a], [, b]) => Number(a?.priority || 0) - Number(b?.priority || 0));
}

const stepOne = computed(() => sortedStepFields('1'));
const stepTwo = computed(() => sortedStepFields('2'));

// --- Field editor modal ---

const editorOpen = ref(false);
const editorSaving = ref(false);
const editorFieldId = ref('');
const editor = reactive({
  enabled: 'yes',
  required: 'no',
  label: '',
  position: 'full',
  classes: '',
  label_classes: '',
  input_mask: '',
  hasMask: false,
  step: '1',
  country: '',
  isCountryField: false,
  type: 'text',
  options: [],
});

const newOption = reactive({ value: '', text: '' });

function openEditor(fieldId) {
  const field = store.fields[fieldId] || {};

  editorFieldId.value = fieldId;
  editor.enabled = field.enabled === 'no' ? 'no' : 'yes';
  editor.required = field.required === 'yes' ? 'yes' : 'no';
  editor.label = field.label || '';
  editor.position = field.position || 'full';
  editor.classes = field.classes || '';
  editor.label_classes = field.label_classes || '';
  editor.input_mask = field.input_mask || '';
  editor.hasMask = field.input_mask !== undefined && field.input_mask !== null;
  editor.step = String(field.step || '1');
  editor.country = field.country || '';
  editor.isCountryField = fieldId === 'billing_country' || fieldId === 'shipping_country';
  editor.type = field.type || 'text';
  editor.options = Array.isArray(field.options)
    ? field.options.filter((option) => option && typeof option === 'object').map((option) => ({ ...option }))
    : [];
  editorOpen.value = true;
}

async function applyEditor() {
  if (editorSaving.value) {
    return;
  }

  editorSaving.value = true;

  const props = {
    enabled: editor.enabled,
    required: editor.required,
    label: editor.label,
    position: editor.position,
    classes: editor.classes,
    label_classes: editor.label_classes,
    step: editor.step,
  };

  if (editor.hasMask) {
    props.input_mask = editor.input_mask;
  }

  if (editor.isCountryField && editor.country) {
    props.country = editor.country;
  }

  if (editor.type === 'select' && editorFieldId.value !== 'billing_country') {
    props.options = editor.options;
  }

  try {
    await store.saveFields({ [editorFieldId.value]: props });
    editorOpen.value = false;
  } finally {
    editorSaving.value = false;
  }
}

function addEditorOption() {
  if (!newOption.value) {
    return;
  }

  editor.options.push({ value: newOption.value, text: newOption.text });
  newOption.value = '';
  newOption.text = '';
}

function removeEditorOption(index) {
  editor.options.splice(index, 1);
}

// --- Reorder ---

const reordering = ref(false);

async function moveField(step, index, direction) {
  if (reordering.value) {
    return;
  }

  const list = sortedStepFields(step);
  const target = index + direction;

  if (target < 0 || target >= list.length) {
    return;
  }

  const [currentId, currentField] = list[index];
  const [neighborId, neighborField] = list[target];

  reordering.value = true;

  try {
    await store.saveFields({
      [currentId]: { priority: String(neighborField.priority) },
      [neighborId]: { priority: String(currentField.priority) },
    });
  } finally {
    reordering.value = false;
  }
}

// --- Add field modal ---

const addOpen = ref(false);
const addSaving = ref(false);
const addForm = reactive({
  id: '',
  type: 'text',
  label: '',
  required: 'no',
  position: 'full',
  classes: '',
  label_classes: '',
  input_mask: '',
  step: '1',
  options: [],
});

const addOption = reactive({ value: '', text: '' });

function openAddModal() {
  Object.assign(addForm, {
    id: '',
    type: 'text',
    label: '',
    required: 'no',
    position: 'full',
    classes: '',
    label_classes: '',
    input_mask: '',
    step: '1',
    options: [],
  });
  addOpen.value = true;
}

function addFormOption() {
  if (!addOption.value) {
    return;
  }

  addForm.options.push({ value: addOption.value, text: addOption.text });
  addOption.value = '';
  addOption.text = '';
}

const addFieldId = computed(() => {
  const slug = String(addForm.id || '')
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '_')
    .replace(/[^a-z0-9_]/g, '');

  return slug ? `billing_${slug}` : '';
});

const addIdInUse = computed(() => Boolean(addFieldId.value && store.fields[addFieldId.value]));

async function submitAddField() {
  if (addSaving.value || !addFieldId.value || addIdInUse.value || !addForm.label) {
    return;
  }

  addSaving.value = true;

  const field = {
    id: addFieldId.value,
    type: addForm.type,
    label: addForm.label,
    required: addForm.required,
    position: addForm.position,
    classes: addForm.classes,
    label_classes: addForm.label_classes,
    input_mask: addForm.input_mask,
    step: addForm.step,
  };

  if (addForm.type === 'select' || addForm.type === 'checkbox') {
    field.options = addForm.options;
  }

  try {
    const response = await store.addField(field);

    if (response?.status === 'success') {
      addOpen.value = false;
    }
  } finally {
    addSaving.value = false;
  }
}

// --- Remove ---

async function removeField(fieldId) {
  if (window.confirm(`Tem certeza que deseja remover o campo "${store.fields[fieldId]?.label || fieldId}"?`)) {
    await store.removeField(fieldId);
  }
}

const inputClass = 'flexify-field-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100';
</script>

<template>
  <div class="relative">
    <div
      v-if="!store.isPro"
      class="mb-4 rounded-xl border border-warning/40 bg-warning/10 px-4 py-3 text-sm text-ink"
    >
      O gerenciador de campos requer uma licença Pro ativa. Ative sua licença na aba Sobre.
    </div>

    <div :class="!store.isPro ? 'pointer-events-none opacity-50' : ''">
      <div class="grid gap-5 lg:grid-cols-2">
        <div
          v-for="step in STEPS"
          :key="step.id"
          class="rounded-xl border border-slate-200 bg-slate-50/60 p-4"
        >
          <p class="mb-3 mt-0 text-xs font-semibold uppercase tracking-wide text-muted">{{ step.title }}</p>

          <ul class="m-0 flex list-none flex-col gap-2 p-0">
            <li
              v-for="([fieldId, field], index) in (step.id === '1' ? stepOne : stepTwo)"
              :key="fieldId"
              class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2"
              :class="field.enabled === 'no' ? 'opacity-60' : ''"
            >
              <div class="flex min-w-0 items-center gap-2">
                <div class="flex flex-col">
                  <button
                    type="button"
                    class="cursor-pointer border-0 bg-transparent p-0 leading-none text-muted hover:text-ink disabled:cursor-not-allowed disabled:opacity-30"
                    :disabled="index === 0"
                    aria-label="Mover para cima"
                    @click="moveField(step.id, index, -1)"
                  >
                    <BoxIcon name="chevron-up" class="h-4 w-4" />
                  </button>

                  <button
                    type="button"
                    class="cursor-pointer border-0 bg-transparent p-0 leading-none text-muted hover:text-ink disabled:cursor-not-allowed disabled:opacity-30"
                    :disabled="index === (step.id === '1' ? stepOne : stepTwo).length - 1"
                    aria-label="Mover para baixo"
                    @click="moveField(step.id, index, 1)"
                  >
                    <BoxIcon name="chevron-down" class="h-4 w-4" />
                  </button>
                </div>

                <span class="truncate text-sm text-ink">{{ field.label || fieldId }}</span>

                <span
                  v-if="field.enabled === 'no'"
                  class="shrink-0 rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-medium text-muted"
                >
                  Inativo
                </span>
              </div>

              <div class="flex shrink-0 items-center gap-1.5">
                <button
                  type="button"
                  class="cursor-pointer rounded-lg border border-primary-200 bg-transparent px-2.5 py-1 text-xs font-medium text-primary transition-colors hover:bg-primary-50"
                  @click="openEditor(fieldId)"
                >
                  Editar
                </button>

                <button
                  v-if="field.source && field.source !== 'native'"
                  type="button"
                  class="cursor-pointer rounded-lg border border-danger/30 bg-transparent px-2 py-1 text-danger transition-colors hover:bg-danger/10"
                  aria-label="Remover campo"
                  @click="removeField(fieldId)"
                >
                  <BoxIcon name="trash" class="h-4 w-4" />
                </button>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <div class="mt-4">
        <BaseButton @click="openAddModal">Adicionar novos campos</BaseButton>
      </div>
    </div>

    <!-- Field editor modal -->
    <ModalDialog :open="editorOpen" :title="`Configurar campo ${editor.label || editorFieldId}`" size="lg" @close="editorOpen = false">
      <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="m-0 text-sm font-medium text-ink">Ativar/Desativar este campo</p>
            <p class="m-0 mt-0.5 text-xs text-muted">Campos nativos do WooCommerce não podem ser removidos, apenas desativados.</p>
          </div>
          <ToggleSwitch v-model="editor.enabled" aria-label="Ativar campo" />
        </div>

        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="m-0 text-sm font-medium text-ink">Obrigatoriedade do campo</p>
            <p class="m-0 mt-0.5 text-xs text-muted">Ao desativar, este campo se tornará não obrigatório.</p>
          </div>
          <ToggleSwitch v-model="editor.required" aria-label="Campo obrigatório" />
        </div>

        <div v-if="editor.isCountryField">
          <label class="mb-1 block text-sm font-medium text-ink">Definir país padrão</label>
          <select v-model="editor.country" :class="inputClass">
            <option v-for="country in store.runtime?.countries || []" :key="country.value" :value="country.value">
              {{ country.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-ink">Nome do campo</label>
          <input v-model="editor.label" type="text" :class="inputClass" />
        </div>

        <div v-if="editor.type === 'select' && editorFieldId !== 'billing_country'">
          <label class="mb-1 block text-sm font-medium text-ink">Opções</label>

          <ul class="m-0 mb-2 flex list-none flex-col gap-1.5 p-0">
            <li v-for="(option, index) in editor.options" :key="`${option.value}-${index}`" class="flex items-center gap-2">
              <code class="rounded bg-slate-100 px-2 py-1 text-xs">{{ option.value }}</code>
              <span class="flex-1 text-sm text-ink">{{ option.text }}</span>
              <button
                type="button"
                class="cursor-pointer rounded-lg border border-danger/30 bg-transparent px-2 py-1 text-danger hover:bg-danger/10"
                aria-label="Remover opção"
                @click="removeEditorOption(index)"
              >
                <BoxIcon name="x" class="h-4 w-4" />
              </button>
            </li>
          </ul>

          <div class="flex items-center gap-2">
            <input v-model="newOption.value" type="text" placeholder="Valor" :class="inputClass" class="!w-28" />
            <input v-model="newOption.text" type="text" placeholder="Título da opção" :class="inputClass" />
            <BaseButton variant="secondary" @click="addEditorOption">Adicionar</BaseButton>
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-ink">Posição do campo</label>
            <select v-model="editor.position" :class="inputClass">
              <option v-for="position in POSITIONS" :key="position.value" :value="position.value">{{ position.label }}</option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-ink">Etapa do campo</label>
            <select v-model="editor.step" :class="inputClass">
              <option v-for="step in STEPS" :key="step.id" :value="step.id">{{ step.title }}</option>
            </select>
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-ink">Classe CSS do campo (opcional)</label>
            <input v-model="editor.classes" type="text" :class="inputClass" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-ink">Classe CSS do título (opcional)</label>
            <input v-model="editor.label_classes" type="text" :class="inputClass" />
          </div>
        </div>

        <div v-if="editor.hasMask">
          <label class="mb-1 block text-sm font-medium text-ink">Máscara do campo (opcional)</label>
          <input v-model="editor.input_mask" type="text" :class="inputClass" placeholder="(00) 00000-0000" />
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <BaseButton variant="secondary" @click="editorOpen = false">Cancelar</BaseButton>
          <BaseButton :loading="editorSaving" @click="applyEditor">Salvar campo</BaseButton>
        </div>
      </template>
    </ModalDialog>

    <!-- Add field modal -->
    <ModalDialog :open="addOpen" title="Adicionar novo campo para finalização de compras" size="lg" @close="addOpen = false">
      <div class="flex flex-col gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-ink">Nome e ID do campo *</label>
          <div class="flex items-center">
            <span class="rounded-l-lg border border-r-0 border-slate-300 bg-slate-100 px-3 py-2 text-sm text-muted">billing_</span>
            <input v-model="addForm.id" type="text" :class="inputClass" class="!rounded-l-none" />
          </div>
          <p v-if="addIdInUse" class="m-0 mt-1.5 text-xs text-danger">
            Este nome e ID do campo já está em uso. Use um outro nome.
          </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-ink">Tipo do campo *</label>
            <select v-model="addForm.type" :class="inputClass">
              <option v-for="type in FIELD_TYPES" :key="type.value" :value="type.value">{{ type.label }}</option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-ink">Título do campo *</label>
            <input v-model="addForm.label" type="text" :class="inputClass" />
          </div>
        </div>

        <div v-if="addForm.type === 'select' || addForm.type === 'checkbox'">
          <label class="mb-1 block text-sm font-medium text-ink">Opções</label>

          <ul class="m-0 mb-2 flex list-none flex-col gap-1.5 p-0">
            <li v-for="(option, index) in addForm.options" :key="`${option.value}-${index}`" class="flex items-center gap-2">
              <code class="rounded bg-slate-100 px-2 py-1 text-xs">{{ option.value }}</code>
              <span class="flex-1 text-sm text-ink">{{ option.text }}</span>
              <button
                type="button"
                class="cursor-pointer rounded-lg border border-danger/30 bg-transparent px-2 py-1 text-danger hover:bg-danger/10"
                aria-label="Remover opção"
                @click="addForm.options.splice(index, 1)"
              >
                <BoxIcon name="x" class="h-4 w-4" />
              </button>
            </li>
          </ul>

          <div class="flex items-center gap-2">
            <input v-model="addOption.value" type="text" placeholder="Valor" :class="inputClass" class="!w-28" />
            <input v-model="addOption.text" type="text" placeholder="Título da opção" :class="inputClass" />
            <BaseButton variant="secondary" @click="addFormOption">Adicionar</BaseButton>
          </div>
        </div>

        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="m-0 text-sm font-medium text-ink">Obrigatoriedade do campo</p>
            <p class="m-0 mt-0.5 text-xs text-muted">Ao desativar, este campo se tornará não obrigatório.</p>
          </div>
          <ToggleSwitch v-model="addForm.required" aria-label="Campo obrigatório" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-ink">Posição do campo</label>
            <select v-model="addForm.position" :class="inputClass">
              <option v-for="position in POSITIONS" :key="position.value" :value="position.value">{{ position.label }}</option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-ink">Etapa do campo</label>
            <select v-model="addForm.step" :class="inputClass">
              <option v-for="step in STEPS" :key="step.id" :value="step.id">{{ step.title }}</option>
            </select>
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-ink">Classe CSS do campo (opcional)</label>
            <input v-model="addForm.classes" type="text" :class="inputClass" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-ink">Classe CSS do título (opcional)</label>
            <input v-model="addForm.label_classes" type="text" :class="inputClass" />
          </div>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-ink">Máscara do campo (opcional)</label>
          <input v-model="addForm.input_mask" type="text" :class="inputClass" placeholder="(00) 00000-0000" />
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <BaseButton variant="secondary" @click="addOpen = false">Cancelar</BaseButton>
          <BaseButton :loading="addSaving" :disabled="!addFieldId || addIdInUse || !addForm.label" @click="submitAddField">
            Adicionar campo
          </BaseButton>
        </div>
      </template>
    </ModalDialog>
  </div>
</template>
