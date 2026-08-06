<script setup>
/**
 * Aplicativos page.
 *
 * Standalone route that hosts the integrations/addons grid, promoted out of the
 * former "Integrações" settings tab into its own top-level menu item. The data
 * tracking platforms (GA4, Google Ads, Meta) render in a separate block at the
 * top, visually distinct from the installable addons.
 *
 * @since 6.0.0
 */
import { useSettingsStore } from '../../stores/useSettingsStore';
import IntegrationsList from '../../components/settings/IntegrationsList.vue';
import ToastStack from '../../components/toasts/ToastStack.vue';
import BaseButton from '../../components/buttons/BaseButton.vue';
import PageHeader from '../../components/layout/PageHeader.vue';

const store = useSettingsStore();
</script>

<template>
  <div class="flexify-settings-app-shell pb-8 pr-4">
    <PageHeader
      title="Aplicativos"
      description="Conecte o Flexify Checkout às plataformas de rastreamento de dados e ative os aplicativos e integrações disponíveis."
    >
      <template #badge>
        <span
          v-if="store.isPro"
          class="inline-flex items-center gap-1 rounded-full bg-primary-100 px-2.5 py-1 text-[13px] font-semibold text-primary"
        >
          <BoxIcon name="crown" type="solid" class="h-3 w-3" />
          Pro
        </span>
      </template>
    </PageHeader>

    <main class="mt-8 overflow-hidden rounded-[8px] bg-white shadow-[0_1px_0_rgba(0,0,0,0.02)] ring-1 ring-slate-100">
      <div class="px-6 py-2">
        <IntegrationsList grouped />
      </div>

      <div class="sticky bottom-0 inset-x-0 z-10 border-t border-black/10 bg-white/80 px-10 py-5 backdrop-blur-[5px]">
        <BaseButton :disabled="!store.dirty" :loading="store.saving" @click="store.save()">
          <BoxIcon v-if="!store.saving" name="save" class="h-[1.1rem] w-[1.1rem]" />
          Salvar alterações
        </BaseButton>
      </div>
    </main>

    <ToastStack />
  </div>
</template>
