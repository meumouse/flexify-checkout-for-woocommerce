<script setup>
import { ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';
import ModalDialog from '../modals/ModalDialog.vue';

const store = useSettingsStore();
const fileInput = ref(null);
const resetModalOpen = ref(false);

function openResetModal() {
  resetModalOpen.value = true;
}

function closeResetModal() {
  resetModalOpen.value = false;
}

async function confirmReset() {
  await store.reset();
  closeResetModal();
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

    <BaseButton variant="outline-warning" :loading="store.resetting" @click="openResetModal">
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

    <ModalDialog :open="resetModalOpen" title="Redefinir configurações" @close="closeResetModal">
      <div class="flex flex-col items-center gap-4 text-center">
        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-warning/10 text-warning">
          <BoxIcon name="reset" class="h-7 w-7" />
        </span>

        <p class="m-0 max-w-md text-[15px] leading-6 text-slate-600">
          Você realmente deseja redefinir as configurações? Todas as opções serão removidas e voltarão ao
          estado original. Sua licença <strong>não</strong> será removida.
        </p>
      </div>

      <template #footer>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-center">
          <BaseButton variant="secondary" :disabled="store.resetting" @click="closeResetModal">
            Cancelar
          </BaseButton>
          <BaseButton variant="danger" :loading="store.resetting" @click="confirmReset">
            Redefinir configurações
          </BaseButton>
        </div>
      </template>
    </ModalDialog>
  </div>
</template>
