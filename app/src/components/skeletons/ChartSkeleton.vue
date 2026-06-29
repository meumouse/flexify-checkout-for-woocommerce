<script setup>
/**
 * Loading placeholder for the analytics ApexCharts panels.
 *
 * Draws a chart-shaped skeleton — a y-axis tick column, a plotting area framed
 * by baseline axes filled with variable-height column bars, and an x-axis label
 * row — so the placeholder matches the rendered chart instead of a flat block.
 *
 * @since 6.0.0
 */
defineProps({
  /** Matches the `height` passed to <VueApexCharts>. */
  height: { type: Number, default: 320 },
});

// Deterministic bar heights (percent of the plot area) so the skeleton reads as
// a chart without animating layout between renders.
const BARS = [45, 70, 55, 85, 60, 75, 50, 90, 65, 80, 58, 72];
</script>

<template>
  <div class="w-full" :style="{ height: `${height}px` }">
    <div class="flex h-full gap-3">
      <!-- Y axis ticks -->
      <div class="flex flex-col justify-between py-1">
        <div v-for="tick in 5" :key="tick" class="flexify-skeleton h-2.5 w-9"></div>
      </div>

      <!-- Plot area -->
      <div class="flex flex-1 flex-col">
        <div class="flex flex-1 items-end gap-2 border-b border-l border-slate-100 px-3 pb-px">
          <div
            v-for="(bar, index) in BARS"
            :key="index"
            class="flexify-skeleton w-full"
            :style="{ height: `${bar}%` }"
          ></div>
        </div>

        <!-- X axis labels -->
        <div class="mt-2 flex justify-between pl-3">
          <div v-for="label in 6" :key="label" class="flexify-skeleton h-2.5 w-10"></div>
        </div>
      </div>
    </div>
  </div>
</template>
