import OrderSummary from '../OrderSummary.jsx';

/**
 * Inline order summary block, placeable in any step by the builder.
 *
 * @param {{config:object}} props Block config.
 */
export default function SummaryBlock({ config = {} }) {
  return (
    <div className="rounded-2xl border border-slate-200 bg-white p-5">
      <OrderSummary hideCoupon={!!config.hide_coupon} title={config.title || undefined} />
    </div>
  );
}
