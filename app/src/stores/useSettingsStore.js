import { defineStore } from 'pinia';
import { apiPost } from '../services/api';

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

    pushToast(type, message) {
      const id = `toast-${this.toasts.length}-${message.length}-${type}`;

      this.toasts.push({ id, type, message });

      setTimeout(() => {
        this.toasts = this.toasts.filter((toast) => toast.id !== id);
      }, 5000);
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
