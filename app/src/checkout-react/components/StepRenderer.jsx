import config from '../config.js';
import { fieldById, itemsForStep } from '../lib/layout.js';
import blockRegistry from '../lib/blockRegistry.js';
import FieldRenderer from './FieldRenderer.jsx';
import AddressSearch from './AddressSearch.jsx';
import ShippingRates from './ShippingRates.jsx';
import PaymentMethods from './PaymentMethods.jsx';
import ContactLogin from './ContactLogin.jsx';
import Selectable from './editor/Selectable.jsx';

const COMPONENT_LABELS = {
  order_bump: 'Order bump',
  html: 'Bloco de conteúdo',
  coupon: 'Cupom',
  summary: 'Resumo',
  notes: 'Observações',
  banner: 'Banner',
  reviews: 'Avaliações',
};

/**
 * Render a builder-defined step: its semantic chrome (login / address search /
 * shipping rates / payment methods, by step type) plus its ordered items.
 *
 * Consecutive field items are grouped into a two-column grid; component items
 * render full width, preserving the operator's ordering. In editor mode each
 * field and component is wrapped in a Selectable for live click-to-select.
 *
 * @param {{step:object, editor?:boolean, selected?:object|null}} props
 */
export default function StepRenderer({ step, editor = false, selected = null }) {
  const items = itemsForStep(step);
  const addressSearch = config.flags && config.flags.address_search;

  // Build an ordered list of render groups: field-grid batches + components.
  const blocks = [];
  let fieldBatch = [];

  const flush = () => {
    if (fieldBatch.length) {
      blocks.push({ kind: 'fields', fields: fieldBatch });
      fieldBatch = [];
    }
  };

  items.forEach((item) => {
    if (item.kind === 'field') {
      const field = fieldById(item.field_id);

      if (field) {
        fieldBatch.push({ field, item });
      }

      return;
    }

    flush();
    blocks.push({ kind: 'component', item });
  });

  flush();

  const wrap = (target, label, node) =>
    editor ? (
      <Selectable target={target} selected={selected} label={label}>
        {node}
      </Selectable>
    ) : (
      node
    );

  return (
    <div className="space-y-5">
      {step.type === 'contact' && <ContactLogin />}
      {step.type === 'shipping' && addressSearch && (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <AddressSearch />
        </div>
      )}
      {step.type === 'payment' && <PaymentMethods />}

      {blocks.map((block, index) => {
        if (block.kind === 'fields') {
          return (
            <div key={`f-${index}`} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              {block.fields.map(({ field, item }) => (
                <FieldRenderer
                  key={item.id}
                  field={field}
                  style={item.style}
                  editor={editor}
                  selected={selected}
                  selectTarget={{ scope: 'item', stepId: step.id, itemId: item.id }}
                />
              ))}
            </div>
          );
        }

        const Component = blockRegistry[block.item.component];

        if (!Component) {
          return null;
        }

        const node = (
          <Component config={block.item.config || {}} product={block.item.product || null} editor={editor} />
        );

        return (
          <div key={block.item.id}>
            {wrap(
              { scope: 'item', stepId: step.id, itemId: block.item.id },
              COMPONENT_LABELS[block.item.component] || block.item.component,
              node,
            )}
          </div>
        );
      })}

      {step.type === 'shipping' && <ShippingRates />}
    </div>
  );
}
