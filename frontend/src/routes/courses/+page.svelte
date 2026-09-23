<script lang="ts">
  import type { PageData } from './$types';
  import { currentUser } from '$lib/api/auth';
  import { createAdminCourse, updateAdminCourse, type AdminCourse } from '$lib/api/admin';

  let { data }: { data: PageData } = $props();

  let isAdmin = $derived(
    $currentUser?.role === 'admin' || $currentUser?.role === 'superadmin'
  );

  let isModalOpen = $state(false);
  let isEditing = $state(false);
  let editingCourseId = $state<number | null>(null);
  let modalLoading = $state(false);
  let modalError = $state('');

  // Course Form State
  let formTitleAr = $state('');
  let formTitleEn = $state('');
  let formSlug = $state('');
  let formDescAr = $state('');
  let formDescEn = $state('');
  let formPricePounds = $state(150);
  let formStatus = $state<'published' | 'draft'>('published');
  let formTelegramLink = $state('');
  let formAcademicYearId = $state<number | ''>('');
  let formDepartmentId = $state<number | ''>('');

  const formatPrice = (cents: number) => `${(cents / 100).toLocaleString('ar-EG')} جنيه`;

  const pageHref = (page: number) => {
    const params = new URLSearchParams();
    if (data.selectedAcademicYear) params.set('academic_year_id', data.selectedAcademicYear);
    if (data.selectedDepartment) params.set('department_id', data.selectedDepartment);
    params.set('page', String(page));
    return `/courses?${params.toString()}`;
  };

  function openCreateModal() {
    isEditing = false;
    editingCourseId = null;
    formTitleAr = '';
    formTitleEn = '';
    formSlug = '';
    formDescAr = '';
    formDescEn = '';
    formPricePounds = 150;
    formStatus = 'published';
    formTelegramLink = '';
    formAcademicYearId = data.academicYears[0]?.id || '';
    formDepartmentId = data.departments[0]?.id || '';
    modalError = '';
    isModalOpen = true;
  }

  function openEditModal(course: any) {
    isEditing = true;
    editingCourseId = course.id;
    formTitleAr = course.title?.ar || '';
    formTitleEn = course.title?.en || '';
    formSlug = course.slug || '';
    formDescAr = course.description?.ar || '';
    formDescEn = course.description?.en || '';
    formPricePounds = Math.round((course.price_cents || course.amount_due_cents || 0) / 100);
    formStatus = (course.status as 'published' | 'draft') || 'published';
    formTelegramLink = course.telegram_invite_link || '';
    formAcademicYearId = data.academicYears[0]?.id || '';
    formDepartmentId = data.departments[0]?.id || '';
    modalError = '';
    isModalOpen = true;
  }

  function closeModal() {
    isModalOpen = false;
    modalError = '';
  }

  function generateSlug() {
    if (!formSlug && formTitleEn) {
      formSlug = formTitleEn
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
    }
  }

  async function handleSaveCourse(e: SubmitEvent) {
    e.preventDefault();
    modalError = '';
    modalLoading = true;

    try {
      const payload: Record<string, unknown> = {
        title: {
          ar: formTitleAr,
          en: formTitleEn || formTitleAr,
        },
        description: {
          ar: formDescAr || 'وصف المادة الدراسية',
          en: formDescEn || 'Course description',
        },
        slug: formSlug,
        price_cents: Math.max(0, Math.round(formPricePounds * 100)),
        status: formStatus,
      };

      if (formTelegramLink) {
        payload.telegram_invite_link = formTelegramLink;
      }

      if (formAcademicYearId && formDepartmentId) {
        payload.audiences = [
          {
            academic_year_id: Number(formAcademicYearId),
            department_id: Number(formDepartmentId),
          },
        ];
      }

      if (isEditing && editingCourseId) {
        await updateAdminCourse(fetch, editingCourseId, payload);
      } else {
        await createAdminCourse(fetch, payload);
      }

      closeModal();
      window.location.reload();
    } catch (err: unknown) {
      modalError = err instanceof Error ? err.message : 'تعذر حفظ بيانات المادة. تأكد من صحة الحقول.';
    } finally {
      modalLoading = false;
    }
  }
</script>

<svelte:head>
  <title>الدورات | منصة FCAI</title>
  <meta name="description" content="تصفح دورات كلية الحاسبات والذكاء الاصطناعي" />
</svelte:head>

<div class="catalog-shell">
  <!-- Intro Banner -->
  <section class="intro">
    <p class="kicker">دورات كلية الحاسبات والذكاء الاصطناعي</p>
    <h1>اختَر مسارك الدراسي</h1>
    <p class="lede">دورات مركزة تساعدك على بناء أساس قوي ومراجعة أهم موضوعات المقرر.</p>
  </section>

  <!-- Admin Quick Actions Bar -->
  {#if isAdmin}
    <div class="admin-top-bar">
      <div class="admin-top-info">
        <span class="admin-badge">صلاحيات المسؤول</span>
        <strong>لوحة التحكم في المقررات والمناهج</strong>
      </div>
      <button type="button" class="btn-create-course" onclick={openCreateModal}>
        <span>➕ إضافة مقرر دراسي جديد</span>
      </button>
    </div>
  {/if}

  <!-- Filter Bar -->
  <form class="filters" method="GET" aria-label="تصفية الدورات">
    <label>
      <span>السنة الدراسية</span>
      <select name="academic_year_id">
        <option value="">كل السنوات</option>
        {#each data.academicYears as year}
          <option value={year.id} selected={String(year.id) === data.selectedAcademicYear}>{year.name.ar}</option>
        {/each}
      </select>
    </label>

    <label>
      <span>القسم</span>
      <select name="department_id">
        <option value="">كل الأقسام</option>
        {#each data.departments as department}
          <option value={department.id} selected={String(department.id) === data.selectedDepartment}>{department.name.ar}</option>
        {/each}
      </select>
    </label>

    <button type="submit">تطبيق الفلاتر</button>
  </form>

  {#if data.error}
    <p class="notice error" role="alert">{data.error}</p>
  {:else if data.courses.length === 0}
    <p class="notice">لا توجد دورات مطابقة للفلاتر الحالية.</p>
  {:else}
    <section class="course-grid" aria-label="الدورات المتاحة">
      {#each data.courses as course}
        <article class="course-card" class:admin-card={isAdmin}>
          <div class="course-card-top">
            <div class="course-mark" aria-hidden="true">
              {course.title.en ? course.title.en.slice(0, 2).toUpperCase() : 'FC'}
            </div>
            {#if isAdmin}
              <span class="admin-chip">مسؤول</span>
            {/if}
          </div>

          <div class="course-copy">
            <p class="course-label">{isAdmin ? 'إدارة المادة' : 'دورة متاحة'}</p>
            <h2>{course.title.ar}</h2>
            <p>{course.description.ar}</p>
          </div>

          {#if isAdmin}
            <!-- Admin Controls on Course Card -->
            <div class="admin-card-actions">
              <div class="admin-card-meta">
                <span>السعر: <strong>{formatPrice(course.amount_due_cents || course.price_cents || 0)}</strong></span>
                <span class="badge-status">{course.status === 'published' ? 'منشورة' : 'مسودة'}</span>
              </div>
              <div class="admin-btn-row">
                <button
                  type="button"
                  class="btn-card-edit"
                  onclick={() => openEditModal(course)}
                >
                  ✏️ تعديل
                </button>
                <a href={`/my-courses/${course.slug}`} class="btn-card-content">
                  📂 المحتوى
                </a>
                <a href={`/courses/${course.slug}`} class="btn-card-view">
                  👁️ عرض
                </a>
              </div>
            </div>
          {:else}
            <!-- Student/Guest Footer -->
            <div class="course-footer">
              <div>
                {#if course.discount_cents > 0}
                  <span class="old-price">{formatPrice(course.list_price_cents)}</span>
                {/if}
                <strong>{formatPrice(course.amount_due_cents)}</strong>
              </div>
              <a href={`/courses/${course.slug}`}>التفاصيل <span aria-hidden="true">←</span></a>
            </div>
          {/if}
        </article>
      {/each}
    </section>

    {#if data.pagination.last_page > 1}
      <nav class="pagination" aria-label="صفحات الدورات">
        {#if data.pagination.current_page > 1}
          <a href={pageHref(data.pagination.current_page - 1)}>السابق</a>
        {:else}
          <span class="disabled">السابق</span>
        {/if}
        <span>صفحة {data.pagination.current_page} من {data.pagination.last_page}</span>
        {#if data.pagination.current_page < data.pagination.last_page}
          <a href={pageHref(data.pagination.current_page + 1)}>التالي</a>
        {:else}
          <span class="disabled">التالي</span>
        {/if}
      </nav>
    {/if}
  {/if}
</div>

<!-- Add/Edit Course Modal for Admin -->
{#if isModalOpen}
  <div class="modal-backdrop" onclick={closeModal} role="presentation">
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <div class="modal-card" onclick={(e) => e.stopPropagation()} role="dialog" aria-modal="true" dir="rtl">
      <div class="modal-header">
        <h2>{isEditing ? 'تعديل بيانات المقرر' : 'إضافة مقرر دراسي جديد'}</h2>
        <button type="button" class="btn-close-modal" onclick={closeModal} aria-label="إغلاق">✕</button>
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
            <label for="course-title-ar">اسم المقرر بالعربية *</label>
            <input
              id="course-title-ar"
              type="text"
              bind:value={formTitleAr}
              required
              placeholder="مثال: هندسة البرمجيات"
            />
          </div>

          <div class="form-group">
            <label for="course-title-en">اسم المقرر بالإنجليزية</label>
            <input
              id="course-title-en"
              type="text"
              dir="ltr"
              bind:value={formTitleEn}
              onblur={generateSlug}
              placeholder="e.g. Software Engineering"
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="course-slug">الرابط الدائم (Slug) *</label>
            <input
              id="course-slug"
              type="text"
              dir="ltr"
              bind:value={formSlug}
              required
              placeholder="software-engineering"
            />
          </div>

          <div class="form-group">
            <label for="course-price">السعر (بالجنيه المصري) *</label>
            <input
              id="course-price"
              type="number"
              min="0"
              step="5"
              bind:value={formPricePounds}
              required
            />
          </div>
        </div>

        <div class="form-group">
          <label for="course-desc-ar">الوصف التعريفي بالمقرر</label>
          <textarea
            id="course-desc-ar"
            rows="3"
            bind:value={formDescAr}
            placeholder="محتوى المقرر، أهدافه، وأهم موضوعاته..."
          ></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="course-status">حالة المقرر</label>
            <select id="course-status" bind:value={formStatus}>
              <option value="published">منشور (يظهر للطلاب للتسجيل)</option>
              <option value="draft">مسودة (مخفي عن الطلاب)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="course-telegram">رابط دعوة تليجرام</label>
            <input
              id="course-telegram"
              type="url"
              dir="ltr"
              bind:value={formTelegramLink}
              placeholder="https://t.me/+joinchat..."
            />
          </div>
        </div>

        {#if !isEditing}
          <div class="form-row">
            <div class="form-group">
              <label for="course-year">الفرقة الدراسية المستهدفة</label>
              <select id="course-year" bind:value={formAcademicYearId}>
                {#each data.academicYears as y}
                  <option value={y.id}>{y.name.ar}</option>
                {/each}
              </select>
            </div>

            <div class="form-group">
              <label for="course-dept">القسم الأكاديمي المستهدف</label>
              <select id="course-dept" bind:value={formDepartmentId}>
                {#each data.departments as d}
                  <option value={d.id}>{d.name.ar}</option>
                {/each}
              </select>
            </div>
          </div>
        {/if}

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick={closeModal} disabled={modalLoading}>
            إلغاء
          </button>
          <button type="submit" class="btn-save" disabled={modalLoading}>
            {#if modalLoading}
              <span>جاري الحفظ...</span>
            {:else}
              <span>{isEditing ? 'حفظ التعديلات' : 'إنشاء المقرر'}</span>
            {/if}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<style>
  .catalog-shell {
    max-width: 1180px;
    margin: 0 auto;
    padding: 2rem 1.25rem 4rem;
  }

  .intro {
    max-width: 700px;
    margin-bottom: 2rem;
  }

  .kicker, .course-label {
    color: var(--deep-cyan);
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    margin: 0 0 0.5rem;
  }

  h1 {
    font-size: clamp(2.2rem, 7vw, 4rem);
    line-height: 1.1;
    margin: 0;
    color: var(--storm);
    font-weight: 900;
  }

  .lede {
    color: var(--muted);
    font-size: 1.05rem;
    line-height: 1.8;
    margin: 1rem 0 0;
  }

  .admin-top-bar {
    background: #eef7f6;
    border: 2px solid var(--line);
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
  }

  .admin-top-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .admin-badge {
    background: var(--storm);
    color: var(--cyan);
    font-size: 0.75rem;
    font-weight: 800;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
  }

  .btn-create-course {
    background: var(--storm);
    color: var(--cyan);
    border: 2px solid var(--storm);
    font-weight: 800;
    font-size: 0.88rem;
    padding: 0.55rem 1.15rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: transform 150ms ease, opacity 150ms ease;
  }

  .btn-create-course:hover {
    transform: translateY(-1px);
    opacity: 0.95;
  }

  .filters {
    align-items: end;
    background: #0f282f;
    border: 2px solid var(--line);
    border-radius: 0.75rem;
    color: white;
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(3, 1fr);
    margin-bottom: 2rem;
    padding: 1rem;
  }

  label {
    display: grid;
    gap: 0.4rem;
  }

  label span {
    font-size: 0.82rem;
    font-weight: 700;
  }

  select, button {
    border-radius: 0.35rem;
    font: inherit;
    min-height: 2.8rem;
    padding: 0.5rem 0.75rem;
  }

  select {
    background: white;
    color: #0f282f;
    border: 2px solid var(--line);
  }

  .filters button {
    background: #02eff0;
    color: #0f282f;
    cursor: pointer;
    font-weight: 800;
    border: 2px solid #02eff0;
  }

  .course-grid {
    display: grid;
    gap: 1.25rem;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  }

  .course-card {
    background: white;
    border: 2px solid var(--line) !important;
    border-radius: 0.85rem;
    display: flex;
    flex-direction: column;
    min-height: 320px;
    padding: 1.25rem;
    box-shadow: 0 4px 12px rgba(15, 40, 47, 0.05);
    transition: transform 150ms ease, border-color 150ms ease;
  }

  .course-card:hover {
    transform: translateY(-2px);
    border-color: var(--deep-cyan) !important;
  }

  .admin-card {
    border-color: var(--line) !important;
  }

  .course-card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .course-mark {
    align-items: center;
    background: #d9fafa;
    border: 2px solid var(--line);
    border-radius: 0.5rem;
    color: #0f282f;
    display: flex;
    font-weight: 900;
    height: 3.25rem;
    justify-content: center;
    width: 3.25rem;
  }

  .admin-chip {
    font-size: 0.7rem;
    font-weight: 800;
    background: #fee2e2;
    color: #991b1b;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
    border: 1px solid #f87171;
  }

  .course-copy {
    flex: 1;
    padding-top: 1.25rem;
  }

  h2 {
    font-size: 1.3rem;
    margin: 0 0 0.65rem;
    color: var(--storm);
    font-weight: 800;
  }

  .course-copy p:last-child {
    color: var(--muted);
    line-height: 1.6;
    margin: 0;
    font-size: 0.92rem;
  }

  .course-footer {
    align-items: end;
    border-top: 2px solid var(--line);
    display: flex;
    justify-content: space-between;
    margin-top: 1.25rem;
    padding-top: 1rem;
  }

  .course-footer strong {
    display: block;
    font-size: 1.15rem;
    color: var(--storm);
  }

  .old-price {
    color: #799095;
    display: block;
    font-size: 0.78rem;
    text-decoration: line-through;
  }

  .course-footer a {
    color: var(--deep-cyan);
    font-weight: 800;
    text-decoration: none;
    font-size: 0.95rem;
  }

  .admin-card-actions {
    border-top: 2px solid var(--line);
    margin-top: 1.25rem;
    padding-top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .admin-card-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
  }

  .badge-status {
    background: #d1fae5;
    color: #065f46;
    font-weight: 800;
    font-size: 0.72rem;
    padding: 0.15rem 0.45rem;
    border-radius: 0.25rem;
  }

  .admin-btn-row {
    display: flex;
    gap: 0.4rem;
  }

  .btn-card-edit, .btn-card-content, .btn-card-view {
    flex: 1;
    text-align: center;
    padding: 0.45rem 0.5rem;
    border-radius: 0.4rem;
    font-size: 0.82rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
    transition: all 120ms ease;
  }

  .btn-card-edit {
    background: var(--storm);
    color: var(--cyan);
    border: 1.5px solid var(--storm);
  }

  .btn-card-content {
    background: #eef7f6;
    color: var(--deep-cyan);
    border: 1.5px solid var(--line);
  }

  .btn-card-view {
    background: white;
    color: var(--storm);
    border: 1.5px solid var(--line);
  }

  .btn-card-edit:hover, .btn-card-content:hover, .btn-card-view:hover {
    transform: translateY(-1px);
    opacity: 0.9;
  }

  .notice {
    background: white;
    border: 2px solid var(--line);
    border-radius: 0.6rem;
    padding: 1.25rem;
    font-weight: 600;
  }

  .error {
    border-color: #b55a55;
    color: #8a302b;
  }

  .pagination {
    align-items: center;
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
  }

  .pagination a, .pagination span {
    color: var(--deep-cyan);
    font-weight: 800;
  }

  .pagination a {
    border: 2px solid var(--line);
    border-radius: 0.35rem;
    padding: 0.55rem 0.85rem;
    text-decoration: none;
  }

  .pagination .disabled {
    color: #9aaeb0;
  }

  /* Modal Styles */
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
    max-width: 620px;
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
    font-size: 1.4rem;
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
    padding: 0.25rem 0.5rem;
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

  .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: var(--deep-cyan) !important;
    outline: none;
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
    .catalog-shell { padding-inline: 1rem; }
    .filters, .course-grid, .form-row { grid-template-columns: 1fr; }
    .filters button { width: 100%; }
  }
</style>