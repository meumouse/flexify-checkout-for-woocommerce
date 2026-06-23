<script setup>
import { ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';

const store = useSettingsStore();
const fileInput = ref(null);

function confirmReset() {
  if (window.confirm('Atenção! Você realmente deseja redefinir as configurações?\n\nAo redefinir as configurações do plugin, todas opções serão removidas, voltando ao estado original. Sua licença não será removida.')) {
    store.reset();
  }
}

function triggerImport() {
  fileInput.value?.click();
}

async function onFileSelected(event) {
  const file = event.target.files?.[0];

  // Reset the input so picking the same file again still fires `change`.
  event.target.value = '';

  if (!file) {
    return;
  }

  try {
    const text = await file.text();
    const payload = JSON.parse(text);

    await store.importSettings(payload);
  } catch (error) {
    store.pushToast('error', 'Não foi possível ler o arquivo. Verifique se é um JSON de configurações válido.', 'Arquivo inválido');
  }
}
</script>

<template>
  <div class="flex flex-wrap items-center gap-3 py-5">
    <BaseButton variant="outline" :loading="store.exporting" @click="store.exportSettings">
      <BoxIcon name="export" class="h-4 w-4" />
      Exportar configurações
    </BaseButton>

    <BaseButton variant="outline" :loading="store.importing" @click="triggerImport">
      <BoxIcon name="import" class="h-4 w-4" />
      Importar configurações
    </BaseButton>

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

    <input
      ref="fileInput"
      type="file"
      accept="application/json,.json"
      class="hidden"
      @change="onFileSelected"
    />
  </div>
</template>
