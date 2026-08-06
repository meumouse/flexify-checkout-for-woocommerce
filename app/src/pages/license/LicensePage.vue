<script setup>
/**
 * Dedicated License page.
 *
 * Surfaces the license/about content (formerly the "Sobre" settings tab) as a
 * standalone admin page under the dedicated Flexify Checkout menu. State comes
 * from the settings store, hydrated by the App shell.
 *
 * @since 6.0.0
 */
import { useSettingsStore } from '../../stores/useSettingsStore';
import LicenseManager from '../../components/settings/LicenseManager.vue';
import PageHeader from '../../components/layout/PageHeader.vue';

const store = useSettingsStore();
</script>

<template>
  <div class="flexify-settings-app-shell pb-8 pr-4">
    <PageHeader
      title="Licença"
      description="Gerencie a sua licença do Flexify Checkout: ative, consulte o status e os detalhes do plano."
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
      <div class="px-8 py-4">
        <LicenseManager />
      </div>
    </main>
  </div>
</template>
