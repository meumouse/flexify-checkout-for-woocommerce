<script setup>
import { computed } from 'vue';
import { readBootstrapConfig } from '../../utils/bootstrap';

const props = defineProps({
  tabId: { type: String, default: '' },
});

const legacyUrl = computed(() => {
  const config = readBootstrapConfig() || {};
  const base = config.legacyUrl || '';

  return props.tabId ? `${base}#${props.tabId}` : base;
});
</script>

<template>
  <div class="flex flex-col items-start gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-5">
    <p class="m-0 text-sm leading-relaxed text-muted">
      Este gerenciador está sendo migrado para a nova interface. Enquanto isso, continue usando a versão atual dele
      na interface clássica de configurações.
    </p>

    <a
      v-if="legacyUrl"
      :href="legacyUrl"
      class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white no-underline transition-colors hover:bg-primary-800 hover:text-white"
    >
      Abrir interface clássica
      <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M7 17L17 7M7 7h10v10" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    </a>
  </div>
</template>
