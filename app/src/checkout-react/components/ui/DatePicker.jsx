import { useEffect, useMemo, useRef, useState } from 'react';
import Select from './Select.jsx';
import { CalendarIcon, ChevronLeftIcon, ChevronRightIcon } from './Icons.jsx';

const MONTHS = [
  'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
  'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
];

const WEEKDAYS = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];

/**
 * Parse an ISO `YYYY-MM-DD` string into numeric parts.
 *
 * @param {string} iso
 * @returns {{year:number, month:number, day:number}|null} month is 0-indexed.
 */
function parseIso(iso) {
  if (!iso || typeof iso !== 'string') {
    return null;
  }

  const match = iso.match(/^(\d{4})-(\d{2})-(\d{2})$/);

  if (!match) {
    return null;
  }

  return { year: Number(match[1]), month: Number(match[2]) - 1, day: Number(match[3]) };
}

const pad = (n) => String(n).padStart(2, '0');
const toIso = (year, month, day) => `${year}-${pad(month + 1)}-${pad(day)}`;
const toDisplay = (parts) => (parts ? `${pad(parts.day)}/${pad(parts.month + 1)}/${parts.year}` : '');

/**
 * Modern date picker: a read-only field that opens a calendar popover with a
 * month + year dropdown header (reusing the modern Select) and prev/next arrows.
 *
 * Stores the value as ISO `YYYY-MM-DD`; displays it localized as `dd/mm/aaaa`.
 *
 * @param {object}   props
 * @param {string}   [props.id]
 * @param {string}   props.value        ISO `YYYY-MM-DD` (or '').
 * @param {Function} props.onChange     Receives the new ISO value.
 * @param {string}   [props.placeholder]
 * @param {boolean}  [props.required]
 * @param {boolean}  [props.disabled]
 * @param {string}   [props.className]
 * @param {object}   [props.style]
 */
export default function DatePicker({
  id,
  value,
  onChange,
  placeholder = 'dd/mm/aaaa',
  required = false,
  disabled = false,
  className = '',
  style = null,
}) {
  const [open, setOpen] = useState(false);
  const rootRef = useRef(null);

  const selected = parseIso(value);
  const today = useMemo(() => {
    const now = new Date();

    return { year: now.getFullYear(), month: now.getMonth(), day: now.getDate() };
  }, []);

  const [view, setView] = useState(() => ({
    year: selected ? selected.year : today.year,
    month: selected ? selected.month : today.month,
  }));

  // Re-sync the visible month when an external value lands on a different month.
  useEffect(() => {
    if (selected) {
      setView({ year: selected.year, month: selected.month });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [value]);

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

  const yearOptions = useMemo(() => {
    const out = [];

    for (let y = today.year + 10; y >= today.year - 100; y -= 1) {
      out.push({ value: String(y), label: String(y) });
    }

    return out;
  }, [today.year]);

  const monthOptions = MONTHS.map((label, index) => ({ value: String(index), label }));

  const firstWeekday = new Date(view.year, view.month, 1).getDay();
  const daysInMonth = new Date(view.year, view.month + 1, 0).getDate();
  const cells = [...Array(firstWeekday).fill(null), ...Array.from({ length: daysInMonth }, (_, i) => i + 1)];

  const step = (delta) => {
    setView((prev) => {
      const next = prev.month + delta;
      const year = prev.year + Math.floor(next / 12);
      const month = ((next % 12) + 12) % 12;

      return { year, month };
    });
  };

  const choose = (day) => {
    onChange(toIso(view.year, view.month, day));
    setOpen(false);
  };

  const isSelected = (day) =>
    selected && selected.year === view.year && selected.month === view.month && selected.day === day;
  const isToday = (day) =>
    today.year === view.year && today.month === view.month && today.day === day;

  return (
    <div className="relative" ref={rootRef}>
      <button
        type="button"
        id={id}
        disabled={disabled}
        aria-haspopup="dialog"
        aria-expanded={open}
        className={`flex h-12 w-full items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary-100 disabled:cursor-not-allowed disabled:opacity-60 ${
          open ? 'border-primary ring-2 ring-primary-100' : ''
        } ${className}`.trim()}
        style={style}
        onClick={() => !disabled && setOpen((o) => !o)}
      >
        <span className={selected ? 'text-slate-800' : 'text-slate-400'}>
          {selected ? toDisplay(selected) : placeholder}
        </span>
        <CalendarIcon className="h-4 w-4 shrink-0 text-slate-400" />
      </button>

      {required && (
        // Mirror the value into a hidden required input so native form validation works.
        <input type="text" className="sr-only" tabIndex={-1} aria-hidden="true" required value={value || ''} readOnly />
      )}

      {open && (
        <div
          role="dialog"
          className="absolute z-30 mt-1 w-72 rounded-xl border border-slate-200 bg-white p-3 shadow-lg"
        >
          <div className="mb-3 flex items-center gap-2">
            <button
              type="button"
              className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100"
              aria-label="Mês anterior"
              onClick={() => step(-1)}
            >
              <ChevronLeftIcon className="h-4 w-4" />
            </button>

            <div className="flex flex-1 gap-2">
              <Select
                size="sm"
                value={String(view.month)}
                options={monthOptions}
                onChange={(v) => setView((prev) => ({ ...prev, month: Number(v) }))}
                className="!px-2"
              />
              <Select
                size="sm"
                value={String(view.year)}
                options={yearOptions}
                onChange={(v) => setView((prev) => ({ ...prev, year: Number(v) }))}
                className="!w-24 !px-2"
              />
            </div>

            <button
              type="button"
              className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100"
              aria-label="Próximo mês"
              onClick={() => step(1)}
            >
              <ChevronRightIcon className="h-4 w-4" />
            </button>
          </div>

          <div className="mb-1 grid grid-cols-7 gap-1">
            {WEEKDAYS.map((wd, index) => (
              <div key={index} className="flex h-8 items-center justify-center text-xs font-medium text-slate-400">
                {wd}
              </div>
            ))}
          </div>

          <div className="grid grid-cols-7 gap-1">
            {cells.map((day, index) => {
              if (day === null) {
                return <div key={`e-${index}`} />;
              }

              const active = isSelected(day);

              return (
                <button
                  key={day}
                  type="button"
                  className={`flex h-9 items-center justify-center rounded-lg text-sm transition ${
                    active
                      ? 'fc-primary-bg font-semibold text-white'
                      : `text-slate-700 hover:bg-primary-50 ${isToday(day) ? 'fc-primary-text font-semibold' : ''}`
                  }`}
                  onClick={() => choose(day)}
                >
                  {day}
                </button>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}
