<script setup>
import { ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';

const store = useSettingsStore();

const licenseKey = ref('');
const activating = ref(false);
const deactivating = ref(false);
const syncing = ref(false);
const altActivating = ref(false);
const altFileInput = ref(null);

async function activate() {
  if (!licenseKey.value || activating.value) {
    return;
  }

  activating.value = true;

  try {
    await store.licenseAction('admin/license/activate', { license_key: licenseKey.value });
  } finally {
    activating.value = false;
  }
}

async function deactivate() {
  if (deactivating.value) {
    return;
  }

  deactivating.value = true;

  try {
    await store.licenseAction('admin/license/deactivate');
    licenseKey.value = '';
  } finally {
    deactivating.value = false;
  }
}

async function sync() {
  if (syncing.value) {
    return;
  }

  syncing.value = true;

  try {
    await store.licenseAction('admin/license/sync');
  } finally {
    syncing.value = false;
  }
}

function pickAltFile() {
  if (altActivating.value) {
    return;
  }

  altFileInput.value?.click();
}

async function onAltFileChange(event) {
  const file = event.target.files?.[0];

  // Reset the input so selecting the same file again re-triggers the change.
  event.target.value = '';

  if (!file || altActivating.value) {
    return;
  }

  altActivating.value = true;

  try {
    await store.alternativeActivate(file);
  } finally {
    altActivating.value = false;
  }
}
</script>

<template>
  <div class="flex flex-col gap-4 py-5">
    <h3 class="m-0 text-sm font-semibold text-brand">Informações sobre a licença:</h3>

    <div class="flex flex-col gap-2.5 text-sm text-ink">
      <div class="flex items-center gap-2">
        <span>Status da licença:</span>
        <span
          class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
          :class="store.license?.is_valid ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
        >
          {{ store.license?.is_valid ? 'Válida' : 'Inválida' }}
        </span>
      </div>

      <div class="flex items-center gap-2">
        <span>Recursos:</span>
        <span
          class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
          :class="store.license?.is_valid ? 'bg-primary-100 text-primary' : 'bg-warning/10 text-warning'"
        >
          {{ store.license?.is_valid ? 'Pro' : 'Básicos' }}
        </span>
      </div>

      <div v-if="store.license?.title">Assinatura: {{ store.license.title }}</div>
      <div v-if="store.license?.expire">Licença expira em: {{ store.license.expire }}</div>
      <div v-if="store.license?.masked_key">Sua chave de licença: {{ store.license.masked_key }}</div>
    </div>

    <template v-if="!store.license?.is_valid">
      <p class="m-0 text-xs italic text-slate-500">Informe sua licença abaixo para desbloquear todos os recursos.</p>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <input
          v-model="licenseKey"
          type="text"
          placeholder="Código da licença"
          class="flexify-field-input w-full max-w-md rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100"
        />

        <BaseButton :loading="activating" @click="activate">Ativar licença</BaseButton>
      </div>

      <a
        v-if="store.license?.buy_url"
        :href="store.license.buy_url"
        target="_blank"
        class="w-fit text-sm text-primary underline-offset-2 hover:underline"
      >
        Comprar licença
      </a>

      <div class="mt-1 flex flex-col gap-2 border-t border-slate-100 pt-4">
        <span class="text-sm font-semibold text-ink">Ativação alternativa (offline)</span>
        <p class="m-0 text-xs italic text-slate-500">
          Caso não consiga ativar pela chave, envie o arquivo <code>.key</code> fornecido pelo suporte.
        </p>

        <input
          ref="altFileInput"
          type="file"
          accept=".key"
          class="hidden"
          @change="onAltFileChange"
        />

        <BaseButton variant="outline" :loading="altActivating" @click="pickAltFile">
          Enviar arquivo de licença
        </BaseButton>
      </div>
    </template>

    <div v-else class="flex flex-wrap gap-3">
      <BaseButton :loading="deactivating" @click="deactivate">Desativar licença</BaseButton>
      <BaseButton variant="outline" :loading="syncing" @click="sync">Sincronizar licença</BaseButton>
    </div>
  </div>
</template>
