<script lang="ts">
  import { onMount } from 'svelte';
  import { getTeacherDashboard, type TeacherDashboardData } from '$lib/api/admin';
  import { getAuthToken } from '$lib/api/client';
  import { getCurrentUser } from '$lib/api/auth';
  import AuthGuardCard from '$lib/components/AuthGuardCard.svelte';

  let data: TeacherDashboardData | null = $state(null);
  let loading = $state(true);
  let errorMsg = $state('');
  let isUnauthenticated = $state(false);
  let currentRole = $state('');

  const formatPrice = (cents: number) => `${(cents / 100).toLocaleString('ar-EG')} جنيه`;

  async function loadData() {
    loading = true;
    errorMsg = '';
    isUnauthenticated = false;

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

      const res = await getTeacherDashboard(fetch);
      data = res.data;
    } catch (e: any) {
      const msg = e?.message || '';
      if (msg.includes('401') || msg.includes('Unauthenticated')) {
        isUnauthenticated = true;
      } else if (msg.includes('403') || msg.includes('Unauthorized')) {
        if (!currentRole) currentRole = 'student';
      } else {
        errorMsg = msg || 'تعذر تحميل بيانات المحاضر. تأكد من تسجيل الدخول بحساب مدرس.';
      }
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    loadData();
  });
</script>

<svelte:head>
  <title>لوحة المحاضر | منصة Codeera</title>
</svelte:head>

{#if isUnauthenticated || (currentRole && currentRole !== 'teacher' && currentRole !== 'admin' && currentRole !== 'superadmin')}
  <AuthGuardCard
    requiredRole="teacher"
    {isUnauthenticated}
    {currentRole}
    onRetry={loadData}
  />
{:else if loading}
  <div class="teacher-loading" dir="rtl">
    <div class="spinner" aria-hidden="true"></div>
    <p>جاري تحميل لوحة المحاضر...</p>
  </div>
{:else if errorMsg}
  <div class="teacher-error" dir="rtl">
    <AuthGuardCard
      requiredRole="teacher"
      customError={errorMsg}
      onRetry={loadData}
    />
  </div>
{:else if data}
  <div class="teacher-page" dir="rtl">
    <div class="teacher-container">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-top">
          <span class="role-badge">محاضر</span>
        </div>
        <h1>لوحة تحكم المحاضر</h1>
        <p>متابعة المواد المكلف بتدريسها، أعداد الطلاب، وأرصدة الأرباح</p>
      </div>

      <!-- KPI Cards -->
      <section class="kpi-grid" aria-label="مؤشرات مالية">
        <div class="kpi-card">
          <span class="kpi-label">الرصيد المتاح للتحويل</span>
          <strong class="kpi-value kpi-green">{formatPrice(data.earnings.balance_cents)}</strong>
          <span class="kpi-hint">أرباح مستحقة الدفع</span>
        </div>

        <div class="kpi-card">
          <span class="kpi-label">إجمالي الأرباح المحققة</span>
          <strong class="kpi-value">{formatPrice(data.earnings.earned_cents)}</strong>
          <span class="kpi-hint">حصة المدرس من الاشتراكات المقبولة</span>
        </div>

        <div class="kpi-card">
          <span class="kpi-label">المسحوبات السابقة</span>
          <strong class="kpi-value kpi-muted">{formatPrice(data.earnings.paid_out_cents)}</strong>
          <span class="kpi-hint">تم تحويلها لحسابك البنكي أو المحفظة</span>
        </div>
      </section>

      <!-- Taught Courses -->
      <section class="section-panel">
        <h2>المواد المسندة إليك</h2>

        {#if data.courses.length === 0}
          <div class="empty-box">
            لم يتم إسناد أي مواد إلى حسابك حتى الآن.
          </div>
        {:else}
          <div class="courses-grid">
            {#each data.courses as course}
              <article class="course-card">
                <div class="course-card-top">
                  <span class="share-badge">
                    نسبة الأرباح: {course.teacher_share_percent ?? 70}%
                  </span>
                  <span class="students-badge">
                    {course.active_students_count} طالب مشترك
                  </span>
                </div>
                <h3>{course.title.ar}</h3>
                <a href="/my-courses/{course.slug}" class="btn-view-content">
                  عرض محتوى المادة
                </a>
              </article>
            {/each}
          </div>
        {/if}
      </section>

      <!-- Payouts History -->
      <section class="section-panel">
        <h2>سجل التحويلات والمسحوبات</h2>

        {#if data.payouts.length === 0}
          <p class="empty-text">لا توجد عمليات تحويل مسجلة بعد.</p>
        {:else}
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>تاريخ التحويل</th>
                  <th>المبلغ</th>
                  <th>ملاحظات</th>
                </tr>
              </thead>
              <tbody>
                {#each data.payouts as payout}
                  <tr>
                    <td>{new Date(payout.paid_at).toLocaleDateString('ar-EG')}</td>
                    <td><strong>{formatPrice(payout.amount_cents)}</strong></td>
                    <td class="muted-cell">{payout.note || '—'}</td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        {/if}
      </section>
    </div>
  </div>
{/if}

<style>
  .teacher-loading, .teacher-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 50vh;
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

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .teacher-page {
    background-color: var(--paper);
    min-height: calc(100vh - 140px);
    padding: 2rem 1.25rem 4rem;
  }

  .teacher-container {
    max-width: 1100px;
    margin-inline: auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .page-header {
    border-bottom: 2px solid var(--line);
    padding-bottom: 1.5rem;
  }

  .header-top {
    margin-bottom: 0.5rem;
  }

  .role-badge {
    background: rgba(var(--brand-navy-rgb), 0.08);
    color: var(--brand-navy);
    font-weight: 700;
    font-size: 0.78rem;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
    border: 2px solid rgba(var(--brand-navy-rgb), 0.25);
  }

  .page-header h1 {
    font-size: 1.85rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0 0 0.35rem;
  }

  .page-header p {
    color: var(--muted);
    font-size: 0.95rem;
    margin: 0;
  }

  /* KPI Cards */
  .kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
  }

  .kpi-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1rem;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    box-shadow: 0 4px 12px rgba(15, 40, 47, 0.04);
  }

  .kpi-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--muted);
  }

  .kpi-value {
    font-size: 2rem;
    font-weight: 800;
    color: var(--storm);
    line-height: 1;
  }

  .kpi-green {
    color: #059669;
  }

  .kpi-muted {
    color: var(--muted);
  }

  .kpi-hint {
    font-size: 0.78rem;
    color: var(--muted);
  }

  /* Section Panel */
  .section-panel {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    padding: 1.75rem;
    box-shadow: 0 4px 12px rgba(15, 40, 47, 0.04);
  }

  .section-panel h2 {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0 0 1.25rem;
  }

  /* Courses Grid */
  .courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
  }

  .course-card {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: 0.85rem;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    transition: transform 150ms ease, border-color 150ms ease;
  }

  .course-card:hover {
    transform: translateY(-2px);
    border-color: var(--deep-cyan);
  }

  .course-card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.4rem;
  }

  .share-badge {
    font-size: 0.75rem;
    font-weight: 700;
    background: rgba(var(--brand-navy-rgb), 0.08);
    color: var(--brand-navy);
    padding: 0.2rem 0.5rem;
    border-radius: 0.25rem;
  }

  .students-badge {
    font-size: 0.75rem;
    font-weight: 600;
    color: #059669;
    background: #ecfdf5;
    padding: 0.2rem 0.5rem;
    border-radius: 0.25rem;
  }

  .course-card h3 {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .btn-view-content {
    background: var(--storm);
    color: var(--cyan);
    font-size: 0.85rem;
    font-weight: 700;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    text-decoration: none;
    text-align: center;
    border: 2px solid var(--storm);
    transition: opacity 150ms ease;
  }

  .btn-view-content:hover {
    opacity: 0.9;
  }

  /* Empty States */
  .empty-box {
    text-align: center;
    padding: 2.5rem 1rem;
    background: var(--paper);
    border: 2px dashed var(--line);
    border-radius: 1rem;
    color: var(--muted);
    font-weight: 600;
  }

  .empty-text {
    text-align: center;
    color: var(--muted);
    font-size: 0.9rem;
    padding: 1.5rem 0;
    margin: 0;
  }

  /* Data Table */
  .table-responsive {
    overflow-x: auto;
  }

  .data-table {
    width: 100%;
    border-collapse: collapse;
    border: 2px solid var(--line);
    font-size: 0.88rem;
  }

  .data-table th,
  .data-table td {
    padding: 0.85rem 1rem;
    text-align: right;
    border-bottom: 2px solid var(--line);
  }

  .data-table th {
    background: var(--paper);
    color: var(--storm);
    font-weight: 700;
    font-size: 0.82rem;
  }

  .data-table tr:hover td {
    background: var(--card-hover);
  }

  .muted-cell {
    color: var(--muted);
  }

  @media (max-width: 768px) {
    .kpi-grid {
      grid-template-columns: 1fr;
    }

    .courses-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
