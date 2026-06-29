<script setup>
import { computed, ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import ProUpsellModal from '../modals/ProUpsellModal.vue';

const store = useSettingsStore();

const themes = computed(() => (Array.isArray(store.runtime?.themes) ? store.runtime.themes : []));

const current = computed(() => store.settings?.flexify_checkout_theme);

// Upsell shown when a locked Pro theme is clicked.
const proModalOpen = ref(false);

const BADGE_META = {
  new: { label: 'Novo', class: 'bg-success text-white' },
  recommended: { label: 'Recomendado', class: 'bg-primary text-white' },
};

function themeBadges(theme) {
  return (Array.isArray(theme.badges) ? theme.badges : [])
    .map((key) => BADGE_META[key])
    .filter(Boolean);
}

// A Pro theme requires an active license; lock it until the store is Pro.
function isLocked(theme) {
  return Boolean(theme.pro) && !store.isPro;
}

function selectTheme(theme) {
  if (theme.status !== 'active') {
    return;
  }

  if (isLocked(theme)) {
    proModalOpen.value = true;

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
          v-if="themeBadges(theme).length"
          class="absolute right-2 top-2 z-20 flex flex-wrap justify-end gap-1"
        >
          <span
            v-for="badge in themeBadges(theme)"
            :key="badge.label"
            class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide shadow-sm"
            :class="badge.class"
          >
            {{ badge.label }}
          </span>
        </div>

        <div
          v-if="isLocked(theme)"
          class="absolute left-2 top-2 z-20 inline-flex items-center gap-1 rounded-full bg-primary-100 px-2 py-0.5 text-[10px] font-semibold text-primary shadow-sm"
        >
          <BoxIcon name="crown" type="solid" class="h-2.5 w-2.5" />
          Pro
        </div>

        <div
          v-if="theme.status !== 'active'"
          class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 bg-white/85 text-muted"
        >
          <BoxIcon name="hourglass" class="h-8 w-8" />
          <span class="text-sm font-medium">Em breve...</span>
        </div>

        <div
          v-if="theme.icon"
          class="theme-preview flex min-h-[150px] items-center justify-center bg-slate-50"
          :class="{ 'opacity-60': isLocked(theme) }"
          v-html="theme.icon"
        />
        <div v-else class="min-h-[150px] bg-slate-50" />

        <div class="px-4 py-3 text-center">
          <span class="text-sm font-semibold text-brand">{{ theme.label }}</span>
          <span v-if="isLocked(theme)" class="mt-0.5 block text-[11px] font-medium text-primary">
            Requer licença ativa
          </span>
        </div>
      </button>
    </div>

    <ProUpsellModal :open="proModalOpen" @close="proModalOpen = false" />
  </div>
</template>

<style scoped>
.theme-preview :deep(svg) {
  width: 100%;
  height: auto;
  max-height: 160px;
}
</style>
