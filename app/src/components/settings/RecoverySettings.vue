<script setup>
/**
 * Recovery settings card (Configurações > Recuperação).
 *
 * Self-contained editor for the common cart recovery settings, backed by its
 * own REST endpoint (flexify-checkout/v1/recovery/settings) since they live in
 * a separate option from the main checkout settings. The complex editors
 * (follow-up events, payment delays, webhooks, lead modal) remain on the
 * advanced screen, linked at the bottom.
 *
 * @since 6.0.0
 */
import { ref, reactive, onMounted } from 'vue';
import { apiGet, apiPost } from '../../services/api';

const loading = ref(true);
const saving = ref(false);
const error = ref('');
const saved = ref(false);
const advancedUrl = ref('');
const timeUnits = ref([
  { value: 'minutes', label: 'Minutos' },
  { value: 'hours', label: 'Horas' },
  { value: 'days', label: 'Dias' },
]);

const settings = reactive({
  task_scheduler: 'wp_cron',
  time_for_lost_carts: 15,
  time_unit_for_lost_carts: 'minutes',
  follow_up_purchase_block_days: 0,
  fallback_first_name: 'Cliente',
  primary_color: '#008aff',
  joinotify_sender_phone: 'none',
  joinotify_test_phone: '',
  toggles: {
    enable_cart_recovery: 'yes',
    enable_joinotify_integration: 'yes',
    enable_email_integration: 'no',
    enable_modal_add_to_cart: 'yes',
    enable_international_phone_modal: 'yes',
    display_modal_for_logged_users: 'no',
    enable_get_location_from_ip: 'yes',
  },
});

function applyServerSettings(data) {
  if (!data || typeof data !== 'object') return;
  Object.keys(settings).forEach((key) => {
    if (key === 'toggles') return;
    if (data[key] !== undefined && data[key] !== null) settings[key] = data[key];
  });
  if (data.toggles && typeof data.toggles === 'object') {
    Object.keys(settings.toggles).forEach((key) => {
      if (data.toggles[key] !== undefined) settings.toggles[key] = data.toggles[key];
    });
  }
}

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const data = await apiGet('recovery/settings');
    applyServerSettings(data.settings);
    if (Array.isArray(data.time_units) && data.time_units.length) timeUnits.value = data.time_units;
    advancedUrl.value = data.advanced_url || '';
  } catch (e) {
    error.value = 'Não foi possível carregar as configurações de recuperação.';
  } finally {
    loading.value = false;
  }
}

async function save() {
  saving.value = true;
  error.value = '';
  saved.value = false;
  try {
    const data = await apiPost('recovery/settings', { settings });
    applyServerSettings(data.settings);
    saved.value = true;
    setTimeout(() => { saved.value = false; }, 3000);
  } catch (e) {
    error.value = 'Não foi possível salvar as configurações.';
  } finally {
    saving.value = false;
  }
}

function toggle(key) {
  settings.toggles[key] = settings.toggles[key] === 'yes' ? 'no' : 'yes';
}

onMounted(load);
</script>

<template>
  <div class="px-2 py-4">
    <div v-if="loading" class="skeleton-content" style="width: 100%; height: 360px;"></div>

    <template v-else>
      <div v-if="error" class="mb-4 rounded-[8px] bg-danger/10 px-5 py-3 text-[14px] text-danger" role="alert">{{ error }}</div>

      <!-- Master toggle -->
      <div class="flex items-start justify-between gap-6 border-b border-slate-100 py-4">
        <div>
          <h3 class="m-0 text-[15px] font-semibold text-brand">Ativar recuperação de carrinhos</h3>
          <p class="m-0 mt-1 text-[13px] text-slate-500">Ativa o rastreamento e as páginas de Análises, Todos os carrinhos e Fila no menu. Ao desativar, essas páginas ficam ocultas.</p>
        </div>
        <button type="button" role="switch" :aria-checked="settings.toggles.enable_cart_recovery === 'yes'"
          class="relative h-6 w-11 shrink-0 rounded-full transition"
          :class="settings.toggles.enable_cart_recovery === 'yes' ? 'bg-primary' : 'bg-slate-300'"
          @click="toggle('enable_cart_recovery')">
          <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white transition-all" :class="settings.toggles.enable_cart_recovery === 'yes' ? 'left-[22px]' : 'left-0.5'"></span>
        </button>
      </div>

      <!-- Scheduler + timing -->
      <div class="grid gap-4 border-b border-slate-100 py-4 md:grid-cols-2">
        <label class="block">
          <span class="mb-1 block text-[13px] font-semibold text-brand">Agendador de tarefas</span>
          <select class="flexify-field-input" v-model="settings.task_scheduler">
            <option value="wp_cron">WP-Cron (padrão)</option>
            <option value="php_cron">PHP-Cron</option>
          </select>
        </label>

        <label class="block">
          <span class="mb-1 block text-[13px] font-semibold text-brand">Nome padrão do cliente</span>
          <input class="flexify-field-input" type="text" v-model="settings.fallback_first_name" />
        </label>

        <div class="block">
          <span class="mb-1 block text-[13px] font-semibold text-brand">Tempo para considerar abandonado</span>
          <div class="flex gap-2">
            <input class="flexify-field-input" type="number" min="1" v-model.number="settings.time_for_lost_carts" />
            <select class="flexify-field-input w-auto" v-model="settings.time_unit_for_lost_carts">
              <option v-for="u in timeUnits" :key="u.value" :value="u.value">{{ u.label }}</option>
            </select>
          </div>
        </div>

        <label class="block">
          <span class="mb-1 block text-[13px] font-semibold text-brand">Bloquear follow-ups após compra (dias)</span>
          <input class="flexify-field-input" type="number" min="0" v-model.number="settings.follow_up_purchase_block_days" />
        </label>
      </div>

      <!-- Integrations -->
      <div class="border-b border-slate-100 py-4">
        <h3 class="m-0 mb-3 text-[15px] font-semibold text-brand">Integrações e canais</h3>
        <div class="grid gap-4 md:grid-cols-2">
          <label class="flex items-center gap-2 text-[14px] text-slate-700">
            <input type="checkbox" :checked="settings.toggles.enable_joinotify_integration === 'yes'" @change="toggle('enable_joinotify_integration')" />
            Enviar via WhatsApp (Joinotify)
          </label>
          <label class="flex items-center gap-2 text-[14px] text-slate-700">
            <input type="checkbox" :checked="settings.toggles.enable_email_integration === 'yes'" @change="toggle('enable_email_integration')" />
            Enviar via e-mail
          </label>
          <label class="block">
            <span class="mb-1 block text-[13px] font-semibold text-brand">Telefone remetente (Joinotify)</span>
            <input class="flexify-field-input" type="text" v-model="settings.joinotify_sender_phone" />
          </label>
          <label class="block">
            <span class="mb-1 block text-[13px] font-semibold text-brand">Telefone de teste</span>
            <input class="flexify-field-input" type="text" v-model="settings.joinotify_test_phone" />
          </label>
        </div>
      </div>

      <!-- Lead modal + location + style -->
      <div class="border-b border-slate-100 py-4">
        <h3 class="m-0 mb-3 text-[15px] font-semibold text-brand">Modal de captura e geolocalização</h3>
        <div class="grid gap-3">
          <label class="flex items-center gap-2 text-[14px] text-slate-700">
            <input type="checkbox" :checked="settings.toggles.enable_modal_add_to_cart === 'yes'" @change="toggle('enable_modal_add_to_cart')" />
            Exibir modal de coleta de lead ao adicionar ao carrinho
          </label>
          <label class="flex items-center gap-2 text-[14px] text-slate-700">
            <input type="checkbox" :checked="settings.toggles.enable_international_phone_modal === 'yes'" @change="toggle('enable_international_phone_modal')" />
            Campo de telefone internacional no modal
          </label>
          <label class="flex items-center gap-2 text-[14px] text-slate-700">
            <input type="checkbox" :checked="settings.toggles.display_modal_for_logged_users === 'yes'" @change="toggle('display_modal_for_logged_users')" />
            Exibir modal também para usuários logados
          </label>
          <label class="flex items-center gap-2 text-[14px] text-slate-700">
            <input type="checkbox" :checked="settings.toggles.enable_get_location_from_ip === 'yes'" @change="toggle('enable_get_location_from_ip')" />
            Obter localização do cliente pelo IP
          </label>
        </div>
      </div>

      <div class="flex items-center gap-3 py-4">
        <span class="text-[13px] font-semibold text-brand">Cor primária</span>
        <input type="color" v-model="settings.primary_color" class="h-9 w-12 cursor-pointer rounded border border-slate-200" />
        <input type="text" v-model="settings.primary_color" class="flexify-field-input w-32" />
      </div>

      <!-- Actions -->
      <div class="flex flex-wrap items-center gap-3 pt-2">
        <button type="button" class="rounded-[8px] bg-primary px-5 py-2.5 text-[14px] font-semibold text-white disabled:opacity-50" :disabled="saving" @click="save">
          {{ saving ? 'Salvando…' : 'Salvar configurações' }}
        </button>
        <span v-if="saved" class="text-[13px] font-semibold text-success">Configurações salvas!</span>

        <a v-if="advancedUrl" :href="advancedUrl" class="ml-auto rounded-[8px] border border-slate-200 px-4 py-2.5 text-[13px] font-semibold text-slate-700 hover:bg-slate-50">
          Editor avançado (follow-ups, cupons, webhooks)
        </a>
      </div>
    </template>
  </div>
</template>
