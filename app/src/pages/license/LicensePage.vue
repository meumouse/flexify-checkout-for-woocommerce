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

const store = useSettingsStore();
</script>

<template>
  <div class="flexify-settings-app-shell pb-8 pr-4">
    <header class="mb-2 mt-2 flex flex-wrap items-center gap-3">
      <svg class="h-9 w-9" viewBox="0 0 1080 1080" xmlns="http://www.w3.org/2000/svg"><g><path fill="#141D26" d="M513.96,116.38c-234.22,0-424.07,189.86-424.07,424.07c0,234.21,189.86,424.08,424.07,424.08 c234.21,0,424.07-189.86,424.07-424.08C938.03,306.25,748.17,116.38,513.96,116.38z M685.34,542.48 c-141.76,0.37-257.11,117.68-257.41,259.44h-88.21c0-191.79,153.83-347.41,345.62-347.41V542.48z M685.34,365.84 c-141.76,0.2-266.84,69.9-346.06,176.13V410.6c91.73-82.48,212.64-133.1,346.06-133.1V365.84z"/></g></svg>

      <h1 class="m-0 text-xl font-semibold text-brand">Licença</h1>

      <span
        v-if="store.isPro"
        class="inline-flex items-center gap-1 rounded-full bg-primary-100 px-2.5 py-1 text-xs font-semibold text-primary"
      >
        <BoxIcon name="crown" type="solid" class="h-3 w-3" />
        Pro
      </span>
    </header>

    <p class="mb-0 mt-0 text-[14px] leading-6 text-slate-600">
      Gerencie a sua licença do Flexify Checkout: ative, consulte o status e os detalhes do plano.
    </p>

    <main class="mt-6 overflow-hidden rounded-[8px] bg-white shadow-[0_1px_0_rgba(0,0,0,0.02)] ring-1 ring-slate-100">
      <div class="px-8 py-4">
        <LicenseManager />
      </div>
    </main>
  </div>
</template>
