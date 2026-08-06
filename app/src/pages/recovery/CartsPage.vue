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
import { apiGet, apiDelete, apiPost, apiDownload } from '../../services/api';
import DataTable from '../../components/table/DataTable.vue';
import CartTimelineDrawer from '../../components/recovery/CartTimelineDrawer.vue';
import PageHeader from '../../components/layout/PageHeader.vue';

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
const exporting = ref(false);
const drawerOpen = ref(false);
const selectedCartId = ref(null);

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

function openDetail(cart) {
  selectedCartId.value = cart.id;
  drawerOpen.value = true;
}

function closeDetail() {
  drawerOpen.value = false;
}

async function exportCsv() {
  exporting.value = true;
  error.value = '';

  try {
    const params = new URLSearchParams(filterParams());
    await apiDownload(`recovery/carts/export?${params.toString()}`, `carrinhos-${new Date().toISOString().slice(0, 10)}.csv`);
  } catch (e) {
    error.value = 'Não foi possível exportar os carrinhos.';
  } finally {
    exporting.value = false;
  }
}

const SENDABLE_STATUSES = ['abandoned', 'order_abandoned', 'lost'];

function canSend(row) {
  return SENDABLE_STATUSES.includes(row.status) && !!(row.contact?.phone || row.contact?.email);
}

async function sendNow(cart) {
  if (!window.confirm(`Enviar uma mensagem de recuperação agora para o carrinho #${cart.id}?`)) {
    return;
  }

  busy.value = true;
  error.value = '';

  try {
    const res = await apiPost(`recovery/carts/${cart.id}/send`, {});
    if (res && res.sent === false) {
      error.value = res.message || 'Nada foi enviado. Verifique o contato e a configuração do canal.';
    }
    await load();
  } catch (e) {
    error.value = e?.message || 'Não foi possível enviar a mensagem de recuperação.';
  } finally {
    busy.value = false;
  }
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
    <PageHeader
      title="Todos os carrinhos"
      description="Audite cada carrinho capturado, filtre por status e gerencie os registros."
    >
      <template #actions>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-[8px] border border-slate-200 bg-white px-4 py-2 text-[13px] font-semibold text-slate-600 transition hover:bg-slate-50 disabled:opacity-50"
          :disabled="exporting"
          @click="exportCsv"
        >
          <BoxIcon name="export" class="h-4 w-4" />
          {{ exporting ? 'Exportando…' : 'Exportar CSV' }}
        </button>
      </template>
    </PageHeader>

    <DataTable
      class="mt-8"
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
          class="rounded-[6px] px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100 disabled:opacity-50"
          :disabled="busy"
          @click="openDetail(row)"
        >Detalhes</button>
        <button
          v-if="canSend(row)"
          type="button"
          class="rounded-[6px] px-2.5 py-1 text-xs font-semibold text-primary hover:bg-primary/10 disabled:opacity-50"
          :disabled="busy"
          @click="sendNow(row)"
        >Enviar agora</button>
        <button
          type="button"
          class="rounded-[6px] px-2.5 py-1 text-xs font-semibold text-danger hover:bg-danger/10 disabled:opacity-50"
          :disabled="busy"
          @click="removeCart(row)"
        >Excluir</button>
      </template>
    </DataTable>

    <CartTimelineDrawer :open="drawerOpen" :cart-id="selectedCartId" @close="closeDetail" />
  </div>
</template>
