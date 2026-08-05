<script setup>
/**
 * Checkout builder layers tree (left panel).
 *
 * Lists steps and their items with native HTML5 drag & drop reordering, lock
 * markers on semantic steps, eye toggles, item counts, condition markers and an
 * inline "add block" palette. All structural changes are emitted as intents;
 * the parent (CheckoutBuilder) owns the draft and applies them.
 *
 * @since 6.0.0
 */
import { ref } from 'vue';

const props = defineProps({
  steps: { type: Array, default: () => [] },
  selected: { type: Object, default: () => ({ stepId: '', itemId: '' }) },
  expanded: { type: Object, default: () => ({}) },
  addMenuStepId: { type: String, default: '' },
  stepMeta: { type: Object, required: true },
  componentMeta: { type: Object, required: true },
  addComponents: { type: Array, default: () => [] },
  availableFields: { type: Array, default: () => [] },
  fieldLabel: { type: Function, required: true },
  fieldRecords: { type: Object, default: () => ({}) },
  ruleTargets: { type: Object, default: () => ({}) },
});

const emit = defineEmits([
  'select', 'toggle-expand', 'toggle-step', 'toggle-item', 'delete-step', 'delete-item',
  'add-step', 'open-add', 'add-component', 'add-field', 'new-field', 'reorder-step', 'reorder-item',
]);

const drag = ref(null);

function isField(item) {
  return item.kind === 'field';
}

function itemIcon(item) {
  return isField(item) ? 'text' : (props.componentMeta[item.component]?.icon || 'layout');
}

function itemLabel(item) {
  return isField(item) ? props.fieldLabel(item.field_id) : (props.componentMeta[item.component]?.label || item.component);
}

function itemKindLabel(item) {
  if (!isField(item)) {
    return 'bloco';
  }

  return props.fieldRecords[item.field_id]?.position || 'full';
}

function isRequired(item) {
  return isField(item) && props.fieldRecords[item.field_id]?.required === 'yes';
}

function hasRule(item) {
  return isField(item) && !!props.ruleTargets[item.field_id];
}

// A field item's on/off state lives on its record; a component item carries its
// own `enabled` flag.
function itemDisabled(item) {
  return isField(item) ? props.fieldRecords[item.field_id]?.enabled === 'no' : item.enabled === false;
}

function stepEnabled(step) {
  return step.enabled !== false;
}

// --- Drag & drop ---

function onStepDragStart(step, event) {
  if (step.type === 'payment') {
    event.preventDefault();

    return;
  }

  drag.value = { type: 'step', stepId: step.id };
  event.dataTransfer.effectAllowed = 'move';
}

function onStepDragOver(event) {
  if (drag.value?.type === 'step') {
    event.preventDefault();
  }
}

function onStepDrop(step, event) {
  const current = drag.value;

  if (!current || current.type !== 'step') {
    return;
  }

  event.preventDefault();

  if (step.id !== current.stepId) {
    emit('reorder-step', { fromId: current.stepId, toId: step.id });
  }

  drag.value = null;
}

function onItemDragStart(step, item, event) {
  event.stopPropagation();
  drag.value = { type: 'item', stepId: step.id, itemId: item.id };
  event.dataTransfer.effectAllowed = 'move';
}

function onItemDragOver(event) {
  if (drag.value?.type === 'item') {
    event.preventDefault();
    event.stopPropagation();
  }
}

function onItemDrop(step, index, event) {
  const current = drag.value;

  if (!current || current.type !== 'item') {
    return;
  }

  event.preventDefault();
  event.stopPropagation();
  emit('reorder-item', { fromStepId: current.stepId, itemId: current.itemId, toStepId: step.id, toIndex: index });
  drag.value = null;
}

function onDragEnd() {
  drag.value = null;
}
</script>

<template>
  <aside class="flex w-72 shrink-0 flex-col overflow-hidden border-r border-slate-200 bg-white">
    <div class="flex items-center justify-between gap-2 px-3.5 py-3">
      <span class="text-[10.5px] font-semibold uppercase tracking-[0.08em] text-slate-400">Camadas</span>
      <button
        type="button"
        title="Adicionar etapa"
        class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11.5px] font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
        @click="emit('add-step')"
      >
        <BoxIcon name="plus" class="h-3.5 w-3.5" />
        Etapa
      </button>
    </div>

    <div class="flex-1 overflow-y-auto px-2 pb-5">
      <div
        v-for="step in steps"
        :key="step.id"
        :draggable="step.type !== 'payment'"
        class="mb-0.5"
        :class="drag && drag.type === 'step' && drag.stepId === step.id ? 'opacity-40' : ''"
        @dragstart="onStepDragStart(step, $event)"
        @dragover="onStepDragOver"
        @drop="onStepDrop(step, $event)"
        @dragend="onDragEnd"
      >
        <!-- Step row -->
        <div
          class="flex items-center gap-1.5 rounded-lg px-1.5 py-1.5 transition"
          :class="selected.stepId === step.id && !selected.itemId
            ? 'bg-primary-50 ring-1 ring-inset ring-primary-200'
            : 'hover:bg-slate-50'"
        >
          <span class="flex cursor-grab text-slate-300" title="Arrastar para reordenar">
            <BoxIcon name="grip" class="h-[15px] w-[15px]" />
          </span>

          <button
            type="button"
            class="flex cursor-pointer border-0 bg-transparent p-0 text-slate-400 hover:text-ink"
            :class="(step.items || []).length ? '' : 'invisible'"
            @click.stop="emit('toggle-expand', step.id)"
          >
            <BoxIcon :name="expanded[step.id] ? 'chevron-down' : 'chevron-right'" class="h-3.5 w-3.5" />
          </button>

          <button
            type="button"
            class="flex min-w-0 flex-1 cursor-pointer items-center gap-2 border-0 bg-transparent text-left"
            @click="emit('select', { stepId: step.id })"
          >
            <BoxIcon
              :name="stepMeta[step.type].icon"
              class="h-3.5 w-3.5 shrink-0"
              :class="stepEnabled(step) ? 'text-primary' : 'text-slate-400'"
            />
            <span
              class="truncate text-[12.5px] font-medium"
              :class="stepEnabled(step) ? 'text-ink' : 'text-slate-400'"
            >
              {{ step.label || stepMeta[step.type].label }}
            </span>
          </button>

          <span
            v-if="step.type !== 'custom'"
            title="Etapa semântica — não pode ser excluída"
            class="flex text-slate-300"
          >
            <BoxIcon name="lock" class="h-3 w-3" />
          </span>

          <span class="font-mono text-[10px] text-slate-400">{{ (step.items || []).length }}</span>

          <button
            type="button"
            title="Ativar/desativar etapa"
            class="flex cursor-pointer rounded border-0 bg-transparent p-0.5 transition hover:bg-slate-100"
            :class="stepEnabled(step) ? 'text-slate-400' : 'text-slate-300'"
            @click.stop="emit('toggle-step', step)"
          >
            <BoxIcon :name="stepEnabled(step) ? 'show' : 'hide'" class="h-3.5 w-3.5" />
          </button>

          <button
            v-if="step.type === 'custom'"
            type="button"
            title="Excluir etapa"
            class="flex cursor-pointer rounded border-0 bg-transparent p-0.5 text-slate-400 transition hover:bg-danger/10 hover:text-danger"
            @click.stop="emit('delete-step', step)"
          >
            <BoxIcon name="trash" class="h-3.5 w-3.5" />
          </button>
        </div>

        <!-- Items -->
        <div
          v-if="expanded[step.id]"
          class="ml-[11px] border-l border-slate-100 py-0.5 pl-3"
          @dragover="onItemDragOver"
          @drop="onItemDrop(step, (step.items || []).length, $event)"
        >
          <div
            v-for="(item, index) in step.items"
            :key="item.id"
            draggable="true"
            class="flex items-center gap-1.5 rounded-lg px-1.5 py-1 transition"
            :class="[
              selected.itemId === item.id ? 'bg-primary-50 ring-1 ring-inset ring-primary-200' : 'hover:bg-slate-50',
              drag && drag.type === 'item' && drag.itemId === item.id ? 'opacity-40' : '',
            ]"
            @dragstart="onItemDragStart(step, item, $event)"
            @dragover="onItemDragOver"
            @drop="onItemDrop(step, index, $event)"
            @dragend="onDragEnd"
            @click="emit('select', { stepId: step.id, itemId: item.id })"
          >
            <BoxIcon
              :name="itemIcon(item)"
              class="h-3.5 w-3.5 shrink-0"
              :class="[
                itemDisabled(item) ? 'text-slate-300' : (isField(item) ? 'text-slate-400' : 'text-primary'),
              ]"
            />
            <span
              class="truncate text-[12px]"
              :class="itemDisabled(item) ? 'text-slate-400' : 'text-ink'"
            >
              {{ itemLabel(item) }}
            </span>

            <span v-if="isRequired(item)" class="text-[12px] leading-none text-primary">*</span>

            <span v-if="hasRule(item)" title="Possui condição" class="flex text-violet-600">
              <BoxIcon name="filter-alt" class="h-2.5 w-2.5" />
            </span>

            <span class="flex-1"></span>

            <span class="font-mono text-[9.5px] uppercase tracking-wide text-slate-300">{{ itemKindLabel(item) }}</span>

            <button
              type="button"
              class="flex cursor-pointer rounded border-0 bg-transparent p-0.5 transition hover:bg-slate-100"
              :class="itemDisabled(item) ? 'text-slate-300' : 'text-slate-400'"
              @click.stop="emit('toggle-item', { step, item })"
            >
              <BoxIcon :name="itemDisabled(item) ? 'hide' : 'show'" class="h-3 w-3" />
            </button>

            <button
              type="button"
              class="flex cursor-pointer rounded border-0 bg-transparent p-0.5 text-slate-400 transition hover:bg-danger/10 hover:text-danger"
              @click.stop="emit('delete-item', { step, item })"
            >
              <BoxIcon name="trash" class="h-3 w-3" />
            </button>
          </div>

          <!-- Add block trigger -->
          <button
            type="button"
            class="mt-0.5 flex w-full cursor-pointer items-center gap-1.5 rounded-lg border-0 bg-transparent px-1.5 py-1.5 text-[11.5px] text-slate-400 transition hover:bg-slate-50 hover:text-primary"
            @click.stop="emit('open-add', step)"
          >
            <BoxIcon name="plus" class="h-3 w-3" />
            Adicionar bloco
          </button>

          <!-- Inline add palette -->
          <div
            v-if="addMenuStepId === step.id"
            class="mb-1 mt-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
          >
            <div class="px-2.5 py-1.5 text-[10px] font-semibold uppercase tracking-[0.07em] text-slate-400">Componentes</div>
            <button
              v-for="component in addComponents"
              :key="component"
              type="button"
              class="flex w-full cursor-pointer items-center gap-2 border-0 bg-transparent px-2.5 py-1.5 text-left text-[12.5px] text-ink transition hover:bg-slate-50"
              @click.stop="emit('add-component', { step, component })"
            >
              <BoxIcon :name="componentMeta[component].icon" class="h-3.5 w-3.5 text-slate-500" />
              {{ componentMeta[component].label }}
            </button>

            <div class="border-t border-slate-100 px-2.5 py-1.5 text-[10px] font-semibold uppercase tracking-[0.07em] text-slate-400">
              Campos disponíveis
            </div>
            <div class="max-h-40 overflow-y-auto pb-1">
              <button
                type="button"
                class="flex w-full cursor-pointer items-center gap-2 border-0 bg-transparent px-2.5 py-1.5 text-left text-[12.5px] text-primary transition hover:bg-slate-50"
                @click.stop="emit('new-field', step)"
              >
                <BoxIcon name="plus" class="h-3.5 w-3.5" />
                Novo campo…
              </button>
              <button
                v-for="field in availableFields"
                :key="field.value"
                type="button"
                class="flex w-full cursor-pointer items-center gap-2 border-0 bg-transparent px-2.5 py-1.5 text-left text-[12.5px] text-ink transition hover:bg-slate-50"
                @click.stop="emit('add-field', { step, fieldId: field.value })"
              >
                <BoxIcon name="text" class="h-3.5 w-3.5 text-slate-500" />
                {{ field.label }}
                <span class="flex-1"></span>
                <span class="font-mono text-[10px] text-slate-400">{{ field.value }}</span>
              </button>
              <p v-if="!availableFields.length" class="px-2.5 py-1.5 text-[11.5px] text-slate-400">
                Todos os campos já foram adicionados.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </aside>
</template>
