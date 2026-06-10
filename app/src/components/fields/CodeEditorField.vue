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

const placeholder = computed(() => {
  return props.field?.language === 'javascript' ? "// console.log('Flexify Checkout');" : '/* .flexify-checkout { } */';
});
</script>

<template>
  <textarea
    v-model="model"
    :name="name"
    rows="10"
    spellcheck="false"
    :placeholder="placeholder"
    :disabled="disabled"
    class="flexify-field-input w-full rounded-lg border border-gray-300 bg-gray-900 px-3 py-2 font-mono text-xs leading-relaxed text-gray-100 focus:border-primary focus:ring-2 focus:ring-primary-100"
    :class="disabled ? 'cursor-not-allowed opacity-50' : ''"
  />
</template>
