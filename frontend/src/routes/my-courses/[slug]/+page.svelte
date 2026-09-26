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
  <title>{content ? content.course.title.ar : 'محتوى المادة'} | منصة Codeera</title>
</svelte:head>

<div class="content-page" dir="rtl">
  <div class="content-container">
    {#if loading}
      <div class="loading-shell">
        <div class="spinner" aria-hidden="true"></div>
        <p>جاري تحميل محتوى المادة...</p>
      </div>
    {:else if errorMsg}
      <div class="error-card">
        <p>{errorMsg}</p>
        <a href="/courses/{slug}" class="btn-back">العودة لصفحة المادة</a>
      </div>
    {:else if content}
      <!-- Header -->
      <div class="page-header">
        <div class="header-main">
          <nav class="breadcrumbs" aria-label="مسار التنقل">
            <a href="/courses">المواد</a>
            <span class="sep">/</span>
            <a href="/courses/{slug}">{content.course.title.ar}</a>
            <span class="sep">/</span>
            <span class="current">المحتوى التعليمي</span>
          </nav>
          <h1>{content.course.title.ar}</h1>
        </div>

        {#if content.is_unlocked && content.course.telegram_invite_link}
          <a
            href={content.course.telegram_invite_link}
            target="_blank"
            rel="noopener noreferrer"
            class="btn-telegram"
          >
            انضم لمجموعة التليجرام للمادة
          </a>
        {/if}
      </div>

      <!-- Telegram Linking Reminder -->
      <TelegramLinkCard />

      <!-- Course Content Sections -->
      <section class="sections-container">
        <h2>الخطة الدراسية والمحتوى</h2>

        {#if content.sections.length === 0}
          <div class="empty-box">
            لم يتم رفع محتوى دراسي لهذه المادة بعد.
          </div>
        {:else}
          {#each content.sections as section}
            <div class="section-card">
              <div class="section-header">
                <h3>{section.title.ar}</h3>
                <span class="item-count">{section.items.length} عنصر</span>
              </div>

              {#if section.items.length === 0}
                <div class="empty-section">لا توجد عناصر في هذا القسم حالياً.</div>
              {:else}
                <div class="items-list">
                  {#each section.items as item}
                    <div class="item-row">
                      <div class="item-info">
                        <div class="item-icon" aria-hidden="true">
                          {#if item.type === 'lecture_link'}
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                              <polygon points="5 3 19 12 5 21 5 3"></polygon>
                            </svg>
                          {:else if item.type === 'file'}
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                              <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                          {:else if item.type === 'quiz' || item.type === 'exam'}
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                              <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                            </svg>
                          {:else}
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                              <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                            </svg>
                          {/if}
                        </div>
                        <div>
                          <p class="item-title">{item.title.ar}</p>
                          {#if item.description?.ar}
                            <p class="item-desc">{item.description.ar}</p>
                          {/if}
                        </div>
                      </div>

                      <div class="item-actions">
                        {#if item.is_locked}
                          <span class="locked-badge">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <span>مغلق (اشترك للفتح)</span>
                          </span>
                        {:else}
                          {#if item.type === 'lecture_link' || item.type === 'external_link'}
                            {#if item.url}
                              <a href={item.url} target="_blank" rel="noopener noreferrer" class="btn-item btn-link">
                                فتح الرابط ↗
                              </a>
                            {/if}
                          {:else if item.type === 'file'}
                            <a href="/api/v1/courses/{slug}/items/{item.id}/file" class="btn-item btn-file">
                              تحميل الملف ↓
                            </a>
                          {:else if (item.type === 'quiz' || item.type === 'exam') && item.quiz}
                            <a href="/quizzes/{item.quiz.id}" class="btn-item btn-quiz">
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
      </section>
    {/if}
  </div>
</div>

<style>
  .content-page {
    background-color: var(--paper);
    min-height: calc(100vh - 140px);
    padding: 2rem 1.25rem 4rem;
  }

  .content-container {
    max-width: 1100px;
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

  .error-card p {
    color: #991b1b;
    font-weight: 600;
    margin: 0 0 1rem;
  }

  .btn-back {
    display: inline-block;
    background: var(--brand-navy);
    color: #FAF8F5;
    padding: 0.6rem 1.25rem;
    border-radius: 0.5rem;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.9rem;
  }

  /* Page Header */
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    border-bottom: 2px solid var(--line);
    padding-bottom: 1.5rem;
  }

  .breadcrumbs {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.82rem;
    color: var(--muted);
    margin-bottom: 0.5rem;
  }

  .breadcrumbs a { color: var(--muted); text-decoration: none; font-weight: 600; }
  .breadcrumbs a:hover { color: var(--storm); text-decoration: underline; }
  .breadcrumbs .sep { color: var(--line); }
  .breadcrumbs .current { color: var(--storm); font-weight: 700; }

  .page-header h1 {
    font-size: 1.85rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .btn-telegram {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #0088cc;
    color: white;
    padding: 0.65rem 1.25rem;
    border-radius: 0.5rem;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.88rem;
    border: 2px solid #0088cc;
    transition: opacity 150ms ease;
  }

  .btn-telegram:hover { opacity: 0.9; }

  /* Sections */
  .sections-container {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .sections-container h2 {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .section-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(15, 40, 47, 0.04);
  }

  .section-header {
    background: var(--paper);
    padding: 1rem 1.25rem;
    border-bottom: 2px solid var(--line);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .section-header h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--storm);
    margin: 0;
  }

  .item-count {
    font-size: 0.78rem;
    color: var(--muted);
  }

  .empty-section, .empty-box {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--muted);
    font-size: 0.9rem;
  }

  .empty-box {
    background: var(--paper);
    border: 2px dashed var(--line);
    border-radius: 1rem;
    font-weight: 600;
  }

  /* Items */
  .items-list {
    display: flex;
    flex-direction: column;
  }

  .item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--line);
    gap: 1rem;
    flex-wrap: wrap;
    transition: background 150ms ease;
  }

  .item-row:last-child { border-bottom: none; }
  .item-row:hover { background: #f8faf9; }

  .item-info {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    flex: 1;
    min-width: 0;
  }

  .item-icon {
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(var(--brand-navy-rgb), 0.08);
    color: var(--brand-navy);
    border-radius: 0.5rem;
    font-size: 0.9rem;
    flex-shrink: 0;
  }

  .item-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--storm);
    margin: 0;
  }

  .item-desc {
    font-size: 0.8rem;
    color: var(--muted);
    margin: 0.2rem 0 0;
  }

  .item-actions {
    flex-shrink: 0;
  }

  .locked-badge {
    font-size: 0.78rem;
    color: var(--muted);
    background: var(--paper);
    border: 2px solid var(--line);
    padding: 0.3rem 0.65rem;
    border-radius: 9999px;
    font-weight: 600;
  }

  .btn-item {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.85rem;
    border-radius: 0.4rem;
    font-size: 0.82rem;
    font-weight: 700;
    text-decoration: none;
    transition: opacity 150ms ease;
  }

  .btn-item:hover { opacity: 0.85; }

  .btn-link {
    background: var(--paper);
    color: var(--deep-cyan);
    border: 2px solid var(--line);
  }

  .btn-file {
    background: var(--paper);
    color: var(--storm);
    border: 2px solid var(--line);
  }

  .btn-quiz {
    background: #059669;
    color: white;
    border: 2px solid #059669;
  }

  @media (max-width: 768px) {
    .page-header {
      flex-direction: column;
      align-items: flex-start;
    }

    .item-row {
      flex-direction: column;
      align-items: flex-start;
    }

    .item-actions {
      align-self: flex-end;
    }
  }
</style>
