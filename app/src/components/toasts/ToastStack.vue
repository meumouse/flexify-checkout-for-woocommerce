<script setup>
import { useSettingsStore } from '../../stores/useSettingsStore';

const store = useSettingsStore();

function toastIcon(type) {
  const icons = {
    success: 'check-circle',
    error: 'error-circle',
    info: 'info-circle',
  };

  return icons[type] || icons.info;
}

function toastHeaderClass(type) {
  const classes = {
    success: 'bg-success text-white',
    error: 'bg-danger text-white',
    info: 'bg-info text-white',
  };

  return classes[type] || classes.info;
}

function toastProgressClass(type) {
  const classes = {
    success: 'bg-success',
    error: 'bg-danger',
    info: 'bg-info',
  };

  return `${classes[type] || classes.info} [animation:flexify-toast-progress_3s_linear_forwards]`;
}
</script>

<template>
  <div class="pointer-events-none fixed right-3 top-12 z-[99999] w-[350px] max-w-full" aria-live="polite" aria-atomic="true">
    <TransitionGroup name="flexify-toast" tag="div" class="space-y-3">
      <article
        v-for="toast in store.toasts"
        :key="toast.id"
        class="pointer-events-auto relative overflow-hidden rounded-lg border border-transparent bg-white shadow-[0_0.275rem_1.25rem_rgba(11,15,25,0.05),0_0.25rem_0.5625rem_rgba(11,15,25,0.03)] transition-all duration-200 ease-out"
        :class="toast.closing ? 'translate-y-1 opacity-0' : 'translate-y-0 opacity-100'"
      >
        <header class="flex items-center border-0 px-4 py-2 text-sm font-bold" :class="toastHeaderClass(toast.type)">
          <BoxIcon :name="toastIcon(toast.type)" type="solid" class="me-2 h-5 w-5 shrink-0 text-current" />
          <span class="me-auto min-w-0 truncate">{{ toast.title }}</span>

          <button
            type="button"
            class="ms-2 box-content flex h-4 w-4 shrink-0 cursor-pointer items-center justify-center rounded border-0 bg-transparent p-1 opacity-50 transition hover:opacity-75 focus:opacity-100 focus:outline-none"
            aria-label="Fechar"
            @click="store.dismissToast(toast.id)"
          >
            <BoxIcon name="x" class="h-3.5 w-3.5" />
          </button>
        </header>

        <div class="px-4 py-4 text-[15px] leading-6 text-slate-600">
          {{ toast.message }}
        </div>

        <div class="h-[3px] w-full origin-left" :class="toastProgressClass(toast.type)" />
      </article>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.flexify-toast-enter-active,
.flexify-toast-leave-active {
  transition:
    opacity 0.25s ease,
    transform 0.25s ease;
}

.flexify-toast-enter-from,
.flexify-toast-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
