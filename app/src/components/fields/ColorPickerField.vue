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
  get: () => props.modelValue || '#000000',
  set: (value) => emit('update:modelValue', value),
});

const textModel = computed({
  get: () => props.modelValue ?? '',
  set: (value) => emit('update:modelValue', value),
});
</script>

<template>
  <div class="flex items-center gap-2">
    <input
      v-model="model"
      type="color"
      :name="name"
      :disabled="disabled"
      class="h-9 w-12 cursor-pointer rounded-lg border border-gray-300 bg-white p-1"
      :class="disabled ? 'cursor-not-allowed opacity-50' : ''"
    />

    <input
      v-model="textModel"
      type="text"
      :disabled="disabled"
      placeholder="#000000"
      class="flexify-field-input w-28 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm uppercase text-ink focus:border-primary focus:ring-2 focus:ring-primary-100"
      :class="disabled ? 'cursor-not-allowed opacity-50' : ''"
    />
  </div>
</template>
