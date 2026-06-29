/**
 * Loading skeleton for the checkout.
 *
 * Mirrors the CheckoutShell layout (stepper + form column + sticky order
 * summary) so the page keeps its shape while the cart loads, instead of
 * collapsing to a bare "Carregando…" line. The same markup is mirrored
 * statically in templates/template-react.php to cover the gap before React
 * mounts; keep the two visually in sync.
 *
 * @since 6.0.0
 */

/** A single shimmering placeholder bar. */
function Bar({ className = '' }) {
  return <div className={`fc-skeleton ${className}`} />;
}

/** Stepper placeholder: three circles joined by dashed connectors. */
function StepperSkeleton() {
  return (
    <div className="mb-10 flex w-full max-w-[520px] items-center">
      {[0, 1, 2].map((i) => (
        <div key={i} className="flex min-w-0 flex-1 items-center">
          <div className="flex shrink-0 items-center gap-3">
            <div className="fc-skeleton h-8 w-8 shrink-0 !rounded-full" />
            <div className="flex flex-col gap-1.5">
              <Bar className="h-2 w-10" />
              <Bar className="h-3 w-16" />
            </div>
          </div>

          {i < 2 && <span className="mx-3 min-w-[1rem] flex-1 border-t border-dashed border-slate-200" />}
        </div>
      ))}
    </div>
  );
}

/** A labelled input placeholder (label line + field box). */
function FieldSkeleton({ className = '' }) {
  return (
    <div className={`flex flex-col gap-2 ${className}`}>
      <Bar className="h-3 w-24" />
      <Bar className="h-12 w-full" />
    </div>
  );
}

/** Order summary card placeholder (line items + totals). */
function SummarySkeleton() {
  return (
    <div className="rounded-2xl border border-slate-200 bg-white p-6">
      <Bar className="mb-6 h-5 w-40" />

      {[0, 1].map((i) => (
        <div key={i} className="mb-4 flex items-center gap-3">
          <Bar className="h-14 w-14 shrink-0 !rounded-xl" />
          <div className="flex flex-1 flex-col gap-2">
            <Bar className="h-3 w-3/4" />
            <Bar className="h-3 w-1/3" />
          </div>
          <Bar className="h-3 w-12" />
        </div>
      ))}

      <div className="mt-6 flex flex-col gap-3 border-t border-slate-100 pt-6">
        <div className="flex justify-between">
          <Bar className="h-3 w-20" />
          <Bar className="h-3 w-14" />
        </div>
        <div className="flex justify-between">
          <Bar className="h-3 w-24" />
          <Bar className="h-3 w-16" />
        </div>
        <div className="mt-1 flex justify-between">
          <Bar className="h-4 w-16" />
          <Bar className="h-4 w-20" />
        </div>
      </div>
    </div>
  );
}

/**
 * Full-page checkout skeleton. Hidden from assistive tech (the live region is
 * the loading text the screen reader announces elsewhere).
 */
export default function CheckoutSkeleton() {
  return (
    <div className="pb-10" aria-hidden="true">
      <div className="mx-auto grid max-w-6xl grid-cols-1 gap-8 px-4 pt-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:px-8">
        <div>
          <StepperSkeleton />

          <div className="flex flex-col gap-5">
            <FieldSkeleton />
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
              <FieldSkeleton />
              <FieldSkeleton />
            </div>
            <FieldSkeleton />
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
              <FieldSkeleton />
              <FieldSkeleton />
              <FieldSkeleton />
            </div>

            <div className="mt-3 flex items-center justify-between border-t border-slate-100 pt-6">
              <Bar className="h-4 w-24" />
              <Bar className="h-11 w-40" />
            </div>
          </div>
        </div>

        <aside className="hidden lg:block">
          <SummarySkeleton />
        </aside>
      </div>
    </div>
  );
}
