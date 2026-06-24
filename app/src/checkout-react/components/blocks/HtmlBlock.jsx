/**
 * Rich content block (security badges, banner, divider, raw HTML).
 *
 * The HTML is sanitized server-side with wp_kses_post before it reaches the
 * client, so rendering it here is safe.
 *
 * @param {{config:object}} props Block config.
 */
export default function HtmlBlock({ config = {} }) {
  const align = config.align === 'center' ? 'text-center' : config.align === 'right' ? 'text-right' : 'text-left';

  if (config.variant === 'divider') {
    return <hr className="my-1 border-slate-200" />;
  }

  if (!config.html) {
    return null;
  }

  const variantClass =
    config.variant === 'banner'
      ? 'rounded-xl bg-primary-50 p-4 text-sm text-slate-700'
      : config.variant === 'badges'
        ? 'flex flex-wrap items-center gap-3 text-slate-500'
        : 'text-sm text-slate-700';

  return (
    <div
      className={`${variantClass} ${align}`}
      dangerouslySetInnerHTML={{ __html: config.html }}
    />
  );
}
