<script setup>
/**
 * Follow-up event builder (fullscreen overlay).
 *
 * Modelled on ConditionBuilder.vue: opens over everything via Teleport and edits
 * a single follow-up message. Works on a local clone of the event so the parent
 * list only updates when "Salvar" is pressed (emits the event back by reference).
 *
 * @since 6.0.0
 */
import { computed, reactive, watch } from 'vue';
import BaseButton from '../../buttons/BaseButton.vue';
import BaseSelect from '../../fields/BaseSelect.vue';
import CouponFields from './CouponFields.vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  event: { type: Object, default: null },
  coupons: { type: Array, default: () => [] },
  whatsappEnabled: { type: Boolean, default: true },
});

const emit = defineEmits(['close', 'save']);

const DELAY_TYPE_OPTIONS = [
  { value: 'minutes', label: 'Minutos' },
  { value: 'hours', label: 'Horas' },
  { value: 'days', label: 'Dias' },
];

const DELIVERY_MODE_OPTIONS = [
  { value: 'engine', label: 'Motor do Flexify (envio automático)' },
  { value: 'joinotify_workflow', label: 'Workflow do Joinotify (fluxo visual)' },
];

// Kept as a script constant so the literal {{ … }} placeholders are not parsed
// as Vue interpolation in the template.
const workflowVarsHint = 'No Joinotify use: {{ fcrc_first_name }}, {{ fcrc_recovery_link }}, {{ fcrc_cart_total }}';

// Kept as a script constant so the literal {{ … }} placeholders are not parsed
// as Vue interpolation in the template.
const varsHint = 'Variáveis: {{ first_name }}, {{ recovery_link }}, {{ coupon_code }}, {{ optout_link }}';

const inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-100';

function couponDefault() {
  return {
    enabled: 'no', generate_coupon: 'yes', coupon_prefix: 'CUPOM_', coupon_code: 'none',
    discount_type: 'percent', discount_value: '', allow_free_shipping: 'yes',
    expiration_time: '', expiration_time_unit: '', limit_usages: '', limit_usages_per_user: '',
  };
}

function blankEvent() {
  return {
    enabled: 'yes', title: 'Nova mensagem', message: '',
    delay_time: 1, delay_type: 'hours',
    delivery_mode: 'engine',
    email_subject: '',
    send_window: { start_time: '', end_time: '' },
    channels: { email: 'no', whatsapp: 'yes' },
    ab_test: { enabled: 'no', variants: [] },
    coupon: couponDefault(),
  };
}

function blankVariant() {
  return { message: '', email_subject: '' };
}

const form = reactive(blankEvent());

function hydrate(source) {
  const base = blankEvent();
  const incoming = source && typeof source === 'object' ? JSON.parse(JSON.stringify(source)) : {};

  form.enabled = incoming.enabled === 'no' ? 'no' : 'yes';
  form.title = incoming.title ?? base.title;
  form.message = incoming.message ?? '';
  form.delay_time = incoming.delay_time ?? base.delay_time;
  form.delay_type = incoming.delay_type ?? base.delay_type;
  form.delivery_mode = incoming.delivery_mode === 'joinotify_workflow' ? 'joinotify_workflow' : 'engine';
  form.email_subject = incoming.email_subject ?? '';
  form.send_window = {
    start_time: incoming.send_window?.start_time ?? '',
    end_time: incoming.send_window?.end_time ?? '',
  };
  form.channels = {
    whatsapp: incoming.channels?.whatsapp === 'no' ? 'no' : 'yes',
    email: incoming.channels?.email === 'yes' ? 'yes' : 'no',
  };
  form.ab_test = {
    enabled: incoming.ab_test?.enabled === 'yes' ? 'yes' : 'no',
    variants: Array.isArray(incoming.ab_test?.variants)
      ? incoming.ab_test.variants.map((v) => ({ message: v?.message ?? '', email_subject: v?.email_subject ?? '' }))
      : [],
  };
  form.coupon = incoming.coupon && typeof incoming.coupon === 'object'
    ? { ...couponDefault(), ...incoming.coupon }
    : couponDefault();
}

const isEditing = computed(() => !!props.event);

// Delivery routed to a Joinotify workflow: the built-in engine sends nothing,
// so the Flexify-side scheduling/channels/coupon/message are irrelevant.
const isWorkflowMode = computed(() => form.delivery_mode === 'joinotify_workflow');

// The workflow option is only meaningful when the Joinotify integration is on;
// keep it visible for an already-saved workflow event so it can be changed back.
const showDeliveryMode = computed(() => props.whatsappEnabled || isWorkflowMode.value);

const isValid = computed(() => {
  if (String(form.title).trim() === '') {
    return false;
  }

  // In workflow mode the delay is defined inside the Joinotify workflow.
  return isWorkflowMode.value || Number(form.delay_time) > 0;
});

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      hydrate(props.event);
    }
  },
  { immediate: true },
);

function toggle(key) {
  form[key] = form[key] === 'yes' ? 'no' : 'yes';
}

function toggleChannel(channel) {
  form.channels[channel] = form.channels[channel] === 'yes' ? 'no' : 'yes';
}

function toggleAbTest() {
  form.ab_test.enabled = form.ab_test.enabled === 'yes' ? 'no' : 'yes';

  // Seed a first alternate so the section is immediately actionable.
  if (form.ab_test.enabled === 'yes' && form.ab_test.variants.length === 0) {
    form.ab_test.variants.push(blankVariant());
  }
}

function addVariant() {
  form.ab_test.variants.push(blankVariant());
}

function removeVariant(index) {
  form.ab_test.variants.splice(index, 1);
}

// Variant letters after the base message: variant 0 is "B", 1 is "C", ...
function variantLetter(index) {
  return String.fromCharCode(66 + index);
}

function submit() {
  if (!isValid.value) {
    return;
  }

  emit('save', JSON.parse(JSON.stringify(form)));
  emit('close');
}
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[99999] flex flex-col bg-slate-50">
      <!-- Header -->
      <header class="flex shrink-0 items-center justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
        <div class="flex items-center gap-3">
          <button
            type="button"
            class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
            @click="emit('close')"
          >
            <BoxIcon name="chevron-left" class="h-4 w-4" />
            Voltar
          </button>

          <div>
            <h2 class="m-0 text-[15px] font-semibold text-ink">{{ isEditing ? 'Editar follow-up' : 'Novo follow-up' }}</h2>
            <p class="m-0 text-[13px] text-slate-500">Defina a mensagem, o tempo de envio e os canais.</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <BaseButton variant="secondary" size="sm" @click="emit('close')">Cancelar</BaseButton>
          <BaseButton size="sm" :disabled="!isValid" @click="submit">
            {{ isEditing ? 'Salvar alterações' : 'Adicionar follow-up' }}
          </BaseButton>
        </div>
      </header>

      <!-- Body -->
      <div class="flex-1 overflow-y-auto">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-6 py-8">
          <!-- Delivery mode -->
          <section v-if="showDeliveryMode" class="rounded-[12px] border border-slate-200 bg-white p-5">
            <div class="mb-4 flex items-center gap-2">
              <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary-100 text-primary">
                <BoxIcon name="git-branch" class="h-4 w-4" />
              </span>
              <h3 class="m-0 text-sm font-semibold text-ink">Entrega</h3>
            </div>

            <label class="mb-1 block text-sm font-medium text-ink">Quem envia esta mensagem?</label>
            <BaseSelect v-model="form.delivery_mode" :options="DELIVERY_MODE_OPTIONS" size="sm" class="w-full sm:w-96" />
            <p class="m-0 mt-2 text-[12px] text-slate-500">
              No modo <strong>Workflow do Joinotify</strong>, o Flexify apenas dispara o gatilho de
              carrinho abandonado — o tempo, o conteúdo e os canais são definidos no fluxo visual do Joinotify.
            </p>
          </section>

          <!-- Message -->
          <section class="rounded-[12px] border border-slate-200 bg-white p-5">
            <div class="mb-4 flex items-center gap-2">
              <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary-100 text-primary">
                <BoxIcon name="note" class="h-4 w-4" />
              </span>
              <h3 class="m-0 text-sm font-semibold text-ink">Mensagem</h3>
            </div>

            <div class="grid gap-4">
              <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
                <div>
                  <label class="mb-1 block text-sm font-medium text-ink">Título</label>
                  <input v-model="form.title" type="text" :class="inputClass" placeholder="Ex.: Lembrete amigável" />
                </div>

                <label class="flex cursor-pointer items-center gap-2 pb-2 text-sm font-medium text-ink">
                  <input :checked="form.enabled === 'yes'" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary-100" @change="toggle('enabled')" />
                  Ativo
                </label>
              </div>

              <div v-if="!isWorkflowMode">
                <label class="mb-1 block text-sm font-medium text-ink">Conteúdo da mensagem</label>
                <textarea v-model="form.message" :class="inputClass" class="min-h-[140px]"></textarea>
                <span class="mt-1 block text-[12px] text-slate-500">{{ varsHint }}</span>
              </div>
            </div>
          </section>

          <!-- A/B testing -->
          <section v-if="!isWorkflowMode" class="rounded-[12px] border border-slate-200 bg-white p-5">
            <div class="mb-4 flex items-center justify-between gap-3">
              <div class="flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary-100 text-primary">
                  <BoxIcon name="git-branch" class="h-4 w-4" />
                </span>
                <h3 class="m-0 text-sm font-semibold text-ink">Teste A/B</h3>
              </div>

              <label class="inline-flex cursor-pointer items-center" title="Ativar teste A/B">
                <input type="checkbox" class="peer sr-only" :checked="form.ab_test.enabled === 'yes'" @change="toggleAbTest" />
                <span class="relative h-5 w-9 rounded-full bg-slate-300 transition-colors peer-checked:bg-primary after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-4" />
              </label>
            </div>

            <p class="m-0 text-[12px] text-slate-500">
              A mensagem principal acima é a <strong>variante A</strong>. Adicione variações para testar qual
              converte melhor — cada carrinho recebe uma variante de forma consistente, e o resultado aparece na página de Análises.
            </p>

            <div v-if="form.ab_test.enabled === 'yes'" class="mt-4 grid gap-4">
              <div v-for="(variant, index) in form.ab_test.variants" :key="index" class="rounded-[10px] border border-slate-200 bg-slate-50/60 p-4">
                <div class="mb-2 flex items-center justify-between">
                  <span class="text-[13px] font-semibold text-ink">Variante {{ variantLetter(index) }}</span>
                  <button type="button" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-danger transition hover:bg-danger/10" @click="removeVariant(index)">
                    <BoxIcon name="trash" class="h-3.5 w-3.5" />
                    Remover
                  </button>
                </div>

                <label class="mb-1 block text-xs font-medium text-slate-500">Conteúdo da mensagem</label>
                <textarea v-model="variant.message" :class="inputClass" class="min-h-[110px]"></textarea>

                <div v-if="form.channels.email === 'yes'" class="mt-3">
                  <label class="mb-1 block text-xs font-medium text-slate-500">Assunto do e-mail (opcional)</label>
                  <input v-model="variant.email_subject" type="text" :class="inputClass" placeholder="Deixe em branco para usar o assunto principal" />
                </div>
              </div>

              <button type="button" class="inline-flex w-fit items-center gap-1.5 rounded-[8px] border border-dashed border-slate-300 px-4 py-2 text-[13px] font-medium text-slate-600 transition hover:border-primary hover:text-primary" @click="addVariant">
                <BoxIcon name="plus" class="h-4 w-4" />
                Adicionar variante
              </button>
            </div>
          </section>

          <!-- Workflow delegation notice -->
          <section v-if="isWorkflowMode" class="rounded-[12px] border border-primary-200 bg-primary-50 p-5">
            <div class="flex items-start gap-3">
              <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary">
                <BoxIcon name="info-circle" class="h-4 w-4" />
              </span>
              <div>
                <h3 class="m-0 text-sm font-semibold text-ink">Entrega pelo Joinotify</h3>
                <p class="m-0 mt-1 text-[13px] text-slate-600">
                  Esta mensagem é entregue por um workflow do Joinotify no gatilho
                  <strong>“Carrinho abandonado”</strong>. Monte o fluxo (mensagem, atrasos, canais e
                  condições) no builder do Joinotify. O Flexify não agenda nem envia nada para este follow-up.
                </p>
                <p class="m-0 mt-2 text-[12px] text-slate-500">{{ workflowVarsHint }}</p>
              </div>
            </div>
          </section>

          <!-- Scheduling -->
          <section v-if="!isWorkflowMode" class="rounded-[12px] border border-slate-200 bg-white p-5">
            <div class="mb-4 flex items-center gap-2">
              <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary-100 text-primary">
                <BoxIcon name="hourglass" class="h-4 w-4" />
              </span>
              <h3 class="m-0 text-sm font-semibold text-ink">Agendamento</h3>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Enviar após</label>
                <div class="flex gap-2">
                  <input v-model="form.delay_time" type="number" min="1" :class="inputClass" />
                  <BaseSelect v-model="form.delay_type" :options="DELAY_TYPE_OPTIONS" size="sm" class="w-32 shrink-0" />
                </div>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Janela de envio (opcional)</label>
                <div class="flex items-center gap-2">
                  <input v-model="form.send_window.start_time" type="time" :class="inputClass" />
                  <span class="text-sm text-slate-400">até</span>
                  <input v-model="form.send_window.end_time" type="time" :class="inputClass" />
                </div>
              </div>
            </div>
          </section>

          <!-- Channels -->
          <section v-if="!isWorkflowMode" class="rounded-[12px] border border-slate-200 bg-white p-5">
            <div class="mb-4 flex items-center gap-2">
              <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary-100 text-primary">
                <BoxIcon name="rocket" class="h-4 w-4" />
              </span>
              <h3 class="m-0 text-sm font-semibold text-ink">Canais de envio</h3>
            </div>

            <div class="flex flex-wrap gap-6">
              <label class="flex items-center gap-2 text-sm text-slate-700" :class="!whatsappEnabled ? 'opacity-50' : ''">
                <input type="checkbox" :checked="form.channels.whatsapp === 'yes'" :disabled="!whatsappEnabled" @change="toggleChannel('whatsapp')" />
                WhatsApp
              </label>
              <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" :checked="form.channels.email === 'yes'" @change="toggleChannel('email')" />
                E-mail
              </label>
            </div>
            <p v-if="!whatsappEnabled" class="m-0 mt-2 text-[12px] text-slate-400">
              Ative a integração com o WhatsApp (Joinotify) na seção Geral para usar este canal.
            </p>

            <div v-if="form.channels.email === 'yes'" class="mt-4">
              <label class="mb-1 block text-sm font-medium text-ink">Assunto do e-mail</label>
              <input v-model="form.email_subject" type="text" :class="inputClass" placeholder="Ex.: Você esqueceu itens no seu carrinho" />
              <span class="mt-1 block text-[12px] text-slate-500">Deixe em branco para usar o assunto padrão. Quando WhatsApp e e-mail estão ativos, o e-mail é enviado apenas se o WhatsApp falhar.</span>
            </div>
          </section>

          <!-- Coupon -->
          <section v-if="!isWorkflowMode" class="rounded-[12px] border border-slate-200 bg-white p-5">
            <div class="mb-4 flex items-center gap-2">
              <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary-100 text-primary">
                <BoxIcon name="purchase-tag" class="h-4 w-4" />
              </span>
              <h3 class="m-0 text-sm font-semibold text-ink">Cupom de desconto</h3>
            </div>

            <CouponFields :coupon="form.coupon" :coupons="coupons" />
          </section>
        </div>
      </div>
    </div>
  </Teleport>
</template>
