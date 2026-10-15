<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/state';
  import { getAuthToken } from '$lib/api/client';
  import { getCurrentUser } from '$lib/api/auth';
  import AuthGuardCard from '$lib/components/AuthGuardCard.svelte';
  import {
    getTeacherCourseContent,
    createTeacherCourseSection,
    updateTeacherCourseSection,
    deleteTeacherCourseSection,
    createTeacherCourseItem,
    updateTeacherCourseItem,
    deleteTeacherCourseItem,
    type CourseSection,
    type CourseItem,
    type CourseItemType,
  } from '$lib/api/admin-learning';

  const courseId = Number(page.params.id);

  let isUnauthenticated = $state(false);
  let currentRole = $state('');
  let loading = $state(true);
  let errorMsg = $state('');

  // Course Details
  let courseTitle = $state({ ar: '', en: '' });
  let courseSlug = $state('');
  let sections = $state<CourseSection[]>([]);
  let collapsedSections = $state<Record<number, boolean>>({});

  function toggleSection(sectionId: number) {
    collapsedSections[sectionId] = !collapsedSections[sectionId];
  }

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
  let itemPosition = $state<number>(1);
  let itemIsPublished = $state<boolean>(true);
  let itemFile = $state<File | null>(null);
  let itemLoading = $state(false);
  let itemError = $state('');

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

      const contentRes = await getTeacherCourseContent(fetch, courseId);
      sections = contentRes.data?.sections || [];
      if (contentRes.data?.course) {
        courseTitle = contentRes.data.course.title;
        courseSlug = contentRes.data.course.slug;
      }
    } catch (err: any) {
      const msg = err?.message || '';
      if (msg.includes('401') || msg.includes('Unauthenticated')) {
        isUnauthenticated = true;
      } else if (msg.includes('403') || msg.includes('غير مصرح')) {
        errorMsg = 'غير مصرح لك بإدارة محتوى هذا المقرر. يرجى التأكد من أن المادة مسندة لحسابك.';
      } else {
        errorMsg = msg || 'تعذر تحميل محتوى المقرر الدراسي.';
      }
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
        await updateTeacherCourseSection(fetch, courseId, editingSectionId, {
          title: {
            ar: sectionTitleAr.trim(),
            en: sectionTitleEn.trim() || sectionTitleAr.trim(),
          },
          position: Number(sectionPosition),
        });
      } else {
        await createTeacherCourseSection(fetch, courseId, {
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
      sectionError = err?.message || 'تعذر حفظ الفصل الدراسي.';
    } finally {
      sectionLoading = false;
    }
  }

  async function handleDeleteSection(sec: CourseSection) {
    if (!confirm(`هل أنت متأكد من حذف فصل "${sec.title.ar}"؟ سيتم حذف جميع الدروس والمحاضرات بداخله.`)) {
      return;
    }
    try {
      await deleteTeacherCourseSection(fetch, courseId, sec.id);
      await loadData();
    } catch (err: any) {
      alert(err?.message || 'تعذر حذف الفصل الدراسي.');
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
    itemPosition = item.position;
    itemIsPublished = item.is_published !== false;
    itemFile = null;
    itemError = '';
    isItemModalOpen = true;
  }

  function closeItemModal() {
    isItemModalOpen = false;
    itemFile = null;
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
        await updateTeacherCourseItem(fetch, courseId, editingItemId, {
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
        await createTeacherCourseItem(fetch, courseId, targetSectionId!, {
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
          telegram_message_id: telId,
          is_free: itemIsFree,
          position: Number(itemPosition),
          is_published: itemIsPublished,
        });
      }

      closeItemModal();
      await loadData();
    } catch (err: any) {
      itemError = err?.message || 'تعذر حفظ الدرس.';
    } finally {
      itemLoading = false;
    }
  }

  async function handleDeleteItem(item: CourseItem) {
    if (!confirm(`هل أنت متأكد من حذف "${item.title.ar}"؟`)) {
      return;
    }
    try {
      await deleteTeacherCourseItem(fetch, courseId, item.id);
      await loadData();
    } catch (err: any) {
      alert(err?.message || 'تعذر حذف العنصر.');
    }
  }

  async function handleTogglePublish(item: CourseItem) {
    try {
      await updateTeacherCourseItem(fetch, courseId, item.id, {
        is_published: !item.is_published,
      });
      await loadData();
    } catch (err: any) {
      alert(err?.message || 'تعذر تعديل حالة النشر.');
    }
  }

  onMount(() => {
    loadData();
  });
</script>

<svelte:head>
  <title>إدارة محتوى المقرر | لوحة المحاضر</title>
</svelte:head>

{#if isUnauthenticated || (currentRole && currentRole !== 'teacher' && currentRole !== 'admin' && currentRole !== 'superadmin')}
  <AuthGuardCard
    requiredRole="teacher"
    {isUnauthenticated}
    {currentRole}
    onRetry={loadData}
  />
{:else if loading}
  <div class="content-loading-shell" dir="rtl">
    <div class="spinner" aria-hidden="true"></div>
    <p>جاري تحميل محتوى وفصول المادة التعليمية...</p>
  </div>
{:else if errorMsg}
  <div class="content-error-shell" dir="rtl">
    <div class="error-box">
      <p>{errorMsg}</p>
      <a href="/teacher" class="btn-back">العودة للوحة المحاضر</a>
    </div>
  </div>
{:else}
  <div class="content-editor-page" dir="rtl">
    <div class="editor-container">
      <!-- Breadcrumbs -->
      <nav class="breadcrumbs" aria-label="مسار التنقل">
        <a href="/">الرئيسية</a>
        <span class="sep">/</span>
        <a href="/teacher">لوحة المحاضر</a>
        <span class="sep">/</span>
        <span class="current">محتوى المادة: {courseTitle.ar || courseSlug}</span>
      </nav>

      <!-- Header Section -->
      <header class="editor-header">
        <div class="header-titles">
          <div class="badge-row">
            <span class="badge-course">إدارة المنهج والمحاضرات</span>
            {#if courseSlug}
              <span class="badge-slug" dir="ltr">{courseSlug}</span>
            {/if}
          </div>
          <h1>{courseTitle.ar}</h1>
          {#if courseTitle.en}
            <small class="en-sub" dir="ltr">{courseTitle.en}</small>
          {/if}
          <p class="teacher-notice">
            يمكنك إضافة روابط محاضرات الفيديو المسجلة على تليجرام، ملفات ومذكرات PDF، والمحاضرات التجريبية المجانية (مثل يوتيوب).
          </p>
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

          {#if courseSlug}
            <a href={`/courses/${courseSlug}`} target="_blank" rel="noreferrer" class="btn-preview-course">
              معاينة صفحة المقرر ↗
            </a>
          {/if}
        </div>
      </header>

      <!-- Sections & Items Tree -->
      <main class="content-tree-view">
        {#if sections.length === 0}
          <div class="empty-sections-box">
            <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
            </svg>
            <h3>لا توجد فصول دراسية مضافة لهذه المادة بعد</h3>
            <p>ابدأ بإضافة أول فصل (Section) لتقسيم المنهج إلى وحدات ومحاضرات.</p>
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
                    <button
                      type="button"
                      class="btn-toggle-collapse"
                      onclick={() => toggleSection(section.id)}
                      aria-label={collapsedSections[section.id] ? 'توسيع الفصل' : 'طي الفصل'}
                      title={collapsedSections[section.id] ? 'توسيع عرض محتوى الفصل' : 'طي محتوى الفصل'}
                    >
                      <svg class="chevron-icon" class:is-rotated={collapsedSections[section.id]} viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="6 9 12 15 18 9"></polyline>
                      </svg>
                    </button>
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
                      class="btn-section-action"
                      onclick={() => openCreateItemModal(section.id)}
                      data-testid={`btn-add-item-${section.id}`}
                    >
                      + إضافة محاضرة / درس
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

                {#if !collapsedSections[section.id]}
                  <!-- Items inside Section -->
                  <div class="section-items-list">
                  {#if section.items.length === 0}
                    <div class="empty-items-row">
                      <p>لا توجد محاضرات أو عناصر في هذا الفصل بعد.</p>
                      <button
                        type="button"
                        class="btn-text-cyan"
                        onclick={() => openCreateItemModal(section.id)}
                      >
                        + إضافة المحاضرة الأولى
                      </button>
                    </div>
                  {:else}
                    {#each section.items as item (item.id)}
                      <div class="item-row" class:is-draft={item.is_published === false} data-testid={`item-row-${item.id}`}>
                        <div class="item-type-icon-col">
                          {#if item.type === 'lecture_link'}
                            <span class="type-icon icon-video" title="محاضرة فيديو">🎬</span>
                          {:else if item.type === 'file'}
                            <span class="type-icon icon-file" title="ملف / كتاب / مذكرة">📄</span>
                          {:else}
                            <span class="type-icon icon-link" title="رابط خارجي">🔗</span>
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
                            {#if item.type === 'lecture_link'}
                              <span class="meta-type-tag">محاضرة فيديو</span>
                            {:else if item.type === 'file'}
                              <span class="meta-type-tag">ملف / مذكرة</span>
                            {:else}
                              <span class="meta-type-tag">رابط خارجي</span>
                            {/if}

                            {#if item.telegram_message_id}
                              <span class="meta-telegram-badge" title="معرّف الرسالة في قناة التليجرام الخاصة">
                                تليجرام: #{item.telegram_message_id}
                              </span>
                            {/if}

                            {#if item.url}
                              <a href={item.url} target="_blank" rel="noreferrer" class="meta-link" dir="ltr">
                                {item.url.length > 45 ? item.url.slice(0, 45) + '...' : item.url}
                              </a>
                            {/if}
                          </div>

                          {#if item.description?.ar}
                            <p class="item-desc-text">{item.description.ar}</p>
                          {/if}
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
                {/if}
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
          <label for="sec-title-ar">اسم الفصل / الوحدة بالعربية *</label>
          <input
            id="sec-title-ar"
            type="text"
            bind:value={sectionTitleAr}
            required
            placeholder="مثال: الباب الأول: أساسيات المادة"
            data-testid="input-section-title-ar"
          />
        </div>

        <div class="form-group">
          <label for="sec-title-en">اسم الفصل بالإنجليزية (اختياري)</label>
          <input
            id="sec-title-en"
            type="text"
            dir="ltr"
            bind:value={sectionTitleEn}
            placeholder="e.g. Unit 1: Fundamentals"
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
        <h3>{isEditingItem ? 'تعديل بيانات المحاضرة / المحتوى' : 'إضافة محتوى أو محاضرة جديدة'}</h3>
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
              <option value="lecture_link">فيديو شرح (رابط تليجرام أو يوتيوب)</option>
              <option value="file">ملخص / مذكرة أو كتاب المادة (PDF عبر تليجرام/رابط)</option>
              <option value="external_link">رابط خارجي إضافي</option>
            </select>
          </div>
        {/if}

        <div class="form-group">
          <label for="item-title-ar">عنوان المحاضرة أو الملف بالعربية *</label>
          <input
            id="item-title-ar"
            type="text"
            bind:value={itemTitleAr}
            required
            placeholder="مثال: المحاضرة الأولى: مقدمة وشرح عام"
            data-testid="input-item-title-ar"
          />
        </div>

        <div class="form-group">
          <label for="item-title-en">العنوان بالإنجليزية (اختياري)</label>
          <input
            id="item-title-en"
            type="text"
            dir="ltr"
            bind:value={itemTitleEn}
            placeholder="e.g. Lecture 1: Overview"
            data-testid="input-item-title-en"
          />
        </div>

        <div class="form-group">
          <label for="item-url">رابط المحاضرة / الفيديو / الرابط الخارجي (أو رابط الملف)</label>
          <input
            id="item-url"
            type="url"
            dir="ltr"
            bind:value={itemUrl}
            placeholder="https://t.me/... أو https://youtube.com/... أو رابط درايف"
            data-testid="input-item-url"
          />
          <small class="hint-text">
            ضع رابط الفيديو أو رابط المذكرة على تليجرام أو رابط يوتيوب/درايف.
          </small>
        </div>

        {#if itemType === 'file' && !isEditingItem}
          <div class="form-group">
            <label for="teacher-item-file-upload">أو رفع ملف مباشر من جهازك (اختياري)</label>
            <input
              id="teacher-item-file-upload"
              type="file"
              onchange={(e) => {
                const target = e.currentTarget as HTMLInputElement;
                itemFile = target.files && target.files[0] ? target.files[0] : null;
              }}
              data-testid="input-item-file"
            />
            <small class="hint-text">يمكنك إما وضع رابط تليجرام/سحابي بالأعلى، أو رفع ملف PDF/مستند مباشرة.</small>
          </div>
        {/if}

        <div class="form-group">
          <label for="item-tel-msg-id">معرّف الرسالة بتليجرام (Telegram Message ID - اختياري)</label>
          <input
            id="item-tel-msg-id"
            type="number"
            min="1"
            dir="ltr"
            bind:value={itemTelegramMessageId}
            placeholder="مثال: 125"
            data-testid="input-item-telegram-id"
          />
          <small class="hint-text">رقم المنشور في قناة التليجرام الخاصة لتسهيل التوجيه المباشر.</small>
        </div>

        <!-- Free Preview Checkbox -->
        <div class="form-group checkbox-highlight-box">
          <label class="checkbox-label">
            <input type="checkbox" bind:checked={itemIsFree} data-testid="input-item-is-free" />
            <div>
              <strong>معاينة مجانية للجميع (Free Preview)</strong>
              <p class="checkbox-subtext">
                عند تفعيل هذا الخيار، سيتمكن أي طالب أو زائر من مشاهدة هذا الفيديو أو فتح الرابط حتى لو لم يكن مسجلاً في المادة.
              </p>
            </div>
          </label>
        </div>

        <div class="form-group">
          <label for="item-desc-ar">ملاحظات أو وصف توضيحي للطلاب (اختياري)</label>
          <textarea
            id="item-desc-ar"
            rows="2"
            bind:value={itemDescAr}
            placeholder="مثال: يرجى حل التمرين المرفق في نهاية الفيديو..."
            data-testid="input-item-desc-ar"
          ></textarea>
        </div>

        <div class="form-row-compact">
          <div class="form-group">
            <label for="item-position">الترتيب</label>
            <input id="item-position" type="number" min="0" bind:value={itemPosition} />
          </div>

          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input type="checkbox" bind:checked={itemIsPublished} data-testid="input-item-published" />
              <span>نشر هذا المحتوى للطلاب فوراً</span>
            </label>
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick={closeItemModal}>إلغاء</button>
          <button type="submit" class="btn-submit" disabled={itemLoading} data-testid="btn-submit-item">
            {itemLoading ? 'جاري الحفظ...' : 'حفظ المحتوى'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<style>
  .content-loading-shell,
  .content-error-shell {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 50vh;
    padding: 2rem;
    color: var(--muted);
  }

  .spinner {
    width: 2.5rem;
    height: 2.5rem;
    border: 3px solid var(--line);
    border-top-color: var(--deep-cyan);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 1rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .error-box {
    background: #fff5f5;
    border: 2px solid #fecaca;
    border-radius: 1rem;
    padding: 2rem;
    text-align: center;
    max-width: 500px;
    color: #b91c1c;
  }

  .btn-back {
    display: inline-block;
    margin-top: 1rem;
    padding: 0.5rem 1.25rem;
    background: var(--deep-cyan);
    color: #ffffff;
    border-radius: 0.5rem;
    text-decoration: none;
    font-weight: 700;
  }

  .content-editor-page {
    background-color: var(--paper);
    min-height: calc(100vh - 140px);
    padding: 2rem 1.25rem 4rem;
  }

  .editor-container {
    max-width: 1050px;
    margin-inline: auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  /* Breadcrumbs */
  .breadcrumbs {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--muted);
  }

  .breadcrumbs a {
    color: var(--deep-cyan);
    text-decoration: none;
  }

  .breadcrumbs .sep {
    color: var(--line);
  }

  .breadcrumbs .current {
    color: var(--storm);
    font-weight: 600;
  }

  /* Header */
  .editor-header {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    padding: 1.75rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 1.25rem;
    box-shadow: 0 4px 12px rgba(15, 40, 47, 0.04);
  }

  .header-titles h1 {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0.35rem 0 0.15rem;
  }

  .en-sub {
    color: var(--muted);
    font-size: 0.95rem;
    display: block;
    margin-bottom: 0.5rem;
  }

  .teacher-notice {
    font-size: 0.85rem;
    color: var(--muted);
    margin: 0.4rem 0 0;
    max-width: 650px;
    line-height: 1.5;
  }

  .badge-row {
    display: flex;
    gap: 0.5rem;
    align-items: center;
  }

  .badge-course {
    background: rgba(var(--brand-navy-rgb, 15, 40, 47), 0.08);
    color: var(--brand-navy, #0f282f);
    font-weight: 700;
    font-size: 0.78rem;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
  }

  .badge-slug {
    font-family: monospace;
    font-size: 0.8rem;
    color: var(--muted);
    background: var(--paper);
    padding: 0.15rem 0.5rem;
    border-radius: 0.25rem;
    border: 1px solid var(--line);
  }

  .header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
  }

  .btn-primary-action {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: var(--deep-cyan);
    color: #ffffff;
    font-weight: 700;
    font-size: 0.9rem;
    padding: 0.65rem 1.25rem;
    border-radius: 0.6rem;
    border: none;
    cursor: pointer;
    transition: opacity 150ms ease;
  }

  .btn-primary-action:hover {
    opacity: 0.9;
  }

  .btn-preview-course {
    display: inline-flex;
    align-items: center;
    padding: 0.6rem 1rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--storm);
    background: var(--paper);
    border: 1.5px solid var(--line);
    border-radius: 0.6rem;
    text-decoration: none;
    transition: background 150ms ease;
  }

  .btn-preview-course:hover {
    background: var(--card-hover);
  }

  /* Empty State */
  .empty-sections-box {
    background: var(--card);
    border: 2px dashed var(--line);
    border-radius: 1.25rem;
    padding: 4rem 2rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    color: var(--muted);
  }

  .empty-sections-box h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--storm);
    margin: 0;
  }

  .empty-sections-box p {
    margin: 0 0 1rem;
    font-size: 0.9rem;
  }

  /* Sections Accordion */
  .sections-accordion {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .section-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(15, 40, 47, 0.03);
  }

  .section-card-header {
    background: var(--paper);
    padding: 1rem 1.25rem;
    border-bottom: 2px solid var(--line);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
  }

  .btn-toggle-collapse {
    background: transparent;
    border: none;
    color: var(--muted);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.3rem;
    border-radius: 0.35rem;
    transition: color 150ms ease, background 150ms ease;
  }

  .btn-toggle-collapse:hover {
    color: var(--storm);
    background: var(--card-hover);
  }

  .chevron-icon {
    transition: transform 200ms ease;
  }

  .chevron-icon.is-rotated {
    transform: rotate(90deg);
  }

  .section-title-group {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
  }

  .section-index-badge {
    background: var(--deep-cyan);
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.2rem 0.5rem;
    border-radius: 0.35rem;
  }

  .section-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .section-title-en {
    color: var(--muted);
    font-size: 0.85rem;
  }

  .items-count-badge {
    font-size: 0.75rem;
    background: rgba(var(--brand-navy-rgb, 15, 40, 47), 0.08);
    color: var(--brand-navy, #0f282f);
    padding: 0.15rem 0.5rem;
    border-radius: 0.25rem;
    font-weight: 600;
  }

  .section-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-section-action {
    background: var(--deep-cyan);
    color: #ffffff;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 0.4rem 0.85rem;
    border-radius: 0.4rem;
    border: none;
    cursor: pointer;
  }

  .btn-section-edit {
    background: transparent;
    color: var(--storm);
    font-size: 0.82rem;
    font-weight: 600;
    padding: 0.4rem 0.75rem;
    border-radius: 0.4rem;
    border: 1px solid var(--line);
    cursor: pointer;
  }

  .btn-section-delete {
    background: transparent;
    border: 1px solid var(--line);
    padding: 0.4rem 0.6rem;
    border-radius: 0.4rem;
    cursor: pointer;
  }

  /* Section Items List */
  .section-items-list {
    display: flex;
    flex-direction: column;
  }

  .empty-items-row {
    padding: 2rem 1.5rem;
    text-align: center;
    color: var(--muted);
    font-size: 0.9rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-text-cyan {
    background: none;
    border: none;
    color: var(--deep-cyan);
    font-weight: 700;
    cursor: pointer;
    font-size: 0.88rem;
  }

  .item-row {
    display: flex;
    align-items: center;
    padding: 0.85rem 1.25rem;
    border-bottom: 1px solid var(--line);
    gap: 1rem;
    transition: background 120ms ease;
  }

  .item-row:last-child {
    border-bottom: none;
  }

  .item-row:hover {
    background: var(--card-hover, rgba(0, 0, 0, 0.02));
  }

  .item-row.is-draft {
    opacity: 0.65;
    background: rgba(0, 0, 0, 0.02);
  }

  .item-type-icon-col {
    font-size: 1.25rem;
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
    flex-wrap: wrap;
  }

  .item-title {
    font-size: 0.95rem;
    color: var(--storm);
  }

  .item-title-en {
    color: var(--muted);
    font-size: 0.82rem;
  }

  .free-preview-pill {
    font-size: 0.72rem;
    font-weight: 700;
    background: #fef3c7;
    color: #92400e;
    padding: 0.15rem 0.5rem;
    border-radius: 9999px;
    border: 1px solid #fde68a;
  }

  :global([data-theme='dark']) .free-preview-pill {
    background: rgba(245, 158, 11, 0.2);
    color: #fcd34d;
    border-color: rgba(245, 158, 11, 0.35);
  }

  .item-status-pill {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.15rem 0.45rem;
    border-radius: 0.25rem;
  }

  .pill-pub {
    background: #ecfdf5;
    color: #059669;
  }

  :global([data-theme='dark']) .pill-pub {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
  }

  .pill-draft {
    background: #f3f4f6;
    color: #6b7280;
  }

  :global([data-theme='dark']) .pill-draft {
    background: rgba(156, 163, 175, 0.2);
    color: #9ca3af;
  }

  .item-meta-line {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    font-size: 0.78rem;
    flex-wrap: wrap;
  }

  .meta-type-tag {
    color: var(--muted);
    font-weight: 600;
  }

  .meta-telegram-badge {
    background: rgba(36, 161, 222, 0.12);
    color: #0284c7;
    padding: 0.1rem 0.45rem;
    border-radius: 0.25rem;
    font-weight: 700;
  }

  .meta-link {
    color: var(--deep-cyan);
    text-decoration: none;
    font-family: monospace;
    font-size: 0.75rem;
  }

  .meta-link:hover {
    text-decoration: underline;
  }

  .item-desc-text {
    font-size: 0.82rem;
    color: var(--muted);
    margin: 0.2rem 0 0;
  }

  .item-actions-col {
    display: flex;
    align-items: center;
    gap: 0.4rem;
  }

  .btn-item-toggle {
    background: transparent;
    border: 1px solid var(--line);
    font-size: 0.78rem;
    padding: 0.25rem 0.55rem;
    border-radius: 0.35rem;
    cursor: pointer;
    color: var(--storm);
  }

  .btn-item-edit {
    background: transparent;
    border: 1px solid var(--line);
    font-size: 0.78rem;
    padding: 0.25rem 0.55rem;
    border-radius: 0.35rem;
    cursor: pointer;
    color: var(--storm);
  }

  .btn-item-del {
    background: transparent;
    border: 1px solid #fee2e2;
    color: #dc2626;
    font-size: 1.1rem;
    line-height: 1;
    padding: 0.15rem 0.45rem;
    border-radius: 0.35rem;
    cursor: pointer;
  }

  /* Modals */
  .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 40, 47, 0.6);
    backdrop-filter: blur(2px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    z-index: 1000;
  }

  .modal-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    width: 100%;
    max-width: 580px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 1.75rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid var(--line);
    padding-bottom: 1rem;
    margin-bottom: 1.25rem;
  }

  .modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--storm);
  }

  .btn-close {
    background: transparent;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--muted);
  }

  .modal-error-banner {
    background: #fff5f5;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.88rem;
  }

  .modal-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
  }

  .form-group label {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--storm);
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    padding: 0.65rem 0.85rem;
    border: 1.5px solid var(--line);
    border-radius: 0.5rem;
    background: var(--paper);
    color: var(--storm);
    font-size: 0.9rem;
    font-family: inherit;
  }

  .form-group select option {
    background: var(--card);
    color: var(--storm);
  }

  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: var(--deep-cyan);
  }

  .hint-text {
    font-size: 0.75rem;
    color: var(--muted);
  }

  .checkbox-highlight-box {
    background: var(--card);
    border: 1.5px solid var(--line);
    border-radius: 0.65rem;
    padding: 0.85rem 1rem;
  }

  .checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    font-size: 0.88rem;
    cursor: pointer;
  }

  .checkbox-subtext {
    font-size: 0.78rem;
    color: var(--muted);
    margin: 0.2rem 0 0;
    line-height: 1.4;
  }

  .form-row-compact {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 1rem;
    align-items: flex-end;
  }

  .checkbox-group {
    padding-bottom: 0.65rem;
  }

  .modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1rem;
    border-top: 2px solid var(--line);
    padding-top: 1rem;
  }

  .btn-cancel {
    background: transparent;
    border: 1.5px solid var(--line);
    padding: 0.6rem 1.25rem;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    color: var(--storm);
  }

  .btn-submit {
    background: var(--deep-cyan);
    color: #fff;
    border: none;
    padding: 0.6rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 700;
    cursor: pointer;
  }

  .btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  @media (max-width: 640px) {
    .editor-header {
      flex-direction: column;
    }

    .form-row-compact {
      grid-template-columns: 1fr;
    }
  }
</style>
