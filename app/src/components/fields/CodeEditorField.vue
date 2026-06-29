<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  field: { type: Object, default: () => ({}) },
  name: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const host = ref(null);

// CodeMirror is heavy, so it is imported on demand here instead of wrapping the
// whole component in defineAsyncComponent. The async component wrapper resolves
// on a microtask and, combined with a template ref, makes Vue 3.5 call setRef
// with a null owner instance ("Cannot read properties of null (reading 'refs')")
// — crashing the settings app whenever a code editor is mounted (or resolves
// after navigating away). Keeping the component synchronous avoids that.
let view = null;
let readOnly = null;
let editorState = null;
let destroyed = false;

onMounted(async () => {
  const [{ EditorView, basicSetup }, { EditorState, Compartment }, { css }, { javascript }, { oneDark }] = await Promise.all([
    import('codemirror'),
    import('@codemirror/state'),
    import('@codemirror/lang-css'),
    import('@codemirror/lang-javascript'),
    import('@codemirror/theme-one-dark'),
  ]);

  // The component may have been unmounted while CodeMirror was loading.
  if (destroyed || !host.value) {
    return;
  }

  readOnly = new Compartment();
  editorState = EditorState;

  const language = props.field?.language === 'javascript' ? javascript() : css();

  view = new EditorView({
    parent: host.value,
    state: EditorState.create({
      doc: props.modelValue || '',
      extensions: [
        basicSetup,
        language,
        oneDark,
        readOnly.of(EditorState.readOnly.of(props.disabled)),
        EditorView.theme({
          '&': { fontSize: '12px', borderRadius: '8px', height: '100%' },
          '.cm-scroller': { fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace', overflow: 'auto' },
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
    if (view && readOnly && editorState) {
      view.dispatch({
        effects: readOnly.reconfigure(editorState.readOnly.of(value)),
      });
    }
  }
);

onBeforeUnmount(() => {
  destroyed = true;

  if (view) {
    view.destroy();
    view = null;
  }
});
</script>

<template>
  <div
    ref="host"
    class="flexify-code-editor w-full resize overflow-hidden rounded-lg border border-slate-300"
    :class="disabled ? 'pointer-events-none opacity-60' : ''"
    style="height: 240px; min-height: 120px; min-width: 240px;"
  />
</template>
