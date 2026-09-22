<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import { page } from '$app/state';
  import { goto } from '$app/navigation';
  import { startQuiz, submitQuiz, type StartQuizResponse } from '$lib/api/learning';

  const quizId = Number(page.params.id);

  let data: StartQuizResponse | null = $state(null);
  let loading = $state(true);
  let submitting = $state(false);
  let errorMsg = $state('');
  let answers: Record<number, number> = $state({});
  let remainingSeconds = $state(0);
  let timerInterval: any = null;

  async function initQuiz() {
    try {
      loading = true;
      errorMsg = '';
      const res = await startQuiz(fetch, quizId);
      data = res.data;

      if (data.attempt.duration_minutes) {
        const startedTime = new Date(data.attempt.started_at).getTime();
        const durationMs = data.attempt.duration_minutes * 60 * 1000;
        const elapsedMs = Date.now() - startedTime;
        const leftMs = Math.max(0, durationMs - elapsedMs);
        remainingSeconds = Math.floor(leftMs / 1000);

        startTimer();
      }
    } catch (e: any) {
      errorMsg = e?.message || 'تعذر بدء الاختبار. تأكد من تفعيل اشتراكك في المادة.';
    } finally {
      loading = false;
    }
  }

  function startTimer() {
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
      if (remainingSeconds > 0) {
        remainingSeconds--;
      } else {
        clearInterval(timerInterval);
        handleSubmit(true);
      }
    }, 1000);
  }

  function formatTime(secs: number): string {
    const mins = Math.floor(secs / 60);
    const s = secs % 60;
    return `${mins}:${s.toString().padStart(2, '0')}`;
  }

  async function handleSubmit(autoSubmit = false) {
    if (!data) return;
    if (!autoSubmit && !confirm('هل أنت متأكد من رغبتك في تسليم الاختبار الآن؟')) return;

    try {
      submitting = true;
      if (timerInterval) clearInterval(timerInterval);

      // Convert answers map to Record<number, number[]>
      const formattedAnswers: Record<number, number[]> = {};
      for (const [qId, optId] of Object.entries(answers)) {
        formattedAnswers[Number(qId)] = [optId];
      }

      const res = await submitQuiz(fetch, quizId, data.attempt.id, formattedAnswers);
      goto(`/quizzes/${quizId}/result?attempt_id=${data.attempt.id}`);
    } catch (e: any) {
      errorMsg = e?.message || 'تعذر تسليم الاختبار.';
      submitting = false;
    }
  }

  onDestroy(() => {
    if (timerInterval) clearInterval(timerInterval);
  });

  onMount(() => {
    initQuiz();
  });
</script>

<svelte:head>
  <title>{data ? data.quiz.title.ar : 'أداء الاختبار'} | منصة دورات حاسبات الأزهر</title>
</svelte:head>

<div class="min-h-screen bg-background text-foreground py-10 px-4 sm:px-6 lg:px-8">
  <div class="max-w-3xl mx-auto space-y-8">
    {#if loading}
      <div class="space-y-4 animate-pulse">
        <div class="h-12 bg-muted rounded-2xl"></div>
        <div class="h-64 bg-muted rounded-2xl"></div>
        <div class="h-64 bg-muted rounded-2xl"></div>
      </div>
    {:else if errorMsg && !data}
      <div class="p-6 rounded-2xl bg-destructive/10 border border-destructive/20 text-center space-y-4">
        <p class="text-sm font-medium text-destructive">{errorMsg}</p>
        <button onclick={() => history.back()} class="px-5 py-2.5 rounded-xl bg-primary text-primary-foreground font-semibold text-sm">
          العودة
        </button>
      </div>
    {:else if data}
      <!-- Header bar with title and sticky timer -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-2xl border border-border bg-card shadow-sm sticky top-4 z-20">
        <div>
          <span class="text-xs font-semibold px-2 py-0.5 rounded bg-primary/10 text-primary">
            {data.quiz.kind === 'exam' ? 'امتحان' : 'اختبار'}
          </span>
          <h1 class="text-xl font-bold mt-1">{data.quiz.title.ar}</h1>
        </div>

        {#if data.attempt.duration_minutes}
          <div class="flex items-center gap-2 self-start sm:self-auto px-4 py-2 rounded-xl border border-border bg-muted/50 font-mono font-bold text-sm {remainingSeconds < 180 ? 'text-destructive animate-pulse' : 'text-foreground'}">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{formatTime(remainingSeconds)}</span>
          </div>
        {/if}
      </div>

      {#if errorMsg}
        <div class="p-4 rounded-xl bg-destructive/10 text-destructive text-sm font-medium">
          {errorMsg}
        </div>
      {/if}

      <!-- Questions List -->
      <div class="space-y-6">
        {#each data.questions as question, idx}
          <div class="p-6 rounded-2xl border border-border bg-card shadow-sm space-y-4">
            <div class="flex items-center justify-between gap-2 border-b border-border pb-3">
              <span class="font-bold text-sm text-foreground">السؤال {idx + 1} من {data.questions.length}</span>
              <span class="text-xs text-muted-foreground bg-muted px-2 py-0.5 rounded">{question.points} درجة</span>
            </div>

            <p class="text-base font-semibold leading-relaxed">{question.text.ar}</p>

            <div class="space-y-2 pt-2">
              {#each question.options as option}
                <label class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition select-none {answers[question.id] === option.id ? 'border-primary bg-primary/5 text-primary font-medium' : 'border-border hover:bg-muted/40'}">
                  <input
                    type="radio"
                    name="q_{question.id}"
                    value={option.id}
                    checked={answers[question.id] === option.id}
                    onchange={() => { answers[question.id] = option.id; }}
                    class="h-4 w-4 text-primary focus:ring-primary"
                  />
                  <span class="text-sm">{option.text.ar}</span>
                </label>
              {/each}
            </div>
          </div>
        {/each}
      </div>

      <!-- Submit Footer -->
      <div class="flex justify-end pt-4 pb-12">
        <button
          onclick={() => handleSubmit(false)}
          disabled={submitting}
          class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-primary text-primary-foreground font-bold text-base hover:bg-primary/90 transition shadow-md disabled:opacity-50"
        >
          {submitting ? 'جاري تصحيح الاختبار...' : 'تسليم الإجابات وإنهاء الاختبار'}
        </button>
      </div>
    {/if}
  </div>
</div>
