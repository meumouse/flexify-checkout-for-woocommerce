/**
 * Presentational card shared by the upsell / cross-sell / downsell offer blocks.
 *
 * Behavior (cart mutations, decline handling) lives in the type-specific blocks;
 * this component is purely visual so the three offer types read as one system.
 *
 * @param {{
 *   product:object, headline?:string, description?:string, badge?:string,
 *   accent?:string, ctaLabel?:string, inCart?:boolean, pending?:boolean,
 *   onAccept?:Function, onDecline?:Function, declineLabel?:string
 * }} props
 */
export default function OfferCard({
  product = null,
  headline = '',
  description = '',
  badge = '',
  accent = '#4f46e5',
  ctaLabel = 'Adicionar',
  inCart = false,
  pending = false,
  onAccept = null,
  onDecline = null,
  declineLabel = 'Não, obrigado',
}) {
  return (
    <div
      className="rounded-xl border-2 p-4 transition"
      style={{ borderColor: inCart ? accent : '#e2e8f0', backgroundColor: inCart ? `${accent}10` : 'transparent' }}
    >
      <div className="flex items-start gap-3">
        {product && product.image && (
          <img src={product.image} alt="" className="h-14 w-14 shrink-0 rounded-lg object-cover" />
        )}

        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2">
            <span className="text-sm font-semibold text-slate-800">
              {headline || (product && product.name) || 'Oferta especial'}
            </span>
            {badge && (
              <span
                className="rounded-full px-2 py-0.5 text-[11px] font-semibold text-white"
                style={{ backgroundColor: accent }}
              >
                {badge}
              </span>
            )}
          </div>

          {description && <span className="mt-0.5 block text-xs text-slate-500">{description}</span>}

          {product && product.price_html && (
            <span
              className="mt-1 block text-sm font-medium text-slate-700"
              dangerouslySetInnerHTML={{ __html: product.price_html }}
            />
          )}
        </div>
      </div>

      <div className="mt-3 flex items-center gap-2">
        <button
          type="button"
          className="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white transition disabled:opacity-60"
          style={{ backgroundColor: accent }}
          disabled={pending || inCart}
          onClick={onAccept}
        >
          {inCart ? 'Adicionado' : ctaLabel}
        </button>

        {onDecline && !inCart && (
          <button
            type="button"
            className="inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-medium text-slate-500 transition hover:text-slate-700"
            onClick={onDecline}
          >
            {declineLabel}
          </button>
        )}
      </div>
    </div>
  );
}
