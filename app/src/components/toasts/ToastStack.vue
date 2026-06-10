<script setup>
import { useSettingsStore } from '../../stores/useSettingsStore';

const store = useSettingsStore();
</script>

<template>
  <div class="fixed bottom-6 right-6 z-[99999] flex flex-col gap-2">
    <transition-group name="flexify-toast">
      <div
        v-for="toast in store.toasts"
        :key="toast.id"
        class="flex min-w-[260px] max-w-sm items-center gap-3 rounded-xl border bg-panel px-4 py-3 shadow-soft"
        :class="toast.type === 'success' ? 'border-success/40' : 'border-danger/40'"
        role="status"
      >
        <span
          class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
          :class="toast.type === 'success' ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
        >
          <svg v-if="toast.type === 'success'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" />
          </svg>

          <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </span>

        <p class="m-0 text-sm text-ink">{{ toast.message }}</p>
      </div>
    </transition-group>
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
