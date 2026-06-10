<script setup>
defineProps({
  title: { type: String, default: '' },
  description: { type: String, default: '' },
});
</script>

<template>
  <section class="rounded-2xl border border-gray-200 bg-panel p-6 shadow-sm">
    <header v-if="title" class="mb-2">
      <h3 class="m-0 text-base font-semibold text-ink">{{ title }}</h3>

      <p v-if="description" class="mb-0 mt-1 text-xs leading-relaxed text-muted">
        {{ description }}
      </p>
    </header>

    <slot />
  </section>
</template>
