<script setup>
import { computed, ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import { resolveFieldComponent } from './fieldRegistry';
import TextField from './TextField.vue';
import BaseButton from '../buttons/BaseButton.vue';
import ModalDialog from '../modals/ModalDialog.vue';
import ProUpsellModal from '../modals/ProUpsellModal.vue';
import EmailProviders from '../settings/EmailProviders.vue';
import FontsManager from '../settings/FontsManager.vue';

const props = defineProps({
  field: { type: Object, required: true },
});

const store = useSettingsStore();

const fieldComponent = computed(() => resolveFieldComponent(props.field) || TextField);

const isProLocked = computed(() => Boolean(props.field?.pro) && !store.isPro);

// Upsell shown when a locked Pro field is interacted with.
const proModalOpen = ref(false);

const model = computed({
  get: () => store.settings?.[props.field.key],
  set: (value) => store.setSetting(props.field.key, value),
});

const isToggle = computed(() => String(props.field?.type || '') === 'toggle');

// --- Popup support ---

const popupComponents = {
  'email-providers': EmailProviders,
  'fonts-manager': FontsManager,
};

const popup = computed(() => (props.field?.popup && typeof props.field.popup === 'object' ? props.field.popup : null));

const popupOpen = ref(false);

const showPopupTrigger = computed(() => {
  if (!popup.value || isProLocked.value) {
    return false;
  }

  // For toggles the trigger only appears while the feature is enabled,
  // mirroring the legacy interface behavior.
  if (isToggle.value) {
    return model.value === 'yes';
  }

  return true;
});

const placeholders = computed(() => (Array.isArray(props.field?.placeholders) ? props.field.placeholders : []));
</script>

<template>
  <div
    v-if="store.isFieldVisible(field)"
    class="grid items-start gap-6 py-6 lg:grid-cols-[minmax(0,450px)_minmax(0,560px)]"
    :class="String(field.type) === 'code-editor' ? '' : 'lg:items-center'"
  >
    <div>
      <div class="flex items-start gap-2">
        <h3 class="m-0 text-[16px] font-semibold leading-snug text-slate-800">{{ field.label }}</h3>

        <button
          v-if="field.pro && !store.isPro"
          type="button"
          class="inline-flex shrink-0 cursor-pointer items-center gap-1 rounded-full border-0 bg-primary-100 px-2 py-0.5 text-[11px] font-semibold text-primary transition hover:bg-primary-200"
          aria-label="Recurso Pro — requer uma licença ativa"
          @click="proModalOpen = true"
        >
          <BoxIcon name="crown" type="solid" class="h-2.5 w-2.5" />
          Pro
        </button>
      </div>

      <p v-if="field.description" class="m-0 mt-1 max-w-xl text-[14px] leading-5 text-slate-500">
        {{ field.description }}
      </p>

      <div v-if="placeholders.length" class="mt-3 flex flex-col gap-1">
        <div v-for="hint in placeholders" :key="hint.token" class="flex items-baseline gap-2">
          <code class="rounded bg-slate-100 px-1.5 py-0.5 text-[12px] text-slate-600">{{ hint.token }}</code>
          <span class="text-[12px] text-slate-500">{{ hint.description }}</span>
        </div>
      </div>
    </div>

    <div class="relative flex min-w-0 items-center gap-4 lg:justify-self-start" :class="String(field.type) === 'code-editor' ? 'w-full' : ''">
      <component
        :is="fieldComponent"
        v-model="model"
        :field="field"
        :name="field.key"
        :disabled="isProLocked"
        :aria-label="field.label"
        true-value="yes"
        false-value="no"
        :class="String(field.type) === 'code-editor' ? 'w-full' : ''"
      />

      <BaseButton v-if="showPopupTrigger" variant="outline" @click="popupOpen = true">
        {{ popup.button }}
      </BaseButton>

      <!-- Locked Pro field: intercept any interaction and offer the upsell. -->
      <button
        v-if="isProLocked"
        type="button"
        class="absolute inset-0 z-10 cursor-pointer rounded-lg border-0 bg-transparent"
        aria-label="Recurso Pro — requer uma licença ativa"
        @click="proModalOpen = true"
      />
    </div>

    <ModalDialog v-if="popup" :open="popupOpen" :title="popup.title || popup.button" size="lg" @close="popupOpen = false">
      <component :is="popupComponents[popup.component]" v-if="popup.component && popupComponents[popup.component]" />

      <div v-else-if="Array.isArray(popup.fields)" class="divide-y divide-slate-100">
        <FieldRow v-for="subField in popup.fields" :key="subField.key" :field="subField" />
      </div>

      <template #footer>
        <div class="flex justify-end">
          <BaseButton variant="secondary" @click="popupOpen = false">Fechar</BaseButton>
        </div>
      </template>
    </ModalDialog>

    <ProUpsellModal :open="proModalOpen" @close="proModalOpen = false" />
  </div>
</template>

<script>
export default {
  name: 'FieldRow',
};
</script>
