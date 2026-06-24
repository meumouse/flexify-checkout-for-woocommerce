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
    primary: 'border-transparent bg-primary text-white hover:bg-primary-700 focus-visible:ring-primary-200',
    outline: 'border-primary-200 bg-white text-primary hover:bg-primary-50 focus-visible:ring-primary-100',
    secondary: 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 focus-visible:ring-slate-200',
    'outline-warning': 'border-warning bg-white text-warning hover:bg-warning hover:text-white focus-visible:ring-yellow-200',
    'outline-danger': 'border-danger bg-white text-danger hover:bg-danger hover:text-white focus-visible:ring-red-200',
    danger: 'border-transparent bg-danger text-white hover:opacity-90 focus-visible:ring-red-200',
  };

  return variants[props.variant] || variants.primary;
});

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'px-3 py-2 text-[14px]',
    md: 'px-5 py-3 text-[15px]',
    lg: 'px-6 py-3.5 text-[16px]',
  };

  return sizes[props.size] || sizes.md;
});

const spinnerClass = computed(() => {
  const sizes = {
    sm: 'h-3.5 w-3.5',
    md: 'h-4 w-4',
    lg: 'h-5 w-5',
  };

  return sizes[props.size] || sizes.md;
});
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[8px] border border-solid font-semibold transition focus:outline-none focus-visible:ring-4 disabled:cursor-not-allowed disabled:opacity-60"
    :class="[classes, sizeClasses]"
  >
    <span
      v-if="loading"
      class="inline-flex shrink-0 animate-spin rounded-full border-2 border-current border-r-transparent"
      :class="spinnerClass"
      aria-hidden="true"
    />

    <slot />
  </button>
</template>
