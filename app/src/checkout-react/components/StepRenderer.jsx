import config from '../config.js';
import { fieldById, itemsForStep } from '../lib/layout.js';
import blockRegistry from '../lib/blockRegistry.js';
import FieldRenderer from './FieldRenderer.jsx';
import AddressSearch from './AddressSearch.jsx';
import ShippingRates from './ShippingRates.jsx';
import PaymentMethods from './PaymentMethods.jsx';
import ContactLogin from './ContactLogin.jsx';

/**
 * Render a builder-defined step: its semantic chrome (login / address search /
 * shipping rates / payment methods, by step type) plus its ordered items.
 *
 * Consecutive field items are grouped into a two-column grid; component items
 * render full width, preserving the operator's ordering.
 *
 * @param {{step:object}} props Layout step.
 */
export default function StepRenderer({ step }) {
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
        fieldBatch.push(field);
      }

      return;
    }

    flush();
    blocks.push({ kind: 'component', item });
  });

  flush();

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
              {block.fields.map((field) => (
                <FieldRenderer key={field.id} field={field} />
              ))}
            </div>
          );
        }

        const Component = blockRegistry[block.item.component];

        if (!Component) {
          return null;
        }

        return (
          <Component
            key={block.item.id}
            config={block.item.config || {}}
            product={block.item.product || null}
          />
        );
      })}

      {step.type === 'shipping' && <ShippingRates />}
    </div>
  );
}
