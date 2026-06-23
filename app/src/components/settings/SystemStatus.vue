<script setup>
import { computed } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';

const store = useSettingsStore();

const system = computed(() => store.runtime?.system || {});
const phpSettings = computed(() => system.value.php_settings || {});

function parseSize(value) {
  const raw = String(value || '').trim().toUpperCase();
  const number = parseFloat(raw);

  if (Number.isNaN(number)) {
    return 0;
  }

  if (raw.endsWith('G')) {
    return number * 1024;
  }

  if (raw.endsWith('K')) {
    return number / 1024;
  }

  return number;
}

const wordpressRows = computed(() => [
  { label: 'Versão do WordPress', value: system.value.wp_version || '—', status: 'info' },
  { label: 'WordPress Multisite', value: system.value.multisite ? 'Sim' : 'Não', status: 'info' },
  { label: 'WP_DEBUG', value: system.value.wp_debug ? 'Ativado' : 'Desativado', status: system.value.wp_debug ? 'warning' : 'success' },
]);

const pluginRows = computed(() => [
  {
    label: 'Versão do Flexify Checkout',
    value: `${store.runtime?.version || '—'}${store.isPro ? ' Pro' : ''}`,
    status: 'info',
  },
  {
    label: 'Versão do WooCommerce',
    value: system.value.wc_version || '—',
    status: system.value.wc_version ? 'success' : 'warning',
  },
]);

const serverRows = computed(() => [
  { label: 'Versão do PHP', value: system.value.php_version || '—', status: 'success' },
  { label: 'DOMDocument', value: system.value.extensions?.dom ? 'Sim' : 'Não', status: system.value.extensions?.dom ? 'success' : 'danger' },
  { label: 'Extensão cURL', value: system.value.extensions?.curl ? 'Sim' : 'Não', status: system.value.extensions?.curl ? 'success' : 'danger' },
  { label: 'Extensão OpenSSL', value: system.value.extensions?.openssl ? 'Sim' : 'Não', status: system.value.extensions?.openssl ? 'success' : 'danger' },
  { label: 'Extensão GD', value: system.value.extensions?.gd ? 'Sim' : 'Não', status: system.value.extensions?.gd ? 'success' : 'warning' },
  {
    label: 'post_max_size',
    value: phpSettings.value.post_max_size || '—',
    status: parseSize(phpSettings.value.post_max_size) >= 64 ? 'success' : 'warning',
    hint: 'Valor mínimo recomendado é 64M',
  },
  {
    label: 'max_execution_time',
    value: phpSettings.value.max_execution_time || '—',
    status: Number(phpSettings.value.max_execution_time) === 0 || Number(phpSettings.value.max_execution_time) >= 180 ? 'success' : 'warning',
    hint: 'Valor mínimo recomendado é 180',
  },
  {
    label: 'max_input_vars',
    value: phpSettings.value.max_input_vars || '—',
    status: Number(phpSettings.value.max_input_vars) >= 10000 ? 'success' : 'warning',
    hint: 'Valor mínimo recomendado é 10000',
  },
  {
    label: 'memory_limit',
    value: phpSettings.value.memory_limit || '—',
    status: parseSize(phpSettings.value.memory_limit) >= 128 || String(phpSettings.value.memory_limit) === '-1' ? 'success' : 'warning',
    hint: 'Valor mínimo recomendado é 128M',
  },
  { label: 'upload_max_filesize', value: phpSettings.value.upload_max_filesize || '—', status: 'success' },
  {
    label: 'allow_url_fopen',
    value: system.value.file_get_content ? 'Ligado' : 'Desligado',
    status: system.value.file_get_content ? 'success' : 'warning',
  },
]);

const columns = computed(() => [
  { key: 'wordpress', title: 'WordPress', rows: wordpressRows.value },
  { key: 'plugin', title: 'Flexify Checkout', rows: pluginRows.value },
  { key: 'server', title: 'Servidor', rows: serverRows.value },
]);

function statusClass(status) {
  const map = {
    success: 'bg-emerald-100 text-emerald-600',
    warning: 'bg-amber-100 text-amber-700',
    danger: 'bg-rose-100 text-rose-700',
    info: 'bg-slate-100 text-slate-600',
  };

  return map[status] || map.info;
}

function statusLabel(status) {
  if (status === 'success') return 'OK';
  if (status === 'warning') return 'Atenção';
  if (status === 'danger') return 'Erro';
  return 'Informações';
}
</script>

<template>
  <div class="space-y-6 py-5">
    <h3 class="m-0 text-[15px] font-semibold text-slate-800">Estado do sistema:</h3>

    <div class="grid gap-6 lg:grid-cols-3">
      <div v-for="column in columns" :key="column.key">
        <h4 class="m-0 text-[14px] font-semibold text-slate-700">{{ column.title }}</h4>

        <div class="mt-4 space-y-3">
          <div
            v-for="row in column.rows"
            :key="row.label"
            class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2"
          >
            <div class="pr-4">
              <div class="text-[13px] text-slate-500">{{ row.label }}</div>
              <div class="text-[14px] font-medium text-slate-700">{{ row.value }}</div>
              <div v-if="row.hint && row.status !== 'success'" class="mt-0.5 text-[11px] text-amber-700">{{ row.hint }}</div>
            </div>

            <span class="shrink-0 rounded-full px-3 py-1 text-[12px] font-semibold" :class="statusClass(row.status)">
              {{ statusLabel(row.status) }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
