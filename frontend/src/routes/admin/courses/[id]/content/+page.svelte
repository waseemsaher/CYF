<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/state';
  import { getAuthToken } from '$lib/api/client';
  import { getCurrentUser } from '$lib/api/auth';
  import AuthGuardCard from '$lib/components/AuthGuardCard.svelte';
  import {
    getAdminCourses,
    type AdminCourse,
  } from '$lib/api/admin';
  import {
    getCourseContent,
    createCourseSection,
    updateCourseSection,
    deleteCourseSection,
    createCourseItem,
    updateCourseItem,
    deleteCourseItem,
    createCourseQuiz,
    createQuizQuestion,
    deleteQuizQuestion,
    type CourseSection,
    type CourseItem,
    type CourseQuiz,
    type QuizQuestion,
    type CourseItemType,
  } from '$lib/api/admin-learning';

  const courseId = Number(page.params.id);

  let isUnauthenticated = $state(false);
  let currentRole = $state('');
  let loading = $state(true);
  let errorMsg = $state('');

  // Course Details
  let course = $state<AdminCourse | null>(null);
  let courseTitle = $state({ ar: '', en: '' });
  let courseSlug = $state('');
  let sections = $state<CourseSection[]>([]);
  let createdQuizzes = $state<CourseQuiz[]>([]);

  // Section Modal State
  let isSectionModalOpen = $state(false);
  let isEditingSection = $state(false);
  let editingSectionId = $state<number | null>(null);
  let sectionTitleAr = $state('');
  let sectionTitleEn = $state('');
  let sectionPosition = $state<number>(1);
  let sectionLoading = $state(false);
  let sectionError = $state('');

  // Item Modal State
  let isItemModalOpen = $state(false);
  let isEditingItem = $state(false);
  let targetSectionId = $state<number | null>(null);
  let editingItemId = $state<number | null>(null);
  let itemType = $state<CourseItemType>('lecture_link');
  let itemTitleAr = $state('');
  let itemTitleEn = $state('');
  let itemDescAr = $state('');
  let itemDescEn = $state('');
  let itemUrl = $state('');
  let itemTelegramMessageId = $state<string>('');
  let itemIsFree = $state<boolean>(false);
  let itemQuizId = $state<number | null>(null);
  let itemPosition = $state<number>(1);
  let itemIsPublished = $state<boolean>(true);
  let itemFile = $state<File | null>(null);
  let itemLoading = $state(false);
  let itemError = $state('');

  // Quiz Modal State
  let isQuizModalOpen = $state(false);
  let quizKind = $state<'quiz' | 'exam'>('quiz');
  let quizTitleAr = $state('');
  let quizTitleEn = $state('');
  let quizDuration = $state<number>(30);
  let quizMaxAttempts = $state<number>(1);
  let quizShuffleQuestions = $state<boolean>(false);
  let quizShuffleOptions = $state<boolean>(false);
  let quizResultsVisibility = $state<'immediate' | 'after_close' | 'hidden'>('immediate');
  let quizLoading = $state(false);
  let quizError = $state('');

  // Question Modal State
  let isQuestionModalOpen = $state(false);
  let activeQuizForQuestions = $state<CourseQuiz | null>(null);
  let quizQuestionsList = $state<QuizQuestion[]>([]);
  let questionType = $state<'mcq' | 'true_false'>('mcq');
  let questionTextAr = $state('');
  let questionTextEn = $state('');
  let questionExplanationAr = $state('');
  let questionExplanationEn = $state('');
  let questionPoints = $state<number>(1);
  let mcqOptions = $state<Array<{ textAr: string; textEn: string; isCorrect: boolean }>>([
    { textAr: '', textEn: '', isCorrect: true },
    { textAr: '', textEn: '', isCorrect: false },
  ]);
  let trueFalseCorrect = $state<'true' | 'false'>('true');
  let questionLoading = $state(false);
  let questionError = $state('');

  async function loadData() {
    loading = true;
    errorMsg = '';

    const token = getAuthToken();
    if (!token) {
      isUnauthenticated = true;
      loading = false;
      return;
    }

    try {
      const userRes = await getCurrentUser(fetch).catch(() => null);
      if (userRes?.data?.user) {
        currentRole = userRes.data.user.role || '';
      }

      // 1. Find course metadata to get slug
      const coursesRes = await getAdminCourses(fetch, 1);
      const found = coursesRes.data?.find((c) => c.id === courseId);
      if (found) {
        course = found;
        courseSlug = found.slug;
        courseTitle = found.title;
      } else {
        const urlSlug = page.url.searchParams.get('slug');
        if (urlSlug) {
          courseSlug = urlSlug;
        }
      }

      if (!courseSlug) {
        errorMsg = 'لم يتم العثور على المقرر المطلوب.';
        loading = false;
        return;
      }

      // 2. Fetch course content outline via CourseContentController
      const contentRes = await getCourseContent(fetch, courseSlug);
      sections = contentRes.data?.sections || [];
      if (contentRes.data?.course) {
        courseTitle = contentRes.data.course.title;
      }

      // Collect any quizzes already referenced in items
      const quizzesMap = new Map<number, CourseQuiz>();
      for (const sec of sections) {
        for (const it of sec.items) {
          if (it.quiz && it.quiz.id) {
            quizzesMap.set(it.quiz.id, it.quiz);
          }
        }
      }
      for (const q of createdQuizzes) {
        quizzesMap.set(q.id, q);
      }
      createdQuizzes = Array.from(quizzesMap.values());
    } catch (err: any) {
      errorMsg = err?.message || 'تعذر تحميل محتوى المقرر.';
    } finally {
      loading = false;
    }
  }

  // ================= Section Handlers =================

  function openCreateSectionModal() {
    isEditingSection = false;
    editingSectionId = null;
    sectionTitleAr = '';
    sectionTitleEn = '';
    sectionPosition = sections.length + 1;
    sectionError = '';
    isSectionModalOpen = true;
  }

  function openEditSectionModal(sec: CourseSection) {
    isEditingSection = true;
    editingSectionId = sec.id;
    sectionTitleAr = sec.title?.ar || '';
    sectionTitleEn = sec.title?.en || '';
    sectionPosition = sec.position;
    sectionError = '';
    isSectionModalOpen = true;
  }

  function closeSectionModal() {
    isSectionModalOpen = false;
    sectionError = '';
  }

  async function handleSaveSection(e: SubmitEvent) {
    e.preventDefault();
    sectionLoading = true;
    sectionError = '';

    try {
      if (isEditingSection && editingSectionId) {
        await updateCourseSection(fetch, editingSectionId, {
          title: {
            ar: sectionTitleAr.trim(),
            en: sectionTitleEn.trim() || sectionTitleAr.trim(),
          },
          position: Number(sectionPosition),
        });
      } else {
        await createCourseSection(fetch, courseId, {
          title: {
            ar: sectionTitleAr.trim(),
            en: sectionTitleEn.trim() || sectionTitleAr.trim(),
          },
          position: Number(sectionPosition),
        });
      }
      closeSectionModal();
      await loadData();
    } catch (err: any) {
      sectionError = err?.message || 'تعذر حفظ الفصل.';
    } finally {
      sectionLoading = false;
    }
  }

  async function handleDeleteSection(sec: CourseSection) {
    if (!confirm(`هل أنت متأكد من حذف فصل "${sec.title.ar}"؟ سيتم حذف جميع الدروس بداخله.`)) {
      return;
    }
    try {
      await deleteCourseSection(fetch, sec.id);
      await loadData();
    } catch (err: any) {
      alert(err?.message || 'تعذر حذف الفصل.');
    }
  }

  async function handleMoveSection(sec: CourseSection, direction: 'up' | 'down') {
    const newPos = direction === 'up' ? Math.max(0, sec.position - 1) : sec.position + 1;
    try {
      await updateCourseSection(fetch, sec.id, { position: newPos });
      await loadData();
    } catch (err: any) {
      alert(err?.message || 'تعذر إعادة ترتيب الفصل.');
    }
  }

  // ================= Item Handlers =================

  function openCreateItemModal(secId: number) {
    isEditingItem = false;
    targetSectionId = secId;
    editingItemId = null;
    itemType = 'lecture_link';
    itemTitleAr = '';
    itemTitleEn = '';
    itemDescAr = '';
    itemDescEn = '';
    itemUrl = '';
    itemTelegramMessageId = '';
    itemIsFree = false;
    itemQuizId = createdQuizzes.length > 0 ? createdQuizzes[0].id : null;
    const sec = sections.find((s) => s.id === secId);
    itemPosition = (sec?.items?.length || 0) + 1;
    itemIsPublished = true;
    itemFile = null;
    itemError = '';
    isItemModalOpen = true;
  }

  function openEditItemModal(item: CourseItem) {
    isEditingItem = true;
    editingItemId = item.id;
    targetSectionId = item.section_id || null;
    itemType = item.type;
    itemTitleAr = item.title?.ar || '';
    itemTitleEn = item.title?.en || '';
    itemDescAr = item.description?.ar || '';
    itemDescEn = item.description?.en || '';
    itemUrl = item.url || '';
    itemTelegramMessageId = item.telegram_message_id ? String(item.telegram_message_id) : '';
    itemIsFree = Boolean(item.is_free);
    itemQuizId = item.quiz?.id || null;
    itemPosition = item.position;
    itemIsPublished = item.is_published !== false;
    itemFile = null;
    itemError = '';
    isItemModalOpen = true;
  }

  function closeItemModal() {
    isItemModalOpen = false;
    itemError = '';
  }

  async function handleSaveItem(e: SubmitEvent) {
    e.preventDefault();
    if (!targetSectionId && !isEditingItem) return;

    itemLoading = true;
    itemError = '';

    try {
      const rawTelId = itemTelegramMessageId ? String(itemTelegramMessageId).trim() : '';
      const telId = rawTelId && !isNaN(Number(rawTelId)) ? Number(rawTelId) : null;

      if (isEditingItem && editingItemId) {
        await updateCourseItem(fetch, editingItemId, {
          title: {
            ar: itemTitleAr.trim(),
            en: itemTitleEn.trim() || itemTitleAr.trim(),
          },
          description: itemDescAr || itemDescEn ? {
            ar: itemDescAr.trim(),
            en: itemDescEn.trim() || itemDescAr.trim(),
          } : undefined,
          url: itemUrl.trim() || null,
          telegram_message_id: telId,
          is_free: itemIsFree,
          position: Number(itemPosition),
          is_published: itemIsPublished,
        });
      } else {
        const createRes = await createCourseItem(fetch, courseId, targetSectionId!, {
          type: itemType,
          title: {
            ar: itemTitleAr.trim(),
            en: itemTitleEn.trim() || itemTitleAr.trim(),
          },
          description: itemDescAr || itemDescEn ? {
            ar: itemDescAr.trim(),
            en: itemDescEn.trim() || itemDescAr.trim(),
          } : undefined,
          url: itemUrl.trim() || null,
          file: itemFile,
          quiz_id: (itemType === 'quiz' || itemType === 'exam') ? itemQuizId : null,
          is_free: itemIsFree,
          telegram_message_id: telId,
          position: Number(itemPosition),
          is_published: itemIsPublished,
        });

        if (createRes?.data?.id && telId) {
          await updateCourseItem(fetch, createRes.data.id, {
            telegram_message_id: telId,
          });
        }
      }

      closeItemModal();
      await loadData();
    } catch (err: any) {
      itemError = err?.message || 'تعذر حفظ العنصر.';
    } finally {
      itemLoading = false;
    }
  }

  async function handleDeleteItem(item: CourseItem) {
    if (!confirm(`هل أنت متأكد من حذف الدرس "${item.title.ar}"؟`)) {
      return;
    }
    try {
      await deleteCourseItem(fetch, item.id);
      await loadData();
    } catch (err: any) {
      alert(err?.message || 'تعذر حذف الدرس.');
    }
  }

  async function handleTogglePublish(item: CourseItem) {
    try {
      await updateCourseItem(fetch, item.id, {
        is_published: !item.is_published,
      });
      await loadData();
    } catch (err: any) {
      alert(err?.message || 'تعذر تغيير حالة النشر.');
    }
  }

  // ================= Quiz Handlers =================

  function openCreateQuizModal() {
    quizKind = 'quiz';
    quizTitleAr = '';
    quizTitleEn = '';
    quizDuration = 30;
    quizMaxAttempts = 1;
    quizShuffleQuestions = false;
    quizShuffleOptions = false;
    quizResultsVisibility = 'immediate';
    quizError = '';
    isQuizModalOpen = true;
  }

  function closeQuizModal() {
    isQuizModalOpen = false;
    quizError = '';
  }

  async function handleSaveQuiz(e: SubmitEvent) {
    e.preventDefault();
    quizLoading = true;
    quizError = '';

    try {
      const res = await createCourseQuiz(fetch, courseId, {
        kind: quizKind,
        title: {
          ar: quizTitleAr.trim(),
          en: quizTitleEn.trim() || quizTitleAr.trim(),
        },
        duration_minutes: Number(quizDuration) || null,
        max_attempts: Number(quizMaxAttempts) || 1,
        shuffle_questions: quizShuffleQuestions,
        shuffle_options: quizShuffleOptions,
        results_visibility: quizResultsVisibility,
      });

      const newQuiz = res.data;
      createdQuizzes = [...createdQuizzes, newQuiz];
      closeQuizModal();

      // Automatically offer to add questions to this new quiz
      openQuestionsModal(newQuiz);
    } catch (err: any) {
      quizError = err?.message || 'تعذر إنشاء الاختبار.';
    } finally {
      quizLoading = false;
    }
  }

  // ================= Question Handlers =================

  function openQuestionsModal(quiz: CourseQuiz) {
    activeQuizForQuestions = quiz;
    questionType = 'mcq';
    questionTextAr = '';
    questionTextEn = '';
    questionExplanationAr = '';
    questionExplanationEn = '';
    questionPoints = 1;
    mcqOptions = [
      { textAr: '', textEn: '', isCorrect: true },
      { textAr: '', textEn: '', isCorrect: false },
    ];
    trueFalseCorrect = 'true';
    questionError = '';
    isQuestionModalOpen = true;
  }

  function closeQuestionModal() {
    isQuestionModalOpen = false;
    activeQuizForQuestions = null;
    questionError = '';
  }

  function addMcqOption() {
    mcqOptions = [...mcqOptions, { textAr: '', textEn: '', isCorrect: false }];
  }

  function removeMcqOption(index: number) {
    if (mcqOptions.length <= 2) return;
    mcqOptions = mcqOptions.filter((_, i) => i !== index);
  }

  function setCorrectMcqOption(index: number) {
    mcqOptions = mcqOptions.map((opt, i) => ({
      ...opt,
      isCorrect: i === index,
    }));
  }

  async function handleAddQuestion(e: SubmitEvent) {
    e.preventDefault();
    if (!activeQuizForQuestions) return;

    questionLoading = true;
    questionError = '';

    try {
      let optionsPayload: Array<{ text: { ar: string; en: string }; is_correct: boolean }> = [];

      if (questionType === 'mcq') {
        optionsPayload = mcqOptions.map((opt) => ({
          text: {
            ar: opt.textAr.trim(),
            en: opt.textEn.trim() || opt.textAr.trim(),
          },
          is_correct: opt.isCorrect,
        }));
      } else {
        optionsPayload = [
          {
            text: { ar: 'صواب', en: 'True' },
            is_correct: trueFalseCorrect === 'true',
          },
          {
            text: { ar: 'خطأ', en: 'False' },
            is_correct: trueFalseCorrect === 'false',
          },
        ];
      }

      const res = await createQuizQuestion(fetch, activeQuizForQuestions.id, {
        type: questionType,
        text: {
          ar: questionTextAr.trim(),
          en: questionTextEn.trim() || questionTextAr.trim(),
        },
        explanation: questionExplanationAr ? {
          ar: questionExplanationAr.trim(),
          en: questionExplanationEn.trim() || questionExplanationAr.trim(),
        } : undefined,
        points: Number(questionPoints) || 1,
        options: optionsPayload,
      });

      quizQuestionsList = [...quizQuestionsList, res.data];

      // Reset question inputs for next question
      questionTextAr = '';
      questionTextEn = '';
      questionExplanationAr = '';
      questionExplanationEn = '';
      mcqOptions = [
        { textAr: '', textEn: '', isCorrect: true },
        { textAr: '', textEn: '', isCorrect: false },
      ];
    } catch (err: any) {
      questionError = err?.message || 'تعذر إضافة السؤال.';
    } finally {
      questionLoading = false;
    }
  }

  async function handleDeleteQuestion(qId: number) {
    try {
      await deleteQuizQuestion(fetch, qId);
      quizQuestionsList = quizQuestionsList.filter((q) => q.id !== qId);
    } catch (err: any) {
      alert(err?.message || 'تعذر حذف السؤال.');
    }
  }

  onMount(() => {
    loadData();
  });
</script>

<svelte:head>
  <title>محرر محتوى المقرر | لوحة تحكم المسؤول</title>
</svelte:head>

{#if isUnauthenticated || (currentRole && currentRole !== 'admin' && currentRole !== 'superadmin')}
  <AuthGuardCard
    requiredRole="admin"
    {isUnauthenticated}
    {currentRole}
    onRetry={loadData}
  />
{:else if loading}
  <div class="content-loading-shell" dir="rtl">
    <div class="spinner" aria-hidden="true"></div>
    <p>جاري تحميل محتوى وفصول المقرر الدراسي...</p>
  </div>
{:else if errorMsg}
  <div class="content-error-shell" dir="rtl">
    <div class="error-box">
      <p>{errorMsg}</p>
      <a href="/admin" class="btn-back">العودة للوحة الإدارة</a>
    </div>
  </div>
{:else}
  <div class="content-editor-page" dir="rtl">
    <div class="editor-container">
      <!-- Breadcrumbs -->
      <nav class="breadcrumbs" aria-label="مسار التنقل">
        <a href="/">الرئيسية</a>
        <span class="sep">/</span>
        <a href="/admin">لوحة تحكم المسؤول</a>
        <span class="sep">/</span>
        <span class="current">محرر المحتوى: {courseTitle.ar || courseSlug}</span>
      </nav>

      <!-- Header Section -->
      <header class="editor-header">
        <div class="header-titles">
          <div class="badge-row">
            <span class="badge-course">مقرر دراسي</span>
            <span class="badge-slug" dir="ltr">{courseSlug}</span>
          </div>
          <h1>{courseTitle.ar}</h1>
          {#if courseTitle.en}
            <small class="en-sub" dir="ltr">{courseTitle.en}</small>
          {/if}
        </div>

        <div class="header-actions">
          <button
            type="button"
            class="btn-primary-action"
            onclick={openCreateSectionModal}
            data-testid="btn-add-section"
          >
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>إضافة فصل جديد</span>
          </button>

          <button
            type="button"
            class="btn-quiz-action"
            onclick={openCreateQuizModal}
            data-testid="btn-add-quiz"
          >
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="12" y1="18" x2="12" y2="12"></line>
              <line x1="9" y1="15" x2="15" y2="15"></line>
            </svg>
            <span>إنشاء اختبار / امتحان</span>
          </button>

          <a href={`/courses/${courseSlug}`} target="_blank" rel="noreferrer" class="btn-preview-course">
            معاينة المقرر ↗
          </a>
        </div>
      </header>

      <!-- Sections & Items Tree -->
      <main class="content-tree-view">
        {#if sections.length === 0}
          <div class="empty-sections-box">
            <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
            </svg>
            <h3>لا توجد فصول دراسية مضافة حتى الآن</h3>
            <p>ابدأ بإضافة أول فصل (Section) لتقسيم المقرر إلى محاضرات واختبارات.</p>
            <button type="button" class="btn-primary-action" onclick={openCreateSectionModal}>
              إضافة الفصل الأول
            </button>
          </div>
        {:else}
          <div class="sections-accordion">
            {#each sections as section, sIndex (section.id)}
              <div class="section-card" data-testid={`section-card-${section.id}`}>
                <div class="section-card-header">
                  <div class="section-title-group">
                    <span class="section-index-badge">الفصل {sIndex + 1}</span>
                    <h2 class="section-title">{section.title.ar}</h2>
                    {#if section.title.en}
                      <small class="section-title-en" dir="ltr">{section.title.en}</small>
                    {/if}
                    <span class="items-count-badge">{section.items.length} عنصر</span>
                  </div>

                  <div class="section-actions">
                    <button
                      type="button"
                      class="btn-icon"
                      onclick={() => handleMoveSection(section, 'up')}
                      title="تحريك لأعلى"
                      aria-label="تحريك لأعلى"
                    >
                      ▲
                    </button>
                    <button
                      type="button"
                      class="btn-icon"
                      onclick={() => handleMoveSection(section, 'down')}
                      title="تحريك لأسفل"
                      aria-label="تحريك لأسفل"
                    >
                      ▼
                    </button>
                    <button
                      type="button"
                      class="btn-section-action"
                      onclick={() => openCreateItemModal(section.id)}
                      data-testid={`btn-add-item-${section.id}`}
                    >
                      + إضافة درس / عنصر
                    </button>
                    <button
                      type="button"
                      class="btn-section-edit"
                      onclick={() => openEditSectionModal(section)}
                    >
                      تعديل
                    </button>
                    <button
                      type="button"
                      class="btn-section-delete"
                      onclick={() => handleDeleteSection(section)}
                      title="حذف الفصل"
                      aria-label="حذف الفصل"
                    >
                      🗑
                    </button>
                  </div>
                </div>

                <!-- Items inside Section -->
                <div class="section-items-list">
                  {#if section.items.length === 0}
                    <div class="empty-items-row">
                      <p>لا توجد دروس أو عناصر في هذا الفصل بعد.</p>
                      <button
                        type="button"
                        class="btn-text-cyan"
                        onclick={() => openCreateItemModal(section.id)}
                      >
                        + إضافة أول درس
                      </button>
                    </div>
                  {:else}
                    {#each section.items as item (item.id)}
                      <div class="item-row" class:is-draft={item.is_published === false} data-testid={`item-row-${item.id}`}>
                        <div class="item-type-icon-col">
                          {#if item.type === 'lecture_link'}
                            <span class="type-icon icon-video" title="محاضرة فيديو">🎬</span>
                          {:else if item.type === 'file'}
                            <span class="type-icon icon-file" title="ملف مرفق">📄</span>
                          {:else if item.type === 'quiz' || item.type === 'exam'}
                            <span class="type-icon icon-quiz" title="اختبار">📝</span>
                          {:else}
                            <span class="type-icon icon-link" title="رابط">🔗</span>
                          {/if}
                        </div>

                        <div class="item-info-col">
                          <div class="item-header-line">
                            <strong class="item-title">{item.title.ar}</strong>
                            {#if item.title.en}
                              <small class="item-title-en" dir="ltr">{item.title.en}</small>
                            {/if}
                            {#if item.is_free}
                              <span class="free-preview-pill" title="متاح كمعاينة مجانية لجميع الطلاب">
                                ★ معاينة مجانية
                              </span>
                            {/if}
                            <span class="item-status-pill {item.is_published !== false ? 'pill-pub' : 'pill-draft'}">
                              {item.is_published !== false ? 'منشور' : 'مسودة'}
                            </span>
                          </div>

                          <div class="item-meta-line">
                            <span class="meta-type-tag">{item.type}</span>

                            {#if item.telegram_message_id}
                              <span class="meta-telegram-badge" title="معرّف رسالة تليجرام للبث الخاص">
                                تليجرام: #{item.telegram_message_id}
                              </span>
                            {/if}

                            {#if item.url}
                              <a href={item.url} target="_blank" rel="noreferrer" class="meta-link" dir="ltr">
                                {item.url.length > 40 ? item.url.slice(0, 40) + '...' : item.url}
                              </a>
                            {/if}

                            {#if item.quiz}
                              <button
                                type="button"
                                class="btn-manage-questions"
                                onclick={() => openQuestionsModal(item.quiz!)}
                              >
                                إدارة أسئلة الاختبار ({item.quiz.kind})
                              </button>
                            {/if}
                          </div>
                        </div>

                        <div class="item-actions-col">
                          <button
                            type="button"
                            class="btn-item-toggle"
                            onclick={() => handleTogglePublish(item)}
                            title={item.is_published !== false ? 'تعطيل ونقل للمسودة' : 'نشر للطلاب'}
                          >
                            {item.is_published !== false ? 'إخفاء' : 'نشر'}
                          </button>
                          <button
                            type="button"
                            class="btn-item-edit"
                            onclick={() => openEditItemModal(item)}
                          >
                            تعديل
                          </button>
                          <button
                            type="button"
                            class="btn-item-del"
                            onclick={() => handleDeleteItem(item)}
                            aria-label="حذف العنصر"
                          >
                            ×
                          </button>
                        </div>
                      </div>
                    {/each}
                  {/if}
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </main>
    </div>
  </div>
{/if}

<!-- Modal: Create / Edit Section -->
{#if isSectionModalOpen}
  <div class="modal-backdrop" onclick={closeSectionModal} role="presentation">
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <div class="modal-card" onclick={(e) => e.stopPropagation()} role="dialog" aria-modal="true" tabindex="-1" dir="rtl">
      <div class="modal-header">
        <h3>{isEditingSection ? 'تعديل بيانات الفصل' : 'إضافة فصل دراسي جديد'}</h3>
        <button type="button" class="btn-close" onclick={closeSectionModal}>&times;</button>
      </div>

      {#if sectionError}
        <div class="modal-error-banner" role="alert"><p>{sectionError}</p></div>
      {/if}

      <form class="modal-form" onsubmit={handleSaveSection}>
        <div class="form-group">
          <label for="sec-title-ar">اسم الفصل بالعربية *</label>
          <input
            id="sec-title-ar"
            type="text"
            bind:value={sectionTitleAr}
            required
            placeholder="مثال: الفصل الأول: مقدمة في هياكل البيانات"
            data-testid="input-section-title-ar"
          />
        </div>

        <div class="form-group">
          <label for="sec-title-en">اسم الفصل بالإنجليزية</label>
          <input
            id="sec-title-en"
            type="text"
            dir="ltr"
            bind:value={sectionTitleEn}
            placeholder="e.g. Chapter 1: Introduction to Data Structures"
            data-testid="input-section-title-en"
          />
        </div>

        <div class="form-group">
          <label for="sec-position">الترتيب الرقمي (Position)</label>
          <input
            id="sec-position"
            type="number"
            min="0"
            bind:value={sectionPosition}
            data-testid="input-section-position"
          />
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick={closeSectionModal}>إلغاء</button>
          <button type="submit" class="btn-submit" disabled={sectionLoading} data-testid="btn-submit-section">
            {sectionLoading ? 'جاري الحفظ...' : 'حفظ الفصل'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<!-- Modal: Create / Edit Item -->
{#if isItemModalOpen}
  <div class="modal-backdrop" onclick={closeItemModal} role="presentation">
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <div class="modal-card" onclick={(e) => e.stopPropagation()} role="dialog" aria-modal="true" tabindex="-1" dir="rtl">
      <div class="modal-header">
        <h3>{isEditingItem ? 'تعديل بيانات الدرس / العنصر' : 'إضافة درس أو عنصر جديد'}</h3>
        <button type="button" class="btn-close" onclick={closeItemModal}>&times;</button>
      </div>

      {#if itemError}
        <div class="modal-error-banner" role="alert"><p>{itemError}</p></div>
      {/if}

      <form class="modal-form" onsubmit={handleSaveItem}>
        {#if !isEditingItem}
          <div class="form-group">
            <label for="item-type-select">نوع المحتوى *</label>
            <select id="item-type-select" bind:value={itemType} data-testid="select-item-type">
              <option value="lecture_link">محاضرة فيديو (رابط خارجي أو يوتيوب)</option>
              <option value="file">ملف مرفق للتحميل (PDF أو مستندات)</option>
              <option value="quiz">اختبار قصير (Quiz)</option>
              <option value="exam">امتحان شامل (Exam)</option>
              <option value="external_link">رابط موقع خارجي</option>
              <option value="text">محتوى نصي وملاحظات</option>
            </select>
          </div>
        {/if}

        <div class="form-group">
          <label for="item-title-ar">عنوان العنصر بالعربية *</label>
          <input
            id="item-title-ar"
            type="text"
            bind:value={itemTitleAr}
            required
            placeholder="مثال: شرح المصفوفات والقوائم المترابطة"
            data-testid="input-item-title-ar"
          />
        </div>

        <div class="form-group">
          <label for="item-title-en">عنوان العنصر بالإنجليزية</label>
          <input
            id="item-title-en"
            type="text"
            dir="ltr"
            bind:value={itemTitleEn}
            placeholder="e.g. Arrays and Linked Lists"
            data-testid="input-item-title-en"
          />
        </div>

        {#if itemType === 'lecture_link' || itemType === 'external_link'}
          <div class="form-group">
            <label for="item-url">رابط المحاضرة / الفيديو (URL)</label>
            <input
              id="item-url"
              type="url"
              dir="ltr"
              bind:value={itemUrl}
              placeholder="https://..."
              data-testid="input-item-url"
            />
          </div>
        {/if}

        <div class="form-group">
          <label for="item-tel-msg-id">معرّف رسالة تليجرام (Telegram Message ID)</label>
          <input
            id="item-tel-msg-id"
            type="number"
            min="1"
            dir="ltr"
            bind:value={itemTelegramMessageId}
            placeholder="مثال: 4821"
            data-testid="input-item-telegram-id"
          />
          <small class="hint-text">مطلوب لبث المحاضرات المحمية عبر بوت تليجرام لمشاهدة الفيديو بالمنصة.</small>
        </div>

        {#if (itemType === 'quiz' || itemType === 'exam') && !isEditingItem}
          <div class="form-group">
            <label for="item-quiz-select">الاختبار المرتبط *</label>
            {#if createdQuizzes.length === 0}
              <p class="warning-text">لا توجد اختبارات منشأة في هذا المقرر بعد. يرجى إنشاء اختبار أولاً.</p>
            {:else}
              <select id="item-quiz-select" bind:value={itemQuizId} required data-testid="select-item-quiz">
                {#each createdQuizzes as q}
                  <option value={q.id}>{q.title.ar} ({q.kind})</option>
                {/each}
              </select>
            {/if}
          </div>
        {/if}

        {#if itemType === 'file' && !isEditingItem}
          <div class="form-group">
            <label for="item-file-upload">الملف المرفق *</label>
            <input
              id="item-file-upload"
              type="file"
              onchange={(e) => {
                const target = e.currentTarget as HTMLInputElement;
                itemFile = target.files && target.files[0] ? target.files[0] : null;
              }}
              data-testid="input-item-file"
            />
          </div>
        {/if}

        <div class="form-group">
          <label for="item-desc-ar">الوصف أو الملاحظات (اختياري)</label>
          <textarea
            id="item-desc-ar"
            rows="2"
            bind:value={itemDescAr}
            placeholder="ملاحظات توضيحية حول الدرس..."
            data-testid="input-item-desc-ar"
          ></textarea>
        </div>

        <div class="form-group checkbox-highlight-box">
          <label class="checkbox-label">
            <input type="checkbox" bind:checked={itemIsFree} data-testid="input-item-is-free" />
            <div>
              <strong>معاينة مجانية للجميع (Free Preview)</strong>
              <p class="checkbox-subtext">
                عند تفعيل هذا الخيار، سيتمكن أي طالب أو زائر من مشاهدة هذا الفيديو أو فتح الرابط حتى لو لم يكن مسجلاً في المقرر.
              </p>
            </div>
          </label>
        </div>

        <div class="form-row-compact">
          <div class="form-group">
            <label for="item-position">الترتيب</label>
            <input id="item-position" type="number" min="0" bind:value={itemPosition} />
          </div>

          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input type="checkbox" bind:checked={itemIsPublished} data-testid="input-item-published" />
              <span>نشر هذا العنصر للطلاب فوراً</span>
            </label>
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick={closeItemModal}>إلغاء</button>
          <button type="submit" class="btn-submit" disabled={itemLoading} data-testid="btn-submit-item">
            {itemLoading ? 'جاري الحفظ...' : 'حفظ العنصر'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<!-- Modal: Create Quiz -->
{#if isQuizModalOpen}
  <div class="modal-backdrop" onclick={closeQuizModal} role="presentation">
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <div class="modal-card" onclick={(e) => e.stopPropagation()} role="dialog" aria-modal="true" tabindex="-1" dir="rtl">
      <div class="modal-header">
        <h3>إنشاء اختبار أو امتحان للمقرر</h3>
        <button type="button" class="btn-close" onclick={closeQuizModal}>&times;</button>
      </div>

      {#if quizError}
        <div class="modal-error-banner" role="alert"><p>{quizError}</p></div>
      {/if}

      <form class="modal-form" onsubmit={handleSaveQuiz}>
        <div class="form-group">
          <label for="quiz-kind-select">نوع التقييم *</label>
          <select id="quiz-kind-select" bind:value={quizKind} data-testid="select-quiz-kind">
            <option value="quiz">اختبار قصير دوري (Quiz)</option>
            <option value="exam">امتحان شامل / نهائي (Exam)</option>
          </select>
        </div>

        <div class="form-group">
          <label for="quiz-title-ar">عنوان الاختبار بالعربية *</label>
          <input
            id="quiz-title-ar"
            type="text"
            bind:value={quizTitleAr}
            required
            placeholder="مثال: اختبار الفصول الثلاثة الأولى"
            data-testid="input-quiz-title-ar"
          />
        </div>

        <div class="form-group">
          <label for="quiz-title-en">عنوان الاختبار بالإنجليزية</label>
          <input
            id="quiz-title-en"
            type="text"
            dir="ltr"
            bind:value={quizTitleEn}
            placeholder="e.g. Midterm Evaluation Quiz"
            data-testid="input-quiz-title-en"
          />
        </div>

        <div class="form-row-compact">
          <div class="form-group">
            <label for="quiz-duration">المدة (بالدقائق)</label>
            <input
              id="quiz-duration"
              type="number"
              min="1"
              bind:value={quizDuration}
              data-testid="input-quiz-duration"
            />
          </div>

          <div class="form-group">
            <label for="quiz-attempts">المحاولات المسموحة</label>
            <input
              id="quiz-attempts"
              type="number"
              min="1"
              bind:value={quizMaxAttempts}
              data-testid="input-quiz-attempts"
            />
          </div>
        </div>

        <div class="form-group">
          <label for="quiz-visibility">رؤية النتائج للطلاب</label>
          <select id="quiz-visibility" bind:value={quizResultsVisibility}>
            <option value="immediate">مباشرة فور التسليم مع التصحيح</option>
            <option value="after_close">بعد إغلاق موعد الاختبار</option>
            <option value="hidden">مخفية (للمعلم فقط)</option>
          </select>
        </div>

        <div class="checkbox-row">
          <label class="checkbox-label">
            <input type="checkbox" bind:checked={quizShuffleQuestions} />
            <span>خلط ترتيب الأسئلة عشوائياً</span>
          </label>
          <label class="checkbox-label">
            <input type="checkbox" bind:checked={quizShuffleOptions} />
            <span>خلط ترتيب الاختيارات عشوائياً</span>
          </label>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick={closeQuizModal}>إلغاء</button>
          <button type="submit" class="btn-submit" disabled={quizLoading} data-testid="btn-submit-quiz">
            {quizLoading ? 'جاري الإنشاء...' : 'إنشاء ومتابعة للأسئلة'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<!-- Modal: Quiz Questions Builder -->
{#if isQuestionModalOpen && activeQuizForQuestions}
  <div class="modal-backdrop" onclick={closeQuestionModal} role="presentation">
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <div
      class="modal-card modal-card-large"
      onclick={(e) => e.stopPropagation()}
      role="dialog"
      aria-modal="true"
      tabindex="-1"
      dir="rtl"
    >
      <div class="modal-header">
        <div>
          <h3>إدارة أسئلة: {activeQuizForQuestions.title.ar}</h3>
          <small style="color: rgba(255,255,255,0.6);">
            {activeQuizForQuestions.kind} • مدة {activeQuizForQuestions.duration_minutes || 0} دقيقة
          </small>
        </div>
        <button type="button" class="btn-close" onclick={closeQuestionModal}>&times;</button>
      </div>

      {#if questionError}
        <div class="modal-error-banner" role="alert"><p>{questionError}</p></div>
      {/if}

      <!-- List of added questions in this session -->
      {#if quizQuestionsList.length > 0}
        <div class="questions-list-preview">
          <h4>الأسئلة المضافة ({quizQuestionsList.length}):</h4>
          {#each quizQuestionsList as q, qIdx}
            <div class="question-preview-item">
              <div>
                <strong>س{qIdx + 1}: {q.text.ar}</strong>
                <span class="points-badge">({q.points} درجات)</span>
              </div>
              <button
                type="button"
                class="btn-del-question"
                onclick={() => handleDeleteQuestion(q.id)}
                title="حذف السؤال"
              >
                حذف
              </button>
            </div>
          {/each}
        </div>
      {/if}

      <!-- Form to add new question -->
      <form class="modal-form question-form" onsubmit={handleAddQuestion}>
        <div class="form-row-compact">
          <div class="form-group">
            <label for="q-type">نوع السؤال</label>
            <select id="q-type" bind:value={questionType} data-testid="select-question-type">
              <option value="mcq">اختيار من متعدد (MCQ)</option>
              <option value="true_false">صح أو خطأ (True / False)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="q-points">الدرجات</label>
            <input
              id="q-points"
              type="number"
              min="1"
              bind:value={questionPoints}
              data-testid="input-question-points"
            />
          </div>
        </div>

        <div class="form-group">
          <label for="q-text-ar">نص السؤال بالعربية *</label>
          <textarea
            id="q-text-ar"
            rows="2"
            bind:value={questionTextAr}
            required
            placeholder="مثال: ما هو التعقيد الزمني لخوارزمية البحث الثنائي؟"
            data-testid="input-question-text-ar"
          ></textarea>
        </div>

        <div class="form-group">
          <label for="q-text-en">نص السؤال بالإنجليزية</label>
          <textarea
            id="q-text-en"
            rows="1"
            dir="ltr"
            bind:value={questionTextEn}
            placeholder="What is the time complexity of binary search?"
            data-testid="input-question-text-en"
          ></textarea>
        </div>

        <!-- Options for MCQ -->
        {#if questionType === 'mcq'}
          <div class="options-builder-box">
            <div class="options-header">
              <label>خيارات الإجابة (حدد الإجابة الصحيحة بالدائرة) *</label>
              <button type="button" class="btn-text-action" onclick={addMcqOption}>+ إضافة خيار</button>
            </div>

            {#each mcqOptions as opt, optIdx}
              <div class="option-row">
                <input
                  type="radio"
                  name="mcq-correct"
                  checked={opt.isCorrect}
                  onchange={() => setCorrectMcqOption(optIdx)}
                  title="الإجابة الصحيحة"
                  data-testid={`radio-correct-${optIdx}`}
                />
                <input
                  type="text"
                  bind:value={opt.textAr}
                  required
                  placeholder={`الخيار ${optIdx + 1} بالعربية`}
                  data-testid={`input-option-ar-${optIdx}`}
                />
                <input
                  type="text"
                  dir="ltr"
                  bind:value={opt.textEn}
                  placeholder={`Option ${optIdx + 1} (EN)`}
                />
                {#if mcqOptions.length > 2}
                  <button
                    type="button"
                    class="btn-remove-opt"
                    onclick={() => removeMcqOption(optIdx)}
                    title="حذف الخيار"
                  >
                    ×
                  </button>
                {/if}
              </div>
            {/each}
          </div>
        {:else}
          <div class="true-false-box">
            <label>الإجابة الصحيحة *</label>
            <div class="radio-choices">
              <label class="radio-label">
                <input type="radio" bind:group={trueFalseCorrect} value="true" />
                <span>صواب (True)</span>
              </label>
              <label class="radio-label">
                <input type="radio" bind:group={trueFalseCorrect} value="false" />
                <span>خطأ (False)</span>
              </label>
            </div>
          </div>
        {/if}

        <div class="form-group">
          <label for="q-exp-ar">شرح الإجابة والتعليل (اختياري)</label>
          <input
            id="q-exp-ar"
            type="text"
            bind:value={questionExplanationAr}
            placeholder="يظهر للطالب بعد تسليم الاختبار..."
          />
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick={closeQuestionModal}>إنهاء وإغلاق</button>
          <button type="submit" class="btn-submit" disabled={questionLoading} data-testid="btn-submit-question">
            {questionLoading ? 'جاري الإضافة...' : '+ إضافة هذا السؤال'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<style>
  .content-loading-shell,
  .content-error-shell {
    min-height: 60vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    color: #e2e8f0;
  }

  .spinner {
    width: 36px;
    height: 36px;
    border: 3px solid rgba(0, 240, 255, 0.2);
    border-top-color: #00f0ff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .content-editor-page {
    min-height: 100vh;
    background: #091a1f;
    color: #f1f5f9;
    padding: 2rem 1rem 4rem 1rem;
  }

  .editor-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .breadcrumbs {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.6);
  }

  .breadcrumbs a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
  }

  .breadcrumbs a:hover {
    color: #00f0ff;
  }

  .breadcrumbs .sep {
    color: rgba(255, 255, 255, 0.3);
  }

  .breadcrumbs .current {
    color: #00f0ff;
    font-weight: 600;
  }

  .editor-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    background: #0f282f;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    padding: 1.5rem 1.75rem;
  }

  .badge-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.35rem;
  }

  .badge-course {
    background: rgba(0, 240, 255, 0.12);
    color: #00f0ff;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
  }

  .badge-slug {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.8125rem;
  }

  .header-titles h1 {
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0 0 0.25rem 0;
    color: #fff;
  }

  .en-sub {
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.875rem;
  }

  .header-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  .btn-primary-action,
  .btn-quiz-action {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 700;
    padding: 0.55rem 1.1rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-primary-action {
    background: #00f0ff;
    color: #0f282f;
  }

  .btn-primary-action:hover {
    background: #4ef4ff;
    transform: translateY(-1px);
  }

  .btn-quiz-action {
    background: rgba(20, 56, 64, 0.8);
    border: 1px solid rgba(0, 240, 255, 0.3);
    color: #e0faff;
  }

  .btn-quiz-action:hover {
    background: rgba(0, 240, 255, 0.15);
  }

  .btn-preview-course {
    color: rgba(255, 255, 255, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 0.5rem 0.875rem;
    border-radius: 8px;
    font-size: 0.8125rem;
    text-decoration: none;
  }

  .btn-preview-course:hover {
    color: #00f0ff;
    border-color: #00f0ff;
  }

  .empty-sections-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 1.5rem;
    text-align: center;
    background: #0f282f;
    border: 1px dashed rgba(255, 255, 255, 0.12);
    border-radius: 14px;
    gap: 1rem;
  }

  .sections-accordion {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .section-card {
    background: #0f282f;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    overflow: hidden;
  }

  .section-card-header {
    background: rgba(20, 56, 64, 0.5);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .section-title-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  .section-index-badge {
    background: rgba(0, 240, 255, 0.15);
    color: #00f0ff;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
  }

  .section-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
  }

  .section-title-en {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.8125rem;
  }

  .items-count-badge {
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.75rem;
    padding: 0.15rem 0.45rem;
    border-radius: 4px;
  }

  .section-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-icon {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #fff;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.75rem;
  }

  .btn-icon:hover {
    background: rgba(255, 255, 255, 0.12);
  }

  .btn-section-action {
    background: #00f0ff;
    color: #0f282f;
    border: none;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    cursor: pointer;
  }

  .btn-section-edit,
  .btn-section-delete {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #fff;
    font-size: 0.75rem;
    padding: 0.35rem 0.65rem;
    border-radius: 6px;
    cursor: pointer;
  }

  .btn-section-delete {
    color: #fca5a5;
  }

  .section-items-list {
    display: flex;
    flex-direction: column;
  }

  .empty-items-row {
    padding: 1.5rem;
    text-align: center;
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.875rem;
  }

  .btn-text-cyan {
    background: none;
    border: none;
    color: #00f0ff;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.8125rem;
  }

  .item-row {
    display: flex;
    align-items: center;
    padding: 0.875rem 1.25rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    gap: 1rem;
    transition: background 0.15s;
  }

  .item-row:last-child {
    border-bottom: none;
  }

  .item-row:hover {
    background: rgba(255, 255, 255, 0.02);
  }

  .item-row.is-draft {
    opacity: 0.65;
  }

  .type-icon {
    font-size: 1.25rem;
    display: inline-block;
  }

  .item-info-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .item-header-line {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .item-title {
    color: #fff;
    font-size: 0.9375rem;
  }

  .item-title-en {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.8125rem;
  }

  .item-status-pill {
    font-size: 0.6875rem;
    font-weight: 700;
    padding: 0.1rem 0.4rem;
    border-radius: 4px;
  }

  .free-preview-pill {
    font-size: 0.6875rem;
    font-weight: 700;
    padding: 0.1rem 0.45rem;
    border-radius: 4px;
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
  }

  .pill-pub {
    background: rgba(34, 197, 94, 0.15);
    color: #4ade80;
  }

  .pill-draft {
    background: rgba(234, 179, 8, 0.15);
    color: #facc15;
  }

  .item-meta-line {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.55);
    flex-wrap: wrap;
  }

  .meta-type-tag {
    background: rgba(255, 255, 255, 0.06);
    padding: 0.1rem 0.35rem;
    border-radius: 3px;
  }

  .meta-telegram-badge {
    background: rgba(0, 136, 204, 0.18);
    color: #38bdf8;
    padding: 0.1rem 0.4rem;
    border-radius: 4px;
    font-weight: 600;
  }

  .meta-link {
    color: rgba(255, 255, 255, 0.65);
    text-decoration: underline;
  }

  .btn-manage-questions {
    background: rgba(0, 240, 255, 0.1);
    border: 1px solid rgba(0, 240, 255, 0.3);
    color: #00f0ff;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    cursor: pointer;
  }

  .item-actions-col {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-item-toggle,
  .btn-item-edit,
  .btn-item-del {
    padding: 0.3rem 0.6rem;
    font-size: 0.75rem;
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    background: rgba(255, 255, 255, 0.06);
    color: #fff;
    cursor: pointer;
  }

  .btn-item-del {
    color: #fca5a5;
    font-size: 1rem;
    line-height: 1;
  }

  /* Modals */
  .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
  }

  .modal-card {
    background: #0f282f;
    border: 1px solid rgba(0, 240, 255, 0.3);
    border-radius: 12px;
    width: 100%;
    max-width: 520px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .modal-card-large {
    max-width: 680px;
  }

  .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding-bottom: 0.75rem;
  }

  .modal-header h3 {
    margin: 0;
    font-size: 1.125rem;
    color: #fff;
    font-weight: 700;
  }

  .btn-close {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.6);
    font-size: 1.5rem;
    cursor: pointer;
  }

  .modal-error-banner {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.4);
    color: #fca5a5;
    padding: 0.625rem 0.875rem;
    border-radius: 6px;
    font-size: 0.8125rem;
  }

  .modal-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
  }

  .form-row-compact {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
  }

  .form-group label {
    font-size: 0.8125rem;
    color: rgba(255, 255, 255, 0.85);
    font-weight: 600;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    background: rgba(20, 56, 64, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    padding: 0.625rem 0.875rem;
    color: #fff;
    font-size: 0.875rem;
    outline: none;
  }

  .form-group select option {
    background: #0f282f;
    color: #fff;
  }

  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    border-color: #00f0ff;
  }

  .hint-text {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.5);
  }

  .checkbox-group {
    justify-content: flex-end;
  }

  .checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #fff;
    cursor: pointer;
  }

  .checkbox-row {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .modal-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
    padding-top: 0.5rem;
  }

  .btn-cancel {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #fff;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    cursor: pointer;
  }

  .btn-submit {
    background: #00f0ff;
    border: none;
    color: #0f282f;
    padding: 0.5rem 1.25rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 700;
    cursor: pointer;
  }

  .btn-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  /* Questions Builder Specific */
  .questions-list-preview {
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 160px;
    overflow-y: auto;
  }

  .questions-list-preview h4 {
    margin: 0;
    font-size: 0.8125rem;
    color: #00f0ff;
  }

  .question-preview-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.8125rem;
    padding: 0.25rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  }

  .points-badge {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.75rem;
    margin-right: 0.5rem;
  }

  .btn-del-question {
    background: none;
    border: none;
    color: #fca5a5;
    cursor: pointer;
    font-size: 0.75rem;
  }

  .options-builder-box {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .options-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.8125rem;
    color: rgba(255, 255, 255, 0.85);
  }

  .btn-text-action {
    background: none;
    border: none;
    color: #00f0ff;
    font-size: 0.75rem;
    cursor: pointer;
  }

  .option-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .option-row input[type="text"] {
    flex: 1;
    background: rgba(20, 56, 64, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 6px;
    padding: 0.4rem 0.6rem;
    color: #fff;
    font-size: 0.8125rem;
  }

  .btn-remove-opt {
    background: none;
    border: none;
    color: #fca5a5;
    font-size: 1.1rem;
    cursor: pointer;
  }

  .true-false-box {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .radio-choices {
    display: flex;
    gap: 1.5rem;
  }

  .radio-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.875rem;
  }
</style>
