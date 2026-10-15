<script lang="ts">
  import { onMount } from 'svelte';
  import {
    getAdminDiscounts,
    createAdminDiscount,
    updateAdminDiscount,
    deleteAdminDiscount,
    getAdminCourses,
    type AdminDiscount,
    type AdminCourse,
  } from '$lib/api/admin';
  import { currentLocale, formatPrice } from '$lib/i18n';

  let discounts = $state<AdminDiscount[]>([]);
  let courses = $state<AdminCourse[]>([]);
  let loading = $state(true);
  let errorMsg = $state('');

  // Modal State
  let isModalOpen = $state(false);
  let isEditing = $state(false);
  let editingId = $state<number | null>(null);
  let modalLoading = $state(false);
  let modalError = $state('');

  // Form Fields
  let formNameAr = $state('');
  let formNameEn = $state('');
  let formType = $state<'percent' | 'fixed'>('percent');
  let formValue = $state<number>(10);
  let formScope = $state<'all' | 'courses'>('all');
  let formStartsAt = $state('');
  let formEndsAt = $state('');
  let formIsActive = $state(true);
  let formCourseIds = $state<number[]>([]);

  function getTodayString(): string {
    const now = new Date();
    return now.toISOString().split('T')[0];
  }

  function getFutureDateString(daysAhead: number): string {
    const d = new Date();
    d.setDate(d.getDate() + daysAhead);
    return d.toISOString().split('T')[0];
  }

  export async function loadDiscounts() {
    loading = true;
    errorMsg = '';
    try {
      const [discountsRes, coursesRes] = await Promise.all([
        getAdminDiscounts(fetch, 1),
        getAdminCourses(fetch, 1).catch(() => ({ data: [] })),
      ]);
      discounts = discountsRes.data || [];
      courses = coursesRes.data || [];
    } catch (err: unknown) {
      errorMsg = err instanceof Error ? err.message : 'تعذر تحميل بيانات الخصومات.';
    } finally {
      loading = false;
    }
  }

  function openCreateModal() {
    isEditing = false;
    editingId = null;
    formNameAr = '';
    formNameEn = '';
    formType = 'percent';
    formValue = 15;
    formScope = 'all';
    formStartsAt = getTodayString();
    formEndsAt = getFutureDateString(30);
    formIsActive = true;
    formCourseIds = [];
    modalError = '';
    isModalOpen = true;
  }

  function openEditModal(discount: AdminDiscount) {
    isEditing = true;
    editingId = discount.id;
    formNameAr = discount.name?.ar || '';
    formNameEn = discount.name?.en || '';
    formType = discount.type;
    formValue = discount.type === 'fixed' ? Math.round(discount.value / 100) : discount.value;
    formScope = discount.scope;
    formStartsAt = discount.starts_at ? discount.starts_at.slice(0, 10) : getTodayString();
    formEndsAt = discount.ends_at ? discount.ends_at.slice(0, 10) : getFutureDateString(30);
    formIsActive = discount.is_active;
    formCourseIds = discount.course_ids ? [...discount.course_ids] : (discount.courses?.map((c) => c.id) || []);
    modalError = '';
    isModalOpen = true;
  }

  function closeModal() {
    isModalOpen = false;
    editingId = null;
    modalError = '';
  }

  function toggleCourseSelection(courseId: number) {
    if (formCourseIds.includes(courseId)) {
      formCourseIds = formCourseIds.filter((id) => id !== courseId);
    } else {
      formCourseIds = [...formCourseIds, courseId];
    }
  }

  async function handleSaveDiscount(e: SubmitEvent) {
    e.preventDefault();
    modalLoading = true;
    modalError = '';

    if (!formNameAr.trim() || !formNameEn.trim()) {
      modalError = 'يرجى إدخال اسم الخصم بالعربية والإنجليزية.';
      modalLoading = false;
      return;
    }

    if (formType === 'percent' && (formValue <= 0 || formValue > 100)) {
      modalError = 'نسبة الخصم يجب أن تكون بين 1 و 100.';
      modalLoading = false;
      return;
    }

    if (formType === 'fixed' && formValue <= 0) {
      modalError = 'قيمة الخصم الثابت يجب أن تكون أكبر من صفر.';
      modalLoading = false;
      return;
    }

    if (formScope === 'courses' && formCourseIds.length === 0) {
      modalError = 'يرجى اختيار مقرر واحد على الأقل للخصم المخصص.';
      modalLoading = false;
      return;
    }

    const payload = {
      name: {
        ar: formNameAr.trim(),
        en: formNameEn.trim(),
      },
      type: formType,
      value: formType === 'fixed' ? Math.round(formValue * 100) : Math.round(formValue),
      scope: formScope,
      starts_at: formStartsAt,
      ends_at: formEndsAt,
      is_active: formIsActive,
      ...(formScope === 'courses' ? { course_ids: formCourseIds } : {}),
    };

    try {
      if (isEditing && editingId) {
        await updateAdminDiscount(fetch, editingId, payload);
      } else {
        await createAdminDiscount(fetch, payload);
      }
      closeModal();
      await loadDiscounts();
    } catch (err: unknown) {
      modalError = err instanceof Error ? err.message : 'تعذر حفظ بيانات الخصم.';
    } finally {
      modalLoading = false;
    }
  }

  async function handleDeleteDiscount(discount: AdminDiscount) {
    const confirmName = discount.name?.ar || discount.name?.en || `#${discount.id}`;
    if (!confirm(`هل أنت متأكد من حذف الخصم "${confirmName}" نهائياً؟`)) {
      return;
    }

    try {
      await deleteAdminDiscount(fetch, discount.id);
      await loadDiscounts();
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : 'تعذر حذف الخصم.');
    }
  }

  function isCurrentlyValid(discount: AdminDiscount): boolean {
    if (!discount.is_active) return false;
    const now = getTodayString();
    const starts = discount.starts_at ? discount.starts_at.slice(0, 10) : '';
    const ends = discount.ends_at ? discount.ends_at.slice(0, 10) : '';
    return starts <= now && ends >= now;
  }

  onMount(() => {
    loadDiscounts();
  });
</script>

<section class="dash-panel" dir="rtl">
  <div class="panel-top-bar">
    <div>
      <h2>إدارة الخصومات التلقائية ({discounts.length})</h2>
      <p>قائمة العروض والخصومات المطبقة تلقائياً عند تسجيل وشراء المقررات الدراسية.</p>
    </div>
    <div class="top-actions">
      <button
        type="button"
        class="btn-create-discount"
        onclick={openCreateModal}
        data-testid="btn-add-discount"
      >
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>إضافة خصم جديد</span>
      </button>
      <button
        type="button"
        class="btn-refresh"
        onclick={loadDiscounts}
        title="تحديث القائمة"
        aria-label="تحديث قائمة الخصومات"
      >
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
        </svg>
      </button>
    </div>
  </div>

  {#if loading}
    <div class="loading-state">
      <div class="spinner" aria-hidden="true"></div>
      <p>جاري تحميل قائمة الخصومات...</p>
    </div>
  {:else if errorMsg}
    <div class="error-state">
      <p>{errorMsg}</p>
      <button type="button" class="btn-retry" onclick={loadDiscounts}>إعادة المحاولة</button>
    </div>
  {:else if discounts.length === 0}
    <div class="empty-state">
      <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <path d="M9 14l6-6"></path>
        <circle cx="9.5" cy="8.5" r="1.5" fill="currentColor"></circle>
        <circle cx="14.5" cy="13.5" r="1.5" fill="currentColor"></circle>
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
      </svg>
      <h3>لا توجد خصومات مضافة حتى الآن</h3>
      <p>يمكنك إنشاء خصم تلقائي بنسبة مئوية أو قيمة ثابتة وتطبيقه على كل المقررات أو مقررات محددة.</p>
      <button type="button" class="btn-create-discount" onclick={openCreateModal}>
        إضافة أول خصم
      </button>
    </div>
  {:else}
    <div class="table-responsive">
      <table class="admin-table" data-testid="discounts-table">
        <thead>
          <tr>
            <th>اسم الخصم</th>
            <th>نوع وقيمة الخصم</th>
            <th>نطاق التطبيق</th>
            <th>فترة الصلاحية</th>
            <th>الحالة</th>
            <th>الإجراءات</th>
          </tr>
        </thead>
        <tbody>
          {#each discounts as discount (discount.id)}
            <tr data-testid={`discount-row-${discount.id}`}>
              <td>
                <div class="name-cell">
                  <strong class="title-ar">{discount.name?.ar || 'بدون اسم'}</strong>
                  {#if discount.name?.en}
                    <small class="title-en" dir="ltr">{discount.name.en}</small>
                  {/if}
                </div>
              </td>
              <td>
                <div class="value-badge-wrapper">
                  {#if discount.type === 'percent'}
                    <span class="value-pill pill-percent">
                      <span class="val-num">{discount.value}%</span>
                      <small>نسبة مئوية</small>
                    </span>
                  {:else}
                    <span class="value-pill pill-fixed">
                      <span class="val-num">{formatPrice(discount.value, $currentLocale)}</span>
                      <small>مبلغ ثابت</small>
                    </span>
                  {/if}
                </div>
              </td>
              <td>
                {#if discount.scope === 'all'}
                  <span class="scope-pill scope-all">جميع المقررات</span>
                {:else}
                  <div class="scope-courses-box">
                    <span class="scope-pill scope-custom">مقررات محددة ({discount.courses?.length || discount.course_ids?.length || 0})</span>
                    {#if discount.courses && discount.courses.length > 0}
                      <div class="courses-mini-list">
                        {#each discount.courses as c}
                          <span class="course-chip">{c.title?.ar || c.slug}</span>
                        {/each}
                      </div>
                    {/if}
                  </div>
                {/if}
              </td>
              <td>
                <div class="date-cell" dir="ltr">
                  <span>{discount.starts_at ? discount.starts_at.slice(0, 10) : '—'}</span>
                  <span class="date-arrow">&rarr;</span>
                  <span>{discount.ends_at ? discount.ends_at.slice(0, 10) : '—'}</span>
                </div>
              </td>
              <td>
                {#if isCurrentlyValid(discount)}
                  <span class="status-pill pill-green">سارٍ الآن</span>
                {:else if discount.is_active}
                  <span class="status-pill pill-yellow">مفعل (خارج الفترة)</span>
                {:else}
                  <span class="status-pill pill-gray">معطل</span>
                {/if}
              </td>
              <td>
                <div class="action-buttons-group">
                  <button
                    type="button"
                    class="btn-sm btn-edit"
                    onclick={() => openEditModal(discount)}
                    data-testid={`btn-edit-discount-${discount.id}`}
                    title="تعديل بيانات الخصم"
                  >
                    تعديل
                  </button>
                  <button
                    type="button"
                    class="btn-sm btn-delete"
                    onclick={() => handleDeleteDiscount(discount)}
                    data-testid={`btn-delete-discount-${discount.id}`}
                    title="حذف الخصم"
                  >
                    حذف
                  </button>
                </div>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  {/if}
</section>

<!-- Create / Edit Discount Modal -->
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
      data-testid="discount-modal"
    >
      <div class="modal-header">
        <h2>{isEditing ? 'تعديل بيانات الخصم' : 'إضافة خصم تلقائي جديد'}</h2>
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

      <form class="modal-form" onsubmit={handleSaveDiscount}>
        <div class="form-row">
          <div class="form-group">
            <label for="discount-name-ar">اسم الخصم بالعربية *</label>
            <input
              id="discount-name-ar"
              type="text"
              bind:value={formNameAr}
              required
              placeholder="مثال: خصم بداية الفصل الدراسي"
            />
          </div>

          <div class="form-group">
            <label for="discount-name-en">اسم الخصم بالإنجليزية *</label>
            <input
              id="discount-name-en"
              type="text"
              dir="ltr"
              bind:value={formNameEn}
              required
              placeholder="e.g. Early Bird Discount"
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="discount-type">نوع الخصم *</label>
            <select id="discount-type" bind:value={formType}>
              <option value="percent">نسبة مئوية (%)</option>
              <option value="fixed">مبلغ ثابت (ج.م)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="discount-value">
              {formType === 'percent' ? 'النسبة المئوية (%) *' : 'المبلغ بالجنيه (ج.م) *'}
            </label>
            <input
              id="discount-value"
              type="number"
              bind:value={formValue}
              min="1"
              max={formType === 'percent' ? 100 : 100000}
              required
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="discount-scope">نطاق تطبيق الخصم *</label>
            <select id="discount-scope" bind:value={formScope}>
              <option value="all">جميع المقررات (All Courses)</option>
              <option value="courses">مقررات محددة فقط (Selected Courses)</option>
            </select>
          </div>

          <div class="form-group">
            <span class="label-heading">حالة التفعيل</span>
            <div class="checkbox-row-wrapper">
              <label class="checkbox-label" for="discount-is-active">
                <input
                  id="discount-is-active"
                  type="checkbox"
                  bind:checked={formIsActive}
                />
                <span>تفعيل الخصم للطلاب</span>
              </label>
            </div>
          </div>
        </div>

        {#if formScope === 'courses'}
          <div class="courses-selector-box">
            <span class="label-heading">حدد المقررات المشمولة بالخصم * ({formCourseIds.length} محددة)</span>
            {#if courses.length === 0}
              <p class="muted-text">لا توجد مقررات متاحة للاختيار.</p>
            {:else}
              <div class="courses-checkboxes-grid">
                {#each courses as course (course.id)}
                  <label class="course-checkbox-card" class:checked={formCourseIds.includes(course.id)}>
                    <input
                      type="checkbox"
                      checked={formCourseIds.includes(course.id)}
                      onchange={() => toggleCourseSelection(course.id)}
                    />
                    <div class="course-meta">
                      <strong>{course.title?.ar || course.slug}</strong>
                      <span class="price">{formatPrice(course.price_cents, $currentLocale)}</span>
                    </div>
                  </label>
                {/each}
              </div>
            {/if}
          </div>
        {/if}

        <div class="form-row">
          <div class="form-group">
            <label for="discount-starts-at">تاريخ البداية *</label>
            <input
              id="discount-starts-at"
              type="date"
              bind:value={formStartsAt}
              required
            />
          </div>

          <div class="form-group">
            <label for="discount-ends-at">تاريخ النهاية *</label>
            <input
              id="discount-ends-at"
              type="date"
              bind:value={formEndsAt}
              min={formStartsAt}
              required
            />
          </div>
        </div>

        <div class="modal-footer-actions">
          <button type="button" class="btn-cancel" onclick={closeModal}>إلغاء</button>
          <button
            type="submit"
            class="btn-save"
            disabled={modalLoading}
            data-testid="btn-save-discount"
          >
            {#if modalLoading}
              <span class="spinner-small" aria-hidden="true"></span>
              <span>جاري الحفظ...</span>
            {:else}
              <span>{isEditing ? 'حفظ التعديلات' : 'إضافة الخصم'}</span>
            {/if}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<style>
  .dash-panel {
    background: var(--card, #1F1D1B);
    border: 1px solid var(--border, #3D3A36);
    border-radius: 14px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .panel-top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border, #3D3A36);
  }

  .panel-top-bar h2 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
    color: var(--text, #F5F3EF);
  }

  .panel-top-bar p {
    font-size: 0.875rem;
    color: var(--muted, #B0AAA0);
    margin: 0;
  }

  .top-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .btn-create-discount {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--brand, #3b82f6);
    color: #ffffff;
    border: none;
    padding: 0.625rem 1.125rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s ease;
  }

  .btn-create-discount:hover {
    background: #2563eb;
  }

  .btn-refresh {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: 1px solid var(--border, #3D3A36);
    color: var(--muted, #B0AAA0);
    padding: 0.5rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .btn-refresh:hover {
    color: var(--text, #F5F3EF);
    border-color: var(--muted, #B0AAA0);
  }

  .loading-state, .error-state, .empty-state {
    padding: 3rem 1.5rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    color: var(--muted, #B0AAA0);
  }

  .spinner {
    width: 32px;
    height: 32px;
    border: 3px solid rgba(255, 255, 255, 0.1);
    border-top-color: var(--brand, #3b82f6);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .table-responsive {
    overflow-x: auto;
  }

  .admin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
    text-align: right;
  }

  .admin-table th {
    background: rgba(255, 255, 255, 0.03);
    color: var(--muted, #B0AAA0);
    font-weight: 600;
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--border, #3D3A36);
  }

  .admin-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border, #3D3A36);
    color: var(--text, #F5F3EF);
    vertical-align: middle;
  }

  .name-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .title-ar {
    font-size: 0.95rem;
    font-weight: 600;
  }

  .title-en {
    color: var(--muted, #B0AAA0);
    font-size: 0.8rem;
  }

  .value-badge-wrapper {
    display: inline-flex;
  }

  .value-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.625rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.85rem;
  }

  .value-pill.pill-percent {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
  }

  .value-pill.pill-fixed {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.3);
  }

  .value-pill small {
    font-size: 0.7rem;
    font-weight: 400;
    opacity: 0.85;
  }

  .scope-pill {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .scope-all {
    background: rgba(139, 92, 246, 0.15);
    color: #a78bfa;
  }

  .scope-custom {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
  }

  .scope-courses-box {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
  }

  .courses-mini-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
  }

  .course-chip {
    font-size: 0.7rem;
    background: rgba(255, 255, 255, 0.05);
    padding: 0.125rem 0.375rem;
    border-radius: 4px;
    color: var(--muted, #B0AAA0);
  }

  .date-cell {
    font-family: monospace;
    font-size: 0.8rem;
    color: var(--muted, #B0AAA0);
    display: flex;
    align-items: center;
    gap: 0.375rem;
  }

  .date-arrow {
    color: var(--muted, #B0AAA0);
  }

  .status-pill {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .pill-green {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
  }

  .pill-yellow {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
  }

  .pill-gray {
    background: rgba(156, 163, 175, 0.15);
    color: #9ca3af;
  }

  .action-buttons-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-sm {
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: opacity 0.2s;
  }

  .btn-sm:hover {
    opacity: 0.85;
  }

  .btn-edit {
    background: rgba(255, 255, 255, 0.08);
    color: var(--text, #F5F3EF);
  }

  .btn-delete {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
  }

  /* Modal Styles */
  .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
    overflow-y: auto;
  }

  .modal-card {
    background: var(--card, #1F1D1B);
    border: 1px solid var(--border, #3D3A36);
    border-radius: 14px;
    width: 100%;
    max-width: 640px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 1.75rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--border, #3D3A36);
  }

  .modal-header h2 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    color: var(--text, #F5F3EF);
  }

  .btn-close-modal {
    background: transparent;
    border: none;
    font-size: 1.5rem;
    color: var(--muted, #B0AAA0);
    cursor: pointer;
  }

  .modal-error-banner {
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #f87171;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 0.625rem;
    font-size: 0.85rem;
  }

  .modal-form {
    display: flex;
    flex-direction: column;
    gap: 1.125rem;
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }

  @media (max-width: 600px) {
    .form-row {
      grid-template-columns: 1fr;
    }
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
  }

  .form-group label, .label-heading {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text, #F5F3EF);
  }

  .form-group input[type="text"],
  .form-group input[type="number"],
  .form-group input[type="date"],
  .form-group select {
    background: rgba(0, 0, 0, 0.25);
    border: 1px solid var(--border, #3D3A36);
    border-radius: 8px;
    padding: 0.625rem 0.875rem;
    color: var(--text, #F5F3EF);
    font-size: 0.875rem;
    outline: none;
  }

  .form-group input:focus,
  .form-group select:focus {
    border-color: var(--brand, #3b82f6);
  }

  .checkbox-row-wrapper {
    display: flex;
    align-items: center;
    height: 100%;
    min-height: 40px;
  }

  .checkbox-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.875rem;
    color: var(--text, #F5F3EF);
  }

  .courses-selector-box {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    background: rgba(0, 0, 0, 0.15);
    padding: 0.875rem;
    border-radius: 8px;
    border: 1px solid var(--border, #3D3A36);
  }

  .courses-checkboxes-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    max-height: 180px;
    overflow-y: auto;
  }

  @media (max-width: 500px) {
    .courses-checkboxes-grid {
      grid-template-columns: 1fr;
    }
  }

  .course-checkbox-card {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: var(--card, #1F1D1B);
    border: 1px solid var(--border, #3D3A36);
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: border-color 0.15s ease;
  }

  .course-checkbox-card.checked {
    border-color: var(--brand, #3b82f6);
    background: rgba(59, 130, 246, 0.08);
  }

  .course-meta {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
  }

  .course-meta .price {
    font-size: 0.7rem;
    color: var(--muted, #B0AAA0);
  }

  .modal-footer-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border, #3D3A36);
  }

  .btn-cancel {
    background: transparent;
    border: 1px solid var(--border, #3D3A36);
    color: var(--text, #F5F3EF);
    padding: 0.625rem 1.25rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
  }

  .btn-save {
    background: var(--brand, #3b82f6);
    color: #ffffff;
    border: none;
    padding: 0.625rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-save:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .spinner-small {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }
</style>

