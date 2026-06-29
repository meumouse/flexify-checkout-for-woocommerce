<script setup>
/**
 * Loading placeholder for the settings cards (recovery settings, webhooks).
 *
 * Mirrors the layout those panels render once loaded: an optional intro line,
 * the pill-style category nav and a stack of field groups (a section heading
 * over a two-column grid of label + input bars). Keeping the same shape avoids
 * a jump when the real form replaces the placeholder.
 *
 * @since 6.0.0
 */
defineProps({
  /** Number of nav pills to render. */
  pills: { type: Number, default: 3 },
  /** Number of field groups (heading + 2-column field grid). */
  groups: { type: Number, default: 3 },
  /** Renders a leading intro paragraph placeholder when true. */
  showIntro: { type: Boolean, default: false },
});
</script>

<template>
  <div>
    <!-- Intro paragraph -->
    <div v-if="showIntro" class="mb-4 space-y-2">
      <div class="flexify-skeleton h-3.5 w-full max-w-[640px]"></div>
      <div class="flexify-skeleton h-3.5 w-3/4 max-w-[520px]"></div>
    </div>

    <!-- Category / section nav -->
    <div class="mb-5 flex w-fit max-w-full flex-wrap gap-1 rounded-[8px] bg-[#e7edf5] p-1">
      <div v-for="pill in pills" :key="pill" class="flexify-skeleton h-8 w-28"></div>
    </div>

    <!-- Field groups -->
    <div
      v-for="group in groups"
      :key="group"
      class="border-b border-slate-100 py-4"
    >
      <div class="mb-4 flexify-skeleton h-4 w-48"></div>
      <div class="grid gap-4 md:grid-cols-2">
        <div v-for="field in 2" :key="field">
          <div class="mb-2 flexify-skeleton h-3 w-32"></div>
          <div class="flexify-skeleton h-9 w-full"></div>
        </div>
      </div>
    </div>
  </div>
</template>
