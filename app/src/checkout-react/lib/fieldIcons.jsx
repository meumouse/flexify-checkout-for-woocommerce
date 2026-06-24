/**
 * Small curated set of leading field icons for the live builder.
 *
 * Each entry is a React element (inline SVG, 16px, currentColor) keyed by the
 * allowlist enforced server-side in Layout_Store::sanitize_field_style.
 */

const base = { width: 16, height: 16, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 1.8, strokeLinecap: 'round', strokeLinejoin: 'round' };

const ICONS = {
  user: (
    <svg {...base} key="user">
      <circle cx="12" cy="8" r="4" />
      <path d="M4 21c0-4 4-6 8-6s8 2 8 6" />
    </svg>
  ),
  envelope: (
    <svg {...base} key="envelope">
      <rect x="3" y="5" width="18" height="14" rx="2" />
      <path d="m3 7 9 6 9-6" />
    </svg>
  ),
  phone: (
    <svg {...base} key="phone">
      <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L20 13l1 4v3a1 1 0 0 1-1 1A16 16 0 0 1 4 5a1 1 0 0 1 1-1Z" />
    </svg>
  ),
  map: (
    <svg {...base} key="map">
      <path d="M12 21s7-6.5 7-11a7 7 0 1 0-14 0c0 4.5 7 11 7 11Z" />
      <circle cx="12" cy="10" r="2.5" />
    </svg>
  ),
  home: (
    <svg {...base} key="home">
      <path d="M3 11 12 4l9 7" />
      <path d="M5 10v10h14V10" />
    </svg>
  ),
  'id-card': (
    <svg {...base} key="id-card">
      <rect x="3" y="5" width="18" height="14" rx="2" />
      <circle cx="8" cy="11" r="2" />
      <path d="M13 10h5M13 14h5M5 15c.6-1.5 4.4-1.5 5 0" />
    </svg>
  ),
  'credit-card': (
    <svg {...base} key="credit-card">
      <rect x="3" y="5" width="18" height="14" rx="2" />
      <path d="M3 10h18" />
    </svg>
  ),
  calendar: (
    <svg {...base} key="calendar">
      <rect x="3" y="5" width="18" height="16" rx="2" />
      <path d="M3 9h18M8 3v4M16 3v4" />
    </svg>
  ),
  search: (
    <svg {...base} key="search">
      <circle cx="11" cy="11" r="7" />
      <path d="m21 21-4.3-4.3" />
    </svg>
  ),
};

/**
 * Resolve a field icon element by its allowlist key.
 *
 * @param {string} key Icon key.
 * @returns {JSX.Element|null}
 */
export default function fieldIcon(key) {
  return ICONS[key] || null;
}

export const FIELD_ICON_KEYS = Object.keys(ICONS);
