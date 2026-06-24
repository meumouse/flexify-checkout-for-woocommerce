/**
 * Apply a theme (palette + global controls + font) to the checkout mount at
 * runtime. Used by the live builder preview (postMessage) so color/font/radius
 * edits reflect instantly without a reload. On the real storefront the same
 * values are injected server-side by Core\Assets for the initial paint.
 */

const COLOR_VARS = {
  primary: '--fc-primary',
  primary_hover: '--fc-primary-hover',
  secondary: '--fc-secondary',
  success: '--fc-success',
  warning: '--fc-warning',
  danger: '--fc-danger',
  info: '--fc-info',
};

const GLOBAL_PX_VARS = {
  radius: '--fc-radius',
  font_size: '--fc-font-size',
  field_height: '--fc-field-height',
};

const GLOBAL_COLOR_VARS = {
  bg: '--fc-bg',
  text: '--fc-text',
};

function px(value) {
  const n = Number(value);

  return Number.isFinite(n) ? `${n}px` : String(value);
}

/**
 * Apply a theme object to `#flexify-react-checkout`.
 *
 * @param {{colors?:object, globals?:object, font?:{family?:string, css?:string}}} theme Theme to apply.
 * @returns {void}
 */
export function applyTheme(theme) {
  if (!theme || typeof document === 'undefined') {
    return;
  }

  const root = document.getElementById('flexify-react-checkout');

  if (!root) {
    return;
  }

  const colors = theme.colors || {};

  Object.entries(COLOR_VARS).forEach(([key, cssVar]) => {
    if (colors[key]) {
      root.style.setProperty(cssVar, colors[key]);
    }
  });

  const globals = theme.globals || {};

  Object.entries(GLOBAL_PX_VARS).forEach(([key, cssVar]) => {
    if (globals[key] !== undefined && globals[key] !== '') {
      root.style.setProperty(cssVar, px(globals[key]));
    }
  });

  Object.entries(GLOBAL_COLOR_VARS).forEach(([key, cssVar]) => {
    if (globals[key]) {
      root.style.setProperty(cssVar, globals[key]);
    }
  });

  // Font: inject the @import/@font-face CSS once and set the family.
  const font = theme.font || {};

  if (font.css) {
    let styleEl = document.getElementById('fc-theme-font');

    if (!styleEl) {
      styleEl = document.createElement('style');
      styleEl.id = 'fc-theme-font';
      document.head.appendChild(styleEl);
    }

    styleEl.textContent = font.css;
  }

  if (font.family) {
    root.style.fontFamily = `${font.family}, sans-serif`;
  }
}

export default applyTheme;
