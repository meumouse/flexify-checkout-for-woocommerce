import config, { t } from '../config.js';
import { useCheckout } from '../context/CheckoutContext.jsx';
import { isFieldVisible } from '../lib/conditions.js';
import { fieldById, itemsForStep } from '../lib/layout.js';
import blockRegistry from '../lib/blockRegistry.js';
import { couponBeforePayment } from '../lib/coupon.js';
import FieldRenderer from './FieldRenderer.jsx';
import AddressSearch from './AddressSearch.jsx';
import ShippingRates from './ShippingRates.jsx';
import PaymentMethods from './PaymentMethods.jsx';
import ContactLogin from './ContactLogin.jsx';
import CouponForm from './CouponForm.jsx';
import StepSummary from './StepSummary.jsx';
import Selectable from './editor/Selectable.jsx';
import { MapPinIcon } from './ui/Icons.jsx';

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
 * @param {{step:object, editor?:boolean, selected?:object|null, onEdit?:(type:string)=>void}} props
 */
export default function StepRenderer({ step, editor = false, selected = null, onEdit = null }) {
  const { billing, extraFields } = useCheckout();
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
      const field = fieldById(item.field_id, { editor });

      // Honor conditional visibility (e.g. CPF/RG for individuals vs CNPJ/IE for
      // companies) in both the storefront and the editor preview, so live rule
      // edits show/hide fields as a shopper would see them. Selection of hidden
      // fields still happens from the builder layers tree.
      if (field && isFieldVisible(field.id, billing, extraFields)) {
        fieldBatch.push({ field, item });
      }

      return;
    }

    // Components can be toggled off in the builder. Hide disabled components on
    // the storefront; keep them visible in the editor so the operator can select
    // and re-enable them.
    if (!editor && item.enabled === false) {
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

      {step.type === 'shipping' && (
        <>
          <StepSummary sections={['contact']} onEdit={onEdit} />
          <h2 className="fc-heading text-xl text-slate-800">{t('shipping_address', 'Endereço de entrega')}</h2>
          {addressSearch && (
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <AddressSearch />
            </div>
          )}
        </>
      )}

      {step.type === 'payment' && (
        <>
          <StepSummary sections={['contact', 'shipping', 'frete']} onEdit={onEdit} />
          {couponBeforePayment() && <CouponForm />}
          <h2 className="fc-heading text-xl text-slate-800">{t('payment_methods', 'Formas de pagamento')}</h2>
          <PaymentMethods />
        </>
      )}

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
