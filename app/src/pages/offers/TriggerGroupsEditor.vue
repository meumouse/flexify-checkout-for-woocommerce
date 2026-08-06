<script setup>
/**
 * Trigger groups editor for checkout offers.
 *
 * Renders the same two-level group/condition tree used by the checkout
 * conditions editor (ConditionBuilder.vue), minus the action picker: an offer's
 * "action" is implicit (show the offer). It mutates the passed `trigger` object
 * in place ({ match, groups }); the parent holds the same reference and reads it
 * back on save. An empty trigger (no conditions) means the offer always shows.
 *
 * @since 6.0.0
 */
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseSelect from '../../components/fields/BaseSelect.vue';
import SearchMultiSelect from '../../components/fields/SearchMultiSelect.vue';
import TagSelect from '../../components/fields/TagSelect.vue';
import {
  SUBJECTS,
  RULE_MATCH_OPTIONS,
  GROUP_MATCH_OPTIONS,
  SEARCH_TYPE,
  ID_SUBJECTS,
  STATIC_SUBJECTS,
  operatorsFor,
  valueKind,
  newGroup,
  newCondition,
  resetCondition,
} from '../../components/settings/builder/conditionsSchema';

const props = defineProps({
  trigger: { type: Object, required: true },
});

const store = useSettingsStore();

const billingFieldOptions = () =>
  Object.entries(store.fields || {})
    .filter(([id]) => id.startsWith('billing_'))
    .map(([id, field]) => ({ value: id, label: field?.label || id }));

const staticOptions = {
  country: () => store.runtime?.countries || [],
  user_role: () => store.runtime?.user_roles || [],
  shipping_region: () => store.runtime?.shipping_zones || [],
};

function onSubjectChange(condition, value) {
  if (value !== undefined) {
    condition.subject = value;
  }

  resetCondition(condition);
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

function addGroup() {
  props.trigger.groups.push(newGroup());
}

function removeGroup(index) {
  props.trigger.groups.splice(index, 1);
}
</script>

<template>
  <div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary-100 text-primary">
          <BoxIcon name="filter-alt" class="h-4 w-4" />
        </span>
        <div>
          <h3 class="m-0 text-sm font-semibold text-ink">Mostrar quando</h3>
          <p class="m-0 text-[12px] text-slate-500">Sem condições, a oferta aparece sempre.</p>
        </div>
      </div>

      <div v-if="trigger.groups.length > 1" class="flex items-center gap-2 text-sm text-slate-500">
        <span>Atender</span>
        <BaseSelect v-model="trigger.match" :options="RULE_MATCH_OPTIONS" size="sm" class="w-44" />
      </div>
    </div>

    <div class="flex flex-col gap-3">
      <template v-for="(group, groupIndex) in trigger.groups" :key="groupIndex">
        <div v-if="groupIndex > 0" class="flex items-center justify-center">
          <span class="rounded-full bg-slate-100 px-3 py-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
            {{ trigger.match === 'any' ? 'OU' : 'E' }}
          </span>
        </div>

        <div class="rounded-[10px] border border-slate-200 bg-slate-50/60 p-4">
          <div class="mb-3 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 text-sm text-slate-500">
              <span>Atender</span>
              <BaseSelect v-model="group.match" :options="GROUP_MATCH_OPTIONS" size="sm" class="w-48" />
            </div>

            <button
              type="button"
              class="cursor-pointer rounded-lg border border-danger/30 bg-white px-2 py-1 text-danger transition hover:bg-danger/10"
              aria-label="Remover grupo"
              @click="removeGroup(groupIndex)"
            >
              <BoxIcon name="trash" class="h-4 w-4" />
            </button>
          </div>

          <div class="flex flex-col gap-2">
            <template v-for="(condition, condIndex) in group.conditions" :key="condIndex">
              <div v-if="condIndex > 0" class="text-center text-xs font-semibold uppercase tracking-wide text-slate-400">
                {{ group.match === 'any' ? 'OU' : 'E' }}
              </div>

              <div class="flex flex-wrap items-start gap-2 rounded-lg border border-slate-200 bg-white p-2.5">
                <div class="min-w-[150px] flex-1">
                  <BaseSelect v-model="condition.subject" :options="SUBJECTS" size="sm" @update:model-value="(val) => onSubjectChange(condition, val)" />
                </div>

                <div v-if="condition.subject === 'field'" class="min-w-[140px] flex-1">
                  <BaseSelect v-model="condition.field" :options="billingFieldOptions()" placeholder="Campo..." size="sm" />
                </div>

                <div class="min-w-[120px] flex-1">
                  <BaseSelect v-model="condition.operator" :options="operatorsFor(condition.subject)" size="sm" />
                </div>

                <div class="min-w-[160px] flex-[2]">
                  <SearchMultiSelect
                    v-if="valueKind(condition) === 'search'"
                    v-model="condition.selected"
                    :type="SEARCH_TYPE[condition.subject]"
                  />

                  <TagSelect
                    v-else-if="valueKind(condition) === 'tags'"
                    v-model="condition.values"
                    :options="staticOptions[condition.subject]()"
                  />

                  <input
                    v-else-if="valueKind(condition) === 'number'"
                    v-model="condition.value"
                    type="number"
                    step="0.01"
                    class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-100"
                    placeholder="Valor"
                  />

                  <input
                    v-else-if="valueKind(condition) === 'text'"
                    v-model="condition.value"
                    type="text"
                    class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-100"
                    placeholder="Valor"
                  />

                  <p v-else class="px-1 py-2 text-xs text-slate-400">Sem valor</p>
                </div>

                <button
                  type="button"
                  class="cursor-pointer rounded-lg border border-slate-200 bg-white px-2 py-2 text-slate-400 transition hover:border-danger/30 hover:text-danger"
                  aria-label="Remover condição"
                  @click="removeCondition(group, condIndex)"
                >
                  <BoxIcon name="x" class="h-4 w-4" />
                </button>
              </div>
            </template>
          </div>

          <button
            type="button"
            class="mt-3 inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-dashed border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:border-primary hover:text-primary"
            @click="addCondition(group)"
          >
            <BoxIcon name="plus" class="h-4 w-4" />
            Adicionar condição
          </button>
        </div>
      </template>
    </div>

    <button
      type="button"
      class="mt-4 inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:border-primary hover:text-primary"
      @click="addGroup"
    >
      <BoxIcon name="plus" class="h-4 w-4" />
      Adicionar grupo
    </button>
  </div>
</template>
