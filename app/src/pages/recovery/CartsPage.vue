<script setup>
/**
 * Cart recovery — All carts page.
 *
 * Replaces the legacy WP_List_Table. Reads a paginated, filterable list from
 * GET flexify-checkout/v1/recovery/carts and supports deleting a cart.
 *
 * @since 6.0.0
 */
import { ref, computed, onMounted } from 'vue';
import { apiGet, apiDelete } from '../../services/api';

const loading = ref(true);
const error = ref('');
const items = ref([]);
const statuses = ref([{ value: 'all', label: 'Todos os status' }]);
const total = ref(0);
const totalPages = ref(1);
const page = ref(1);
const perPage = ref(20);
const status = ref('all');
const search = ref('');
const deletingId = ref(0);

const STATUS_CLASSES = {
  lead: 'bg-slate-100 text-slate-600',
  shopping: 'bg-primary-100 text-primary',
  abandoned: 'bg-warning/10 text-warning',
  order_abandoned: 'bg-warning/10 text-warning',
  recovered: 'bg-success/10 text-success',
  purchased: 'bg-success/10 text-success',
  lost: 'bg-danger/10 text-danger',
};

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
    const params = new URLSearchParams({
      page: String(page.value),
      per_page: String(perPage.value),
      status: status.value,
      search: search.value,
    });
    const data = await apiGet(`recovery/carts?${params.toString()}`);
    items.value = data.items || [];
    total.value = data.total || 0;
    totalPages.value = data.total_pages || 1;
    statuses.value = data.statuses || statuses.value;
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

function goTo(next) {
  if (next < 1 || next > totalPages.value) return;
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

  deletingId.value = cart.id;

  try {
    await apiDelete(`recovery/carts/${cart.id}`);
    await load();
  } catch (e) {
    error.value = 'Não foi possível excluir o carrinho.';
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
      <h1 class="m-0 text-xl font-semibold text-brand">Todos os carrinhos</h1>
    </header>

    <div class="mt-6 flex flex-wrap items-center gap-3">
      <select class="flexify-field-input w-auto" v-model="status" @change="applyFilters">
        <option v-for="opt in statuses" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>

      <form class="flex items-center gap-2" @submit.prevent="applyFilters">
        <input
          v-model="search"
          type="search"
          placeholder="Buscar por nome, e-mail…"
          class="flexify-field-input w-auto"
        />
        <button type="submit" class="rounded-[8px] bg-primary px-4 py-2 text-[13px] font-semibold text-white">Buscar</button>
      </form>

      <span class="ml-auto text-[13px] text-slate-500">{{ rangeLabel }}</span>
    </div>

    <div v-if="error" class="mt-4 rounded-[8px] bg-danger/10 px-5 py-4 text-[14px] text-danger" role="alert">{{ error }}</div>

    <section class="mt-4 overflow-hidden rounded-[8px] bg-white ring-1 ring-slate-100">
      <div v-if="loading" class="p-6">
        <div class="skeleton-content" style="width: 100%; height: 280px;"></div>
      </div>

      <table v-else class="w-full border-collapse text-[13px]">
        <thead>
          <tr class="border-b border-slate-100 text-left text-slate-500">
            <th class="px-4 py-3 font-semibold">#</th>
            <th class="px-4 py-3 font-semibold">Contato</th>
            <th class="px-4 py-3 font-semibold">Localização</th>
            <th class="px-4 py-3 font-semibold">Produtos</th>
            <th class="px-4 py-3 font-semibold">Valor</th>
            <th class="px-4 py-3 font-semibold">Data do evento</th>
            <th class="px-4 py-3 font-semibold">Notif.</th>
            <th class="px-4 py-3 font-semibold">Status</th>
            <th class="px-4 py-3 font-semibold"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!items.length">
            <td colspan="9" class="px-4 py-8 text-center text-slate-500">Nenhum carrinho encontrado.</td>
          </tr>
          <tr v-for="cart in items" :key="cart.id" class="border-b border-slate-50 align-top">
            <td class="px-4 py-3 text-slate-500">{{ cart.id }}</td>
            <td class="px-4 py-3">
              <div class="font-semibold text-brand">{{ cart.contact.name || 'Visitante' }}</div>
              <div v-if="cart.contact.phone" class="text-slate-500">{{ cart.contact.phone }}</div>
              <div v-if="cart.contact.email" class="text-slate-500">{{ cart.contact.email }}</div>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ cart.location || '—' }}</td>
            <td class="px-4 py-3 text-slate-600">
              <span v-if="!cart.products.length">—</span>
              <ul v-else class="m-0 list-none p-0">
                <li v-for="(p, i) in cart.products" :key="i">{{ p.name }} <span class="text-slate-400">× {{ p.quantity }}</span></li>
              </ul>
            </td>
            <td class="px-4 py-3 text-slate-700" v-html="cart.total_formatted"></td>
            <td class="px-4 py-3 text-slate-600">{{ cart.event_date }}</td>
            <td class="px-4 py-3 text-slate-600">{{ cart.notifications_count }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(cart.status)">{{ cart.status_label }}</span>
            </td>
            <td class="px-4 py-3 text-right">
              <button
                type="button"
                class="rounded-[6px] px-2.5 py-1 text-xs font-semibold text-danger hover:bg-danger/10 disabled:opacity-50"
                :disabled="deletingId === cart.id"
                @click="removeCart(cart)"
              >Excluir</button>
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <div class="mt-4 flex items-center justify-end gap-2">
      <button
        type="button"
        class="rounded-[8px] border border-slate-200 px-3 py-1.5 text-[13px] disabled:opacity-40"
        :disabled="page <= 1 || loading"
        @click="goTo(page - 1)"
      >Anterior</button>
      <span class="text-[13px] text-slate-500">Página {{ page }} de {{ totalPages }}</span>
      <button
        type="button"
        class="rounded-[8px] border border-slate-200 px-3 py-1.5 text-[13px] disabled:opacity-40"
        :disabled="page >= totalPages || loading"
        @click="goTo(page + 1)"
      >Próxima</button>
    </div>
  </div>
</template>
