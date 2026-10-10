<script lang="ts">
  import { onMount } from 'svelte';
  import {
    getAdminTeachers,
    createAdminTeacher,
    assignTeacherToCourse,
    recordTeacherPayout,
    getAdminCourses,
    type AdminTeacher,
    type AdminCourse,
  } from '$lib/api/admin';
  import { currentLocale, formatPrice } from '$lib/i18n';

  let teachers = $state<AdminTeacher[]>([]);
  let courses = $state<AdminCourse[]>([]);
  let loading = $state(true);
  let errorMsg = $state('');

  // Create Teacher Modal
  let isCreateModalOpen = $state(false);
  let createName = $state('');
  let createEmail = $state('');
  let createPassword = $state('');
  let createPhone = $state('');
  let createLoading = $state(false);
  let createError = $state('');

  // Assign Course Modal
  let isAssignModalOpen = $state(false);
  let selectedTeacherForAssign = $state<AdminTeacher | null>(null);
  let assignCourseId = $state<number | null>(null);
  let assignSharePercent = $state<number>(70);
  let assignLoading = $state(false);
  let assignError = $state('');

  // Record Payout Modal
  let isPayoutModalOpen = $state(false);
  let selectedTeacherForPayout = $state<AdminTeacher | null>(null);
  let payoutAmountPounds = $state<number>(1000);
  let payoutNote = $state('');
  let payoutLoading = $state(false);
  let payoutError = $state('');

  export async function loadTeachers() {
    loading = true;
    errorMsg = '';
    try {
      const [teachersRes, coursesRes] = await Promise.all([
        getAdminTeachers(fetch),
        getAdminCourses(fetch, 1).catch(() => ({ data: [] })),
      ]);
      teachers = teachersRes.data || [];
      courses = coursesRes.data || [];
    } catch (err: any) {
      errorMsg = err?.message || 'تعذر تحميل بيانات المحاضرين.';
    } finally {
      loading = false;
    }
  }

  function generateRandomPassword() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%&*';
    let res = '';
    for (let i = 0; i < 12; i++) {
      res += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    createPassword = res;
  }

  function openCreateModal() {
    createName = '';
    createEmail = '';
    generateRandomPassword();
    createPhone = '';
    createError = '';
    isCreateModalOpen = true;
  }

  function closeCreateModal() {
    isCreateModalOpen = false;
    createError = '';
  }

  async function handleCreateTeacher(e: SubmitEvent) {
    e.preventDefault();
    createLoading = true;
    createError = '';
    try {
      await createAdminTeacher(fetch, {
        name: createName.trim(),
        email: createEmail.trim(),
        password: createPassword,
        phone: createPhone.trim() || null,
      });
      closeCreateModal();
      await loadTeachers();
    } catch (err: any) {
      createError = err?.message || 'تعذر إنشاء حساب المحاضر.';
    } finally {
      createLoading = false;
    }
  }

  function openAssignModal(teacher: AdminTeacher) {
    selectedTeacherForAssign = teacher;
    assignCourseId = courses.length > 0 ? courses[0].id : null;
    assignSharePercent = 70;
    assignError = '';
    isAssignModalOpen = true;
  }

  function closeAssignModal() {
    isAssignModalOpen = false;
    selectedTeacherForAssign = null;
    assignError = '';
  }

  async function handleAssignCourse(e: SubmitEvent) {
    e.preventDefault();
    if (!selectedTeacherForAssign || !assignCourseId) return;

    assignLoading = true;
    assignError = '';
    try {
      await assignTeacherToCourse(fetch, assignCourseId, {
        teacher_id: selectedTeacherForAssign.id,
        teacher_share_percent: Number(assignSharePercent),
      });
      closeAssignModal();
      await loadTeachers();
    } catch (err: any) {
      assignError = err?.message || 'تعذر تعيين المحاضر للمقرر.';
    } finally {
      assignLoading = false;
    }
  }

  function openPayoutModal(teacher: AdminTeacher) {
    selectedTeacherForPayout = teacher;
    payoutAmountPounds = Math.max(100, Math.round((teacher.earnings?.balance_cents || 0) / 100));
    payoutNote = '';
    payoutError = '';
    isPayoutModalOpen = true;
  }

  function closePayoutModal() {
    isPayoutModalOpen = false;
    selectedTeacherForPayout = null;
    payoutError = '';
  }

  async function handleRecordPayout(e: SubmitEvent) {
    e.preventDefault();
    if (!selectedTeacherForPayout) return;

    payoutLoading = true;
    payoutError = '';
    try {
      await recordTeacherPayout(fetch, selectedTeacherForPayout.id, {
        amount_cents: Math.round(payoutAmountPounds * 100),
        note: payoutNote.trim() || null,
      });
      closePayoutModal();
      await loadTeachers();
    } catch (err: any) {
      payoutError = err?.message || 'تعذر تسجيل الدفعة للمحاضر.';
    } finally {
      payoutLoading = false;
    }
  }

  onMount(() => {
    loadTeachers();
  });
</script>

<div class="teacher-management-root" dir="rtl">
  <div class="panel-top-bar">
    <div>
      <h2>إدارة أعضاء هيئة التدريس والمحاضرين ({teachers.length})</h2>
      <p>قائمة بالمحاضرين، المواد المكلفين بها، نسب الأرباح، وأرصدة المستحقات والتحويلات المالية.</p>
    </div>
    <div class="top-actions">
      <button type="button" class="btn-create-teacher" onclick={openCreateModal} data-testid="btn-add-teacher">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>إضافة محاضر جديد</span>
      </button>
      <button type="button" class="btn-refresh-teachers" onclick={loadTeachers} title="تحديث" aria-label="تحديث">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
        </svg>
      </button>
    </div>
  </div>

  {#if loading}
    <div class="loading-state">
      <div class="spinner"></div>
      <p>جاري تحميل قائمة المحاضرين...</p>
    </div>
  {:else if errorMsg}
    <div class="error-state">
      <p>{errorMsg}</p>
      <button type="button" class="btn-retry" onclick={loadTeachers}>إعادة المحاولة</button>
    </div>
  {:else if teachers.length === 0}
    <div class="empty-state">
      <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
        <circle cx="9" cy="7" r="4"></circle>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
      </svg>
      <h3>لا يوجد محاضرون مضافون حتى الآن</h3>
      <p>أضف حساب أول محاضر لتعيينه للمقررات ومتابعة مستحقاته المالية.</p>
      <button type="button" class="btn-create-teacher" onclick={openCreateModal}>
        إضافة أول محاضر
      </button>
    </div>
  {:else}
    <div class="table-responsive">
      <table class="teachers-table" data-testid="teachers-table">
        <thead>
          <tr>
            <th>المحاضر</th>
            <th>البريد الإلكتروني / الهاتف</th>
            <th>المقررات ونسبة الأرباح</th>
            <th>إجمالي الأرباح</th>
            <th>المدفوع</th>
            <th>الرصيد المتاح</th>
            <th>الإجراءات</th>
          </tr>
        </thead>
        <tbody>
          {#each teachers as teacher (teacher.id)}
            <tr data-testid={`teacher-row-${teacher.id}`}>
              <td>
                <div class="teacher-profile-cell">
                  <span class="avatar-circle">{teacher.name.charAt(0)}</span>
                  <div>
                    <strong class="teacher-name">{teacher.name}</strong>
                    <span class="teacher-id">#{teacher.id}</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="contact-cell">
                  <span class="teacher-email" dir="ltr">{teacher.email}</span>
                  {#if teacher.phone}
                    <small class="teacher-phone" dir="ltr">{teacher.phone}</small>
                  {/if}
                </div>
              </td>
              <td>
                {#if teacher.courses && teacher.courses.length > 0}
                  <div class="courses-tags-list">
                    {#each teacher.courses as c}
                      <span class="course-badge">
                        <span class="course-badge-title">{c.title?.ar || c.slug}</span>
                        <span class="course-badge-share">{c.teacher_share_percent ?? 70}%</span>
                      </span>
                    {/each}
                  </div>
                {:else}
                  <span class="no-courses-text">لا توجد مقررات معينة</span>
                {/if}
              </td>
              <td>
                <strong class="earnings-val earned">
                  {formatPrice(teacher.earnings?.earned_cents || 0, $currentLocale)}
                </strong>
              </td>
              <td>
                <span class="earnings-val paid">
                  {formatPrice(teacher.earnings?.paid_out_cents || 0, $currentLocale)}
                </span>
              </td>
              <td>
                <strong class="earnings-val balance" class:has-balance={(teacher.earnings?.balance_cents || 0) > 0}>
                  {formatPrice(teacher.earnings?.balance_cents || 0, $currentLocale)}
                </strong>
              </td>
              <td>
                <div class="actions-group">
                  <button
                    type="button"
                    class="btn-action btn-assign"
                    onclick={() => openAssignModal(teacher)}
                    title="تعيين لمقرر دراسي"
                    data-testid={`btn-assign-${teacher.id}`}
                  >
                    تعيين لمقرر
                  </button>
                  <button
                    type="button"
                    class="btn-action btn-payout"
                    onclick={() => openPayoutModal(teacher)}
                    title="تسجيل دفعة نقدية"
                    data-testid={`btn-payout-${teacher.id}`}
                  >
                    تسجيل دفعة
                  </button>
                </div>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  {/if}
</div>

<!-- Modal: Create Teacher -->
{#if isCreateModalOpen}
  <div class="modal-backdrop" onclick={closeCreateModal} role="presentation">
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <div
      class="modal-card"
      onclick={(e) => e.stopPropagation()}
      role="dialog"
      aria-modal="true"
      tabindex="-1"
      dir="rtl"
    >
      <div class="modal-header">
        <h3>إضافة محاضر جديد</h3>
        <button type="button" class="btn-close" onclick={closeCreateModal} aria-label="إغلاق">&times;</button>
      </div>

      {#if createError}
        <div class="modal-error-banner" role="alert">
          <p>{createError}</p>
        </div>
      {/if}

      <form class="modal-form" onsubmit={handleCreateTeacher}>
        <div class="form-group">
          <label for="create-t-name">اسم المحاضر *</label>
          <input
            id="create-t-name"
            type="text"
            bind:value={createName}
            required
            placeholder="مثال: د. حسام عادل"
            data-testid="input-teacher-name"
          />
        </div>

        <div class="form-group">
          <label for="create-t-email">البريد الإلكتروني *</label>
          <input
            id="create-t-email"
            type="email"
            dir="ltr"
            bind:value={createEmail}
            required
            placeholder="teacher@example.com"
            data-testid="input-teacher-email"
          />
        </div>

        <div class="form-group">
          <div class="label-with-action">
            <label for="create-t-pass">كلمة المرور الابتدائية *</label>
            <button type="button" class="btn-text-action" onclick={generateRandomPassword}>
              توليد كلمة مرور عشوائية
            </button>
          </div>
          <input
            id="create-t-pass"
            type="text"
            dir="ltr"
            bind:value={createPassword}
            required
            minlength="8"
            placeholder="8 أحرف على الأقل"
            data-testid="input-teacher-password"
          />
          <small class="hint-text">سيُطلب من المحاضر تغيير كلمة المرور عند تسجيل الدخول الأول.</small>
        </div>

        <div class="form-group">
          <label for="create-t-phone">رقم الهاتف (اختياري)</label>
          <input
            id="create-t-phone"
            type="tel"
            dir="ltr"
            bind:value={createPhone}
            placeholder="01012345678"
            data-testid="input-teacher-phone"
          />
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick={closeCreateModal}>إلغاء</button>
          <button type="submit" class="btn-submit" disabled={createLoading} data-testid="btn-submit-teacher">
            {createLoading ? 'جاري الحفظ...' : 'إنشاء حساب المحاضر'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<!-- Modal: Assign Course -->
{#if isAssignModalOpen && selectedTeacherForAssign}
  <div class="modal-backdrop" onclick={closeAssignModal} role="presentation">
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <div
      class="modal-card"
      onclick={(e) => e.stopPropagation()}
      role="dialog"
      aria-modal="true"
      tabindex="-1"
      dir="rtl"
    >
      <div class="modal-header">
        <h3>تعيين مقرر للمحاضر: {selectedTeacherForAssign.name}</h3>
        <button type="button" class="btn-close" onclick={closeAssignModal} aria-label="إغلاق">&times;</button>
      </div>

      {#if assignError}
        <div class="modal-error-banner" role="alert">
          <p>{assignError}</p>
        </div>
      {/if}

      <form class="modal-form" onsubmit={handleAssignCourse}>
        <div class="form-group">
          <label for="assign-course-select">المقرر الدراسي *</label>
          <select id="assign-course-select" bind:value={assignCourseId} required data-testid="select-assign-course">
            {#each courses as course}
              <option value={course.id}>
                {course.title?.ar || course.slug} ({formatPrice(course.price_cents, $currentLocale)})
              </option>
            {/each}
          </select>
        </div>

        <div class="form-group">
          <label for="assign-share-percent">نسبة أرباح المحاضر من مبيعات المقرر (%) *</label>
          <input
            id="assign-share-percent"
            type="number"
            min="0"
            max="100"
            bind:value={assignSharePercent}
            required
            data-testid="input-assign-share"
          />
          <small class="hint-text">النسبة المئوية من صافي ثمن المقرر المودع لرصيد المحاضر.</small>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick={closeAssignModal}>إلغاء</button>
          <button type="submit" class="btn-submit" disabled={assignLoading} data-testid="btn-submit-assign">
            {assignLoading ? 'جاري الحفظ...' : 'تأكيد التعيين'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<!-- Modal: Record Payout -->
{#if isPayoutModalOpen && selectedTeacherForPayout}
  <div class="modal-backdrop" onclick={closePayoutModal} role="presentation">
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <div
      class="modal-card"
      onclick={(e) => e.stopPropagation()}
      role="dialog"
      aria-modal="true"
      tabindex="-1"
      dir="rtl"
    >
      <div class="modal-header">
        <h3>تسجيل دفعة نقدية للمحاضر: {selectedTeacherForPayout.name}</h3>
        <button type="button" class="btn-close" onclick={closePayoutModal} aria-label="إغلاق">&times;</button>
      </div>

      {#if payoutError}
        <div class="modal-error-banner" role="alert">
          <p>{payoutError}</p>
        </div>
      {/if}

      <div class="payout-balance-summary">
        <span>الرصيد المتبقي الحالي:</span>
        <strong>{formatPrice(selectedTeacherForPayout.earnings?.balance_cents || 0, $currentLocale)}</strong>
      </div>

      <form class="modal-form" onsubmit={handleRecordPayout}>
        <div class="form-group">
          <label for="payout-amount">مبلغ الدفعة (بالجنيه المصري) *</label>
          <input
            id="payout-amount"
            type="number"
            min="1"
            step="10"
            bind:value={payoutAmountPounds}
            required
            data-testid="input-payout-amount"
          />
        </div>

        <div class="form-group">
          <label for="payout-note">ملاحظة أو رقم التحويل (اختياري)</label>
          <textarea
            id="payout-note"
            rows="2"
            bind:value={payoutNote}
            placeholder="مثال: تحويل إنستاباي، رقم إيصال، أو تحويل بنكي..."
            data-testid="input-payout-note"
          ></textarea>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick={closePayoutModal}>إلغاء</button>
          <button type="submit" class="btn-submit" disabled={payoutLoading} data-testid="btn-submit-payout">
            {payoutLoading ? 'جاري التسجيل...' : 'تأكيد تسجيل الدفعة'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<style>
  .teacher-management-root {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .panel-top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  }

  .panel-top-bar h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 0.25rem 0;
  }

  .panel-top-bar p {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.65);
    margin: 0;
  }

  .top-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .btn-create-teacher {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #00f0ff;
    color: #0f282f;
    font-weight: 700;
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .btn-create-teacher:hover {
    background: #4ef4ff;
    transform: translateY(-1px);
  }

  .btn-refresh-teachers {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    color: #fff;
    cursor: pointer;
    transition: background 0.2s;
  }

  .btn-refresh-teachers:hover {
    background: rgba(255, 255, 255, 0.12);
  }

  .loading-state,
  .empty-state,
  .error-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1.5rem;
    text-align: center;
    background: rgba(255, 255, 255, 0.03);
    border: 1px dashed rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    gap: 1rem;
  }

  .spinner {
    width: 32px;
    height: 32px;
    border: 3px solid rgba(0, 240, 255, 0.2);
    border-top-color: #00f0ff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  .table-responsive {
    overflow-x: auto;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(15, 40, 47, 0.6);
  }

  .teachers-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
  }

  .teachers-table th {
    background: rgba(20, 56, 64, 0.8);
    color: rgba(255, 255, 255, 0.85);
    font-weight: 600;
    text-align: right;
    padding: 0.875rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    white-space: nowrap;
  }

  .teachers-table td {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    vertical-align: middle;
  }

  .teachers-table tr:hover td {
    background: rgba(255, 255, 255, 0.03);
  }

  .teacher-profile-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .avatar-circle {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #143840, #00f0ff);
    color: #0f282f;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
  }

  .teacher-name {
    display: block;
    color: #fff;
    font-weight: 600;
  }

  .teacher-id {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.45);
  }

  .contact-cell {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
  }

  .teacher-email {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.8125rem;
  }

  .teacher-phone {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.75rem;
  }

  .courses-tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
    max-width: 280px;
  }

  .course-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    background: rgba(0, 240, 255, 0.08);
    border: 1px solid rgba(0, 240, 255, 0.25);
    border-radius: 6px;
    padding: 0.2rem 0.5rem;
    font-size: 0.75rem;
    color: #e0faff;
  }

  .course-badge-share {
    background: rgba(0, 240, 255, 0.2);
    color: #00f0ff;
    padding: 0.05rem 0.3rem;
    border-radius: 4px;
    font-weight: 700;
  }

  .no-courses-text {
    color: rgba(255, 255, 255, 0.4);
    font-size: 0.8125rem;
  }

  .earnings-val {
    white-space: nowrap;
    font-size: 0.875rem;
  }

  .earnings-val.earned {
    color: #e0faff;
  }

  .earnings-val.paid {
    color: rgba(255, 255, 255, 0.55);
  }

  .earnings-val.balance {
    color: #f1f5f9;
  }

  .earnings-val.balance.has-balance {
    color: #00f0ff;
    font-weight: 700;
  }

  .actions-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
  }

  .btn-action {
    padding: 0.35rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 6px;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-assign {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    border-color: rgba(255, 255, 255, 0.15);
  }

  .btn-assign:hover {
    background: rgba(255, 255, 255, 0.16);
  }

  .btn-payout {
    background: rgba(0, 240, 255, 0.12);
    color: #00f0ff;
    border-color: rgba(0, 240, 255, 0.3);
  }

  .btn-payout:hover {
    background: rgba(0, 240, 255, 0.25);
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
    max-width: 500px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
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
    line-height: 1;
  }

  .btn-close:hover {
    color: #fff;
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

  .form-group label {
    font-size: 0.8125rem;
    color: rgba(255, 255, 255, 0.85);
    font-weight: 600;
  }

  .label-with-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .btn-text-action {
    background: none;
    border: none;
    color: #00f0ff;
    font-size: 0.75rem;
    cursor: pointer;
    text-decoration: underline;
    padding: 0;
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
    transition: border-color 0.2s;
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

  .payout-balance-summary {
    display: flex;
    justify-content: space-between;
    background: rgba(0, 240, 255, 0.08);
    border: 1px solid rgba(0, 240, 255, 0.2);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #e0faff;
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

  .btn-cancel:hover {
    background: rgba(255, 255, 255, 0.15);
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

  .btn-submit:not(:disabled):hover {
    background: #4ef4ff;
  }
</style>
