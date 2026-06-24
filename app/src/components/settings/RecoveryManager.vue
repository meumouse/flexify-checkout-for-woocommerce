<script setup>
/**
 * Cart recovery hub (Configurações > Recuperação).
 *
 * Folds the previously standalone recovery menu pages (Análise, Carrinhos, Fila)
 * into the settings panel as sub-views alongside the recovery settings, so the
 * whole feature lives under a single Configurações tab instead of separate
 * top-level submenus. Each sub-view reuses the existing page component as-is
 * (they fetch their own data over REST on mount).
 *
 * @since 6.0.0
 */
import { ref } from 'vue';
import BoxIcon from '../icons/BoxIcon.vue';
import RecoverySettings from './RecoverySettings.vue';
import AnalyticsPage from '../../pages/recovery/AnalyticsPage.vue';
import CartsPage from '../../pages/recovery/CartsPage.vue';
import QueuePage from '../../pages/recovery/QueuePage.vue';

const views = [
  { id: 'settings', label: 'Configurações', icon: 'cog' },
  { id: 'analytics', label: 'Análise', icon: 'like' },
  { id: 'carts', label: 'Carrinhos', icon: 'cart' },
  { id: 'queue', label: 'Fila', icon: 'hourglass' },
];

const activeView = ref('settings');

const components = {
  settings: RecoverySettings,
  analytics: AnalyticsPage,
  carts: CartsPage,
  queue: QueuePage,
};
</script>

<template>
  <div class="py-2">
    <!-- Sub-view nav -->
    <nav class="mb-2 flex w-fit max-w-full flex-wrap overflow-hidden rounded-[8px] bg-[#e7edf5] p-0.5">
      <button v-for="v in views" :key="v.id" type="button"
        class="flex cursor-pointer items-center gap-2 rounded-none px-4 py-2 text-[13px] font-semibold transition first:rounded-l-[8px] last:rounded-r-[8px]"
        :class="activeView === v.id ? 'bg-primary text-white' : 'bg-transparent text-slate-600 hover:bg-[#d0dce9]'"
        @click="activeView = v.id">
        <BoxIcon :name="v.icon" class="h-4 w-4" />
        <span>{{ v.label }}</span>
      </button>
    </nav>

    <component :is="components[activeView]" />
  </div>
</template>
