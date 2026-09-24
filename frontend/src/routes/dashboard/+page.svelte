<script lang="ts">
  import { onMount } from 'svelte';
  import { getCurrentUser, type UserProfile } from '$lib/api/auth';
  import { getMyPayments, type Payment } from '$lib/api/payments';
  import { getAuthToken } from '$lib/api/client';
  import AuthGuardCard from '$lib/components/AuthGuardCard.svelte';
  import TelegramLinkCard from '$lib/components/TelegramLinkCard.svelte';

  let user: UserProfile | null = $state(null);
  let payments: Payment[] = $state([]);
  let loading = $state(true);
  let isUnauthenticated = $state(false);
  let errorMsg = $state('');

  let approvedCourses = $derived(
    payments
      .filter((p) => p.status === 'approved' && p.course)
      .map((p) => p.course!)
  );

  let pendingPayments = $derived(
    payments.filter((p) => p.status === 'pending')
  );

  const academicYearLabels: Record<string, string> = {
    first_year: 'الفرقة الأولى',
    second_year: 'الفرقة الثانية',
    third_year: 'الفرقة الثالثة',
    fourth_year: 'الفرقة الرابعة',
  };

  const departmentLabels: Record<string, string> = {
    general: 'العام (المرحلة الأساسية)',
    cs: 'علوم الحاسب (CS)',
    is: 'نظم المعلومات (IS)',
    it: 'تكنولوجيا المعلومات (IT)',
    ai: 'الذكاء الاصطناعي (AI)',
  };

  const branchLabels: Record<string, string> = {
    azhar_boys: 'فرع البنين — مدينة نصر',
    azhar_girls: 'فرع البنات — يوسف عباس',
  };

  async function loadDashboard() {
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
      const [userRes, paymentsRes] = await Promise.all([
        getCurrentUser(fetch).catch(() => null),
        getMyPayments(fetch).catch(() => ({ data: [] })),
      ]);

      if (!userRes?.data?.user) {
        isUnauthenticated = true;
      } else {
        user = userRes.data.user;
        payments = paymentsRes.data || [];
      }
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : String(err);
      if (msg.includes('401') || msg.includes('Unauthenticated')) {
        isUnauthenticated = true;
      } else {
        errorMsg = msg || 'تعذر تحميل بيانات لوحة الطالب.';
      }
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    loadDashboard();
  });
</script>

<svelte:head>
  <title>لوحة تحكم الطالب | منصة Codeera</title>
  <meta name="description" content="لوحة تحكم الطالب لمتابعة المقررات المسجلة والاشتراكات وحساب التليجرام." />
</svelte:head>

{#if isUnauthenticated}
  <AuthGuardCard requiredRole="student" isUnauthenticated={true} onRetry={loadDashboard} />
{:else if loading}
  <div class="dashboard-loading" dir="rtl">
    <div class="spinner" aria-hidden="true"></div>
    <p>جاري تحميل لوحة تحكم الطالب...</p>
  </div>
{:else if user}
  <div class="dashboard-page" dir="rtl">
    <div class="dashboard-container">
      <!-- Breadcrumb -->
      <nav class="breadcrumbs" aria-label="مسار التنقل">
        <a href="/">الرئيسية</a>
        <span class="sep">/</span>
        <span class="current">لوحة الطالب</span>
      </nav>

      <!-- Welcome Banner -->
      <header class="welcome-banner">
        <div class="banner-content">
          <div class="user-greeting">
            <span class="badge-role">طالب بالكلية</span>
            <h1>مرحباً بك، {user.name} 👋</h1>
            <p class="user-meta-summary">
              <span>{branchLabels[user.branch || ''] || 'كلية الحاسبات والذكاء الاصطناعي'}</span>
              <span class="dot">•</span>
              <span>{academicYearLabels[user.academic_year || ''] || user.academic_year || 'الفرقة الدراسية'}</span>
              <span class="dot">•</span>
              <span>{departmentLabels[user.department || ''] || user.department || 'القسم الأكاديمي'}</span>
            </p>
          </div>
          <div class="banner-actions">
            <a href="/courses" class="btn-primary">
              <span>تصفح المواد المتاحة</span>
              <span aria-hidden="true">←</span>
            </a>
            <a href="/payments" class="btn-secondary">
              سجل مدفوعاتي
            </a>
          </div>
        </div>
      </header>

      {#if errorMsg}
        <div class="error-banner" role="alert">
          <span aria-hidden="true">⚠️</span>
          <p>{errorMsg}</p>
        </div>
      {/if}

      <!-- Main Grid Layout -->
      <div class="dashboard-grid">
        <!-- Left / Main Column: Courses & Subscriptions -->
        <div class="grid-main">
          <!-- Active Enrolled Courses -->
          <section class="dash-section">
            <div class="section-header">
              <div class="section-title">
                <h2>📚 المواد المشترك بها</h2>
                <span class="counter-badge">{approvedCourses.length}</span>
              </div>
              <a href="/courses" class="view-all-link">إضافة مادة جديدة</a>
            </div>

            {#if approvedCourses.length > 0}
              <div class="courses-grid">
                {#each approvedCourses as course (course.id)}
                  <article class="course-card">
                    <div class="course-card-body">
                      <span class="course-badge">اشتراك نشط</span>
                      <h3 class="course-title">{course.title.ar}</h3>
                      {#if course.title.en}
                        <span class="course-en-title" dir="ltr">{course.title.en}</span>
                      {/if}
                    </div>
                    <div class="course-card-footer">
                      <a href="/my-courses/{course.slug}" class="btn-go-course">
                        <span>الدخول إلى محتوى المادة</span>
                        <span aria-hidden="true">←</span>
                      </a>
                    </div>
                  </article>
                {/each}
              </div>
            {:else}
              <div class="empty-state-box">
                <div class="empty-icon" aria-hidden="true">📖</div>
                <h3>لم تشترك في أي مواد دراسية بعد</h3>
                <p>تصفح قائمة مقررات الفرقة الدراسية واشترك لفتح المحاضرات والملفات والتكليفات.</p>
                <a href="/courses" class="btn-browse-courses">استعراض المواد الآن</a>
              </div>
            {/if}
          </section>

          <!-- Pending Approvals -->
          {#if pendingPayments.length > 0}
            <section class="dash-section pending-section">
              <div class="section-header">
                <div class="section-title">
                  <h2>⏳ اشتراكات قيد المراجعة والاعتماد</h2>
                  <span class="counter-badge pending-badge">{pendingPayments.length}</span>
                </div>
                <a href="/payments" class="view-all-link">عرض التفاصيل الكاملة</a>
              </div>

              <div class="pending-list">
                {#each pendingPayments as p (p.id)}
                  <div class="pending-item">
                    <div class="pending-info">
                      <h4>{p.course?.title?.ar || 'طلب اشتراك بمقرر'}</h4>
                      <p>
                        <span>طريقة الدفع: <strong>{p.method}</strong></span>
                        <span class="dot">•</span>
                        <span>رقم الحساب/المحفظة: <strong dir="ltr">{p.sender_identifier}</strong></span>
                        <span class="dot">•</span>
                        <span>المبلغ: <strong>{(p.amount_due_cents / 100).toLocaleString('ar-EG')} جنيه</strong></span>
                      </p>
                    </div>
                    <div class="pending-status-chip">
                      <span>قيد التدقيق من الإدارة</span>
                    </div>
                  </div>
                {/each}
              </div>
            </section>
          {/if}

          <!-- Telegram Linking Section -->
          <section class="dash-section">
            <div class="section-header">
              <div class="section-title">
                <h2>🤖 ربط حساب التليجرام الرسمي</h2>
              </div>
            </div>
            <p class="section-desc">
              اربط حسابك لتتمكن من الانضمام التلقائي لجروبات المواد الخاصة على تليجرام، واستلام إشعارات القبول والمذكرات لحظة بلحظة.
            </p>
            <TelegramLinkCard />
          </section>
        </div>

        <!-- Right / Sidebar Column: Academic Profile Info -->
        <aside class="grid-sidebar">
          <div class="profile-card">
            <div class="profile-header">
              <div class="profile-avatar" aria-hidden="true">
                {user.name.charAt(0)}
              </div>
              <h3 class="profile-name">{user.name}</h3>
              <span class="profile-email" dir="ltr">{user.email}</span>
            </div>

            <hr class="profile-divider" />

            <div class="profile-details">
              <div class="detail-row">
                <span class="detail-label">الفرع:</span>
                <span class="detail-value">{branchLabels[user.branch || ''] || user.branch || 'غير محدد'}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">الفرقة الدراسية:</span>
                <span class="detail-value">{academicYearLabels[user.academic_year || ''] || user.academic_year || 'غير محدد'}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">القسم:</span>
                <span class="detail-value">{departmentLabels[user.department || ''] || user.department || 'غير محدد'}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">الهاتف:</span>
                <span class="detail-value" dir="ltr">{user.phone || 'لم يُسجل'}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">معرّف تليجرام:</span>
                <span class="detail-value" dir="ltr">
                  {#if user.telegram_username}
                    @{user.telegram_username}
                  {:else}
                    غير مربوط
                  {/if}
                </span>
              </div>
            </div>

            <div class="profile-actions">
              <a href="/payments" class="btn-profile-link">
                💳 سجل عمليات الدفع
              </a>
              <a href="/courses" class="btn-profile-link">
                📚 دليل المقررات
              </a>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>
{/if}

<style>
  .dashboard-loading {
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

  .dashboard-page {
    background-color: var(--paper);
    min-height: calc(100vh - 140px);
    padding: 2rem 1.25rem 4rem;
  }

  .dashboard-container {
    max-width: 1200px;
    margin-inline: auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .breadcrumbs {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--muted);
  }

  .breadcrumbs a {
    color: var(--muted);
    text-decoration: none;
    font-weight: 600;
  }

  .breadcrumbs a:hover {
    color: var(--storm);
  }

  .breadcrumbs .sep {
    color: var(--line);
  }

  .breadcrumbs .current {
    color: var(--storm);
    font-weight: 700;
  }

  .welcome-banner {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    padding: 1.75rem 2rem;
    box-shadow: 0 4px 20px -5px rgba(15, 40, 47, 0.05);
  }

  .banner-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
  }

  .user-greeting {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
  }

  .badge-role {
    align-self: flex-start;
    font-size: 0.72rem;
    font-weight: 800;
    background: #eef7f6;
    color: var(--deep-cyan);
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    border: 1px solid rgba(23, 119, 122, 0.25);
  }

  :global([data-theme='dark']) .badge-role {
    background: rgba(2, 239, 240, 0.12);
    color: var(--cyan);
    border-color: rgba(2, 239, 240, 0.3);
  }

  .user-greeting h1 {
    font-size: 1.75rem;
    font-weight: 900;
    color: var(--storm);
    margin: 0;
  }

  .user-meta-summary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--muted);
    font-size: 0.9rem;
    margin: 0;
    flex-wrap: wrap;
  }

  .dot {
    color: var(--line);
  }

  .banner-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
  }

  .btn-primary {
    background: var(--storm);
    color: var(--cyan);
    font-weight: 800;
    font-size: 0.92rem;
    padding: 0.65rem 1.25rem;
    border-radius: 0.5rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    border: 2px solid var(--storm);
    transition: transform 150ms ease, opacity 150ms ease;
  }

  .btn-primary:hover {
    transform: translateY(-1px);
    opacity: 0.95;
  }

  .btn-secondary {
    background: var(--card);
    color: var(--storm);
    font-weight: 700;
    font-size: 0.92rem;
    padding: 0.65rem 1.15rem;
    border-radius: 0.5rem;
    text-decoration: none;
    border: 2px solid var(--line);
    transition: background-color 150ms ease;
  }

  .btn-secondary:hover {
    background-color: var(--paper);
    border-color: var(--storm);
  }

  .error-banner {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    background: #fdf2f2;
    border: 2px solid #f8b4b4;
    color: #9b1c1c;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.9rem;
  }

  :global([data-theme='dark']) .error-banner {
    background: rgba(239, 68, 68, 0.12);
    border-color: rgba(239, 68, 68, 0.4);
    color: #fca5a5;
  }

  .dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.5rem;
    align-items: start;
  }

  .grid-main {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .dash-section {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    padding: 1.5rem 1.75rem;
    box-shadow: 0 4px 20px -5px rgba(15, 40, 47, 0.04);
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .section-title {
    display: flex;
    align-items: center;
    gap: 0.6rem;
  }

  .section-title h2 {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .counter-badge {
    background: #eef7f6;
    color: var(--deep-cyan);
    font-weight: 800;
    font-size: 0.8rem;
    padding: 0.15rem 0.5rem;
    border-radius: 9999px;
  }

  :global([data-theme='dark']) .counter-badge {
    background: rgba(2, 239, 240, 0.15);
    color: var(--cyan);
  }

  .pending-badge {
    background: #fef3c7;
    color: #92400e;
  }

  :global([data-theme='dark']) .pending-badge {
    background: rgba(245, 158, 11, 0.2);
    color: #fde68a;
  }

  .view-all-link {
    color: var(--deep-cyan);
    font-weight: 700;
    font-size: 0.85rem;
    text-decoration: none;
  }

  .view-all-link:hover {
    text-decoration: underline;
  }

  .section-desc {
    color: var(--muted);
    font-size: 0.9rem;
    margin: 0 0 1rem;
    line-height: 1.5;
  }

  .courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
  }

  .course-card {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: 0.85rem;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 1rem;
    transition: transform 150ms ease, border-color 150ms ease;
  }

  .course-card:hover {
    transform: translateY(-2px);
    border-color: var(--deep-cyan);
  }

  .course-badge {
    font-size: 0.7rem;
    font-weight: 800;
    color: #065f46;
    background: #d1fae5;
    padding: 0.15rem 0.5rem;
    border-radius: 0.25rem;
    display: inline-block;
    margin-bottom: 0.5rem;
  }

  :global([data-theme='dark']) .course-badge {
    background: rgba(16, 185, 129, 0.18);
    color: #6ee7b7;
    border: 1px solid rgba(16, 185, 129, 0.3);
  }

  .course-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0 0 0.25rem;
  }

  .course-en-title {
    font-size: 0.8rem;
    color: var(--muted);
    display: block;
  }

  .btn-go-course {
    background: var(--storm);
    color: var(--cyan);
    font-size: 0.85rem;
    font-weight: 800;
    padding: 0.5rem 0.85rem;
    border-radius: 0.4rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    transition: opacity 150ms ease;
  }

  .btn-go-course:hover {
    opacity: 0.9;
  }

  .empty-state-box {
    text-align: center;
    padding: 2.5rem 1rem;
    background: var(--paper);
    border: 2px dashed var(--line);
    border-radius: 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
  }

  .empty-icon {
    font-size: 2.5rem;
  }

  .empty-state-box h3 {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .empty-state-box p {
    color: var(--muted);
    font-size: 0.9rem;
    margin: 0;
    max-width: 420px;
    line-height: 1.5;
  }

  .btn-browse-courses {
    background: var(--storm);
    color: var(--cyan);
    font-size: 0.9rem;
    font-weight: 800;
    padding: 0.6rem 1.25rem;
    border-radius: 0.5rem;
    text-decoration: none;
    margin-top: 0.5rem;
  }

  .pending-section {
    border-color: #fde68a;
    background: #fffbeb;
  }

  .pending-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .pending-item {
    background: var(--card);
    border: 2px solid #fde68a;
    border-radius: 0.75rem;
    padding: 0.85rem 1.15rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .pending-info h4 {
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0 0 0.25rem;
  }

  .pending-info p {
    font-size: 0.82rem;
    color: var(--muted);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
  }

  .pending-status-chip {
    font-size: 0.75rem;
    font-weight: 800;
    color: #92400e;
    background: #fef3c7;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    border: 1px solid #fcd34d;
  }

  :global([data-theme='dark']) .pending-status-chip {
    background: rgba(245, 158, 11, 0.2);
    color: #fde68a;
    border-color: rgba(245, 158, 11, 0.4);
  }

  .grid-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .profile-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    padding: 1.75rem 1.5rem;
    box-shadow: 0 4px 20px -5px rgba(15, 40, 47, 0.04);
  }

  .profile-header {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
  }

  .profile-avatar {
    width: 4rem;
    height: 4rem;
    background: var(--storm);
    color: var(--cyan);
    font-size: 1.5rem;
    font-weight: 900;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.35rem;
    border: 2px solid var(--cyan);
  }

  .profile-name {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .profile-email {
    font-size: 0.85rem;
    color: var(--muted);
  }

  .profile-divider {
    border: none;
    border-top: 2px solid var(--line);
    margin: 1.25rem 0;
  }

  .profile-details {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    gap: 0.5rem;
  }

  .detail-label {
    color: var(--muted);
    font-weight: 600;
  }

  .detail-value {
    color: var(--storm);
    font-weight: 700;
    text-align: left;
  }

  .profile-actions {
    margin-top: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .btn-profile-link {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: 0.5rem;
    padding: 0.6rem 0.85rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--storm);
    text-decoration: none;
    text-align: center;
    transition: background-color 150ms ease, border-color 150ms ease;
  }

  .btn-profile-link:hover {
    background-color: var(--card-hover);
    border-color: var(--deep-cyan);
  }

  @media (max-width: 900px) {
    .dashboard-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 768px) {
    .dashboard-page {
      padding: 1.25rem 0.75rem 3rem;
    }

    .welcome-banner {
      padding: 1.25rem 1rem;
    }

    .banner-content {
      flex-direction: column;
      align-items: stretch;
      gap: 1rem;
    }

    .banner-actions {
      flex-direction: column;
      align-items: stretch;
      width: 100%;
    }

    .banner-actions a {
      width: 100%;
      text-align: center;
      justify-content: center;
    }

    .courses-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
