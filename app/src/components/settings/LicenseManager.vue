<script setup>
import { ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';

const store = useSettingsStore();

const licenseKey = ref('');
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
  <div class="py-5">
    <!-- Section heading -->
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <span class="text-xs font-semibold uppercase tracking-wider text-primary">Licença</span>
        <h2 class="m-0 mt-1 text-lg font-semibold text-brand">Ativar licença</h2>
        <p class="m-0 mt-1 text-sm text-slate-600">
          Insira sua chave de licença para desbloquear os recursos premium.
        </p>
      </div>

      <a
        v-if="store.license?.buy_url"
        :href="store.license.buy_url"
        target="_blank"
        rel="noopener"
      >
        <BaseButton variant="secondary">Comprar licença</BaseButton>
      </a>
    </div>

    <!-- Two-column layout -->
    <div class="mt-6 grid gap-5 lg:grid-cols-[1.5fr_1fr]">
      <!-- Activation / status card -->
      <section class="rounded-[8px] border border-slate-200 p-6">
        <div class="flex items-start justify-between gap-3">
          <h3 class="m-0 text-base font-semibold text-brand">
            {{ store.license?.is_valid ? 'Licença ativa' : 'Ativar licença' }}
          </h3>

          <span
            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
            :class="store.license?.is_valid ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
          >
            {{ store.license?.is_valid ? 'Válida' : 'Inválida' }}
          </span>
        </div>

        <!-- Invalid: activation flow -->
        <template v-if="!store.license?.is_valid">
          <p class="mb-0 mt-2 text-sm text-slate-600">
            Cole a chave de licença que você recebeu após a compra e clique em Ativar para desbloquear
            todos os recursos.
          </p>

          <input
            v-model="licenseKey"
            type="text"
            placeholder="Exemplo: CM-0000-0000-0000"
            class="flexify-field-input mt-4 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100"
          />

          <div class="mt-4 flex flex-wrap gap-3">
            <BaseButton :loading="activating" @click="activate">Ativar licença</BaseButton>

            <a
              v-if="store.license?.buy_url"
              :href="store.license.buy_url"
              target="_blank"
              rel="noopener"
            >
              <BaseButton variant="success">Comprar licença</BaseButton>
            </a>
          </div>
        </template>

        <!-- Valid: license details + management -->
        <template v-else>
          <dl class="mt-4 flex flex-col gap-2.5 text-sm text-ink">
            <div v-if="store.license?.title" class="flex flex-wrap gap-x-2">
              <dt class="text-slate-500">Assinatura:</dt>
              <dd class="m-0 font-medium">{{ store.license.title }}</dd>
            </div>
            <div v-if="store.license?.expire" class="flex flex-wrap gap-x-2">
              <dt class="text-slate-500">Licença expira em:</dt>
              <dd class="m-0 font-medium">{{ store.license.expire }}</dd>
            </div>
            <div v-if="store.license?.masked_key" class="flex flex-wrap gap-x-2">
              <dt class="text-slate-500">Sua chave de licença:</dt>
              <dd class="m-0 font-medium">{{ store.license.masked_key }}</dd>
            </div>
          </dl>

          <div class="mt-5 flex flex-wrap gap-3">
            <BaseButton :loading="deactivating" @click="deactivate">Desativar licença</BaseButton>
            <BaseButton variant="outline" :loading="syncing" @click="sync">Sincronizar licença</BaseButton>
          </div>
        </template>
      </section>

      <!-- What you unlock -->
      <aside class="rounded-[8px] border border-dashed border-slate-200 bg-slate-50/60 p-6">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">
          O que você desbloqueia
        </span>

        <h3 class="m-0 mt-2 text-base font-semibold text-brand">
          Ative a licença para desbloquear os recursos premium do Flexify Checkout.
        </h3>

        <ul class="m-0 mt-4 flex list-none flex-col gap-3 p-0 text-sm text-slate-600">
          <li class="flex items-start gap-2.5">
            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" aria-hidden="true" />
            Atualizações automáticas e sincronização da licença
          </li>
          <li class="flex items-start gap-2.5">
            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" aria-hidden="true" />
            Validação de acesso e desbloqueio de recursos premium
          </li>
          <li class="flex items-start gap-2.5">
            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" aria-hidden="true" />
            Acesso ao suporte e atualizações da assinatura
          </li>
        </ul>
      </aside>
    </div>
  </div>
</template>
