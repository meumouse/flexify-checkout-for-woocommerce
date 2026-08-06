<script setup>
import { Check } from '@boxicons/vue';

/**
 * Selectable card used across the setup wizard steps.
 *
 * Mirrors the reference design: icon, title, description and a checkmark badge
 * on the selected card. Works for single or multi select (parent owns state).
 * The `icon` prop takes a Boxicons Vue component (from '@boxicons/vue').
 *
 * @since 6.0.0
 */
defineProps({
  icon: { type: [Object, Function], default: null },
  iconImg: { type: String, default: '' },
  title: { type: String, default: '' },
  description: { type: String, default: '' },
  selected: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['select']);
</script>

<template>
  <button
    type="button"
    :disabled="disabled"
    class="group relative flex w-full flex-col items-center gap-3 rounded-2xl border-2 bg-white px-5 py-6 text-center transition"
    :class="[
      selected ? 'border-primary bg-primary-50/40' : 'border-slate-200 hover:border-primary-300',
      disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
    ]"
    @click="!disabled && emit('select')"
  >
    <span
      v-if="selected"
      class="absolute right-3 top-3 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-white"
    >
      <Check class="h-4 w-4" />
    </span>

    <span
      v-if="icon || iconImg"
      class="flex h-16 w-16 items-center justify-center rounded-2xl transition"
      :class="selected ? 'bg-primary-100 text-primary' : 'bg-slate-100 text-slate-500 group-hover:bg-primary-50'"
    >
      <img v-if="iconImg" :src="iconImg" alt="" class="h-9 w-9 object-contain">
      <component :is="icon" v-else class="h-8 w-8" />
    </span>

    <span class="text-[15px] font-semibold text-ink">{{ title }}</span>

    <span v-if="description" class="text-[13px] leading-5 text-slate-500">{{ description }}</span>

    <slot />
  </button>
</template>
