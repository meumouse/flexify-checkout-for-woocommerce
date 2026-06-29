<script setup>
/**
 * Advanced logs viewer (Configurações > Sobre).
 *
 * Surfaces the plugin's file-based log (requests, payments, errors, webhooks).
 * The "Ver logs" button is shown only while debug mode is active; the modal
 * lists entries with level/category/search filters, pagination, raw download
 * and a clear action, all backed by the flexify-checkout/v1/admin/logs routes.
 *
 * @since 6.0.0
 */
import { ref, reactive, computed, watch } from 'vue';
import { apiGet, apiDelete } from '../../services/api';
import { useSettingsStore } from '../../stores/useSettingsStore';
import ModalDialog from '../modals/ModalDialog.vue';
import BoxIcon from '../icons/BoxIcon.vue';

const store = useSettingsStore();

// Debug mode is "active" when the saved runtime flag is on or the live toggle
// (same page) is set to yes — so the button appears as soon as it is enabled.
const debugActive = computed(
  () => store.settings?.enable_debug_mode === 'yes' || store.runtime?.is_debug === true,
);

const open = ref(false);
const loading = ref(false);
const error = ref('');
const clearing = ref(false);
const downloading = ref(false);
const expanded = ref(-1);

const entries = ref([]);
const total = ref(0);
const page = ref(1);
const perPage = ref(50);
const availableLevels = ref([]);
const availableCategories = ref([]);

const filters = reactive({ level: '', category: '', search: '' });

const totalPages = computed(() => Math.max(1, Math.ceil(total.value / perPage.value)));

const categoryLabels = {
  request: 'Requisição',
  payment: 'Pagamento',
  error: 'Erro',
  webhook: 'Webhook',
  license: 'Licença',
  general: 'Geral',
};

const levelLabels = {
  emergency: 'Emergência',
  alert: 'Alerta',
  critical: 'Crítico',
  error: 'Erro',
  warning: 'Aviso',
  notice: 'Aviso',
  info: 'Info',
  debug: 'Debug',
};

function levelClass(level) {
  if (['emergency', 'alert', 'critical', 'error'].includes(level)) {
    return 'bg-rose-100 text-rose-700';
  }

  if (level === 'warning') {
    return 'bg-amber-100 text-amber-700';
  }

  if (level === 'debug') {
    return 'bg-slate-100 text-slate-500';
  }

  return 'bg-sky-100 text-sky-700';
}

function labelFor(map, key) {
  return map[key] || key;
}

function hasContext(entry) {
  return entry && entry.context && typeof entry.context === 'object' && Object.keys(entry.context).length > 0;
}

function prettyContext(entry) {
  try {
    return JSON.stringify(entry.context, null, 2);
  } catch (e) {
    return String(entry.context);
  }
}

function buildQuery() {
  const params = new URLSearchParams();

  if (filters.level) params.set('level', filters.level);
  if (filters.category) params.set('category', filters.category);
  if (filters.search) params.set('search', filters.search);
  params.set('page', String(page.value));
  params.set('per_page', String(perPage.value));

  return params.toString();
}

async function load() {
  loading.value = true;
  error.value = '';
  expanded.value = -1;

  try {
    const data = await apiGet(`admin/logs?${buildQuery()}`);

    entries.value = Array.isArray(data.entries) ? data.entries : [];
    total.value = Number(data.total) || 0;
    availableLevels.value = Array.isArray(data.levels) ? data.levels : [];
    availableCategories.value = Array.isArray(data.categories) ? data.categories : [];
  } catch (e) {
    error.value = 'Não foi possível carregar os logs.';
    entries.value = [];
    total.value = 0;
  } finally {
    loading.value = false;
  }
}

function openModal() {
  open.value = true;
  page.value = 1;
  load();
}

function closeModal() {
  open.value = false;
}

function applyFilters() {
  page.value = 1;
  load();
}

function goToPage(next) {
  const target = Math.min(totalPages.value, Math.max(1, next));

  if (target !== page.value) {
    page.value = target;
    load();
  }
}

function toggleExpand(index) {
  expanded.value = expanded.value === index ? -1 : index;
}

async function downloadLogs() {
  downloading.value = true;

  try {
    const data = await apiGet('admin/logs/download');
    const blob = new Blob([data.content || ''], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = data.filename || 'flexify-checkout-logs.log';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  } catch (e) {
    error.value = 'Não foi possível baixar o arquivo de log.';
  } finally {
    downloading.value = false;
  }
}

async function clearLogs() {
  // eslint-disable-next-line no-alert
  if (!window.confirm('Tem certeza que deseja apagar todos os logs? Esta ação não pode ser desfeita.')) {
    return;
  }

  clearing.value = true;
  error.value = '';

  try {
    await apiDelete('admin/logs');
    page.value = 1;
    await load();
  } catch (e) {
    error.value = 'Não foi possível limpar os logs.';
  } finally {
    clearing.value = false;
  }
}

// Reload with a small debounce while typing in the search box.
let searchTimer = null;

watch(
  () => filters.search,
  () => {
    if (!open.value) return;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
  },
);
</script>

<template>
  <div class="space-y-3 py-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h3 class="m-0 text-[15px] font-semibold text-slate-800">Registro de logs</h3>
        <p class="m-0 mt-1 max-w-2xl text-[13px] leading-6 text-slate-500">
          Acompanhe requisições, pagamentos, erros e webhooks em um arquivo de log dedicado. Erros são sempre
          registrados; os demais eventos exigem o modo de depuração ativo.
        </p>
      </div>

      <button
        v-if="debugActive"
        type="button"
        class="inline-flex shrink-0 items-center gap-2 rounded-[8px] bg-primary px-4 py-2.5 text-[14px] font-semibold text-white transition hover:opacity-90"
        @click="openModal"
      >
        <BoxIcon name="receipt" class="h-[1.1rem] w-[1.1rem]" />
        Ver logs
      </button>
    </div>

    <p v-if="!debugActive" class="m-0 rounded-[8px] bg-slate-50 px-4 py-3 text-[13px] text-slate-500">
      Ative o <strong>modo de depuração</strong> acima e salve as alterações para visualizar os logs.
    </p>

    <ModalDialog :open="open" size="xl" title="Logs do Flexify Checkout" @close="closeModal">
      <div class="space-y-4">
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-2">
          <select v-model="filters.level" class="flexify-field-input w-auto min-w-[130px]" @change="applyFilters">
            <option value="">Todos os níveis</option>
            <option v-for="lvl in availableLevels" :key="lvl" :value="lvl">{{ labelFor(levelLabels, lvl) }}</option>
          </select>

          <select v-model="filters.category" class="flexify-field-input w-auto min-w-[150px]" @change="applyFilters">
            <option value="">Todas as categorias</option>
            <option v-for="cat in availableCategories" :key="cat" :value="cat">{{ labelFor(categoryLabels, cat) }}</option>
          </select>

          <div class="relative flex-1 min-w-[180px]">
            <BoxIcon name="filter-alt" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              v-model="filters.search"
              type="search"
              placeholder="Buscar na mensagem…"
              class="flexify-field-input w-full pl-9"
            />
          </div>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-[8px] border border-slate-200 px-3 py-2 text-[13px] font-semibold text-slate-600 transition hover:bg-slate-50"
            :disabled="loading"
            @click="load"
          >
            <BoxIcon name="reset" class="h-4 w-4" />
            Atualizar
          </button>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-[8px] border border-slate-200 px-3 py-2 text-[13px] font-semibold text-slate-600 transition hover:bg-slate-50 disabled:opacity-50"
            :disabled="downloading"
            @click="downloadLogs"
          >
            <BoxIcon name="export" class="h-4 w-4" />
            {{ downloading ? 'Baixando…' : 'Baixar .log' }}
          </button>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-[8px] border border-rose-200 px-3 py-2 text-[13px] font-semibold text-rose-600 transition hover:bg-rose-50 disabled:opacity-50"
            :disabled="clearing"
            @click="clearLogs"
          >
            <BoxIcon name="trash" class="h-4 w-4" />
            {{ clearing ? 'Limpando…' : 'Limpar' }}
          </button>
        </div>

        <div v-if="error" class="rounded-[8px] bg-rose-50 px-4 py-3 text-[13px] text-rose-700" role="alert">{{ error }}</div>

        <!-- Loading -->
        <div v-if="loading" class="space-y-2">
          <div v-for="n in 6" :key="n" class="h-10 animate-pulse rounded-[8px] bg-slate-100" />
        </div>

        <!-- Empty -->
        <p v-else-if="!entries.length" class="rounded-[8px] bg-slate-50 px-4 py-8 text-center text-[14px] text-slate-500">
          Nenhum registro encontrado.
        </p>

        <!-- Entries -->
        <div v-else class="overflow-hidden rounded-[8px] border border-slate-100">
          <div
            v-for="(entry, index) in entries"
            :key="index"
            class="border-b border-slate-100 last:border-b-0"
          >
            <button
              type="button"
              class="flex w-full cursor-pointer items-center gap-3 px-3 py-2.5 text-left transition hover:bg-slate-50"
              @click="toggleExpand(index)"
            >
              <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold uppercase" :class="levelClass(entry.level)">
                {{ labelFor(levelLabels, entry.level) }}
              </span>

              <span class="shrink-0 rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">
                {{ labelFor(categoryLabels, entry.category) }}
              </span>

              <span class="min-w-0 flex-1 truncate text-[13px] text-slate-700" :title="entry.message">{{ entry.message }}</span>

              <span class="shrink-0 whitespace-nowrap text-[12px] tabular-nums text-slate-400">{{ entry.time }}</span>

              <BoxIcon
                v-if="hasContext(entry)"
                :name="expanded === index ? 'chevron-up' : 'chevron-down'"
                class="h-4 w-4 shrink-0 text-slate-400"
              />
              <span v-else class="h-4 w-4 shrink-0" />
            </button>

            <div v-if="expanded === index && hasContext(entry)" class="bg-slate-900 px-4 py-3">
              <pre class="m-0 overflow-x-auto whitespace-pre-wrap break-words text-[12px] leading-5 text-slate-100">{{ prettyContext(entry) }}</pre>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="total > 0" class="flex items-center justify-between text-[13px] text-slate-500">
          <span>{{ total }} registro(s)</span>

          <div v-if="totalPages > 1" class="flex items-center gap-1">
            <button
              type="button"
              class="inline-flex h-8 w-8 items-center justify-center rounded-[6px] border border-slate-200 text-slate-600 transition hover:bg-slate-50 disabled:opacity-40"
              :disabled="page <= 1 || loading"
              @click="goToPage(page - 1)"
            >
              <BoxIcon name="chevron-left" class="h-4 w-4" />
            </button>

            <span class="px-2 tabular-nums">{{ page }} / {{ totalPages }}</span>

            <button
              type="button"
              class="inline-flex h-8 w-8 items-center justify-center rounded-[6px] border border-slate-200 text-slate-600 transition hover:bg-slate-50 disabled:opacity-40"
              :disabled="page >= totalPages || loading"
              @click="goToPage(page + 1)"
            >
              <BoxIcon name="chevron-right" class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    </ModalDialog>
  </div>
</template>
