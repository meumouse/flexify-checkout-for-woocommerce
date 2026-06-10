<script setup>
import { computed, ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import { resolveFieldComponent } from './fieldRegistry';
import TextField from './TextField.vue';
import BaseButton from '../buttons/BaseButton.vue';
import ModalDialog from '../modals/ModalDialog.vue';
import EmailProviders from '../settings/EmailProviders.vue';
import FontsManager from '../settings/FontsManager.vue';

const props = defineProps({
  field: { type: Object, required: true },
});

const store = useSettingsStore();

const fieldComponent = computed(() => resolveFieldComponent(props.field) || TextField);

const isProLocked = computed(() => Boolean(props.field?.pro) && !store.isPro);

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
    class="grid items-start gap-6 py-6 lg:grid-cols-[minmax(0,420px)_minmax(0,520px)]"
    :class="String(field.type) === 'code-editor' ? '' : 'lg:items-center'"
  >
    <div>
      <div class="flex items-start gap-2">
        <h3 class="m-0 text-[15px] font-semibold leading-snug text-slate-800">{{ field.label }}</h3>

        <span
          v-if="field.pro && !store.isPro"
          class="inline-flex shrink-0 items-center gap-1 rounded-full bg-primary-100 px-2 py-0.5 text-[10px] font-semibold text-primary"
        >
          <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3Z" /></svg>
          Pro
        </span>
      </div>

      <p v-if="field.description" class="m-0 mt-1 max-w-xl text-[13px] leading-5 text-slate-500">
        {{ field.description }}
      </p>

      <div v-if="placeholders.length" class="mt-3 flex flex-col gap-1">
        <div v-for="hint in placeholders" :key="hint.token" class="flex items-baseline gap-2">
          <code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-600">{{ hint.token }}</code>
          <span class="text-[11px] text-slate-500">{{ hint.description }}</span>
        </div>
      </div>
    </div>

    <div class="flex min-w-0 items-center gap-4 lg:justify-self-start" :class="String(field.type) === 'code-editor' ? 'w-full' : ''">
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
    </div>

    <ModalDialog v-if="popup" :open="popupOpen" :title="popup.title || popup.button" size="lg" @close="popupOpen = false">
      <component :is="popupComponents[popup.component]" v-if="popup.component && popupComponents[popup.component]" />

      <div v-else-if="Array.isArray(popup.fields)" class="divide-y divide-gray-100">
        <FieldRow v-for="subField in popup.fields" :key="subField.key" :field="subField" />
      </div>

      <template #footer>
        <div class="flex justify-end">
          <BaseButton variant="secondary" @click="popupOpen = false">Fechar</BaseButton>
        </div>
      </template>
    </ModalDialog>
  </div>
</template>

<script>
export default {
  name: 'FieldRow',
};
</script>
