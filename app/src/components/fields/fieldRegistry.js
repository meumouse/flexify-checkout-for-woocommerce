import { defineAsyncComponent } from 'vue';
import ToggleSwitch from '../toggles/ToggleSwitch.vue';
import TextField from './TextField.vue';
import NumberField from './NumberField.vue';
import TextAreaField from './TextAreaField.vue';
import SelectField from './SelectField.vue';
import ColorPickerField from './ColorPickerField.vue';
import DimensionField from './DimensionField.vue';
import MediaPickerField from './MediaPickerField.vue';

// CodeMirror is heavy, so the code editor is lazy-loaded on first render.
const CodeEditorField = defineAsyncComponent(() => import('./CodeEditorField.vue'));

const registry = new Map();

function normalizeName(name) {
  return String(name || '')
    .trim()
    .toLowerCase()
    .replace(/[\s_-]+/g, '');
}

function registerAlias(name, component) {
  const key = normalizeName(name);

  if (!key || !component) {
    return;
  }

  registry.set(key, component);
}

registerAlias('toggle', ToggleSwitch);
registerAlias('text', TextField);
registerAlias('url', TextField);
registerAlias('number', NumberField);
registerAlias('textarea', TextAreaField);
registerAlias('select', SelectField);
registerAlias('color', ColorPickerField);
registerAlias('color-picker', ColorPickerField);
registerAlias('dimension', DimensionField);
registerAlias('media', MediaPickerField);
registerAlias('code-editor', CodeEditorField);

/**
 * Register a custom field component, also exposed globally so extensions
 * can plug their own field types into the settings app.
 *
 * @since 6.0.0
 * @param {string} name - Field type name.
 * @param {Object} component - Vue component.
 * @return {void}
 */
export function registerFieldComponent(name, component) {
  registerAlias(name, component);
  syncWindowRegistry();
}

/**
 * Resolve the Vue component for a schema field definition.
 *
 * @since 6.0.0
 * @param {Object} field - Field definition.
 * @return {Object|null} Vue component or null when unknown.
 */
export function resolveFieldComponent(field) {
  if (!field || typeof field !== 'object') {
    return null;
  }

  const names = [field.component, field.type].filter(Boolean);

  for (const name of names) {
    const component = registry.get(normalizeName(name));

    if (component) {
      return component;
    }
  }

  return null;
}

function syncWindowRegistry() {
  if (typeof window === 'undefined') {
    return;
  }

  window.FlexifyCheckoutFieldComponents = window.FlexifyCheckoutFieldComponents || {};
  window.FlexifyCheckoutFieldComponents.register = registerFieldComponent;
  window.FlexifyCheckoutFieldComponents.resolve = resolveFieldComponent;
}

syncWindowRegistry();
