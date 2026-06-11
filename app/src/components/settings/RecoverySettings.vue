<script setup>
/**
 * Recovery settings card (Configurações > Recuperação).
 *
 * Full editor for the cart recovery settings, backed by its own REST endpoint
 * (flexify-checkout/v1/recovery/settings) since they live in a separate option
 * from the main checkout settings. Organised in sub-sections: Geral, Follow-ups,
 * Formas de pagamento, Webhooks and Modal de captura.
 *
 * @since 6.0.0
 */
import { ref, reactive, computed, onMounted } from 'vue';
import { apiGet, apiPost } from '../../services/api';
import FollowUpEventCard from './recovery/FollowUpEventCard.vue';
import CouponFields from './recovery/CouponFields.vue';

const loading = ref(true);
const saving = ref(false);
const error = ref('');
const saved = ref(false);
const activeSection = ref('general');

const sections = [
  { id: 'general', label: 'Geral' },
  { id: 'followups', label: 'Follow-ups' },
  { id: 'payments', label: 'Formas de pagamento' },
  { id: 'webhooks', label: 'Webhooks' },
  { id: 'modal', label: 'Modal de captura' },
];

const support = reactive({
  time_units: [
    { value: 'minutes', label: 'Minutos' },
    { value: 'hours', label: 'Horas' },
    { value: 'days', label: 'Dias' },
  ],
  discount_types: [],
  coupons: [],
  gateways: [],
  webhook_events: [],
});

const SCALARS = [
  'task_scheduler', 'time_for_lost_carts', 'time_unit_for_lost_carts',
  'follow_up_purchase_block_days', 'fallback_first_name', 'primary_color',
  'joinotify_sender_phone', 'joinotify_test_phone', 'select_coupon',
];

const settings = reactive({
  task_scheduler: 'wp_cron',
  time_for_lost_carts: 15,
  time_unit_for_lost_carts: 'minutes',
  follow_up_purchase_block_days: 0,
  fallback_first_name: 'Cliente',
  primary_color: '#008aff',
  joinotify_sender_phone: 'none',
  joinotify_test_phone: '',
  select_coupon: 'none',
  toggles: {
    enable_cart_recovery: 'yes',
    enable_joinotify_integration: 'yes',
    enable_email_integration: 'no',
    enable_modal_add_to_cart: 'yes',
    enable_international_phone_modal: 'yes',
    display_modal_for_logged_users: 'no',
    enable_get_location_from_ip: 'yes',
  },
  follow_up_events: {},
  payment_methods: {},
  collect_lead_modal: {
    title: '', button_title: '', message: '', triggers_list: '',
    coupon: couponDefault(),
  },
  webhooks: {},
});

function couponDefault() {
  return {
    enabled: 'no', generate_coupon: 'yes', coupon_prefix: 'CUPOM_', coupon_code: 'none',
    discount_type: 'percent', discount_value: '', allow_free_shipping: 'yes',
    expiration_time: '', expiration_time_unit: '', limit_usages: '', limit_usages_per_user: '',
  };
}

function newEvent() {
  return {
    enabled: 'yes', title: 'Nova mensagem', message: '',
    delay_time: 1, delay_type: 'hours',
    send_window: { start_time: '', end_time: '' },
    channels: { email: 'no', whatsapp: 'yes' },
    coupon: couponDefault(),
  };
}

const followUpList = computed(() =>
  Object.keys(settings.follow_up_events).map((key) => ({ key, event: settings.follow_up_events[key] }))
);

const whatsappEnabled = computed(() => settings.toggles.enable_joinotify_integration === 'yes');

function toggle(key) {
  settings.toggles[key] = settings.toggles[key] === 'yes' ? 'no' : 'yes';
}

function addFollowUp() {
  const key = `custom_${Object.keys(settings.follow_up_events).length + 1}_${followUpList.value.length}`;
  settings.follow_up_events[key] = newEvent();
  activeSection.value = 'followups';
}

function removeFollowUp(key) {
  delete settings.follow_up_events[key];
}

function ensurePaymentDefaults() {
  support.gateways.forEach((g) => {
    if (!settings.payment_methods[g.id] || typeof settings.payment_methods[g.id] !== 'object') {
      settings.payment_methods[g.id] = { delay_time: 5, delay_unit: 'minutes' };
    }
  });
}

function ensureWebhookDefaults() {
  support.webhook_events.forEach((e) => {
    if (!Array.isArray(settings.webhooks[e.key])) settings.webhooks[e.key] = [];
  });
}

function addWebhook(key) {
  if (!Array.isArray(settings.webhooks[key])) settings.webhooks[key] = [];
  settings.webhooks[key].push({ enabled: 'yes', url: '', headers: {} });
}

function removeWebhook(key, index) {
  settings.webhooks[key].splice(index, 1);
}

function applyServerSettings(s) {
  if (!s || typeof s !== 'object') return;
  SCALARS.forEach((k) => { if (s[k] !== undefined && s[k] !== null) settings[k] = s[k]; });
  if (s.toggles) Object.keys(settings.toggles).forEach((k) => { if (s.toggles[k] !== undefined) settings.toggles[k] = s.toggles[k]; });
  settings.follow_up_events = s.follow_up_events && typeof s.follow_up_events === 'object' ? s.follow_up_events : {};
  settings.payment_methods = s.payment_methods && typeof s.payment_methods === 'object' ? s.payment_methods : {};
  settings.webhooks = s.webhooks && typeof s.webhooks === 'object' ? s.webhooks : {};
  if (s.collect_lead_modal && typeof s.collect_lead_modal === 'object' && Object.keys(s.collect_lead_modal).length) {
    settings.collect_lead_modal = {
      title: s.collect_lead_modal.title || '',
      button_title: s.collect_lead_modal.button_title || '',
      message: s.collect_lead_modal.message || '',
      triggers_list: s.collect_lead_modal.triggers_list || '',
      coupon: s.collect_lead_modal.coupon && typeof s.collect_lead_modal.coupon === 'object' ? s.collect_lead_modal.coupon : couponDefault(),
    };
  }
}

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const data = await apiGet('recovery/settings');
    if (data.support) Object.assign(support, data.support);
    applyServerSettings(data.settings);
    ensurePaymentDefaults();
    ensureWebhookDefaults();
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
    ensurePaymentDefaults();
    ensureWebhookDefaults();
    saved.value = true;
    setTimeout(() => { saved.value = false; }, 3000);
  } catch (e) {
    error.value = 'Não foi possível salvar as configurações.';
  } finally {
    saving.value = false;
  }
}

onMounted(load);
</script>

<template>
  <div class="px-2 py-4">
    <div v-if="loading" class="skeleton-content" style="width: 100%; height: 360px;"></div>

    <template v-else>
      <div v-if="error" class="mb-4 rounded-[8px] bg-danger/10 px-5 py-3 text-[14px] text-danger" role="alert">{{ error }}</div>

      <nav class="mb-5 flex w-fit max-w-full flex-wrap overflow-hidden rounded-[8px] bg-[#e7edf5] p-0.5">
        <button v-for="s in sections" :key="s.id" type="button"
          class="cursor-pointer rounded-none px-4 py-2 text-[13px] font-semibold transition first:rounded-l-[8px] last:rounded-r-[8px]"
          :class="activeSection === s.id ? 'bg-primary text-white' : 'bg-transparent text-slate-600 hover:bg-[#d0dce9]'"
          @click="activeSection = s.id">{{ s.label }}</button>
      </nav>

      <!-- General -->
      <section v-show="activeSection === 'general'">
        <div class="flex items-start justify-between gap-6 border-b border-slate-100 py-4">
          <div>
            <h3 class="m-0 text-[15px] font-semibold text-brand">Ativar recuperação de carrinhos</h3>
            <p class="m-0 mt-1 text-[13px] text-slate-500">Ativa o rastreamento e as páginas de Análises, Todos os carrinhos e Fila no menu.</p>
          </div>
          <button type="button" role="switch" :aria-checked="settings.toggles.enable_cart_recovery === 'yes'"
            class="relative h-6 w-11 shrink-0 rounded-full transition"
            :class="settings.toggles.enable_cart_recovery === 'yes' ? 'bg-primary' : 'bg-slate-300'"
            @click="toggle('enable_cart_recovery')">
            <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white transition-all" :class="settings.toggles.enable_cart_recovery === 'yes' ? 'left-[22px]' : 'left-0.5'"></span>
          </button>
        </div>

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
                <option v-for="u in support.time_units" :key="u.value" :value="u.value">{{ u.label }}</option>
              </select>
            </div>
          </div>
          <label class="block">
            <span class="mb-1 block text-[13px] font-semibold text-brand">Bloquear follow-ups após compra (dias)</span>
            <input class="flexify-field-input" type="number" min="0" v-model.number="settings.follow_up_purchase_block_days" />
          </label>
        </div>

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

        <div class="flex items-center gap-3 py-4">
          <span class="text-[13px] font-semibold text-brand">Cor primária</span>
          <input type="color" v-model="settings.primary_color" class="h-9 w-12 cursor-pointer rounded border border-slate-200" />
          <input type="text" v-model="settings.primary_color" class="flexify-field-input w-32" />
          <label class="ml-4 flex items-center gap-2 text-[14px] text-slate-700">
            <input type="checkbox" :checked="settings.toggles.enable_get_location_from_ip === 'yes'" @change="toggle('enable_get_location_from_ip')" />
            Obter localização do cliente pelo IP
          </label>
        </div>
      </section>

      <!-- Follow-ups -->
      <section v-show="activeSection === 'followups'">
        <div class="mb-3 flex items-center justify-between">
          <p class="m-0 text-[13px] text-slate-500">Mensagens enviadas automaticamente após o abandono do carrinho.</p>
          <button type="button" class="rounded-[8px] bg-primary px-4 py-2 text-[13px] font-semibold text-white" @click="addFollowUp">Adicionar follow-up</button>
        </div>
        <div class="grid gap-4">
          <p v-if="!followUpList.length" class="text-[14px] text-slate-500">Nenhum follow-up configurado.</p>
          <FollowUpEventCard
            v-for="item in followUpList"
            :key="item.key"
            :event="item.event"
            :coupons="support.coupons"
            :whatsapp-enabled="whatsappEnabled"
            @remove="removeFollowUp(item.key)"
          />
        </div>
      </section>

      <!-- Payments -->
      <section v-show="activeSection === 'payments'">
        <p class="mb-3 text-[13px] text-slate-500">Atraso para considerar o pedido abandonado por forma de pagamento.</p>
        <div class="overflow-hidden rounded-[8px] ring-1 ring-slate-100">
          <table class="w-full border-collapse text-[13px]">
            <thead>
              <tr class="border-b border-slate-100 text-left text-slate-500">
                <th class="px-4 py-3 font-semibold">Forma de pagamento</th>
                <th class="px-4 py-3 font-semibold">Tempo</th>
                <th class="px-4 py-3 font-semibold">Unidade</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!support.gateways.length"><td colspan="3" class="px-4 py-6 text-center text-slate-500">Nenhum gateway de pagamento ativo.</td></tr>
              <tr v-for="g in support.gateways" :key="g.id" class="border-b border-slate-50">
                <td class="px-4 py-3 text-slate-700">{{ g.title }}</td>
                <td class="px-4 py-3"><input class="flexify-field-input w-28" type="number" min="0" v-model="settings.payment_methods[g.id].delay_time" /></td>
                <td class="px-4 py-3">
                  <select class="flexify-field-input w-auto" v-model="settings.payment_methods[g.id].delay_unit">
                    <option v-for="u in support.time_units" :key="u.value" :value="u.value">{{ u.label }}</option>
                  </select>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Webhooks -->
      <section v-show="activeSection === 'webhooks'">
        <p class="mb-3 text-[13px] text-slate-500">Envie uma requisição POST para uma URL externa quando cada evento ocorrer.</p>
        <div class="grid gap-4">
          <div v-for="ev in support.webhook_events" :key="ev.key" class="rounded-[8px] border border-slate-200 p-4">
            <div class="flex items-center justify-between">
              <h3 class="m-0 text-[14px] font-semibold text-brand">{{ ev.label }}</h3>
              <button type="button" class="rounded-[6px] px-2.5 py-1 text-xs font-semibold text-primary hover:bg-primary-100" @click="addWebhook(ev.key)">Adicionar URL</button>
            </div>
            <div v-if="settings.webhooks[ev.key] && settings.webhooks[ev.key].length" class="mt-3 grid gap-2">
              <div v-for="(hook, i) in settings.webhooks[ev.key]" :key="i" class="flex items-center gap-2">
                <input type="checkbox" :checked="hook.enabled === 'yes'" @change="hook.enabled = hook.enabled === 'yes' ? 'no' : 'yes'" />
                <input class="flexify-field-input flex-1" type="url" placeholder="https://…" v-model="hook.url" />
                <button type="button" class="rounded-[6px] px-2.5 py-1 text-xs font-semibold text-danger hover:bg-danger/10" @click="removeWebhook(ev.key, i)">Remover</button>
              </div>
            </div>
            <p v-else class="mt-2 text-[12px] text-slate-400">Nenhuma URL configurada.</p>
          </div>
        </div>
      </section>

      <!-- Lead modal -->
      <section v-show="activeSection === 'modal'">
        <div class="grid gap-3">
          <label class="flex items-center gap-2 text-[14px] text-slate-700">
            <input type="checkbox" :checked="settings.toggles.enable_modal_add_to_cart === 'yes'" @change="toggle('enable_modal_add_to_cart')" />
            Exibir modal de coleta de lead ao adicionar ao carrinho
          </label>
          <div class="grid gap-3 md:grid-cols-2">
            <label class="block">
              <span class="mb-1 block text-[13px] font-semibold text-brand">Título do modal</span>
              <input class="flexify-field-input" type="text" v-model="settings.collect_lead_modal.title" />
            </label>
            <label class="block">
              <span class="mb-1 block text-[13px] font-semibold text-brand">Texto do botão</span>
              <input class="flexify-field-input" type="text" v-model="settings.collect_lead_modal.button_title" />
            </label>
          </div>
          <label class="block">
            <span class="mb-1 block text-[13px] font-semibold text-brand">Mensagem enviada ao coletar o lead</span>
            <textarea class="flexify-field-input min-h-[100px]" v-model="settings.collect_lead_modal.message"></textarea>
          </label>
          <label class="block">
            <span class="mb-1 block text-[13px] font-semibold text-brand">Seletores que abrem o modal</span>
            <textarea class="flexify-field-input" v-model="settings.collect_lead_modal.triggers_list"></textarea>
          </label>
          <div class="grid gap-3 md:grid-cols-2">
            <label class="flex items-center gap-2 text-[14px] text-slate-700">
              <input type="checkbox" :checked="settings.toggles.enable_international_phone_modal === 'yes'" @change="toggle('enable_international_phone_modal')" />
              Campo de telefone internacional
            </label>
            <label class="flex items-center gap-2 text-[14px] text-slate-700">
              <input type="checkbox" :checked="settings.toggles.display_modal_for_logged_users === 'yes'" @change="toggle('display_modal_for_logged_users')" />
              Exibir também para usuários logados
            </label>
          </div>
          <CouponFields v-if="settings.collect_lead_modal.coupon" :coupon="settings.collect_lead_modal.coupon" :coupons="support.coupons" />
        </div>
      </section>

      <!-- Actions -->
      <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4">
        <button type="button" class="rounded-[8px] bg-primary px-5 py-2.5 text-[14px] font-semibold text-white disabled:opacity-50" :disabled="saving" @click="save">
          {{ saving ? 'Salvando…' : 'Salvar configurações' }}
        </button>
        <span v-if="saved" class="text-[13px] font-semibold text-success">Configurações salvas!</span>
      </div>
    </template>
  </div>
</template>
