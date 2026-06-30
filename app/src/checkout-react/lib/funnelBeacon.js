import { useEffect, useRef } from 'react';

/**
 * Checkout funnel beacon.
 *
 * Reports step progress (contact / shipping / payment) to the always-on
 * analytics endpoint (flexify-checkout/v1/recovery/track-step) so the admin
 * "Funil de checkout" cards have data, independent of whether the external
 * tracking integrations (GA4/Meta/…) are configured. The purchase step is
 * recorded server-side from the order, so it is never sent from here.
 */

const FUNNEL_STEPS = ['contact', 'shipping', 'payment'];

/**
 * Resolve the localized React checkout config.
 *
 * @returns {object}
 */
function readConfig() {
  return (typeof window !== 'undefined' && window.flexify_react_checkout) || {};
}

/**
 * Absolute URL of the funnel beacon endpoint, or '' when unavailable.
 *
 * @returns {string}
 */
function beaconUrl() {
  const base = readConfig().rest_url || '';

  return base ? `${base.replace(/\/$/, '')}/recovery/track-step` : '';
}

/**
 * Stable per-session client id used to de-duplicate step pings server-side.
 *
 * @returns {string}
 */
function clientId() {
  try {
    let cid = sessionStorage.getItem('fcrc_funnel_cid');

    if (!cid) {
      cid = `c${Date.now().toString(36)}${Math.random().toString(36).slice(2, 10)}`;
      sessionStorage.setItem('fcrc_funnel_cid', cid);
    }

    return cid;
  } catch (e) {
    return '';
  }
}

/**
 * Coarse device class derived from the user agent.
 *
 * @returns {string} desktop | mobile | tablet
 */
function deviceType() {
  const ua = (typeof navigator !== 'undefined' && navigator.userAgent) || '';

  if (/iPad|Tablet|PlayBook|Silk|Android(?!.*Mobile)/i.test(ua)) {
    return 'tablet';
  }

  if (/Mobi|Android|iPhone|iPod|IEMobile|BlackBerry|Opera Mini/i.test(ua)) {
    return 'mobile';
  }

  return 'desktop';
}

/**
 * Best-effort traffic source from the UTM parameter or the referrer host.
 *
 * @returns {string}
 */
function trafficSource() {
  try {
    const utm = new URLSearchParams(window.location.search).get('utm_source');

    if (utm) {
      return utm;
    }

    const ref = document.referrer || '';

    if (!ref) {
      return 'direct';
    }

    const host = new URL(ref).hostname.replace(/^www\./, '');
    const here = window.location.hostname.replace(/^www\./, '');

    if (host === here) {
      return 'direct';
    }

    if (/google|bing|yahoo|duckduckgo|ecosia|yandex/i.test(host)) {
      return 'organic';
    }

    return 'referral';
  } catch (e) {
    return '';
  }
}

/**
 * Send a single funnel step ping (sendBeacon, with a fetch fallback).
 *
 * @param {string} step One of contact|shipping|payment.
 * @returns {void}
 */
export function recordFunnelStep(step) {
  if (!FUNNEL_STEPS.includes(step)) {
    return;
  }

  const url = beaconUrl();

  if (!url) {
    return;
  }

  const payload = { step, cid: clientId() };

  if (step === 'contact') {
    payload.device = deviceType();
    payload.source = trafficSource();
  }

  try {
    const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });

    if (navigator.sendBeacon && navigator.sendBeacon(url, blob)) {
      return;
    }
  } catch (e) {
    // fall through to fetch
  }

  try {
    fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      keepalive: true,
      credentials: 'same-origin',
    });
  } catch (e) {
    // best effort — analytics must never break checkout
  }
}

/**
 * Fire the funnel beacon once per step as the active checkout step changes.
 *
 * @param {string} slug Active step slug / type (contact|shipping|payment).
 * @returns {void}
 */
export function useFunnelBeacon(slug) {
  const sent = useRef({});

  useEffect(() => {
    if (!FUNNEL_STEPS.includes(slug) || sent.current[slug]) {
      return;
    }

    sent.current[slug] = true;
    recordFunnelStep(slug);
  }, [slug]);
}
