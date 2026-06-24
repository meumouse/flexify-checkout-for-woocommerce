import { useEffect, useState } from 'react';
import config, { t } from '../config.js';

/**
 * Animation config localized by Core\Assets (flexify_react_checkout.purchase_animation).
 * Mirrors the legacy `#flexify_checkout_purchase_animation` overlay, but rendered
 * and driven by React instead of the legacy jQuery engine.
 */
const anim = config.purchase_animation || {};
const items = (anim.items || []).filter((item) => item && (item.file || item.text));

/**
 * Full-screen overlay shown while the order is being placed. Cycles through the
 * configured Lordicon steps and fills a progress bar up to 95% (the redirect
 * completes the flow). Renders nothing when the feature is off or unconfigured.
 *
 * @param {{ active: boolean }} props Whether the order submission is in flight.
 */
export default function PurchaseAnimation({ active }) {
  const [step, setStep] = useState(0);
  const [progress, setProgress] = useState(0);

  useEffect(() => {
    if (!active || items.length === 0) {
      setStep(0);
      setProgress(0);

      return undefined;
    }

    const stepId = setInterval(() => {
      setStep((s) => (s + 1) % items.length);
    }, 2000);

    const progressId = setInterval(() => {
      setProgress((p) => (p >= 95 ? 95 : p + 1));
    }, 120);

    return () => {
      clearInterval(stepId);
      clearInterval(progressId);
    };
  }, [active]);

  if (!anim.enabled || items.length === 0) {
    return null;
  }

  return (
    <div className={`fc-purchase-animation${active ? ' is-active' : ''}`} aria-hidden={!active}>
      <div className="fc-purchase-animation__content">
        <div className="fc-purchase-animation__items">
          {items.map((item, i) => (
            <div
              key={i}
              className={`fc-purchase-animation__item${i === step ? ' is-active' : ''}`}
            >
              {item.file ? (
                <lord-icon
                  className="fc-purchase-animation__icon"
                  src={item.file}
                  trigger="loop"
                  delay="1000"
                  stroke="regular"
                  state="hover"
                  colors="primary:#212529,secondary:#212529"
                />
              ) : null}
              {item.text ? <h5 className="fc-purchase-animation__text">{item.text}</h5> : null}
            </div>
          ))}
        </div>

        <div className="fc-purchase-animation__progress">
          <div className="fc-purchase-animation__progress-track">
            <div
              className="fc-purchase-animation__progress-bar"
              style={{ width: `${progress}%` }}
            />
          </div>
          <span className="fc-purchase-animation__hint">
            {anim.wait_text || t('please_wait', 'Aguarde alguns instantes')}
          </span>
        </div>
      </div>
    </div>
  );
}
