<script setup>
import { computed, onMounted, ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import FieldRow from '../../components/fields/FieldRow.vue';
import BaseButton from '../../components/buttons/BaseButton.vue';
import ToastStack from '../../components/toasts/ToastStack.vue';
import LicenseManager from '../../components/settings/LicenseManager.vue';
import SystemStatus from '../../components/settings/SystemStatus.vue';
import AboutActions from '../../components/settings/AboutActions.vue';
import ThemePicker from '../../components/settings/ThemePicker.vue';
import FieldsManager from '../../components/settings/FieldsManager.vue';
import ConditionsManager from '../../components/settings/ConditionsManager.vue';
import IntegrationsList from '../../components/settings/IntegrationsList.vue';
import EmailProviders from '../../components/settings/EmailProviders.vue';
import FontsManager from '../../components/settings/FontsManager.vue';

const props = defineProps({
  bootstrap: { type: Object, default: () => ({}) },
});

const store = useSettingsStore();

store.hydrate(props.bootstrap);

const activeTab = ref('');

onMounted(() => {
  const fromHash = window.location.hash.replace('#', '');
  const validTab = store.schema.find((tab) => tab.id === fromHash);

  activeTab.value = validTab ? validTab.id : store.schema[0]?.id || '';
});

const currentTab = computed(() => store.schema.find((tab) => tab.id === activeTab.value) || null);

const customComponents = {
  'license-manager': LicenseManager,
  'system-status': SystemStatus,
  'about-actions': AboutActions,
  'theme-picker': ThemePicker,
  'fields-manager': FieldsManager,
  'conditions-manager': ConditionsManager,
  'integrations-list': IntegrationsList,
  'fonts-manager': FontsManager,
  'email-providers': EmailProviders,
};

function selectTab(tabId) {
  activeTab.value = tabId;
  window.location.hash = tabId;
}
</script>

<template>
  <div class="flexify-settings-app-shell pb-8 pr-4">
    <header class="mb-2 mt-2 flex flex-wrap items-center gap-3">
      <svg class="h-9 w-9" viewBox="0 0 1080 1080" xmlns="http://www.w3.org/2000/svg"><g><path fill="#141D26" d="M513.96,116.38c-234.22,0-424.07,189.86-424.07,424.07c0,234.21,189.86,424.08,424.07,424.08 c234.21,0,424.07-189.86,424.07-424.08C938.03,306.25,748.17,116.38,513.96,116.38z M685.34,542.48 c-141.76,0.37-257.11,117.68-257.41,259.44h-88.21c0-191.79,153.83-347.41,345.62-347.41V542.48z M685.34,365.84 c-141.76,0.2-266.84,69.9-346.06,176.13V410.6c91.73-82.48,212.64-133.1,346.06-133.1V365.84z"/></g></svg>

      <h1 class="m-0 text-xl font-semibold text-brand">Flexify Checkout para WooCommerce</h1>

      <span
        v-if="store.isPro"
        class="inline-flex items-center gap-1 rounded-full bg-primary-100 px-2.5 py-1 text-xs font-semibold text-primary"
      >
        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3Z" /></svg>
        Pro
      </span>
    </header>

    <p class="mb-5 mt-0 text-[13px] text-ink">
      Configure abaixo as opções da finalização de compra do WooCommerce. Se precisar de ajuda para configurar, acesse nossa
      <a
        v-if="store.runtime?.docs_link"
        :href="store.runtime.docs_link"
        target="_blank"
        class="font-medium text-primary underline underline-offset-2"
      >Central de ajuda</a>
    </p>

    <nav class="mb-4 inline-flex max-w-full flex-wrap overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
      <button
        v-for="tab in store.schema"
        :key="tab.id"
        type="button"
        class="flexify-tab inline-flex cursor-pointer items-center gap-2 border-0 border-r border-gray-200 px-6 py-4 text-xs font-semibold uppercase tracking-wide transition-colors last:border-r-0"
        :class="activeTab === tab.id ? 'bg-primary text-white' : 'bg-white text-ink hover:bg-gray-50'"
        @click="selectTab(tab.id)"
      >
        <span v-if="tab.icon" class="flexify-tab-icon" v-html="tab.icon" />
        {{ tab.title }}
      </button>
    </nav>

    <main v-if="currentTab" class="flex flex-col gap-1.5">
      <section
        v-for="(card, index) in currentTab.cards"
        :key="card.id"
        class="bg-white px-8 py-3"
        :class="[index === 0 ? 'rounded-t-2xl' : '', 'shadow-sm']"
      >
        <component
          :is="customComponents[card.component]"
          v-if="card.component && customComponents[card.component]"
          :tab-id="currentTab.id"
        />

        <div v-if="Array.isArray(card.fields) && card.fields.length">
          <FieldRow v-for="field in card.fields" :key="field.key" :field="field" />
        </div>
      </section>

      <section class="rounded-b-2xl bg-white px-8 py-5 shadow-sm">
        <BaseButton :disabled="!store.dirty" :loading="store.saving" @click="store.save()">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M5 21h14a2 2 0 0 0 2-2V8a1 1 0 0 0-.29-.71l-4-4A1 1 0 0 0 16 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2zm10-2H9v-5h6zM13 7h-2V5h2zM5 5h2v4h8V5h.59L19 8.41V19h-2v-5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v5H5z"></path></svg>
          Salvar alterações
        </BaseButton>
      </section>
    </main>

    <ToastStack />
  </div>
</template>

<style scoped>
.flexify-tab-icon :deep(svg) {
  width: 16px;
  height: 16px;
  fill: currentColor;
}
</style>
