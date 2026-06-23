<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  field: { type: Object, default: () => ({}) },
  name: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const model = computed({
  get: () => props.modelValue ?? '',
  set: (value) => emit('update:modelValue', value),
});
</script>

<template>
  <textarea
    v-model="model"
    :name="name"
    :rows="field?.rows || 3"
    :placeholder="field?.placeholder || ''"
    :disabled="disabled"
    class="flexify-field-input w-full max-w-md rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100"
    :class="disabled ? 'cursor-not-allowed opacity-50' : ''"
  />
</template>
