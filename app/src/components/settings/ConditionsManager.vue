<script setup>
import { computed, ref } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../buttons/BaseButton.vue';
import ConditionBuilder from './ConditionBuilder.vue';

const store = useSettingsStore();

const builderOpen = ref(false);
const editingRule = ref(null);

const rules = computed(() => store.conditions || []);

const ACTION_BADGE = {
  show: { label: 'Mostrar', class: 'bg-success/10 text-success' },
  hide: { label: 'Ocultar', class: 'bg-danger/10 text-danger' },
  discount: { label: 'Desconto', class: 'bg-primary-100 text-primary' },
};

function badgeFor(rule) {
  return ACTION_BADGE[rule?.action?.type] || ACTION_BADGE.show;
}

function openCreate() {
  editingRule.value = null;
  builderOpen.value = true;
}

function openEdit(rule) {
  editingRule.value = rule;
  builderOpen.value = true;
}

function closeBuilder() {
  builderOpen.value = false;
  editingRule.value = null;
}

async function toggleEnabled(rule) {
  await store.saveCondition({ ...rule, enabled: !rule.enabled }, rule.id);
}

async function remove(rule) {
  if (window.confirm('Tem certeza que deseja excluir esta regra?')) {
    await store.removeCondition(rule.id);
  }
}
</script>

<template>
  <div>
    <div
      v-if="!store.isPro"
      class="mb-4 rounded-xl border border-warning/40 bg-warning/10 px-4 py-3 text-sm text-ink"
    >
      O gerenciador de condições requer uma licença Pro ativa. Ative sua licença na aba Sobre.
    </div>

    <div :class="!store.isPro ? 'pointer-events-none opacity-50' : ''">
      <div class="mb-4 flex items-center justify-between gap-3">
        <p class="m-0 text-sm text-muted">
          Mostre, oculte ou aplique descontos em campos, entrega e pagamento conforme regras condicionais.
        </p>

        <BaseButton size="sm" @click="openCreate">
          <BoxIcon name="plus" class="h-4 w-4" />
          Criar regra
        </BaseButton>
      </div>

      <div
        v-if="!rules.length"
        class="rounded-xl border border-dashed border-slate-300 bg-slate-50/60 px-4 py-10 text-center"
      >
        <p class="m-0 text-sm font-medium text-ink">Nenhuma regra criada</p>
        <p class="m-0 mt-1 text-xs text-muted">Crie sua primeira regra condicional para o checkout.</p>
      </div>

      <ul v-else class="m-0 flex list-none flex-col gap-2 p-0">
        <li
          v-for="rule in rules"
          :key="rule.id"
          class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 transition hover:border-slate-300"
          :class="rule.enabled === false ? 'opacity-60' : ''"
        >
          <div class="flex min-w-0 items-center gap-3">
            <span
              class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-semibold"
              :class="badgeFor(rule).class"
            >
              {{ badgeFor(rule).label }}
            </span>

            <div class="min-w-0">
              <p class="m-0 truncate text-sm font-medium text-ink">
                {{ rule.name || rule.summary?.line_1 }}
              </p>
              <p class="m-0 mt-0.5 truncate text-xs text-muted">{{ rule.summary?.line_2 }}</p>
            </div>
          </div>

          <div class="flex shrink-0 items-center gap-1.5">
            <label class="mr-1 inline-flex cursor-pointer items-center" :title="rule.enabled === false ? 'Inativa' : 'Ativa'">
              <input
                type="checkbox"
                class="peer sr-only"
                :checked="rule.enabled !== false"
                @change="toggleEnabled(rule)"
              />
              <span class="relative h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-primary after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-4" />
            </label>

            <button
              type="button"
              class="cursor-pointer rounded-lg border border-slate-300 bg-transparent px-2.5 py-1 text-xs font-medium text-ink transition-colors hover:bg-slate-100"
              @click="openEdit(rule)"
            >
              Editar
            </button>

            <button
              type="button"
              class="cursor-pointer rounded-lg border border-danger/30 bg-transparent px-2 py-1 text-danger transition-colors hover:bg-danger/10"
              aria-label="Excluir regra"
              @click="remove(rule)"
            >
              <BoxIcon name="trash" class="h-4 w-4" />
            </button>
          </div>
        </li>
      </ul>
    </div>

    <ConditionBuilder :open="builderOpen" :rule="editingRule" @close="closeBuilder" />
  </div>
</template>
