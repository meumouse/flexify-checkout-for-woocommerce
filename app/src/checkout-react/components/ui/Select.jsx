import { useEffect, useId, useRef, useState } from 'react';
import { CheckIcon, ChevronDownIcon } from './Icons.jsx';

/**
 * Modern accessible select (custom dropdown).
 *
 * Renders a button trigger + floating listbox instead of a native <select>, so
 * the option list, selected state and focus ring can be styled to match the
 * checkout. Falls back to the placeholder when nothing is chosen. When
 * `searchable` is set, an inline filter input narrows long lists (e.g. the
 * country selector).
 *
 * @param {object}   props
 * @param {string}   [props.id]          Element id (associates an external <label>).
 * @param {string}   props.value         Selected option value.
 * @param {Array}    props.options       [{ value, text|label }].
 * @param {Function} props.onChange      Receives the new value.
 * @param {string}   [props.placeholder] Empty-state text.
 * @param {boolean}  [props.required]
 * @param {boolean}  [props.disabled]
 * @param {boolean}  [props.searchable]  Show an inline filter input.
 * @param {string}   [props.searchPlaceholder] Filter input placeholder.
 * @param {string}   [props.className]   Extra classes for the trigger.
 * @param {object}   [props.style]       Inline style for the trigger.
 * @param {'md'|'sm'} [props.size]       Control height (md = h-12, sm = h-10).
 */
export default function Select({
  id,
  value,
  options = [],
  onChange,
  placeholder = '—',
  required = false,
  disabled = false,
  searchable = false,
  searchPlaceholder = 'Search…',
  className = '',
  style = null,
  size = 'md',
}) {
  const [open, setOpen] = useState(false);
  const [active, setActive] = useState(-1);
  const [query, setQuery] = useState('');
  const rootRef = useRef(null);
  const listRef = useRef(null);
  const searchRef = useRef(null);
  const listId = useId();

  const normalized = options.map((opt) => ({
    value: opt.value,
    label: opt.text || opt.label || opt.value,
  }));
  const selected = normalized.find((opt) => String(opt.value) === String(value));
  const height = size === 'sm' ? 'h-10' : 'h-12';

  // Options actually shown — filtered by the search query when searchable.
  const term = query.trim().toLowerCase();
  const visible = searchable && term
    ? normalized.filter((opt) => opt.label.toLowerCase().includes(term))
    : normalized;

  // Close on outside click / Escape.
  useEffect(() => {
    if (!open) {
      return undefined;
    }

    const onDocClick = (event) => {
      if (rootRef.current && !rootRef.current.contains(event.target)) {
        setOpen(false);
      }
    };

    document.addEventListener('mousedown', onDocClick);

    return () => document.removeEventListener('mousedown', onDocClick);
  }, [open]);

  // Reset the filter and focus the search input when the list opens.
  useEffect(() => {
    if (!open) {
      setQuery('');

      return;
    }

    if (searchable && searchRef.current) {
      searchRef.current.focus();
    }
  }, [open, searchable]);

  // Keep the active option in view while navigating with the keyboard.
  useEffect(() => {
    if (open && active >= 0 && listRef.current) {
      const node = listRef.current.children[active];

      if (node) {
        node.scrollIntoView({ block: 'nearest' });
      }
    }
  }, [open, active]);

  const openList = () => {
    if (disabled) {
      return;
    }

    setActive(normalized.findIndex((opt) => String(opt.value) === String(value)));
    setOpen(true);
  };

  const choose = (optValue) => {
    onChange(optValue);
    setOpen(false);
  };

  const onKeyDown = (event) => {
    if (disabled) {
      return;
    }

    if (!open) {
      if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown') {
        event.preventDefault();
        openList();
      }

      return;
    }

    switch (event.key) {
      case 'Escape':
        event.preventDefault();
        setOpen(false);
        break;
      case 'ArrowDown':
        event.preventDefault();
        setActive((i) => Math.min(visible.length - 1, i + 1));
        break;
      case 'ArrowUp':
        event.preventDefault();
        setActive((i) => Math.max(0, i - 1));
        break;
      case 'Enter':
        event.preventDefault();
        if (active >= 0 && visible[active]) {
          choose(visible[active].value);
        }
        break;
      case ' ':
        // Space selects only when not typing in the filter input.
        if (!searchable) {
          event.preventDefault();
          if (active >= 0 && visible[active]) {
            choose(visible[active].value);
          }
        }
        break;
      default:
        break;
    }
  };

  return (
    <div className="relative" ref={rootRef}>
      <button
        type="button"
        id={id}
        role="combobox"
        aria-haspopup="listbox"
        aria-expanded={open}
        aria-controls={listId}
        aria-required={required}
        disabled={disabled}
        className={`flex ${height} w-full items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary-100 disabled:cursor-not-allowed disabled:opacity-60 ${
          open ? 'border-primary ring-2 ring-primary-100' : ''
        } ${className}`.trim()}
        style={style}
        onClick={() => (open ? setOpen(false) : openList())}
        onKeyDown={onKeyDown}
      >
        <span className={`truncate ${selected ? 'text-slate-800' : 'text-slate-400'}`}>
          {selected ? selected.label : placeholder}
        </span>
        <ChevronDownIcon
          className={`h-4 w-4 shrink-0 text-slate-400 transition-transform ${open ? 'rotate-180' : ''}`}
        />
      </button>

      {open && (
        <div className="absolute z-30 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg">
          {searchable && (
            <div className="border-b border-slate-100 p-2">
              <input
                ref={searchRef}
                type="text"
                value={query}
                placeholder={searchPlaceholder}
                className="h-9 w-full rounded-md border border-slate-200 px-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary-100"
                onChange={(event) => {
                  setQuery(event.target.value);
                  setActive(0);
                }}
                onKeyDown={onKeyDown}
              />
            </div>
          )}

          <ul
            ref={listRef}
            id={listId}
            role="listbox"
            className="max-h-60 overflow-auto p-1"
          >
            {visible.length === 0 && (
              <li className="px-3 py-2 text-sm text-slate-400">—</li>
            )}

            {visible.map((opt, index) => {
              const isSelected = String(opt.value) === String(value);
              const isActive = index === active;

              return (
                <li
                  key={`${opt.value}-${index}`}
                  role="option"
                  aria-selected={isSelected}
                  className={`flex cursor-pointer items-center justify-between gap-2 rounded-md px-3 py-2 text-sm transition ${
                    isActive ? 'bg-primary-50' : ''
                  } ${isSelected ? 'fc-primary-text font-medium' : 'text-slate-700'}`}
                  onMouseEnter={() => setActive(index)}
                  onMouseDown={(event) => {
                    // Prevent the trigger's blur from racing the selection.
                    event.preventDefault();
                    choose(opt.value);
                  }}
                >
                  <span className="truncate">{opt.label}</span>
                  {isSelected && <CheckIcon className="h-4 w-4 shrink-0" />}
                </li>
              );
            })}
          </ul>
        </div>
      )}
    </div>
  );
}
