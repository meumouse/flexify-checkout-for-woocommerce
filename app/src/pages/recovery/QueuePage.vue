<script setup>
/**
 * Cart recovery — Processing queue page.
 *
 * Replaces the legacy queue WP_List_Table. Lists scheduled cron events from
 * GET flexify-checkout/v1/recovery/queue through the shared <DataTable> (event
 * tabs with counts, date-range filter, bulk cancel and "clear all" via POST
 * recovery/queue/bulk-delete).
 *
 * @since 6.0.0
 */
import { ref, computed, onMounted } from 'vue';
import { apiGet, apiDelete, apiPost } from '../../services/api';
import DataTable from '../../components/table/DataTable.vue';
import PageHeader from '../../components/layout/PageHeader.vue';

const loading = ref(true);
const busy = ref(false);
const error = ref('');
const items = ref([]);
const events = ref([{ value: 'all', label: 'Todos os eventos' }]);
const counts = ref({});
const total = ref(0);
const totalPages = ref(1);
const page = ref(1);
const perPage = ref(20);
const event = ref('all');
const dateFrom = ref('');
const dateTo = ref('');

const columns = [
  { key: 'cart_id', label: 'Carrinho' },
  { key: 'contact', label: 'Contato' },
  { key: 'event_name', label: 'Evento' },
  { key: 'scheduled_at', label: 'Data e horário' },
];

const tabs = computed(() =>
  events.value.map((opt) => ({
    value: opt.value,
    label: opt.label,
    count: counts.value[opt.value] ?? 0,
  })),
);

function filterParams() {
  return {
    event: event.value,
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
    const data = await apiGet(`recovery/queue?${params.toString()}`);
    items.value = data.items || [];
    total.value = data.total || 0;
    totalPages.value = data.total_pages || 1;
    events.value = data.events || events.value;
    counts.value = data.counts || {};
  } catch (e) {
    error.value = 'Não foi possível carregar a fila. Recarregue e tente novamente.';
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

async function cancelEvent(item) {
  if (!window.confirm(`Cancelar o evento agendado #${item.id}?`)) {
    return;
  }

  busy.value = true;

  try {
    await apiDelete(`recovery/queue/${item.id}`);
    await load();
  } catch (e) {
    error.value = 'Não foi possível cancelar o evento.';
  } finally {
    busy.value = false;
  }
}

async function deleteSelected(ids) {
  if (!window.confirm(`Cancelar ${ids.length} evento(s) selecionado(s)?`)) {
    return;
  }

  busy.value = true;

  try {
    await apiPost('recovery/queue/bulk-delete', { ids });
    await load();
  } catch (e) {
    error.value = 'Não foi possível cancelar os eventos selecionados.';
  } finally {
    busy.value = false;
  }
}

async function clearAll() {
  if (!window.confirm('Cancelar todos os eventos que correspondem aos filtros atuais?')) {
    return;
  }

  busy.value = true;

  try {
    await apiPost('recovery/queue/bulk-delete', { all: true, ...filterParams() });
    page.value = 1;
    await load();
  } catch (e) {
    error.value = 'Não foi possível limpar a fila.';
  } finally {
    busy.value = false;
  }
}

onMounted(load);
</script>

<template>
  <div class="flexify-settings-app-shell pb-8 pr-4">
    <PageHeader
      title="Fila de processamentos"
      description="Acompanhe os eventos agendados de recuperação, filtre por tipo e gerencie a fila."
    />

    <DataTable
      class="mt-8"
      :columns="columns"
      :rows="items"
      :loading="loading"
      :error="error"
      :busy="busy"
      :tabs="tabs"
      v-model:active-tab="event"
      v-model:date-from="dateFrom"
      v-model:date-to="dateTo"
      :show-search="false"
      :page="page"
      :total-pages="totalPages"
      :total="total"
      :per-page="perPage"
      empty-text="Nenhum evento na fila."
      @apply="applyFilters"
      @clear-filters="applyFilters"
      @page-change="changePage"
      @delete-selected="deleteSelected"
      @clear-all="clearAll"
    >
      <template #cell-cart_id="{ row }">
        <span class="text-slate-500">#{{ row.cart_id }}</span>
      </template>

      <template #cell-contact="{ row }">
        <div class="font-semibold text-brand">{{ row.contact.name || 'Visitante' }}</div>
        <div v-if="row.contact.phone" class="text-slate-500">{{ row.contact.phone }}</div>
        <div v-if="row.contact.email" class="text-slate-500">{{ row.contact.email }}</div>
      </template>

      <template #cell-event_name="{ row }">
        <span class="inline-flex rounded-full bg-primary-100 px-2.5 py-1 text-xs font-semibold text-primary">{{ row.event_name }}</span>
      </template>

      <template #cell-scheduled_at="{ row }">
        <span class="text-slate-600">{{ row.scheduled_at }}</span>
      </template>

      <template #actions="{ row }">
        <button
          type="button"
          class="rounded-[6px] px-2.5 py-1 text-xs font-semibold text-danger hover:bg-danger/10 disabled:opacity-50"
          :disabled="busy"
          @click="cancelEvent(row)"
        >Cancelar</button>
      </template>
    </DataTable>
  </div>
</template>
