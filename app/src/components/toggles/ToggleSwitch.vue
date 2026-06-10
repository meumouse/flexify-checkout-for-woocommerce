<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Boolean], default: 'no' },
  trueValue: { type: [String, Boolean], default: 'yes' },
  falseValue: { type: [String, Boolean], default: 'no' },
  disabled: { type: Boolean, default: false },
  name: { type: String, default: '' },
  ariaLabel: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const isOn = computed(() => props.modelValue === props.trueValue);

function toggle() {
  if (props.disabled) {
    return;
  }

  emit('update:modelValue', isOn.value ? props.falseValue : props.trueValue);
}
</script>

<template>
  <button
    type="button"
    role="switch"
    :name="name"
    :aria-checked="isOn ? 'true' : 'false'"
    :aria-label="ariaLabel"
    :disabled="disabled"
    class="relative inline-flex h-7 w-[52px] shrink-0 items-center rounded-full border-0 p-0 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-200 focus:ring-offset-1"
    :class="[isOn ? 'bg-primary' : 'bg-gray-200', disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer']"
    @click="toggle"
  >
    <span
      class="inline-block h-[22px] w-[22px] transform rounded-full bg-white shadow-sm transition-transform duration-200"
      :class="isOn ? 'translate-x-[27px]' : 'translate-x-[3px]'"
    />
  </button>
</template>
