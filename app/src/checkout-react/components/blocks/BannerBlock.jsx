import { useEffect, useState } from 'react';

/**
 * Compute the remaining time parts until a target datetime.
 *
 * @param {string} target datetime-local string (YYYY-MM-DDTHH:MM).
 * @returns {{done:boolean, d:number, h:number, m:number, s:number}}
 */
function remaining(target) {
  const end = new Date(target).getTime();
  const diff = end - Date.now();

  if (!target || Number.isNaN(end) || diff <= 0) {
    return { done: true, d: 0, h: 0, m: 0, s: 0 };
  }

  const s = Math.floor(diff / 1000);

  return {
    done: false,
    d: Math.floor(s / 86400),
    h: Math.floor((s % 86400) / 3600),
    m: Math.floor((s % 3600) / 60),
    s: s % 60,
  };
}

function pad(n) {
  return String(n).padStart(2, '0');
}

/**
 * Promotional banner: image or solid background, overlaid title/subtitle/button,
 * and an optional countdown.
 *
 * @param {{config:object}} props Block config.
 */
export default function BannerBlock({ config = {} }) {
  const hasCountdown = !!config.countdown;
  const [time, setTime] = useState(() => remaining(config.countdown));

  useEffect(() => {
    if (!hasCountdown) {
      return undefined;
    }

    setTime(remaining(config.countdown));
    const id = setInterval(() => setTime(remaining(config.countdown)), 1000);

    return () => clearInterval(id);
  }, [config.countdown, hasCountdown]);

  const align =
    config.align === 'left' ? 'items-start text-left' : config.align === 'right' ? 'items-end text-right' : 'items-center text-center';

  const style = {
    backgroundColor: config.bg_color || '#0f172a',
    backgroundImage: config.image ? `url(${config.image})` : undefined,
    backgroundSize: 'cover',
    backgroundPosition: 'center',
  };

  const titleStyle = config.title_color ? { color: config.title_color } : { color: '#ffffff' };

  const showCountdown = hasCountdown && !time.done;

  const content = (
    <div className={`relative flex flex-col justify-center gap-2 rounded-xl px-6 py-7 ${align}`} style={style}>
      {(config.image || config.bg_color) && <span className="pointer-events-none absolute inset-0 rounded-xl bg-black/20" />}

      <div className="relative">
        {config.title && <p className="m-0 text-lg font-bold" style={titleStyle}>{config.title}</p>}
        {config.subtitle && <p className="m-0 mt-1 text-sm" style={{ color: titleStyle.color, opacity: 0.9 }}>{config.subtitle}</p>}

        {showCountdown && (
          <div className="mt-3 inline-flex items-center gap-2 rounded-lg bg-white/15 px-3 py-1.5 text-sm font-semibold tabular-nums" style={{ color: titleStyle.color }}>
            {time.d > 0 && <span>{time.d}d</span>}
            <span>{pad(time.h)}:{pad(time.m)}:{pad(time.s)}</span>
          </div>
        )}

        {config.button_text && (
          <span className="mt-3 inline-block rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900">
            {config.button_text}
          </span>
        )}
      </div>
    </div>
  );

  if (config.link) {
    return (
      <a href={config.link} className="block no-underline" rel="noopener noreferrer">
        {content}
      </a>
    );
  }

  return content;
}
