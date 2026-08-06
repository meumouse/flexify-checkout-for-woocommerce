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
import BaseSelect from '../../components/fields/BaseSelect.vue';
import ChartSkeleton from '../../components/skeletons/ChartSkeleton.vue';
import { apiGet } from '../../services/api';
import PageHeader from '../../components/layout/PageHeader.vue';

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

// --- A/B testing results ---
// Per follow-up event, the sent/recovered tally for each message variant, from
// the recovery/analytics `ab_tests` block. Only events that actually ran more
// than one variant appear here.
const abTests = computed(() => data.value?.ab_tests || []);
const hasAbTests = computed(() => abTests.value.length > 0);

// Best-performing variant id per event, to highlight the winner in the table.
function bestVariantId(test) {
  let best = null;

  test.variants.forEach((v) => {
    if (v.sent > 0 && (!best || v.rate > best.rate)) {
      best = v;
    }
  });

  return best ? best.id : null;
}

// --- Checkout funnel metrics (section 9.1) ---
// Backed by the recovery/analytics endpoint's `funnel` block, which derives the
// KPIs/segments from WooCommerce orders + recovery carts + Order Attribution and
// reads the per-step completion from the always-on checkout beacon
// (recovery/track-step). Cards fall back to "—"/"Sem dados" until data arrives.
const funnel = computed(() => data.value?.funnel || null);
const hasFunnelData = computed(() => !!funnel.value?.has_data);

const FUNNEL_METRICS = [
  { key: 'abandonment_rate', label: 'Taxa de abandono', hint: 'Checkouts iniciados sem pedido concluído.' },
  { key: 'conversion_rate', label: 'Taxa de conversão', hint: 'Pedidos concluídos sobre checkouts iniciados.' },
  { key: 'average_ticket', label: 'Ticket médio', hint: 'Valor médio dos pedidos concluídos.' },
  { key: 'completion_time', label: 'Tempo médio de conclusão', hint: 'Do início do checkout até o pedido.' },
  { key: 'payment_failure_rate', label: 'Taxa de falha de pagamento', hint: 'Pagamentos recusados ou com erro.' },
  { key: 'coupon_usage', label: 'Uso de cupom', hint: 'Pedidos com cupom de desconto aplicado.' },
];

const funnelMetrics = computed(() =>
  FUNNEL_METRICS.map((m) => ({ ...m, data: funnel.value?.metrics?.[m.key] || null })),
);

const stepCompletion = computed(() => funnel.value?.steps || [
  { key: 'contact', label: 'Contato', count: 0, percent: null },
  { key: 'shipping', label: 'Entrega', count: 0, percent: null },
  { key: 'payment', label: 'Pagamento', count: 0, percent: null },
]);

const SEGMENTS = [
  { key: 'device', label: 'Dispositivo' },
  { key: 'payment_method', label: 'Método de pagamento' },
  { key: 'customer_type', label: 'Novo vs. recorrente' },
  { key: 'traffic_source', label: 'Origem do tráfego' },
];

const segments = computed(() =>
  SEGMENTS.map((s) => ({ ...s, items: funnel.value?.segments?.[s.key] || [] })),
);

// Color a delta green/red depending on whether the movement is favourable.
function deltaClass(metric) {
  if (!metric || metric.delta == null || metric.delta === 0) {
    return 'text-slate-400';
  }

  const good = metric.delta > 0 ? metric.up_is_good : !metric.up_is_good;

  return good ? 'text-success' : 'text-danger';
}

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

function onPeriodChange(value) {
  period.value = Number(value);
  load();
}

onMounted(load);
</script>

<template>
  <div class="flexify-settings-app-shell pb-8 pr-4">
    <PageHeader
      title="Análise"
      description="Acompanhe o funil de checkout, o desempenho da recuperação de carrinhos e a receita recuperada."
    >
      <template #actions>
        <BaseSelect
          class="w-48"
          :model-value="period"
          :options="periods"
          @update:model-value="onPeriodChange"
        />
      </template>
    </PageHeader>

    <div v-if="error" class="mt-8 rounded-[8px] bg-danger/10 px-5 py-4 text-[14px] text-danger" role="alert">
      {{ error }}
    </div>

    <template v-else>
      <!-- Checkout funnel metrics (section 9.1) -->
      <section class="mt-8">
        <div class="mb-3 flex items-center gap-3">
          <h2 class="m-0 text-[15px] font-semibold text-brand">Funil de checkout</h2>
          <span
            v-if="!loading && !hasFunnelData"
            class="inline-flex items-center rounded-full bg-primary-100 px-2.5 py-0.5 text-[11px] font-semibold text-primary"
          >Aguardando dados</span>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
          <div
            v-for="metric in funnelMetrics"
            :key="metric.key"
            class="rounded-[8px] bg-white px-5 py-4 ring-1 ring-slate-100"
          >
            <div class="text-[13px] text-slate-500">{{ metric.label }}</div>
            <div class="mt-1 text-xl font-semibold" :class="(!loading && metric.data?.formatted) ? 'text-brand' : 'text-slate-300'">
              {{ loading ? '—' : (metric.data?.formatted ?? '—') }}
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-[11px]">
              <span v-if="!loading && metric.data?.delta_formatted" class="font-semibold" :class="deltaClass(metric.data)">
                {{ metric.data.delta_formatted }}
              </span>
              <span class="text-slate-400">{{ metric.hint }}</span>
            </div>
          </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <div class="rounded-[8px] bg-white px-6 py-5 ring-1 ring-slate-100">
            <h3 class="m-0 mb-3 text-[14px] font-semibold text-brand">Conclusão por etapa</h3>
            <div class="flex flex-col gap-3">
              <div v-for="step in stepCompletion" :key="step.key" class="flex items-center gap-3">
                <span class="w-24 shrink-0 text-[13px] text-slate-500">{{ step.label }}</span>
                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                  <div
                    class="h-full rounded-full bg-primary transition-all"
                    :style="{ width: (loading ? 0 : (step.percent ?? 0)) + '%' }"
                  ></div>
                </div>
                <span class="w-10 shrink-0 text-right text-[13px]" :class="step.percent != null ? 'text-slate-600' : 'text-slate-300'">
                  {{ loading || step.percent == null ? '—' : step.percent + '%' }}
                </span>
              </div>
            </div>
          </div>

          <div class="rounded-[8px] bg-white px-6 py-5 ring-1 ring-slate-100">
            <h3 class="m-0 mb-3 text-[14px] font-semibold text-brand">Métricas por segmento</h3>
            <div class="grid grid-cols-2 gap-3">
              <div v-for="segment in segments" :key="segment.key" class="rounded-[6px] bg-slate-50 px-4 py-3">
                <div class="text-[13px] text-slate-500">{{ segment.label }}</div>

                <div v-if="!loading && segment.items.length" class="mt-2 flex flex-col gap-1">
                  <div v-for="item in segment.items" :key="item.label" class="flex items-center justify-between gap-2 text-[12px]">
                    <span class="truncate text-slate-600">{{ item.label }}</span>
                    <span class="shrink-0 font-medium text-slate-500">{{ item.percent }}%</span>
                  </div>
                </div>

                <div v-else class="mt-1 text-[13px] font-medium text-slate-300">Sem dados</div>
              </div>
            </div>
          </div>
        </div>

        <p class="mt-3 text-[12px] italic text-slate-400">
          Etapas e segmentos de dispositivo/origem são coletados na finalização da compra a partir do momento da ativação; pedidos e cupons já são considerados retroativamente. A comparação usa o mesmo período imediatamente anterior.
        </p>
      </section>

      <h2 class="mt-8 mb-3 text-[15px] font-semibold text-brand">Recuperação de carrinhos</h2>

      <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
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
        <ChartSkeleton v-else :height="320" />
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
        <ChartSkeleton v-else-if="loading" :height="320" />
        <p v-else class="text-[14px] text-slate-500">Nenhuma notificação enviada no período.</p>
      </section>

      <!-- A/B testing results -->
      <section v-if="!loading && hasAbTests" class="mt-6 rounded-[8px] bg-white px-6 py-5 ring-1 ring-slate-100">
        <h2 class="mb-1 text-[15px] font-semibold text-brand">Testes A/B</h2>
        <p class="m-0 mb-4 text-[13px] text-slate-500">Desempenho de cada variante de mensagem por follow-up no período.</p>

        <div v-for="test in abTests" :key="test.event_key" class="mb-5 last:mb-0">
          <h3 class="m-0 mb-2 text-[14px] font-semibold text-brand">{{ test.title }}</h3>
          <div class="overflow-hidden rounded-[8px] ring-1 ring-slate-100">
            <table class="w-full border-collapse text-[13px]">
              <thead>
                <tr class="border-b border-slate-100 text-left text-slate-500">
                  <th class="px-4 py-2.5 font-semibold">Variante</th>
                  <th class="px-4 py-2.5 font-semibold">Enviados</th>
                  <th class="px-4 py-2.5 font-semibold">Recuperados</th>
                  <th class="px-4 py-2.5 font-semibold">Taxa de recuperação</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="variant in test.variants" :key="variant.id" class="border-b border-slate-50 last:border-0">
                  <td class="px-4 py-2.5">
                    <span class="inline-flex items-center gap-1.5 font-medium text-slate-700">
                      Variante {{ variant.label }}
                      <span v-if="bestVariantId(test) === variant.id" class="inline-flex items-center rounded-full bg-success/10 px-2 py-0.5 text-[11px] font-semibold text-success">Melhor</span>
                    </span>
                  </td>
                  <td class="px-4 py-2.5 text-slate-600">{{ variant.sent }}</td>
                  <td class="px-4 py-2.5 text-slate-600">{{ variant.recovered }}</td>
                  <td class="px-4 py-2.5 font-semibold text-slate-700">{{ variant.rate }}%</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>
