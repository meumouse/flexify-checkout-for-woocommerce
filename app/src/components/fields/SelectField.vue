<script setup>
import { computed } from 'vue';
import BaseSelect from './BaseSelect.vue';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  field: { type: Object, default: () => ({}) },
  name: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const options = computed(() => (Array.isArray(props.field?.options) ? props.field.options : []));
</script>

<template>
  <BaseSelect
    :model-value="String(modelValue ?? '')"
    :options="options"
    :disabled="disabled"
    class="max-w-md"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>
