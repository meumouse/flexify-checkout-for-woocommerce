<script setup>
import { computed } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';

const store = useSettingsStore();

const themes = computed(() => (Array.isArray(store.runtime?.themes) ? store.runtime.themes : []));

const current = computed(() => store.settings?.flexify_checkout_theme);

function selectTheme(theme) {
  if (theme.status !== 'active') {
    return;
  }

  store.setSetting('flexify_checkout_theme', theme.value);
}
</script>

<template>
  <div class="py-5">
    <div class="w-full sm:w-[340px] sm:pr-8">
      <span class="text-[13px] font-semibold leading-snug text-brand">Selecione um tema</span>
      <p class="m-0 mt-1 text-xs italic leading-relaxed text-slate-500">
        Selecione um tema que será carregado na página de finalização de compras.
      </p>
    </div>

    <div class="mt-4 flex flex-wrap gap-4">
      <button
        v-for="theme in themes"
        :key="theme.value"
        type="button"
        class="relative w-64 overflow-hidden rounded-xl border-2 bg-white p-0 text-left transition-all"
        :class="[
          current === theme.value ? 'border-primary shadow-sm' : 'border-slate-200',
          theme.status === 'active' ? 'cursor-pointer hover:border-primary-300' : 'cursor-not-allowed',
        ]"
        @click="selectTheme(theme)"
      >
        <div
          v-if="theme.status !== 'active'"
          class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 bg-white/85 text-muted"
        >
          <BoxIcon name="hourglass" class="h-8 w-8" />
          <span class="text-sm font-medium">Em breve...</span>
        </div>

        <div v-if="theme.icon" class="theme-preview flex min-h-[150px] items-center justify-center bg-slate-50" v-html="theme.icon" />
        <div v-else class="min-h-[150px] bg-slate-50" />

        <div class="px-4 py-3 text-center">
          <span class="text-sm font-semibold text-brand">{{ theme.label }}</span>
        </div>
      </button>
    </div>
  </div>
</template>

<style scoped>
.theme-preview :deep(svg) {
  width: 100%;
  height: auto;
  max-height: 160px;
}
</style>
