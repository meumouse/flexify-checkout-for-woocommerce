<script setup>
/**
 * Cart recovery — Analytics page.
 *
 * Replaces the legacy server-rendered analytics screen. Reads its data from
 * the REST endpoint GET flexify-checkout/v1/recovery/analytics and renders the
 * KPI counters plus the recovered-value and notifications charts with ApexCharts.
 *
 * @since 6.0.0
 */
import { ref, computed, onMounted } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { apiGet } from '../../services/api';

const loading = ref(true);
const error = ref('');
const period = ref(7);
const data = ref(null);

const STATUS_LABELS = {
  lead: 'Leads',
  shopping: 'Em compra',
  abandoned: 'Abandonados',
  order_abandoned: 'Pedido abandonado',
  recovered: 'Recuperados',
  lost: 'Perdidos',
  purchased: 'Comprados',
};

const periods = computed(() => data.value?.periods || [{ value: 7, label: '7 dias' }]);

const counters = computed(() => {
  const counts = data.value?.counts || {};

  return Object.keys(STATUS_LABELS).map((key) => ({
    key,
    label: STATUS_LABELS[key],
    value: counts[key] ?? 0,
  }));
});

const recoveredSeries = computed(() => [
  { name: 'Valor recuperado', data: data.value?.recovered_chart?.series || [] },
]);

const recoveredOptions = computed(() => ({
  chart: { type: 'area', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
  colors: ['#0d6efd'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2 },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } },
  xaxis: { categories: data.value?.recovered_chart?.labels || [] },
  yaxis: { labels: { formatter: (v) => Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) } },
  tooltip: { y: { formatter: (v) => `R$ ${Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}` } },
}));

const notificationsSeries = computed(() => data.value?.notifications_chart?.series || []);

const notificationsOptions = computed(() => ({
  chart: { type: 'bar', height: 320, stacked: true, toolbar: { show: false }, fontFamily: 'inherit' },
  colors: ['#0d6efd', '#22c55e', '#f59e0b', '#ec4899', '#8b5cf6'],
  plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } },
  dataLabels: { enabled: false },
  legend: { position: 'top' },
  xaxis: { categories: data.value?.notifications_chart?.categories || [] },
}));

const hasNotifications = computed(() => notificationsSeries.value.length > 0);

async function load() {
  loading.value = true;
  error.value = '';

  try {
    data.value = await apiGet(`recovery/analytics?period=${period.value}`);
  } catch (e) {
    error.value = 'Não foi possível carregar as análises. Recarregue a página e tente novamente.';
  } finally {
    loading.value = false;
  }
}

function onPeriodChange(event) {
  period.value = Number(event.target.value);
  load();
}

onMounted(load);
</script>

<template>
  <div class="flexify-settings-app-shell pb-8 pr-4">
    <header class="mb-2 mt-2 flex flex-wrap items-center gap-3">
      <svg class="h-9 w-9" viewBox="0 0 1080 1080" xmlns="http://www.w3.org/2000/svg"><g><path fill="#141D26" d="M513.96,116.38c-234.22,0-424.07,189.86-424.07,424.07c0,234.21,189.86,424.08,424.07,424.08 c234.21,0,424.07-189.86,424.07-424.08C938.03,306.25,748.17,116.38,513.96,116.38z M685.34,542.48 c-141.76,0.37-257.11,117.68-257.41,259.44h-88.21c0-191.79,153.83-347.41,345.62-347.41V542.48z M685.34,365.84 c-141.76,0.2-266.84,69.9-346.06,176.13V410.6c91.73-82.48,212.64-133.1,346.06-133.1V365.84z"/></g></svg>

      <h1 class="m-0 text-xl font-semibold text-brand">Análises de recuperação</h1>

      <select
        class="flexify-field-input ml-auto w-auto"
        :value="period"
        @change="onPeriodChange"
      >
        <option v-for="opt in periods" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
    </header>

    <div v-if="error" class="mt-6 rounded-[8px] bg-danger/10 px-5 py-4 text-[14px] text-danger" role="alert">
      {{ error }}
    </div>

    <template v-else>
      <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-[8px] bg-white px-5 py-4 ring-1 ring-slate-100">
          <div class="text-[13px] text-slate-500">Valor recuperado</div>
          <div class="mt-1 text-xl font-semibold text-success">
            {{ loading ? '—' : (data?.recovered_total_formatted || 'R$ 0,00') }}
          </div>
        </div>

        <div
          v-for="counter in counters"
          :key="counter.key"
          class="rounded-[8px] bg-white px-5 py-4 ring-1 ring-slate-100"
        >
          <div class="text-[13px] text-slate-500">{{ counter.label }}</div>
          <div class="mt-1 text-xl font-semibold text-brand">{{ loading ? '—' : counter.value }}</div>
        </div>
      </div>

      <section class="mt-6 rounded-[8px] bg-white px-6 py-5 ring-1 ring-slate-100">
        <h2 class="mb-4 text-[15px] font-semibold text-brand">Valor recuperado por dia</h2>
        <VueApexCharts v-if="!loading" type="area" height="320" :options="recoveredOptions" :series="recoveredSeries" />
        <div v-else class="skeleton-content" style="width: 100%; height: 320px;"></div>
      </section>

      <section class="mt-6 rounded-[8px] bg-white px-6 py-5 ring-1 ring-slate-100">
        <h2 class="mb-4 text-[15px] font-semibold text-brand">Notificações enviadas</h2>
        <VueApexCharts
          v-if="!loading && hasNotifications"
          type="bar"
          height="320"
          :options="notificationsOptions"
          :series="notificationsSeries"
        />
        <div v-else-if="loading" class="skeleton-content" style="width: 100%; height: 320px;"></div>
        <p v-else class="text-[14px] text-slate-500">Nenhuma notificação enviada no período.</p>
      </section>
    </template>
  </div>
</template>
