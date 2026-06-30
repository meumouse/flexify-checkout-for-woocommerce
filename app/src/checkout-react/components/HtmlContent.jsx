import { useEffect, useRef } from 'react';

/**
 * Render a trusted server-rendered HTML string, re-executing any <script> tags
 * it contains.
 *
 * The WooCommerce thank-you hooks (woocommerce_thankyou, gateway instructions,
 * tracking pixels) are captured server-side as HTML. React's
 * dangerouslySetInnerHTML injects markup but never runs inline scripts, so this
 * component clones each <script> into a fresh element the browser will execute —
 * preserving payment instructions and analytics events.
 *
 * The HTML originates from the site's own PHP (the localized order payload), not
 * from user input, so it is trusted by construction.
 *
 * @param {object} props
 * @param {string} props.html      Trusted HTML string.
 * @param {string} [props.className]
 * @returns {JSX.Element|null}
 */
export default function HtmlContent({ html, className }) {
  const ref = useRef(null);

  useEffect(() => {
    const container = ref.current;

    if (!container) {
      return;
    }

    container.innerHTML = html || '';

    // Re-create each <script> so the browser executes it (innerHTML does not).
    const scripts = container.querySelectorAll('script');

    scripts.forEach((old) => {
      const script = document.createElement('script');

      for (const attr of old.attributes) {
        script.setAttribute(attr.name, attr.value);
      }

      script.text = old.textContent || '';
      old.parentNode.replaceChild(script, old);
    });
  }, [html]);

  if (!html) {
    return null;
  }

  return <div ref={ref} className={className} />;
}
