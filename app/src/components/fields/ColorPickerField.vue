<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  field: { type: Object, default: () => ({}) },
  name: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const model = computed({
  get: () => props.modelValue || '#000000',
  set: (value) => emit('update:modelValue', value),
});

const textModel = computed({
  get: () => props.modelValue ?? '',
  set: (value) => emit('update:modelValue', value),
});

const hasDefault = computed(() => Boolean(props.field?.default));

function resetToDefault() {
  if (!props.disabled && hasDefault.value) {
    emit('update:modelValue', props.field.default);
  }
}
</script>

<template>
  <div class="inline-flex items-stretch gap-2" :class="disabled ? 'opacity-50' : ''">
    <div class="inline-flex items-stretch overflow-hidden rounded-lg border border-slate-300 bg-white">
      <label class="flex cursor-pointer items-center border-r border-slate-200 px-1.5">
        <span class="block h-6 w-9 rounded" :style="{ backgroundColor: modelValue || '#000000' }" />
        <input v-model="model" type="color" :name="name" :disabled="disabled" class="sr-only" />
      </label>

      <input
        v-model="textModel"
        type="text"
        :disabled="disabled"
        placeholder="#000000"
        class="flexify-group-control w-28 px-3 py-2.5 text-sm lowercase text-ink focus:outline-none"
      />
    </div>

    <button
      v-if="hasDefault"
      type="button"
      :disabled="disabled"
      class="flex cursor-pointer items-center justify-center rounded-lg border border-slate-300 bg-white px-2.5 text-muted transition-colors hover:bg-slate-50 hover:text-ink"
      title="Redefinir para cor padrão"
      aria-label="Redefinir para cor padrão"
      @click="resetToDefault"
    >
      <BoxIcon name="reset" class="h-4 w-4" />
    </button>
  </div>
</template>
