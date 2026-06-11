<script setup>
/**
 * One follow-up event editor card.
 *
 * Mutates the passed reactive `event` object in place. Emits `remove` so the
 * parent can drop it from the list.
 *
 * @since 6.0.0
 */
import CouponFields from './CouponFields.vue';

const props = defineProps({
  event: { type: Object, required: true },
  coupons: { type: Array, default: () => [] },
  whatsappEnabled: { type: Boolean, default: true },
});

defineEmits(['remove']);

function toggle(key) {
  props.event[key] = props.event[key] === 'yes' ? 'no' : 'yes';
}

function toggleChannel(channel) {
  if (!props.event.channels) props.event.channels = {};
  props.event.channels[channel] = props.event.channels[channel] === 'yes' ? 'no' : 'yes';
}

// Kept as a script constant so the literal {{ … }} placeholders are not parsed
// as Vue interpolation in the template.
const varsHint = 'Variáveis: {{ first_name }}, {{ recovery_link }}, {{ coupon_code }}';
</script>

<template>
  <div class="rounded-[8px] border border-slate-200 bg-white p-5">
    <div class="flex items-center justify-between gap-3">
      <label class="flex items-center gap-2 text-[14px] font-semibold text-brand">
        <input type="checkbox" :checked="event.enabled === 'yes'" @change="toggle('enabled')" />
        {{ event.title || 'Mensagem' }}
      </label>
      <button type="button" class="rounded-[6px] px-2.5 py-1 text-xs font-semibold text-danger hover:bg-danger/10" @click="$emit('remove')">Remover</button>
    </div>

    <div class="mt-3 grid gap-3">
      <label class="block">
        <span class="mb-1 block text-[13px] font-semibold text-brand">Título</span>
        <input class="flexify-field-input" type="text" v-model="event.title" />
      </label>

      <label class="block">
        <span class="mb-1 block text-[13px] font-semibold text-brand">Mensagem</span>
        <textarea class="flexify-field-input min-h-[120px]" v-model="event.message"></textarea>
        <span class="mt-1 block text-[12px] text-slate-500">{{ varsHint }}</span>
      </label>

      <div class="grid gap-3 md:grid-cols-2">
        <div class="block">
          <span class="mb-1 block text-[13px] font-semibold text-brand">Enviar após</span>
          <div class="flex gap-2">
            <input class="flexify-field-input" type="number" min="1" v-model="event.delay_time" />
            <select class="flexify-field-input w-auto" v-model="event.delay_type">
              <option value="minutes">Minutos</option>
              <option value="hours">Horas</option>
              <option value="days">Dias</option>
            </select>
          </div>
        </div>

        <div class="block">
          <span class="mb-1 block text-[13px] font-semibold text-brand">Canais de envio</span>
          <div class="flex flex-wrap gap-4 pt-2">
            <label class="flex items-center gap-2 text-[13px] text-slate-700">
              <input type="checkbox" :checked="event.channels && event.channels.whatsapp === 'yes'" :disabled="!whatsappEnabled" @change="toggleChannel('whatsapp')" />
              WhatsApp
            </label>
            <label class="flex items-center gap-2 text-[13px] text-slate-700">
              <input type="checkbox" :checked="event.channels && event.channels.email === 'yes'" @change="toggleChannel('email')" />
              E-mail
            </label>
          </div>
        </div>
      </div>

      <CouponFields v-if="event.coupon" :coupon="event.coupon" :coupons="coupons" />
    </div>
  </div>
</template>
