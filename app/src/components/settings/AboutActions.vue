<script setup>
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';

const store = useSettingsStore();

function confirmReset() {
  if (window.confirm('Atenção! Você realmente deseja redefinir as configurações?\n\nAo redefinir as configurações do plugin, todas opções serão removidas, voltando ao estado original. Sua licença não será removida.')) {
    store.reset();
  }
}
</script>

<template>
  <div class="flex flex-wrap items-center gap-3 py-5">
    <BaseButton variant="outline-warning" :loading="store.resetting" @click="confirmReset">
      <BoxIcon name="reset" class="h-4 w-4" />
      Redefinir configurações
    </BaseButton>

    <a
      v-if="store.runtime?.report_problems_url"
      :href="store.runtime.report_problems_url"
      target="_blank"
      class="inline-flex items-center gap-2 rounded-lg border border-danger bg-white px-4 py-2 text-sm font-medium text-danger no-underline transition-colors hover:bg-danger hover:text-white"
    >
      Reportar problemas
    </a>
  </div>
</template>
