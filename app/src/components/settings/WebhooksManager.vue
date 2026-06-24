<script setup>
/**
 * Global checkout webhooks manager (Configurações > Webhooks).
 *
 * Lists every checkout event grouped by category (Pedido, Checkout,
 * Rastreamento, Recuperação, Conta) and lets the admin register POST endpoints
 * per event, with custom headers and a send-test button. Backed by its own REST
 * endpoint (flexify-checkout/v1/webhooks/settings), independent from the main
 * settings option.
 *
 * @since 6.0.0
 */
import { ref, reactive, computed, onMounted } from 'vue';
import { apiGet, apiPost } from '../../services/api';
import BoxIcon from '../icons/BoxIcon.vue';

const loading = ref(true);
const saving = ref(false);
const error = ref('');
const saved = ref(false);
const activeCategory = ref('');

// Ordered list of { category, label, events: [{ key, label, description }] }.
const groups = ref([]);

// Per-event endpoint config: { event_key: [ { enabled, url, headers: [{name,value}] } ] }.
const config = reactive({});

// Per-endpoint test feedback keyed by `${eventKey}:${index}`.
const testState = reactive({});

const activeGroup = computed(() => groups.value.find((g) => g.category === activeCategory.value) || null);

function normalizeHeaders(headers) {
  if (Array.isArray(headers)) {
    return headers
      .filter((h) => h && typeof h === 'object')
      .map((h) => ({ name: String(h.name || ''), value: String(h.value || '') }));
  }

  // Legacy data may store headers as an object map or empty object.
  if (headers && typeof headers === 'object') {
    return Object.keys(headers).map((name) => ({ name, value: String(headers[name] || '') }));
  }

  return [];
}

function applyServerSettings(s) {
  Object.keys(config).forEach((k) => delete config[k]);

  if (!s || typeof s !== 'object') {
    return;
  }

  Object.keys(s).forEach((eventKey) => {
    const list = Array.isArray(s[eventKey]) ? s[eventKey] : [];

    config[eventKey] = list.map((hook) => ({
      enabled: hook && hook.enabled === 'no' ? 'no' : 'yes',
      url: hook && hook.url ? String(hook.url) : '',
      headers: normalizeHeaders(hook && hook.headers),
    }));
  });
}

function endpointsFor(eventKey) {
  if (!Array.isArray(config[eventKey])) {
    config[eventKey] = [];
  }

  return config[eventKey];
}

function addEndpoint(eventKey) {
  endpointsFor(eventKey).push({ enabled: 'yes', url: '', headers: [] });
}

function removeEndpoint(eventKey, index) {
  config[eventKey].splice(index, 1);
}

function toggleEndpoint(hook) {
  hook.enabled = hook.enabled === 'yes' ? 'no' : 'yes';
}

function addHeader(hook) {
  if (!Array.isArray(hook.headers)) {
    hook.headers = [];
  }

  hook.headers.push({ name: '', value: '' });
}

function removeHeader(hook, index) {
  hook.headers.splice(index, 1);
}

async function sendTest(eventKey, hook, index) {
  const stateKey = `${eventKey}:${index}`;
  testState[stateKey] = { loading: true, ok: null, message: '' };

  try {
    const data = await apiPost('webhooks/test', {
      event_key: eventKey,
      endpoint: { url: hook.url, headers: hook.headers },
    });

    if (data.status === 'success') {
      testState[stateKey] = { loading: false, ok: true, message: `HTTP ${data.http_code}` };
    } else {
      testState[stateKey] = { loading: false, ok: false, message: data.message || 'Falhou' };
    }
  } catch (e) {
    testState[stateKey] = { loading: false, ok: false, message: 'Erro de rede' };
  }
}

async function load() {
  loading.value = true;
  error.value = '';

  try {
    const data = await apiGet('webhooks/settings');
    groups.value = Array.isArray(data.events_grouped) ? data.events_grouped : [];
    applyServerSettings(data.settings);
    activeCategory.value = groups.value[0]?.category || '';
  } catch (e) {
    error.value = 'Não foi possível carregar os webhooks.';
  } finally {
    loading.value = false;
  }
}

async function save() {
  saving.value = true;
  error.value = '';
  saved.value = false;

  try {
    const data = await apiPost('webhooks/settings', { webhooks: config });
    applyServerSettings(data.settings);
    saved.value = true;
    setTimeout(() => { saved.value = false; }, 3000);
  } catch (e) {
    error.value = 'Não foi possível salvar os webhooks.';
  } finally {
    saving.value = false;
  }
}

onMounted(load);
</script>

<template>
  <div class="px-2 py-4">
    <div v-if="loading" class="skeleton-content" style="width: 100%; height: 360px;"></div>

    <template v-else>
      <div v-if="error" class="mb-4 rounded-[8px] bg-danger/10 px-5 py-3 text-[14px] text-danger" role="alert">{{ error }}</div>

      <p class="mb-4 mt-0 text-[14px] leading-6 text-slate-600">
        Envie uma requisição <strong>POST</strong> em formato JSON para uma URL externa sempre que um evento da finalização de compra ocorrer.
        Cada evento aceita várias URLs e cabeçalhos personalizados.
      </p>

      <!-- Category nav -->
      <nav v-if="groups.length" class="mb-5 flex w-fit max-w-full flex-wrap overflow-hidden rounded-[8px] bg-[#e7edf5] p-0.5">
        <button v-for="g in groups" :key="g.category" type="button"
          class="cursor-pointer rounded-none px-4 py-2 text-[13px] font-semibold transition first:rounded-l-[8px] last:rounded-r-[8px]"
          :class="activeCategory === g.category ? 'bg-primary text-white' : 'bg-transparent text-slate-600 hover:bg-[#d0dce9]'"
          @click="activeCategory = g.category">{{ g.label }}</button>
      </nav>

      <p v-if="!groups.length" class="text-[14px] text-slate-500">Nenhum evento de webhook disponível.</p>

      <!-- Events of the active category -->
      <div v-if="activeGroup" class="grid gap-4">
        <div v-for="ev in activeGroup.events" :key="ev.key" class="rounded-[8px] border border-slate-200 p-4">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="m-0 text-[14px] font-semibold text-brand">{{ ev.label }}</h3>
              <p v-if="ev.description" class="m-0 mt-1 text-[12px] text-slate-500">{{ ev.description }}</p>
              <code class="mt-1 inline-block rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-500">{{ ev.key }}</code>
            </div>
            <button type="button" class="shrink-0 inline-flex items-center gap-1 rounded-[6px] px-2.5 py-1 text-xs font-semibold text-primary hover:bg-primary-100" @click="addEndpoint(ev.key)">
              <BoxIcon name="plus" class="h-4 w-4" /> Adicionar URL
            </button>
          </div>

          <div v-if="endpointsFor(ev.key).length" class="mt-3 grid gap-3">
            <div v-for="(hook, i) in config[ev.key]" :key="i" class="rounded-[8px] bg-slate-50 p-3">
              <div class="flex items-center gap-2">
                <button type="button" role="switch" :aria-checked="hook.enabled === 'yes'"
                  class="relative h-5 w-9 shrink-0 rounded-full transition"
                  :class="hook.enabled === 'yes' ? 'bg-primary' : 'bg-slate-300'"
                  @click="toggleEndpoint(hook)">
                  <span class="absolute top-0.5 h-4 w-4 rounded-full bg-white transition-all" :class="hook.enabled === 'yes' ? 'left-[18px]' : 'left-0.5'"></span>
                </button>
                <input class="flexify-field-input flex-1" type="url" placeholder="https://…" v-model="hook.url" />
                <button type="button" class="shrink-0 rounded-[6px] px-2 py-1 text-xs font-semibold text-primary hover:bg-primary-100 disabled:opacity-50"
                  :disabled="testState[`${ev.key}:${i}`]?.loading || !hook.url"
                  @click="sendTest(ev.key, hook, i)">
                  {{ testState[`${ev.key}:${i}`]?.loading ? 'Testando…' : 'Testar' }}
                </button>
                <button type="button" class="shrink-0 rounded-[6px] px-2 py-1 text-xs font-semibold text-danger hover:bg-danger/10" @click="removeEndpoint(ev.key, i)">
                  <BoxIcon name="trash" class="h-4 w-4" />
                </button>
              </div>

              <p v-if="testState[`${ev.key}:${i}`] && !testState[`${ev.key}:${i}`].loading"
                class="m-0 mt-1.5 pl-11 text-[12px] font-semibold"
                :class="testState[`${ev.key}:${i}`].ok ? 'text-success' : 'text-danger'">
                {{ testState[`${ev.key}:${i}`].ok ? 'Enviado' : 'Falhou' }} — {{ testState[`${ev.key}:${i}`].message }}
              </p>

              <!-- Headers editor -->
              <div class="mt-2 pl-11">
                <div v-if="hook.headers && hook.headers.length" class="grid gap-2">
                  <div v-for="(header, hi) in hook.headers" :key="hi" class="flex items-center gap-2">
                    <input class="flexify-field-input w-1/3" type="text" placeholder="Cabeçalho" v-model="header.name" />
                    <input class="flexify-field-input flex-1" type="text" placeholder="Valor" v-model="header.value" />
                    <button type="button" class="shrink-0 rounded-[6px] px-2 py-1 text-xs text-danger hover:bg-danger/10" @click="removeHeader(hook, hi)">
                      <BoxIcon name="x" class="h-4 w-4" />
                    </button>
                  </div>
                </div>
                <button type="button" class="mt-1 text-[12px] font-semibold text-primary hover:underline" @click="addHeader(hook)">+ Adicionar cabeçalho</button>
              </div>
            </div>
          </div>
          <p v-else class="mt-2 text-[12px] text-slate-400">Nenhuma URL configurada.</p>
        </div>
      </div>

      <!-- Actions -->
      <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4">
        <button type="button" class="rounded-[8px] bg-primary px-5 py-2.5 text-[14px] font-semibold text-white disabled:opacity-50" :disabled="saving" @click="save">
          {{ saving ? 'Salvando…' : 'Salvar webhooks' }}
        </button>
        <span v-if="saved" class="text-[13px] font-semibold text-success">Webhooks salvos!</span>
      </div>
    </template>
  </div>
</template>
