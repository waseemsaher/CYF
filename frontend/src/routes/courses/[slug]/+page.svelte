<script lang="ts">
  import type { PageData } from './$types';
  import { currentUser } from '$lib/api/auth';
  import { updateAdminCourse } from '$lib/api/admin';
  import { currentLocale, formatPrice, getLocalizedText } from '$lib/i18n';

  let { data }: { data: PageData } = $props();

  let isAdmin = $derived(
    $currentUser?.role === 'admin' || $currentUser?.role === 'superadmin'
  );

  // Free Preview Modal State
  let isPreviewModalOpen = $state(false);
  let previewItemTitle = $state('');
  let previewVideoUrl = $state('');
  let previewEmbedUrl = $derived.by(() => {
    if (!previewVideoUrl) return '';
    try {
      // Check for youtube.com/watch?v= or youtu.be/
      if (previewVideoUrl.includes('youtube.com/watch')) {
        const url = new URL(previewVideoUrl);
        const v = url.searchParams.get('v');
        if (v) return `https://www.youtube.com/embed/${v}?autoplay=1`;
      } else if (previewVideoUrl.includes('youtu.be/')) {
        const parts = previewVideoUrl.split('youtu.be/');
        const id = parts[1]?.split(/[?#]/)[0];
        if (id) return `https://www.youtube.com/embed/${id}?autoplay=1`;
      } else if (previewVideoUrl.includes('youtube.com/embed/')) {
        return previewVideoUrl;
      }
    } catch {
      // ignore
    }
    return '';
  });

  function openPreviewModal(title: string, url: string) {
    previewItemTitle = title;
    previewVideoUrl = url;
    isPreviewModalOpen = true;
  }

  function closePreviewModal() {
    isPreviewModalOpen = false;
    previewVideoUrl = '';
    previewItemTitle = '';
  }

  // Edit Modal State
  let isEditModalOpen = $state(false);
  let editTitleAr = $state('');
  let editTitleEn = $state('');
  let editDescAr = $state('');
  let editDescEn = $state('');
  let editPricePounds = $state(0);
  let editStatus = $state<'published' | 'draft'>('published');
  let editTelegramLink = $state('');
  let editTelegramGroupId = $state('');
  let editTelegramChannelId = $state('');
  let modalLoading = $state(false);
  let modalError = $state('');

  function openEditModal() {
    editTitleAr = data.course.title.ar;
    editTitleEn = data.course.title.en || '';
    editDescAr = data.course.description.ar;
    editDescEn = data.course.description.en || '';
    editPricePounds = Math.round((data.course.price_cents || data.course.amount_due_cents || 0) / 100);
    editStatus = (data.course.status as 'published' | 'draft') || 'published';
    editTelegramLink = data.course.telegram_invite_link || '';
    editTelegramGroupId = data.course.telegram_group_id ? String(data.course.telegram_group_id) : '';
    editTelegramChannelId = data.course.telegram_channel_id ? String(data.course.telegram_channel_id) : '';
    modalError = '';
    isEditModalOpen = true;
  }

  function closeEditModal() {
    isEditModalOpen = false;
    modalError = '';
  }

  async function handleSaveCourse(e: SubmitEvent) {
    e.preventDefault();
    modalLoading = true;
    modalError = '';

    try {
      const payload: Record<string, unknown> = {
        title: {
          ar: editTitleAr,
          en: editTitleEn || editTitleAr,
        },
        description: {
          ar: editDescAr,
          en: editDescEn || editDescAr,
        },
        price_cents: Math.max(0, Math.round(editPricePounds * 100)),
        status: editStatus,
        telegram_invite_link: editTelegramLink.trim() || null,
        telegram_group_id: editTelegramGroupId.trim() ? Number(editTelegramGroupId.trim()) : null,
        telegram_channel_id: editTelegramChannelId.trim() ? Number(editTelegramChannelId.trim()) : null,
      };

      await updateAdminCourse(fetch, data.course.id, payload);
      closeEditModal();
      window.location.reload();
    } catch (err: unknown) {
      modalError = err instanceof Error ? err.message : 'تعذر حفظ تعديلات المقرر.';
    } finally {
      modalLoading = false;
    }
  }
</script>

<svelte:head>
  <title>{getLocalizedText(data.course.title, $currentLocale)} | {$currentLocale === 'en' ? 'Codeera Platform' : 'منصة Codeera'}</title>
  <meta name="description" content={getLocalizedText(data.course.description, $currentLocale)} />
</svelte:head>

<div class="detail-shell">
  <nav class="breadcrumb-nav" aria-label={$currentLocale === 'en' ? 'Breadcrumbs' : 'مسار التنقل'}>
    <a class="back-link" href="/courses">
      <span aria-hidden="true">{$currentLocale === 'en' ? '←' : '→'}</span>
      {$currentLocale === 'en' ? 'Back to Courses Catalog' : 'العودة إلى قائمة الدورات'}
    </a>
  </nav>

  <section class="course-hero">
    <div class="hero-copy">
      <div class="hero-tag-row">
        <p class="kicker">{isAdmin ? ($currentLocale === 'en' ? 'Course Management' : 'لوحة إدارة المقرر الدراسي') : ($currentLocale === 'en' ? 'Available Course' : 'دورة متاحة للتسجيل')}</p>
        {#if isAdmin}
          <span class="admin-badge">{$currentLocale === 'en' ? 'Admin Mode' : 'وضع المسؤول'}</span>
        {/if}
      </div>
      <h1>{getLocalizedText(data.course.title, $currentLocale)}</h1>
      <p class="description">{getLocalizedText(data.course.description, $currentLocale)}</p>
    </div>

    <!-- Side Panel: Admin Controls OR Student Enrollment -->
    {#if isAdmin}
      <aside class="admin-panel" aria-label={$currentLocale === 'en' ? 'Admin Course Controls' : 'لوحة تحكم المسؤول في المقرر'}>
        <div class="panel-header">
          <span class="panel-tag">{$currentLocale === 'en' ? 'Admin' : 'لوحة المسؤول'}</span>
          <h3>{$currentLocale === 'en' ? 'Manage Course & Content' : 'إدارة المقرر والمحتوى'}</h3>
        </div>

        <div class="admin-info-box">
          <div class="info-row">
            <span>{$currentLocale === 'en' ? 'Course Fee:' : 'سعر المقرر:'}</span>
            <strong>{formatPrice(data.course.amount_due_cents, $currentLocale)}</strong>
          </div>
          <div class="info-row">
            <span>{$currentLocale === 'en' ? 'Status:' : 'حالة المقرر:'}</span>
            <span class="status-chip">{data.course.status === 'published' ? ($currentLocale === 'en' ? 'Published' : 'منشور') : ($currentLocale === 'en' ? 'Draft' : 'مسودة')}</span>
          </div>
        </div>

        <div class="admin-buttons-stack">
          <a class="btn-panel-action btn-manage-content" href={`/my-courses/${data.course.slug}`}>
            <span>{$currentLocale === 'en' ? 'Manage Lectures & Content' : 'إدارة المحتوى والمحاضرات'}</span>
            <span aria-hidden="true">{$currentLocale === 'en' ? '→' : '←'}</span>
          </a>
          <button type="button" class="btn-panel-action btn-edit-course" onclick={openEditModal}>
            <span>{$currentLocale === 'en' ? 'Edit Course Details & Price' : 'تعديل بيانات وسعر المقرر'}</span>
          </button>
          <a class="btn-panel-action btn-back-dashboard" href="/admin">
            <span>{$currentLocale === 'en' ? 'Main Admin Dashboard' : 'لوحة تحكم الإدارة الرئيسية'}</span>
          </a>
        </div>
      </aside>
    {:else}
      <aside class="enrollment-panel" aria-label={$currentLocale === 'en' ? 'Price & Enrollment Details' : 'تفاصيل السعر والتسجيل'}>
        <p>{$currentLocale === 'en' ? 'Current Price' : 'السعر الحالي'}</p>
        {#if data.course.discount_cents > 0}
          <span class="old-price">{formatPrice(data.course.list_price_cents, $currentLocale)}</span>
        {/if}
        <strong>{formatPrice(data.course.amount_due_cents, $currentLocale)}</strong>
        {#if data.course.amount_due_cents === 0}
          <span class="free-label">{$currentLocale === 'en' ? 'Free' : 'مجانية'}</span>
          <a class="enroll-btn free" href={`/courses/${data.course.slug}/checkout`}>
            {$currentLocale === 'en' ? 'Enroll Now — Free' : 'سجّل فورًا — مجانًا'}
          </a>
        {:else}
          <a class="enroll-btn" href={`/courses/${data.course.slug}/checkout`}>
            <span>{$currentLocale === 'en' ? 'Enroll Now' : 'سجّل الآن'}</span>
            <span aria-hidden="true">{$currentLocale === 'en' ? '→' : '←'}</span>
          </a>
        {/if}
      </aside>
    {/if}
  </section>

  <section class="outline">
    <p class="kicker">{$currentLocale === 'en' ? 'Course Overview' : 'نظرة عامة على المقرر'}</p>
    <h2>{$currentLocale === 'en' ? 'Curriculum & Course Structure' : 'محتوى وتفاصيل المنهج'}</h2>
    <p>{$currentLocale === 'en' ? 'Inside this course you will find structured lessons, video lecture links, and interactive quizzes for regular review and final exam preparation.' : 'ستجد داخل الدورة محتوى مرتبًا وروابط المحاضرات والاختبارات الخاصة بالمقرر للمساعدة في المذاكرة والمراجعة النهائية.'}</p>
    
    {#if data.content?.sections && data.content.sections.length > 0}
      <div class="curriculum-list">
        {#each data.content.sections as section, sIdx}
          <div class="curriculum-section">
            <div class="curriculum-section-header">
              <span class="section-idx">{$currentLocale === 'en' ? `Section ${sIdx + 1}` : `الفصل ${sIdx + 1}`}</span>
              <h3>{getLocalizedText(section.title, $currentLocale)}</h3>
              <span class="section-badge">{section.items.length} {$currentLocale === 'en' ? 'items' : 'عنصر'}</span>
            </div>

            <div class="curriculum-items">
              {#each section.items as item}
                <div class="curriculum-item" class:is-preview={item.is_free}>
                  <div class="item-title-wrap">
                    <span class="item-icon-tag" aria-hidden="true">
                      {#if item.type === 'lecture_link'}
                        🎬
                      {:else if item.type === 'file'}
                        📄
                      {:else if item.type === 'quiz' || item.type === 'exam'}
                        📝
                      {:else}
                        🔗
                      {/if}
                    </span>
                    <span class="item-title">{getLocalizedText(item.title, $currentLocale)}</span>

                    {#if item.is_free}
                      <span class="preview-tag">
                        {$currentLocale === 'en' ? 'Free Preview' : 'معاينة مجانية'}
                      </span>
                    {/if}
                  </div>

                  <div class="item-status-wrap">
                    {#if item.is_free && item.url}
                      <button
                        type="button"
                        class="btn-watch-preview"
                        onclick={() => openPreviewModal(getLocalizedText(item.title, $currentLocale), item.url!)}
                      >
                        {$currentLocale === 'en' ? 'Watch Preview ▶' : 'مشاهدة المعاينة ▶'}
                      </button>
                    {:else if item.is_locked}
                      <span class="lock-indicator" title={$currentLocale === 'en' ? 'Requires enrollment' : 'يتطلب الاشتراك في المادة'}>
                        🔒 {$currentLocale === 'en' ? 'Locked' : 'مغلق'}
                      </span>
                    {:else}
                      <a href={`/my-courses/${data.course.slug}`} class="btn-item-open">
                        {$currentLocale === 'en' ? 'Open Lesson' : 'فتح الدرس'}
                      </a>
                    {/if}
                  </div>
                </div>
              {/each}
            </div>
          </div>
        {/each}
      </div>
    {/if}

    {#if isAdmin}
      <div class="admin-inline-notice">
        <p>{$currentLocale === 'en' ? 'As an administrator, you can manage lectures, quizzes, and materials by clicking "Manage Lectures & Content" above.' : 'بصفتك مسؤولاً، يمكنك الوصول للمحتوى وإضافة المحاضرات والاختبارات بالضغط على زر "إدارة المحتوى والمحاضرات" بالأعلى.'}</p>
        <a href={`/my-courses/${data.course.slug}`} class="btn-inline-manage">
          {$currentLocale === 'en' ? 'Open Course Materials →' : 'فتح المحتوى التعليمي الآن ←'}
        </a>
      </div>
    {/if}
  </section>
</div>

<!-- Modal: Free Preview Video Player -->
{#if isPreviewModalOpen}
  <div class="modal-backdrop" onclick={closePreviewModal} role="presentation">
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <div class="modal-card preview-player-modal" onclick={(e) => e.stopPropagation()} role="dialog" aria-modal="true" tabindex="-1" dir="rtl">
      <div class="modal-header">
        <div class="preview-modal-titles">
          <span class="preview-tag-modal">معاينة مجانية</span>
          <h3>{previewItemTitle}</h3>
        </div>
        <button type="button" class="btn-close-modal" onclick={closePreviewModal} aria-label="إغلاق">&times;</button>
      </div>

      <div class="preview-player-body">
        {#if previewEmbedUrl}
          <div class="video-responsive-wrapper">
            <iframe
              src={previewEmbedUrl}
              title={previewItemTitle}
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen
            ></iframe>
          </div>
        {:else if previewVideoUrl}
          <div class="external-preview-card">
            <p>هذا المحتوى متاح للمشاهدة الخارجية عبر الرابط المباشر:</p>
            <a href={previewVideoUrl} target="_blank" rel="noopener noreferrer" class="btn-open-external">
              فتح الرابط في نافذة جديدة ↗
            </a>
          </div>
        {/if}
      </div>

      <div class="preview-modal-footer">
        <p>أعجبك الشرح؟ يمكنك الاشتراك في المنهج الكامل الآن والوصول لجميع المحاضرات والملفات والاختبارات.</p>
        <a href={`/courses/${data.course.slug}/checkout`} class="btn-enroll-from-preview">
          الاشتراك في المقرر الكامل
        </a>
      </div>
    </div>
  </div>
{/if}

<!-- Edit Course Modal for Admin -->
{#if isEditModalOpen}
  <div class="modal-backdrop" onclick={closeEditModal} role="presentation">
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <div class="modal-card" onclick={(e) => e.stopPropagation()} role="dialog" aria-modal="true" dir="rtl" tabindex="-1">
      <div class="modal-header">
        <h2>تعديل بيانات المقرر ({data.course.title.ar})</h2>
        <button type="button" class="btn-close-modal" onclick={closeEditModal} aria-label="إغلاق">&times;</button>
      </div>

      {#if modalError}
        <div class="modal-error-banner" role="alert">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
          <p>{modalError}</p>
        </div>
      {/if}

      <form class="modal-form" onsubmit={handleSaveCourse}>
        <div class="form-row">
          <div class="form-group">
            <label for="edit-title-ar">اسم المقرر بالعربية *</label>
            <input
              id="edit-title-ar"
              type="text"
              bind:value={editTitleAr}
              required
            />
          </div>

          <div class="form-group">
            <label for="edit-title-en">اسم المقرر بالإنجليزية</label>
            <input
              id="edit-title-en"
              type="text"
              dir="ltr"
              bind:value={editTitleEn}
            />
          </div>
        </div>

        <div class="form-group">
          <label for="edit-price">سعر المقرر (بالجنيه المصري) *</label>
          <input
            id="edit-price"
            type="number"
            min="0"
            step="5"
            bind:value={editPricePounds}
            required
          />
        </div>

        <div class="form-group">
          <label for="edit-desc-ar">الوصف التعريفي بالمقرر</label>
          <textarea
            id="edit-desc-ar"
            rows="3"
            bind:value={editDescAr}
          ></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="edit-status">حالة المقرر</label>
            <select id="edit-status" bind:value={editStatus}>
              <option value="published">منشور (متاح للتسجيل)</option>
              <option value="draft">مسودة (مخفي عن الطلاب)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="edit-telegram">رابط دعوة تليجرام</label>
            <input
              id="edit-telegram"
              type="url"
              dir="ltr"
              bind:value={editTelegramLink}
              placeholder="https://t.me/+joinchat..."
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="edit-telegram-group">معرّف مجموعة تليجرام (Group ID)</label>
            <input
              id="edit-telegram-group"
              type="text"
              dir="ltr"
              bind:value={editTelegramGroupId}
              placeholder="-1001234567890"
            />
            <small style="color: var(--storm); opacity: 0.7; font-size: 0.75rem;">
              معرّف المجموعة (يبدأ بـ 100- للمجموعات الخارقة).
            </small>
          </div>

          <div class="form-group">
            <label for="edit-telegram-channel">معرّف قناة تليجرام (Channel ID)</label>
            <input
              id="edit-telegram-channel"
              type="text"
              dir="ltr"
              bind:value={editTelegramChannelId}
              placeholder="-1001987654321"
            />
            <small style="color: var(--storm); opacity: 0.7; font-size: 0.75rem;">
              معرّف القناة لبث الدروس (اختياري).
            </small>
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick={closeEditModal} disabled={modalLoading}>
            إلغاء
          </button>
          <button type="submit" class="btn-save" disabled={modalLoading}>
            {#if modalLoading}
              <span>جاري الحفظ...</span>
            {:else}
              <span>حفظ التعديلات</span>
            {/if}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<style>
  .detail-shell {
    margin: 0 auto;
    max-width: 1180px;
    padding: 2rem 1.25rem 4rem;
  }

  .breadcrumb-nav {
    margin-bottom: 1.5rem;
  }

  .back-link {
    color: var(--deep-cyan);
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
  }

  .back-link:hover {
    text-decoration: underline;
  }

  .course-hero {
    align-items: end;
    background: radial-gradient(135% 120% at 50% 0%, #1E2B4D 0%, #1A1918 100%);
    border: 2px solid var(--line);
    border-radius: 1rem;
    color: white;
    display: grid;
    gap: 2rem;
    grid-template-columns: 1fr minmax(280px, 360px);
    padding: clamp(1.5rem, 5vw, 3.5rem);
    box-shadow: 0 10px 30px rgba(26, 25, 24, 0.2);
  }

  .hero-tag-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
  }

  .kicker {
    color: var(--brand-accent);
    font-size: 0.82rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    margin: 0;
  }

  .admin-badge {
    background: #fee2e2;
    color: #991b1b;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.15rem 0.55rem;
    border-radius: 9999px;
    border: 1px solid #f87171;
  }

  h1 {
    font-size: clamp(2.3rem, 6vw, 4.5rem);
    line-height: 1.1;
    margin: 0;
    font-weight: 900;
  }

  .description {
    color: #d7eeee;
    font-size: 1.05rem;
    line-height: 1.8;
    margin: 1.25rem 0 0;
    max-width: 650px;
  }

  /* Admin Panel on Course Hero */
  .admin-panel {
    background: var(--card);
    border: 2.5px solid var(--line);
    border-radius: 0.85rem;
    color: var(--storm);
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  }

  .panel-header {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
  }

  .panel-tag {
    font-size: 0.72rem;
    font-weight: 800;
    color: #991b1b;
    background: #fee2e2;
    padding: 0.15rem 0.5rem;
    border-radius: 0.25rem;
    align-self: flex-start;
  }

  .panel-header h3 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--storm);
  }

  .admin-info-box {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    font-size: 0.88rem;
  }

  .info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .status-chip {
    background: #d1fae5;
    color: #065f46;
    font-weight: 800;
    font-size: 0.75rem;
    padding: 0.15rem 0.45rem;
    border-radius: 0.25rem;
  }

  .admin-buttons-stack {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
  }

  .btn-panel-action {
    padding: 0.65rem 1rem;
    border-radius: 0.45rem;
    font-size: 0.88rem;
    font-weight: 800;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    transition: all 120ms ease;
  }

  .btn-manage-content {
    background: var(--brand-navy);
    color: #FAF8F5;
    border: 2px solid var(--brand-navy);
  }

  .btn-edit-course {
    background: var(--paper);
    color: var(--deep-cyan);
    border: 2px solid var(--line);
  }

  .btn-back-dashboard {
    background: var(--card);
    color: var(--muted);
    border: 2px solid var(--line);
    font-size: 0.82rem;
  }

  .btn-panel-action:hover {
    transform: translateY(-1px);
    opacity: 0.95;
  }

  /* Enrollment Panel for Students */
  .enrollment-panel {
    background: var(--card);
    border: 2.5px solid var(--line);
    border-radius: 0.85rem;
    color: var(--storm);
    padding: 1.5rem;
  }

  .enrollment-panel p {
    color: var(--muted);
    margin: 0 0 0.5rem;
    font-weight: 600;
  }

  .enrollment-panel strong {
    display: block;
    font-size: 2rem;
    color: var(--storm);
  }

  .old-price {
    color: #799095;
    display: block;
    text-decoration: line-through;
    font-size: 0.9rem;
  }

  .free-label {
    color: var(--brand-navy);
    display: block;
    font-weight: 800;
    margin-top: 0.35rem;
  }

  .enroll-btn {
    align-items: center;
    background: var(--brand-accent);
    border: 2px solid var(--brand-accent);
    border-radius: 0.5rem;
    color: #ffffff;
    display: flex;
    font: inherit;
    font-weight: 900;
    gap: 0.5rem;
    justify-content: center;
    margin-top: 1.25rem;
    min-height: 3rem;
    text-decoration: none;
    transition: opacity 150ms;
    width: 100%;
    font-size: 1.05rem;
  }

  .enroll-btn:hover {
    opacity: 0.88;
  }

  .enroll-btn.free {
    background: var(--brand-navy);
    color: #FAF8F5;
    border-color: var(--brand-navy);
  }

  .outline {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 0.85rem;
    margin-top: 1.5rem;
    padding: 2rem;
  }

  .outline .kicker {
    color: var(--brand-navy);
  }

  .outline h2 {
    font-size: 1.8rem;
    margin: 0 0 0.5rem;
    color: var(--storm);
    font-weight: 800;
  }

  .outline p:last-child {
    color: var(--muted);
    line-height: 1.8;
    margin: 0;
  }

  .admin-inline-notice {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: 0.75rem;
    padding: 1.25rem;
    margin-top: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .admin-inline-notice p {
    margin: 0;
    font-weight: 600;
    color: var(--storm);
  }

  .btn-inline-manage {
    align-self: flex-start;
    background: var(--storm);
    color: var(--cyan);
    padding: 0.5rem 1rem;
    border-radius: 0.4rem;
    font-weight: 800;
    text-decoration: none;
    font-size: 0.88rem;
  }

  /* Modal */
  .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(26, 25, 24, 0.65);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999;
    padding: 1rem;
  }

  .modal-card {
    background: var(--card);
    border: 2.5px solid var(--line);
    border-radius: 1.25rem;
    max-width: 580px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    padding: 2rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid var(--line);
    padding-bottom: 1rem;
    margin-bottom: 1.25rem;
  }

  .modal-header h2 {
    font-size: 1.35rem;
    font-weight: 900;
    color: var(--storm);
    margin: 0;
  }

  .btn-close-modal {
    background: none;
    border: none;
    font-size: 1.25rem;
    color: var(--muted);
    cursor: pointer;
  }

  .modal-error-banner {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #fdf2f2;
    border: 2px solid #f8b4b4;
    color: #9b1c1c;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.25rem;
    font-size: 0.9rem;
  }

  .modal-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
  }

  .form-group label {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--storm);
  }

  .form-group input, .form-group select, .form-group textarea {
    border: 2px solid var(--line) !important;
    border-radius: 0.5rem;
    padding: 0.65rem 0.85rem;
    font-size: 0.92rem;
    font-family: inherit;
    width: 100%;
    box-sizing: border-box;
  }

  .modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px solid var(--line);
  }

  .btn-cancel {
    background: var(--card);
    border: 2px solid var(--line);
    padding: 0.6rem 1.25rem;
    border-radius: 0.5rem;
    font-weight: 700;
    color: var(--storm);
    cursor: pointer;
  }

  .btn-save {
    background: var(--storm);
    color: var(--cyan);
    border: 2px solid var(--storm);
    padding: 0.6rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 800;
    cursor: pointer;
  }

  /* Curriculum Syllabus */
  .curriculum-list {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    margin-top: 1.5rem;
  }

  .curriculum-section {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: 1rem;
    overflow: hidden;
  }

  .curriculum-section-header {
    background: var(--card);
    padding: 0.85rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-bottom: 1.5px solid var(--line);
  }

  .section-idx {
    font-size: 0.75rem;
    font-weight: 700;
    background: var(--deep-cyan);
    color: #ffffff;
    padding: 0.15rem 0.5rem;
    border-radius: 0.35rem;
  }

  .curriculum-section-header h3 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--storm);
    flex: 1;
  }

  .section-badge {
    font-size: 0.75rem;
    color: var(--muted);
    font-weight: 600;
  }

  .curriculum-items {
    display: flex;
    flex-direction: column;
  }

  .curriculum-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.85rem 1.25rem;
    border-bottom: 1px solid var(--line);
    gap: 1rem;
    transition: background 150ms ease;
  }

  .curriculum-item:last-child {
    border-bottom: none;
  }

  .curriculum-item.is-preview {
    background: rgba(245, 158, 11, 0.06);
  }

  :global([data-theme='dark']) .curriculum-item.is-preview {
    background: rgba(245, 158, 11, 0.12);
  }

  .item-title-wrap {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
  }

  .item-icon-tag {
    font-size: 1.1rem;
  }

  .item-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--storm);
  }

  .preview-tag {
    font-size: 0.72rem;
    font-weight: 800;
    background: #fef3c7;
    color: #92400e;
    padding: 0.15rem 0.5rem;
    border-radius: 9999px;
    border: 1px solid #fde68a;
  }

  :global([data-theme='dark']) .preview-tag {
    background: rgba(245, 158, 11, 0.2);
    color: #fcd34d;
    border-color: rgba(245, 158, 11, 0.35);
  }

  .item-status-wrap {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-watch-preview {
    background: #059669;
    color: #ffffff;
    font-weight: 800;
    font-size: 0.8rem;
    padding: 0.4rem 0.85rem;
    border-radius: 0.45rem;
    border: none;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(5, 150, 105, 0.25);
    transition: opacity 150ms ease;
  }

  .btn-watch-preview:hover {
    opacity: 0.9;
  }

  .lock-indicator {
    font-size: 0.8rem;
    color: var(--muted);
    font-weight: 600;
  }

  .btn-item-open {
    font-size: 0.8rem;
    color: var(--deep-cyan);
    text-decoration: none;
    font-weight: 700;
  }

  /* Preview Player Modal */
  .preview-player-modal {
    max-width: 760px;
  }

  .preview-modal-titles {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .preview-modal-titles h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--storm);
  }

  .preview-tag-modal {
    font-size: 0.72rem;
    font-weight: 800;
    background: #fef3c7;
    color: #92400e;
    padding: 0.15rem 0.5rem;
    border-radius: 9999px;
    width: fit-content;
  }

  .preview-player-body {
    margin: 1.25rem 0;
  }

  .video-responsive-wrapper {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 ratio */
    height: 0;
    overflow: hidden;
    border-radius: 0.75rem;
    background: #000;
  }

  .video-responsive-wrapper iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  }

  .external-preview-card {
    background: var(--paper);
    border: 2px dashed var(--line);
    border-radius: 0.75rem;
    padding: 2.5rem 1.5rem;
    text-align: center;
    color: var(--storm);
  }

  .btn-open-external {
    display: inline-block;
    margin-top: 1rem;
    background: var(--deep-cyan);
    color: #ffffff;
    font-weight: 700;
    padding: 0.6rem 1.25rem;
    border-radius: 0.5rem;
    text-decoration: none;
  }

  .preview-modal-footer {
    border-top: 1.5px solid var(--line);
    padding-top: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    align-items: center;
    text-align: center;
  }

  .preview-modal-footer p {
    margin: 0;
    font-size: 0.88rem;
    color: var(--muted);
  }

  .btn-enroll-from-preview {
    background: var(--storm);
    color: var(--cyan);
    padding: 0.65rem 1.75rem;
    border-radius: 0.5rem;
    font-weight: 800;
    text-decoration: none;
  }

  @media (max-width: 760px) {
    .detail-shell { padding-inline: 1rem; }
    .course-hero { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
    .curriculum-item { flex-direction: column; align-items: flex-start; }
  }
</style>