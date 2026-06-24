import OrderBump from '../components/blocks/OrderBump.jsx';
import HtmlBlock from '../components/blocks/HtmlBlock.jsx';
import CouponBlock from '../components/blocks/CouponBlock.jsx';
import SummaryBlock from '../components/blocks/SummaryBlock.jsx';
import NotesBlock from '../components/blocks/NotesBlock.jsx';
import BannerBlock from '../components/blocks/BannerBlock.jsx';
import ReviewsBlock from '../components/blocks/ReviewsBlock.jsx';

/**
 * Map a builder component type to its React component. StepRenderer looks items
 * up here; unknown types render nothing.
 */
const blockRegistry = {
  order_bump: OrderBump,
  html: HtmlBlock,
  coupon: CouponBlock,
  summary: SummaryBlock,
  notes: NotesBlock,
  banner: BannerBlock,
  reviews: ReviewsBlock,
};

export default blockRegistry;
