<script setup>
/**
 * Cart recovery — All carts page.
 *
 * Replaces the legacy WP_List_Table. Reads a paginated, filterable list from
 * GET flexify-checkout/v1/recovery/carts and renders it through the shared
 * <DataTable> (status tabs with counts, date-range filter, bulk delete and
 * "clear all" via POST recovery/carts/bulk-delete).
 *
 * @since 6.0.0
 */
import { ref, computed, onMounted } from 'vue';
import { apiGet, apiDelete, apiPost } from '../../services/api';
import DataTable from '../../components/table/DataTable.vue';

const loading = ref(true);
const busy = ref(false);
const error = ref('');
const items = ref([]);
const statuses = ref([{ value: 'all', label: 'Todos os status' }]);
const counts = ref({});
const total = ref(0);
const totalPages = ref(1);
const page = ref(1);
const perPage = ref(20);
const status = ref('all');
const search = ref('');
const dateFrom = ref('');
const dateTo = ref('');

const STATUS_CLASSES = {
  lead: 'bg-slate-100 text-slate-600',
  shopping: 'bg-primary-100 text-primary',
  abandoned: 'bg-warning/10 text-warning',
  order_abandoned: 'bg-warning/10 text-warning',
  recovered: 'bg-success/10 text-success',
  purchased: 'bg-success/10 text-success',
  lost: 'bg-danger/10 text-danger',
};

const columns = [
  { key: 'id', label: '#' },
  { key: 'contact', label: 'Contato' },
  { key: 'location', label: 'Localização' },
  { key: 'products', label: 'Produtos' },
  { key: 'total', label: 'Valor' },
  { key: 'event_date', label: 'Data do evento' },
  { key: 'notifications_count', label: 'Notif.', align: 'center' },
  { key: 'status', label: 'Status' },
];

const tabs = computed(() =>
  statuses.value.map((opt) => ({
    value: opt.value,
    label: opt.label,
    count: counts.value[opt.value] ?? 0,
  })),
);

function filterParams() {
  return {
    status: status.value,
    search: search.value,
    date_from: dateFrom.value,
    date_to: dateTo.value,
  };
}

async function load() {
  loading.value = true;
  error.value = '';

  try {
    const params = new URLSearchParams({
      page: String(page.value),
      per_page: String(perPage.value),
      ...filterParams(),
    });
    const data = await apiGet(`recovery/carts?${params.toString()}`);
    items.value = data.items || [];
    total.value = data.total || 0;
    totalPages.value = data.total_pages || 1;
    statuses.value = data.statuses || statuses.value;
    counts.value = data.counts || {};
  } catch (e) {
    error.value = 'Não foi possível carregar os carrinhos. Recarregue e tente novamente.';
  } finally {
    loading.value = false;
  }
}

function applyFilters() {
  page.value = 1;
  load();
}

function changePage(next) {
  page.value = next;
  load();
}

function statusClass(key) {
  return STATUS_CLASSES[key] || 'bg-slate-100 text-slate-600';
}

async function removeCart(cart) {
  if (!window.confirm(`Excluir o carrinho #${cart.id}? Esta ação não pode ser desfeita.`)) {
    return;
  }

  busy.value = true;

  try {
    await apiDelete(`recovery/carts/${cart.id}`);
    await load();
  } catch (e) {
    error.value = 'Não foi possível excluir o carrinho.';
  } finally {
    busy.value = false;
  }
}

async function deleteSelected(ids) {
  if (!window.confirm(`Excluir ${ids.length} carrinho(s) selecionado(s)? Esta ação não pode ser desfeita.`)) {
    return;
  }

  busy.value = true;

  try {
    await apiPost('recovery/carts/bulk-delete', { ids });
    await load();
  } catch (e) {
    error.value = 'Não foi possível excluir os carrinhos selecionados.';
  } finally {
    busy.value = false;
  }
}

async function clearAll() {
  if (!window.confirm('Excluir todos os carrinhos que correspondem aos filtros atuais? Esta ação não pode ser desfeita.')) {
    return;
  }

  busy.value = true;

  try {
    await apiPost('recovery/carts/bulk-delete', { all: true, ...filterParams() });
    page.value = 1;
    await load();
  } catch (e) {
    error.value = 'Não foi possível limpar os carrinhos.';
  } finally {
    busy.value = false;
  }
}

onMounted(load);
</script>

<template>
  <div class="flexify-settings-app-shell pb-8 pr-4">
    <header class="mb-6 mt-2 flex flex-wrap items-center gap-3">
      <svg class="h-9 w-9" viewBox="0 0 1080 1080" xmlns="http://www.w3.org/2000/svg"><g><path fill="#141D26" d="M513.96,116.38c-234.22,0-424.07,189.86-424.07,424.07c0,234.21,189.86,424.08,424.07,424.08 c234.21,0,424.07-189.86,424.07-424.08C938.03,306.25,748.17,116.38,513.96,116.38z M685.34,542.48 c-141.76,0.37-257.11,117.68-257.41,259.44h-88.21c0-191.79,153.83-347.41,345.62-347.41V542.48z M685.34,365.84 c-141.76,0.2-266.84,69.9-346.06,176.13V410.6c91.73-82.48,212.64-133.1,346.06-133.1V365.84z"/></g></svg>
      <div>
        <h1 class="m-0 text-xl font-semibold text-brand">Todos os carrinhos</h1>
        <p class="m-0 mt-1 text-[13px] text-slate-500">Audite cada carrinho capturado, filtre por status e gerencie os registros.</p>
      </div>
    </header>

    <DataTable
      :columns="columns"
      :rows="items"
      :loading="loading"
      :error="error"
      :busy="busy"
      :tabs="tabs"
      v-model:active-tab="status"
      v-model:search="search"
      v-model:date-from="dateFrom"
      v-model:date-to="dateTo"
      search-placeholder="Buscar por nome, e-mail…"
      :page="page"
      :total-pages="totalPages"
      :total="total"
      :per-page="perPage"
      empty-text="Nenhum carrinho encontrado."
      @apply="applyFilters"
      @clear-filters="applyFilters"
      @page-change="changePage"
      @delete-selected="deleteSelected"
      @clear-all="clearAll"
    >
      <template #cell-id="{ row }">
        <span class="text-slate-500">#{{ row.id }}</span>
      </template>

      <template #cell-contact="{ row }">
        <div class="font-semibold text-brand">{{ row.contact.name || 'Visitante' }}</div>
        <div v-if="row.contact.phone" class="text-slate-500">{{ row.contact.phone }}</div>
        <div v-if="row.contact.email" class="text-slate-500">{{ row.contact.email }}</div>
      </template>

      <template #cell-location="{ row }">
        <span class="text-slate-600">{{ row.location || '—' }}</span>
      </template>

      <template #cell-products="{ row }">
        <span v-if="!row.products.length" class="text-slate-400">—</span>
        <ul v-else class="m-0 list-none p-0 text-slate-600">
          <li v-for="(p, i) in row.products" :key="i">{{ p.name }} <span class="text-slate-400">× {{ p.quantity }}</span></li>
        </ul>
      </template>

      <template #cell-total="{ row }">
        <span class="text-slate-700" v-html="row.total_formatted"></span>
      </template>

      <template #cell-event_date="{ row }">
        <span class="text-slate-600">{{ row.event_date }}</span>
      </template>

      <template #cell-notifications_count="{ row }">
        <span class="text-slate-600">{{ row.notifications_count }}</span>
      </template>

      <template #cell-status="{ row }">
        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(row.status)">{{ row.status_label }}</span>
      </template>

      <template #actions="{ row }">
        <button
          type="button"
          class="rounded-[6px] px-2.5 py-1 text-xs font-semibold text-danger hover:bg-danger/10 disabled:opacity-50"
          :disabled="busy"
          @click="removeCart(row)"
        >Excluir</button>
      </template>
    </DataTable>
  </div>
</template>
