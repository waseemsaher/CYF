<script lang="ts">
  import { onMount } from 'svelte';

  interface Props {
    prefix?: string;
    phrases: string[];
  }

  let { prefix = '', phrases = [] }: Props = $props();

  let displayedText = $state('');
  let isReducedMotion = $state(false);
  let isDeleting = $state(false);
  let phraseIndex = $state(0);
  let charIndex = $state(0);
  let timer: ReturnType<typeof setTimeout> | null = null;

  const TYPING_SPEED = 75; // ms per char
  const DELETING_SPEED = 38; // ms per char
  const END_PAUSE = 2200; // pause on full phrase
  const START_PAUSE = 350; // pause before typing next phrase

  function clearTypewriterTimer() {
    if (timer) {
      clearTimeout(timer);
      timer = null;
    }
  }

  function tick() {
    if (isReducedMotion) {
      displayedText = phrases[0] || '';
      return;
    }

    if (!phrases || phrases.length === 0) {
      displayedText = '';
      return;
    }

    const currentPhrase = phrases[phraseIndex % phrases.length];

    if (!isDeleting) {
      // Typing phase
      if (charIndex < currentPhrase.length) {
        charIndex++;
        displayedText = currentPhrase.slice(0, charIndex);
        timer = setTimeout(tick, TYPING_SPEED);
      } else {
        // Finished typing phrase, pause before deleting
        timer = setTimeout(() => {
          isDeleting = true;
          tick();
        }, END_PAUSE);
      }
    } else {
      // Deleting phase
      if (charIndex > 0) {
        charIndex--;
        displayedText = currentPhrase.slice(0, charIndex);
        timer = setTimeout(tick, DELETING_SPEED);
      } else {
        // Finished deleting, advance to next phrase
        isDeleting = false;
        phraseIndex = (phraseIndex + 1) % phrases.length;
        timer = setTimeout(tick, START_PAUSE);
      }
    }
  }

  // React to phrases changing (e.g. language toggle)
  $effect(() => {
    if (phrases && phrases.length > 0) {
      clearTypewriterTimer();
      phraseIndex = 0;
      charIndex = 0;
      isDeleting = false;

      if (isReducedMotion) {
        displayedText = phrases[0] || '';
      } else {
        displayedText = '';
        timer = setTimeout(tick, START_PAUSE);
      }
    }
  });

  onMount(() => {
    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    isReducedMotion = motionQuery.matches;

    const handleMotionChange = (e: MediaQueryListEvent) => {
      isReducedMotion = e.matches;
      clearTypewriterTimer();
      if (isReducedMotion) {
        displayedText = phrases[0] || '';
      } else {
        charIndex = 0;
        isDeleting = false;
        timer = setTimeout(tick, START_PAUSE);
      }
    };
    motionQuery.addEventListener('change', handleMotionChange);

    if (isReducedMotion) {
      displayedText = phrases[0] || '';
    } else {
      timer = setTimeout(tick, START_PAUSE);
    }

    return () => {
      clearTypewriterTimer();
      motionQuery.removeEventListener('change', handleMotionChange);
    };
  });
</script>

<div class="typewriter-container" aria-live="polite" aria-atomic="true">
  {#if prefix}
    <span class="typewriter-prefix">{prefix}</span>
  {/if}
  <span class="typewriter-text-wrapper">
    <span class="typewriter-text">{displayedText}</span>
    {#if !isReducedMotion}
      <span class="typewriter-cursor" aria-hidden="true"></span>
    {/if}
  </span>
</div>

<style>
  .typewriter-container {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: clamp(0.95rem, 2vw, 1.15rem);
    font-weight: 600;
    min-height: 2rem;
    padding: 0.35rem 1rem;
    border-radius: 9999px;
    background: rgba(26, 25, 24, 0.65);
    border: 1px solid rgba(213, 203, 193, 0.3);
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.4);
    max-width: 100%;
    flex-wrap: wrap;
    justify-content: center;
  }

  .typewriter-prefix {
    color: #D5CBC1;
    font-weight: 500;
    font-size: 0.9em;
  }

  .typewriter-text-wrapper {
    display: inline-flex;
    align-items: center;
    color: #FAF8F5;
    text-shadow: 0 0 16px rgba(200, 43, 52, 0.35);
  }

  .typewriter-text {
    display: inline-block;
    white-space: pre-wrap;
    word-break: break-word;
  }

  .typewriter-cursor {
    display: inline-block;
    width: 2px;
    height: 1.15em;
    background-color: var(--brand-accent, #C82B34);
    margin-inline-start: 2px;
    border-radius: 1px;
    box-shadow: 0 0 8px rgba(200, 43, 52, 0.7);
    animation: cursorBlink 1s infinite;
  }

  @keyframes cursorBlink {
    0%, 45% {
      opacity: 1;
    }
    50%, 95% {
      opacity: 0;
    }
    100% {
      opacity: 1;
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .typewriter-cursor {
      display: none;
    }
  }
</style>
