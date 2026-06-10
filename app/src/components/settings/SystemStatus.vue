<script setup>
import { computed } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';

const store = useSettingsStore();

const system = computed(() => store.runtime?.system || {});

const rows = computed(() => [
  { label: 'Versão do plugin', value: store.runtime?.version || '—' },
  { label: 'WordPress', value: system.value.wp_version || '—' },
  { label: 'WooCommerce', value: system.value.wc_version || '—' },
  { label: 'PHP', value: system.value.php_version || '—' },
  { label: 'Limite de memória', value: system.value.php_settings?.memory_limit || '—' },
  { label: 'Tempo máximo de execução', value: system.value.php_settings?.max_execution_time || '—' },
  { label: 'Tamanho máximo de POST', value: system.value.php_settings?.post_max_size || '—' },
  { label: 'Tamanho máximo de upload', value: system.value.php_settings?.upload_max_filesize || '—' },
]);

const extensions = computed(() => {
  const list = system.value.extensions || {};

  return [
    { label: 'DOMDocument', enabled: Boolean(list.dom) },
    { label: 'cURL', enabled: Boolean(list.curl) },
    { label: 'OpenSSL', enabled: Boolean(list.openssl) },
    { label: 'GD', enabled: Boolean(list.gd) },
  ];
});
</script>

<template>
  <div class="grid gap-6 sm:grid-cols-2">
    <dl class="m-0 flex flex-col gap-2">
      <div v-for="row in rows" :key="row.label" class="flex items-center justify-between gap-4 text-sm">
        <dt class="text-muted">{{ row.label }}</dt>
        <dd class="m-0 font-medium text-ink">{{ row.value }}</dd>
      </div>
    </dl>

    <div>
      <p class="mb-2 mt-0 text-xs font-semibold uppercase tracking-wide text-muted">Extensões PHP</p>

      <ul class="m-0 flex list-none flex-col gap-2 p-0">
        <li v-for="extension in extensions" :key="extension.label" class="flex items-center gap-2 text-sm">
          <span
            class="inline-block h-2 w-2 rounded-full"
            :class="extension.enabled ? 'bg-success' : 'bg-danger'"
          />
          {{ extension.label }}
        </li>
      </ul>
    </div>
  </div>
</template>
