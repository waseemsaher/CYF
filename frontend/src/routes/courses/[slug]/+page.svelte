<script lang="ts">
  import type { PageData } from './$types';
  import { currentUser } from '$lib/api/auth';
  import { updateAdminCourse } from '$lib/api/admin';

  let { data }: { data: PageData } = $props();

  let isAdmin = $derived(
    $currentUser?.role === 'admin' || $currentUser?.role === 'superadmin'
  );

  const formatPrice = (cents: number) => `${(cents / 100).toLocaleString('ar-EG')} جنيه`;

  // Edit Modal State
  let isEditModalOpen = $state(false);
  let editTitleAr = $state('');
  let editTitleEn = $state('');
  let editDescAr = $state('');
  let editDescEn = $state('');
  let editPricePounds = $state(0);
  let editStatus = $state<'published' | 'draft'>('published');
  let editTelegramLink = $state('');
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
      };

      if (editTelegramLink) {
        payload.telegram_invite_link = editTelegramLink;
      }

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
  <title>{data.course.title.ar} | منصة FCAI</title>
  <meta name="description" content={data.course.description.ar} />
</svelte:head>

<div class="detail-shell">
  <nav class="breadcrumb-nav" aria-label="مسار التنقل">
    <a class="back-link" href="/courses"><span aria-hidden="true">→</span> العودة إلى قائمة الدورات</a>
  </nav>

  <section class="course-hero">
    <div class="hero-copy">
      <div class="hero-tag-row">
        <p class="kicker">{isAdmin ? 'لوحة إدارة المقرر الدراسي' : 'دورة متاحة للتسجيل'}</p>
        {#if isAdmin}
          <span class="admin-badge">وضع المسؤول</span>
        {/if}
      </div>
      <h1>{data.course.title.ar}</h1>
      <p class="description">{data.course.description.ar}</p>
    </div>

    <!-- Side Panel: Admin Controls OR Student Enrollment -->
    {#if isAdmin}
      <aside class="admin-panel" aria-label="لوحة تحكم المسؤول في المقرر">
        <div class="panel-header">
          <span class="panel-tag">لوحة المسؤول</span>
          <h3>إدارة المقرر والمحتوى</h3>
        </div>

        <div class="admin-info-box">
          <div class="info-row">
            <span>سعر المقرر:</span>
            <strong>{formatPrice(data.course.amount_due_cents)}</strong>
          </div>
          <div class="info-row">
            <span>حالة المقرر:</span>
            <span class="status-chip">{data.course.status === 'published' ? 'منشور' : 'مسودة'}</span>
          </div>
        </div>

        <div class="admin-buttons-stack">
          <a class="btn-panel-action btn-manage-content" href={`/my-courses/${data.course.slug}`}>
            <span>📂 إدارة المحتوى والمحاضرات</span>
            <span aria-hidden="true">←</span>
          </a>
          <button type="button" class="btn-panel-action btn-edit-course" onclick={openEditModal}>
            <span>✏️ تعديل بيانات وسعر المقرر</span>
          </button>
          <a class="btn-panel-action btn-back-dashboard" href="/admin">
            <span>لوحة تحكم الإدارة الرئيسية</span>
          </a>
        </div>
      </aside>
    {:else}
      <aside class="enrollment-panel" aria-label="تفاصيل السعر والتسجيل">
        <p>السعر الحالي</p>
        {#if data.course.discount_cents > 0}
          <span class="old-price">{formatPrice(data.course.list_price_cents)}</span>
        {/if}
        <strong>{formatPrice(data.course.amount_due_cents)}</strong>
        {#if data.course.amount_due_cents === 0}
          <span class="free-label">مجانية</span>
          <a class="enroll-btn free" href={`/courses/${data.course.slug}/checkout`}>سجّل فورًا — مجانًا</a>
        {:else}
          <a class="enroll-btn" href={`/courses/${data.course.slug}/checkout`}>سجّل الآن <span aria-hidden="true">←</span></a>
        {/if}
      </aside>
    {/if}
  </section>

  <section class="outline">
    <p class="kicker">نظرة عامة على المقرر</p>
    <h2>محتوى وتفاصيل المنهج</h2>
    <p>ستجد داخل الدورة محتوى مرتبًا وروابط المحاضرات والاختبارات الخاصة بالمقرر للمساعدة في المذاكرة والمراجعة النهائية.</p>
    
    {#if isAdmin}
      <div class="admin-inline-notice">
        <p>💡 بصفتك مسؤولاً، يمكنك الوصول للمحتوى وإضافة المحاضرات والاختبارات بالضغط على زر "إدارة المحتوى والمحاضرات" بالأعلى.</p>
        <a href={`/my-courses/${data.course.slug}`} class="btn-inline-manage">
          فتح المحتوى التعليمي الآن ←
        </a>
      </div>
    {/if}
  </section>
</div>

<!-- Edit Course Modal for Admin -->
{#if isEditModalOpen}
  <div class="modal-backdrop" onclick={closeEditModal} role="presentation">
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <div class="modal-card" onclick={(e) => e.stopPropagation()} role="dialog" aria-modal="true" dir="rtl">
      <div class="modal-header">
        <h2>تعديل بيانات المقرر ({data.course.title.ar})</h2>
        <button type="button" class="btn-close-modal" onclick={closeEditModal} aria-label="إغلاق">✕</button>
      </div>

      {#if modalError}
        <div class="modal-error-banner" role="alert">
          <span>⚠️</span>
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
            <label for="edit-telegram">رابط مجموعة تليجرام</label>
            <input
              id="edit-telegram"
              type="url"
              dir="ltr"
              bind:value={editTelegramLink}
              placeholder="https://t.me/..."
            />
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
    background: #0f282f;
    border: 2px solid var(--line);
    border-radius: 1rem;
    color: white;
    display: grid;
    gap: 2rem;
    grid-template-columns: 1fr minmax(280px, 360px);
    padding: clamp(1.5rem, 5vw, 3.5rem);
    box-shadow: 0 10px 30px rgba(15, 40, 47, 0.12);
  }

  .hero-tag-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
  }

  .kicker {
    color: #02eff0;
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
    background: white;
    border: 2.5px solid var(--line);
    border-radius: 0.85rem;
    color: #0f282f;
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
    border: 1.5px solid var(--line);
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
    background: var(--storm);
    color: var(--cyan);
    border: 2px solid var(--storm);
  }

  .btn-edit-course {
    background: #eef7f6;
    color: var(--deep-cyan);
    border: 2px solid var(--line);
  }

  .btn-back-dashboard {
    background: white;
    color: var(--muted);
    border: 1.5px solid var(--line);
    font-size: 0.82rem;
  }

  .btn-panel-action:hover {
    transform: translateY(-1px);
    opacity: 0.95;
  }

  /* Enrollment Panel for Students */
  .enrollment-panel {
    background: white;
    border: 2.5px solid var(--line);
    border-radius: 0.85rem;
    color: #0f282f;
    padding: 1.5rem;
  }

  .enrollment-panel p {
    color: #49636a;
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
    color: #17777a;
    display: block;
    font-weight: 800;
    margin-top: 0.35rem;
  }

  .enroll-btn {
    align-items: center;
    background: #02eff0;
    border: 2px solid #02eff0;
    border-radius: 0.5rem;
    color: #0f282f;
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
    opacity: 0.85;
  }

  .enroll-btn.free {
    background: #17777a;
    color: white;
    border-color: #17777a;
  }

  .outline {
    background: white;
    border: 2px solid var(--line);
    border-radius: 0.85rem;
    margin-top: 1.5rem;
    padding: 2rem;
  }

  .outline .kicker {
    color: #17777a;
  }

  .outline h2 {
    font-size: 1.8rem;
    margin: 0 0 0.5rem;
    color: var(--storm);
    font-weight: 800;
  }

  .outline p:last-child {
    color: #49636a;
    line-height: 1.8;
    margin: 0;
  }

  .admin-inline-notice {
    background: #eef7f6;
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
    background: rgba(15, 40, 47, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999;
    padding: 1rem;
  }

  .modal-card {
    background: white;
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
    border: 1.5px solid #f8b4b4;
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
    background: white;
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

  @media (max-width: 760px) {
    .detail-shell { padding-inline: 1rem; }
    .course-hero { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
  }
</style>