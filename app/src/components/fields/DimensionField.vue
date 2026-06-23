<script setup>
import { computed } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseSelect from './BaseSelect.vue';

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
  <div class="inline-flex items-stretch gap-2" :class="disabled ? 'opacity-50' : ''">
    <input
      v-model="value"
      type="number"
      step="any"
      :name="name"
      :disabled="disabled"
      class="w-24 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-100"
    />

    <BaseSelect
      v-model="unit"
      :options="units"
      :disabled="disabled"
      size="sm"
      class="w-28"
    />
  </div>
</template>
