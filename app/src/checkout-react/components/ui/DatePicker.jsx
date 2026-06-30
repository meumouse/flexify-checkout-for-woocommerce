import { useEffect, useMemo, useRef, useState } from 'react';
import Select from './Select.jsx';
import { CalendarIcon, ChevronLeftIcon, ChevronRightIcon } from './Icons.jsx';

const MONTHS = [
  'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
  'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
];

const WEEKDAYS = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
const WEEKDAY_NAMES = [
  'domingo', 'segunda-feira', 'terça-feira', 'quarta-feira',
  'quinta-feira', 'sexta-feira', 'sábado',
];

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

const daysInMonth = (year, month) => new Date(year, month + 1, 0).getDate();
const clampDay = (year, month, day) => Math.min(day, daysInMonth(year, month));

/** Shift a `{year,month,day}` focus by a number of days, rolling across months. */
function addDays(focus, delta) {
  const d = new Date(focus.year, focus.month, focus.day + delta);

  return { year: d.getFullYear(), month: d.getMonth(), day: d.getDate() };
}

/** Shift focus by whole months, clamping the day into the target month length. */
function addMonths(focus, delta) {
  const total = focus.month + delta;
  const year = focus.year + Math.floor(total / 12);
  const month = ((total % 12) + 12) % 12;

  return { year, month, day: clampDay(year, month, focus.day) };
}

/**
 * Modern date picker: a read-only field that opens a calendar popover with a
 * month + year dropdown header (reusing the modern Select) and prev/next arrows.
 *
 * Stores the value as ISO `YYYY-MM-DD`; displays it localized as `dd/mm/aaaa`.
 * The grid is fully keyboard navigable (arrows, Home/End, PageUp/Down, Enter,
 * Escape) with a roving tabindex, and focus returns to the trigger on close.
 *
 * Required-presence is enforced by the checkout's JS validation, so the trigger
 * only advertises `aria-required` (no hidden native input).
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
  const gridRef = useRef(null);
  const triggerRef = useRef(null);

  const selected = parseIso(value);
  const today = useMemo(() => {
    const now = new Date();

    return { year: now.getFullYear(), month: now.getMonth(), day: now.getDate() };
  }, []);

  // The date the keyboard cursor sits on; also drives which month is visible.
  const [focus, setFocus] = useState(() => selected || today);
  const view = { year: focus.year, month: focus.month };

  // Each time the popover opens, start the cursor on the selected date (or today).
  useEffect(() => {
    if (open) {
      setFocus(parseIso(value) || today);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open]);

  // Move DOM focus onto the focused day so keyboard navigation visibly tracks it.
  useEffect(() => {
    if (!open || !gridRef.current) {
      return;
    }

    const cell = gridRef.current.querySelector(`[data-day="${focus.day}"]`);

    if (cell) {
      cell.focus();
    }
  }, [open, focus]);

  // Close on outside click.
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
  const monthLength = daysInMonth(view.year, view.month);
  const cells = [...Array(firstWeekday).fill(null), ...Array.from({ length: monthLength }, (_, i) => i + 1)];

  const close = (returnFocus = true) => {
    setOpen(false);

    if (returnFocus && triggerRef.current) {
      triggerRef.current.focus();
    }
  };

  const step = (delta) => setFocus((prev) => addMonths(prev, delta));

  const choose = (day) => {
    onChange(toIso(view.year, view.month, day));
    close();
  };

  const onGridKeyDown = (event) => {
    const weekday = new Date(focus.year, focus.month, focus.day).getDay();
    let next = null;

    switch (event.key) {
      case 'ArrowLeft':
        next = addDays(focus, -1);
        break;
      case 'ArrowRight':
        next = addDays(focus, 1);
        break;
      case 'ArrowUp':
        next = addDays(focus, -7);
        break;
      case 'ArrowDown':
        next = addDays(focus, 7);
        break;
      case 'Home':
        next = addDays(focus, -weekday);
        break;
      case 'End':
        next = addDays(focus, 6 - weekday);
        break;
      case 'PageUp':
        next = addMonths(focus, event.shiftKey ? -12 : -1);
        break;
      case 'PageDown':
        next = addMonths(focus, event.shiftKey ? 12 : 1);
        break;
      case 'Enter':
      case ' ':
        event.preventDefault();
        choose(focus.day);
        return;
      case 'Escape':
        event.preventDefault();
        close();
        return;
      default:
        return;
    }

    event.preventDefault();
    setFocus(next);
  };

  const isSelected = (day) =>
    selected && selected.year === view.year && selected.month === view.month && selected.day === day;
  const isToday = (day) =>
    today.year === view.year && today.month === view.month && today.day === day;
  const dayLabel = (day) =>
    `${day} de ${MONTHS[view.month]} de ${view.year}, ${WEEKDAY_NAMES[new Date(view.year, view.month, day).getDay()]}`;

  return (
    <div className="relative" ref={rootRef}>
      <button
        type="button"
        id={id}
        ref={triggerRef}
        disabled={disabled}
        aria-haspopup="dialog"
        aria-expanded={open}
        aria-required={required}
        className={`flex h-12 w-full items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary-100 disabled:cursor-not-allowed disabled:opacity-60 ${
          open ? 'border-primary ring-2 ring-primary-100' : ''
        } ${className}`.trim()}
        style={style}
        onClick={() => !disabled && setOpen((o) => !o)}
        onKeyDown={(event) => {
          if (disabled) {
            return;
          }

          if (!open && event.key === 'ArrowDown') {
            event.preventDefault();
            setOpen(true);
          } else if (open && event.key === 'Escape') {
            event.preventDefault();
            close();
          }
        }}
      >
        <span className={selected ? 'text-slate-800' : 'text-slate-400'}>
          {selected ? toDisplay(selected) : placeholder}
        </span>
        <CalendarIcon className="h-4 w-4 shrink-0 text-slate-400" />
      </button>

      {open && (
        <div
          role="dialog"
          aria-label="Selecionar data"
          className="absolute z-30 mt-1 w-80 rounded-xl border border-slate-200 bg-white p-3 shadow-lg"
        >
          <div className="mb-3 flex items-center gap-1.5">
            <button
              type="button"
              className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100"
              aria-label="Mês anterior"
              onClick={() => step(-1)}
            >
              <ChevronLeftIcon className="h-4 w-4" />
            </button>

            <div className="flex min-w-0 flex-1 gap-1.5">
              <div className="min-w-0 flex-1">
                <Select
                  size="sm"
                  value={String(view.month)}
                  options={monthOptions}
                  onChange={(v) => setFocus((prev) => ({ ...prev, month: Number(v), day: clampDay(prev.year, Number(v), prev.day) }))}
                  className="!px-2"
                />
              </div>
              <div className="w-[4.75rem] shrink-0">
                <Select
                  size="sm"
                  value={String(view.year)}
                  options={yearOptions}
                  onChange={(v) => setFocus((prev) => ({ ...prev, year: Number(v), day: clampDay(Number(v), prev.month, prev.day) }))}
                  className="!px-2"
                />
              </div>
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

          <div
            ref={gridRef}
            role="grid"
            aria-label={`${MONTHS[view.month]} de ${view.year}`}
            className="grid grid-cols-7 gap-1"
            onKeyDown={onGridKeyDown}
          >
            {cells.map((day, index) => {
              if (day === null) {
                return <div key={`e-${index}`} role="presentation" />;
              }

              const active = isSelected(day);

              return (
                <button
                  key={day}
                  type="button"
                  data-day={day}
                  role="gridcell"
                  aria-label={dayLabel(day)}
                  aria-selected={active}
                  aria-current={isToday(day) ? 'date' : undefined}
                  tabIndex={day === focus.day ? 0 : -1}
                  className={`flex h-9 items-center justify-center rounded-lg text-sm outline-none transition focus-visible:ring-2 focus-visible:ring-primary-100 ${
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
