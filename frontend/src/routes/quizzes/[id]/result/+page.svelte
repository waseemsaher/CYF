<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/state';
  import { getQuizResult, type QuizResultResponse } from '$lib/api/learning';

  const quizId = Number(page.params.id);
  const attemptId = Number(page.url.searchParams.get('attempt_id') || 0);

  let result: QuizResultResponse | null = $state(null);
  let loading = $state(true);
  let errorMsg = $state('');

  async function loadResult() {
    if (!attemptId) {
      errorMsg = 'معرف المحاولة غير متوفر.';
      loading = false;
      return;
    }

    try {
      loading = true;
      errorMsg = '';
      const res = await getQuizResult(fetch, quizId, attemptId);
      result = res.data;
    } catch (e: any) {
      errorMsg = e?.message || 'تعذر تحميل نتيجة الاختبار.';
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    loadResult();
  });
</script>

<svelte:head>
  <title>نتيجة الاختبار | منصة دورات حاسبات الأزهر</title>
</svelte:head>

<div class="min-h-screen bg-background text-foreground py-10 px-4 sm:px-6 lg:px-8">
  <div class="max-w-3xl mx-auto space-y-8">
    {#if loading}
      <div class="space-y-4 animate-pulse">
        <div class="h-40 bg-muted rounded-2xl"></div>
        <div class="h-64 bg-muted rounded-2xl"></div>
      </div>
    {:else if errorMsg}
      <div class="p-6 rounded-2xl bg-destructive/10 border border-destructive/20 text-center space-y-4">
        <p class="text-sm font-medium text-destructive">{errorMsg}</p>
        <button onclick={() => history.back()} class="px-5 py-2.5 rounded-xl bg-primary text-primary-foreground font-semibold text-sm">
          العودة
        </button>
      </div>
    {:else if result}
      <!-- Score summary card -->
      <div class="p-8 rounded-3xl border border-border bg-card shadow-sm text-center space-y-4">
        <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl {(result.percentage ?? 0) >= 50 ? 'bg-emerald-500/10 text-emerald-500' : 'bg-destructive/10 text-destructive'} mx-auto">
          {#if (result.percentage ?? 0) >= 50}
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          {:else}
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          {/if}
        </div>

        <div>
          <h1 class="text-2xl font-extrabold">تم تسليم الاختبار بنجاح</h1>
          <p class="text-xs text-muted-foreground mt-1">حالة المحاولة: {result.status === 'submitted' ? 'مكتملة' : 'منتهية الوقت'}</p>
        </div>

        {#if result.score !== null && result.max_score !== null}
          <div class="flex items-center justify-center gap-6 py-4">
            <div>
              <p class="text-3xl font-black text-primary">{result.score} / {result.max_score}</p>
              <p class="text-xs text-muted-foreground mt-0.5">الدرجة الكلية</p>
            </div>
            <div class="h-10 w-px bg-border"></div>
            <div>
              <p class="text-3xl font-black {(result.percentage ?? 0) >= 50 ? 'text-emerald-500' : 'text-destructive'}">
                {result.percentage}%
              </p>
              <p class="text-xs text-muted-foreground mt-0.5">النسبة المئوية</p>
            </div>
          </div>
        {:else}
          <div class="p-4 rounded-xl bg-muted/40 text-xs text-muted-foreground">
            {result.visibility === 'after_close' ? 'سيتم إعلان الدرجات والإجابات النموذجية بعد انتهاء موعد الاختبار.' : 'نتائج هذا الاختبار غير معلنة للطلاب.'}
          </div>
        {/if}

        <div class="pt-2">
          <button
            onclick={() => history.back()}
            class="px-6 py-2.5 rounded-xl bg-secondary text-secondary-foreground font-semibold text-sm hover:bg-secondary/80 transition"
          >
            العودة للمادة
          </button>
        </div>
      </div>

      <!-- Question breakdown if available -->
      {#if result.breakdown && result.breakdown.length > 0}
        <div class="space-y-6">
          <h2 class="text-lg font-bold">مراجعة الإجابات والحل النموذجي</h2>

          {#each result.breakdown as q, idx}
            <div class="p-6 rounded-2xl border {q.is_correct ? 'border-emerald-500/20 bg-emerald-500/5' : 'border-destructive/20 bg-destructive/5'} space-y-4">
              <div class="flex items-center justify-between gap-2 border-b border-border/50 pb-3">
                <span class="font-bold text-sm">السؤال {idx + 1}</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded {q.is_correct ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-destructive/20 text-destructive'}">
                  {q.points_awarded} / {q.points} درجة
                </span>
              </div>

              <p class="text-base font-semibold">{q.text.ar}</p>

              <!-- Options review -->
              <div class="space-y-2 pt-2">
                {#each q.options as opt}
                  {@const isSelected = q.selected_option_ids.includes(opt.id)}
                  {@const isCorrect = opt.is_correct}

                  <div class="flex items-center justify-between p-3.5 rounded-xl border text-sm {isCorrect ? 'border-emerald-500 bg-emerald-500/10 font-semibold' : isSelected && !isCorrect ? 'border-destructive bg-destructive/10 text-destructive line-through' : 'border-border bg-card/50 text-muted-foreground'}">
                    <span>{opt.text.ar}</span>

                    {#if isCorrect}
                      <span class="text-xs text-emerald-600 font-bold flex items-center gap-1">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        الإجابة الصحيحة
                      </span>
                    {:else if isSelected}
                      <span class="text-xs text-destructive font-semibold">إجابتك</span>
                    {/if}
                  </div>
                {/each}
              </div>

              {#if q.explanation?.ar}
                <div class="p-3.5 rounded-xl bg-muted/60 text-xs text-foreground/80 space-y-1">
                  <span class="font-bold block text-foreground">توضيح:</span>
                  <p>{q.explanation.ar}</p>
                </div>
              {/if}
            </div>
          {/each}
        </div>
      {/if}
    {/if}
  </div>
</div>
