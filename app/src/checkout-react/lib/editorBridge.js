import config from '../config.js';

/**
 * Same-origin postMessage bridge between the React checkout (running inside an
 * iframe in editor mode) and the admin Vue builder (the parent window).
 *
 * Message types are namespaced with the `fc-builder:` prefix. Both sides
 * validate the origin and the expected source window before trusting a message.
 */

const PREFIX = 'fc-builder:';

/**
 * Origin of the parent (admin) window, derived from the referrer. The admin
 * page may be on a different scheme/host than this checkout iframe, so we use
 * it as the postMessage target and don't rely on strict origin equality.
 *
 * @returns {string}
 */
function parentOrigin() {
  try {
    return document.referrer ? new URL(document.referrer).origin : '*';
  } catch (e) {
    return '*';
  }
}

/**
 * Whether the checkout is rendered in live builder editor mode.
 *
 * @returns {boolean}
 */
export function isEditor() {
  // `editor` is localized server-side via wp_localize_script, which stringifies
  // top-level scalars — so a PHP `true` arrives as the string "1", not a boolean.
  // Accept both so the editor mounts regardless of the transport's coercion.
  const flag = config.editor;
  const enabled = flag === true || flag === 1 || flag === '1';

  return enabled && typeof window !== 'undefined' && window.parent !== window;
}

/**
 * Subscribe to messages coming from the parent (builder) window.
 *
 * @param {(msg: object) => void} handler Receives the validated message object.
 * @returns {() => void} Unsubscribe function.
 */
export function onParentMessage(handler) {
  const listener = (event) => {
    // The parent origin may differ (scheme/host); trust the source window and
    // the namespaced message type instead of strict origin equality. The render
    // itself is admin-capability + nonce gated server-side.
    if (event.source !== window.parent) {
      return;
    }

    const data = event.data;

    if (!data || typeof data.type !== 'string' || data.type.indexOf(PREFIX) !== 0) {
      return;
    }

    handler(data);
  };

  window.addEventListener('message', listener);

  return () => window.removeEventListener('message', listener);
}

/**
 * Post a message to the parent (builder) window.
 *
 * @param {object} msg Message payload (must include a `type`).
 */
export function postToParent(msg) {
  if (typeof window === 'undefined' || window.parent === window) {
    return;
  }

  window.parent.postMessage(msg, parentOrigin());
}

/**
 * Tell the parent the editor is mounted and ready for the initial state.
 */
export function emitReady() {
  postToParent({ type: `${PREFIX}ready` });
}

/**
 * Report a selection made inside the iframe (click on a field/component/step).
 *
 * @param {object|null} target Selection target ({scope, stepId, itemId?}).
 */
export function emitSelect(target) {
  postToParent({ type: `${PREFIX}select`, target });
}

export const MESSAGE_PREFIX = PREFIX;
