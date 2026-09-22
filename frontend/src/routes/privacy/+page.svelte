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
  <title>سياسة الخصوصية | منصة دورات حاسبات الأزهر</title>
</svelte:head>

<div class="min-h-screen bg-background text-foreground py-16 px-4 sm:px-6 lg:px-8">
  <div class="max-w-3xl mx-auto space-y-8">
    <div class="border-b border-border pb-6">
      <h1 class="text-3xl font-extrabold tracking-tight">سياسة الخصوصية (Privacy Policy)</h1>
      <p class="text-xs text-muted-foreground mt-1">آخر تحديث: سبتمبر 2026</p>
    </div>

    {#if loading}
      <div class="space-y-4 animate-pulse">
        <div class="h-6 bg-muted rounded w-3/4"></div>
        <div class="h-20 bg-muted rounded"></div>
      </div>
    {:else}
      <div class="prose dark:prose-invert max-w-none text-sm leading-relaxed whitespace-pre-line text-foreground/90">
        {content}
      </div>
    {/if}
  </div>
</div>
