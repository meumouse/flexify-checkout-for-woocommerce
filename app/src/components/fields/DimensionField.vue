<script setup>
import { computed } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  field: { type: Object, default: () => ({}) },
  name: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const store = useSettingsStore();

const value = computed({
  get: () => props.modelValue ?? '',
  set: (next) => emit('update:modelValue', next),
});

const unit = computed({
  get: () => String(store.settings?.[props.field?.unit_key] ?? ''),
  set: (next) => store.setSetting(props.field.unit_key, next),
});

const units = computed(() => (Array.isArray(props.field?.units) ? props.field.units : []));
</script>

<template>
  <div class="inline-flex items-stretch overflow-hidden rounded-lg border border-gray-300 bg-white" :class="disabled ? 'opacity-50' : ''">
    <input
      v-model="value"
      type="number"
      step="any"
      :name="name"
      :disabled="disabled"
      class="flexify-field-input w-20 border-0 bg-transparent px-3 py-2 text-sm text-ink focus:outline-none"
    />

    <select
      v-model="unit"
      :disabled="disabled"
      class="cursor-pointer border-0 border-l border-gray-200 bg-gray-50 px-2 py-2 text-sm text-ink focus:outline-none"
    >
      <option v-for="option in units" :key="option.value" :value="option.value">{{ option.label }}</option>
    </select>
  </div>
</template>
