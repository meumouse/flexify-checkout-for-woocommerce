<script setup>
import { ref, watch } from 'vue';
import { apiGet } from '../../services/api';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  type: { type: String, required: true },
  placeholder: { type: String, default: 'Comece a digitar para pesquisar...' },
});

const emit = defineEmits(['update:modelValue']);

const term = ref('');
const results = ref([]);
const searching = ref(false);

let debounceTimer = null;

watch(term, (value) => {
  clearTimeout(debounceTimer);

  if (!value || value.length < 2) {
    results.value = [];

    return;
  }

  debounceTimer = setTimeout(async () => {
    searching.value = true;

    try {
      const response = await apiGet(`admin/search?type=${encodeURIComponent(props.type)}&term=${encodeURIComponent(value)}`);

      results.value = Array.isArray(response?.items) ? response.items : [];
    } catch (error) {
      results.value = [];
    } finally {
      searching.value = false;
    }
  }, 350);
});

function isSelected(item) {
  return props.modelValue.some((selected) => Number(selected.id) === Number(item.id));
}

function toggleItem(item) {
  if (isSelected(item)) {
    emit('update:modelValue', props.modelValue.filter((selected) => Number(selected.id) !== Number(item.id)));
  } else {
    emit('update:modelValue', [...props.modelValue, item]);
  }
}

function removeItem(item) {
  emit('update:modelValue', props.modelValue.filter((selected) => Number(selected.id) !== Number(item.id)));
}
</script>

<template>
  <div class="flex flex-col gap-2">
    <div v-if="modelValue.length" class="flex flex-wrap gap-1.5">
      <span
        v-for="item in modelValue"
        :key="item.id"
        class="inline-flex items-center gap-1.5 rounded-full bg-primary-100 px-2.5 py-1 text-xs font-medium text-primary"
      >
        {{ item.label }}

        <button
          type="button"
          class="cursor-pointer border-0 bg-transparent p-0 leading-none text-primary hover:text-danger"
          aria-label="Remover"
          @click="removeItem(item)"
        >
          <BoxIcon name="x" class="h-3.5 w-3.5" />
        </button>
      </span>
    </div>

    <input
      v-model="term"
      type="text"
      :placeholder="placeholder"
      class="flexify-field-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary-100"
    />

    <div v-if="searching" class="text-xs text-muted">Pesquisando...</div>

    <ul
      v-else-if="results.length"
      class="m-0 flex max-h-44 list-none flex-col gap-0.5 overflow-y-auto rounded-lg border border-slate-200 bg-white p-1"
    >
      <li v-for="item in results" :key="item.id">
        <button
          type="button"
          class="flex w-full cursor-pointer items-center justify-between rounded-md border-0 bg-transparent px-2.5 py-1.5 text-left text-sm text-ink hover:bg-slate-100"
          @click="toggleItem(item)"
        >
          {{ item.label }}

          <BoxIcon v-if="isSelected(item)" name="check" class="h-4 w-4 text-success" />
        </button>
      </li>
    </ul>
  </div>
</template>
