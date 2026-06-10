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

function openMediaLibrary() {
  if (props.disabled || typeof window.wp === 'undefined' || !window.wp.media) {
    return;
  }

  const frame = window.wp.media({
    title: props.field?.label || '',
    multiple: false,
  });

  frame.on('select', () => {
    const attachment = frame.state().get('selection').first()?.toJSON();

    if (attachment?.url) {
      emit('update:modelValue', attachment.url);
    }
  });

  frame.open();
}
</script>

<template>
  <div class="inline-flex w-full max-w-md items-stretch overflow-hidden rounded-lg border border-gray-300 bg-white" :class="disabled ? 'opacity-50' : ''">
    <input
      v-model="model"
      type="text"
      :name="name"
      :disabled="disabled"
      class="flexify-group-control w-full px-4 py-2.5 text-sm text-ink focus:outline-none"
    />

    <button
      type="button"
      :disabled="disabled"
      class="cursor-pointer whitespace-nowrap border-0 border-l border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-ink transition-colors hover:bg-gray-100"
      @click="openMediaLibrary"
    >
      Procurar
    </button>
  </div>
</template>
