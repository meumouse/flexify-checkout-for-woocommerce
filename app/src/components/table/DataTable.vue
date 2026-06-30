<script setup>
/**
 * Reusable admin data table.
 *
 * Reproduces the shared "log table" chrome used across the recovery admin pages
 * (abandoned carts, processing queue): a card wrapping status filter tabs with
 * count badges, a search + date-range filter row, a bulk-selection action bar,
 * a checkbox-selectable table and arrow pagination.
 *
 * Columns are declared via the `columns` prop; cell content can be customised
 * per column through a `#cell-<key>` scoped slot. Per-row controls go in the
 * `#actions` slot. Selection is tracked internally and surfaced through the
 * `delete-selected` event; filter/pagination changes are emitted for the parent
 * to refetch.
 *
 * @since 6.0.0
 */
import { ref, computed, watch, useSlots } from 'vue';
import BoxIcon from '../icons/BoxIcon.vue';
import DatePicker from '../fields/DatePicker.vue';
import TableSkeleton from '../skeletons/TableSkeleton.vue';

const props = defineProps({
  /** Column definitions: { key, label, align?, headerClass?, cellClass? }. */
  columns: { type: Array, required: true },
  /** Row objects to render. Each must expose the `rowKey` field. */
  rows: { type: Array, default: () => [] },
  /** Unique identifier field on each row. */
  rowKey: { type: String, default: 'id' },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
  /** Filter tabs: { value, label, count }. Hidden when empty. */
  tabs: { type: Array, default: () => [] },
  activeTab: { type: String, default: 'all' },
  /** Search box (v-model:search). */
  search: { type: String, default: '' },
  showSearch: { type: Boolean, default: true },
  searchPlaceholder: { type: String, default: 'Pesquisar…' },
  /** Date-range filter (v-model:date-from / v-model:date-to). */
  showDateFilter: { type: Boolean, default: true },
  dateFrom: { type: String, default: '' },
  dateTo: { type: String, default: '' },
  /** Row selection + bulk actions. */
  selectable: { type: Boolean, default: true },
  /** Pagination state. */
  page: { type: Number, default: 1 },
  totalPages: { type: Number, default: 1 },
  total: { type: Number, default: 0 },
  perPage: { type: Number, default: 20 },
  /** Disables destructive controls while a request is in flight. */
  busy: { type: Boolean, default: false },
  emptyText: { type: String, default: 'Nenhum registro encontrado.' },
});

const emit = defineEmits([
  'update:activeTab',
  'update:search',
  'update:dateFrom',
  'update:dateTo',
  'apply',
  'clear-filters',
  'page-change',
  'delete-selected',
  'clear-all',
]);

const slots = useSlots();
const selected = ref([]);

// Reset the selection whenever the underlying rows change (page/tab/filter
// change or a refetch after delete) so stale ids never leak into a bulk action.
watch(() => props.rows, () => { selected.value = []; });

const hasActions = computed(() => !!slots.actions);
const colspan = computed(
  () => props.columns.length + (props.selectable ? 1 : 0) + (hasActions.value ? 1 : 0),
);

const allSelected = computed(
  () => props.rows.length > 0 && selected.value.length === props.rows.length,
);
const someSelected = computed(
  () => selected.value.length > 0 && selected.value.length < props.rows.length,
);

const rangeLabel = computed(() => {
  if (!props.total) return '0';
  const from = (props.page - 1) * props.perPage + 1;
  const to = Math.min(props.page * props.perPage, props.total);
  return `${from}–${to} de ${props.total}`;
});

const hasActiveFilters = computed(
  () => props.search !== '' || props.dateFrom !== '' || props.dateTo !== '' || props.activeTab !== 'all',
);

function isSelected(id) {
  return selected.value.includes(id);
}

function toggleRow(id) {
  selected.value = isSelected(id)
    ? selected.value.filter((value) => value !== id)
    : [...selected.value, id];
}

function toggleAll() {
  selected.value = allSelected.value ? [] : props.rows.map((row) => row[props.rowKey]);
}

function selectTab(value) {
  if (value === props.activeTab) return;
  emit('update:activeTab', value);
  emit('apply');
}

function submitSearch() {
  emit('apply');
}

function onDate(field, value) {
  emit(field === 'from' ? 'update:dateFrom' : 'update:dateTo', value);
  emit('apply');
}

function clearFilters() {
  emit('update:search', '');
  emit('update:dateFrom', '');
  emit('update:dateTo', '');
  emit('update:activeTab', 'all');
  emit('clear-filters');
}

function goTo(next) {
  if (next < 1 || next > props.totalPages || next === props.page) return;
  emit('page-change', next);
}

function deleteSelected() {
  if (!selected.value.length) return;
  emit('delete-selected', [...selected.value]);
}
</script>

<template>
  <div class="overflow-hidden rounded-[12px] bg-white shadow-soft ring-1 ring-slate-100">
    <div class="p-5">
      <!-- Status / event filter tabs -->
      <div v-if="tabs.length" class="mb-4 flex flex-wrap gap-2">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-[13px] font-semibold transition-colors"
          :class="tab.value === activeTab
            ? 'bg-primary text-white'
            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          @click="selectTab(tab.value)"
        >
          {{ tab.label }}
          <span
            class="text-[12px] font-semibold"
            :class="tab.value === activeTab ? 'text-white/80' : 'text-slate-400'"
          >{{ tab.count }}</span>
        </button>
      </div>

      <!-- Filter row -->
      <div class="flex flex-wrap items-end gap-3">
        <slot name="toolbar" />

        <label v-if="showDateFilter" class="flex flex-col gap-1">
          <span class="text-[12px] font-medium text-slate-500">De</span>
          <input
            type="date"
            class="flexify-field-input w-auto"
            :value="dateFrom"
            @change="onDate('from', $event.target.value)"
          />
        </label>

        <label v-if="showDateFilter" class="flex flex-col gap-1">
          <span class="text-[12px] font-medium text-slate-500">Para</span>
          <input
            type="date"
            class="flexify-field-input w-auto"
            :value="dateTo"
            @change="onDate('to', $event.target.value)"
          />
        </label>

        <form v-if="showSearch" class="flex min-w-[220px] flex-1 flex-col gap-1" @submit.prevent="submitSearch">
          <span class="text-[12px] font-medium text-slate-500">{{ searchPlaceholder }}</span>
          <input
            :value="search"
            type="search"
            :placeholder="searchPlaceholder"
            class="flexify-field-input w-full"
            @input="emit('update:search', $event.target.value)"
            @search="submitSearch"
          />
        </form>

        <button
          type="button"
          class="rounded-[8px] border border-slate-200 px-4 py-2 text-[13px] font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-40"
          :disabled="!hasActiveFilters || busy"
          @click="clearFilters"
        >Limpar filtros</button>
      </div>

      <!-- Bulk action bar -->
      <div class="mt-4 flex flex-wrap items-center gap-3">
        <button
          v-if="selectable && selected.length"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-[8px] border border-danger/30 px-3.5 py-2 text-[13px] font-semibold text-danger hover:bg-danger/10 disabled:opacity-40"
          :disabled="busy"
          @click="deleteSelected"
        >
          <BoxIcon name="trash" class="h-4 w-4" />
          Excluir selecionados ({{ selected.length }})
        </button>

        <button
          type="button"
          class="rounded-[8px] border border-slate-200 px-3.5 py-2 text-[13px] font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-40"
          :disabled="busy || !total"
          @click="emit('clear-all')"
        >Limpar tudo</button>

        <span class="ml-auto text-[13px] text-slate-500">{{ rangeLabel }}</span>
      </div>
    </div>

    <div v-if="error" class="mx-5 mb-5 rounded-[8px] bg-danger/10 px-5 py-4 text-[14px] text-danger" role="alert">
      {{ error }}
    </div>

    <!-- Table -->
    <div class="border-t border-slate-100">
      <TableSkeleton
        v-if="loading"
        :columns="columns"
        :selectable="selectable"
        :has-actions="hasActions"
      />

      <div v-else class="overflow-x-auto">
        <table class="w-full border-collapse text-[13px]">
          <thead>
            <tr class="border-b border-slate-100 text-left text-slate-500">
              <th v-if="selectable" class="w-10 px-4 py-3">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 accent-[#0d6efd]"
                  :checked="allSelected"
                  :indeterminate.prop="someSelected"
                  :disabled="!rows.length"
                  @change="toggleAll"
                />
              </th>
              <th
                v-for="col in columns"
                :key="col.key"
                class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wide"
                :class="[col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '', col.headerClass]"
              >{{ col.label }}</th>
              <th v-if="hasActions" class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!rows.length">
              <td :colspan="colspan" class="px-4 py-10 text-center text-slate-500">
                <slot name="empty">{{ emptyText }}</slot>
              </td>
            </tr>
            <tr
              v-for="row in rows"
              :key="row[rowKey]"
              class="border-b border-slate-50 align-middle transition-colors hover:bg-slate-50/70"
              :class="{ 'bg-primary-50/60': selectable && isSelected(row[rowKey]) }"
            >
              <td v-if="selectable" class="px-4 py-3">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 accent-[#0d6efd]"
                  :checked="isSelected(row[rowKey])"
                  @change="toggleRow(row[rowKey])"
                />
              </td>
              <td
                v-for="col in columns"
                :key="col.key"
                class="px-4 py-3"
                :class="[col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '', col.cellClass]"
              >
                <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">{{ row[col.key] }}</slot>
              </td>
              <td v-if="hasActions" class="px-4 py-3 text-right">
                <slot name="actions" :row="row" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Footer / pagination -->
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3">
      <span class="text-[13px] text-slate-500">{{ rangeLabel }}</span>

      <div class="flex items-center gap-1">
        <button type="button" class="flexify-pager" :disabled="page <= 1 || loading" @click="goTo(1)" aria-label="Primeira página">
          <BoxIcon name="chevrons-left" class="h-4 w-4" />
        </button>
        <button type="button" class="flexify-pager" :disabled="page <= 1 || loading" @click="goTo(page - 1)" aria-label="Página anterior">
          <BoxIcon name="chevron-left" class="h-4 w-4" />
        </button>
        <span class="px-2 text-[13px] font-medium text-slate-600">{{ page }} / {{ totalPages }}</span>
        <button type="button" class="flexify-pager" :disabled="page >= totalPages || loading" @click="goTo(page + 1)" aria-label="Próxima página">
          <BoxIcon name="chevron-right" class="h-4 w-4" />
        </button>
        <button type="button" class="flexify-pager" :disabled="page >= totalPages || loading" @click="goTo(totalPages)" aria-label="Última página">
          <BoxIcon name="chevrons-right" class="h-4 w-4" />
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.flexify-pager {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 8px;
  color: #475569;
  transition: background-color 0.15s ease, color 0.15s ease;
}

.flexify-pager:hover:not(:disabled) {
  background-color: #f1f5f9;
  color: #0d6efd;
}

.flexify-pager:disabled {
  opacity: 0.35;
  cursor: default;
}
</style>
