<script setup>
/**
 * Compact color control for the builder inspector: a color swatch that opens the
 * native picker plus an editable hex field, laid out full width.
 *
 * @since 6.0.0
 */
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  fallback: { type: String, default: '#000000' },
});

const emit = defineEmits(['update:modelValue']);

const swatch = computed({
  get: () => props.modelValue || props.fallback,
  set: (value) => emit('update:modelValue', value),
});

const hex = computed({
  get: () => props.modelValue ?? '',
  set: (value) => emit('update:modelValue', value),
});
</script>

<template>
  <div class="flex items-center gap-2">
    <label class="relative flex h-[34px] w-[34px] shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-lg border border-slate-200">
      <span class="block h-full w-full" :style="{ backgroundColor: modelValue || fallback }" />
      <input v-model="swatch" type="color" class="absolute inset-0 cursor-pointer opacity-0" />
    </label>

    <input
      v-model="hex"
      type="text"
      placeholder="#000000"
      class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-2.5 py-2 font-mono text-xs uppercase text-ink transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-100"
    />
  </div>
</template>
