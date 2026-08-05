<script setup>
/**
 * Full-width compact text input for the builder inspector.
 *
 * Wraps a native input so builder templates stay free of raw HTML controls and
 * share one look. Supports text/url/number/datetime-local via `type`.
 *
 * @since 6.0.0
 */
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  mono: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const model = computed({
  get: () => props.modelValue ?? '',
  set: (value) => emit('update:modelValue', value),
});
</script>

<template>
  <input
    v-model="model"
    :type="type"
    :placeholder="placeholder"
    :disabled="disabled"
    class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-[12.5px] text-ink transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-100"
    :class="[mono ? 'font-mono text-xs' : '', disabled ? 'cursor-not-allowed opacity-50' : '']"
  />
</template>
