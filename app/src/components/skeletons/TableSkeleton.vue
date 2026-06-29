<script setup>
/**
 * Loading placeholder for <DataTable>.
 *
 * Reproduces the real table chrome (the same header row with column labels,
 * the selection checkbox column and the per-row action cell) while swapping the
 * body cells for shimmering bars. This keeps the skeleton in the exact format of
 * the loaded table so the layout does not shift once data arrives.
 *
 * @since 6.0.0
 */
defineProps({
  /** Same column definitions passed to <DataTable>. */
  columns: { type: Array, required: true },
  /** Renders the leading checkbox column when true. */
  selectable: { type: Boolean, default: true },
  /** Renders a trailing actions column when true. */
  hasActions: { type: Boolean, default: false },
  /** Number of placeholder rows. */
  rows: { type: Number, default: 8 },
});

// Deterministic, natural-looking widths cycled by cell index so the bars do not
// line up in a rigid grid.
const WIDTHS = ['w-3/4', 'w-1/2', 'w-2/3', 'w-5/6', 'w-2/5', 'w-4/5'];

function barWidth(rowIndex, colIndex) {
  return WIDTHS[(rowIndex + colIndex * 2) % WIDTHS.length];
}
</script>

<template>
  <div class="overflow-x-auto">
    <table class="w-full border-collapse text-[13px]">
      <thead>
        <tr class="border-b border-slate-100 text-left text-slate-500">
          <th v-if="selectable" class="w-10 px-4 py-3">
            <div class="flexify-skeleton h-4 w-4"></div>
          </th>
          <th
            v-for="col in columns"
            :key="col.key"
            class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wide"
            :class="col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : ''"
          >{{ col.label }}</th>
          <th v-if="hasActions" class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row" class="border-b border-slate-50">
          <td v-if="selectable" class="px-4 py-3">
            <div class="flexify-skeleton h-4 w-4"></div>
          </td>
          <td
            v-for="(col, colIndex) in columns"
            :key="col.key"
            class="px-4 py-3"
          >
            <div
              class="flexify-skeleton h-3.5"
              :class="[barWidth(row, colIndex), col.align === 'right' ? 'ml-auto' : col.align === 'center' ? 'mx-auto' : '']"
            ></div>
          </td>
          <td v-if="hasActions" class="px-4 py-3 text-right">
            <div class="ml-auto flexify-skeleton h-6 w-16"></div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
