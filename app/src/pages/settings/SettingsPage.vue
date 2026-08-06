<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import FieldRow from '../../components/fields/FieldRow.vue';
import BaseButton from '../../components/buttons/BaseButton.vue';
import ToastStack from '../../components/toasts/ToastStack.vue';
import PageHeader from '../../components/layout/PageHeader.vue';
import LicenseManager from '../../components/settings/LicenseManager.vue';
import SystemStatus from '../../components/settings/SystemStatus.vue';
import AboutActions from '../../components/settings/AboutActions.vue';
import ThemePicker from '../../components/settings/ThemePicker.vue';
import ConditionsManager from '../../components/settings/ConditionsManager.vue';
import CheckoutBuilderManager from '../../components/settings/CheckoutBuilderManager.vue';
import EmailProviders from '../../components/settings/EmailProviders.vue';
import FontsManager from '../../components/settings/FontsManager.vue';
import RecoverySettings from '../../components/settings/RecoverySettings.vue';
import WebhooksManager from '../../components/settings/WebhooksManager.vue';
import LogsViewer from '../../components/settings/LogsViewer.vue';
import SetupWizardModal from '../../components/wizard/SetupWizardModal.vue';
import { MagicWand } from '@boxicons/vue';

const props = defineProps({
  bootstrap: { type: Object, default: () => ({}) },
});

const store = useSettingsStore();

store.hydrate(props.bootstrap);

const activeTab = ref('');
const wizardOpen = ref(false);

const tabsScroller = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);

function updateScrollIndicators() {
  const el = tabsScroller.value;

  if (!el) {
    return;
  }

  const maxScroll = el.scrollWidth - el.clientWidth;

  canScrollLeft.value = el.scrollLeft > 1;
  canScrollRight.value = el.scrollLeft < maxScroll - 1;
}

function scrollTabs(direction) {
  const el = tabsScroller.value;

  if (!el) {
    return;
  }

  el.scrollBy({ left: direction * Math.max(el.clientWidth * 0.7, 160), behavior: 'smooth' });
}

let resizeObserver = null;

onMounted(() => {
  const fromQuery = new URLSearchParams(window.location.search).get('tab') || '';
  const validTab = store.schema.find((tab) => tab.id === fromQuery);

  activeTab.value = validTab ? validTab.id : store.schema[0]?.id || '';

  // Open the setup wizard automatically on first run.
  if (store.runtime?.needs_setup_wizard) {
    wizardOpen.value = true;
  }

  nextTick(updateScrollIndicators);

  if (typeof ResizeObserver !== 'undefined' && tabsScroller.value) {
    resizeObserver = new ResizeObserver(updateScrollIndicators);
    resizeObserver.observe(tabsScroller.value);
  }
});

onBeforeUnmount(() => {
  if (resizeObserver) {
    resizeObserver.disconnect();
    resizeObserver = null;
  }
});

const currentTab = computed(() => store.schema.find((tab) => tab.id === activeTab.value) || null);

watch(activeTab, (id) => {
  nextTick(() => {
    const el = tabsScroller.value?.querySelector(`[data-tab-id="${id}"]`);

    if (el) {
      el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
    }

    updateScrollIndicators();
  });
});

const customComponents = {
  'license-manager': LicenseManager,
  'system-status': SystemStatus,
  'about-actions': AboutActions,
  'theme-picker': ThemePicker,
  'conditions-manager': ConditionsManager,
  'checkout-builder': CheckoutBuilderManager,
  'fonts-manager': FontsManager,
  'email-providers': EmailProviders,
  'recovery-settings': RecoverySettings,
  'webhooks-manager': WebhooksManager,
  'logs-viewer': LogsViewer,
};

function selectTab(tabId) {
  activeTab.value = tabId;

  // Keep the wp-admin ?page= param and any other query, just swap ?tab=.
  const url = new URL(window.location.href);
  url.searchParams.set('tab', tabId);
  url.hash = '';
  window.history.replaceState(window.history.state, '', url);
}
</script>

<template>
  <div class="flexify-settings-app-shell pb-8 pr-4">
    <PageHeader title="Configurações">
      <template #badge>
        <span
          v-if="store.isPro"
          class="inline-flex items-center gap-1 rounded-full bg-primary-100 px-2.5 py-1 text-[13px] font-semibold text-primary"
        >
          <BoxIcon name="crown" type="solid" class="h-3 w-3" />
          Pro
        </span>
      </template>

      <template #description>
        Configure abaixo as opções da finalização de compra do WooCommerce. Se precisar de ajuda para configurar, acesse nossa
        <a
          v-if="store.runtime?.docs_link"
          :href="store.runtime.docs_link"
          target="_blank"
          rel="noreferrer"
          class="font-semibold text-primary underline underline-offset-4"
        >Central de ajuda</a>
      </template>

      <template #actions>
        <button
          type="button"
          class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-primary-200 bg-white px-3 py-2 text-[13px] font-semibold text-primary transition hover:bg-primary-50"
          @click="wizardOpen = true"
        >
          <MagicWand class="h-4 w-4" />
          Assistente de configuração
        </button>
      </template>
    </PageHeader>

    <!-- The tab strip hugs its content and only scrolls once it runs out of room. -->
    <div class="relative mt-10 inline-flex w-auto max-w-full">
      <!-- Indicador / botão de rolagem à esquerda -->
      <transition name="fade">
        <button
          v-if="canScrollLeft"
          type="button"
          aria-label="Rolar abas para a esquerda"
          class="absolute left-0 top-1/2 z-20 -translate-y-1/2 flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-md transition hover:text-primary"
          @click="scrollTabs(-1)"
        >
          <BoxIcon name="chevron-left" class="h-5 w-5" />
        </button>
      </transition>

      <!-- Gradiente de borda esquerda -->
      <div
        v-show="canScrollLeft"
        class="pointer-events-none absolute inset-y-0 left-0 z-10 w-10 rounded-l-[8px] bg-gradient-to-r from-[#e7edf5] to-transparent"
      />

      <nav
        ref="tabsScroller"
        class="flexify-tabs-scroller flex max-w-full flex-nowrap overflow-x-auto rounded-[8px] bg-[#e7edf5] p-0.5"
        @scroll.passive="updateScrollIndicators"
      >
        <button
          v-for="tab in store.schema"
          :key="tab.id"
          type="button"
          :data-tab-id="tab.id"
          class="flexify-tab flex min-w-[165px] shrink-0 cursor-pointer items-center justify-center gap-2 rounded-none px-6 py-5 text-[15px] font-semibold uppercase tracking-wide transition first:rounded-l-[8px] last:rounded-r-[8px]"
          :class="activeTab === tab.id ? 'active bg-primary text-white shadow-sm' : 'bg-transparent text-slate-600 hover:bg-[#d0dce9] hover:text-slate-800'"
          @click="selectTab(tab.id)"
        >
          <BoxIcon v-if="tab.icon" :name="tab.icon" class="h-[22px] w-[22px] shrink-0" />
          <span>{{ tab.title }}</span>
        </button>
      </nav>

      <!-- Gradiente de borda direita -->
      <div
        v-show="canScrollRight"
        class="pointer-events-none absolute inset-y-0 right-0 z-10 w-10 rounded-r-[8px] bg-gradient-to-l from-[#e7edf5] to-transparent"
      />

      <!-- Indicador / botão de rolagem à direita -->
      <transition name="fade">
        <button
          v-if="canScrollRight"
          type="button"
          aria-label="Rolar abas para a direita"
          class="absolute right-0 top-1/2 z-20 -translate-y-1/2 flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-md transition hover:text-primary"
          @click="scrollTabs(1)"
        >
          <BoxIcon name="chevron-right" class="h-5 w-5" />
        </button>
      </transition>
    </div>

    <main v-if="currentTab" class="mt-8 rounded-[8px] bg-white shadow-[0_1px_0_rgba(0,0,0,0.02)] ring-1 ring-slate-100">
      <div class="px-10 py-12">
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

          <!-- Fields are laid out as a label/control table so every row of every
               tab shares the same column grid. -->
          <table
            v-if="Array.isArray(card.fields) && card.fields.length"
            class="flexify-fields-table w-full table-fixed border-collapse text-left"
          >
            <colgroup>
              <col class="w-[444px]" />
              <col />
            </colgroup>

            <tbody>
              <FieldRow v-for="field in card.fields" :key="field.key" :field="field" />
            </tbody>
          </table>
        </div>
      </div>

      <div class="sticky bottom-0 inset-x-0 z-10 rounded-b-[8px] border-t border-black/10 bg-white/80 px-10 py-6 backdrop-blur-[5px]">
        <BaseButton size="lg" :disabled="!store.dirty" :loading="store.saving" @click="store.save()">
          <BoxIcon v-if="!store.saving" name="save" class="h-[1.1rem] w-[1.1rem]" />
          Salvar alterações
        </BaseButton>
      </div>
    </main>

    <SetupWizardModal :open="wizardOpen" @close="wizardOpen = false" />

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

/* Rolagem horizontal sem barra visível */
.flexify-tabs-scroller {
  scrollbar-width: none;
  -ms-overflow-style: none;
  scroll-behavior: smooth;
}

.flexify-tabs-scroller::-webkit-scrollbar {
  display: none;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
