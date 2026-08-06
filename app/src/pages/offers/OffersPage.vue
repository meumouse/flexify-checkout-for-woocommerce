<script setup>
/**
 * Offers management page.
 *
 * Lists checkout offers (order bump / upsell / cross-sell / downsell) from the
 * hydrated store and orchestrates the full-screen <OfferEditor>. Creating and
 * editing offers is a Pro feature; without a valid license the list is read-only
 * and an upgrade banner is shown.
 *
 * @since 6.0.0
 */
import { ref, computed, onMounted } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../../components/buttons/BaseButton.vue';
import OfferEditor from './OfferEditor.vue';

const store = useSettingsStore();

const editorOpen = ref(false);
const editingOffer = ref(null);

const TYPE_LABELS = {
  order_bump: 'Order bump',
  upsell: 'Upsell',
  cross_sell: 'Cross-sell',
  downsell: 'Downsell',
};

const TYPE_CLASSES = {
  order_bump: 'bg-primary-100 text-primary',
  upsell: 'bg-success/10 text-success',
  cross_sell: 'bg-warning/10 text-warning',
  downsell: 'bg-slate-100 text-slate-600',
};

const offers = computed(() => store.offers || []);
const isPro = computed(() => store.isPro);
const buyUrl = computed(() => store.license?.buy_url || 'https://meumouse.com/plugins/flexify-checkout-para-woocommerce/');

function triggerSummary(offer) {
  const groups = offer.trigger?.groups || [];
  const count = groups.reduce((sum, group) => sum + (group.conditions?.length || 0), 0);

  return count === 0 ? 'Sempre' : `${count} condição${count > 1 ? 'ões' : ''}`;
}

function discountSummary(offer) {
  const discount = offer.discount || {};
  const symbol = store.runtime?.currency_symbol || 'R$';

  if (discount.mode === 'percent') {
    return `${Number(discount.value) || 0}%`;
  }

  if (discount.mode === 'fixed') {
    return `${symbol} ${Number(discount.value) || 0}`;
  }

  return '—';
}

function openCreate() {
  editingOffer.value = null;
  editorOpen.value = true;
}

function openEdit(offer) {
  editingOffer.value = offer;
  editorOpen.value = true;
}

async function toggleEnabled(offer) {
  await store.saveOffer({ ...offer, enabled: !offer.enabled });
}

async function duplicate(offer) {
  await store.saveOffer({ ...offer, id: undefined, name: `${offer.name || offer.headline || 'Oferta'} (cópia)` });
}

async function remove(offer) {
  if (!window.confirm(`Excluir a oferta "${offer.name || offer.headline || offer.id}"? Esta ação não pode ser desfeita.`)) {
    return;
  }

  await store.deleteOffer(offer.id);
}

onMounted(() => {
  if (!offers.value.length) {
    store.loadOffers();
  }
});
</script>

<template>
  <div class="flexify-settings-app-shell pb-8 pr-4">
    <header class="mb-6 mt-2 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <svg class="h-9 w-9" viewBox="0 0 1080 1080" xmlns="http://www.w3.org/2000/svg"><g><path fill="#141D26" d="M513.96,116.38c-234.22,0-424.07,189.86-424.07,424.07c0,234.21,189.86,424.08,424.07,424.08 c234.21,0,424.07-189.86,424.07-424.08C938.03,306.25,748.17,116.38,513.96,116.38z M685.34,542.48 c-141.76,0.37-257.11,117.68-257.41,259.44h-88.21c0-191.79,153.83-347.41,345.62-347.41V542.48z M685.34,365.84 c-141.76,0.2-266.84,69.9-346.06,176.13V410.6c91.73-82.48,212.64-133.1,346.06-133.1V365.84z"/></g></svg>
        <div>
          <h1 class="m-0 text-xl font-semibold text-brand">Ofertas</h1>
          <p class="m-0 mt-1 text-[13px] text-slate-500">Crie order bumps, upsells, cross-sells e downsells para o checkout.</p>
        </div>
      </div>

      <BaseButton v-if="isPro" size="sm" @click="openCreate">
        <BoxIcon name="plus" class="mr-1 h-4 w-4" />
        Nova oferta
      </BaseButton>
    </header>

    <!-- Pro gate -->
    <div v-if="!isPro" class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-[12px] border border-primary/20 bg-primary-100/40 p-5">
      <div class="flex items-center gap-3">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-primary text-white">
          <BoxIcon name="crown" type="solid" class="h-4 w-4" />
        </span>
        <div>
          <h3 class="m-0 text-sm font-semibold text-ink">Ofertas são um recurso Pro</h3>
          <p class="m-0 text-[13px] text-slate-500">Ative uma licença Pro para criar e exibir ofertas no checkout.</p>
        </div>
      </div>

      <a :href="buyUrl" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90">
        Conhecer o Pro
      </a>
    </div>

    <!-- Empty state -->
    <div v-if="!offers.length" class="rounded-[12px] border border-dashed border-slate-300 bg-white p-10 text-center">
      <span class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <BoxIcon name="purchase-tag" class="h-6 w-6" />
      </span>
      <h3 class="m-0 text-sm font-semibold text-ink">Nenhuma oferta criada</h3>
      <p class="mx-auto mt-1 max-w-md text-[13px] text-slate-500">
        Ofertas aparecem no checkout com base em regras do carrinho e ajudam a aumentar o valor médio do pedido.
      </p>
      <BaseButton v-if="isPro" class="mt-4" size="sm" @click="openCreate">Criar primeira oferta</BaseButton>
    </div>

    <!-- List -->
    <div v-else class="flex flex-col gap-3">
      <div
        v-for="offer in offers"
        :key="offer.id"
        class="flex flex-wrap items-center gap-4 rounded-[12px] border border-slate-200 bg-white p-4"
      >
        <img
          v-if="offer.product?.image"
          :src="offer.product.image"
          alt=""
          class="h-12 w-12 shrink-0 rounded-lg border border-slate-100 object-cover"
        />
        <span v-else class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
          <BoxIcon name="purchase-tag" class="h-5 w-5" />
        </span>

        <div class="min-w-[180px] flex-1">
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-ink">{{ offer.name || offer.headline || offer.product?.name || 'Oferta sem nome' }}</span>
            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="TYPE_CLASSES[offer.type]">{{ TYPE_LABELS[offer.type] || offer.type }}</span>
          </div>
          <div class="mt-0.5 text-[12px] text-slate-500">{{ offer.product?.name || 'Produto não definido' }}</div>
        </div>

        <div class="hidden text-center sm:block">
          <div class="text-[11px] uppercase tracking-wide text-slate-400">Gatilho</div>
          <div class="text-[13px] font-medium text-slate-600">{{ triggerSummary(offer) }}</div>
        </div>

        <div class="hidden text-center sm:block">
          <div class="text-[11px] uppercase tracking-wide text-slate-400">Desconto</div>
          <div class="text-[13px] font-medium text-slate-600">{{ discountSummary(offer) }}</div>
        </div>

        <label class="flex cursor-pointer items-center gap-2 text-[13px] text-slate-500">
          <input
            type="checkbox"
            class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary-100"
            :checked="offer.enabled"
            :disabled="!isPro"
            @change="toggleEnabled(offer)"
          />
          Ativa
        </label>

        <div class="flex items-center gap-1">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white p-2 text-slate-500 transition hover:border-primary hover:text-primary disabled:opacity-50"
            :disabled="!isPro"
            aria-label="Editar"
            @click="openEdit(offer)"
          >
            <BoxIcon name="edit" class="h-4 w-4" />
          </button>

          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white p-2 text-slate-500 transition hover:border-primary hover:text-primary disabled:opacity-50"
            :disabled="!isPro"
            aria-label="Duplicar"
            @click="duplicate(offer)"
          >
            <BoxIcon name="copy" class="h-4 w-4" />
          </button>

          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white p-2 text-slate-400 transition hover:border-danger/30 hover:text-danger disabled:opacity-50"
            :disabled="!isPro"
            aria-label="Excluir"
            @click="remove(offer)"
          >
            <BoxIcon name="trash" class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>

    <OfferEditor :open="editorOpen" :offer="editingOffer" @close="editorOpen = false" @saved="editorOpen = false" />
  </div>
</template>
