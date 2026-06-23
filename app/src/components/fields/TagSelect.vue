<script setup>
import { computed } from 'vue';
import BaseSelect from './BaseSelect.vue';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Selecionar...' },
});

const emit = defineEmits(['update:modelValue']);

const selectedOptions = computed(() =>
  props.modelValue
    .map((value) => props.options.find((option) => String(option.value) === String(value)))
    .filter(Boolean),
);

const availableOptions = computed(() =>
  props.options.filter((option) => !props.modelValue.some((value) => String(value) === String(option.value))),
);

function addValue(value) {
  if (value === '' || value === null || value === undefined) {
    return;
  }

  emit('update:modelValue', [...props.modelValue, value]);
}

function removeValue(value) {
  emit('update:modelValue', props.modelValue.filter((current) => String(current) !== String(value)));
}
</script>

<template>
  <div class="flex flex-col gap-2">
    <div v-if="selectedOptions.length" class="flex flex-wrap gap-1.5">
      <span
        v-for="option in selectedOptions"
        :key="option.value"
        class="inline-flex items-center gap-1.5 rounded-full bg-primary-100 px-2.5 py-1 text-xs font-medium text-primary"
      >
        {{ option.label }}

        <button
          type="button"
          class="cursor-pointer border-0 bg-transparent p-0 leading-none text-primary hover:text-danger"
          aria-label="Remover"
          @click="removeValue(option.value)"
        >
          <BoxIcon name="x" class="h-3.5 w-3.5" />
        </button>
      </span>
    </div>

    <BaseSelect
      :model-value="''"
      :options="availableOptions"
      :placeholder="availableOptions.length ? placeholder : 'Nenhuma opção disponível'"
      :disabled="!availableOptions.length"
      @update:model-value="addValue"
    />
  </div>
</template>
