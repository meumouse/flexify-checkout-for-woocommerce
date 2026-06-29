<script setup>
import { computed, ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';
import ModalDialog from '../modals/ModalDialog.vue';
import ProUpsellModal from '../modals/ProUpsellModal.vue';
import ToggleSwitch from '../toggles/ToggleSwitch.vue';

const props = defineProps({
  // When true, render the data-tracking platforms in their own block, visually
  // separated from the installable apps/addons (used on the Aplicativos page).
  grouped: { type: Boolean, default: false },
});

const store = useSettingsStore();

const cards = computed(() => store.integrations);

// Upsell shown when a locked Pro integration is interacted with.
const proModalOpen = ref(false);

// Sections drive the layout: a single unlabeled group by default, or two
// labeled groups (Rastreamento / Aplicativos) when grouped is enabled.
const sections = computed(() => {
  const all = cards.value || [];

  if (!props.grouped) {
    return [{ key: 'all', title: '', cards: all }];
  }

  return [
    { key: 'tracking', title: 'Rastreamento', cards: all.filter((card) => card.type === 'tracking') },
    { key: 'apps', title: 'Aplicativos', cards: all.filter((card) => card.type !== 'tracking') },
  ].filter((section) => section.cards.length);
});

// --- Tracking settings modal ---

const trackingOpen = ref(false);

const TRACKING_PLATFORMS = [
  {
    key: 'ga4',
    label: 'GA4',
    description: 'Configure os parâmetros do Google Analytics 4 para envio de eventos de checkout.',
    fields: [
      { key: 'measurement_id', label: 'Measurement ID', placeholder: 'G-XXXXXXXXXX' },
      { key: 'api_secret', label: 'API Secret', placeholder: '' },
    ],
  },
  {
    key: 'google_ads',
    label: 'Google Ads',
    description: 'Defina os parâmetros de conversão para envio dos eventos ao Google Ads.',
    fields: [
      { key: 'conversion_id', label: 'Conversion ID', placeholder: 'AW-123456789' },
      { key: 'conversion_label', label: 'Conversion Label', placeholder: '' },
    ],
  },
  {
    key: 'meta',
    label: 'Meta',
    description: 'Configure o Pixel e o token da Conversions API para envio dos eventos.',
    fields: [
      { key: 'pixel_id', label: 'Pixel ID', placeholder: '' },
      { key: 'access_token', label: 'Access Token', placeholder: '' },
      { key: 'test_event_code', label: 'Test Event Code (opcional)', placeholder: '' },
    ],
  },
];

const TRACKING_EVENTS = [
  { key: 'fc_begin_checkout', label: 'Checkout iniciado', description: 'Disparado quando o cliente entra no checkout.' },
  { key: 'fc_add_shipping_info', label: 'Informações de entrega', description: 'Disparado quando o cliente seleciona ou altera o método de entrega.' },
  { key: 'fc_add_payment_info', label: 'Informações de pagamento', description: 'Disparado quando o cliente seleciona o método de pagamento e envia o pedido.' },
  { key: 'fc_purchase', label: 'Compra', description: 'Disparado quando o pedido é concluído (página de obrigado e/ou status pago).' },
];

const tracking = computed(() => {
  const settings = store.settings?.tracking_integrations;

  return settings && typeof settings === 'object' ? settings : {};
});

const routes = computed(() => {
  const settings = store.settings?.tracking_routes;

  return settings && typeof settings === 'object' ? settings : {};
});

function updateTracking(path, value) {
  const next = JSON.parse(JSON.stringify(tracking.value || {}));
  let cursor = next;

  for (let index = 0; index < path.length - 1; index += 1) {
    if (!cursor[path[index]] || typeof cursor[path[index]] !== 'object') {
      cursor[path[index]] = {};
    }

    cursor = cursor[path[index]];
  }

  cursor[path[path.length - 1]] = value;
  store.setSetting('tracking_integrations', next);
}

function updateRoute(eventKey, platformKey, value) {
  const next = JSON.parse(JSON.stringify(routes.value || {}));

  if (!next[eventKey] || typeof next[eventKey] !== 'object') {
    next[eventKey] = {};
  }

  next[eventKey][platformKey] = value;
  store.setSetting('tracking_routes', next);
}

// --- Module actions ---

const busyModule = ref('');

async function installModule(card) {
  busyModule.value = card.id;

  try {
    await store.installModule(card.slug, card.download_url);
  } finally {
    busyModule.value = '';
  }
}

async function activateModule(card) {
  busyModule.value = card.id;

  try {
    await store.activateModule(card.slug);
  } finally {
    busyModule.value = '';
  }
}

// --- Per-app settings modals ---
//
// Flexify settings that belong conceptually to an integration are configured
// directly from its card (instead of a dedicated settings tab). The values live
// in store.settings and are persisted with the page's "Salvar alterações".

const APP_CONFIGS = {
  joinotify: {
    button: 'Configurar login WhatsApp',
    title: 'Login via WhatsApp',
    description: 'Permite que o cliente entre/identifique-se com um código enviado pelo WhatsApp (requer o plugin Joinotify ativo).',
    fields: [
      { key: 'enable_whatsapp_login', type: 'toggle', pro: true, label: 'Ativar login via WhatsApp', help: 'Exibe a opção de entrar por código do WhatsApp no checkout. Sem o Joinotify ativo, a opção é ocultada automaticamente.' },
      { key: 'whatsapp_login_sender', type: 'text', label: 'Remetente do WhatsApp (DDI+DDD+número)', placeholder: '5511999999999', help: 'Número remetente registrado no Joinotify que enviará os códigos. Deixe em branco para usar o primeiro remetente configurado.', visibleWhen: { field: 'enable_whatsapp_login', equals: 'yes' } },
    ],
  },
  'google-maps': {
    button: 'Configurar',
    title: 'Busca de endereço (Google Maps)',
    description: 'Pesquisa de endereço com autocompletar do Google Places. Sem a chave configurada, o checkout usa o preenchimento por CEP.',
    fields: [
      { key: 'enable_google_address_search', type: 'toggle', pro: true, label: 'Ativar busca de endereço com Google Maps', help: 'Usa a Places API (New) para sugerir endereços e descobrir o CEP. A chave é usada apenas no servidor (proxy).' },
      { key: 'google_maps_api_key', type: 'text', label: 'Chave da API do Google Maps/Places', help: 'Chave com a "Places API (New)" habilitada. Mantida no servidor — nunca é enviada ao navegador.', visibleWhen: { field: 'enable_google_address_search', equals: 'yes' } },
    ],
  },
};

const appConfigOpen = ref('');
const activeConfig = computed(() => APP_CONFIGS[appConfigOpen.value] || null);

function openConfig(id) {
  appConfigOpen.value = id;
}

function closeConfig() {
  appConfigOpen.value = '';
}

function settingValue(key) {
  return store.settings?.[key];
}

function toggleValue(key) {
  return store.settings?.[key] === 'yes' ? 'yes' : 'no';
}

function updateSetting(key, value) {
  store.setSetting(key, value);
}

function isConfigFieldVisible(field) {
  if (!field.visibleWhen) {
    return true;
  }

  return String(store.settings?.[field.visibleWhen.field]) === String(field.visibleWhen.equals);
}

const inputClass = 'flexify-field-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100';
</script>

<template>
  <div class="px-2 py-6">
    <section v-for="section in sections" :key="section.key" class="mb-8 last:mb-0">
      <h3 v-if="section.title" class="mb-4 mt-0 text-[15px] font-semibold text-brand">{{ section.title }}</h3>

      <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in section.cards"
        :key="card.id"
        class="flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white"
      >
        <!-- Third-party cards keep their legacy HTML rendering -->
        <div v-if="card.type === 'custom'" v-html="card.html" />

        <template v-else>
          <div class="flex items-center justify-center border-b border-slate-200 px-6 py-8">
            <span class="integration-icon flex h-20 items-center justify-center" v-html="card.icon" />
          </div>

          <div class="flex flex-1 flex-col items-center gap-3 px-5 py-6 text-center">
            <h4 class="m-0 text-lg font-semibold leading-snug text-slate-700">{{ card.title }}</h4>

            <button
              v-if="card.pro && !store.isPro"
              type="button"
              class="inline-flex cursor-pointer items-center gap-1 rounded-full border-0 bg-primary-100 px-2.5 py-0.5 text-[10px] font-semibold text-primary transition hover:bg-primary-200"
              aria-label="Recurso Pro — requer uma licença ativa"
              @click="proModalOpen = true"
            >
              <BoxIcon name="crown" type="solid" class="h-2.5 w-2.5" />
              Pro
            </button>

            <p class="m-0 flex-1 text-[13px] leading-relaxed text-ink/80">{{ card.description }}</p>

            <!-- Tracking platforms card -->
            <template v-if="card.type === 'tracking'">
              <ToggleSwitch
                :model-value="tracking.enabled === 'yes' ? 'yes' : 'no'"
                :disabled="!store.isPro"
                aria-label="Ativar rastreamento"
                @update:model-value="(value) => updateTracking(['enabled'], value)"
              />

              <BaseButton variant="outline" size="sm" :disabled="!store.isPro" @click="trackingOpen = true">
                Configurar
              </BaseButton>
            </template>

            <!-- Soon card -->
            <span
              v-else-if="card.type === 'soon'"
              class="inline-flex items-center rounded-full bg-primary-100 px-3 py-1.5 text-sm font-medium text-primary"
            >
              Em breve
            </span>

            <!-- Module cards -->
            <template v-else-if="card.type === 'module'">
              <a
                v-if="card.state === 'active'"
                :href="card.settings_url"
                class="inline-flex items-center rounded-lg border border-primary bg-white px-4 py-1.5 text-xs font-medium text-primary no-underline transition-colors hover:bg-primary hover:text-white"
              >
                Configurar
              </a>

              <BaseButton
                v-else-if="card.state === 'installed'"
                size="sm"
                :loading="busyModule === card.id"
                :disabled="card.pro && !store.isPro"
                @click="activateModule(card)"
              >
                Ativar módulo
              </BaseButton>

              <BaseButton
                v-else
                size="sm"
                :loading="busyModule === card.id"
                :disabled="card.pro && !store.isPro"
                @click="installModule(card)"
              >
                Instalar módulo
              </BaseButton>
            </template>

            <!-- Flexify settings owned by this integration (configured inline) -->
            <BaseButton
              v-if="APP_CONFIGS[card.id]"
              variant="outline"
              size="sm"
              @click="openConfig(card.id)"
            >
              {{ APP_CONFIGS[card.id].button }}
            </BaseButton>
          </div>
        </template>
      </div>
      </div>
    </section>

    <!-- Tracking settings modal -->
    <ModalDialog :open="trackingOpen" title="Configurações de rastreio de dados" size="lg" @close="trackingOpen = false">
      <div class="flex flex-col gap-6">
        <section v-for="platform in TRACKING_PLATFORMS" :key="platform.key">
          <div class="mb-3 flex items-center justify-between gap-4">
            <div>
              <p class="m-0 text-sm font-semibold text-brand">{{ platform.label }}</p>
              <p class="m-0 mt-0.5 text-xs italic text-slate-500">{{ platform.description }}</p>
            </div>

            <ToggleSwitch
              :model-value="tracking[platform.key]?.enabled === 'yes' ? 'yes' : 'no'"
              :aria-label="`Ativar ${platform.label}`"
              @update:model-value="(value) => updateTracking([platform.key, 'enabled'], value)"
            />
          </div>

          <div class="grid gap-3 sm:grid-cols-2">
            <div v-for="field in platform.fields" :key="field.key">
              <label class="mb-1 block text-xs font-medium text-muted">{{ field.label }}</label>
              <input
                :value="tracking[platform.key]?.[field.key] || ''"
                type="text"
                :placeholder="field.placeholder"
                :class="inputClass"
                @input="(event) => updateTracking([platform.key, field.key], event.target.value)"
              />
            </div>
          </div>
        </section>

        <section>
          <p class="mb-1 mt-0 text-sm font-semibold text-brand">Eventos por plataforma</p>
          <p class="m-0 mb-3 text-xs italic text-slate-500">
            Defina quais eventos do checkout serão enviados para cada plataforma. As credenciais acima precisam estar configuradas para o disparo acontecer.
          </p>

          <div class="flex flex-col gap-3">
            <div
              v-for="event in TRACKING_EVENTS"
              :key="event.key"
              class="flex flex-col gap-2 rounded-xl border border-slate-200 p-3 sm:flex-row sm:items-center sm:justify-between"
            >
              <div>
                <p class="m-0 text-sm font-medium text-brand">{{ event.label }}</p>
                <p class="m-0 mt-0.5 text-xs italic text-slate-500">{{ event.description }}</p>
                <code class="text-[10px] text-muted">{{ event.key }}</code>
              </div>

              <div class="flex shrink-0 items-center gap-4">
                <label
                  v-for="platform in TRACKING_PLATFORMS"
                  :key="platform.key"
                  class="flex items-center gap-1.5 text-xs text-ink"
                >
                  <ToggleSwitch
                    :model-value="routes[event.key]?.[platform.key] === 'yes' ? 'yes' : 'no'"
                    :aria-label="`${event.label} - ${platform.label}`"
                    @update:model-value="(value) => updateRoute(event.key, platform.key, value)"
                  />
                  {{ platform.label }}
                </label>
              </div>
            </div>
          </div>
        </section>

        <p class="m-0 text-xs italic text-slate-500">
          As alterações deste painel são aplicadas ao clicar em "Salvar alterações" no rodapé da página.
        </p>
      </div>

      <template #footer>
        <div class="flex justify-end">
          <BaseButton variant="secondary" @click="trackingOpen = false">Fechar</BaseButton>
        </div>
      </template>
    </ModalDialog>

    <!-- Per-app settings modal -->
    <ModalDialog :open="!!activeConfig" :title="activeConfig?.title || ''" @close="closeConfig">
      <div v-if="activeConfig" class="flex flex-col gap-5">
        <p class="m-0 text-xs italic text-slate-500">{{ activeConfig.description }}</p>

        <div v-for="field in activeConfig.fields" v-show="isConfigFieldVisible(field)" :key="field.key">
          <template v-if="field.type === 'toggle'">
            <div class="flex items-center justify-between gap-4">
              <div>
                <p class="m-0 text-sm font-medium text-brand">{{ field.label }}</p>
                <p v-if="field.help" class="m-0 mt-0.5 text-xs italic text-slate-500">{{ field.help }}</p>
              </div>

              <ToggleSwitch
                :model-value="toggleValue(field.key)"
                :disabled="field.pro && !store.isPro"
                :aria-label="field.label"
                @update:model-value="(value) => updateSetting(field.key, value)"
              />
            </div>
          </template>

          <template v-else>
            <label class="mb-1 block text-xs font-medium text-muted">{{ field.label }}</label>
            <input
              :value="settingValue(field.key) || ''"
              type="text"
              :placeholder="field.placeholder || ''"
              :class="inputClass"
              @input="(event) => updateSetting(field.key, event.target.value)"
            />
            <p v-if="field.help" class="m-0 mt-1 text-xs italic text-slate-500">{{ field.help }}</p>
          </template>
        </div>

        <p class="m-0 text-xs italic text-slate-500">
          As alterações deste painel são aplicadas ao clicar em "Salvar alterações" no rodapé da página.
        </p>
      </div>

      <template #footer>
        <div class="flex justify-end">
          <BaseButton variant="secondary" @click="closeConfig">Fechar</BaseButton>
        </div>
      </template>
    </ModalDialog>

    <ProUpsellModal :open="proModalOpen" @close="proModalOpen = false" />
  </div>
</template>

<style scoped>
.integration-icon :deep(svg) {
  max-height: 80px;
  max-width: 160px;
  width: auto;
  height: auto;
}
</style>
