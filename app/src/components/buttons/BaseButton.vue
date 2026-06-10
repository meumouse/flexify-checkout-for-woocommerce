<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: { type: String, default: 'primary' },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  type: { type: String, default: 'button' },
});

const classes = computed(() => {
  const variants = {
    primary: 'bg-primary text-white hover:bg-primary-800 focus:ring-primary-300',
    secondary: 'bg-white text-ink border border-gray-300 hover:bg-gray-50 focus:ring-gray-200',
    danger: 'bg-danger text-white hover:bg-red-600 focus:ring-red-300',
  };

  return variants[props.variant] || variants.primary;
});
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-1"
    :class="[classes, disabled || loading ? 'cursor-not-allowed opacity-60' : 'cursor-pointer']"
  >
    <svg
      v-if="loading"
      class="h-4 w-4 animate-spin"
      viewBox="0 0 24 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
    </svg>

    <slot />
  </button>
</template>
