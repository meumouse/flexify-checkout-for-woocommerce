<script setup>
import { computed } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';

const store = useSettingsStore();

const system = computed(() => store.runtime?.system || {});

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

const phpSettings = computed(() => system.value.php_settings || {});

const serverRows = computed(() => [
  {
    label: 'Versão do PHP:',
    value: system.value.php_version || '—',
    ok: true,
    hint: '',
  },
  { label: 'DOMDocument:', value: system.value.extensions?.dom ? 'Sim' : 'Não', ok: Boolean(system.value.extensions?.dom) },
  { label: 'Extensão cURL:', value: system.value.extensions?.curl ? 'Sim' : 'Não', ok: Boolean(system.value.extensions?.curl) },
  { label: 'Extensão GD:', value: system.value.extensions?.gd ? 'Sim' : 'Não', ok: Boolean(system.value.extensions?.gd) },
  { label: 'Extensão OpenSSL:', value: system.value.extensions?.openssl ? 'Sim' : 'Não', ok: Boolean(system.value.extensions?.openssl) },
  {
    label: 'Tamanho máximo da postagem do PHP:',
    value: phpSettings.value.post_max_size || '—',
    ok: parseSize(phpSettings.value.post_max_size) >= 64,
    hint: 'Valor mínimo recomendado é 64M',
  },
  {
    label: 'Limite de tempo do PHP:',
    value: phpSettings.value.max_execution_time || '—',
    ok: Number(phpSettings.value.max_execution_time) === 0 || Number(phpSettings.value.max_execution_time) >= 180,
    hint: 'Valor mínimo recomendado é 180',
  },
  {
    label: 'Variáveis máximas de entrada do PHP:',
    value: phpSettings.value.max_input_vars || '—',
    ok: Number(phpSettings.value.max_input_vars) >= 10000,
    hint: 'Valor mínimo recomendado é 10000',
  },
  {
    label: 'Limite de memória do PHP:',
    value: phpSettings.value.memory_limit || '—',
    ok: parseSize(phpSettings.value.memory_limit) >= 128 || String(phpSettings.value.memory_limit) === '-1',
    hint: 'Valor mínimo recomendado é 128M',
  },
  {
    label: 'Tamanho máximo de envio do PHP:',
    value: phpSettings.value.upload_max_filesize || '—',
    ok: true,
  },
  {
    label: 'Função PHP "file_get_content":',
    value: system.value.file_get_content ? 'Ligado' : 'Desligado',
    ok: Boolean(system.value.file_get_content),
  },
]);
</script>

<template>
  <div class="flex flex-col gap-5 py-5">
    <h3 class="m-0 text-sm font-semibold text-brand">Status do sistema:</h3>

    <div>
      <p class="m-0 mb-2 text-[13px] font-semibold text-brand">WordPress</p>

      <div class="flex flex-col gap-2 text-sm text-ink">
        <div class="flex items-center gap-2">
          Versão do WordPress:
          <span class="font-medium">{{ system.wp_version || '—' }}</span>
        </div>
        <div class="flex items-center gap-2">
          WordPress Multisite:
          <span class="font-medium">{{ system.multisite ? 'Sim' : 'Não' }}</span>
        </div>
        <div class="flex items-center gap-2">
          Modo de depuração do WordPress:
          <span class="font-medium">{{ system.wp_debug ? 'Ativo' : 'Desativado' }}</span>
        </div>
      </div>
    </div>

    <div>
      <p class="m-0 mb-2 text-[13px] font-semibold text-brand">WooCommerce</p>

      <div class="flex flex-col gap-2 text-sm text-ink">
        <div class="flex items-center gap-2">
          Versão do WooCommerce:
          <span class="rounded-md bg-success/10 px-2 py-0.5 text-xs font-semibold text-success">{{ system.wc_version || '—' }}</span>
        </div>
        <div class="flex items-center gap-2">
          Versão do Flexify Checkout para WooCommerce:
          <span class="rounded-md bg-success/10 px-2 py-0.5 text-xs font-semibold text-success">
            {{ store.runtime?.version }}{{ store.isPro ? ' Pro' : '' }}
          </span>
        </div>
      </div>
    </div>

    <div>
      <p class="m-0 mb-2 text-[13px] font-semibold text-brand">Servidor</p>

      <div class="flex flex-col gap-2 text-sm text-ink">
        <div v-for="row in serverRows" :key="row.label" class="flex flex-wrap items-center gap-2">
          {{ row.label }}
          <span
            class="rounded-md px-2 py-0.5 text-xs font-semibold"
            :class="row.ok ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
          >
            {{ row.value }}
          </span>
          <span v-if="row.hint && !row.ok" class="text-xs text-muted">{{ row.hint }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
