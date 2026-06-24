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
 * Whether the checkout is rendered in live builder editor mode.
 *
 * @returns {boolean}
 */
export function isEditor() {
  return config.editor === true && typeof window !== 'undefined' && window.parent !== window;
}

/**
 * Subscribe to messages coming from the parent (builder) window.
 *
 * @param {(msg: object) => void} handler Receives the validated message object.
 * @returns {() => void} Unsubscribe function.
 */
export function onParentMessage(handler) {
  const listener = (event) => {
    if (event.origin !== window.location.origin) {
      return;
    }

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

  window.parent.postMessage(msg, window.location.origin);
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
