<script setup>
import { computed } from 'vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
  size: { type: String, default: 'md' },
});

const emit = defineEmits(['close']);

const maxWidthClass = computed(() => {
  const map = { md: 'max-w-xl', lg: 'max-w-3xl', xl: 'max-w-5xl' };

  return map[props.size] || map.md;
});
</script>

<template>
  <teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-[99990] flex items-center justify-center bg-ink/40 p-4"
      @click.self="emit('close')"
    >
      <div
        class="flex max-h-[85vh] w-full flex-col overflow-hidden rounded-2xl bg-panel shadow-soft"
        :class="maxWidthClass"
        role="dialog"
        aria-modal="true"
      >
        <header class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
          <h3 class="m-0 text-base font-semibold text-ink">{{ title }}</h3>

          <button
            type="button"
            class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border-0 bg-transparent text-muted transition-colors hover:bg-slate-100 hover:text-ink"
            aria-label="Fechar"
            @click="emit('close')"
          >
            <BoxIcon name="x" class="h-5 w-5" />
          </button>
        </header>

        <div class="overflow-y-auto px-6 py-5">
          <slot />
        </div>

        <footer v-if="$slots.footer" class="border-t border-slate-100 px-6 py-4">
          <slot name="footer" />
        </footer>
      </div>
    </div>
  </teleport>
</template>
