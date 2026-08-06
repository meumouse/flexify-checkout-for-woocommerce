<script setup>
/**
 * Shared admin page header.
 *
 * Mirrors the Joinotify design system header: a small uppercase brand eyebrow,
 * the brand mark next to the page title, an optional description line and a
 * bottom-aligned action area on the right.
 *
 * Slots:
 * - `icon`        replaces the default brand mark;
 * - `badge`       inline content rendered right after the title (Pro pill, counters);
 * - `description` rich description (falls back to the `description` prop);
 * - `actions`     right-aligned buttons.
 *
 * @since 6.0.0
 */
defineProps({
  eyebrow: { type: String, default: 'Flexify Checkout' },
  title: { type: String, required: true },
  description: { type: String, default: '' },
});
</script>

<template>
  <header class="flexify-page-header flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
      <p class="m-0 text-xs font-semibold uppercase tracking-[0.22em] text-shell-500">{{ eyebrow }}</p>

      <div class="mt-2 flex items-center gap-3">
        <slot name="icon">
          <svg class="h-9 w-9 shrink-0" viewBox="0 0 1080 1080" xmlns="http://www.w3.org/2000/svg"><g><path fill="#141D26" d="M513.96,116.38c-234.22,0-424.07,189.86-424.07,424.07c0,234.21,189.86,424.08,424.07,424.08 c234.21,0,424.07-189.86,424.07-424.08C938.03,306.25,748.17,116.38,513.96,116.38z M685.34,542.48 c-141.76,0.37-257.11,117.68-257.41,259.44h-88.21c0-191.79,153.83-347.41,345.62-347.41V542.48z M685.34,365.84 c-141.76,0.2-266.84,69.9-346.06,176.13V410.6c91.73-82.48,212.64-133.1,346.06-133.1V365.84z"/></g></svg>
        </slot>

        <h1 class="m-0 text-3xl font-semibold tracking-tight text-ink">{{ title }}</h1>

        <slot name="badge" />
      </div>

      <p
        v-if="$slots.description || description"
        class="m-0 mt-2 max-w-3xl text-sm leading-6 text-shell-500"
      >
        <slot name="description">{{ description }}</slot>
      </p>
    </div>

    <div v-if="$slots.actions" class="shrink-0">
      <slot name="actions" />
    </div>
  </header>
</template>
