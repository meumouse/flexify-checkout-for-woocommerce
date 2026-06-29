<script setup>
import { computed } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import ModalDialog from './ModalDialog.vue';
import BaseButton from '../buttons/BaseButton.vue';

/**
 * Upsell shown when a Pro option is interacted with on a site without an active
 * license. Offers two paths: open the License page to activate an existing
 * license, or open the plugin page to purchase one.
 *
 * @since 6.0.0
 */
defineProps({
  open: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const store = useSettingsStore();

const buyUrl = computed(
  () => store.license?.buy_url || 'https://meumouse.com/plugins/flexify-checkout-para-woocommerce/',
);

function goToLicense() {
  emit('close');
  // The License page is its own wp-admin submenu page: keep the current
  // admin.php path and just swap the ?page= slug.
  const url = new URL(window.location.href);
  url.searchParams.set('page', 'flexify-checkout-license');
  url.searchParams.delete('tab');
  window.location.href = url.toString();
}

function buyLicense() {
  window.open(buyUrl.value, '_blank', 'noopener');
  emit('close');
}
</script>

<template>
  <ModalDialog :open="open" title="Recurso Pro" @close="emit('close')">
    <div class="flex flex-col items-center gap-4 text-center">
      <span class="flex h-14 w-14 items-center justify-center rounded-full bg-primary-100 text-primary">
        <BoxIcon name="crown" type="solid" class="h-7 w-7" />
      </span>

      <p class="m-0 max-w-md text-[15px] leading-6 text-slate-600">
        Este é um recurso <strong class="text-primary">Pro</strong>. Ative uma licença para desbloquear
        todos os recursos Pro do Flexify Checkout.
      </p>
    </div>

    <template #footer>
      <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-center">
        <BaseButton variant="secondary" @click="goToLicense">Já tenho uma licença</BaseButton>
        <BaseButton variant="primary" @click="buyLicense">Comprar uma licença</BaseButton>
      </div>
    </template>
  </ModalDialog>
</template>
