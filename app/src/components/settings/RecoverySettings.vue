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
import FollowUpBuilder from './recovery/FollowUpBuilder.vue';
import CouponFields from './recovery/CouponFields.vue';
import BaseSelect from '../fields/BaseSelect.vue';
import FormSkeleton from '../skeletons/FormSkeleton.vue';

const loading = ref(true);
const saving = ref(false);
const error = ref('');
const saved = ref(false);
const activeSection = ref('general');

const sections = [
  { id: 'general', label: 'Geral' },
  { id: 'followups', label: 'Follow-ups' },
  { id: 'payments', label: 'Formas de pagamento' },
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
});

const SCALARS = [
  'time_for_lost_carts', 'time_unit_for_lost_carts',
  'follow_up_purchase_block_days', 'fallback_first_name',
  'joinotify_sender_phone', 'joinotify_test_phone', 'select_coupon',
];

const settings = reactive({
  time_for_lost_carts: 15,
  time_unit_for_lost_carts: 'minutes',
  follow_up_purchase_block_days: 0,
  fallback_first_name: 'Cliente',
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
});

function couponDefault() {
  return {
    enabled: 'no', generate_coupon: 'yes', coupon_prefix: 'CUPOM_', coupon_code: 'none',
    discount_type: 'percent', discount_value: '', allow_free_shipping: 'yes',
    expiration_time: '', expiration_time_unit: '', limit_usages: '', limit_usages_per_user: '',
  };
}

const builderOpen = ref(false);
const editingKey = ref(null);

const followUpList = computed(() =>
  Object.keys(settings.follow_up_events).map((key) => ({ key, event: settings.follow_up_events[key] }))
);

const whatsappEnabled = computed(() => settings.toggles.enable_joinotify_integration === 'yes');

const editingEvent = computed(() =>
  editingKey.value !== null ? settings.follow_up_events[editingKey.value] || null : null
);

const DELAY_UNIT_SHORT = { minutes: 'min', hours: 'h', days: 'd' };

function toggle(key) {
  settings.toggles[key] = settings.toggles[key] === 'yes' ? 'no' : 'yes';
}

function toggleFollowUp(key) {
  const event = settings.follow_up_events[key];

  if (event) {
    event.enabled = event.enabled === 'yes' ? 'no' : 'yes';
  }
}

function followUpSummary(event) {
  const unit = DELAY_UNIT_SHORT[event.delay_type] || '';
  const when = `Enviar após ${event.delay_time || 0} ${unit}`.trim();
  const channels = [];

  if (event.channels?.whatsapp === 'yes') channels.push('WhatsApp');
  if (event.channels?.email === 'yes') channels.push('E-mail');

  return channels.length ? `${when} • ${channels.join(', ')}` : when;
}

function openCreateFollowUp() {
  editingKey.value = null;
  builderOpen.value = true;
}

function openEditFollowUp(key) {
  editingKey.value = key;
  builderOpen.value = true;
}

function closeBuilder() {
  builderOpen.value = false;
  editingKey.value = null;
}

function saveFollowUp(event) {
  if (editingKey.value !== null && settings.follow_up_events[editingKey.value]) {
    settings.follow_up_events[editingKey.value] = event;
  } else {
    settings.follow_up_events[`custom_${Date.now()}`] = event;
  }
}

function removeFollowUp(key) {
  if (window.confirm('Tem certeza que deseja remover este follow-up?')) {
    delete settings.follow_up_events[key];
  }
}

function ensurePaymentDefaults() {
  support.gateways.forEach((g) => {
    if (!settings.payment_methods[g.id] || typeof settings.payment_methods[g.id] !== 'object') {
      settings.payment_methods[g.id] = { delay_time: 5, delay_unit: 'minutes' };
    }
  });
}

function applyServerSettings(s) {
  if (!s || typeof s !== 'object') return;
  SCALARS.forEach((k) => { if (s[k] !== undefined && s[k] !== null) settings[k] = s[k]; });
  if (s.toggles) Object.keys(settings.toggles).forEach((k) => { if (s.toggles[k] !== undefined) settings.toggles[k] = s.toggles[k]; });
  settings.follow_up_events = s.follow_up_events && typeof s.follow_up_events === 'object' ? s.follow_up_events : {};
  settings.payment_methods = s.payment_methods && typeof s.payment_methods === 'object' ? s.payment_methods : {};
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
    <FormSkeleton v-if="loading" :pills="3" :groups="3" />

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
            <span class="mb-1 block text-[13px] font-semibold text-brand">Nome padrão do cliente</span>
            <input class="flexify-field-input" type="text" v-model="settings.fallback_first_name" />
          </label>
          <div class="block">
            <span class="mb-1 block text-[13px] font-semibold text-brand">Tempo para considerar abandonado</span>
            <div class="flex gap-2">
              <input class="flexify-field-input" type="number" min="1" v-model.number="settings.time_for_lost_carts" />
              <BaseSelect v-model="settings.time_unit_for_lost_carts" :options="support.time_units" size="sm" class="w-32 shrink-0" />
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

        <div class="py-4">
          <label class="flex items-center gap-2 text-[14px] text-slate-700">
            <input type="checkbox" :checked="settings.toggles.enable_get_location_from_ip === 'yes'" @change="toggle('enable_get_location_from_ip')" />
            Obter localização do cliente pelo IP
          </label>
        </div>
      </section>

      <!-- Follow-ups -->
      <section v-show="activeSection === 'followups'">
        <div class="mb-4 flex items-center justify-between gap-3">
          <p class="m-0 text-[13px] text-slate-500">Mensagens enviadas automaticamente após o abandono do carrinho.</p>
          <button type="button" class="inline-flex items-center gap-1.5 rounded-[8px] bg-primary px-4 py-2 text-[13px] font-semibold text-white" @click="openCreateFollowUp">
            <BoxIcon name="plus" class="h-4 w-4" />
            Adicionar follow-up
          </button>
        </div>

        <div
          v-if="!followUpList.length"
          class="rounded-xl border border-dashed border-slate-300 bg-slate-50/60 px-4 py-10 text-center"
        >
          <p class="m-0 text-sm font-medium text-ink">Nenhum follow-up configurado</p>
          <p class="m-0 mt-1 text-xs text-muted">Crie sua primeira mensagem de recuperação.</p>
        </div>

        <ul v-else class="m-0 flex list-none flex-col gap-2 p-0">
          <li
            v-for="item in followUpList"
            :key="item.key"
            class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 transition hover:border-slate-300"
            :class="item.event.enabled === 'no' ? 'opacity-60' : ''"
          >
            <div class="min-w-0">
              <p class="m-0 truncate text-sm font-medium text-ink">{{ item.event.title || 'Mensagem' }}</p>
              <p class="m-0 mt-0.5 truncate text-xs text-muted">{{ followUpSummary(item.event) }}</p>
            </div>

            <div class="flex shrink-0 items-center gap-1.5">
              <label class="mr-1 inline-flex cursor-pointer items-center" :title="item.event.enabled === 'no' ? 'Inativo' : 'Ativo'">
                <input
                  type="checkbox"
                  class="peer sr-only"
                  :checked="item.event.enabled !== 'no'"
                  @change="toggleFollowUp(item.key)"
                />
                <span class="relative h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-primary after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-4" />
              </label>

              <button
                type="button"
                class="cursor-pointer rounded-lg border border-slate-300 bg-transparent px-2.5 py-1 text-xs font-medium text-ink transition-colors hover:bg-slate-100"
                @click="openEditFollowUp(item.key)"
              >
                Editar
              </button>

              <button
                type="button"
                class="cursor-pointer rounded-lg border border-danger/30 bg-transparent px-2 py-1 text-danger transition-colors hover:bg-danger/10"
                aria-label="Remover follow-up"
                @click="removeFollowUp(item.key)"
              >
                <BoxIcon name="trash" class="h-4 w-4" />
              </button>
            </div>
          </li>
        </ul>
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
                  <BaseSelect v-model="settings.payment_methods[g.id].delay_unit" :options="support.time_units" size="sm" class="w-32" />
                </td>
              </tr>
            </tbody>
          </table>
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

    <FollowUpBuilder
      :open="builderOpen"
      :event="editingEvent"
      :coupons="support.coupons"
      :whatsapp-enabled="whatsappEnabled"
      @save="saveFollowUp"
      @close="closeBuilder"
    />
  </div>
</template>
