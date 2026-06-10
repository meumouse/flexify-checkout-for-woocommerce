import { defineStore } from 'pinia';
import { apiPost, apiPostForm } from '../services/api';

/**
 * Pinia store for the settings app.
 *
 * Holds the settings values, the schema tree and runtime context fetched
 * from the bootstrap endpoint, plus save/reset actions.
 *
 * @since 6.0.0
 */
export const useSettingsStore = defineStore('flexify-checkout-settings', {
  state: () => ({
    settings: {},
    schema: [],
    runtime: {},
    fields: {},
    conditions: [],
    integrations: [],
    dirty: false,
    saving: false,
    resetting: false,
    toasts: [],
  }),

  getters: {
    isPro(state) {
      return Boolean(state.runtime?.is_pro);
    },

    license(state) {
      return state.runtime?.license || {};
    },
  },

  actions: {
    hydrate(bootstrap) {
      this.settings = bootstrap?.settings && typeof bootstrap.settings === 'object' ? bootstrap.settings : {};
      this.schema = Array.isArray(bootstrap?.schema) ? bootstrap.schema : [];
      this.runtime = bootstrap?.runtime && typeof bootstrap.runtime === 'object' ? bootstrap.runtime : {};
      this.fields = this.runtime?.fields && typeof this.runtime.fields === 'object' ? this.runtime.fields : {};
      this.conditions = Array.isArray(this.runtime?.conditions) ? this.runtime.conditions : [];
      this.integrations = Array.isArray(this.runtime?.integrations) ? this.runtime.integrations : [];
      this.dirty = false;
    },

    setSetting(key, value) {
      this.settings = { ...this.settings, [key]: value };
      this.dirty = true;
    },

    /**
     * Evaluate a field's visible_when conditions against current settings.
     *
     * @param {Object} field - Field definition from the schema.
     * @return {boolean} Whether the field should be displayed.
     */
    isFieldVisible(field) {
      const conditions = Array.isArray(field?.visible_when) ? field.visible_when : [];

      return conditions.every((condition) => {
        const value = this.settings?.[condition.field];

        if ('equals' in condition) {
          return String(value) === String(condition.equals);
        }

        return true;
      });
    },

    pushToast(type, message, title = '') {
      const id = `toast-${this.toasts.length}-${message.length}-${type}`;
      const defaultTitles = {
        success: 'Salvo com sucesso',
        error: 'Ops! Ocorreu um erro.',
        info: 'Flexify Checkout',
      };

      this.toasts.push({
        id,
        type,
        title: title || defaultTitles[type] || defaultTitles.info,
        message,
        closing: false,
      });

      setTimeout(() => {
        this.toasts = this.toasts.map((toast) => (toast.id === id ? { ...toast, closing: true } : toast));
      }, 3000);

      setTimeout(() => {
        this.toasts = this.toasts.filter((toast) => toast.id !== id);
      }, 3500);
    },

    dismissToast(id) {
      this.toasts = this.toasts.map((toast) => (toast.id === id ? { ...toast, closing: true } : toast));

      setTimeout(() => {
        this.toasts = this.toasts.filter((toast) => toast.id !== id);
      }, 180);
    },

    async save() {
      if (this.saving) {
        return;
      }

      this.saving = true;

      try {
        const response = await apiPost('admin/settings', { settings: this.settings });

        if (response?.status === 'success') {
          this.settings = response.settings || this.settings;
          this.dirty = false;
          this.pushToast('success', response.message || 'As configurações foram salvas.');
        } else {
          this.pushToast('error', response?.message || 'Ocorreu um erro ao salvar as configurações.');
        }
      } catch (error) {
        this.pushToast('error', 'Ocorreu um erro ao salvar as configurações.');
      } finally {
        this.saving = false;
      }
    },

    async reset() {
      if (this.resetting) {
        return;
      }

      this.resetting = true;

      try {
        const response = await apiPost('admin/settings/reset', {});

        if (response?.status === 'success') {
          this.settings = response.settings || this.settings;
          this.runtime = response.runtime || this.runtime;
          this.dirty = false;
          this.pushToast('success', response.message || 'As opções foram redefinidas com sucesso!');
        } else {
          this.pushToast('error', response?.message || 'Ocorreu um erro ao redefinir as configurações.');
        }
      } catch (error) {
        this.pushToast('error', 'Ocorreu um erro ao redefinir as configurações.');
      } finally {
        this.resetting = false;
      }
    },

    /**
     * Persist a map of checkout field updates (field id => properties).
     */
    async saveFields(updates) {
      try {
        const response = await apiPost('admin/fields', { fields: updates });

        if (response?.status === 'success') {
          this.fields = response.fields || this.fields;
          this.pushToast('success', response.message || 'Os campos foram atualizados!');
        } else {
          this.pushToast('error', response?.message || 'Ocorreu um erro ao atualizar os campos.');
        }

        return response;
      } catch (error) {
        this.pushToast('error', 'Ocorreu um erro ao atualizar os campos.');

        return null;
      }
    },

    async addField(field) {
      try {
        const response = await apiPost('admin/fields/add', { field });

        if (response?.status === 'success') {
          this.fields = response.fields || this.fields;
        }

        this.pushToast(response?.status === 'success' ? 'success' : 'error', response?.message || '');

        return response;
      } catch (error) {
        this.pushToast('error', 'Ocorreu um erro ao adicionar o novo campo.');

        return null;
      }
    },

    async removeField(fieldId) {
      try {
        const response = await apiPost('admin/fields/remove', { field_id: fieldId });

        if (response?.status === 'success') {
          this.fields = response.fields || this.fields;
        }

        this.pushToast(response?.status === 'success' ? 'success' : 'error', response?.message || '');

        return response;
      } catch (error) {
        this.pushToast('error', 'Ocorreu um erro ao remover o campo.');

        return null;
      }
    },

    async conditionAction(endpoint, body) {
      try {
        const response = await apiPost(endpoint, body);

        if (response?.status === 'success' && Array.isArray(response.conditions)) {
          this.conditions = response.conditions;
        }

        this.pushToast(response?.status === 'success' ? 'success' : 'error', response?.message || '');

        return response;
      } catch (error) {
        this.pushToast('error', 'Ocorreu um erro ao processar a condição.');

        return null;
      }
    },

    addCondition(condition) {
      return this.conditionAction('admin/conditions', { condition });
    },

    updateCondition(index, condition) {
      return this.conditionAction('admin/conditions/update', { index, condition });
    },

    removeCondition(index) {
      return this.conditionAction('admin/conditions/remove', { index });
    },

    async moduleAction(endpoint, body) {
      try {
        const response = await apiPost(endpoint, body);

        if (Array.isArray(response?.integrations)) {
          this.integrations = response.integrations;
        }

        this.pushToast(response?.status === 'success' ? 'success' : 'error', response?.message || '');

        return response;
      } catch (error) {
        this.pushToast('error', 'Ocorreu um erro ao processar o módulo.');

        return null;
      }
    },

    installModule(slug, downloadUrl) {
      return this.moduleAction('admin/modules/install', { slug, download_url: downloadUrl });
    },

    activateModule(slug) {
      return this.moduleAction('admin/modules/activate', { slug });
    },

    /**
     * Refresh the fonts library and the set_font_family select options.
     */
    syncFonts(fonts) {
      if (!fonts || typeof fonts !== 'object') {
        return;
      }

      this.runtime = { ...this.runtime, fonts };

      const options = Object.entries(fonts).map(([id, font]) => ({
        value: id,
        label: font?.font_name || id,
      }));

      for (const tab of this.schema) {
        for (const card of tab.cards || []) {
          for (const field of card.fields || []) {
            if (field.key === 'set_font_family') {
              field.options = options;
            }
          }
        }
      }
    },

    async saveFont(font) {
      try {
        const formData = new FormData();

        formData.append('font_id', font.font_id);
        formData.append('font_name', font.font_name);
        formData.append('font_type', font.font_type);
        formData.append('font_url', font.font_url || '');
        formData.append('font_weight', font.font_weight || '400');
        formData.append('font_style', font.font_style || 'normal');
        formData.append('is_new', font.is_new);

        if (font.file) {
          formData.append('font_file', font.file);
        }

        const response = await apiPostForm('admin/fonts', formData);

        if (response?.status === 'success') {
          this.syncFonts(response.fonts);
        }

        this.pushToast(response?.status === 'success' ? 'success' : 'error', response?.message || '');

        return response;
      } catch (error) {
        this.pushToast('error', 'Ocorreu um erro ao salvar a fonte.');

        return null;
      }
    },

    async deleteFont(fontId) {
      try {
        const response = await apiPost('admin/fonts/delete', { font_id: fontId });

        if (response?.status === 'success') {
          this.syncFonts(response.fonts);

          if (response.current_font) {
            this.settings = { ...this.settings, set_font_family: response.current_font };
          }
        }

        this.pushToast(response?.status === 'success' ? 'success' : 'error', response?.message || '');

        return response;
      } catch (error) {
        this.pushToast('error', 'Ocorreu um erro ao remover a fonte.');

        return null;
      }
    },

    async licenseAction(endpoint, body = {}) {
      const response = await apiPost(endpoint, body);

      if (response?.runtime) {
        this.runtime = response.runtime;
      }

      this.pushToast(response?.status === 'success' ? 'success' : 'error', response?.message || '');

      return response;
    },
  },
});
