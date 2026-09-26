<script lang="ts">
  import { onMount, onDestroy } from 'svelte';

  let canvas = $state<HTMLCanvasElement | null>(null);

  interface Particle {
    x: number;
    y: number;
    vx: number;
    vy: number;
    baseRadius: number;
    currentRadius: number;
    alpha: number;
  }

  let particles: Particle[] = [];
  let animFrameId: number | null = null;
  let resizeObserver: ResizeObserver | null = null;
  let isReducedMotion = false;
  let isTabHidden = false;

  let width = 0;
  let height = 0;
  let dpr = 1;

  // Mouse interaction state (desktop only)
  let mouse = {
    x: -9999,
    y: -9999,
    radius: 140,
    active: false
  };

  // Color tokens extracted from CSS variables to adhere to brand guidelines
  let stormGreenColor = '#0F282F';
  let vividCyanRgb = '2, 239, 240';

  function readBrandTokens() {
    if (typeof window === 'undefined') return;
    const styles = getComputedStyle(document.documentElement);
    const storm = styles.getPropertyValue('--storm-green').trim();
    const cyanRgb = styles.getPropertyValue('--vivid-cyan-rgb').trim();

    if (storm) stormGreenColor = storm;
    if (cyanRgb) vividCyanRgb = cyanRgb;
  }

  function getParticleCount(w: number): number {
    if (w < 640) return 38; // Mobile: cheap & lightweight
    if (w < 1024) return 52; // Tablet
    return 68; // Desktop: rich tech-forward look
  }

  function getDistanceThreshold(w: number): number {
    if (w < 640) return 85;
    if (w < 1024) return 105;
    return 125;
  }

  function initParticles(count: number, w: number, h: number) {
    particles = [];
    for (let i = 0; i < count; i++) {
      const baseRadius = 1.4 + Math.random() * 1.1; // 1.4px to 2.5px
      // Gentle drift speed: -0.35 to +0.35 px/frame
      const speed = 0.35;
      const angle = Math.random() * Math.PI * 2;
      particles.push({
        x: Math.random() * w,
        y: Math.random() * h,
        vx: Math.cos(angle) * (0.15 + Math.random() * speed),
        vy: Math.sin(angle) * (0.15 + Math.random() * speed),
        baseRadius,
        currentRadius: baseRadius,
        alpha: 0.45 + Math.random() * 0.4
      });
    }
  }

  function renderFrame(updatePositions = true) {
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    // Clear and fill background with the official Storm Green token
    ctx.fillStyle = stormGreenColor;
    ctx.fillRect(0, 0, width, height);

    const maxDist = getDistanceThreshold(width);
    const maxDistSq = maxDist * maxDist;
    const count = particles.length;

    // Draw connection lines first (neural network aesthetic)
    for (let i = 0; i < count; i++) {
      const p1 = particles[i];

      for (let j = i + 1; j < count; j++) {
        const p2 = particles[j];
        const dx = p1.x - p2.x;
        const dy = p1.y - p2.y;
        const distSq = dx * dx + dy * dy;

        if (distSq < maxDistSq) {
          const dist = Math.sqrt(distSq);
          let lineAlpha = (1 - dist / maxDist) * 0.32;

          // Subtle brightening near cursor on desktop
          if (mouse.active) {
            const midX = (p1.x + p2.x) * 0.5;
            const midY = (p1.y + p2.y) * 0.5;
            const mdx = midX - mouse.x;
            const mdy = midY - mouse.y;
            const mDistSq = mdx * mdx + mdy * mdy;
            if (mDistSq < mouse.radius * mouse.radius) {
              const mDist = Math.sqrt(mDistSq);
              const mFactor = 1 - mDist / mouse.radius;
              lineAlpha = Math.min(0.75, lineAlpha + mFactor * 0.28);
            }
          }

          ctx.beginPath();
          ctx.strokeStyle = `rgba(${vividCyanRgb}, ${lineAlpha})`;
          ctx.lineWidth = 1;
          ctx.moveTo(p1.x, p1.y);
          ctx.lineTo(p2.x, p2.y);
          ctx.stroke();
        }
      }
    }

    // Draw & update particles
    for (let i = 0; i < count; i++) {
      const p = particles[i];

      if (updatePositions) {
        p.x += p.vx;
        p.y += p.vy;

        // Smooth wrap-around edges
        if (p.x < -10) p.x = width + 10;
        else if (p.x > width + 10) p.x = -10;

        if (p.y < -10) p.y = height + 10;
        else if (p.y > height + 10) p.y = -10;
      }

      let currentRadius = p.baseRadius;
      let dotAlpha = p.alpha;

      // Mouse proximity interaction (desktop)
      if (mouse.active) {
        const mdx = p.x - mouse.x;
        const mdy = p.y - mouse.y;
        const mDistSq = mdx * mdx + mdy * mdy;
        if (mDistSq < mouse.radius * mouse.radius) {
          const mDist = Math.sqrt(mDistSq);
          const mFactor = 1 - mDist / mouse.radius;
          currentRadius += mFactor * 1.5;
          dotAlpha = Math.min(1.0, dotAlpha + mFactor * 0.45);
        }
      }

      ctx.beginPath();
      ctx.arc(p.x, p.y, currentRadius, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(${vividCyanRgb}, ${dotAlpha})`;
      ctx.fill();
    }
  }

  function loop() {
    if (isReducedMotion || isTabHidden) return;
    renderFrame(true);
    animFrameId = requestAnimationFrame(loop);
  }

  function startAnimation() {
    if (animFrameId) cancelAnimationFrame(animFrameId);
    if (isReducedMotion) {
      renderFrame(false); // Single static frame when reduced motion is preferred
      return;
    }
    if (!isTabHidden) {
      animFrameId = requestAnimationFrame(loop);
    }
  }

  function stopAnimation() {
    if (animFrameId) {
      cancelAnimationFrame(animFrameId);
      animFrameId = null;
    }
  }

  function setupCanvasDimensions() {
    if (!canvas) return;
    const parent = canvas.parentElement;
    if (!parent) return;

    const rect = parent.getBoundingClientRect();
    width = Math.max(300, Math.floor(rect.width));
    height = Math.max(250, Math.floor(rect.height));
    dpr = Math.min(typeof window !== 'undefined' ? window.devicePixelRatio || 1 : 1, 2);

    canvas.width = Math.floor(width * dpr);
    canvas.height = Math.floor(height * dpr);
    canvas.style.width = `${width}px`;
    canvas.style.height = `${height}px`;

    const ctx = canvas.getContext('2d');
    if (ctx) {
      ctx.resetTransform?.();
      ctx.scale(dpr, dpr);
    }

    readBrandTokens();

    const targetCount = getParticleCount(width);
    if (particles.length === 0 || Math.abs(particles.length - targetCount) > 10) {
      initParticles(targetCount, width, height);
    } else {
      // Re-bound existing particles if canvas resized
      for (const p of particles) {
        if (p.x > width) p.x = Math.random() * width;
        if (p.y > height) p.y = Math.random() * height;
      }
    }

    if (isReducedMotion) {
      renderFrame(false);
    }
  }

  // Mouse event handlers for desktop interactivity
  function handleMouseMove(e: MouseEvent) {
    if (!canvas) return;
    const rect = canvas.getBoundingClientRect();
    mouse.x = e.clientX - rect.left;
    mouse.y = e.clientY - rect.top;
    mouse.active = true;
  }

  function handleMouseLeave() {
    mouse.active = false;
    mouse.x = -9999;
    mouse.y = -9999;
  }

  onMount(() => {
    readBrandTokens();

    // 1. Reduced motion check
    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    isReducedMotion = motionQuery.matches;

    const handleMotionChange = (e: MediaQueryListEvent) => {
      isReducedMotion = e.matches;
      if (isReducedMotion) {
        stopAnimation();
        renderFrame(false);
      } else {
        startAnimation();
      }
    };
    motionQuery.addEventListener('change', handleMotionChange);

    // 2. Visibility change handling (pause RAF when tab is hidden to conserve battery/CPU)
    const handleVisibilityChange = () => {
      isTabHidden = document.visibilityState !== 'visible';
      if (isTabHidden) {
        stopAnimation();
      } else if (!isReducedMotion) {
        startAnimation();
      }
    };
    document.addEventListener('visibilitychange', handleVisibilityChange);

    // 3. Parent container resize observer
    if (canvas && canvas.parentElement) {
      setupCanvasDimensions();

      resizeObserver = new ResizeObserver(() => {
        setupCanvasDimensions();
      });
      resizeObserver.observe(canvas.parentElement);

      // Mouse tracking on hero container
      canvas.parentElement.addEventListener('mousemove', handleMouseMove, { passive: true });
      canvas.parentElement.addEventListener('mouseleave', handleMouseLeave, { passive: true });
    }

    // 4. Start animation loop
    startAnimation();

    return () => {
      stopAnimation();
      motionQuery.removeEventListener('change', handleMotionChange);
      document.removeEventListener('visibilitychange', handleVisibilityChange);
      if (resizeObserver) resizeObserver.disconnect();
      if (canvas && canvas.parentElement) {
        canvas.parentElement.removeEventListener('mousemove', handleMouseMove);
        canvas.parentElement.removeEventListener('mouseleave', handleMouseLeave);
      }
    };
  });
</script>

<canvas
  bind:this={canvas}
  class="hero-canvas"
  aria-hidden="true"
></canvas>

<style>
  .hero-canvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: block;
    pointer-events: none;
    background-color: var(--storm-green, #0F282F);
    z-index: 0;
  }
</style>
