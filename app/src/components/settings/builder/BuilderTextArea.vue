<script setup>
/**
 * Full-width compact textarea for the builder inspector.
 *
 * @since 6.0.0
 */
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  rows: { type: [String, Number], default: 4 },
  mono: { type: Boolean, default: false },
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
    :rows="rows"
    :placeholder="placeholder"
    class="w-full resize-y rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-[12.5px] leading-relaxed text-ink transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-100"
    :class="mono ? 'font-mono text-xs' : ''"
  />
</template>
