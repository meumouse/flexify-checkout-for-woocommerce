import { emitSelect } from '../../lib/editorBridge.js';

/**
 * Compare two selection targets for equality.
 *
 * @param {object|null} a Target.
 * @param {object|null} b Target.
 * @returns {boolean}
 */
export function sameTarget(a, b) {
  if (!a || !b) {
    return false;
  }

  return a.scope === b.scope && a.stepId === b.stepId && (a.itemId || '') === (b.itemId || '');
}

/**
 * Editor-only wrapper that makes its children selectable in the live builder.
 *
 * Clicking reports the selection to the parent (admin) window; the innermost
 * selectable wins via stopPropagation. The selected one gets a highlight ring
 * and a floating label.
 *
 * @param {{target: object, selected: object|null, label?: string, children: any}} props
 */
export default function Selectable({ target, selected, label, children }) {
  const isSelected = sameTarget(selected, target);

  const onClick = (event) => {
    // Bubble phase + stopPropagation => the innermost selectable wins.
    event.stopPropagation();
    emitSelect(target);
  };

  return (
    <div
      className={`fc-editor-selectable ${isSelected ? 'is-selected' : ''}`}
      data-fc-label={label || ''}
      onClick={onClick}
    >
      {children}
    </div>
  );
}
