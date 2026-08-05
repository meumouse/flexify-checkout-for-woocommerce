<script setup>
/**
 * Inline conditions editor (the inspector "Condições" tab).
 *
 * Ports the reference rule-card UI (então / quando / grupos / condições) onto the
 * project's conditions model. Operates on an editable rules draft owned by the
 * parent; structural rule add/remove is emitted, everything else is edited in
 * place. Value widgets reuse SearchMultiSelect/TagSelect for id/static subjects.
 *
 * @since 6.0.0
 */
import BaseSelect from '../../fields/BaseSelect.vue';
import SearchMultiSelect from '../../fields/SearchMultiSelect.vue';
import TagSelect from '../../fields/TagSelect.vue';
import BuilderTextInput from './BuilderTextInput.vue';
import {
  ACTION_TYPES, COMPONENTS, SUBJECTS, RULE_MATCH_OPTIONS, GROUP_MATCH_OPTIONS, DISCOUNT_MODES,
  SEARCH_TYPE, operatorsFor, valueKind, newGroup, newCondition, resetCondition, ruleValid,
} from './conditionsSchema.js';

const props = defineProps({
  rules: { type: Array, default: () => [] },
  fieldOptions: { type: Array, default: () => [] },
  shippingMethods: { type: Array, default: () => [] },
  paymentGateways: { type: Array, default: () => [] },
  countries: { type: Array, default: () => [] },
  userRoles: { type: Array, default: () => [] },
  shippingZones: { type: Array, default: () => [] },
  currencySymbol: { type: String, default: 'R$' },
});

const emit = defineEmits(['add-rule', 'delete-rule']);

const discountModes = DISCOUNT_MODES;

function targetOptions(rule) {
  if (rule.action.component === 'shipping') {
    return props.shippingMethods;
  }

  if (rule.action.component === 'payment') {
    return props.paymentGateways;
  }

  return props.fieldOptions;
}

function targetModel(rule) {
  if (rule.action.component === 'shipping') {
    return 'shipping_method';
  }

  if (rule.action.component === 'payment') {
    return 'payment_method';
  }

  return 'field';
}

function staticOptionsFor(subject) {
  if (subject === 'country') {
    return props.countries;
  }

  if (subject === 'user_role') {
    return props.userRoles;
  }

  if (subject === 'shipping_region') {
    return props.shippingZones;
  }

  return [];
}

function onActionType(rule, value) {
  rule.action.type = value;

  if (value === 'discount' && !rule.action.discount) {
    rule.action.discount = { mode: 'percent', value: 10, label: '' };
  }
}

function onSubject(condition, value) {
  condition.subject = value;
  resetCondition(condition);
}

function addGroup(rule) {
  rule.groups.push(newGroup());
}

function removeGroup(rule, index) {
  rule.groups.splice(index, 1);

  if (!rule.groups.length) {
    rule.groups.push(newGroup());
  }
}

function addCondition(group) {
  group.conditions.push(newCondition());
}

function removeCondition(group, index) {
  group.conditions.splice(index, 1);

  if (!group.conditions.length) {
    group.conditions.push(newCondition());
  }
}

function statusFor(rule) {
  if (!rule.enabled) {
    return { text: 'Regra desativada', tone: 'muted' };
  }

  if (!ruleValid(rule)) {
    return { text: 'Configuração incompleta', tone: 'warn' };
  }

  return { text: 'Regra ativa', tone: 'ok' };
}
</script>

<template>
  <div class="flex flex-col gap-2.5 px-3 pb-10 pt-3">
    <div class="flex items-center gap-2">
      <span class="flex-1 text-[11.5px] leading-snug text-slate-500">
        Mostre, oculte ou aplique descontos conforme regras. Condições de campo são avaliadas ao vivo no preview.
      </span>
      <button
        type="button"
        class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11.5px] font-medium text-slate-600 transition hover:bg-slate-50"
        @click="emit('add-rule')"
      >
        <BoxIcon name="plus" class="h-3 w-3" />
        Regra
      </button>
    </div>

    <p v-if="!rules.length" class="rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-3 py-8 text-center text-[12px] text-slate-400">
      Nenhuma regra criada. Clique em “Regra” para começar.
    </p>

    <div
      v-for="rule in rules"
      :key="rule._localId"
      class="overflow-hidden rounded-xl border bg-white"
      :class="rule.enabled ? 'border-slate-200' : 'border-slate-200 opacity-60'"
    >
      <!-- Card header -->
      <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/70 px-2.5 py-2">
        <span
          class="h-1.5 w-1.5 shrink-0 rounded-full"
          :class="rule.enabled && ruleValid(rule) ? 'bg-violet-500' : 'bg-slate-300'"
        />
        <input
          v-model="rule.name"
          class="min-w-0 flex-1 rounded-md border border-transparent bg-transparent px-1.5 py-1 text-[12.5px] font-semibold text-ink transition hover:border-slate-200 hover:bg-white focus:border-primary focus:bg-white focus:outline-none"
          placeholder="Nome da regra"
        />
        <span class="rounded border border-slate-200 bg-white px-1.5 py-0.5 font-mono text-[9px] font-bold uppercase tracking-wide text-slate-400">
          {{ rule.match === 'any' ? 'Qualquer' : 'Todos' }}
        </span>
        <button
          type="button"
          title="Ativar/desativar"
          class="flex cursor-pointer rounded border-0 bg-transparent p-1 text-slate-400 transition hover:bg-slate-100"
          @click="rule.enabled = !rule.enabled"
        >
          <BoxIcon :name="rule.enabled ? 'show' : 'hide'" class="h-3.5 w-3.5" />
        </button>
        <button
          type="button"
          title="Excluir regra"
          class="flex cursor-pointer rounded border-0 bg-transparent p-1 text-slate-400 transition hover:bg-danger/10 hover:text-danger"
          @click="emit('delete-rule', rule)"
        >
          <BoxIcon name="trash" class="h-3.5 w-3.5" />
        </button>
      </div>

      <!-- Card body -->
      <div class="flex flex-col gap-2.5 p-2.5">
        <!-- Then -->
        <div class="flex flex-wrap items-center gap-1.5">
          <span class="text-[11.5px] text-slate-500">Então</span>
          <BaseSelect
            :model-value="rule.action.type"
            :options="ACTION_TYPES"
            size="sm"
            class="w-32"
            @update:model-value="(v) => onActionType(rule, v)"
          />

          <template v-if="rule.action.type !== 'discount'">
            <BaseSelect v-model="rule.action.component" :options="COMPONENTS" size="sm" class="w-36" />
            <BaseSelect
              v-model="rule.action[targetModel(rule)]"
              :options="targetOptions(rule)"
              size="sm"
              class="w-40"
              placeholder="alvo…"
            />
          </template>

          <template v-else>
            <BaseSelect v-model="rule.action.discount.mode" :options="discountModes" size="sm" class="w-28" />
            <div class="w-20"><BuilderTextInput v-model="rule.action.discount.value" type="number" /></div>
            <div class="w-28"><BuilderTextInput v-model="rule.action.discount.label" placeholder="rótulo" /></div>
          </template>
        </div>

        <!-- When -->
        <div class="flex flex-wrap items-center gap-1.5">
          <span class="text-[11.5px] text-slate-500">Quando</span>
          <BaseSelect v-model="rule.match" :options="RULE_MATCH_OPTIONS" size="sm" class="w-40" />
          <span class="text-[11.5px] text-slate-500">corresponderem</span>
        </div>

        <!-- Groups -->
        <div
          v-for="(group, groupIndex) in rule.groups"
          :key="groupIndex"
          class="flex flex-col gap-2 rounded-lg border border-slate-100 bg-slate-50/70 p-2"
        >
          <div class="flex items-center gap-1.5">
            <BaseSelect v-model="group.match" :options="GROUP_MATCH_OPTIONS" size="sm" class="w-44" />
            <span class="flex-1"></span>
            <button
              type="button"
              class="flex cursor-pointer rounded border-0 bg-transparent p-1 text-slate-400 transition hover:bg-danger/10 hover:text-danger"
              title="Remover grupo"
              @click="removeGroup(rule, groupIndex)"
            >
              <BoxIcon name="trash" class="h-3 w-3" />
            </button>
          </div>

          <div
            v-for="(condition, condIndex) in group.conditions"
            :key="condIndex"
            class="flex flex-wrap items-center gap-1.5 rounded-lg border border-slate-100 bg-white p-1.5"
          >
            <BaseSelect
              :model-value="condition.subject"
              :options="SUBJECTS"
              size="sm"
              class="w-36"
              @update:model-value="(v) => onSubject(condition, v)"
            />

            <BaseSelect
              v-if="condition.subject === 'field'"
              v-model="condition.field"
              :options="fieldOptions"
              size="sm"
              class="w-32"
              placeholder="campo…"
            />

            <BaseSelect v-model="condition.operator" :options="operatorsFor(condition.subject)" size="sm" class="w-32" />

            <div class="min-w-[120px] flex-1">
              <SearchMultiSelect
                v-if="valueKind(condition) === 'search'"
                v-model="condition.selected"
                :type="SEARCH_TYPE[condition.subject]"
              />
              <TagSelect
                v-else-if="valueKind(condition) === 'tags'"
                v-model="condition.values"
                :options="staticOptionsFor(condition.subject)"
              />
              <BuilderTextInput
                v-else-if="valueKind(condition) === 'number'"
                v-model="condition.value"
                type="number"
                placeholder="valor"
              />
              <BuilderTextInput
                v-else-if="valueKind(condition) === 'text'"
                v-model="condition.value"
                placeholder="valor"
              />
              <span v-else class="block px-1 py-1.5 text-[11px] text-slate-400">sem valor</span>
            </div>

            <button
              type="button"
              class="ml-auto flex cursor-pointer rounded border-0 bg-transparent p-1 text-slate-300 transition hover:bg-danger/10 hover:text-danger"
              title="Remover condição"
              @click="removeCondition(group, condIndex)"
            >
              <BoxIcon name="x" class="h-3 w-3" />
            </button>
          </div>

          <button
            type="button"
            class="inline-flex cursor-pointer items-center gap-1 self-start rounded-lg border border-dashed border-slate-300 bg-transparent px-2 py-1 text-[11.5px] text-slate-500 transition hover:border-primary hover:text-primary"
            @click="addCondition(group)"
          >
            <BoxIcon name="plus" class="h-2.5 w-2.5" />
            Condição
          </button>
        </div>

        <button
          type="button"
          class="inline-flex cursor-pointer items-center gap-1 self-start rounded-lg border border-dashed border-slate-300 bg-transparent px-2.5 py-1 text-[11.5px] text-slate-500 transition hover:border-primary hover:text-primary"
          @click="addGroup(rule)"
        >
          <BoxIcon name="plus" class="h-2.5 w-2.5" />
          Grupo
        </button>

        <div
          class="rounded-lg border px-2 py-1.5 text-[11px]"
          :class="{
            'border-violet-100 bg-violet-50 text-violet-600': statusFor(rule).tone === 'ok',
            'border-amber-100 bg-amber-50 text-amber-600': statusFor(rule).tone === 'warn',
            'border-slate-100 bg-slate-50 text-slate-400': statusFor(rule).tone === 'muted',
          }"
        >
          {{ statusFor(rule).text }}
        </div>
      </div>
    </div>
  </div>
</template>
