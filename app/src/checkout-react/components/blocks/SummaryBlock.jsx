import OrderSummary from '../OrderSummary.jsx';

/**
 * Inline order summary block, placeable in any step by the builder.
 *
 * @param {{config:object}} props Block config.
 */
export default function SummaryBlock({ config = {} }) {
  return <OrderSummary hideCoupon={!!config.hide_coupon} title={config.title || undefined} />;
}
