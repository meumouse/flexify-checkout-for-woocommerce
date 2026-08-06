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

// Wide fields break out of the two-column label/field table and stack the
// control under the label so it can span the full content width. The cells are
// collapsed to blocks in CSS, so the markup stays a single table row.
const isWide = computed(() => Boolean(props.field?.wide));

// Controls that manage their own width still need the cell to stretch.
const isFullWidthControl = computed(() => isWide.value || String(props.field?.type) === 'code-editor');

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

// The placeholder-aware field renders its own interactive token list, so the
// static legend in the label column would just be a duplicate.
const fieldOwnsPlaceholders = computed(() =>
  [props.field?.component, props.field?.type].some((name) => String(name || '').replace(/[\s_-]+/g, '').toLowerCase() === 'placeholdertextarea'),
);

// Tall controls (editors, placeholder legends) read better top-aligned; every
// other row centers the label against its control, as in the design system.
const stretchedRow = computed(
  () => isFullWidthControl.value || fieldOwnsPlaceholders.value || placeholders.value.length > 0,
);
</script>

<template>
  <tr
    v-if="store.isFieldVisible(field)"
    class="flexify-field-row"
    :class="isWide ? 'flexify-field-row--wide' : ''"
  >
    <td class="flexify-field-cell flexify-field-cell--label" :class="stretchedRow ? 'align-top' : 'align-middle'">
      <div class="flex items-start gap-2">
        <h3 class="m-0 text-[15px] font-semibold leading-snug text-slate-800">{{ field.label }}</h3>

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

      <p v-if="field.description" class="m-0 mt-1 max-w-xl text-[13px] leading-5 text-slate-500">
        {{ field.description }}
      </p>

      <div v-if="placeholders.length && !fieldOwnsPlaceholders" class="mt-3 flex flex-col gap-1">
        <div v-for="hint in placeholders" :key="hint.token" class="flex items-baseline gap-2">
          <code class="rounded bg-slate-100 px-1.5 py-0.5 text-[12px] text-slate-600">{{ hint.token }}</code>
          <span class="text-[12px] text-slate-500">{{ hint.description }}</span>
        </div>
      </div>
    </td>

    <td class="flexify-field-cell flexify-field-cell--control" :class="stretchedRow ? 'align-top' : 'align-middle'">
      <div class="relative flex min-w-0 items-center gap-4" :class="isFullWidthControl ? 'w-full' : ''">
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

      <!-- Both dialogs teleport to <body>, so living inside the cell is safe. -->
      <ModalDialog v-if="popup" :open="popupOpen" :title="popup.title || popup.button" size="lg" @close="popupOpen = false">
        <component :is="popupComponents[popup.component]" v-if="popup.component && popupComponents[popup.component]" />

        <table v-else-if="Array.isArray(popup.fields)" class="flexify-fields-table flexify-fields-table--stacked w-full border-collapse">
          <colgroup>
            <col class="w-[444px]" />
            <col />
          </colgroup>

          <tbody>
            <FieldRow v-for="subField in popup.fields" :key="subField.key" :field="subField" />
          </tbody>
        </table>

        <template #footer>
          <div class="flex justify-end">
            <BaseButton variant="secondary" @click="popupOpen = false">Fechar</BaseButton>
          </div>
        </template>
      </ModalDialog>

      <ProUpsellModal :open="proModalOpen" @close="proModalOpen = false" />
    </td>
  </tr>
</template>

<style scoped>
/*
 * Cell spacing lives here instead of in utility classes: Tailwind runs with
 * `important: true`, so the stacked-layout overrides below could never win
 * against a `py-6`/`pr-10` utility on the same element.
 */
.flexify-field-cell {
  padding-top: 1.5rem;
  padding-bottom: 1.5rem;
}

/*
 * The column widths come from the parent table's <colgroup> (fixed layout);
 * here the label cell only reserves the 24px gutter before the control column,
 * matching the 420px / 460px pair used across the design system.
 */
.flexify-field-cell--label {
  padding-right: 1.5rem;
}

/*
 * Wide fields and narrow viewports collapse the row to a stack: the cells become
 * blocks so the control spans the full content width under its label.
 */
.flexify-field-row--wide,
.flexify-field-row--wide > .flexify-field-cell {
  display: block;
  width: 100%;
}

.flexify-field-row--wide > .flexify-field-cell--label {
  padding-right: 0;
  padding-bottom: 0;
}

.flexify-field-row--wide > .flexify-field-cell--control {
  padding-top: 0.75rem;
}

/* Narrow containers (modals) always stack, there is no room for two columns. */
.flexify-fields-table--stacked .flexify-field-row,
.flexify-fields-table--stacked .flexify-field-cell {
  display: block;
  width: 100%;
}

.flexify-fields-table--stacked .flexify-field-cell--label {
  padding-right: 0;
  padding-bottom: 0;
}

.flexify-fields-table--stacked .flexify-field-cell--control {
  padding-top: 0.75rem;
}

@media (max-width: 1024px) {
  .flexify-field-row,
  .flexify-field-row > .flexify-field-cell {
    display: block;
    width: 100%;
  }

  .flexify-field-row > .flexify-field-cell--label {
    padding-right: 0;
    padding-bottom: 0;
  }

  .flexify-field-row > .flexify-field-cell--control {
    padding-top: 0.75rem;
  }
}
</style>

<script>
export default {
  name: 'FieldRow',
};
</script>
