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
  <title>{data ? data.quiz.title.ar : 'أداء الاختبار'} | منصة Codeera</title>
</svelte:head>

<div class="quiz-page" dir="rtl">
  <div class="quiz-container">
    {#if loading}
      <div class="loading-shell">
        <div class="spinner" aria-hidden="true"></div>
        <p>جاري تحميل أسئلة الاختبار...</p>
      </div>
    {:else if errorMsg && !data}
      <div class="error-card">
        <p>{errorMsg}</p>
        <button type="button" onclick={() => history.back()} class="btn-back">العودة</button>
      </div>
    {:else if data}
      <!-- Header bar with title and sticky timer -->
      <div class="quiz-header">
        <div>
          <span class="quiz-type-badge">
            {data.quiz.kind === 'exam' ? '📝 امتحان' : '📝 اختبار'}
          </span>
          <h1>{data.quiz.title.ar}</h1>
        </div>

        {#if data.attempt.duration_minutes}
          <div class="timer-badge" class:timer-warning={remainingSeconds < 180}>
            ⏱ {formatTime(remainingSeconds)}
          </div>
        {/if}
      </div>

      {#if errorMsg}
        <div class="inline-error" role="alert">{errorMsg}</div>
      {/if}

      <!-- Questions List -->
      <div class="questions-list">
        {#each data.questions as question, idx}
          <div class="question-card">
            <div class="question-header">
              <span class="question-num">السؤال {idx + 1} من {data.questions.length}</span>
              <span class="points-badge">{question.points} درجة</span>
            </div>

            <p class="question-text">{question.text.ar}</p>

            <div class="options-list">
              {#each question.options as option}
                <label class="option-label" class:selected={answers[question.id] === option.id}>
                  <input
                    type="radio"
                    name="q_{question.id}"
                    value={option.id}
                    checked={answers[question.id] === option.id}
                    onchange={() => { answers[question.id] = option.id; }}
                  />
                  <span>{option.text.ar}</span>
                </label>
              {/each}
            </div>
          </div>
        {/each}
      </div>

      <!-- Submit Footer -->
      <div class="submit-footer">
        <button
          type="button"
          onclick={() => handleSubmit(false)}
          disabled={submitting}
          class="btn-submit-quiz"
        >
          {submitting ? 'جاري تصحيح الاختبار...' : 'تسليم الإجابات وإنهاء الاختبار'}
        </button>
      </div>
    {/if}
  </div>
</div>

<style>
  .quiz-page {
    background-color: var(--paper);
    min-height: calc(100vh - 140px);
    padding: 2rem 1.25rem 4rem;
  }

  .quiz-container {
    max-width: 780px;
    margin-inline: auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .loading-shell {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 40vh;
    gap: 1rem;
    color: var(--muted);
    font-weight: 600;
  }

  .spinner {
    width: 2.5rem;
    height: 2.5rem;
    border: 3px solid var(--line);
    border-top-color: var(--deep-cyan);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  @keyframes spin { to { transform: rotate(360deg); } }

  .error-card {
    background: #fef2f2;
    border: 2px solid #fecaca;
    border-radius: 1rem;
    padding: 2rem;
    text-align: center;
  }

  .error-card p { color: #991b1b; font-weight: 600; margin: 0 0 1rem; }

  .btn-back {
    background: var(--storm);
    color: var(--cyan);
    padding: 0.6rem 1.25rem;
    border-radius: 0.5rem;
    font-weight: 700;
    font-size: 0.9rem;
    border: none;
    cursor: pointer;
  }

  /* Quiz Header */
  .quiz-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1rem;
    padding: 1.25rem 1.5rem;
    position: sticky;
    top: 4.5rem;
    z-index: 20;
    box-shadow: 0 4px 12px rgba(15, 40, 47, 0.06);
  }

  .quiz-type-badge {
    font-size: 0.78rem;
    font-weight: 700;
    background: rgba(var(--brand-navy-rgb), 0.08);
    color: var(--brand-navy);
    padding: 0.2rem 0.5rem;
    border-radius: 0.25rem;
  }

  .quiz-header h1 {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0.25rem 0 0;
  }

  .timer-badge {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    font-weight: 800;
    font-size: 0.95rem;
    color: var(--storm);
    font-feature-settings: "tnum";
  }

  .timer-warning {
    color: #dc2626;
    border-color: #fecaca;
    background: #fef2f2;
    animation: pulse-timer 1.5s infinite;
  }

  @keyframes pulse-timer {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
  }

  .inline-error {
    background: #fef2f2;
    border: 2px solid #fecaca;
    color: #991b1b;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
    font-weight: 600;
  }

  /* Questions */
  .questions-list {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .question-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(15, 40, 47, 0.04);
  }

  .question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid var(--line);
    padding-bottom: 0.85rem;
    margin-bottom: 1rem;
  }

  .question-num {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--storm);
  }

  .points-badge {
    font-size: 0.78rem;
    font-weight: 600;
    background: var(--paper);
    color: var(--muted);
    padding: 0.2rem 0.5rem;
    border-radius: 0.25rem;
    border: 2px solid var(--line);
  }

  .question-text {
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.7;
    color: var(--storm);
    margin: 0 0 1rem;
  }

  .options-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .option-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    border: 2px solid var(--line);
    border-radius: 0.65rem;
    cursor: pointer;
    transition: border-color 150ms ease, background 150ms ease;
    font-size: 0.92rem;
    color: var(--storm);
  }

  .option-label:hover {
    background: #f8faf9;
  }

  .option-label.selected {
    border-color: var(--deep-cyan);
    background: rgba(var(--brand-navy-rgb), 0.08);
    font-weight: 600;
    color: var(--deep-cyan);
  }

  .option-label input[type="radio"] {
    accent-color: var(--deep-cyan);
    margin: 0;
    width: 1rem;
    height: 1rem;
  }

  /* Submit Footer */
  .submit-footer {
    display: flex;
    justify-content: flex-end;
    padding-top: 1rem;
    padding-bottom: 3rem;
  }

  .btn-submit-quiz {
    background: var(--brand-navy);
    color: #FAF8F5;
    font-weight: 800;
    font-size: 1rem;
    padding: 0.85rem 2rem;
    border-radius: 0.5rem;
    border: 2px solid var(--brand-navy);
    cursor: pointer;
    transition: opacity 150ms ease, transform 150ms ease, background-color 150ms ease;
    width: 100%;
  }

  .btn-submit-quiz:hover:not(:disabled) {
    opacity: 0.95;
    transform: translateY(-1px);
    background-color: #1E2B4D;
  }

  .btn-submit-quiz:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  @media (min-width: 640px) {
    .btn-submit-quiz {
      width: auto;
    }
  }
</style>
