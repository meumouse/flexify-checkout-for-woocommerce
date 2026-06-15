<script setup>
import { computed, reactive, ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';
import ModalDialog from '../modals/ModalDialog.vue';
import ToggleSwitch from '../toggles/ToggleSwitch.vue';

const props = defineProps({
  // When true, render the data-tracking platforms in their own block, visually
  // separated from the installable apps/addons (used on the Aplicativos page).
  grouped: { type: Boolean, default: false },
});

const store = useSettingsStore();

const cards = computed(() => store.integrations);

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

const inputClass = 'flexify-field-input w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100';
</script>

<template>
  <div class="px-2 py-6">
    <section v-for="section in sections" :key="section.key" class="mb-8 last:mb-0">
      <h3 v-if="section.title" class="mb-4 mt-0 text-[15px] font-semibold text-brand">{{ section.title }}</h3>

      <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in section.cards"
        :key="card.id"
        class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white"
      >
        <!-- Third-party cards keep their legacy HTML rendering -->
        <div v-if="card.type === 'custom'" v-html="card.html" />

        <template v-else>
          <div class="flex items-center justify-center border-b border-gray-200 px-6 py-8">
            <span class="integration-icon flex h-20 items-center justify-center" v-html="card.icon" />
          </div>

          <div class="flex flex-1 flex-col items-center gap-3 px-5 py-6 text-center">
            <h4 class="m-0 text-base font-semibold leading-snug text-brand">{{ card.title }}</h4>

            <span
              v-if="card.pro && !store.isPro"
              class="inline-flex items-center gap-1 rounded-full bg-primary-100 px-2.5 py-0.5 text-[10px] font-semibold text-primary"
            >
              Pro
            </span>

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
              <p class="m-0 mt-0.5 text-xs italic text-gray-500">{{ platform.description }}</p>
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
          <p class="m-0 mb-3 text-xs italic text-gray-500">
            Defina quais eventos do checkout serão enviados para cada plataforma. As credenciais acima precisam estar configuradas para o disparo acontecer.
          </p>

          <div class="flex flex-col gap-3">
            <div
              v-for="event in TRACKING_EVENTS"
              :key="event.key"
              class="flex flex-col gap-2 rounded-xl border border-gray-200 p-3 sm:flex-row sm:items-center sm:justify-between"
            >
              <div>
                <p class="m-0 text-sm font-medium text-brand">{{ event.label }}</p>
                <p class="m-0 mt-0.5 text-xs italic text-gray-500">{{ event.description }}</p>
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

        <p class="m-0 text-xs italic text-gray-500">
          As alterações deste painel são aplicadas ao clicar em "Salvar alterações" no rodapé da página.
        </p>
      </div>

      <template #footer>
        <div class="flex justify-end">
          <BaseButton variant="secondary" @click="trackingOpen = false">Fechar</BaseButton>
        </div>
      </template>
    </ModalDialog>
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
