<script setup>
/**
 * Cart recovery — Processing queue page.
 *
 * Replaces the legacy queue WP_List_Table. Lists scheduled cron events from
 * GET flexify-checkout/v1/recovery/queue and supports cancelling one.
 *
 * @since 6.0.0
 */
import { ref, computed, onMounted } from 'vue';
import { apiGet, apiDelete } from '../../services/api';

const loading = ref(true);
const error = ref('');
const items = ref([]);
const total = ref(0);
const totalPages = ref(1);
const page = ref(1);
const perPage = ref(20);
const deletingId = ref(0);

const rangeLabel = computed(() => {
  if (!total.value) return '0';
  const from = (page.value - 1) * perPage.value + 1;
  const to = Math.min(page.value * perPage.value, total.value);
  return `${from}–${to} de ${total.value}`;
});

async function load() {
  loading.value = true;
  error.value = '';

  try {
    const params = new URLSearchParams({ page: String(page.value), per_page: String(perPage.value) });
    const data = await apiGet(`recovery/queue?${params.toString()}`);
    items.value = data.items || [];
    total.value = data.total || 0;
    totalPages.value = data.total_pages || 1;
  } catch (e) {
    error.value = 'Não foi possível carregar a fila. Recarregue e tente novamente.';
  } finally {
    loading.value = false;
  }
}

function goTo(next) {
  if (next < 1 || next > totalPages.value) return;
  page.value = next;
  load();
}

async function cancelEvent(event) {
  if (!window.confirm(`Cancelar o evento agendado #${event.id}?`)) {
    return;
  }

  deletingId.value = event.id;

  try {
    await apiDelete(`recovery/queue/${event.id}`);
    await load();
  } catch (e) {
    error.value = 'Não foi possível cancelar o evento.';
  } finally {
    deletingId.value = 0;
  }
}

onMounted(load);
</script>

<template>
  <div class="flexify-settings-app-shell pb-8 pr-4">
    <header class="mb-2 mt-2 flex flex-wrap items-center gap-3">
      <svg class="h-9 w-9" viewBox="0 0 1080 1080" xmlns="http://www.w3.org/2000/svg"><g><path fill="#141D26" d="M513.96,116.38c-234.22,0-424.07,189.86-424.07,424.07c0,234.21,189.86,424.08,424.07,424.08 c234.21,0,424.07-189.86,424.07-424.08C938.03,306.25,748.17,116.38,513.96,116.38z M685.34,542.48 c-141.76,0.37-257.11,117.68-257.41,259.44h-88.21c0-191.79,153.83-347.41,345.62-347.41V542.48z M685.34,365.84 c-141.76,0.2-266.84,69.9-346.06,176.13V410.6c91.73-82.48,212.64-133.1,346.06-133.1V365.84z"/></g></svg>
      <h1 class="m-0 text-xl font-semibold text-brand">Fila de processamentos</h1>
      <span class="ml-auto text-[13px] text-slate-500">{{ rangeLabel }}</span>
    </header>

    <div v-if="error" class="mt-4 rounded-[8px] bg-danger/10 px-5 py-4 text-[14px] text-danger" role="alert">{{ error }}</div>

    <section class="mt-4 overflow-hidden rounded-[8px] bg-white ring-1 ring-slate-100">
      <div v-if="loading" class="p-6">
        <div class="skeleton-content" style="width: 100%; height: 280px;"></div>
      </div>

      <table v-else class="w-full border-collapse text-[13px]">
        <thead>
          <tr class="border-b border-slate-100 text-left text-slate-500">
            <th class="px-4 py-3 font-semibold">Carrinho</th>
            <th class="px-4 py-3 font-semibold">Contato</th>
            <th class="px-4 py-3 font-semibold">Evento</th>
            <th class="px-4 py-3 font-semibold">Data e horário</th>
            <th class="px-4 py-3 font-semibold"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!items.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Nenhum evento na fila.</td>
          </tr>
          <tr v-for="event in items" :key="event.id" class="border-b border-slate-50 align-top">
            <td class="px-4 py-3 text-slate-500">#{{ event.cart_id }}</td>
            <td class="px-4 py-3">
              <div class="font-semibold text-brand">{{ event.contact.name || 'Visitante' }}</div>
              <div v-if="event.contact.phone" class="text-slate-500">{{ event.contact.phone }}</div>
              <div v-if="event.contact.email" class="text-slate-500">{{ event.contact.email }}</div>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex rounded-full bg-primary-100 px-2.5 py-1 text-xs font-semibold text-primary">{{ event.event_name }}</span>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ event.scheduled_at }}</td>
            <td class="px-4 py-3 text-right">
              <button
                type="button"
                class="rounded-[6px] px-2.5 py-1 text-xs font-semibold text-danger hover:bg-danger/10 disabled:opacity-50"
                :disabled="deletingId === event.id"
                @click="cancelEvent(event)"
              >Cancelar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <div class="mt-4 flex items-center justify-end gap-2">
      <button type="button" class="rounded-[8px] border border-slate-200 px-3 py-1.5 text-[13px] disabled:opacity-40" :disabled="page <= 1 || loading" @click="goTo(page - 1)">Anterior</button>
      <span class="text-[13px] text-slate-500">Página {{ page }} de {{ totalPages }}</span>
      <button type="button" class="rounded-[8px] border border-slate-200 px-3 py-1.5 text-[13px] disabled:opacity-40" :disabled="page >= totalPages || loading" @click="goTo(page + 1)">Próxima</button>
    </div>
  </div>
</template>
