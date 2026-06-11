<script setup>
/**
 * Coupon configuration sub-form, reused by follow-up events and the lead modal.
 *
 * Mutates the passed reactive `coupon` object in place (same reference the
 * parent holds), so changes flow back into the settings payload on save.
 *
 * @since 6.0.0
 */
const props = defineProps({
  coupon: { type: Object, required: true },
  coupons: { type: Array, default: () => [] },
});

function toggle(key) {
  props.coupon[key] = props.coupon[key] === 'yes' ? 'no' : 'yes';
}
</script>

<template>
  <div class="rounded-[8px] bg-slate-50 p-4">
    <label class="flex items-center gap-2 text-[14px] font-semibold text-brand">
      <input type="checkbox" :checked="coupon.enabled === 'yes'" @change="toggle('enabled')" />
      Oferecer cupom de desconto
    </label>

    <div v-if="coupon.enabled === 'yes'" class="mt-3 grid gap-3">
      <label class="flex items-center gap-2 text-[13px] text-slate-700">
        <input type="checkbox" :checked="coupon.generate_coupon === 'yes'" @change="toggle('generate_coupon')" />
        Gerar cupom automaticamente
      </label>

      <!-- Use an existing coupon -->
      <label v-if="coupon.generate_coupon !== 'yes'" class="block">
        <span class="mb-1 block text-[13px] font-semibold text-brand">Cupom existente</span>
        <select class="flexify-field-input" v-model="coupon.coupon_code">
          <option value="none">Selecione um cupom de desconto</option>
          <option v-for="code in coupons" :key="code" :value="code">{{ code }}</option>
        </select>
      </label>

      <!-- Generate a new coupon -->
      <div v-else class="grid gap-3 md:grid-cols-2">
        <label class="block">
          <span class="mb-1 block text-[13px] font-semibold text-brand">Prefixo do código</span>
          <input class="flexify-field-input" type="text" v-model="coupon.coupon_prefix" />
        </label>
        <label class="block">
          <span class="mb-1 block text-[13px] font-semibold text-brand">Tipo de desconto</span>
          <select class="flexify-field-input" v-model="coupon.discount_type">
            <option value="percent">Percentual (%)</option>
            <option value="fixed_cart">Valor fixo</option>
          </select>
        </label>
        <label class="block">
          <span class="mb-1 block text-[13px] font-semibold text-brand">Valor do desconto</span>
          <input class="flexify-field-input" type="number" min="0" step="0.01" v-model="coupon.discount_value" />
        </label>
        <label class="flex items-center gap-2 pt-6 text-[13px] text-slate-700">
          <input type="checkbox" :checked="coupon.allow_free_shipping === 'yes'" @change="toggle('allow_free_shipping')" />
          Permitir frete grátis
        </label>
        <div class="block">
          <span class="mb-1 block text-[13px] font-semibold text-brand">Expiração</span>
          <div class="flex gap-2">
            <input class="flexify-field-input" type="number" min="0" v-model="coupon.expiration_time" placeholder="Tempo" />
            <select class="flexify-field-input w-auto" v-model="coupon.expiration_time_unit">
              <option value="">—</option>
              <option value="minutes">Minutos</option>
              <option value="hours">Horas</option>
              <option value="days">Dias</option>
            </select>
          </div>
        </div>
        <label class="block">
          <span class="mb-1 block text-[13px] font-semibold text-brand">Limite de usos</span>
          <input class="flexify-field-input" type="number" min="0" v-model="coupon.limit_usages" />
        </label>
        <label class="block">
          <span class="mb-1 block text-[13px] font-semibold text-brand">Limite de usos por cliente</span>
          <input class="flexify-field-input" type="number" min="0" v-model="coupon.limit_usages_per_user" />
        </label>
      </div>
    </div>
  </div>
</template>
