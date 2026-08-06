<script setup>
/**
 * Full-screen editor for a single checkout offer (order bump / upsell /
 * cross-sell / downsell). Mirrors the ConditionBuilder shell: a teleported
 * overlay with a sticky header and a scrollable body. The trigger rules are
 * edited through the shared <TriggerGroupsEditor>.
 *
 * @since 6.0.0
 */
import { computed, reactive, watch } from 'vue';
import { useSettingsStore } from '../../stores/useSettingsStore';
import BaseButton from '../../components/buttons/BaseButton.vue';
import BaseSelect from '../../components/fields/BaseSelect.vue';
import SearchMultiSelect from '../../components/fields/SearchMultiSelect.vue';
import TriggerGroupsEditor from './TriggerGroupsEditor.vue';
import { ID_SUBJECTS, STATIC_SUBJECTS, NO_VALUE_OPERATORS, newGroup } from '../../components/settings/builder/conditionsSchema';

const props = defineProps({
  open: { type: Boolean, default: false },
  offer: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const store = useSettingsStore();

const OFFER_TYPES = [
  { value: 'order_bump', label: 'Order bump', hint: 'Caixa de seleção que adiciona um produto ao pedido.' },
  { value: 'upsell', label: 'Upsell', hint: 'Oferece um produto de maior valor ou upgrade.' },
  { value: 'cross_sell', label: 'Cross-sell', hint: 'Sugere produtos complementares.' },
  { value: 'downsell', label: 'Downsell', hint: 'Alternativa mais barata quando outra oferta é recusada.' },
];

const DISCOUNT_MODES = computed(() => [
  { value: 'none', label: 'Sem desconto' },
  { value: 'percent', label: 'Percentual (%)' },
  { value: 'fixed', label: `Valor fixo (${store.runtime?.currency_symbol || 'R$'})` },
]);

const inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-100';

const form = reactive({
  id: null,
  type: 'order_bump',
  enabled: true,
  name: '',
  priority: 0,
  products: [],
  quantity: 1,
  discount: { mode: 'none', value: 0, label: '' },
  headline: '',
  description: '',
  image_id: 0,
  image_url: '',
  discount_label: '',
  highlight_color: '',
  cta_label: '',
  downsell_of: '',
  trigger: { match: 'all', groups: [newGroup()] },
});

const editingId = computed(() => form.id);
const saving = computed(() => store.savingOffer);
const isCrossSell = computed(() => form.type === 'cross_sell');

// Other offers a downsell can attach to (bumps / upsells, excluding itself).
const parentOfferOptions = computed(() =>
  (store.offers || [])
    .filter((o) => o.id !== form.id && ['order_bump', 'upsell'].includes(o.type))
    .map((o) => ({ value: o.id, label: o.name || o.headline || o.product?.name || o.id })),
);

function loadCondition(condition) {
  const subject = condition.subject || 'field';

  return {
    subject,
    field: condition.field || '',
    operator: condition.operator || 'is',
    value: condition.value || '',
    selected: ID_SUBJECTS.includes(subject) ? (condition.items_labeled || []).map((item) => ({ ...item })) : [],
    values: STATIC_SUBJECTS.includes(subject) ? (condition.items || []).map((item) => String(item)) : [],
  };
}

function reset() {
  form.id = null;
  form.type = 'order_bump';
  form.enabled = true;
  form.name = '';
  form.priority = 0;
  form.products = [];
  form.quantity = 1;
  form.discount = { mode: 'none', value: 0, label: '' };
  form.headline = '';
  form.description = '';
  form.image_id = 0;
  form.image_url = '';
  form.discount_label = '';
  form.highlight_color = '';
  form.cta_label = '';
  form.downsell_of = '';
  form.trigger = { match: 'all', groups: [newGroup()] };
}

function loadOffer(offer) {
  reset();
  form.id = offer.id || null;
  form.type = offer.type || 'order_bump';
  form.enabled = offer.enabled !== false;
  form.name = offer.name || '';
  form.priority = Number(offer.priority) || 0;
  form.quantity = Number(offer.quantity) || 1;
  form.discount = {
    mode: offer.discount?.mode || 'none',
    value: offer.discount?.value ?? 0,
    label: offer.discount?.label || '',
  };
  form.headline = offer.headline || '';
  form.description = offer.description || '';
  form.image_id = Number(offer.image_id) || 0;
  form.image_url = offer.product?.image || '';
  form.discount_label = offer.discount_label || '';
  form.highlight_color = offer.highlight_color || '';
  form.cta_label = offer.cta_label || '';
  form.downsell_of = offer.downsell_of || '';

  if (offer.product_id && offer.product) {
    form.products = [{ id: Number(offer.product_id), label: offer.product.name || `#${offer.product_id}` }];
  }

  const groups = Array.isArray(offer.trigger?.groups) ? offer.trigger.groups : [];

  form.trigger = {
    match: offer.trigger?.match === 'any' ? 'any' : 'all',
    groups: groups.length
      ? groups.map((group) => ({
          match: group.match === 'any' ? 'any' : 'all',
          conditions: (group.conditions || []).map(loadCondition),
        }))
      : [newGroup()],
  };
}

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      return;
    }

    if (props.offer) {
      loadOffer(props.offer);
    } else {
      reset();
    }
  },
  { immediate: true },
);

function openMedia() {
  const wp = window.wp;

  if (!wp || !wp.media) {
    return;
  }

  const frame = wp.media({ title: 'Selecionar imagem', button: { text: 'Usar imagem' }, multiple: false });

  frame.on('select', () => {
    const attachment = frame.state().get('selection').first().toJSON();

    form.image_id = attachment.id;
    form.image_url = attachment.sizes?.thumbnail?.url || attachment.url || '';
  });

  frame.open();
}

function clearImage() {
  form.image_id = 0;
  form.image_url = '';
}

const isValid = computed(() => form.products.length > 0);

function buildConditionPayload(condition) {
  const payload = { subject: condition.subject, operator: condition.operator, value: '', field: '', items: [] };

  if (condition.subject === 'field') {
    payload.field = condition.field;
    payload.value = NO_VALUE_OPERATORS.includes(condition.operator) ? '' : condition.value;
  } else if (condition.subject === 'cart_qty' || condition.subject === 'cart_total') {
    payload.value = condition.value;
  } else if (ID_SUBJECTS.includes(condition.subject)) {
    payload.items = condition.selected.map((item) => item.id);
  } else {
    payload.items = condition.values;
  }

  return payload;
}

function hasConditions() {
  return form.trigger.groups.some((group) => (group.conditions || []).length > 0);
}

function buildPayload() {
  return {
    id: form.id || undefined,
    type: form.type,
    enabled: form.enabled,
    name: form.name,
    priority: Number(form.priority) || 0,
    product_id: form.products[0]?.id || 0,
    quantity: Number(form.quantity) || 1,
    discount: {
      mode: form.discount.mode,
      value: Number(form.discount.value) || 0,
      label: form.discount.label,
    },
    headline: form.headline,
    description: form.description,
    image_id: form.image_id,
    discount_label: form.discount_label,
    highlight_color: form.highlight_color,
    cta_label: form.cta_label,
    downsell_of: form.type === 'downsell' ? form.downsell_of : '',
    trigger: hasConditions()
      ? {
          match: form.trigger.match,
          groups: form.trigger.groups
            .filter((group) => (group.conditions || []).length > 0)
            .map((group) => ({
              match: group.match,
              conditions: group.conditions.map(buildConditionPayload),
            })),
        }
      : { match: 'all', groups: [] },
  };
}

async function submit() {
  if (!isValid.value || saving.value) {
    return;
  }

  const response = await store.saveOffer(buildPayload());

  if (response?.status === 'success') {
    emit('saved');
    emit('close');
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[99999] flex flex-col bg-slate-50">
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
            <h2 class="m-0 text-[15px] font-semibold text-ink">{{ editingId ? 'Editar oferta' : 'Nova oferta' }}</h2>
            <p class="m-0 text-[13px] text-slate-500">Configure o produto, o desconto e quando a oferta aparece.</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <BaseButton variant="secondary" size="sm" @click="emit('close')">Cancelar</BaseButton>
          <BaseButton size="sm" :loading="saving" :disabled="!isValid" @click="submit">
            {{ editingId ? 'Salvar alterações' : 'Criar oferta' }}
          </BaseButton>
        </div>
      </header>

      <div class="flex-1 overflow-y-auto">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-6 py-8">
          <!-- Type -->
          <section class="rounded-[12px] border border-slate-200 bg-white p-5">
            <h3 class="m-0 mb-3 text-sm font-semibold text-ink">Tipo de oferta</h3>
            <div class="grid gap-3 sm:grid-cols-2">
              <button
                v-for="option in OFFER_TYPES"
                :key="option.value"
                type="button"
                class="flex cursor-pointer flex-col gap-1 rounded-[10px] border p-3 text-left transition"
                :class="form.type === option.value ? 'border-primary bg-primary-100/40' : 'border-slate-200 bg-white hover:border-slate-300'"
                @click="form.type = option.value"
              >
                <span class="text-sm font-semibold text-ink">{{ option.label }}</span>
                <span class="text-[12px] text-slate-500">{{ option.hint }}</span>
              </button>
            </div>
          </section>

          <!-- Identification + product -->
          <section class="rounded-[12px] border border-slate-200 bg-white p-5">
            <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Nome interno</label>
                <input v-model="form.name" type="text" :class="inputClass" placeholder="Opcional — ex.: Bump garantia estendida" />
              </div>

              <label class="flex cursor-pointer items-center gap-2 pb-2 text-sm font-medium text-ink">
                <input v-model="form.enabled" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary-100" />
                Ativa
              </label>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-[1fr_120px_120px]">
              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Produto ofertado</label>
                <SearchMultiSelect v-model="form.products" type="products" placeholder="Buscar produto..." />
                <p v-if="!isCrossSell" class="mt-1 text-[12px] text-slate-400">Apenas o primeiro produto selecionado é usado.</p>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Quantidade</label>
                <input v-model="form.quantity" type="number" min="1" step="1" :class="inputClass" />
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Prioridade</label>
                <input v-model="form.priority" type="number" min="0" step="1" :class="inputClass" />
              </div>
            </div>

            <div v-if="form.type === 'downsell'" class="mt-4">
              <label class="mb-1 block text-sm font-medium text-ink">Recusa de qual oferta</label>
              <BaseSelect v-model="form.downsell_of" :options="parentOfferOptions" placeholder="Selecione a oferta pai" />
              <p class="mt-1 text-[12px] text-slate-400">Este downsell aparece quando a oferta escolhida for recusada no checkout.</p>
            </div>
          </section>

          <!-- Discount -->
          <section class="rounded-[12px] border border-slate-200 bg-white p-5">
            <h3 class="m-0 mb-3 text-sm font-semibold text-ink">Desconto</h3>
            <div class="grid gap-4 sm:grid-cols-3">
              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Tipo</label>
                <BaseSelect v-model="form.discount.mode" :options="DISCOUNT_MODES" />
              </div>

              <div v-if="form.discount.mode !== 'none'">
                <label class="mb-1 block text-sm font-medium text-ink">Valor</label>
                <input v-model="form.discount.value" type="number" min="0" step="0.01" :class="inputClass" placeholder="0" />
              </div>

              <div v-if="form.discount.mode !== 'none'">
                <label class="mb-1 block text-sm font-medium text-ink">Rótulo do desconto</label>
                <input v-model="form.discount.label" type="text" :class="inputClass" placeholder="Desconto da oferta" />
              </div>
            </div>
            <p class="mt-2 text-[12px] text-slate-400">O desconto é aplicado como uma taxa negativa no resumo do pedido.</p>
          </section>

          <!-- Copy & visual -->
          <section class="rounded-[12px] border border-slate-200 bg-white p-5">
            <h3 class="m-0 mb-3 text-sm font-semibold text-ink">Aparência</h3>
            <div class="grid gap-4">
              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Título</label>
                <input v-model="form.headline" type="text" :class="inputClass" placeholder="Ex.: Adicione a garantia estendida" />
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Descrição</label>
                <textarea v-model="form.description" rows="2" :class="inputClass" placeholder="Texto curto de apoio (opcional)"></textarea>
              </div>

              <div class="grid gap-4 sm:grid-cols-3">
                <div>
                  <label class="mb-1 block text-sm font-medium text-ink">Selo</label>
                  <input v-model="form.discount_label" type="text" :class="inputClass" placeholder="-20%" />
                </div>

                <div>
                  <label class="mb-1 block text-sm font-medium text-ink">Botão (CTA)</label>
                  <input v-model="form.cta_label" type="text" :class="inputClass" placeholder="Adicionar" />
                </div>

                <div>
                  <label class="mb-1 block text-sm font-medium text-ink">Cor de destaque</label>
                  <input v-model="form.highlight_color" type="text" :class="inputClass" placeholder="#16a34a" />
                </div>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-ink">Imagem</label>
                <div class="flex items-center gap-3">
                  <img v-if="form.image_url" :src="form.image_url" alt="" class="h-14 w-14 rounded-lg border border-slate-200 object-cover" />
                  <BaseButton variant="secondary" size="sm" @click="openMedia">Selecionar imagem</BaseButton>
                  <BaseButton v-if="form.image_id" variant="secondary" size="sm" @click="clearImage">Remover</BaseButton>
                </div>
                <p class="mt-1 text-[12px] text-slate-400">Opcional — sem imagem, usa a do produto.</p>
              </div>
            </div>
          </section>

          <!-- Trigger -->
          <section class="rounded-[12px] border border-slate-200 bg-white p-5">
            <TriggerGroupsEditor :trigger="form.trigger" />
          </section>
        </div>
      </div>
    </div>
  </Teleport>
</template>
