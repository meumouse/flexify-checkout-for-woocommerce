<script setup>
import { computed, onMounted, ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import SectionCard from '../../components/cards/SectionCard.vue';
import FieldRow from '../../components/fields/FieldRow.vue';
import BaseButton from '../../components/buttons/BaseButton.vue';
import ToastStack from '../../components/toasts/ToastStack.vue';
import LicenseManager from '../../components/settings/LicenseManager.vue';
import SystemStatus from '../../components/settings/SystemStatus.vue';
import LegacyFallbackCard from '../../components/settings/LegacyFallbackCard.vue';
import FieldsManager from '../../components/settings/FieldsManager.vue';
import ConditionsManager from '../../components/settings/ConditionsManager.vue';
import IntegrationsList from '../../components/settings/IntegrationsList.vue';

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
  'fields-manager': FieldsManager,
  'conditions-manager': ConditionsManager,
  'integrations-list': IntegrationsList,
};

function selectTab(tabId) {
  activeTab.value = tabId;
  window.location.hash = tabId;
}

function confirmReset() {
  if (window.confirm('Tem certeza que deseja redefinir todas as configurações para o padrão? Essa ação não pode ser desfeita.')) {
    store.reset();
  }
}
</script>

<template>
  <div class="flexify-settings-app-shell pb-24">
    <header class="mb-6 flex flex-wrap items-center gap-3">
      <h1 class="m-0 text-xl font-semibold text-ink">Flexify Checkout para WooCommerce</h1>

      <span
        v-if="store.isPro"
        class="inline-flex items-center rounded-full bg-primary-100 px-3 py-1 text-xs font-semibold text-primary"
      >
        Pro
      </span>

      <a
        v-if="store.runtime?.docs_link"
        :href="store.runtime.docs_link"
        target="_blank"
        class="ml-auto text-sm text-primary-600 no-underline hover:underline"
      >
        Central de ajuda
      </a>
    </header>

    <nav class="mb-6 flex flex-wrap gap-1 rounded-xl bg-white p-1.5 shadow-sm ring-1 ring-gray-200">
      <button
        v-for="tab in store.schema"
        :key="tab.id"
        type="button"
        class="rounded-lg border-0 px-4 py-2 text-sm font-medium transition-colors"
        :class="activeTab === tab.id ? 'bg-primary text-white' : 'bg-transparent text-muted hover:bg-gray-100 hover:text-ink cursor-pointer'"
        @click="selectTab(tab.id)"
      >
        {{ tab.title }}
      </button>
    </nav>

    <main v-if="currentTab" class="flex flex-col gap-5">
      <p v-if="currentTab.description" class="m-0 text-sm text-muted">
        {{ currentTab.description }}
      </p>

      <SectionCard
        v-for="card in currentTab.cards"
        :key="card.id"
        :title="card.title"
        :description="card.description"
      >
        <component
          :is="customComponents[card.component]"
          v-if="card.component && customComponents[card.component]"
          :tab-id="currentTab.id"
        />

        <div v-if="Array.isArray(card.fields) && card.fields.length">
          <FieldRow v-for="field in card.fields" :key="field.key" :field="field" />
        </div>
      </SectionCard>
    </main>

    <footer
      class="fixed bottom-0 left-0 right-0 z-[9999] border-t border-gray-200 bg-white/95 px-6 py-3 backdrop-blur sm:left-[160px]"
    >
      <div class="flex items-center justify-between gap-4">
        <BaseButton variant="secondary" :loading="store.resetting" @click="confirmReset">
          Redefinir configurações
        </BaseButton>

        <div class="flex items-center gap-3">
          <span v-if="store.dirty" class="text-xs text-muted">Há alterações não salvas</span>

          <BaseButton :disabled="!store.dirty" :loading="store.saving" @click="store.save()">
            Salvar alterações
          </BaseButton>
        </div>
      </div>
    </footer>

    <ToastStack />
  </div>
</template>
