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
import EmailProviders from '../../components/settings/EmailProviders.vue';
import FontsManager from '../../components/settings/FontsManager.vue';
import RecoverySettings from '../../components/settings/RecoverySettings.vue';

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
  'fonts-manager': FontsManager,
  'email-providers': EmailProviders,
  'recovery-settings': RecoverySettings,
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
        <BoxIcon name="star" type="solid" class="h-3 w-3" />
        Pro
      </span>
    </header>

    <p class="mb-0 mt-0 text-[14px] leading-6 text-slate-600">
      Configure abaixo as opções da finalização de compra do WooCommerce. Se precisar de ajuda para configurar, acesse nossa
      <a
        v-if="store.runtime?.docs_link"
        :href="store.runtime.docs_link"
        target="_blank"
        class="font-semibold text-primary underline underline-offset-4"
      >Central de ajuda</a>
    </p>

    <nav class="mt-8 flex w-fit max-w-full flex-wrap overflow-hidden rounded-[8px] bg-[#e7edf5] p-0.5">
      <button
        v-for="tab in store.schema"
        :key="tab.id"
        type="button"
        class="flexify-tab flex min-w-[130px] cursor-pointer items-center justify-center gap-2 rounded-none px-5 py-4 text-[13px] font-semibold uppercase tracking-wide transition first:rounded-l-[8px] last:rounded-r-[8px]"
        :class="activeTab === tab.id ? 'active bg-primary text-white shadow-sm' : 'bg-transparent text-slate-600 hover:bg-[#d0dce9] hover:text-slate-800'"
        @click="selectTab(tab.id)"
      >
        <BoxIcon v-if="tab.icon" :name="tab.icon" class="h-[18px] w-[18px] shrink-0" />
        <span>{{ tab.title }}</span>
      </button>
    </nav>

    <main v-if="currentTab" class="mt-6 overflow-hidden rounded-[8px] bg-white shadow-[0_1px_0_rgba(0,0,0,0.02)] ring-1 ring-slate-100">
      <div class="px-10 py-4">
        <div
          v-for="(card, index) in currentTab.cards"
          :key="card.id"
          :class="index > 0 ? 'border-t border-slate-100' : ''"
        >
          <component
            :is="customComponents[card.component]"
            v-if="card.component && customComponents[card.component]"
            :tab-id="currentTab.id"
          />

          <div v-if="Array.isArray(card.fields) && card.fields.length">
            <FieldRow v-for="field in card.fields" :key="field.key" :field="field" />
          </div>
        </div>
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

<style scoped>
.flexify-tab {
  border: none;
  border-left: 1px solid #dbdee1;
}

.flexify-tab:first-child {
  border-left: none;
}
</style>
