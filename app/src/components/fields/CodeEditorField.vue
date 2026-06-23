<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { EditorView, basicSetup } from 'codemirror';
import { EditorState, Compartment } from '@codemirror/state';
import { css } from '@codemirror/lang-css';
import { javascript } from '@codemirror/lang-javascript';
import { oneDark } from '@codemirror/theme-one-dark';

const props = defineProps({
  modelValue: { type: String, default: '' },
  field: { type: Object, default: () => ({}) },
  name: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const host = ref(null);

let view = null;
const readOnly = new Compartment();

function languageExtension() {
  return props.field?.language === 'javascript' ? javascript() : css();
}

onMounted(() => {
  view = new EditorView({
    parent: host.value,
    state: EditorState.create({
      doc: props.modelValue || '',
      extensions: [
        basicSetup,
        languageExtension(),
        oneDark,
        readOnly.of(EditorState.readOnly.of(props.disabled)),
        EditorView.theme({
          '&': { fontSize: '12px', borderRadius: '8px' },
          '.cm-scroller': { fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace', minHeight: '180px', maxHeight: '360px' },
        }),
        EditorView.updateListener.of((update) => {
          if (update.docChanged) {
            emit('update:modelValue', update.state.doc.toString());
          }
        }),
      ],
    }),
  });
});

watch(
  () => props.modelValue,
  (value) => {
    if (view && value !== view.state.doc.toString()) {
      view.dispatch({
        changes: { from: 0, to: view.state.doc.length, insert: value || '' },
      });
    }
  }
);

watch(
  () => props.disabled,
  (value) => {
    if (view) {
      view.dispatch({
        effects: readOnly.reconfigure(EditorState.readOnly.of(value)),
      });
    }
  }
);

onBeforeUnmount(() => {
  if (view) {
    view.destroy();
    view = null;
  }
});
</script>

<template>
  <!--
    The template ref must not sit on the component root: when this component is
    loaded through defineAsyncComponent, Vue forwards the root vnode's ref and
    can call setRef with a null owner instance, throwing
    "Cannot read properties of null (reading 'refs')". Keeping `host` on an inner
    element resolves the ref within this component's own render context.
  -->
  <div class="w-full">
    <div
      ref="host"
      class="flexify-code-editor w-full overflow-hidden rounded-lg border border-slate-300"
      :class="disabled ? 'pointer-events-none opacity-60' : ''"
    />
  </div>
</template>
