import { useEffect, useRef, useState } from 'react';

/**
 * Read the current `?step` slug from the page URL.
 *
 * @returns {string} The step slug, or '' when absent / unavailable.
 */
export function readStepParam() {
  if (typeof window === 'undefined') {
    return '';
  }

  return new URLSearchParams(window.location.search).get('step') || '';
}

/**
 * Write (or clear) the `?step` slug on the page URL without reloading.
 *
 * @param {string}  slug              Step slug to store; falsy clears the param.
 * @param {{replace?:boolean}} [opts] When `replace`, swap the current history
 *                                    entry instead of pushing a new one.
 * @returns {void}
 */
export function writeStepParam(slug, { replace = false } = {}) {
  if (typeof window === 'undefined' || !window.history) {
    return;
  }

  const url = new URL(window.location.href);

  if (slug) {
    url.searchParams.set('step', slug);
  } else {
    url.searchParams.delete('step');
  }

  const method = replace ? 'replaceState' : 'pushState';
  window.history[method](window.history.state, '', url.toString());
}

/**
 * Drive the active step index from the `?step` URL parameter so a reload or a
 * shared link reopens the checkout on the same step, and the browser back /
 * forward buttons move between steps.
 *
 * The step set isn't known until the cart loads (it gates the shipping step),
 * so restoration is deferred to the first render where `slugs` is non-empty.
 * The slug list is compared by value (its join key) to avoid re-subscribing on
 * every render, since callers rebuild the array each time.
 *
 * @param {Array<string>} slugs Ordered step slugs, one per visible step.
 * @returns {[number, Function]} `[activeIndex, setIndex]`.
 */
export function useStepRouter(slugs) {
  const key = slugs.join('|');
  const [index, setIndex] = useState(0);
  const activeIndex = Math.min(index, Math.max(0, slugs.length - 1));

  // Latest slug list, read inside effects that depend on the stable `key`.
  const slugsRef = useRef(slugs);
  slugsRef.current = slugs;
  // Guards so the first settle adopts the URL (no new history entry) and we
  // never push a redundant entry for the step we're already on.
  const restored = useRef(false);
  const lastSlug = useRef(null);

  // Keep the active step in sync with Back / Forward navigation.
  useEffect(() => {
    const onPop = () => {
      const list = slugsRef.current;
      const found = list.indexOf(readStepParam());
      const target = found >= 0 ? found : 0;
      lastSlug.current = list[target];
      setIndex(target);
    };

    window.addEventListener('popstate', onPop);

    return () => window.removeEventListener('popstate', onPop);
  }, [key]);

  // Restore from / publish to the URL as the active step changes.
  useEffect(() => {
    const list = slugsRef.current;

    if (!list.length) {
      return;
    }

    // First settle (cart loaded): adopt ?step from the URL, normalizing it in
    // place so a reload or shared link lands on the saved step.
    if (!restored.current) {
      restored.current = true;

      const found = list.indexOf(readStepParam());
      const target = found >= 0 ? found : 0;

      lastSlug.current = list[target];
      writeStepParam(list[target], { replace: true });

      if (target !== activeIndex) {
        setIndex(target);
      }

      return;
    }

    const slug = list[activeIndex];

    if (slug && slug !== lastSlug.current) {
      lastSlug.current = slug;
      writeStepParam(slug);
    }
  }, [key, activeIndex]);

  return [activeIndex, setIndex];
}
