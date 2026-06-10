<script setup>
import { computed } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import { resolveFieldComponent } from './fieldRegistry';
import TextField from './TextField.vue';

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

// Wide controls render below the label, taking the full row width.
const isWide = computed(() => String(props.field?.type || '') === 'code-editor');
</script>

<template>
  <div
    v-if="store.isFieldVisible(field)"
    class="flex flex-col gap-3 border-b border-gray-100 py-4 last:border-b-0"
    :class="isWide ? '' : 'sm:flex-row sm:items-start sm:justify-between'"
  >
    <div class="max-w-xl">
      <div class="flex items-center gap-2">
        <span class="text-sm font-medium text-ink">{{ field.label }}</span>

        <span
          v-if="field.pro"
          class="inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary"
        >
          Pro
        </span>
      </div>

      <p v-if="field.description" class="mt-1 text-xs leading-relaxed text-muted">
        {{ field.description }}
      </p>
    </div>

    <div class="shrink-0" :class="isToggle ? 'pt-0.5' : isWide ? 'w-full' : 'w-full sm:w-auto sm:min-w-[16rem]'">
      <component
        :is="fieldComponent"
        v-model="model"
        :field="field"
        :name="field.key"
        :disabled="isProLocked"
        :aria-label="field.label"
        true-value="yes"
        false-value="no"
      />
    </div>
  </div>
</template>
