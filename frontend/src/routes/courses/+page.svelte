<script lang="ts">
  import type { PageData } from './$types';
  import { currentUser } from '$lib/api/auth';
  import { createAdminCourse, updateAdminCourse } from '$lib/api/admin';

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

  // Client search filter
  let searchQuery = $state('');

  let filteredCourses = $derived(
    data.courses.filter((course) => {
      if (!searchQuery.trim()) return true;
      const q = searchQuery.toLowerCase().trim();
      return (
        course.title?.ar?.toLowerCase().includes(q) ||
        course.title?.en?.toLowerCase().includes(q) ||
        course.slug?.toLowerCase().includes(q) ||
        course.description?.ar?.toLowerCase().includes(q)
      );
    })
  );

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
  <title>الدليل الأكاديمي للدورات | منصة Codeera</title>
  <meta name="description" content="تصفح جميع المقررات الدراسية التخصصية في البرمجة وعلوم الحاسب في منصة Codeera." />
</svelte:head>

<div class="catalog-shell">
  <!-- Immersive Catalog Header -->
  <header class="catalog-hero">
    <div class="hero-mesh-overlay" aria-hidden="true"></div>
    <div class="catalog-hero-inner">
      <div class="header-top-pill">
        <span class="pill-dot"></span>
        <span>الدليل الأكاديمي الشامل لجميع الفرق والأقسام</span>
      </div>
      <h1 class="catalog-title">اختر مقررك الدراسي وابدأ رحلة التفوق</h1>
      <p class="catalog-subtitle">
        محتوى تعليمي أكاديمي دقيق، شروحات مسجلة، بنك أسئلة واختبارات تفاعلية، مع وصول فوري لمجموعات التليجرام الخاصة بكل مادة.
      </p>

      <!-- Admin Quick Action Toolbar -->
      {#if isAdmin}
        <div class="admin-toolbar-card">
          <div class="admin-toolbar-info">
            <span class="admin-shield-icon">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
              </svg>
            </span>
            <div>
              <strong>صلاحيات إدارة المقررات</strong>
              <p>يمكنك إنشاء مقررات جديدة أو تعديل المحتوى والأسعار مباشرة من هنا.</p>
            </div>
          </div>
          <button type="button" class="btn-create-course" onclick={openCreateModal}>
            <span>إضافة مقرر دراسي جديد</span>
          </button>
        </div>
      {/if}
    </div>
  </header>

  <!-- Filter and Search Bar -->
  <section class="controls-section" aria-label="أدوات البحث والتصفية">
    <div class="search-box-wrap">
      <svg class="search-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <input
        type="search"
        class="search-input"
        placeholder="ابحث بالاسم أو الرمز (مثال: برمجيات، ذكاء اصطناعي، CS)..."
        bind:value={searchQuery}
        aria-label="البحث في المقررات"
      />
    </div>

    <form class="filters-form" method="GET" aria-label="تصفية الدورات حسب الفرقة والقسم">
      <div class="select-group">
        <label for="filter-year" class="filter-label">الفرقة الدراسية:</label>
        <select id="filter-year" name="academic_year_id" class="filter-select">
          <option value="">كل الفرق الدراسية</option>
          {#each data.academicYears as year}
            <option value={year.id} selected={String(year.id) === data.selectedAcademicYear}>{year.name.ar}</option>
          {/each}
        </select>
      </div>

      <div class="select-group">
        <label for="filter-dept" class="filter-label">القسم الأكاديمي:</label>
        <select id="filter-dept" name="department_id" class="filter-select">
          <option value="">جميع الأقسام</option>
          {#each data.departments as department}
            <option value={department.id} selected={String(department.id) === data.selectedDepartment}>{department.name.ar}</option>
          {/each}
        </select>
      </div>

      <button type="submit" class="btn-apply-filters">
        <span>تطبيق الفلتر</span>
      </button>

      {#if data.selectedAcademicYear || data.selectedDepartment}
        <a href="/courses" class="btn-reset-filters">
          <span>إلغاء التصفية</span>
        </a>
      {/if}
    </form>
  </section>

  <!-- Courses Grid or Empty State -->
  {#if data.error}
    <div class="notice-box error" role="alert">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
        <line x1="12" y1="9" x2="12" y2="13"></line>
        <line x1="12" y1="17" x2="12.01" y2="17"></line>
      </svg>
      <p>{data.error}</p>
    </div>
  {:else if filteredCourses.length === 0}
    <div class="empty-catalog-box">
      <div class="empty-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </div>
      <h3>لا توجد مقررات مطابقة للبحث أو الفلتر المحدد</h3>
      <p>جرب تغيير خيارات التصفية أو مسح كلمة البحث للعثور على المقررات المتاحة.</p>
      {#if searchQuery || data.selectedAcademicYear || data.selectedDepartment}
        <button
          type="button"
          class="btn-clear-search"
          onclick={() => { searchQuery = ''; window.location.href = '/courses'; }}
        >
          عرض كافة المقررات
        </button>
      {/if}
    </div>
  {:else}
    <div class="catalog-results-header">
      <span class="results-count">
        تم العثور على <strong>{filteredCourses.length}</strong> مقرر دراسي
      </span>
    </div>

    <section class="courses-card-grid" aria-label="قائمة المقررات">
      {#each filteredCourses as course}
        <article class="course-card-premium" class:course-admin-card={isAdmin}>
          <div class="card-hero-stripe">
            <div class="stripe-badge-left">
              <span class="course-code-pill">{course.slug}</span>
              {#if course.discount_cents > 0}
                <span class="pill-discount">خصم خاص</span>
              {/if}
            </div>

            {#if isAdmin}
              <span class="badge-admin-status" class:status-draft={course.status === 'draft'}>
                {course.status === 'published' ? 'منشور' : 'مسودة'}
              </span>
            {/if}
          </div>

          <div class="card-main-content">
            <div class="card-title-group">
              <h2 class="course-title-ar">{course.title.ar}</h2>
              {#if course.title.en}
                <p class="course-title-en" dir="ltr">{course.title.en}</p>
              {/if}
            </div>

            <p class="course-description">
              {course.description.ar || 'محتوى دراسي منظم يتضمن شرحاً تفصيلياً لأهم محاور المقرر واختبارات تفاعلية دورية.'}
            </p>
          </div>

          <!-- Bottom Footer Bar -->
          {#if isAdmin}
            <div class="admin-controls-footer">
              <div class="admin-price-line">
                <span class="price-lbl">سعر الاشتراك:</span>
                <strong>{formatPrice(course.amount_due_cents || course.price_cents || 0)}</strong>
              </div>
              <div class="admin-action-buttons">
                <button
                  type="button"
                  class="btn-adm-edit"
                  onclick={() => openEditModal(course)}
                  title="تعديل بيانات المقرر"
                >
                  تعديل
                </button>
                <a href={`/my-courses/${course.slug}`} class="btn-adm-content" title="إدارة المحتوى">
                  المحتوى
                </a>
                <a href={`/courses/${course.slug}`} class="btn-adm-view" title="عرض كما يظهر للطلاب">
                  عرض
                </a>
              </div>
            </div>
          {:else}
            <div class="student-pricing-footer">
              <div class="price-container">
                <span class="price-sub">رسوم المقرر</span>
                <div class="price-figures">
                  {#if course.discount_cents > 0}
                    <span class="price-old">{formatPrice(course.list_price_cents)}</span>
                  {/if}
                  <strong class="price-current">{formatPrice(course.amount_due_cents)}</strong>
                </div>
              </div>

              <a href={`/courses/${course.slug}`} class="btn-explore-course">
                <span>استكشف المقرر</span>
                <span class="btn-arrow" aria-hidden="true">←</span>
              </a>
            </div>
          {/if}
        </article>
      {/each}
    </section>

    <!-- Pagination -->
    {#if data.pagination.last_page > 1}
      <nav class="pagination-bar" aria-label="صفحات المقررات">
        {#if data.pagination.current_page > 1}
          <a href={pageHref(data.pagination.current_page - 1)} class="page-nav-btn">السابق</a>
        {:else}
          <span class="page-nav-btn disabled">السابق</span>
        {/if}

        <span class="page-indicator">
          صفحة <strong>{data.pagination.current_page}</strong> من <strong>{data.pagination.last_page}</strong>
        </span>

        {#if data.pagination.current_page < data.pagination.last_page}
          <a href={pageHref(data.pagination.current_page + 1)} class="page-nav-btn">التالي</a>
        {:else}
          <span class="page-nav-btn disabled">التالي</span>
        {/if}
      </nav>
    {/if}
  {/if}
</div>

<!-- Admin Modal: Create / Edit Course -->
{#if isModalOpen}
  <div class="modal-backdrop" onclick={closeModal} role="presentation">
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <div
      class="modal-card"
      onclick={(e) => e.stopPropagation()}
      role="dialog"
      aria-modal="true"
      dir="rtl"
      tabindex="-1"
    >
      <div class="modal-header">
        <h2>{isEditing ? 'تعديل بيانات المقرر' : 'إضافة مقرر دراسي جديد'}</h2>
        <button type="button" class="btn-close-modal" onclick={closeModal} aria-label="إغلاق">&times;</button>
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
    max-width: 1200px;
    margin-inline: auto;
    padding: 1.5rem 1.25rem 5rem;
    display: flex;
    flex-direction: column;
    gap: 2.5rem;
  }

  /* ---------------- HERO BANNER ---------------- */
  .catalog-hero {
    position: relative;
    overflow: hidden;
    background: radial-gradient(135% 120% at 50% 0%, #1E2B4D 0%, #1A1918 100%);
    border: 2px solid var(--line);
    border-radius: 1.5rem;
    padding: clamp(2.5rem, 5vw, 4rem) clamp(1.5rem, 4vw, 3rem);
    color: white;
    box-shadow: var(--shadow-md);
  }

  :global(:root[data-theme='dark']) .catalog-hero {
    background: radial-gradient(135% 120% at 50% 0%, #17223D 0%, #121211 100%);
    border-color: rgba(91, 122, 199, 0.25);
  }

  .hero-mesh-overlay {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(213, 203, 193, 0.15) 1px, transparent 1px);
    background-size: 24px 24px;
    pointer-events: none;
    opacity: 0.4;
  }

  .catalog-hero-inner {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 1.25rem;
    max-width: 800px;
    margin-inline: auto;
  }

  .header-top-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1.5px solid rgba(200, 43, 52, 0.35);
    padding: 0.35rem 0.95rem;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 700;
    color: #FAF8F5;
    backdrop-filter: blur(8px);
  }

  .pill-dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    background: var(--brand-accent);
    box-shadow: 0 0 6px var(--brand-accent);
  }

  .catalog-title {
    font-size: clamp(1.8rem, 3.8vw, 2.6rem);
    font-weight: 900;
    line-height: 1.3;
    margin: 0;
    color: white;
  }

  .catalog-subtitle {
    font-size: clamp(0.95rem, 1.6vw, 1.05rem);
    color: #D5CBC1;
    line-height: 1.7;
    margin: 0;
  }

  /* Admin Toolbar in Hero */
  .admin-toolbar-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.25rem;
    width: 100%;
    background: rgba(26, 25, 24, 0.7);
    border: 2px solid rgba(213, 203, 193, 0.3);
    border-radius: 1rem;
    padding: 1.25rem 1.5rem;
    margin-top: 1rem;
    text-align: right;
    backdrop-filter: blur(12px);
    flex-wrap: wrap;
  }

  .admin-toolbar-info {
    display: flex;
    align-items: center;
    gap: 0.85rem;
  }

  .admin-shield-icon {
    font-size: 1.75rem;
  }

  .admin-toolbar-info strong {
    display: block;
    color: var(--brand-accent);
    font-size: 0.95rem;
    font-weight: 800;
  }

  .admin-toolbar-info p {
    margin: 0;
    font-size: 0.82rem;
    color: #D5CBC1;
  }

  .btn-create-course {
    background: var(--brand-accent);
    color: #ffffff;
    font-weight: 800;
    font-size: 0.92rem;
    padding: 0.65rem 1.25rem;
    border-radius: 0.5rem;
    border: 2px solid var(--brand-accent);
    cursor: pointer;
    font-family: inherit;
    transition: transform 120ms ease, box-shadow 120ms ease;
  }

  .btn-create-course:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 18px rgba(var(--brand-accent-rgb), 0.5);
  }

  /* ---------------- CONTROLS & FILTERS ---------------- */
  .controls-section {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
  }

  .search-box-wrap {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
  }

  .search-icon {
    position: absolute;
    right: 1rem;
    color: var(--muted);
    pointer-events: none;
  }

  .search-input {
    width: 100%;
    padding: 0.85rem 3rem 0.85rem 1rem;
    font-size: 0.95rem;
    font-family: inherit;
    border: 2px solid var(--line);
    border-radius: 0.75rem;
    background: var(--paper);
    color: var(--storm);
    box-sizing: border-box;
    transition: border-color 150ms ease, background-color 150ms ease;
  }

  .search-input:focus {
    border-color: var(--deep-cyan);
    background-color: var(--card);
    outline: none;
  }

  .filters-form {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .select-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    min-width: 220px;
  }

  .filter-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--muted);
    white-space: nowrap;
  }

  .filter-select {
    flex: 1;
    padding: 0.65rem 1rem;
    border-radius: 0.6rem;
    border: 2px solid var(--line);
    background: var(--paper);
    color: var(--storm);
    font-size: 0.9rem;
    font-family: inherit;
    cursor: pointer;
  }

  .btn-apply-filters {
    background: var(--brand-navy);
    color: #FAF8F5;
    border: 2px solid var(--brand-navy);
    padding: 0.65rem 1.35rem;
    border-radius: 0.6rem;
    font-size: 0.9rem;
    font-weight: 800;
    cursor: pointer;
    font-family: inherit;
    transition: transform 120ms ease, opacity 120ms ease, background-color 120ms ease;
  }

  .btn-apply-filters:hover {
    transform: translateY(-1px);
    opacity: 0.95;
    background-color: #1E2B4D;
  }

  .btn-reset-filters {
    color: #e53e3e;
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
    padding: 0.65rem 0.85rem;
    border-radius: 0.5rem;
    border: 1.5px solid #feb2b2;
    background: #fff5f5;
  }

  /* ---------------- RESULTS & CARDS ---------------- */
  .catalog-results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: -1rem;
  }

  .results-count {
    font-size: 0.92rem;
    color: var(--muted);
  }

  .results-count strong {
    color: var(--storm);
  }

  .courses-card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
  }

  .course-card-premium {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 1.25rem;
    box-shadow: var(--shadow-sm);
    transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
  }

  .course-card-premium:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
    border-color: var(--deep-cyan);
  }

  :global(:root[data-theme='dark']) .course-card-premium:hover {
    border-color: var(--brand-accent);
    box-shadow: 0 10px 30px rgba(var(--brand-accent-rgb), 0.15);
  }

  .course-admin-card {
    border-left: 4px solid var(--deep-cyan);
  }

  .card-hero-stripe {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
  }

  .stripe-badge-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .course-code-pill {
    font-size: 0.75rem;
    font-weight: 800;
    color: var(--deep-cyan);
    background: var(--paper);
    padding: 0.2rem 0.6rem;
    border-radius: 0.35rem;
    border: 1px solid var(--line);
    text-transform: uppercase;
  }

  :global(:root[data-theme='dark']) .course-code-pill {
    color: var(--deep-cyan);
    background: rgba(91, 122, 199, 0.15);
    border-color: rgba(91, 122, 199, 0.3);
  }

  .pill-discount {
    font-size: 0.72rem;
    font-weight: 800;
    color: #92400e;
    background: #fef3c7;
    padding: 0.2rem 0.5rem;
    border-radius: 0.35rem;
  }

  .badge-admin-status {
    font-size: 0.72rem;
    font-weight: 800;
    color: #065f46;
    background: #d1fae5;
    padding: 0.15rem 0.5rem;
    border-radius: 0.25rem;
  }

  .status-draft {
    color: #991b1b;
    background: #fee2e2;
  }

  .card-main-content {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    flex: 1;
  }

  .card-title-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .course-title-ar {
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
    line-height: 1.35;
  }

  .course-title-en {
    font-size: 0.82rem;
    color: var(--muted);
    margin: 0;
    font-family: inherit;
  }

  .course-description {
    font-size: 0.88rem;
    color: var(--muted);
    line-height: 1.6;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  /* Student Pricing Footer */
  .student-pricing-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 1.25rem;
    border-top: 2px solid var(--line);
  }

  .price-container {
    display: flex;
    flex-direction: column;
  }

  .price-sub {
    font-size: 0.72rem;
    color: var(--muted);
  }

  .price-figures {
    display: flex;
    align-items: baseline;
    gap: 0.45rem;
  }

  .price-old {
    font-size: 0.82rem;
    color: var(--muted);
    text-decoration: line-through;
  }

  .price-current {
    font-size: 1.25rem;
    font-weight: 900;
    color: var(--storm);
  }

  .btn-explore-course {
    background: var(--brand-navy);
    color: #FAF8F5;
    font-size: 0.88rem;
    font-weight: 800;
    padding: 0.55rem 1.15rem;
    border-radius: 0.5rem;
    border: 2px solid var(--brand-navy);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    transition: transform 120ms ease, opacity 120ms ease, background-color 120ms ease, border-color 120ms ease;
  }

  .btn-explore-course:hover {
    transform: translateY(-1px);
    opacity: 0.95;
    background-color: var(--brand-accent);
    border-color: var(--brand-accent);
    color: #ffffff;
  }

  .btn-arrow {
    transition: transform 120ms ease;
  }

  .btn-explore-course:hover .btn-arrow {
    transform: translateX(-3px);
  }

  /* Admin Controls Footer */
  .admin-controls-footer {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding-top: 1.25rem;
    border-top: 2px solid var(--line);
  }

  .admin-price-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    color: var(--muted);
  }

  .admin-price-line strong {
    color: var(--storm);
  }

  .admin-action-buttons {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
  }

  .btn-adm-edit, .btn-adm-content, .btn-adm-view {
    padding: 0.45rem 0.5rem;
    font-size: 0.82rem;
    font-weight: 800;
    border-radius: 0.4rem;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
    transition: opacity 120ms ease;
  }

  .btn-adm-edit {
    background: var(--brand-navy);
    color: #FAF8F5;
    border: 2px solid var(--brand-navy);
  }

  .btn-adm-content {
    background: var(--paper);
    color: var(--deep-cyan);
    border: 2px solid var(--line);
  }

  .btn-adm-view {
    background: var(--card);
    color: var(--storm);
    border: 2px solid var(--line);
  }

  .btn-adm-edit:hover, .btn-adm-content:hover, .btn-adm-view:hover {
    opacity: 0.9;
  }

  /* ---------------- EMPTY STATE ---------------- */
  .empty-catalog-box {
    background: var(--card);
    border: 2px dashed var(--line);
    border-radius: 1.25rem;
    padding: 3.5rem 1.5rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.85rem;
  }

  .empty-icon {
    font-size: 2.5rem;
  }

  .empty-catalog-box h3 {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .empty-catalog-box p {
    color: var(--muted);
    font-size: 0.92rem;
    max-width: 440px;
    margin: 0;
  }

  .btn-clear-search {
    background: var(--brand-navy);
    color: #FAF8F5;
    border: 2px solid var(--brand-navy);
    padding: 0.65rem 1.35rem;
    border-radius: 0.5rem;
    font-weight: 800;
    font-size: 0.9rem;
    cursor: pointer;
    font-family: inherit;
    margin-top: 0.5rem;
  }

  /* ---------------- PAGINATION ---------------- */
  .pagination-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    margin-top: 1rem;
  }

  .page-nav-btn {
    background: var(--card);
    border: 2px solid var(--line);
    color: var(--storm);
    padding: 0.55rem 1.25rem;
    border-radius: 0.5rem;
    font-weight: 700;
    font-size: 0.88rem;
    text-decoration: none;
    transition: border-color 150ms ease;
  }

  .page-nav-btn:hover:not(.disabled) {
    border-color: var(--deep-cyan);
  }

  .page-nav-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .page-indicator {
    font-size: 0.9rem;
    color: var(--muted);
  }

  .page-indicator strong {
    color: var(--storm);
  }

  /* ---------------- MODAL STYLES ---------------- */
  .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(26, 25, 24, 0.65);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
  }

  .modal-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    padding: 2rem;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
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
    border: 2px solid var(--line);
    border-radius: 0.5rem;
    padding: 0.65rem 0.85rem;
    font-size: 0.92rem;
    font-family: inherit;
    width: 100%;
    box-sizing: border-box;
    background: var(--paper);
    color: var(--storm);
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
    background: var(--paper);
    border: 2px solid var(--line);
    padding: 0.6rem 1.25rem;
    border-radius: 0.5rem;
    font-weight: 700;
    color: var(--storm);
    cursor: pointer;
    font-family: inherit;
  }

  .btn-save {
    background: var(--brand-navy);
    color: #FAF8F5;
    border: 2px solid var(--brand-navy);
    padding: 0.6rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 800;
    cursor: pointer;
    font-family: inherit;
  }

  .notice-box {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 0.75rem;
    padding: 1.5rem;
    text-align: center;
  }

  .notice-box.error {
    border-color: #f87171;
    color: #991b1b;
  }

  /* ---------------- RESPONSIVENESS ---------------- */
  @media (max-width: 768px) {
    .catalog-shell {
      padding: 1rem 0.75rem 3.5rem;
      gap: 1.75rem;
    }

    .admin-toolbar-card {
      flex-direction: column;
      align-items: stretch;
    }

    .btn-create-course {
      width: 100%;
      text-align: center;
    }

    .filters-form {
      flex-direction: column;
      align-items: stretch;
    }

    .select-group {
      width: 100%;
    }

    .btn-apply-filters {
      width: 100%;
    }

    .courses-card-grid {
      grid-template-columns: 1fr;
    }

    .form-row {
      grid-template-columns: 1fr;
    }
  }
</style>