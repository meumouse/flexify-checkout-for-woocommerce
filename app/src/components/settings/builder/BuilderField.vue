<script setup>
/**
 * Inspector control row for the checkout builder.
 *
 * Renders a compact stacked layout — label (with optional monospace suffix) on
 * top, the control in the default slot below, and an optional hint underneath.
 * Keeps every inspector row visually consistent with the reference design.
 *
 * @since 6.0.0
 */
defineProps({
  label: { type: String, default: '' },
  suffix: { type: String, default: '' },
  hint: { type: String, default: '' },
});
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" class="flex items-center gap-2 text-[11.5px] font-medium text-slate-600">
      {{ label }}
      <span v-if="suffix" class="ml-auto font-mono text-[10.5px] text-slate-400">{{ suffix }}</span>
    </label>

    <slot />

    <span v-if="hint" class="text-[11px] leading-snug text-slate-400">{{ hint }}</span>
  </div>
</template>
