<script setup>
import { computed, ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';

const store = useSettingsStore();

const newProvider = ref('');

const providers = computed(() => {
  const list = store.settings?.set_email_providers;

  return Array.isArray(list) ? list : Object.values(list || {});
});

function addProvider() {
  const value = newProvider.value.trim().toLowerCase().replace(/^@/, '');

  if (!value || providers.value.includes(value)) {
    return;
  }

  store.setSetting('set_email_providers', [...providers.value, value]);
  newProvider.value = '';
}

function removeProvider(provider) {
  store.setSetting('set_email_providers', providers.value.filter((item) => item !== provider));
}
</script>

<template>
  <div class="flex flex-col gap-3">
    <div v-if="providers.length" class="flex flex-wrap gap-1.5">
      <span
        v-for="provider in providers"
        :key="provider"
        class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-ink"
      >
        {{ provider }}

        <button
          type="button"
          class="cursor-pointer border-0 bg-transparent p-0 leading-none text-muted hover:text-danger"
          aria-label="Remover provedor"
          @click="removeProvider(provider)"
        >
          <BoxIcon name="x" class="h-3.5 w-3.5" />
        </button>
      </span>
    </div>

    <div class="flex items-center gap-2">
      <input
        v-model="newProvider"
        type="text"
        placeholder="exemplo.com.br"
        class="flexify-field-input w-full max-w-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100"
        @keyup.enter="addProvider"
      />

      <BaseButton variant="secondary" @click="addProvider">Adicionar</BaseButton>
    </div>

    <p class="m-0 text-xs text-muted">
      As alterações são aplicadas ao clicar em "Salvar alterações" no rodapé da página.
    </p>
  </div>
</template>
