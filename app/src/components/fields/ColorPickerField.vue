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

const hasDefault = computed(() => Boolean(props.field?.default));

function resetToDefault() {
  if (!props.disabled && hasDefault.value) {
    emit('update:modelValue', props.field.default);
  }
}
</script>

<template>
  <div class="inline-flex items-stretch gap-2" :class="disabled ? 'opacity-50' : ''">
    <div class="inline-flex items-stretch overflow-hidden rounded-lg border border-gray-300 bg-white">
      <label class="flex cursor-pointer items-center border-r border-gray-200 px-1.5">
        <span class="block h-6 w-9 rounded" :style="{ backgroundColor: modelValue || '#000000' }" />
        <input v-model="model" type="color" :name="name" :disabled="disabled" class="sr-only" />
      </label>

      <input
        v-model="textModel"
        type="text"
        :disabled="disabled"
        placeholder="#000000"
        class="flexify-field-input w-28 border-0 bg-transparent px-3 py-2 text-sm lowercase text-ink focus:outline-none"
      />
    </div>

    <button
      v-if="hasDefault"
      type="button"
      :disabled="disabled"
      class="flex cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-white px-2.5 text-muted transition-colors hover:bg-gray-50 hover:text-ink"
      title="Redefinir para cor padrão"
      aria-label="Redefinir para cor padrão"
      @click="resetToDefault"
    >
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 12a9 9 0 1 0 3-6.7L3 8" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M3 3v5h5" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    </button>
  </div>
</template>
