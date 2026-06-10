<script setup>
import { computed, reactive, ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';
import ModalDialog from '../modals/ModalDialog.vue';

const store = useSettingsStore();

const fonts = computed(() => {
  const library = store.runtime?.fonts;

  return library && typeof library === 'object' ? library : {};
});

const editorOpen = ref(false);
const editorSaving = ref(false);

const form = reactive({
  isNew: true,
  fontId: '',
  fontName: '',
  fontType: 'google',
  fontUrl: '',
  fontWeight: '400',
  fontStyle: 'normal',
  file: null,
});

const fontId = computed(() => {
  if (!form.isNew) {
    return form.fontId;
  }

  return String(form.fontName || '')
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '_')
    .replace(/[^a-z0-9_]/g, '');
});

const idInUse = computed(() => Boolean(form.isNew && fontId.value && fonts.value[fontId.value]));

const isValid = computed(() => {
  if (!fontId.value || !form.fontName || idInUse.value) {
    return false;
  }

  if (form.fontType === 'google') {
    return Boolean(form.fontUrl);
  }

  return Boolean(form.file) || !form.isNew;
});

function openCreate() {
  Object.assign(form, {
    isNew: true,
    fontId: '',
    fontName: '',
    fontType: 'google',
    fontUrl: '',
    fontWeight: '400',
    fontStyle: 'normal',
    file: null,
  });
  editorOpen.value = true;
}

function openEdit(id, font) {
  Object.assign(form, {
    isNew: false,
    fontId: id,
    fontName: font.font_name || id,
    fontType: font.type === 'upload' ? 'upload' : 'google',
    fontUrl: font.font_url || '',
    fontWeight: font.font_weight || '400',
    fontStyle: font.font_style || 'normal',
    file: null,
  });
  editorOpen.value = true;
}

function onFileChange(event) {
  form.file = event.target.files?.[0] || null;
}

async function submit() {
  if (!isValid.value || editorSaving.value) {
    return;
  }

  editorSaving.value = true;

  try {
    const response = await store.saveFont({
      font_id: fontId.value,
      font_name: form.fontName,
      font_type: form.fontType,
      font_url: form.fontUrl,
      font_weight: form.fontWeight,
      font_style: form.fontStyle,
      is_new: form.isNew ? 'yes' : 'no',
      file: form.file,
    });

    if (response?.status === 'success') {
      editorOpen.value = false;
    }
  } finally {
    editorSaving.value = false;
  }
}

async function removeFont(id, font) {
  if (window.confirm(`Tem certeza que deseja remover a fonte "${font.font_name || id}"?`)) {
    await store.deleteFont(id);
  }
}

const inputClass = 'flexify-field-input w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100';
</script>

<template>
  <div class="mb-5">
    <ul class="m-0 mb-3 flex list-none flex-col gap-1.5 p-0">
      <li
        v-for="(font, id) in fonts"
        :key="id"
        class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2"
      >
        <div class="flex min-w-0 items-center gap-2">
          <span class="truncate text-sm text-ink">{{ font.font_name || id }}</span>

          <span
            class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium"
            :class="font.source === 'custom' ? 'bg-primary-100 text-primary' : 'bg-gray-100 text-muted'"
          >
            {{ font.source === 'custom' ? (font.type === 'upload' ? 'Upload' : 'Google Fonts') : 'Padrão' }}
          </span>

          <span
            v-if="store.settings?.set_font_family === id"
            class="shrink-0 rounded-full bg-success/10 px-2 py-0.5 text-[10px] font-medium text-success"
          >
            Em uso
          </span>
        </div>

        <div class="flex shrink-0 items-center gap-1.5">
          <button
            v-if="font.source === 'custom'"
            type="button"
            class="cursor-pointer rounded-lg border border-gray-300 bg-transparent px-2.5 py-1 text-xs font-medium text-ink transition-colors hover:bg-gray-100"
            @click="openEdit(id, font)"
          >
            Editar
          </button>

          <button
            v-if="font.source === 'custom'"
            type="button"
            class="cursor-pointer rounded-lg border border-danger/30 bg-transparent px-2 py-1 text-danger transition-colors hover:bg-danger/10"
            aria-label="Remover fonte"
            @click="removeFont(id, font)"
          >
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6" stroke-linecap="round" stroke-linejoin="round" /></svg>
          </button>
        </div>
      </li>
    </ul>

    <BaseButton variant="secondary" @click="openCreate">Adicionar nova fonte</BaseButton>

    <ModalDialog
      :open="editorOpen"
      :title="form.isNew ? 'Adicionar nova fonte' : `Editar fonte ${form.fontName}`"
      @close="editorOpen = false"
    >
      <div class="flex flex-col gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-ink">Nome da fonte *</label>
          <input v-model="form.fontName" type="text" :class="inputClass" placeholder="Minha fonte" />
          <p v-if="idInUse" class="m-0 mt-1.5 text-xs text-danger">Essa fonte já existe.</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-ink">Tipo da fonte</label>
          <select v-model="form.fontType" :class="inputClass" :disabled="!form.isNew">
            <option value="google">Google Fonts</option>
            <option value="upload">Upload de arquivo</option>
          </select>
        </div>

        <div v-if="form.fontType === 'google'">
          <label class="mb-1 block text-sm font-medium text-ink">URL de incorporação do Google Fonts *</label>
          <input
            v-model="form.fontUrl"
            type="url"
            :class="inputClass"
            placeholder="https://fonts.googleapis.com/css2?family=..."
          />
        </div>

        <template v-else>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-ink">Peso da fonte</label>
              <input v-model="form.fontWeight" type="text" :class="inputClass" placeholder="400" />
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-ink">Estilo</label>
              <select v-model="form.fontStyle" :class="inputClass">
                <option value="normal">Normal</option>
                <option value="italic">Itálico</option>
              </select>
            </div>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-ink">Arquivo da fonte (WOFF2, WOFF ou TTF) {{ form.isNew ? '*' : '' }}</label>
            <input
              type="file"
              accept=".woff2,.woff,.ttf"
              class="block w-full text-sm text-muted"
              @change="onFileChange"
            />
          </div>
        </template>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <BaseButton variant="secondary" @click="editorOpen = false">Cancelar</BaseButton>
          <BaseButton :loading="editorSaving" :disabled="!isValid" @click="submit">Salvar fonte</BaseButton>
        </div>
      </template>
    </ModalDialog>
  </div>
</template>
