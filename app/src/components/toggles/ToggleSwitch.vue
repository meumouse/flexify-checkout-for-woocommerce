<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Boolean], default: 'no' },
  trueValue: { type: [String, Boolean], default: 'yes' },
  falseValue: { type: [String, Boolean], default: 'no' },
  size: { type: String, default: 'md' },
  disabled: { type: Boolean, default: false },
  name: { type: String, default: '' },
  ariaLabel: { type: String, default: '' },
  id: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'change']);

const inputId = computed(() => props.id || `flexify-toggle-${Math.random().toString(36).slice(2, 10)}`);
const checked = computed(() => props.modelValue === props.trueValue);

function handleChange(event) {
  const nextValue = event.target.checked ? props.trueValue : props.falseValue;

  emit('update:modelValue', nextValue);
  emit('change', nextValue);
}
</script>

<template>
  <label
    :for="inputId"
    class="relative inline-flex shrink-0 cursor-pointer items-center"
    :class="{ 'cursor-not-allowed opacity-60': disabled }"
  >
    <input
      :id="inputId"
      :name="name"
      :checked="checked"
      :aria-label="ariaLabel || name"
      :disabled="disabled"
      type="checkbox"
      class="peer sr-only"
      @change="handleChange"
    >

    <span
      aria-hidden="true"
      :class="[
        'inline-flex shrink-0 rounded-full border border-slate-200 bg-slate-300 transition-colors duration-200 ease-in-out',
        size === 'sm' ? 'h-[21px] w-[38px]' : 'h-[26px] w-[47px]',
        'peer-focus-visible:outline-none peer-focus-visible:ring-4 peer-focus-visible:ring-primary-100',
        'peer-checked:border-primary peer-checked:bg-primary',
      ]"
    />

    <span
      aria-hidden="true"
      :class="[
        'pointer-events-none absolute left-0.5 top-0.5 rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out',
        size === 'sm' ? 'h-[17px] w-[17px] peer-checked:translate-x-[17px]' : 'h-[22px] w-[22px] peer-checked:translate-x-[21px]',
      ]"
    />
  </label>
</template>
