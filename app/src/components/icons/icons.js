/**
 * Curated Boxicons registry consumed by <BoxIcon>.
 *
 * Only the icons referenced by the admin are imported here, so the bundle ships
 * a handful of SVGs instead of the full 800+ icon set. Each value is the raw
 * `<svg>` markup straight from the `boxicons` package.
 *
 * To add an icon: import it from
 *   'boxicons/svg/regular/bx-NAME.svg?raw'  (outline — navigation, tabs, most UI)
 *   'boxicons/svg/solid/bxs-NAME.svg?raw'   (filled — badges, status indicators)
 *   'boxicons/svg/logos/bxl-NAME.svg?raw'   (brand marks)
 * then register it under the matching `type/name` key below. <BoxIcon> looks up
 * icons by `${type}/${name}`.
 *
 * @since 6.0.0
 */

// Regular (outline) — navigation, tabs and general UI.
import save from 'boxicons/svg/regular/bx-save.svg?raw';
import x from 'boxicons/svg/regular/bx-x.svg?raw';
import check from 'boxicons/svg/regular/bx-check.svg?raw';
import chevronUp from 'boxicons/svg/regular/bx-chevron-up.svg?raw';
import chevronDown from 'boxicons/svg/regular/bx-chevron-down.svg?raw';
import chevronLeft from 'boxicons/svg/regular/bx-chevron-left.svg?raw';
import plus from 'boxicons/svg/regular/bx-plus.svg?raw';
import trash from 'boxicons/svg/regular/bx-trash.svg?raw';
import reset from 'boxicons/svg/regular/bx-reset.svg?raw';
import hourglass from 'boxicons/svg/regular/bx-hourglass.svg?raw';
import sliderAlt from 'boxicons/svg/regular/bx-slider-alt.svg?raw';
import cart from 'boxicons/svg/regular/bx-cart.svg?raw';
import user from 'boxicons/svg/regular/bx-user.svg?raw';
import like from 'boxicons/svg/regular/bx-like.svg?raw';
import text from 'boxicons/svg/regular/bx-text.svg?raw';
import listPlus from 'boxicons/svg/regular/bx-list-plus.svg?raw';
import filterAlt from 'boxicons/svg/regular/bx-filter-alt.svg?raw';
import palette from 'boxicons/svg/regular/bx-palette.svg?raw';
import infoCircle from 'boxicons/svg/regular/bx-info-circle.svg?raw';
import rocket from 'boxicons/svg/regular/bx-rocket.svg?raw';
import exportIcon from 'boxicons/svg/regular/bx-export.svg?raw';
import importIcon from 'boxicons/svg/regular/bx-import.svg?raw';

// Solid (filled) — badges and status indicators.
import starSolid from 'boxicons/svg/solid/bxs-star.svg?raw';
import checkCircleSolid from 'boxicons/svg/solid/bxs-check-circle.svg?raw';
import errorCircleSolid from 'boxicons/svg/solid/bxs-error-circle.svg?raw';
import infoCircleSolid from 'boxicons/svg/solid/bxs-info-circle.svg?raw';

export default {
  'regular/save': save,
  'regular/x': x,
  'regular/check': check,
  'regular/chevron-up': chevronUp,
  'regular/chevron-down': chevronDown,
  'regular/chevron-left': chevronLeft,
  'regular/plus': plus,
  'regular/trash': trash,
  'regular/reset': reset,
  'regular/hourglass': hourglass,
  'regular/slider-alt': sliderAlt,
  'regular/cart': cart,
  'regular/user': user,
  'regular/like': like,
  'regular/text': text,
  'regular/list-plus': listPlus,
  'regular/filter-alt': filterAlt,
  'regular/palette': palette,
  'regular/info-circle': infoCircle,
  'regular/rocket': rocket,
  'regular/export': exportIcon,
  'regular/import': importIcon,
  'solid/star': starSolid,
  'solid/check-circle': checkCircleSolid,
  'solid/error-circle': errorCircleSolid,
  'solid/info-circle': infoCircleSolid,
};
