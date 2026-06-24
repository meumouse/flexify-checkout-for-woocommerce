import { useEffect, useState } from 'react';
import storeApi from '../../api/storeApi.js';

function Stars({ rating = 5 }) {
  const full = Math.round(Number(rating) || 0);

  return (
    <span className="text-amber-400" aria-label={`${full} de 5`}>
      {'★★★★★'.slice(0, full)}
      <span className="text-slate-300">{'★★★★★'.slice(full)}</span>
    </span>
  );
}

function ReviewCard({ author, text, rating, avatar }) {
  return (
    <div className="rounded-xl border border-slate-100 bg-white p-4 shadow-soft">
      <div className="mb-1 flex items-center gap-2">
        {avatar && <img src={avatar} alt="" className="h-8 w-8 rounded-full object-cover" />}
        <span className="text-sm font-semibold text-slate-800">{author || 'Cliente'}</span>
      </div>
      <Stars rating={rating} />
      <div className="mt-1 text-sm text-slate-600" dangerouslySetInnerHTML={{ __html: text || '' }} />
    </div>
  );
}

/**
 * Social proof block: WooCommerce product reviews (Store API) or a manual list
 * of testimonials, rendered as a list or a horizontal carousel.
 *
 * @param {{config:object, editor?:boolean}} props Block config.
 */
export default function ReviewsBlock({ config = {} }) {
  const [productReviews, setProductReviews] = useState(null);
  const isProduct = config.source === 'product';

  useEffect(() => {
    if (!isProduct || !config.product_id) {
      return;
    }

    let active = true;

    storeApi
      .getReviews(config.product_id, config.limit || 5)
      .then((data) => {
        if (active) {
          setProductReviews(Array.isArray(data) ? data : []);
        }
      })
      .catch(() => {
        if (active) {
          setProductReviews([]);
        }
      });

    return () => {
      active = false;
    };
  }, [isProduct, config.product_id, config.limit]);

  let reviews = [];

  if (isProduct) {
    if (productReviews === null) {
      // Loading skeleton keeps the block visible/selectable in the editor.
      return (
        <div className="grid gap-3 sm:grid-cols-2">
          {[0, 1].map((i) => (
            <div key={i} className="h-24 animate-pulse rounded-xl bg-slate-100" />
          ))}
        </div>
      );
    }

    reviews = productReviews.map((r) => ({
      author: r.reviewer,
      text: r.review,
      rating: r.rating,
      avatar: r.reviewer_avatar_urls && (r.reviewer_avatar_urls['48'] || r.reviewer_avatar_urls['96']),
    }));
  } else {
    reviews = (config.items || []).slice(0, config.limit || 20);
  }

  if (!reviews.length) {
    return (
      <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-400">
        Nenhuma avaliação para exibir.
      </div>
    );
  }

  if (config.layout === 'carousel') {
    return (
      <div className="flex snap-x gap-3 overflow-x-auto pb-2">
        {reviews.map((r, i) => (
          <div key={i} className="w-64 shrink-0 snap-start">
            <ReviewCard {...r} />
          </div>
        ))}
      </div>
    );
  }

  return (
    <div className="grid gap-3 sm:grid-cols-2">
      {reviews.map((r, i) => (
        <ReviewCard key={i} {...r} />
      ))}
    </div>
  );
}
