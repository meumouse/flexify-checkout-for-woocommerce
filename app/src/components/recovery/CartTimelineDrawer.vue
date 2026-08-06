<script setup>
/**
 * Cart recovery — per-cart detail drawer.
 *
 * Slides in from the right when a cart row is opened in CartsPage. Fetches the
 * cart's full detail and chronological event history from
 * GET recovery/carts/{id}/timeline and renders it as a vertical timeline
 * (creation, lead capture, abandonment, each follow-up with channel/variant,
 * and the recovering order).
 *
 * @since 6.0.0
 */
import { ref, watch } from 'vue';
import { apiGet } from '../../services/api';

const props = defineProps({
  open: { type: Boolean, default: false },
  cartId: { type: [Number, null], default: null },
});

const emit = defineEmits(['close']);

const loading = ref(false);
const error = ref('');
const cart = ref(null);
const events = ref([]);

const ICONS = {
  created: 'cart',
  lead: 'user',
  abandoned: 'time-five',
  notification: 'paper-plane',
  order: 'check',
};

const ICON_CLASSES = {
  created: 'bg-slate-100 text-slate-500',
  lead: 'bg-primary-100 text-primary',
  abandoned: 'bg-warning/10 text-warning',
  notification: 'bg-primary-100 text-primary',
  order: 'bg-success/10 text-success',
};

function iconFor(type) {
  return ICONS[type] || 'note';
}

function iconClass(type) {
  return ICON_CLASSES[type] || 'bg-slate-100 text-slate-500';
}

async function load() {
  if (!props.cartId) {
    return;
  }

  loading.value = true;
  error.value = '';
  cart.value = null;
  events.value = [];

  try {
    const data = await apiGet(`recovery/carts/${props.cartId}/timeline`);
    cart.value = data.cart || null;
    events.value = data.events || [];
  } catch (e) {
    error.value = 'Não foi possível carregar o histórico do carrinho.';
  } finally {
    loading.value = false;
  }
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      load();
    }
  },
);
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[99999] flex justify-end">
      <div class="absolute inset-0 bg-slate-900/40" @click="emit('close')"></div>

      <aside class="relative flex h-full w-full max-w-md flex-col bg-white shadow-2xl">
        <header class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
          <div>
            <h2 class="m-0 text-[15px] font-semibold text-ink">
              Carrinho <span v-if="cartId" class="text-slate-400">#{{ cartId }}</span>
            </h2>
            <p class="m-0 text-[13px] text-slate-500">Histórico completo do carrinho</p>
          </div>
          <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100"
            aria-label="Fechar"
            @click="emit('close')"
          >
            <BoxIcon name="x" class="h-5 w-5" />
          </button>
        </header>

        <div class="flex-1 overflow-y-auto px-5 py-4">
          <div v-if="loading" class="space-y-3">
            <div class="h-4 w-2/3 animate-pulse rounded bg-slate-100"></div>
            <div class="h-24 animate-pulse rounded bg-slate-100"></div>
            <div class="h-40 animate-pulse rounded bg-slate-100"></div>
          </div>

          <div v-else-if="error" class="rounded-[8px] bg-danger/10 px-4 py-3 text-[13px] text-danger" role="alert">{{ error }}</div>

          <template v-else-if="cart">
            <!-- Summary -->
            <section class="mb-5 rounded-xl border border-slate-200 p-4">
              <div class="mb-3 flex items-center justify-between gap-2">
                <span class="text-[13px] font-semibold text-ink">{{ cart.contact.name || 'Visitante' }}</span>
                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ cart.status_label }}</span>
              </div>

              <dl class="grid gap-1.5 text-[13px]">
                <div v-if="cart.contact.phone" class="flex justify-between gap-3">
                  <dt class="text-slate-400">Telefone</dt><dd class="m-0 text-slate-600">{{ cart.contact.phone }}</dd>
                </div>
                <div v-if="cart.contact.email" class="flex justify-between gap-3">
                  <dt class="text-slate-400">E-mail</dt><dd class="m-0 truncate text-slate-600">{{ cart.contact.email }}</dd>
                </div>
                <div v-if="cart.location" class="flex justify-between gap-3">
                  <dt class="text-slate-400">Localização</dt><dd class="m-0 text-slate-600">{{ cart.location }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                  <dt class="text-slate-400">Valor</dt><dd class="m-0 text-slate-700" v-html="cart.total_formatted"></dd>
                </div>
                <div v-if="cart.coupon_code" class="flex justify-between gap-3">
                  <dt class="text-slate-400">Cupom</dt><dd class="m-0 font-mono text-slate-700">{{ cart.coupon_code }}</dd>
                </div>
              </dl>

              <ul v-if="cart.products.length" class="mt-3 flex list-none flex-col gap-1 border-t border-slate-100 p-0 pt-3 text-[13px] text-slate-600">
                <li v-for="(p, i) in cart.products" :key="i">{{ p.name }} <span class="text-slate-400">× {{ p.quantity }}</span></li>
              </ul>

              <a
                v-if="cart.order && cart.order.edit_url"
                :href="cart.order.edit_url"
                target="_blank"
                rel="noopener"
                class="mt-3 inline-flex items-center gap-1.5 text-[13px] font-semibold text-primary hover:underline"
              >
                Ver pedido #{{ cart.order.number }} ({{ cart.order.status_label }})
                <BoxIcon name="link-external" class="h-3.5 w-3.5" />
              </a>
            </section>

            <!-- Timeline -->
            <h3 class="mb-3 text-[13px] font-semibold uppercase tracking-wide text-slate-400">Linha do tempo</h3>

            <ol v-if="events.length" class="relative m-0 list-none border-l border-slate-200 p-0 pl-6">
              <li v-for="(ev, i) in events" :key="i" class="relative mb-5 last:mb-0">
                <span
                  class="absolute -left-[34px] inline-flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white"
                  :class="iconClass(ev.type)"
                >
                  <BoxIcon :name="iconFor(ev.type)" class="h-3.5 w-3.5" />
                </span>
                <p class="m-0 text-[13px] font-medium text-ink">{{ ev.label }}</p>
                <p v-if="ev.description" class="m-0 mt-0.5 text-[12px] text-slate-500">{{ ev.description }}</p>
                <p v-if="ev.date" class="m-0 mt-0.5 text-[11px] text-slate-400">{{ ev.date }}</p>
              </li>
            </ol>

            <p v-else class="text-[13px] text-slate-400">Nenhum evento registrado.</p>
          </template>
        </div>
      </aside>
    </div>
  </Teleport>
</template>
