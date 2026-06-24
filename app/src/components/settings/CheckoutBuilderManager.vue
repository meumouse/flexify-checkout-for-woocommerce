<script setup>
import { computed, ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';
import CheckoutBuilder from './CheckoutBuilder.vue';

const store = useSettingsStore();

const builderOpen = ref(false);

const steps = computed(() => store.layout?.steps || []);

const stepCount = computed(() => steps.value.length);

const fieldCount = computed(() =>
  steps.value.reduce((total, step) => total + (step.items || []).filter((item) => item.kind === 'field').length, 0),
);

const componentCount = computed(() =>
  steps.value.reduce((total, step) => total + (step.items || []).filter((item) => item.kind === 'component').length, 0),
);

function openBuilder() {
  builderOpen.value = true;
}

function closeBuilder() {
  builderOpen.value = false;
}
</script>

<template>
  <div>
    <div
      v-if="!store.isPro"
      class="mb-4 rounded-xl border border-warning/40 bg-warning/10 px-4 py-3 text-sm text-ink"
    >
      O construtor de checkout requer uma licença Pro ativa. Ative sua licença na aba Sobre.
    </div>

    <div :class="!store.isPro ? 'pointer-events-none opacity-50' : ''">
      <div class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-slate-50/60 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
          <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-100 text-primary">
            <BoxIcon name="layout" class="h-6 w-6" />
          </span>

          <div class="flex flex-wrap items-center gap-x-5 gap-y-1">
            <div>
              <p class="m-0 text-lg font-semibold leading-none text-ink">{{ stepCount }}</p>
              <p class="m-0 mt-1 text-xs text-muted">etapas</p>
            </div>
            <div>
              <p class="m-0 text-lg font-semibold leading-none text-ink">{{ fieldCount }}</p>
              <p class="m-0 mt-1 text-xs text-muted">campos</p>
            </div>
            <div>
              <p class="m-0 text-lg font-semibold leading-none text-ink">{{ componentCount }}</p>
              <p class="m-0 mt-1 text-xs text-muted">componentes</p>
            </div>
          </div>
        </div>

        <BaseButton @click="openBuilder">
          <BoxIcon name="layout" class="h-4 w-4" />
          Abrir construtor
        </BaseButton>
      </div>
    </div>

    <CheckoutBuilder :open="builderOpen" @close="closeBuilder" />
  </div>
</template>
