<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Selecionar...' },
  disabled: { type: Boolean, default: false },
  size: { type: String, default: 'md' },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const root = ref(null);

const selectedLabel = computed(() => {
  const found = props.options.find((option) => String(option.value) === String(props.modelValue));

  return found ? found.label : '';
});

const sizeClass = computed(() => (props.size === 'sm' ? 'px-2.5 py-2 text-sm' : 'px-3 py-2 text-sm'));

function toggle() {
  if (!props.disabled) {
    open.value = !open.value;
  }
}

function close() {
  open.value = false;
}

function pick(option) {
  if (option.disabled) {
    return;
  }

  emit('update:modelValue', option.value);
  close();
}

function onDocumentClick(event) {
  if (root.value && !root.value.contains(event.target)) {
    close();
  }
}

function onKeydown(event) {
  if (event.key === 'Escape') {
    close();
  }
}

onMounted(() => document.addEventListener('click', onDocumentClick));
onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick));
</script>

<template>
  <div ref="root" class="relative">
    <button
      type="button"
      :disabled="disabled"
      class="flexify-field-input flex w-full items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white text-left text-ink transition focus:outline-none"
      :class="[
        sizeClass,
        disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
        open ? 'border-primary ring-2 ring-primary-100' : 'hover:border-slate-400',
      ]"
      @click="toggle"
      @keydown="onKeydown"
    >
      <span class="truncate" :class="selectedLabel ? 'text-ink' : 'text-slate-400'">
        {{ selectedLabel || placeholder }}
      </span>

      <BoxIcon
        name="chevron-down"
        class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
        :class="open ? 'rotate-180' : ''"
      />
    </button>

    <transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="-translate-y-1 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="-translate-y-1 opacity-0"
    >
      <ul
        v-if="open"
        class="absolute left-0 right-0 top-[calc(100%+4px)] z-30 m-0 max-h-56 list-none overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg"
      >
        <li v-if="!options.length" class="px-2.5 py-2 text-sm text-slate-400">Nenhuma opção</li>

        <li v-for="option in options" :key="option.value">
          <button
            type="button"
            :disabled="option.disabled"
            class="flex w-full cursor-pointer items-center justify-between gap-2 rounded-md border-0 bg-transparent px-2.5 py-1.5 text-left text-sm text-ink transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
            :class="String(option.value) === String(modelValue) ? 'bg-primary-50 font-medium text-primary' : ''"
            @click="pick(option)"
          >
            <span class="truncate">{{ option.label }}</span>

            <BoxIcon
              v-if="String(option.value) === String(modelValue)"
              name="check"
              class="h-4 w-4 shrink-0 text-primary"
            />
          </button>
        </li>
      </ul>
    </transition>
  </div>
</template>
