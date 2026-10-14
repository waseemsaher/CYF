<script lang="ts">
  import { onMount } from 'svelte';
  import {
    getAdminCoupons,
    getAdminCoupon,
    createAdminCoupon,
    updateAdminCoupon,
    deleteAdminCoupon,
    toggleAdminCouponActive,
    type AdminCoupon,
    type CouponPayload
  } from '$lib/api/coupons';
  import { getAdminCourses, type AdminCourse } from '$lib/api/admin';
  import { getAuthToken } from '$lib/api/client';
  import { getCurrentUser } from '$lib/api/auth';
  import { currentLocale, formatPrice } from '$lib/i18n';
  import AuthGuardCard from '$lib/components/AuthGuardCard.svelte';

  let coupons: AdminCoupon[] = $state([]);
  let coursesList: AdminCourse[] = $state([]);
  let loading = $state(true);
  let errorMsg = $state('');
  let isUnauthenticated = $state(false);
  let currentRole = $state('');
  let currentPage = $state(1);
  let totalPages = $state(1);
  let totalCoupons = $state(0);

  // Modal State
  let isModalOpen = $state(false);
  let isEditing = $state(false);
  let editingId = $state<number | null>(null);
  let modalLoading = $state(false);
  let modalError = $state('');

  // Form Fields
  let formCode = $state('');
  let formType = $state<'percent' | 'fixed'>('percent');
  let formValue = $state<number>(10);
  let formScope = $state<'all' | 'courses'>('all');
  let formSelectedCourseIds = $state<number[]>([]);
  let formMaxRedemptions = $state<number | null>(null);
  let formMaxPerUser = $state<number>(1);
  let formStartsAt = $state('');
  let formEndsAt = $state('');
  let formIsActive = $state(true);

  // Usage History Drawer/Modal
  let isUsageOpen = $state(false);
  let usageLoading = $state(false);
  let usageCoupon = $state<AdminCoupon | null>(null);

  // Action Loading
  let actionLoadingId = $state<number | null>(null);

  const formatDate = (iso: string | null | undefined) => {
    if (!iso) return 'دائم';
    return new Date(iso).toLocaleDateString('ar-EG', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const formatDateTime = (iso: string | null | undefined) => {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString('ar-EG', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  async function checkAuthAndLoad() {
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

      await Promise.all([
        loadCoupons(1),
        loadCourses()
      ]);
    } catch {
      isUnauthenticated = true;
    } finally {
      loading = false;
    }
  }

  async function loadCourses() {
    try {
      const res = await getAdminCourses(fetch, 1);
      coursesList = res.data;
    } catch (e) {
      console.error('Failed to load courses for coupon assignment:', e);
    }
  }

  async function loadCoupons(page = 1) {
    loading = true;
    errorMsg = '';
    try {
      const res = await getAdminCoupons(fetch, page);
      coupons = res.data;
      currentPage = res.meta.current_page;
      totalPages = res.meta.last_page;
      totalCoupons = res.meta.total;
    } catch (e: any) {
      errorMsg = e?.message || 'حدث خطأ أثناء تحميل الكوبونات.';
    } finally {
      loading = false;
    }
  }

  function openCreateModal() {
    isEditing = false;
    editingId = null;
    formCode = '';
    formType = 'percent';
    formValue = 10;
    formScope = 'all';
    formSelectedCourseIds = [];
    formMaxRedemptions = null;
    formMaxPerUser = 1;
    formStartsAt = '';
    formEndsAt = '';
    formIsActive = true;
    modalError = '';
    isModalOpen = true;
  }

  function openEditModal(c: AdminCoupon) {
    isEditing = true;
    editingId = c.id;
    formCode = c.code;
    formType = c.type;
    formValue = c.type === 'fixed' ? c.value / 100 : c.value;
    formScope = c.scope;
    formSelectedCourseIds = c.courses?.map(course => course.id) || [];
    formMaxRedemptions = c.max_redemptions;
    formMaxPerUser = c.max_redemptions_per_user || 1;
    formStartsAt = c.starts_at ? c.starts_at.slice(0, 10) : '';
    formEndsAt = c.ends_at ? c.ends_at.slice(0, 10) : '';
    formIsActive = c.is_active;
    modalError = '';
    isModalOpen = true;
  }

  function toggleCourseSelection(id: number) {
    if (formSelectedCourseIds.includes(id)) {
      formSelectedCourseIds = formSelectedCourseIds.filter(cid => cid !== id);
    } else {
      formSelectedCourseIds = [...formSelectedCourseIds, id];
    }
  }

  async function handleSaveCoupon() {
    const code = formCode.trim().toUpperCase();
    if (!code) {
      modalError = 'يرجى إدخال رمز الكوبون.';
      return;
    }

    if (formValue <= 0) {
      modalError = 'قيمة الخصم يجب أن تكون أكبر من صفر.';
      return;
    }

    if (formType === 'percent' && formValue > 100) {
      modalError = 'نسبة الخصم المئوية لا يمكن أن تتجاوز 100%.';
      return;
    }

    if (formScope === 'courses' && formSelectedCourseIds.length === 0) {
      modalError = 'يرجى اختيار مادة واحدة على الأقل للكوبون المقيد.';
      return;
    }

    modalLoading = true;
    modalError = '';

    const payload: CouponPayload = {
      code,
      type: formType,
      value: formType === 'fixed' ? Math.round(formValue * 100) : Math.round(formValue),
      scope: formScope,
      course_ids: formScope === 'courses' ? formSelectedCourseIds : [],
      max_redemptions: formMaxRedemptions && formMaxRedemptions > 0 ? formMaxRedemptions : null,
      max_redemptions_per_user: formMaxPerUser > 0 ? formMaxPerUser : 1,
      starts_at: formStartsAt ? `${formStartsAt} 00:00:00` : null,
      ends_at: formEndsAt ? `${formEndsAt} 23:59:59` : null,
      is_active: formIsActive
    };

    try {
      if (isEditing && editingId !== null) {
        await updateAdminCoupon(fetch, editingId, payload);
      } else {
        await createAdminCoupon(fetch, payload);
      }

      isModalOpen = false;
      await loadCoupons(currentPage);
    } catch (e: any) {
      modalError = e?.message || 'حدث خطأ أثناء حفظ الكوبون.';
    } finally {
      modalLoading = false;
    }
  }

  async function handleToggleActive(c: AdminCoupon) {
    actionLoadingId = c.id;
    try {
      const res = await toggleAdminCouponActive(fetch, c.id);
      coupons = coupons.map(item => item.id === c.id ? { ...item, is_active: res.data.is_active } : item);
    } catch (e: any) {
      errorMsg = e?.message || 'تعذر تغيير حالة الكوبون.';
    } finally {
      actionLoadingId = null;
    }
  }

  async function handleDelete(c: AdminCoupon) {
    if (!confirm(`هل أنت متأكد من حذف الكوبون (${c.code}) نهائيًا؟`)) {
      return;
    }

    actionLoadingId = c.id;
    try {
      await deleteAdminCoupon(fetch, c.id);
      coupons = coupons.filter(item => item.id !== c.id);
      totalCoupons = Math.max(0, totalCoupons - 1);
    } catch (e: any) {
      errorMsg = e?.message || 'تعذر حذف الكوبون.';
    } finally {
      actionLoadingId = null;
    }
  }

  async function openUsageDrawer(c: AdminCoupon) {
    usageLoading = true;
    isUsageOpen = true;
    usageCoupon = c;
    try {
      const res = await getAdminCoupon(fetch, c.id);
      usageCoupon = res.data;
    } catch (e: any) {
      errorMsg = e?.message || 'تعذر تحميل سجل الاستخدام.';
    } finally {
      usageLoading = false;
    }
  }

  onMount(() => {
    checkAuthAndLoad();
  });
</script>

<svelte:head>
  <title>إدارة كوبونات الخصم | لوحة تحكم المسؤول</title>
</svelte:head>

{#if isUnauthenticated || (currentRole && currentRole !== 'admin' && currentRole !== 'superadmin')}
  <AuthGuardCard
    requiredRole="admin"
    {isUnauthenticated}
    {currentRole}
    onRetry={checkAuthAndLoad}
  />
{:else}
  <main class="admin-shell" dir="rtl">
    <header class="admin-header">
      <div>
        <nav class="breadcrumbs" aria-label="مسار التنقل">
          <a href="/">الرئيسية</a>
          <span class="sep">/</span>
          <a href="/admin">لوحة تحكم المسؤول</a>
          <span class="sep">/</span>
          <span class="current">كوبونات الخصم</span>
        </nav>
        <h1 class="page-title">كوبونات الخصم {#if !loading && !errorMsg}({totalCoupons}){/if}</h1>
      </div>
      <button type="button" class="btn-primary" onclick={openCreateModal}>
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>إنشاء كوبون جديد</span>
      </button>
    </header>

    {#if loading}
      <div class="loading-state">
        <div class="spinner" aria-hidden="true"></div>
        <p>جاري تحميل الكوبونات...</p>
      </div>
    {:else if errorMsg}
      <div class="empty-state error-state">
        <div class="empty-icon" style="color: #dc2626;">
          <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.75">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>
        <h2>تعذر تحميل الكوبونات من الخادم</h2>
        <p dir="ltr" style="font-family: monospace; font-size: 0.95rem; color: #991b1b; background: #fef2f2; padding: 0.5rem 1rem; border-radius: 0.35rem; display: inline-block;">
          {errorMsg}
        </p>
        <div style="margin-top: 1.25rem;">
          <button type="button" class="btn-primary" onclick={() => loadCoupons(currentPage)}>
            إعادة المحاولة
          </button>
        </div>
      </div>
    {:else if coupons.length === 0}
      <div class="empty-state">
        <div class="empty-icon">
          <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.75">
            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
            <path d="M12 5v14"></path>
            <circle cx="8" cy="12" r="1.5"></circle>
            <circle cx="16" cy="12" r="1.5"></circle>
          </svg>
        </div>
        <h2>لا توجد كوبونات خصم حالياً</h2>
        <p>قم بإنشاء أول كود خصم لتوزيعه على الطلاب للمواد المختلفة.</p>
        <button type="button" class="btn-primary" onclick={openCreateModal}>إنشاء كوبون الآن</button>
      </div>
    {:else}
      <div class="coupons-table-wrapper">
        <table class="coupons-table">
          <thead>
            <tr>
              <th>الكود</th>
              <th>قيمة الخصم</th>
              <th>نطاق السريان</th>
              <th>الاستخدام</th>
              <th>لكل طالب</th>
              <th>فترة الصلاحية</th>
              <th>الحالة</th>
              <th>الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            {#each coupons as c (c.id)}
              <tr class:inactive-row={!c.is_active}>
                <td class="code-cell">
                  <span class="code-badge" dir="ltr">{c.code}</span>
                </td>
                <td class="value-cell">
                  {#if c.type === 'percent'}
                    <span class="discount-pill percent">{c.value}%</span>
                  {:else}
                    <span class="discount-pill fixed">{formatPrice(c.value, $currentLocale)}</span>
                  {/if}
                </td>
                <td class="scope-cell">
                  {#if c.scope === 'all'}
                    <span class="badge scope-all">جميع المواد</span>
                  {:else}
                    <span class="badge scope-courses" title={c.courses?.map(crs => crs.title.ar).join('، ')}>
                      {c.courses && c.courses.length > 0 ? `${c.courses.length} مواد محددة` : 'مواد محددة'}
                    </span>
                  {/if}
                </td>
                <td class="usage-cell">
                  <button type="button" class="usage-link" onclick={() => openUsageDrawer(c)}>
                    <strong>{c.times_redeemed}</strong>
                    <span class="text-muted">/ {c.max_redemptions !== null ? c.max_redemptions : '∞'}</span>
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M9 18l6-6-6-6"></path>
                    </svg>
                  </button>
                </td>
                <td class="per-user-cell">
                  <span>{c.max_redemptions_per_user || 1} مرة</span>
                </td>
                <td class="date-cell">
                  {#if !c.starts_at && !c.ends_at}
                    <span class="date-tag permanent">مستمر دائماً</span>
                  {:else}
                    <span class="date-tag">
                      {formatDate(c.starts_at)} — {formatDate(c.ends_at)}
                    </span>
                  {/if}
                </td>
                <td class="status-cell">
                  <button
                    type="button"
                    class="toggle-status-btn"
                    class:active={c.is_active}
                    disabled={actionLoadingId === c.id}
                    onclick={() => handleToggleActive(c)}
                  >
                    <span class="status-dot"></span>
                    <span>{c.is_active ? 'مفعّل' : 'معطّل'}</span>
                  </button>
                </td>
                <td class="actions-cell">
                  <div class="actions-group">
                    <button
                      type="button"
                      class="btn-icon"
                      title="سجل الاستخدام"
                      onclick={() => openUsageDrawer(c)}
                    >
                      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                      </svg>
                    </button>
                    <button
                      type="button"
                      class="btn-icon"
                      title="تعديل الكوبون"
                      onclick={() => openEditModal(c)}
                    >
                      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                      </svg>
                    </button>
                    <button
                      type="button"
                      class="btn-icon danger"
                      title="حذف الكوبون"
                      disabled={actionLoadingId === c.id}
                      onclick={() => handleDelete(c)}
                    >
                      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>

      {#if totalPages > 1}
        <div class="pagination">
          <button
            type="button"
            disabled={currentPage <= 1}
            onclick={() => loadCoupons(currentPage - 1)}
          >
            السابق
          </button>
          <span>صفحة {currentPage} من {totalPages}</span>
          <button
            type="button"
            disabled={currentPage >= totalPages}
            onclick={() => loadCoupons(currentPage + 1)}
          >
            التالي
          </button>
        </div>
      {/if}
    {/if}

    <!-- Create / Edit Modal -->
    {#if isModalOpen}
      <div class="modal-backdrop" onclick={() => (isModalOpen = false)} role="presentation">
        <div class="modal-card" onclick={(e) => e.stopPropagation()} role="dialog" aria-modal="true">
          <div class="modal-header">
            <h2>{isEditing ? 'تعديل كوبون الخصم' : 'إنشاء كوبون خصم جديد'}</h2>
            <button type="button" class="close-btn" onclick={() => (isModalOpen = false)} aria-label="إغلاق">✕</button>
          </div>

          {#if modalError}
            <div class="notice error">{modalError}</div>
          {/if}

          <form onsubmit={(e) => { e.preventDefault(); handleSaveCoupon(); }} class="modal-form">
            <div class="form-row">
              <label class="field flex-1">
                <span>رمز الكوبون (Code) <span class="req">*</span></span>
                <input
                  type="text"
                  bind:value={formCode}
                  placeholder="مثال: SAVE20, RAMADAN"
                  dir="ltr"
                  required
                />
              </label>

              <label class="field flex-1">
                <span>نوع الخصم <span class="req">*</span></span>
                <select bind:value={formType}>
                  <option value="percent">نسبة مئوية (%)</option>
                  <option value="fixed">مبلغ ثابت (ج.م)</option>
                </select>
              </label>
            </div>

            <div class="form-row">
              <label class="field flex-1">
                <span>{formType === 'percent' ? 'النسبة المئوية (%)' : 'المبلغ بالجنيه (EGP)'} <span class="req">*</span></span>
                <input
                  type="number"
                  min="1"
                  max={formType === 'percent' ? 100 : undefined}
                  bind:value={formValue}
                  required
                />
              </label>

              <label class="field flex-1">
                <span>نطاق السريان <span class="req">*</span></span>
                <select bind:value={formScope}>
                  <option value="all">جميع المواد (شامل)</option>
                  <option value="courses">مواد محددة فقط</option>
                </select>
              </label>
            </div>

            {#if formScope === 'courses'}
              <div class="field course-picker-field">
                <span>اختر المواد المؤهلة لهذا الكوبون: <span class="req">*</span></span>
                <div class="courses-checklist">
                  {#each coursesList as crs}
                    <label class="course-check-item" class:checked={formSelectedCourseIds.includes(crs.id)}>
                      <input
                        type="checkbox"
                        checked={formSelectedCourseIds.includes(crs.id)}
                        onchange={() => toggleCourseSelection(crs.id)}
                      />
                      <span>{crs.title.ar}</span>
                      <small class="course-price">{crs.price_cents / 100} ج.م</small>
                    </label>
                  {/each}
                </div>
              </div>
            {/if}

            <div class="form-row">
              <label class="field flex-1">
                <span>أقصى عدد استخدامات إجمالي</span>
                <input
                  type="number"
                  min="1"
                  bind:value={formMaxRedemptions}
                  placeholder="اتركه فارغاً لعدد غير محدود"
                />
              </label>

              <label class="field flex-1">
                <span>أقصى استخدام لكل طالب</span>
                <input
                  type="number"
                  min="1"
                  bind:value={formMaxPerUser}
                  placeholder="الافتراضي: 1"
                  required
                />
              </label>
            </div>

            <div class="form-row">
              <label class="field flex-1">
                <span>تاريخ البداية (اختياري)</span>
                <input type="date" bind:value={formStartsAt} />
              </label>

              <label class="field flex-1">
                <span>تاريخ الانتهاء (اختياري)</span>
                <input type="date" bind:value={formEndsAt} />
              </label>
            </div>

            <label class="toggle-field">
              <input type="checkbox" bind:checked={formIsActive} />
              <span>تفعيل الكوبون فوراً للسماح باستخدامه</span>
            </label>

            <div class="modal-footer">
              <button type="button" class="btn-secondary" onclick={() => (isModalOpen = false)}>إلغاء</button>
              <button type="submit" class="btn-primary" disabled={modalLoading}>
                {modalLoading ? 'جاري الحفظ...' : isEditing ? 'حفظ التعديلات' : 'إنشاء الكوبون'}
              </button>
            </div>
          </form>
        </div>
      </div>
    {/if}

    <!-- Usage History Drawer / Modal -->
    {#if isUsageOpen && usageCoupon}
      <div class="modal-backdrop" onclick={() => (isUsageOpen = false)} role="presentation">
        <div class="modal-card drawer-card" onclick={(e) => e.stopPropagation()} role="dialog" aria-modal="true">
          <div class="modal-header">
            <div>
              <h2>سجل استخدام الكوبون: <span dir="ltr" class="highlight-code">{usageCoupon.code}</span></h2>
              <p class="modal-sub">
                تم الاستخدام <strong>{usageCoupon.times_redeemed}</strong> مرات من إجمالي {usageCoupon.max_redemptions || 'غير محدود'}.
              </p>
            </div>
            <button type="button" class="close-btn" onclick={() => (isUsageOpen = false)} aria-label="إغلاق">✕</button>
          </div>

          {#if usageLoading}
            <div class="loading-state">
              <div class="spinner" aria-hidden="true"></div>
              <p>جاري تحميل سجل الاستخدام...</p>
            </div>
          {:else if !usageCoupon.redemptions || usageCoupon.redemptions.length === 0}
            <div class="empty-state">
              <p>لم يقم أي طالب باستخدام هذا الكوبون حتى الآن.</p>
            </div>
          {:else}
            <div class="usage-table-wrapper">
              <table class="usage-table">
                <thead>
                  <tr>
                    <th>الطالب</th>
                    <th>البريد الإلكتروني</th>
                    <th>حالة الدفعة</th>
                    <th>المبلغ بعد الخصم</th>
                    <th>تاريخ الاستخدام</th>
                  </tr>
                </thead>
                <tbody>
                  {#each usageCoupon.redemptions as r (r.id)}
                    <tr>
                      <td>
                        <strong>{r.user?.name ?? 'طالب'}</strong>
                        {#if r.user?.branch}
                          <small class="branch-tag">{r.user.branch}</small>
                        {/if}
                      </td>
                      <td>{r.user?.email ?? '-'}</td>
                      <td>
                        {#if r.payment}
                          <span class={`status-badge badge-${r.payment.status}`}>
                            {r.payment.status === 'approved' ? 'مقبول' : r.payment.status === 'pending' ? 'قيد المراجعة' : r.payment.status}
                          </span>
                        {:else}
                          <span class="status-badge badge-free">تسجيل مجاني</span>
                        {/if}
                      </td>
                      <td>
                        {r.payment ? formatPrice(r.payment.amount_due_cents, $currentLocale) : 'مجاناً'}
                      </td>
                      <td>{formatDateTime(r.created_at)}</td>
                    </tr>
                  {/each}
                </tbody>
              </table>
            </div>
          {/if}

          <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick={() => (isUsageOpen = false)}>إغلاق</button>
          </div>
        </div>
      </div>
    {/if}
  </main>
{/if}

<style>
  .admin-shell { max-width: 1200px; margin: 0 auto; padding: 2rem 1.25rem 4rem; color: var(--storm); }
  .admin-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
  .breadcrumbs { font-size: 0.85rem; color: var(--muted); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.35rem; }
  .breadcrumbs a { color: var(--deep-cyan); text-decoration: none; font-weight: 600; }
  .breadcrumbs a:hover { text-decoration: underline; }
  .breadcrumbs .sep { color: #aaa; }
  .breadcrumbs .current { color: var(--storm); font-weight: 700; }
  .page-title { font-size: 1.85rem; margin: 0; font-weight: 900; color: var(--storm); }
  
  .btn-primary { background: var(--brand-accent, #c2453e); color: #fff; border: 0; border-radius: 0.5rem; padding: 0.65rem 1.25rem; font-weight: 800; display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; text-decoration: none; transition: opacity 150ms; font-family: inherit; }
  .btn-primary:hover:not(:disabled) { opacity: 0.9; }
  .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
  .btn-secondary { background: var(--paper, #f7f6f3); border: 1.5px solid var(--line, #e2ded9); border-radius: 0.5rem; padding: 0.65rem 1.25rem; font-weight: 700; color: var(--storm); cursor: pointer; }
  .btn-secondary:hover { background: #eee; }

  .notice.error { background: #fef2f2; border: 1.5px solid #f87171; color: #991b1b; padding: 0.85rem 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-weight: 600; }

  .loading-state { text-align: center; padding: 4rem 1rem; color: var(--muted); }
  .spinner { width: 2.5rem; height: 2.5rem; border: 3px solid rgba(0,0,0,0.1); border-top-color: var(--deep-cyan); border-radius: 50%; margin: 0 auto 1rem; animation: spin 0.8s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }

  .empty-state { background: var(--card, #fff); border: 2px dashed var(--line, #e2ded9); border-radius: 0.75rem; padding: 4rem 2rem; text-align: center; margin-top: 1rem; }
  .empty-icon { color: var(--muted); margin-bottom: 1rem; }
  .empty-state h2 { font-size: 1.35rem; margin: 0 0 0.5rem; }
  .empty-state p { color: var(--muted); margin: 0 0 1.5rem; }

  .coupons-table-wrapper { background: var(--card, #fff); border: 1.5px solid var(--line, #e2ded9); border-radius: 0.75rem; overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
  .coupons-table { width: 100%; border-collapse: collapse; text-align: right; font-size: 0.92rem; }
  .coupons-table th { background: #faf9f7; padding: 0.85rem 1rem; border-bottom: 1.5px solid var(--line, #e2ded9); font-weight: 800; color: var(--storm); white-space: nowrap; }
  .coupons-table td { padding: 1rem; border-bottom: 1px solid var(--line, #e2ded9); vertical-align: middle; }
  .coupons-table tr:last-child td { border-bottom: none; }
  .inactive-row { opacity: 0.6; background: #fafafa; }

  .code-badge { font-family: monospace; font-size: 1.05rem; font-weight: 800; background: #f0f4f8; color: #1e3a8a; padding: 0.25rem 0.65rem; border-radius: 0.35rem; border: 1px solid #dbeafe; letter-spacing: 0.05em; }
  .discount-pill { display: inline-block; font-weight: 800; font-size: 0.88rem; padding: 0.25rem 0.65rem; border-radius: 0.35rem; }
  .discount-pill.percent { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
  .discount-pill.fixed { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }

  .badge { font-size: 0.78rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 0.35rem; }
  .badge.scope-all { background: #f3f4f6; color: #374151; }
  .badge.scope-courses { background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; }

  .usage-link { background: none; border: 0; color: var(--deep-cyan); font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; font-family: inherit; font-size: 0.9rem; padding: 0.2rem 0.4rem; border-radius: 0.3rem; }
  .usage-link:hover { background: #f0fdfa; text-decoration: underline; }
  .text-muted { color: var(--muted); }

  .date-tag { font-size: 0.8rem; color: var(--storm); }
  .date-tag.permanent { color: #059669; font-weight: 700; }

  .toggle-status-btn { background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 1rem; padding: 0.25rem 0.65rem; font-size: 0.78rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; color: #4b5563; font-family: inherit; transition: all 150ms; }
  .toggle-status-btn.active { background: #ecfdf5; border-color: #10b981; color: #047857; }
  .status-dot { width: 0.5rem; height: 0.5rem; border-radius: 50%; background: #9ca3af; }
  .toggle-status-btn.active .status-dot { background: #10b981; }

  .actions-group { display: flex; gap: 0.35rem; justify-content: flex-end; }
  .btn-icon { background: transparent; border: 1px solid var(--line, #e2ded9); border-radius: 0.35rem; color: var(--storm); width: 2rem; height: 2rem; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: background 150ms; }
  .btn-icon:hover { background: #f3f4f6; }
  .btn-icon.danger { color: #dc2626; }
  .btn-icon.danger:hover { background: #fee2e2; border-color: #fca5a5; }

  .pagination { display: flex; justify-content: center; align-items: center; gap: 1rem; margin-top: 1.5rem; }
  .pagination button { background: #fff; border: 1.5px solid var(--line); border-radius: 0.4rem; padding: 0.4rem 0.85rem; font-weight: 700; cursor: pointer; }
  .pagination button:disabled { opacity: 0.5; cursor: not-allowed; }

  /* Modals */
  .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px); z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
  .modal-card { background: #fff; border-radius: 0.75rem; width: 100%; max-width: 620px; max-height: 90vh; overflow-y: auto; padding: 1.75rem; box-shadow: 0 10px 25px rgba(0,0,0,0.15); box-sizing: border-box; }
  .drawer-card { max-width: 780px; }
  .modal-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem; border-bottom: 1.5px solid var(--line); padding-bottom: 0.75rem; }
  .modal-header h2 { margin: 0; font-size: 1.35rem; font-weight: 800; }
  .modal-sub { margin: 0.35rem 0 0; color: var(--muted); font-size: 0.88rem; }
  .highlight-code { font-family: monospace; color: #1e3a8a; background: #e0e7ff; padding: 0.15rem 0.4rem; border-radius: 0.25rem; }
  .close-btn { background: none; border: 0; font-size: 1.25rem; cursor: pointer; color: var(--muted); }
  .close-btn:hover { color: #000; }

  .modal-form { display: grid; gap: 1rem; }
  .form-row { display: flex; gap: 1rem; flex-wrap: wrap; }
  .flex-1 { flex: 1; min-width: 220px; }
  .field { display: flex; flex-direction: column; gap: 0.35rem; }
  .field span { font-size: 0.85rem; font-weight: 700; }
  .req { color: #dc2626; }
  input[type="text"], input[type="number"], input[type="date"], select { border: 1.5px solid var(--line, #e2ded9); border-radius: 0.4rem; padding: 0.6rem 0.85rem; font-family: inherit; font-size: 0.95rem; }
  input:focus, select:focus { border-color: var(--deep-cyan); outline: none; }

  .course-picker-field { margin-top: 0.25rem; }
  .courses-checklist { max-height: 180px; overflow-y: auto; border: 1.5px solid var(--line); border-radius: 0.4rem; padding: 0.5rem; display: grid; gap: 0.35rem; background: #faf9f7; }
  .course-check-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.65rem; border-radius: 0.3rem; cursor: pointer; background: #fff; border: 1px solid #eee; }
  .course-check-item.checked { background: #f0fdf4; border-color: #86efac; font-weight: 700; }
  .course-price { margin-right: auto; color: var(--muted); font-size: 0.78rem; }

  .toggle-field { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem; font-weight: 700; margin: 0.5rem 0; }
  .toggle-field input { accent-color: var(--deep-cyan); width: 1.1rem; height: 1.1rem; }

  .modal-footer { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; border-top: 1.5px solid var(--line); padding-top: 1rem; }

  /* Usage Table */
  .usage-table-wrapper { max-height: 380px; overflow-y: auto; border: 1px solid var(--line); border-radius: 0.4rem; margin: 1rem 0; }
  .usage-table { width: 100%; border-collapse: collapse; text-align: right; font-size: 0.85rem; }
  .usage-table th { background: #faf9f7; padding: 0.65rem 0.85rem; border-bottom: 1px solid var(--line); position: sticky; top: 0; }
  .usage-table td { padding: 0.65rem 0.85rem; border-bottom: 1px solid #f0f0f0; }
  .branch-tag { display: block; font-size: 0.75rem; color: var(--muted); }
  .status-badge { display: inline-block; padding: 0.15rem 0.45rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 700; }
  .badge-approved { background: #d1fae5; color: #065f46; }
  .badge-pending { background: #fef3c7; color: #92400e; }
  .badge-free { background: #e0e7ff; color: #3730a3; }
</style>

