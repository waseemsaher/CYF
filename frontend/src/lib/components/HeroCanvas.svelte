<script lang="ts">
  import { onMount } from 'svelte';

  let canvas = $state<HTMLCanvasElement | null>(null);
  let videoEl = $state<HTMLVideoElement | null>(null);

  interface Particle {
    x: number;
    y: number;
    vx: number;
    vy: number;
    baseRadius: number;
    alpha: number;
  }

  let particles: Particle[] = [];
  let animFrameId: number | null = null;
  let resizeObserver: ResizeObserver | null = null;
  let isReducedMotion = false;
  let isTabHidden = false;
  let isPointerFine = false;
  let canLoadVideo = $state(false);

  let width = 0;
  let height = 0;
  let dpr = 1;

  // Cached section bounds to avoid DOM querying in mousemove or renderFrame
  let sectionLeft = 0;
  let sectionTop = 0;

  // Mouse interaction state (desktop pointer only)
  let mouse = {
    x: -9999,
    y: -9999,
    radius: 160,
    active: false
  };

  // Brand tokens extracted from CSS variables
  let brandAccentRgb = '200, 43, 52';
  let secondaryRgb = '213, 203, 193';

  function readBrandTokens() {
    if (typeof window === 'undefined') return;
    const styles = getComputedStyle(document.documentElement);
    const accent = styles.getPropertyValue('--brand-accent-rgb').trim();
    const sec = styles.getPropertyValue('--bg-secondary-rgb').trim();
    if (accent) brandAccentRgb = accent;
    if (sec) secondaryRgb = sec;
  }

  function shouldLoadVideo(): boolean {
    if (typeof window === 'undefined') return false;

    // 1. Reduced motion preference
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return false;
    }

    // 2. Small viewport check (below 768px)
    if (window.innerWidth < 768) {
      return false;
    }

    // 3. Network connection check: saveData or slow effectiveType ('2g' or 'slow-2g')
    const nav = navigator as Navigator & {
      connection?: {
        saveData?: boolean;
        effectiveType?: string;
      };
      mozConnection?: {
        saveData?: boolean;
        effectiveType?: string;
      };
      webkitConnection?: {
        saveData?: boolean;
        effectiveType?: string;
      };
    };
    const conn = nav.connection || nav.mozConnection || nav.webkitConnection;
    if (conn) {
      if (conn.saveData) return false;
      if (conn.effectiveType === '2g' || conn.effectiveType === 'slow-2g') return false;
    }

    return true;
  }

  function getParticleCount(w: number): number {
    if (w < 640) return 28;
    if (w < 1024) return 42;
    return 56;
  }

  function getDistanceThreshold(w: number): number {
    if (w < 640) return 80;
    if (w < 1024) return 100;
    return 120;
  }

  function initParticles(count: number, w: number, h: number) {
    particles = [];
    for (let i = 0; i < count; i++) {
      const baseRadius = 1.2 + Math.random() * 1.0;
      const speed = 0.28;
      const angle = Math.random() * Math.PI * 2;
      particles.push({
        x: Math.random() * w,
        y: Math.random() * h,
        vx: Math.cos(angle) * (0.10 + Math.random() * speed),
        vy: Math.sin(angle) * (0.10 + Math.random() * speed),
        baseRadius,
        alpha: 0.30 + Math.random() * 0.25
      });
    }
  }

  function renderFrame(updatePositions = true) {
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    // Fully transparent clear: video shows through directly
    ctx.clearRect(0, 0, width, height);

    const maxDist = getDistanceThreshold(width);
    const maxDistSq = maxDist * maxDist;
    const count = particles.length;
    const isMouseInteractive = mouse.active && isPointerFine;
    const mouseRadiusSq = mouse.radius * mouse.radius;

    // Draw connection lines
    for (let i = 0; i < count; i++) {
      const p1 = particles[i];

      for (let j = i + 1; j < count; j++) {
        const p2 = particles[j];
        const dx = p1.x - p2.x;
        const dy = p1.y - p2.y;
        const distSq = dx * dx + dy * dy;

        if (distSq < maxDistSq) {
          const dist = Math.sqrt(distSq);
          // Subtle base opacity as texture over video
          let lineAlpha = (1 - dist / maxDist) * 0.16;
          let lineWidth = 1;
          let strokeRgb = secondaryRgb;

          if (isMouseInteractive) {
            const midX = (p1.x + p2.x) * 0.5;
            const midY = (p1.y + p2.y) * 0.5;
            const mdx = midX - mouse.x;
            const mdy = midY - mouse.y;
            const mDistSq = mdx * mdx + mdy * mdy;

            if (mDistSq < mouseRadiusSq) {
              const mDist = Math.sqrt(mDistSq);
              const mFactor = 1 - mDist / mouse.radius;
              lineAlpha = Math.min(0.85, lineAlpha + mFactor * 0.45);
              lineWidth = 1 + mFactor * 0.75;
              strokeRgb = brandAccentRgb;
            }
          }

          ctx.beginPath();
          ctx.strokeStyle = `rgba(${strokeRgb}, ${lineAlpha})`;
          ctx.lineWidth = lineWidth;
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

        if (p.x < -10) p.x = width + 10;
        else if (p.x > width + 10) p.x = -10;

        if (p.y < -10) p.y = height + 10;
        else if (p.y > height + 10) p.y = -10;
      }

      let currentRadius = p.baseRadius;
      let dotAlpha = p.alpha;
      let fillRgb = secondaryRgb;

      if (isMouseInteractive) {
        const mdx = p.x - mouse.x;
        const mdy = p.y - mouse.y;
        const mDistSq = mdx * mdx + mdy * mdy;

        if (mDistSq < mouseRadiusSq) {
          const mDist = Math.sqrt(mDistSq);
          const mFactor = 1 - mDist / mouse.radius;
          currentRadius += mFactor * 1.8;
          dotAlpha = Math.min(1.0, dotAlpha + mFactor * 0.55);
          fillRgb = brandAccentRgb;
        }
      }

      ctx.beginPath();
      ctx.arc(p.x, p.y, currentRadius, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(${fillRgb}, ${dotAlpha})`;
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
      renderFrame(false);
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

  function updateBounds() {
    if (!canvas) return;
    const parent = canvas.parentElement;
    if (!parent) return;

    const rect = parent.getBoundingClientRect();
    width = Math.max(300, Math.floor(rect.width));
    height = Math.max(250, Math.floor(rect.height));
    sectionLeft = rect.left + window.scrollX;
    sectionTop = rect.top + window.scrollY;

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
      for (const p of particles) {
        if (p.x > width) p.x = Math.random() * width;
        if (p.y > height) p.y = Math.random() * height;
      }
    }

    if (isReducedMotion) {
      renderFrame(false);
    }
  }

  function handleMouseMove(e: MouseEvent) {
    if (!isPointerFine) return;
    mouse.x = e.pageX - sectionLeft;
    mouse.y = e.pageY - sectionTop;
    mouse.active = true;
  }

  function handleMouseLeave() {
    mouse.active = false;
    mouse.x = -9999;
    mouse.y = -9999;
  }

  function handleScroll() {
    if (canvas && canvas.parentElement) {
      const rect = canvas.parentElement.getBoundingClientRect();
      sectionLeft = rect.left + window.scrollX;
      sectionTop = rect.top + window.scrollY;
    }
  }

  onMount(() => {
    readBrandTokens();

    // Fine pointer detection (desktop mouse vs touch)
    isPointerFine = window.matchMedia('(pointer: fine)').matches;

    // Check video criteria
    canLoadVideo = shouldLoadVideo();

    // Reduced motion media query
    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    isReducedMotion = motionQuery.matches;

    const handleMotionChange = (e: MediaQueryListEvent) => {
      isReducedMotion = e.matches;
      canLoadVideo = shouldLoadVideo();
      if (isReducedMotion) {
        stopAnimation();
        if (videoEl) videoEl.pause();
        renderFrame(false);
      } else {
        startAnimation();
        if (videoEl && canLoadVideo) {
          videoEl.play().catch(() => {});
        }
      }
    };
    motionQuery.addEventListener('change', handleMotionChange);

    // Tab visibility handling
    const handleVisibilityChange = () => {
      isTabHidden = document.visibilityState !== 'visible';
      if (isTabHidden) {
        stopAnimation();
        if (videoEl) videoEl.pause();
      } else {
        if (!isReducedMotion) {
          startAnimation();
        }
        if (videoEl && canLoadVideo && !isReducedMotion) {
          videoEl.play().catch(() => {});
        }
      }
    };
    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('scroll', handleScroll, { passive: true });

    // Setup dimensions and observers
    if (canvas && canvas.parentElement) {
      updateBounds();

      resizeObserver = new ResizeObserver(() => {
        canLoadVideo = shouldLoadVideo();
        updateBounds();
      });
      resizeObserver.observe(canvas.parentElement);

      canvas.parentElement.addEventListener('mousemove', handleMouseMove, { passive: true });
      canvas.parentElement.addEventListener('mouseleave', handleMouseLeave, { passive: true });
    }

    startAnimation();

    return () => {
      stopAnimation();
      motionQuery.removeEventListener('change', handleMotionChange);
      document.removeEventListener('visibilitychange', handleVisibilityChange);
      window.removeEventListener('scroll', handleScroll);
      if (resizeObserver) resizeObserver.disconnect();
      if (canvas && canvas.parentElement) {
        canvas.parentElement.removeEventListener('mousemove', handleMouseMove);
        canvas.parentElement.removeEventListener('mouseleave', handleMouseLeave);
      }
    };
  });
</script>

<div class="hero-layers" aria-hidden="true">
  <!-- Layer 1: Bottom Video Layer or Fallback Poster Image -->
  {#if canLoadVideo}
    <video
      bind:this={videoEl}
      class="hero-media hero-video"
      src="/videos/hero-bg.mp4"
      poster="/images/hero-poster.jpg"
      autoplay
      muted
      loop
      playsinline
      preload="metadata"
    ></video>
  {:else}
    <img
      src="/images/hero-poster.jpg"
      alt=""
      class="hero-media hero-poster"
      loading="eager"
      decoding="async"
    />
  {/if}

  <!-- Layer 2: Transparent Particle Canvas Overlay -->
  <canvas
    bind:this={canvas}
    class="hero-canvas"
  ></canvas>
</div>

<style>
  .hero-layers {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    pointer-events: none;
    z-index: 0;
    background-color: var(--text-main, #1A1918);
    background-image: url('/images/hero-poster.jpg');
    background-size: cover;
    background-position: center;
  }

  .hero-media {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
  }

  .hero-canvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: block;
    pointer-events: none;
    z-index: 1;
  }
</style>
