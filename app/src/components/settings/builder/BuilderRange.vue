<script setup>
/**
 * Range slider for the builder inspector. Emits numbers so numeric style props
 * (radius, sizes, weight, margins) round-trip cleanly.
 *
 * @since 6.0.0
 */
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number], default: 0 },
  min: { type: [String, Number], default: 0 },
  max: { type: [String, Number], default: 100 },
  step: { type: [String, Number], default: 1 },
});

const emit = defineEmits(['update:modelValue']);

const model = computed({
  get: () => Number(props.modelValue) || 0,
  set: (value) => emit('update:modelValue', Number(value)),
});
</script>

<template>
  <!--
    Native range appearance kept on purpose: `appearance-none` removes the
    default thumb/track, which — without custom pseudo-element styling — leaves
    an undraggable control. `accent-color` (accent-primary) themes the native
    slider while keeping it fully interactive.
  -->
  <input
    v-model="model"
    type="range"
    :min="min"
    :max="max"
    :step="step"
    class="w-full cursor-pointer accent-primary"
  />
</template>
