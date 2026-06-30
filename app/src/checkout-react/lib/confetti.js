/**
 * Lightweight, dependency-free confetti burst for the thank-you page.
 *
 * Fires a couple of cannon bursts from the lower corners plus one from the
 * center, then removes its own canvas once the particles settle. Honors
 * `prefers-reduced-motion` and is a no-op on the server.
 *
 * @param {object}   [options]
 * @param {string[]} [options.colors] Particle colors.
 * @returns {Function} A cleanup function that stops and removes the canvas.
 */
export function fireConfetti({ colors = ['#22c55e'] } = {}) {
  if (typeof window === 'undefined' || typeof document === 'undefined') {
    return () => {};
  }

  const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (reduce) {
    return () => {};
  }

  const palette = Array.isArray(colors) && colors.length ? colors : ['#22c55e'];
  const canvas = document.createElement('canvas');

  canvas.setAttribute('aria-hidden', 'true');
  Object.assign(canvas.style, {
    position: 'fixed',
    inset: '0',
    width: '100%',
    height: '100%',
    pointerEvents: 'none',
    zIndex: '1059',
  });

  document.body.appendChild(canvas);

  const ctx = canvas.getContext('2d');
  const dpr = window.devicePixelRatio || 1;
  const particles = [];
  let running = true;
  let frameId = 0;
  const timers = [];

  function resize() {
    canvas.width = window.innerWidth * dpr;
    canvas.height = window.innerHeight * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }

  resize();
  window.addEventListener('resize', resize);

  function burst(originX) {
    const count = 80;

    for (let i = 0; i < count; i += 1) {
      const angle = Math.PI * (0.5 + (Math.random() - 0.5) * 0.7);
      const speed = 8 + Math.random() * 9;
      const dir = originX < window.innerWidth / 2 ? 1 : -1;

      particles.push({
        x: originX,
        y: window.innerHeight + 10,
        vx: Math.cos(angle) * speed * dir + (Math.random() - 0.5) * 3,
        vy: -Math.sin(angle) * speed - Math.random() * 6,
        size: 5 + Math.random() * 6,
        color: palette[Math.floor(Math.random() * palette.length)],
        rotation: Math.random() * Math.PI,
        spin: (Math.random() - 0.5) * 0.3,
        life: 1,
        decay: 0.006 + Math.random() * 0.006,
      });
    }
  }

  burst(window.innerWidth * 0.2);
  burst(window.innerWidth * 0.8);
  timers.push(setTimeout(() => burst(window.innerWidth * 0.5), 280));

  function cleanup() {
    running = false;
    window.cancelAnimationFrame(frameId);
    window.removeEventListener('resize', resize);
    timers.forEach(clearTimeout);

    if (canvas.parentNode) {
      canvas.parentNode.removeChild(canvas);
    }
  }

  function frame() {
    if (!running) {
      return;
    }

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    let alive = false;

    for (let i = 0; i < particles.length; i += 1) {
      const p = particles[i];

      if (p.life <= 0) {
        continue;
      }

      alive = true;
      p.vy += 0.28;
      p.vx *= 0.99;
      p.x += p.vx;
      p.y += p.vy;
      p.rotation += p.spin;
      p.life -= p.decay;

      ctx.save();
      ctx.globalAlpha = Math.max(0, p.life);
      ctx.translate(p.x, p.y);
      ctx.rotate(p.rotation);
      ctx.fillStyle = p.color;
      ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6);
      ctx.restore();
    }

    if (alive) {
      frameId = window.requestAnimationFrame(frame);
    } else {
      cleanup();
    }
  }

  frameId = window.requestAnimationFrame(frame);

  return cleanup;
}
