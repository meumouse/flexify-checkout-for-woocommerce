<script setup>
import { computed, reactive, ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';
import ModalDialog from '../modals/ModalDialog.vue';
import ToggleSwitch from '../toggles/ToggleSwitch.vue';

const store = useSettingsStore();

const cards = computed(() => store.integrations);

// --- Tracking settings modal ---

const trackingOpen = ref(false);

const TRACKING_PLATFORMS = [
  {
    key: 'ga4',
    label: 'GA4',
    fields: [
      { key: 'measurement_id', label: 'Measurement ID', placeholder: 'G-XXXXXXXXXX' },
      { key: 'api_secret', label: 'API Secret', placeholder: '' },
    ],
  },
  {
    key: 'google_ads',
    label: 'Google Ads',
    fields: [
      { key: 'conversion_id', label: 'Conversion ID', placeholder: 'AW-123456789' },
      { key: 'conversion_label', label: 'Conversion Label', placeholder: '' },
    ],
  },
  {
    key: 'meta',
    label: 'Meta',
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
  { key: 'fc_purchase', label: 'Compra', description: 'Disparado quando o pedido é concluído.' },
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
  <div>
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="card in cards"
        :key="card.id"
        class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5"
      >
        <!-- Third-party cards keep their legacy HTML rendering -->
        <div v-if="card.type === 'custom'" v-html="card.html" />

        <template v-else>
          <div class="mb-2 flex items-center gap-2">
            <h4 class="m-0 text-sm font-semibold text-ink">{{ card.title }}</h4>

            <span
              v-if="card.pro && !store.isPro"
              class="inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary"
            >
              Pro
            </span>
          </div>

          <p class="m-0 mb-4 flex-1 text-xs leading-relaxed text-muted">{{ card.description }}</p>

          <!-- Tracking platforms card -->
          <div v-if="card.type === 'tracking'" class="flex items-center justify-between gap-3">
            <ToggleSwitch
              :model-value="tracking.enabled === 'yes' ? 'yes' : 'no'"
              :disabled="!store.isPro"
              aria-label="Ativar rastreamento"
              @update:model-value="(value) => updateTracking(['enabled'], value)"
            />

            <BaseButton variant="secondary" :disabled="!store.isPro" @click="trackingOpen = true">
              Configurar
            </BaseButton>
          </div>

          <!-- Soon card -->
          <span
            v-else-if="card.type === 'soon'"
            class="inline-flex w-fit items-center rounded-full bg-primary-100 px-3 py-1 text-xs font-semibold text-primary"
          >
            Em breve
          </span>

          <!-- Module cards -->
          <div v-else-if="card.type === 'module'">
            <a
              v-if="card.state === 'active'"
              :href="card.settings_url"
              class="inline-flex items-center rounded-lg border border-primary-200 bg-transparent px-4 py-2 text-sm font-medium text-primary no-underline transition-colors hover:bg-primary-50"
            >
              Configurar
            </a>

            <BaseButton
              v-else-if="card.state === 'installed'"
              :loading="busyModule === card.id"
              :disabled="card.pro && !store.isPro"
              @click="activateModule(card)"
            >
              Ativar módulo
            </BaseButton>

            <BaseButton
              v-else
              :loading="busyModule === card.id"
              :disabled="card.pro && !store.isPro"
              @click="installModule(card)"
            >
              Instalar módulo
            </BaseButton>
          </div>
        </template>
      </div>
    </div>

    <!-- Tracking settings modal -->
    <ModalDialog :open="trackingOpen" title="Configurações de rastreio de dados" size="lg" @close="trackingOpen = false">
      <div class="flex flex-col gap-6">
        <section v-for="platform in TRACKING_PLATFORMS" :key="platform.key">
          <div class="mb-3 flex items-center justify-between gap-4">
            <p class="m-0 text-sm font-semibold text-ink">{{ platform.label }}</p>

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
          <p class="mb-1 mt-0 text-sm font-semibold text-ink">Eventos por plataforma</p>
          <p class="m-0 mb-3 text-xs text-muted">
            Defina quais eventos do checkout serão enviados para cada plataforma.
          </p>

          <div class="flex flex-col gap-3">
            <div
              v-for="event in TRACKING_EVENTS"
              :key="event.key"
              class="flex flex-col gap-2 rounded-xl border border-gray-200 p-3 sm:flex-row sm:items-center sm:justify-between"
            >
              <div>
                <p class="m-0 text-sm font-medium text-ink">{{ event.label }}</p>
                <p class="m-0 mt-0.5 text-xs text-muted">{{ event.description }}</p>
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

        <p class="m-0 text-xs text-muted">
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
