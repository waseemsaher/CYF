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
  <title>نتيجة الاختبار | منصة Codeera</title>
</svelte:head>

<div class="result-page" dir="rtl">
  <div class="result-container">
    {#if loading}
      <div class="loading-shell">
        <div class="spinner" aria-hidden="true"></div>
        <p>جاري تحميل نتيجة الاختبار...</p>
      </div>
    {:else if errorMsg}
      <div class="error-card">
        <p>{errorMsg}</p>
        <button type="button" onclick={() => history.back()} class="btn-back">العودة</button>
      </div>
    {:else if result}
      <!-- Score summary card -->
      <div class="score-card">
        <div class="score-icon" class:pass={(result.percentage ?? 0) >= 50} class:fail={(result.percentage ?? 0) < 50}>
          {#if (result.percentage ?? 0) >= 50}
            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          {:else}
            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          {/if}
        </div>

        <h1>تم تسليم الاختبار بنجاح</h1>
        <p class="result-status">حالة المحاولة: {result.status === 'submitted' ? 'مكتملة' : 'منتهية الوقت'}</p>

        {#if result.score !== null && result.max_score !== null}
          <div class="score-stats">
            <div class="stat">
              <strong class="score-value">{result.score} / {result.max_score}</strong>
              <span>الدرجة الكلية</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat">
              <strong class="percentage-value" class:pass={(result.percentage ?? 0) >= 50} class:fail={(result.percentage ?? 0) < 50}>
                {result.percentage}%
              </strong>
              <span>النسبة المئوية</span>
            </div>
          </div>
        {:else}
          <div class="hidden-results-notice">
            {result.visibility === 'after_close' ? 'سيتم إعلان الدرجات والإجابات النموذجية بعد انتهاء موعد الاختبار.' : 'نتائج هذا الاختبار غير معلنة للطلاب.'}
          </div>
        {/if}

        <button type="button" onclick={() => history.back()} class="btn-go-back">
          العودة للمادة
        </button>
      </div>

      <!-- Question breakdown if available -->
      {#if result.breakdown && result.breakdown.length > 0}
        <section class="breakdown-section">
          <h2>مراجعة الإجابات والحل النموذجي</h2>

          {#each result.breakdown as q, idx}
            <div class="breakdown-card" class:correct={q.is_correct} class:incorrect={!q.is_correct}>
              <div class="breakdown-header">
                <span class="q-num">السؤال {idx + 1}</span>
                <span class="q-score" class:correct={q.is_correct} class:incorrect={!q.is_correct}>
                  {q.points_awarded} / {q.points} درجة
                </span>
              </div>

              <p class="q-text">{q.text.ar}</p>

              <div class="options-review">
                {#each q.options as opt}
                  {@const isSelected = q.selected_option_ids.includes(opt.id)}
                  {@const isCorrect = opt.is_correct}

                  <div class="review-option" class:is-correct={isCorrect} class:is-wrong={isSelected && !isCorrect} class:is-neutral={!isCorrect && !isSelected}>
                    <span class:strikethrough={isSelected && !isCorrect}>{opt.text.ar}</span>

                    {#if isCorrect}
                      <span class="tag-correct">الإجابة الصحيحة</span>
                    {:else if isSelected}
                      <span class="tag-wrong">إجابتك</span>
                    {/if}
                  </div>
                {/each}
              </div>

              {#if q.explanation?.ar}
                <div class="explanation-box">
                  <strong>توضيح:</strong>
                  <p>{q.explanation.ar}</p>
                </div>
              {/if}
            </div>
          {/each}
        </section>
      {/if}
    {/if}
  </div>
</div>

<style>
  .result-page {
    background-color: var(--paper);
    min-height: calc(100vh - 140px);
    padding: 2rem 1.25rem 4rem;
  }

  .result-container {
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

  /* Score Card */
  .score-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    padding: 2.5rem 2rem;
    text-align: center;
    box-shadow: 0 4px 16px rgba(15, 40, 47, 0.06);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
  }

  .score-icon {
    width: 4rem;
    height: 4rem;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 800;
  }

  .score-icon.pass {
    background: #ecfdf5;
    color: #059669;
    border: 2px solid #a7f3d0;
  }

  .score-icon.fail {
    background: #fef2f2;
    color: #dc2626;
    border: 2px solid #fecaca;
  }

  .score-card h1 {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .result-status {
    font-size: 0.82rem;
    color: var(--muted);
    margin: 0;
  }

  .score-stats {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2rem;
    padding: 1rem 0;
  }

  .stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
  }

  .stat span {
    font-size: 0.78rem;
    color: var(--muted);
  }

  .score-value {
    font-size: 1.85rem;
    font-weight: 800;
    color: var(--deep-cyan);
  }

  .percentage-value {
    font-size: 1.85rem;
    font-weight: 800;
  }

  .percentage-value.pass { color: #059669; }
  .percentage-value.fail { color: #dc2626; }

  .stat-divider {
    width: 1px;
    height: 2.5rem;
    background: var(--line);
  }

  .hidden-results-notice {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: 0.5rem;
    padding: 1rem;
    font-size: 0.85rem;
    color: var(--muted);
  }

  .btn-go-back {
    background: var(--paper);
    color: var(--storm);
    font-weight: 700;
    font-size: 0.9rem;
    padding: 0.6rem 1.5rem;
    border-radius: 0.5rem;
    border: 2px solid var(--line);
    cursor: pointer;
    transition: background 150ms ease;
    margin-top: 0.5rem;
  }

  .btn-go-back:hover {
    background: #eef5f4;
  }

  /* Breakdown Section */
  .breakdown-section {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .breakdown-section h2 {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .breakdown-card {
    border-radius: 1rem;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .breakdown-card.correct {
    background: #f0fdf4;
    border: 2px solid #a7f3d0;
  }

  .breakdown-card.incorrect {
    background: #fef2f2;
    border: 2px solid #fecaca;
  }

  .breakdown-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(0,0,0,0.08);
    padding-bottom: 0.75rem;
  }

  .q-num {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--storm);
  }

  .q-score {
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.2rem 0.5rem;
    border-radius: 0.25rem;
  }

  .q-score.correct {
    background: #d1fae5;
    color: #065f46;
  }

  .q-score.incorrect {
    background: #fee2e2;
    color: #991b1b;
  }

  .q-text {
    font-size: 1rem;
    font-weight: 600;
    color: var(--storm);
    margin: 0;
    line-height: 1.6;
  }

  .options-review {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .review-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.85rem 1rem;
    border-radius: 0.65rem;
    font-size: 0.9rem;
    border: 2px solid var(--line);
    background: var(--card);
    color: var(--muted);
  }

  .review-option.is-correct {
    border-color: #059669;
    background: #ecfdf5;
    color: var(--storm);
    font-weight: 600;
  }

  .review-option.is-wrong {
    border-color: #dc2626;
    background: #fef2f2;
    color: #dc2626;
  }

  .strikethrough {
    text-decoration: line-through;
  }

  .tag-correct {
    font-size: 0.75rem;
    font-weight: 700;
    color: #059669;
    display: flex;
    align-items: center;
    gap: 0.25rem;
  }

  .tag-wrong {
    font-size: 0.75rem;
    font-weight: 600;
    color: #dc2626;
  }

  .explanation-box {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 0.5rem;
    padding: 0.85rem 1rem;
    font-size: 0.85rem;
    color: var(--storm);
  }

  .explanation-box strong {
    display: block;
    margin-bottom: 0.25rem;
  }

  .explanation-box p {
    margin: 0;
    line-height: 1.6;
    color: var(--muted);
  }
</style>
