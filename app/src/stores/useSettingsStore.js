import { defineStore } from 'pinia';
import { toast } from 'vue-sonner';
import { apiGet, apiPost, apiPostForm, apiDelete } from '../services/api';

/**
 * Deep clone a plain settings object so the saved baseline can't be mutated
 * by later edits to the live `settings` reference.
 *
 * @param {Object} value - Plain serializable settings object.
 * @return {Object} An independent copy.
 */
function cloneSettings(value) {
  return value && typeof value === 'object' ? JSON.parse(JSON.stringify(value)) : {};
}

/**
 * Stable JSON string with object keys sorted recursively, so two settings
 * snapshots compare equal regardless of key insertion order.
 *
 * @param {*} value - Any serializable value.
 * @return {string} Canonical JSON representation.
 */
function stableStringify(value) {
  if (Array.isArray(value)) {
    return `[${value.map(stableStringify).join(',')}]`;
  }

  if (value && typeof value === 'object') {
    return `{${Object.keys(value)
      .sort()
      .map((key) => `${JSON.stringify(key)}:${stableStringify(value[key])}`)
      .join(',')}}`;
  }

  return JSON.stringify(value);
}

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
    baseline: {},
    schema: [],
    runtime: {},
    fields: {},
    conditions: [],
    layout: { version: 1, steps: [] },
    fieldCatalog: [],
    integrations: [],
    offers: [],
    saving: false,
    savingCondition: false,
    savingLayout: false,
    savingOffer: false,
    resetting: false,
    exporting: false,
    importing: false,
  }),

  getters: {
    /**
     * Whether the live settings differ from the last saved snapshot.
     *
     * Compares against the baseline instead of tracking a flag, so reverting a
     * field back to its original value disables the save button again.
     */
    dirty(state) {
      return stableStringify(state.settings) !== stableStringify(state.baseline);
    },

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
      this.layout = this.runtime?.layout && Array.isArray(this.runtime.layout.steps)
        ? { version: this.runtime.layout.version || 1, steps: this.runtime.layout.steps }
        : { version: 1, steps: [] };
      this.fieldCatalog = Array.isArray(this.runtime?.layout?.field_catalog) ? this.runtime.layout.field_catalog : [];
      this.integrations = Array.isArray(this.runtime?.integrations) ? this.runtime.integrations : [];
      this.offers = Array.isArray(this.runtime?.offers) ? this.runtime.offers : [];
      this.baseline = cloneSettings(this.settings);
    },

    setSetting(key, value) {
      this.settings = { ...this.settings, [key]: value };
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
      const defaultTitles = {
        success: 'Salvo com sucesso',
        error: 'Ops! Ocorreu um erro.',
        info: 'Flexify Checkout',
      };
      const heading = title || defaultTitles[type] || defaultTitles.info;
      const notify = toast[type] || toast.info;

      return notify(heading, { description: message });
    },

    dismissToast(id) {
      toast.dismiss(id);
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
          this.baseline = cloneSettings(this.settings);
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
          this.baseline = cloneSettings(this.settings);
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

    async exportSettings() {
      if (this.exporting) {
        return;
      }

      this.exporting = true;

      try {
        const response = await apiGet('admin/settings/export');

        if (response?.status !== 'success' || !response.payload) {
          this.pushToast('error', response?.message || 'Não foi possível exportar as configurações.');

          return;
        }

        const json = JSON.stringify(response.payload, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');

        link.href = url;
        link.download = response.filename || 'flexify-checkout-settings.json';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);

        this.pushToast('success', 'As configurações foram exportadas com sucesso!', 'Exportado com sucesso');
      } catch (error) {
        this.pushToast('error', 'Não foi possível exportar as configurações.');
      } finally {
        this.exporting = false;
      }
    },

    async importSettings(payload) {
      if (this.importing) {
        return;
      }

      this.importing = true;

      try {
        const response = await apiPost('admin/settings/import', { payload });

        if (response?.status === 'success') {
          this.settings = response.settings || this.settings;
          this.runtime = response.runtime || this.runtime;
          this.baseline = cloneSettings(this.settings);
          this.pushToast('success', response.message || 'As configurações foram importadas com sucesso!', 'Importado com sucesso');

          // Import overwrites settings, fields and conditions wholesale; reload
          // so every store slice reflects the imported snapshot.
          if (response.reload) {
            window.setTimeout(() => window.location.reload(), 1200);
          }
        } else {
          this.pushToast('error', response?.message || 'Não foi possível importar as configurações.');
        }
      } catch (error) {
        this.pushToast('error', 'Não foi possível importar as configurações.');
      } finally {
        this.importing = false;
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
      this.savingCondition = true;

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
      } finally {
        this.savingCondition = false;
      }
    },

    saveCondition(rule, id = null) {
      return id
        ? this.conditionAction('admin/conditions/update', { id, rule })
        : this.conditionAction('admin/conditions', { rule });
    },

    removeCondition(id) {
      return this.conditionAction('admin/conditions/remove', { id });
    },

    /**
     * Reload the checkout offers from the server.
     */
    async loadOffers() {
      try {
        const response = await apiGet('admin/offers');

        if (response?.status === 'success' && Array.isArray(response.offers)) {
          this.offers = response.offers;
        }

        return response;
      } catch (error) {
        return null;
      }
    },

    /**
     * Create or update a single offer (upsert). Swaps the full offers list from
     * the server response so product enrichment and labels stay fresh.
     *
     * @param {Object} offer - Offer payload (include `id` to update).
     */
    async saveOffer(offer) {
      this.savingOffer = true;

      try {
        const response = await apiPost('admin/offers', { offer });

        if (response?.status === 'success' && Array.isArray(response.offers)) {
          this.offers = response.offers;
        }

        this.pushToast(response?.status === 'success' ? 'success' : 'error', response?.message || '');

        return response;
      } catch (error) {
        this.pushToast('error', 'Ocorreu um erro ao salvar a oferta.');

        return null;
      } finally {
        this.savingOffer = false;
      }
    },

    async deleteOffer(id) {
      try {
        const response = await apiDelete(`admin/offers/${id}`);

        if (response?.status === 'success' && Array.isArray(response.offers)) {
          this.offers = response.offers;
        }

        this.pushToast(response?.status === 'success' ? 'success' : 'error', response?.message || '');

        return response;
      } catch (error) {
        this.pushToast('error', 'Ocorreu um erro ao remover a oferta.');

        return null;
      }
    },

    /**
     * Reload the checkout builder layout + field catalog from the server.
     */
    async loadLayout() {
      try {
        const response = await apiGet('admin/layout');

        if (response?.status === 'success' && response.layout) {
          this.layout = { version: response.layout.version || 1, steps: response.layout.steps || [] };
          this.fieldCatalog = Array.isArray(response.field_catalog) ? response.field_catalog : this.fieldCatalog;
        }

        return response;
      } catch (error) {
        return null;
      }
    },

    /**
     * Persist the checkout builder layout. Refreshes the layout, field catalog
     * and the (synced) field map so the Fields Manager stays consistent.
     */
    async saveLayout(layout) {
      this.savingLayout = true;

      try {
        const response = await apiPost('admin/layout', { layout });

        if (response?.status === 'success' && response.layout) {
          this.layout = { version: response.layout.version || 1, steps: response.layout.steps || [] };
          this.fieldCatalog = Array.isArray(response.field_catalog) ? response.field_catalog : this.fieldCatalog;
          this.fields = response.fields || this.fields;
        }

        this.pushToast(response?.status === 'success' ? 'success' : 'error', response?.message || '');

        return response;
      } catch (error) {
        this.pushToast('error', 'Ocorreu um erro ao salvar o construtor de checkout.');

        return null;
      } finally {
        this.savingLayout = false;
      }
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

    /**
     * Fetch the first-run setup wizard context (countries, current WooCommerce
     * config, license and Brazilian settings state).
     *
     * @return {Promise<Object|null>} Context payload or null on failure.
     */
    async loadWizardContext() {
      try {
        const response = await apiGet('admin/setup-wizard');

        return response?.status === 'success' ? response.context : null;
      } catch (error) {
        this.pushToast('error', 'Não foi possível carregar o assistente de configuração.');

        return null;
      }
    },

    /**
     * Apply the setup wizard answers and refresh the runtime-derived slices.
     *
     * @param {Object} payload - Wizard answers (or { skip: true }).
     * @return {Promise<Object|null>} Response payload or null on failure.
     */
    async applyWizard(payload) {
      try {
        const response = await apiPost('admin/setup-wizard', payload);

        if (response?.runtime) {
          this.runtime = response.runtime;
          this.fields = response.runtime.fields && typeof response.runtime.fields === 'object' ? response.runtime.fields : this.fields;
          this.conditions = Array.isArray(response.runtime.conditions) ? response.runtime.conditions : this.conditions;
        }

        if (payload?.skip) {
          return response;
        }

        if (response?.status === 'success') {
          this.pushToast('success', response.message || 'Configuração concluída!', 'Assistente de configuração');

          (Array.isArray(response.notices) ? response.notices : []).forEach((notice) => {
            this.pushToast('info', notice);
          });
        } else {
          this.pushToast('error', response?.message || 'Não foi possível aplicar as configurações.');
        }

        return response;
      } catch (error) {
        this.pushToast('error', 'Ocorreu um erro ao aplicar o assistente de configuração.');

        return null;
      }
    },
  },
});
