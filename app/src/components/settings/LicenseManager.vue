<script setup>
import { ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';

const store = useSettingsStore();

const licenseKey = ref(store.license?.key || '');
const activating = ref(false);
const deactivating = ref(false);
const syncing = ref(false);

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
</script>

<template>
  <div class="flex flex-col gap-4">
    <div class="flex flex-wrap items-center gap-3">
      <span class="text-sm text-muted">Status:</span>

      <span
        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
        :class="store.license?.is_valid ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
      >
        {{ store.license?.is_valid ? 'Válida' : 'Inválida' }}
      </span>

      <span v-if="store.license?.title" class="text-xs text-muted">
        {{ store.license.title }}
      </span>

      <span v-if="store.license?.expire" class="text-xs text-muted">
        Expira em: {{ store.license.expire }}
      </span>
    </div>

    <div v-if="!store.license?.is_valid" class="flex flex-col gap-3 sm:flex-row sm:items-center">
      <input
        v-model="licenseKey"
        type="text"
        placeholder="Informe o código da licença"
        class="flexify-field-input w-full max-w-md rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100"
      />

      <BaseButton :loading="activating" @click="activate">Ativar licença</BaseButton>
    </div>

    <div v-else class="flex flex-wrap gap-3">
      <BaseButton variant="secondary" :loading="syncing" @click="sync">Sincronizar licença</BaseButton>
      <BaseButton variant="danger" :loading="deactivating" @click="deactivate">Desativar licença</BaseButton>
    </div>
  </div>
</template>
