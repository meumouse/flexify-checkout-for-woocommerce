<script setup>
/**
 * Segmented control for the builder inspector — a pill group where one option is
 * active. Used for width/alignment/source/layout/payment-layout choices.
 *
 * @since 6.0.0
 */
defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
  <div class="flex gap-0.5 rounded-lg bg-slate-100 p-0.5">
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      class="flex-1 cursor-pointer rounded-md border-0 px-2 py-1.5 text-[11.5px] font-medium transition"
      :class="String(modelValue) === String(option.value)
        ? 'bg-white text-ink shadow-sm'
        : 'bg-transparent text-slate-500 hover:text-ink'"
      @click="emit('update:modelValue', option.value)"
    >
      {{ option.label }}
    </button>
  </div>
</template>
