<script setup>
import { computed, ref } from 'vue';
import { Search, Check } from '@boxicons/vue';

/**
 * Searchable multi-select country list for the wizard.
 *
 * The parent owns the selected codes array (v-model). Options come from the
 * wizard context payload ({ value, label }).
 *
 * @since 6.0.0
 */
const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  options: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const search = ref('');

const filtered = computed(() => {
  const term = search.value.trim().toLowerCase();

  if (!term) {
    return props.options;
  }

  return props.options.filter(
    (option) => option.label.toLowerCase().includes(term) || option.value.toLowerCase().includes(term),
  );
});

const selectedSet = computed(() => new Set(props.modelValue));

function toggle(code) {
  const next = new Set(props.modelValue);

  if (next.has(code)) {
    next.delete(code);
  } else {
    next.add(code);
  }

  emit('update:modelValue', Array.from(next));
}

function clearAll() {
  emit('update:modelValue', []);
}
</script>

<template>
  <div class="rounded-xl border border-slate-200 bg-white">
    <div class="flex items-center gap-2 border-b border-slate-100 px-3 py-2">
      <Search class="h-4 w-4 text-slate-400" />

      <input
        v-model="search"
        type="text"
        placeholder="Buscar país…"
        class="w-full border-0 bg-transparent text-[14px] text-ink outline-none placeholder:text-slate-400 focus:ring-0"
      >

      <button
        v-if="modelValue.length"
        type="button"
        class="shrink-0 cursor-pointer rounded-md border-0 bg-transparent px-2 py-1 text-[12px] font-semibold text-primary hover:underline"
        @click="clearAll"
      >
        Limpar ({{ modelValue.length }})
      </button>
    </div>

    <div class="max-h-[280px] overflow-y-auto p-1">
      <button
        v-for="option in filtered"
        :key="option.value"
        type="button"
        class="flex w-full cursor-pointer items-center gap-3 rounded-lg border-0 bg-transparent px-3 py-2 text-left transition hover:bg-slate-50"
        @click="toggle(option.value)"
      >
        <span
          class="flex h-5 w-5 shrink-0 items-center justify-center rounded-[6px] border-2 transition"
          :class="selectedSet.has(option.value) ? 'border-primary bg-primary text-white' : 'border-slate-300 bg-white'"
        >
          <Check v-if="selectedSet.has(option.value)" class="h-3.5 w-3.5" />
        </span>

        <span class="text-[14px] text-ink">{{ option.label }}</span>

        <span class="ml-auto text-[12px] font-medium text-slate-400">{{ option.value }}</span>
      </button>

      <p v-if="!filtered.length" class="px-3 py-6 text-center text-[13px] text-slate-400">
        Nenhum país encontrado.
      </p>
    </div>
  </div>
</template>
