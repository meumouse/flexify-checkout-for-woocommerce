/**
 * Minimal inline SVG icon set for the checkout UI (no external icon dependency).
 * Each icon inherits `currentColor` and accepts a className for sizing.
 */

const base = (props) => ({
  xmlns: 'http://www.w3.org/2000/svg',
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  strokeWidth: 2,
  strokeLinecap: 'round',
  strokeLinejoin: 'round',
  ...props,
});

export function BasketIcon(props) {
  return (
    <svg {...base(props)}>
      <path d="m5 11 4-7" />
      <path d="m19 11-4-7" />
      <path d="M2 11h20" />
      <path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.6-7.4" />
      <path d="m9 15 1 0" />
      <path d="m14 15 1 0" />
    </svg>
  );
}

export function MinusIcon(props) {
  return (
    <svg {...base(props)}>
      <path d="M5 12h14" />
    </svg>
  );
}

export function PlusIcon(props) {
  return (
    <svg {...base(props)}>
      <path d="M12 5v14" />
      <path d="M5 12h14" />
    </svg>
  );
}

export function CloseIcon(props) {
  return (
    <svg {...base(props)}>
      <path d="M18 6 6 18" />
      <path d="m6 6 12 12" />
    </svg>
  );
}

export function CheckIcon(props) {
  return (
    <svg {...base({ strokeWidth: 3, ...props })}>
      <path d="M20 6 9 17l-5-5" />
    </svg>
  );
}

export function ArrowLeftIcon(props) {
  return (
    <svg {...base(props)}>
      <path d="m12 19-7-7 7-7" />
      <path d="M19 12H5" />
    </svg>
  );
}

export function ChevronUpIcon(props) {
  return (
    <svg {...base(props)}>
      <path d="m18 15-6-6-6 6" />
    </svg>
  );
}

export function ChevronDownIcon(props) {
  return (
    <svg {...base(props)}>
      <path d="m6 9 6 6 6-6" />
    </svg>
  );
}

export function ChevronLeftIcon(props) {
  return (
    <svg {...base(props)}>
      <path d="m15 18-6-6 6-6" />
    </svg>
  );
}

export function ChevronRightIcon(props) {
  return (
    <svg {...base(props)}>
      <path d="m9 18 6-6-6-6" />
    </svg>
  );
}

export function CalendarIcon(props) {
  return (
    <svg {...base(props)}>
      <rect width="18" height="18" x="3" y="4" rx="2" />
      <path d="M3 10h18" />
      <path d="M8 2v4" />
      <path d="M16 2v4" />
    </svg>
  );
}

export function MapPinIcon(props) {
  return (
    <svg {...base(props)}>
      <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
      <circle cx="12" cy="10" r="3" />
    </svg>
  );
}

export function PackageIcon(props) {
  return (
    <svg {...base(props)}>
      <path d="m7.5 4.27 9 5.15" />
      <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
      <path d="m3.3 7 8.7 5 8.7-5" />
      <path d="M12 22V12" />
    </svg>
  );
}

export function LockIcon(props) {
  return (
    <svg {...base(props)}>
      <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
      <path d="M7 11V7a5 5 0 0 1 10 0v4" />
    </svg>
  );
}

export function CreditCardIcon(props) {
  return (
    <svg {...base(props)}>
      <rect width="20" height="14" x="2" y="5" rx="2" />
      <path d="M2 10h20" />
    </svg>
  );
}

export function BarcodeIcon(props) {
  return (
    <svg {...base(props)}>
      <path d="M3 5v14" />
      <path d="M8 5v14" />
      <path d="M12 5v14" />
      <path d="M17 5v14" />
      <path d="M21 5v14" />
    </svg>
  );
}

export function AlertTriangleIcon(props) {
  return (
    <svg {...base(props)}>
      <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
      <path d="M12 9v4" />
      <path d="M12 17h.01" />
    </svg>
  );
}

export function InfoIcon(props) {
  return (
    <svg {...base(props)}>
      <circle cx="12" cy="12" r="10" />
      <path d="M12 16v-4" />
      <path d="M12 8h.01" />
    </svg>
  );
}

export function PixIcon(props) {
  // Pix brand glyph (four-diamond), filled with currentColor.
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 24 24"
      fill="currentColor"
      {...props}
    >
      <path d="M12 2.4 16.2 6.6a1.2 1.2 0 0 0 1.7 0L19 5.5l-5.3-5.3a2.4 2.4 0 0 0-3.4 0L5 5.5l1.1 1.1a1.2 1.2 0 0 0 1.7 0L12 2.4Z" transform="translate(0 1.5)" />
      <path d="M12 21.6 7.8 17.4a1.2 1.2 0 0 0-1.7 0L5 18.5l5.3 5.3a2.4 2.4 0 0 0 3.4 0L19 18.5l-1.1-1.1a1.2 1.2 0 0 0-1.7 0L12 21.6Z" transform="translate(0 -1.5)" />
      <path d="M2.4 12 6.6 7.8a1.2 1.2 0 0 0 0-1.7L5.5 5 .2 10.3a2.4 2.4 0 0 0 0 3.4L5.5 19l1.1-1.1a1.2 1.2 0 0 0 0-1.7L2.4 12Z" transform="translate(1.5 0)" />
      <path d="M21.6 12 17.4 7.8a1.2 1.2 0 0 1 0-1.7L18.5 5l5.3 5.3a2.4 2.4 0 0 1 0 3.4L18.5 19l-1.1-1.1a1.2 1.2 0 0 1 0-1.7L21.6 12Z" transform="translate(-1.5 0)" />
      <circle cx="12" cy="12" r="2.6" />
    </svg>
  );
}
