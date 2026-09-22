<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/state';
  import { getCourseContent, type CourseContentResponse } from '$lib/api/learning';
  import TelegramLinkCard from '$lib/components/TelegramLinkCard.svelte';

  let content: CourseContentResponse | null = $state(null);
  let loading = $state(true);
  let errorMsg = $state('');

  const slug = page.params.slug || '';

  async function loadData() {
    try {
      loading = true;
      errorMsg = '';
      const res = await getCourseContent(fetch, slug);
      content = res.data;
    } catch (e: any) {
      errorMsg = e?.message || 'تعذر تحميل محتوى المادة. تأكد من تفعيل اشتراكك في المادة.';
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    loadData();
  });
</script>

<svelte:head>
  <title>{content ? content.course.title.ar : 'محتوى المادة'} | منصة دورات حاسبات الأزهر</title>
</svelte:head>

<div class="min-h-screen bg-background text-foreground py-10 px-4 sm:px-6 lg:px-8">
  <div class="max-w-5xl mx-auto space-y-8">
    {#if loading}
      <div class="space-y-4 animate-pulse">
        <div class="h-10 bg-muted rounded-xl w-1/3"></div>
        <div class="h-48 bg-muted rounded-2xl"></div>
        <div class="h-64 bg-muted rounded-2xl"></div>
      </div>
    {:else if errorMsg}
      <div class="p-6 rounded-2xl bg-destructive/10 border border-destructive/20 text-center space-y-4">
        <p class="text-sm font-medium text-destructive">{errorMsg}</p>
        <a href="/courses/{slug}" class="inline-block px-5 py-2.5 rounded-xl bg-primary text-primary-foreground font-semibold text-sm">
          العودة لصفحة المادة
        </a>
      </div>
    {:else if content}
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-border pb-6">
        <div>
          <nav class="text-xs text-muted-foreground mb-2 flex items-center gap-1.5">
            <a href="/courses" class="hover:underline">المواد</a>
            <span>/</span>
            <a href="/courses/{slug}" class="hover:underline">{content.course.title.ar}</a>
            <span>/</span>
            <span class="text-foreground">المحتوى التعليمي</span>
          </nav>
          <h1 class="text-3xl font-extrabold tracking-tight">{content.course.title.ar}</h1>
        </div>

        {#if content.is_unlocked && content.course.telegram_invite_link}
          <a
            href={content.course.telegram_invite_link}
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-sky-500 text-white font-bold text-sm hover:bg-sky-600 transition shadow-sm"
          >
            <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .37z"/>
            </svg>
            انضم لمجموعة التليجرام للمادة
          </a>
        {/if}
      </div>

      <!-- Telegram Linking Reminder Card -->
      <TelegramLinkCard />

      <!-- Course Content Sections -->
      <div class="space-y-6">
        <h2 class="text-xl font-bold">الخطة الدراسية والمحتوى</h2>

        {#if content.sections.length === 0}
          <div class="p-8 rounded-2xl border border-dashed border-border text-center text-muted-foreground">
            لم يتم رفع محتوى دراسي لهذه المادة بعد.
          </div>
        {:else}
          {#each content.sections as section}
            <div class="rounded-2xl border border-border bg-card overflow-hidden shadow-sm">
              <div class="bg-muted/40 p-4 border-b border-border flex items-center justify-between">
                <h3 class="font-bold text-base">{section.title.ar}</h3>
                <span class="text-xs text-muted-foreground">{section.items.length} عنصر</span>
              </div>

              {#if section.items.length === 0}
                <div class="p-4 text-xs text-muted-foreground text-center">لا توجد عناصر في هذا القسم حالياً.</div>
              {:else}
                <div class="divide-y divide-border">
                  {#each section.items as item}
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-muted/20 transition">
                      <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary shrink-0">
                          {#if item.type === 'lecture_link'}
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                          {:else if item.type === 'file'}
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                          {:else if item.type === 'quiz' || item.type === 'exam'}
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                          {:else}
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                          {/if}
                        </div>
                        <div>
                          <p class="font-bold text-sm text-foreground">{item.title.ar}</p>
                          {#if item.description?.ar}
                            <p class="text-xs text-muted-foreground mt-0.5">{item.description.ar}</p>
                          {/if}
                        </div>
                      </div>

                      <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
                        {#if item.is_locked}
                          <span class="inline-flex items-center gap-1 text-xs text-muted-foreground bg-muted px-2.5 py-1 rounded-full">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            مغلق (اشترك للفتح)
                          </span>
                        {:else}
                          {#if item.type === 'lecture_link' || item.type === 'external_link'}
                            {#if item.url}
                              <a
                                href={item.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 text-xs font-semibold transition"
                              >
                                فتح الرابط
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                              </a>
                            {/if}
                          {:else if item.type === 'file'}
                            <a
                              href="/api/v1/courses/{slug}/items/{item.id}/file"
                              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-secondary text-secondary-foreground hover:bg-secondary/80 text-xs font-semibold transition"
                            >
                              تحميل الملف
                              <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                          {:else if (item.type === 'quiz' || item.type === 'exam') && item.quiz}
                            <a
                              href="/quizzes/{item.quiz.id}"
                              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 text-xs font-semibold transition"
                            >
                              ابدأ {item.quiz.kind === 'exam' ? 'الامتحان' : 'الاختبار'}
                            </a>
                          {/if}
                        {/if}
                      </div>
                    </div>
                  {/each}
                </div>
              {/if}
            </div>
          {/each}
        {/if}
      </div>
    {/if}
  </div>
</div>
