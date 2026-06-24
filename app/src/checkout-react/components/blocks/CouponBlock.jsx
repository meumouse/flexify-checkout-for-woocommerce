import CouponForm from '../CouponForm.jsx';

/**
 * Standalone coupon field block, placeable in any step by the builder.
 *
 * @param {{config:object}} props Block config.
 */
export default function CouponBlock({ config = {} }) {
  return (
    <div>
      {config.title && <h3 className="mb-1 text-sm font-semibold text-slate-700">{config.title}</h3>}
      <CouponForm />
    </div>
  );
}
