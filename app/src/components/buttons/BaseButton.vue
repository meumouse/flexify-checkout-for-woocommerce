<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: { type: String, default: 'primary' },
  size: { type: String, default: 'md' },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  type: { type: String, default: 'button' },
});

const classes = computed(() => {
  const variants = {
    primary: 'bg-primary text-white border border-primary hover:bg-primary-700 hover:border-primary-700 focus:ring-primary-200',
    outline: 'bg-white text-primary border border-primary hover:bg-primary hover:text-white focus:ring-primary-200',
    secondary: 'bg-white text-ink border border-gray-300 hover:bg-gray-50 focus:ring-gray-200',
    'outline-warning': 'bg-white text-warning border border-warning hover:bg-warning hover:text-white focus:ring-yellow-200',
    'outline-danger': 'bg-white text-danger border border-danger hover:bg-danger hover:text-white focus:ring-red-200',
    danger: 'bg-danger text-white border border-danger hover:bg-red-600 focus:ring-red-200',
  };

  return variants[props.variant] || variants.primary;
});

const sizeClasses = computed(() => {
  return props.size === 'sm' ? 'px-3 py-1.5 text-xs' : 'px-4 py-2 text-sm';
});
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    class="inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-1"
    :class="[classes, sizeClasses, disabled || loading ? 'cursor-not-allowed opacity-60' : 'cursor-pointer']"
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
