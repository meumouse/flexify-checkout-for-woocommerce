<script setup>
/**
 * Modern admin date picker.
 *
 * The Vue counterpart of the checkout's React DatePicker: a read-only trigger
 * that opens a calendar popover with a month + year dropdown header (reusing
 * <BaseSelect>) and prev/next arrows, replacing the native `<input type="date">`.
 *
 * Stores/emits the value as ISO `YYYY-MM-DD` (same shape the native input
 * produced, so it drops into existing handlers) and displays it as `dd/mm/aaaa`.
 * The grid is keyboard navigable (arrows, Home/End, PageUp/Down, Enter, Escape)
 * with a roving tabindex, and focus returns to the trigger on close.
 *
 * @since 6.0.0
 */
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import BaseSelect from './BaseSelect.vue';
import BoxIcon from '../icons/BoxIcon.vue';

const props = defineProps({
  /** ISO `YYYY-MM-DD` (or ''). */
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'dd/mm/aaaa' },
  disabled: { type: Boolean, default: false },
  size: { type: String, default: 'md' },
  /** Extra classes appended to the trigger button (e.g. a fixed width). */
  triggerClass: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const MONTHS = [
  'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
  'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
];
const WEEKDAYS = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
const WEEKDAY_NAMES = [
  'domingo', 'segunda-feira', 'terça-feira', 'quarta-feira',
  'quinta-feira', 'sexta-feira', 'sábado',
];

const pad = (n) => String(n).padStart(2, '0');
const toIso = (year, month, day) => `${year}-${pad(month + 1)}-${pad(day)}`;
const daysInMonth = (year, month) => new Date(year, month + 1, 0).getDate();
const clampDay = (year, month, day) => Math.min(day, daysInMonth(year, month));

function parseIso(iso) {
  if (!iso || typeof iso !== 'string') {
    return null;
  }

  const match = iso.match(/^(\d{4})-(\d{2})-(\d{2})$/);

  if (!match) {
    return null;
  }

  return { year: Number(match[1]), month: Number(match[2]) - 1, day: Number(match[3]) };
}

/** Shift a `{year,month,day}` focus by a number of days, rolling across months. */
function addDays(focus, delta) {
  const d = new Date(focus.year, focus.month, focus.day + delta);

  return { year: d.getFullYear(), month: d.getMonth(), day: d.getDate() };
}

/** Shift focus by whole months, clamping the day into the target month length. */
function addMonths(focus, delta) {
  const total = focus.month + delta;
  const year = focus.year + Math.floor(total / 12);
  const month = ((total % 12) + 12) % 12;

  return { year, month, day: clampDay(year, month, focus.day) };
}

const open = ref(false);
const root = ref(null);
const grid = ref(null);
const trigger = ref(null);

const todayDate = new Date();
const today = {
  year: todayDate.getFullYear(),
  month: todayDate.getMonth(),
  day: todayDate.getDate(),
};

const selected = computed(() => parseIso(props.modelValue));
const display = computed(() => {
  const s = selected.value;

  return s ? `${pad(s.day)}/${pad(s.month + 1)}/${s.year}` : '';
});

// The date the keyboard cursor sits on; also drives which month is visible.
const focus = ref(selected.value || today);
const view = computed(() => ({ year: focus.value.year, month: focus.value.month }));

const yearOptions = computed(() => {
  const out = [];

  for (let y = today.year + 10; y >= today.year - 100; y -= 1) {
    out.push({ value: String(y), label: String(y) });
  }

  return out;
});
const monthOptions = MONTHS.map((label, index) => ({ value: String(index), label }));

const cells = computed(() => {
  const firstWeekday = new Date(view.value.year, view.value.month, 1).getDay();
  const length = daysInMonth(view.value.year, view.value.month);

  return [...Array(firstWeekday).fill(null), ...Array.from({ length }, (_, i) => i + 1)];
});

const triggerSizeClass = computed(() => (props.size === 'sm' ? 'px-2.5 py-2 text-sm' : 'px-3 py-2 text-sm'));

function isSelected(day) {
  const s = selected.value;

  return s && s.year === view.value.year && s.month === view.value.month && s.day === day;
}

function isToday(day) {
  return today.year === view.value.year && today.month === view.value.month && today.day === day;
}

function dayLabel(day) {
  const weekday = WEEKDAY_NAMES[new Date(view.value.year, view.value.month, day).getDay()];

  return `${day} de ${MONTHS[view.value.month]} de ${view.value.year}, ${weekday}`;
}

function toggle() {
  if (props.disabled) {
    return;
  }

  open.value = !open.value;
}

function close(returnFocus = true) {
  open.value = false;

  if (returnFocus && trigger.value) {
    trigger.value.focus();
  }
}

function choose(day) {
  emit('update:modelValue', toIso(view.value.year, view.value.month, day));
  close();
}

function step(delta) {
  focus.value = addMonths(focus.value, delta);
}

function onMonth(value) {
  const month = Number(value);

  focus.value = { ...focus.value, month, day: clampDay(focus.value.year, month, focus.value.day) };
}

function onYear(value) {
  const year = Number(value);

  focus.value = { ...focus.value, year, day: clampDay(year, focus.value.month, focus.value.day) };
}

function onGridKeydown(event) {
  const weekday = new Date(focus.value.year, focus.value.month, focus.value.day).getDay();
  let next = null;

  switch (event.key) {
    case 'ArrowLeft':
      next = addDays(focus.value, -1);
      break;
    case 'ArrowRight':
      next = addDays(focus.value, 1);
      break;
    case 'ArrowUp':
      next = addDays(focus.value, -7);
      break;
    case 'ArrowDown':
      next = addDays(focus.value, 7);
      break;
    case 'Home':
      next = addDays(focus.value, -weekday);
      break;
    case 'End':
      next = addDays(focus.value, 6 - weekday);
      break;
    case 'PageUp':
      next = addMonths(focus.value, event.shiftKey ? -12 : -1);
      break;
    case 'PageDown':
      next = addMonths(focus.value, event.shiftKey ? 12 : 1);
      break;
    case 'Enter':
    case ' ':
      event.preventDefault();
      choose(focus.value.day);
      return;
    case 'Escape':
      event.preventDefault();
      close();
      return;
    default:
      return;
  }

  event.preventDefault();
  focus.value = next;
}

function onTriggerKeydown(event) {
  if (props.disabled) {
    return;
  }

  if (!open.value && event.key === 'ArrowDown') {
    event.preventDefault();
    open.value = true;
  } else if (open.value && event.key === 'Escape') {
    event.preventDefault();
    close();
  }
}

function onDocumentClick(event) {
  if (root.value && !root.value.contains(event.target)) {
    open.value = false;
  }
}

// Each time the popover opens, start the cursor on the selected date (or today).
watch(open, (isOpen) => {
  if (isOpen) {
    focus.value = selected.value || today;
  }
});

// Move DOM focus onto the focused day so keyboard navigation visibly tracks it.
watch(
  [open, focus],
  async ([isOpen]) => {
    if (!isOpen) {
      return;
    }

    await nextTick();

    const cell = grid.value && grid.value.querySelector(`[data-day="${focus.value.day}"]`);

    if (cell) {
      cell.focus();
    }
  },
  { deep: true },
);

onMounted(() => document.addEventListener('mousedown', onDocumentClick));
onBeforeUnmount(() => document.removeEventListener('mousedown', onDocumentClick));
</script>

<template>
  <div ref="root" class="relative">
    <button
      ref="trigger"
      type="button"
      :disabled="disabled"
      aria-haspopup="dialog"
      :aria-expanded="open"
      class="flexify-field-input flex items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white text-left text-ink transition focus:outline-none"
      :class="[
        triggerSizeClass,
        triggerClass || 'w-full',
        disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
        open ? 'border-primary ring-2 ring-primary-100' : 'hover:border-slate-400',
      ]"
      @click="toggle"
      @keydown="onTriggerKeydown"
    >
      <span :class="display ? 'text-ink' : 'text-slate-400'">{{ display || placeholder }}</span>
      <BoxIcon name="calendar" class="h-4 w-4 shrink-0 text-slate-400" />
    </button>

    <transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="-translate-y-1 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="-translate-y-1 opacity-0"
    >
      <div
        v-if="open"
        role="dialog"
        aria-label="Selecionar data"
        class="absolute left-0 top-[calc(100%+4px)] z-30 w-72 rounded-xl border border-slate-200 bg-white p-3 shadow-lg"
      >
        <div class="mb-3 flex items-center gap-2">
          <button
            type="button"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100"
            aria-label="Mês anterior"
            @click="step(-1)"
          >
            <BoxIcon name="chevron-left" class="h-4 w-4" />
          </button>

          <div class="flex min-w-0 flex-1 gap-2">
            <BaseSelect
              size="sm"
              class="min-w-0 flex-1"
              :model-value="String(view.month)"
              :options="monthOptions"
              @update:model-value="onMonth"
            />
            <BaseSelect
              size="sm"
              class="w-24 shrink-0"
              :model-value="String(view.year)"
              :options="yearOptions"
              @update:model-value="onYear"
            />
          </div>

          <button
            type="button"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100"
            aria-label="Próximo mês"
            @click="step(1)"
          >
            <BoxIcon name="chevron-right" class="h-4 w-4" />
          </button>
        </div>

        <div class="mb-1 grid grid-cols-7 gap-1">
          <div
            v-for="(wd, index) in WEEKDAYS"
            :key="index"
            class="flex h-8 items-center justify-center text-xs font-medium text-slate-400"
          >
            {{ wd }}
          </div>
        </div>

        <div
          ref="grid"
          role="grid"
          :aria-label="`${MONTHS[view.month]} de ${view.year}`"
          class="grid grid-cols-7 gap-1"
          @keydown="onGridKeydown"
        >
          <template v-for="(day, index) in cells">
            <div v-if="day === null" :key="`e-${index}`" role="presentation" />
            <button
              v-else
              :key="day"
              type="button"
              :data-day="day"
              role="gridcell"
              :aria-label="dayLabel(day)"
              :aria-selected="isSelected(day)"
              :aria-current="isToday(day) ? 'date' : undefined"
              :tabindex="day === focus.day ? 0 : -1"
              class="flex h-9 items-center justify-center rounded-lg text-sm outline-none transition focus-visible:ring-2 focus-visible:ring-primary-100"
              :class="isSelected(day)
                ? 'bg-primary font-semibold text-white'
                : ['text-slate-700 hover:bg-primary-50', isToday(day) ? 'font-semibold text-primary' : '']"
              @click="choose(day)"
            >
              {{ day }}
            </button>
          </template>
        </div>
      </div>
    </transition>
  </div>
</template>
