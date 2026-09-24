<script lang="ts">
  import { onMount } from 'svelte';
  import { getContentBlock } from '$lib/api/admin';

  let content: string = $state('');
  let loading = $state(true);

  async function loadBlock() {
    try {
      const res = await getContentBlock(fetch, 'legal.privacy');
      content = res.data.content.ar;
    } catch {
      content = 'سياسة الخصوصية: نحن نحترم خصوصية بياناتك ولا نشارك معلوماتك الشخصية مع أي طرف ثالث.';
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    loadBlock();
  });
</script>

<svelte:head>
  <title>سياسة الخصوصية | منصة Codeera</title>
</svelte:head>

<div class="legal-page" dir="rtl">
  <div class="legal-container">
    <div class="legal-header">
      <h1>سياسة الخصوصية (Privacy Policy)</h1>
      <p class="legal-date">آخر تحديث: سبتمبر 2026</p>
    </div>

    {#if loading}
      <div class="loading-skeleton">
        <div class="skeleton-line wide"></div>
        <div class="skeleton-block"></div>
      </div>
    {:else}
      <div class="legal-content">
        {content}
      </div>
    {/if}
  </div>
</div>

<style>
  .legal-page {
    background-color: var(--paper);
    min-height: calc(100vh - 140px);
    padding: 3rem 1.25rem 5rem;
  }

  .legal-container {
    max-width: 780px;
    margin-inline: auto;
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }

  .legal-header {
    border-bottom: 2px solid var(--line);
    padding-bottom: 1.5rem;
  }

  .legal-header h1 {
    font-size: 1.85rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0 0 0.35rem;
    letter-spacing: -0.02em;
  }

  .legal-date {
    font-size: 0.82rem;
    color: var(--muted);
    margin: 0;
  }

  .loading-skeleton {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .skeleton-line {
    height: 1.5rem;
    background: var(--line);
    border-radius: 0.35rem;
    opacity: 0.3;
    animation: pulse-skeleton 1.2s ease-in-out infinite;
  }

  .skeleton-line.wide {
    width: 75%;
  }

  .skeleton-block {
    height: 5rem;
    background: var(--line);
    border-radius: 0.5rem;
    opacity: 0.2;
    animation: pulse-skeleton 1.2s ease-in-out infinite;
    animation-delay: 0.2s;
  }

  @keyframes pulse-skeleton {
    0%, 100% { opacity: 0.2; }
    50% { opacity: 0.35; }
  }

  .legal-content {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1rem;
    padding: 2rem;
    font-size: 0.95rem;
    line-height: 1.85;
    color: var(--ink);
    white-space: pre-line;
    box-shadow: 0 4px 12px rgba(15, 40, 47, 0.04);
  }

  @media (max-width: 600px) {
    .legal-page {
      padding: 2rem 1rem 4rem;
    }
  }
</style>
