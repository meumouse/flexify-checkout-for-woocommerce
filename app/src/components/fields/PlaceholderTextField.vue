<script setup>
/**
 * Textarea paired with an interactive list of checkout-field placeholders.
 *
 * Used by the "contact / delivery information summary" review texts: instead of
 * forcing the user to read a static legend and hand-type `{{ token }}`, each
 * available checkout field is shown as a clickable chip. Clicking inserts the
 * token at the caret position; the copy button puts it on the clipboard. The
 * field details (label/description) stay visible so the merchant knows exactly
 * what each placeholder resolves to.
 *
 * @since 6.0.0
 */
import { computed, nextTick, ref } from 'vue';
import BoxIcon from '../icons/BoxIcon.vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  field: { type: Object, default: () => ({}) },
  name: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const textareaRef = ref(null);

// Token that most recently flashed "copied", so the chip can show feedback.
const copiedToken = ref('');
let copyTimer = null;

const model = computed({
  get: () => props.modelValue ?? '',
  set: (value) => emit('update:modelValue', value),
});

const placeholders = computed(() => {
  const list = Array.isArray(props.field?.placeholders) ? props.field.placeholders : [];

  return list.filter((hint) => hint && hint.token);
});

/**
 * Insert a token at the current caret position, replacing any selection.
 * Falls back to appending when the textarea isn't focusable yet.
 */
function insertToken(token) {
  if (props.disabled || !token) {
    return;
  }

  const el = textareaRef.value;
  const current = model.value || '';

  if (!el || typeof el.selectionStart !== 'number') {
    model.value = current ? `${current} ${token}` : token;
    return;
  }

  const start = el.selectionStart;
  const end = el.selectionEnd;
  const needsSpaceBefore = start > 0 && !/\s$/.test(current.slice(0, start));
  const snippet = needsSpaceBefore ? ` ${token}` : token;

  model.value = current.slice(0, start) + snippet + current.slice(end);

  // Restore focus and drop the caret right after the inserted token.
  const caret = start + snippet.length;

  nextTick(() => {
    el.focus();
    el.setSelectionRange(caret, caret);
  });
}

async function copyToken(token) {
  if (!token) {
    return;
  }

  try {
    await navigator.clipboard.writeText(token);
  } catch (error) {
    // Clipboard API unavailable (e.g. non-secure context): fall back to insert
    // so the action is never a dead end.
    insertToken(token);
    return;
  }

  copiedToken.value = token;

  if (copyTimer) {
    clearTimeout(copyTimer);
  }

  copyTimer = setTimeout(() => {
    if (copiedToken.value === token) {
      copiedToken.value = '';
    }
  }, 1500);
}
</script>

<template>
  <div class="flex w-full max-w-xl flex-col gap-3">
    <textarea
      ref="textareaRef"
      v-model="model"
      :name="name"
      :rows="field?.rows || 4"
      :placeholder="field?.placeholder || ''"
      :disabled="disabled"
      class="flexify-field-input w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm leading-relaxed text-ink focus:border-primary focus:ring-2 focus:ring-primary-100"
      :class="disabled ? 'cursor-not-allowed opacity-50' : ''"
    />

    <div v-if="placeholders.length" class="rounded-lg border border-slate-200 bg-slate-50/60">
      <div class="flex items-center gap-1.5 border-b border-slate-200 px-3 py-2">
        <BoxIcon name="list-plus" class="h-3.5 w-3.5 text-slate-400" />
        <span class="text-[12px] font-semibold uppercase tracking-wide text-slate-500">
          Campos disponíveis
        </span>
        <span class="ml-auto text-[11px] text-slate-400">Clique para inserir</span>
      </div>

      <ul class="max-h-56 divide-y divide-slate-100 overflow-y-auto">
        <li v-for="hint in placeholders" :key="hint.token">
          <div
            class="group flex items-center gap-3 px-3 py-2 transition"
            :class="disabled ? 'opacity-50' : 'hover:bg-white'"
          >
            <button
              type="button"
              :disabled="disabled"
              class="flex min-w-0 flex-1 items-center gap-3 border-0 bg-transparent p-0 text-left"
              :class="disabled ? 'cursor-not-allowed' : 'cursor-pointer'"
              :title="`Inserir ${hint.token}`"
              @click="insertToken(hint.token)"
            >
              <code
                class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[12px] text-slate-700 transition group-hover:bg-primary-100 group-hover:text-primary"
              >{{ hint.token }}</code>
              <span v-if="hint.description" :title="hint.description" class="min-w-0 truncate text-[12px] text-slate-500">
                {{ hint.description }}
              </span>
            </button>

            <button
              type="button"
              :disabled="disabled"
              class="inline-flex shrink-0 items-center gap-1 rounded-md border-0 bg-transparent px-1.5 py-1 text-[11px] font-medium transition"
              :class="[
                disabled ? 'cursor-not-allowed text-slate-300' : 'cursor-pointer text-slate-400 hover:text-primary',
                copiedToken === hint.token ? 'text-emerald-600' : '',
              ]"
              :title="`Copiar ${hint.token}`"
              @click="copyToken(hint.token)"
            >
              <BoxIcon :name="copiedToken === hint.token ? 'check' : 'copy'" class="h-3.5 w-3.5" />
              <span>{{ copiedToken === hint.token ? 'Copiado' : 'Copiar' }}</span>
            </button>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PlaceholderTextField',
};
</script>
