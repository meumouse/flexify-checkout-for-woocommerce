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

// Kept as a script constant so the literal {{ … }} placeholders are not parsed
// as Vue interpolation in the template.
const varsHint = 'Variáveis: {{ first_name }}, {{ recovery_link }}, {{ coupon_code }}';

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
    send_window: { start_time: '', end_time: '' },
    channels: { email: 'no', whatsapp: 'yes' },
    coupon: couponDefault(),
  };
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
  form.send_window = {
    start_time: incoming.send_window?.start_time ?? '',
    end_time: incoming.send_window?.end_time ?? '',
  };
  form.channels = {
    whatsapp: incoming.channels?.whatsapp === 'no' ? 'no' : 'yes',
    email: incoming.channels?.email === 'yes' ? 'yes' : 'no',
  };
  form.coupon = incoming.coupon && typeof incoming.coupon === 'object'
    ? { ...couponDefault(), ...incoming.coupon }
    : couponDefault();
}

const isEditing = computed(() => !!props.event);

const isValid = computed(() => String(form.title).trim() !== '' && Number(form.delay_time) > 0);

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

              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Conteúdo da mensagem</label>
                <textarea v-model="form.message" :class="inputClass" class="min-h-[140px]"></textarea>
                <span class="mt-1 block text-[12px] text-slate-500">{{ varsHint }}</span>
              </div>
            </div>
          </section>

          <!-- Scheduling -->
          <section class="rounded-[12px] border border-slate-200 bg-white p-5">
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
          <section class="rounded-[12px] border border-slate-200 bg-white p-5">
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
          </section>

          <!-- Coupon -->
          <section class="rounded-[12px] border border-slate-200 bg-white p-5">
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
