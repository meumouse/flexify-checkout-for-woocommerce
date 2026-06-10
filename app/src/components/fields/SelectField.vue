<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  field: { type: Object, default: () => ({}) },
  name: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const model = computed({
  get: () => String(props.modelValue ?? ''),
  set: (value) => emit('update:modelValue', value),
});

const options = computed(() => (Array.isArray(props.field?.options) ? props.field.options : []));
</script>

<template>
  <select
    v-model="model"
    :name="name"
    :disabled="disabled"
    class="flexify-field-input w-full max-w-md rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100"
    :class="disabled ? 'cursor-not-allowed opacity-50' : ''"
  >
    <option
      v-for="option in options"
      :key="option.value"
      :value="String(option.value)"
      :disabled="Boolean(option.disabled)"
    >
      {{ option.label }}
    </option>
  </select>
</template>
