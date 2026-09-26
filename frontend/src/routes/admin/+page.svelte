<script lang="ts">
  import { onMount } from 'svelte';
  import {
    getAdminOverview,
    getAdminCourses,
    getAdminStudents,
    createAdminCourse,
    updateAdminCourse,
    deleteAdminCourse,
    type AdminOverviewData,
    type AdminCourse,
    type AdminStudent,
  } from '$lib/api/admin';
  import { getAuthToken } from '$lib/api/client';
  import { getCurrentUser } from '$lib/api/auth';
  import AuthGuardCard from '$lib/components/AuthGuardCard.svelte';

  let overview: AdminOverviewData | null = $state(null);
  let courses: AdminCourse[] = $state([]);
  let students: any[] = $state([]);
  let loading = $state(true);
  let errorMsg = $state('');
  let isUnauthenticated = $state(false);
  let currentRole = $state('');

  // Active Tab
  let activeTab = $state<'courses' | 'students' | 'activity' | 'system'>('courses');

  // Modal State
  let isCourseModalOpen = $state(false);
  let isEditingCourse = $state(false);
  let editingCourseId = $state<number | null>(null);
  let modalLoading = $state(false);
  let modalError = $state('');

  // Course Form Fields
  let formTitleAr = $state('');
  let formTitleEn = $state('');
  let formSlug = $state('');
  let formPricePounds = $state(150);
  let formDescAr = $state('');
  let formDescEn = $state('');
  let formStatus = $state<'published' | 'draft' | 'archived'>('published');
  let formTelegramLink = $state('');
  let formTeacherShare = $state(70);

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

      const [overviewRes, coursesRes, studentsRes] = await Promise.all([
        getAdminOverview(fetch),
        getAdminCourses(fetch, 1).catch(() => ({ data: [] })),
        getAdminStudents(fetch, 1).catch(() => ({ data: [] })),
      ]);

      overview = overviewRes.data;
      courses = coursesRes.data || [];
      students = (studentsRes as any).data || [];
    } catch (e: any) {
      const msg = e?.message || '';
      if (msg.includes('401') || msg.includes('Unauthenticated')) {
        isUnauthenticated = true;
      } else if (msg.includes('403') || msg.includes('Unauthorized')) {
        if (!currentRole) currentRole = 'student';
      } else {
        errorMsg = msg || 'تعذر تحميل بيانات لوحة التحكم. تأكد من امتلاك صلاحيات المسؤول.';
      }
    } finally {
      loading = false;
    }
  }

  function openCreateCourseModal() {
    isEditingCourse = false;
    editingCourseId = null;
    formTitleAr = '';
    formTitleEn = '';
    formSlug = '';
    formPricePounds = 150;
    formDescAr = '';
    formDescEn = '';
    formStatus = 'published';
    formTelegramLink = '';
    formTeacherShare = 70;
    modalError = '';
    isCourseModalOpen = true;
  }

  function openEditCourseModal(course: AdminCourse) {
    isEditingCourse = true;
    editingCourseId = course.id;
    formTitleAr = course.title?.ar || '';
    formTitleEn = course.title?.en || '';
    formSlug = course.slug || '';
    formPricePounds = Math.round((course.price_cents || 0) / 100);
    formDescAr = course.description?.ar || '';
    formDescEn = course.description?.en || '';
    formStatus = course.status || 'published';
    formTelegramLink = course.telegram_invite_link || '';
    formTeacherShare = course.teacher_share_percent || 70;
    modalError = '';
    isCourseModalOpen = true;
  }

  function closeCourseModal() {
    isCourseModalOpen = false;
    modalError = '';
  }

  function autoSlug() {
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
    modalLoading = true;
    modalError = '';

    try {
      const payload: Record<string, unknown> = {
        title: {
          ar: formTitleAr,
          en: formTitleEn || formTitleAr,
        },
        description: {
          ar: formDescAr || 'وصف المادة',
          en: formDescEn || 'Course description',
        },
        slug: formSlug,
        price_cents: Math.max(0, Math.round(formPricePounds * 100)),
        status: formStatus,
        teacher_share_percent: Number(formTeacherShare),
      };

      if (formTelegramLink) {
        payload.telegram_invite_link = formTelegramLink;
      }

      if (isEditingCourse && editingCourseId) {
        await updateAdminCourse(fetch, editingCourseId, payload);
      } else {
        await createAdminCourse(fetch, payload);
      }

      closeCourseModal();
      await loadData();
    } catch (err: unknown) {
      modalError = err instanceof Error ? err.message : 'تعذر حفظ بيانات المقرر.';
    } finally {
      modalLoading = false;
    }
  }

  async function handleDeleteCourse(course: AdminCourse) {
    if (!confirm(`هل أنت متأكد من رغبتك في حذف مقرر "${course.title.ar}" نهائياً؟`)) {
      return;
    }

    try {
      await deleteAdminCourse(fetch, course.id);
      await loadData();
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : 'تعذر حذف المقرر.');
    }
  }

  onMount(() => {
    loadData();
  });
</script>

<svelte:head>
  <title>لوحة تحكم المسؤول | منصة Codeera</title>
  <meta name="description" content="لوحة تحكم إدارة المنصة الشاملة لإدارة المقررات والمحتوى والمدفوعات والطلاب." />
</svelte:head>

{#if isUnauthenticated || (currentRole && currentRole !== 'admin' && currentRole !== 'superadmin')}
  <AuthGuardCard
    requiredRole="admin"
    {isUnauthenticated}
    {currentRole}
    onRetry={loadData}
  />
{:else if loading}
  <div class="admin-loading-shell" dir="rtl">
    <div class="spinner" aria-hidden="true"></div>
    <p>جاري تحميل لوحة تحكم المسؤول الشاملة...</p>
  </div>
{:else if errorMsg}
  <div class="admin-error-shell" dir="rtl">
    <AuthGuardCard
      requiredRole="admin"
      customError={errorMsg}
      onRetry={loadData}
    />
  </div>
{:else if overview}
  <div class="admin-page" dir="rtl">
    <div class="admin-container">
      <!-- Breadcrumb -->
      <nav class="breadcrumbs" aria-label="مسار التنقل">
        <a href="/">الرئيسية</a>
        <span class="sep">/</span>
        <span class="current">لوحة تحكم المسؤول</span>
      </nav>

      <!-- Admin Top Banner -->
      <header class="admin-header">
        <div class="header-titles">
          <div class="badge-row">
            <span class="role-badge">مسؤول المنصة</span>
            <span class="role-sub">نظام إدارة الدورات والمدفوعات</span>
          </div>
          <h1>لوحة تحكم الإدارة العامة</h1>
          <p>إدارة مركزية للمقررات، مراجعة إيصالات الدفع، متابعة حسابات الطلاب، وتخصيص النظام.</p>
        </div>

        <div class="header-actions">
          <button type="button" class="btn-create-course-header" onclick={openCreateCourseModal}>
            <span>إضافة مقرر جديد</span>
          </button>
          <a href="/admin/payments" class="btn-payments-queue">
            <span>طابور مراجعة الإيصالات</span>
            {#if overview.pending_payments_count > 0}
              <span class="badge-count-pulse">{overview.pending_payments_count}</span>
            {/if}
          </a>
          <button type="button" class="btn-refresh" onclick={loadData} title="تحديث البيانات" aria-label="تحديث البيانات">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
            </svg>
          </button>
        </div>
      </header>

      <!-- KPI Metrics Cards Grid -->
      <section class="kpi-grid" aria-label="مؤشرات الأداء">
        <!-- Card 1 -->
        <a href="/admin/payments" class="kpi-card pending-kpi">
          <div class="kpi-card-head">
            <span class="kpi-label">طلبات الدفع المعلقة</span>
            <span class="indicator-dot {overview.pending_payments_count > 0 ? 'dot-pulse' : ''}" aria-hidden="true"></span>
          </div>
          <strong class="kpi-value pending-val">{overview.pending_payments_count}</strong>
          <span class="kpi-hint">تحتاج مراجعة وقبول فوري ←</span>
        </a>

        <!-- Card 2 -->
        <div class="kpi-card enroll-kpi">
          <div class="kpi-card-head">
            <span class="kpi-label">الاشتراكات الفعالة</span>
            <span class="kpi-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path>
              </svg>
            </span>
          </div>
          <strong class="kpi-value enroll-val">{overview.active_enrollments_count}</strong>
          <span class="kpi-hint">طالب مشترك بمقررات الفصل</span>
        </div>

        <!-- Card 3 -->
        <div class="kpi-card revenue-kpi">
          <div class="kpi-card-head">
            <span class="kpi-label">إيرادات هذا الفصل</span>
            <span class="kpi-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                <circle cx="12" cy="12" r="2"></circle>
                <path d="M6 12h.01M18 12h.01"></path>
              </svg>
            </span>
          </div>
          <strong class="kpi-value revenue-val">{formatPrice(overview.term_revenue_cents)}</strong>
          <span class="kpi-hint">إجمالي المبيعات المعتمدة</span>
        </div>

        <!-- Card 4 -->
        <div class="kpi-card students-kpi">
          <div class="kpi-card-head">
            <span class="kpi-label">إجمالي الطلاب المسجلين</span>
            <span class="kpi-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
            </span>
          </div>
          <strong class="kpi-value students-val">{overview.total_students_count}</strong>
          <span class="kpi-hint">حساب طالب نشط بالمنصة</span>
        </div>
      </section>

      <!-- Tab Navigation -->
      <nav class="admin-tabs" aria-label="أقسام لوحة الإدارة">
        <button
          type="button"
          class="tab-btn"
          class:active={activeTab === 'courses'}
          onclick={() => activeTab = 'courses'}
        >
          إدارة المقررات ({courses.length})
        </button>
        <button
          type="button"
          class="tab-btn"
          class:active={activeTab === 'students'}
          onclick={() => activeTab = 'students'}
        >
          دليل الطلاب ({students.length})
        </button>
        <button
          type="button"
          class="tab-btn"
          class:active={activeTab === 'activity'}
          onclick={() => activeTab = 'activity'}
        >
          سجل العمليات والتدقيق
        </button>
        <button
          type="button"
          class="tab-btn"
          class:active={activeTab === 'system'}
          onclick={() => activeTab = 'system'}
        >
          إعدادات المنصة والصفحات
        </button>
      </nav>

      <!-- TAB 1: COURSES MANAGEMENT -->
      {#if activeTab === 'courses'}
        <section class="dash-panel">
          <div class="panel-top-bar">
            <div>
              <h2>قائمة المقررات الدراسية ({courses.length})</h2>
              <p>إمكانية إضافة المقررات، تعديل الأسعار والبيانات، وحذف المواد غير المطلوبة.</p>
            </div>
            <button type="button" class="btn-create-course-header" onclick={openCreateCourseModal}>
              إضافة مقرر جديد
            </button>
          </div>

          {#if courses.length === 0}
            <div class="empty-box">
              <p>لا توجد مقررات دراسية مضافة حتى الآن.</p>
              <button type="button" class="btn-create-course-header" onclick={openCreateCourseModal}>
                إضافة أول مقرر دراسي
              </button>
            </div>
          {:else}
            <div class="table-responsive">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>المقرر (عربي / إنجليزي)</th>
                    <th>الرابط (Slug)</th>
                    <th>السعر</th>
                    <th>نسبة المحاضر</th>
                    <th>الحالة</th>
                    <th>مجموعة تليجرام</th>
                    <th>الإجراءات</th>
                  </tr>
                </thead>
                <tbody>
                  {#each courses as course (course.id)}
                    <tr>
                      <td>
                        <strong>{course.title.ar}</strong>
                        {#if course.title.en}
                          <small class="en-sub" dir="ltr">{course.title.en}</small>
                        {/if}
                      </td>
                      <td>
                        <code class="slug-tag" dir="ltr">{course.slug}</code>
                      </td>
                      <td>
                        <strong>{formatPrice(course.price_cents)}</strong>
                      </td>
                      <td>
                        <span>{course.teacher_share_percent ?? 70}%</span>
                      </td>
                      <td>
                        <span class="status-pill {course.status === 'published' ? 'pill-green' : 'pill-yellow'}">
                          {course.status === 'published' ? 'منشور' : 'مسودة'}
                        </span>
                      </td>
                      <td>
                        {#if course.telegram_invite_link}
                          <a href={course.telegram_invite_link} target="_blank" rel="noreferrer" class="link-tel" dir="ltr">
                            مربوط ↗
                          </a>
                        {:else}
                          <span class="muted-text">غير مربوط</span>
                        {/if}
                      </td>
                      <td>
                        <div class="action-buttons-group">
                          <button
                            type="button"
                            class="btn-sm btn-edit"
                            onclick={() => openEditCourseModal(course)}
                            title="تعديل بيانات وسعر المادة"
                          >
                            تعديل
                          </button>
                          <a
                            href={`/my-courses/${course.slug}`}
                            class="btn-sm btn-content"
                            title="إدارة فصول ومحاضرات واختبارات المادة"
                          >
                            المحتوى
                          </a>
                          <a
                            href={`/courses/${course.slug}`}
                            class="btn-sm btn-view"
                            title="معاينة صفحة المادة العامة"
                          >
                            عرض
                          </a>
                          <button
                            type="button"
                            class="btn-sm btn-delete"
                            onclick={() => handleDeleteCourse(course)}
                            title="حذف المقرر"
                            aria-label="حذف المقرر"
                          >
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
          {/if}
        </section>

      <!-- TAB 2: STUDENTS DIRECTORY -->
      {:else if activeTab === 'students'}
        <section class="dash-panel">
          <div class="panel-top-bar">
            <div>
              <h2>دليل الطلاب المسجلين ({students.length})</h2>
              <p>استعراض بيانات الطلاب، الفرق الدراسية، والأقسام الأكاديمية.</p>
            </div>
          </div>

          {#if students.length === 0}
            <div class="empty-box">
              <p>لا يوجد طلاب مسجلون بعد.</p>
            </div>
          {:else}
            <div class="table-responsive">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>اسم الطالب</th>
                    <th>البريد الإلكتروني</th>
                    <th>الفرع</th>
                    <th>الفرقة</th>
                    <th>القسم</th>
                    <th>معرّف تليجرام</th>
                    <th>تاريخ التسجيل</th>
                  </tr>
                </thead>
                <tbody>
                  {#each students as st (st.id)}
                    <tr>
                      <td><strong>{st.name}</strong></td>
                      <td><span dir="ltr">{st.email}</span></td>
                      <td>{st.branch === 'azhar_boys' ? 'بنين' : st.branch === 'azhar_girls' ? 'بنات' : '—'}</td>
                      <td>{st.academic_year || '—'}</td>
                      <td>{st.department || '—'}</td>
                      <td>
                        {#if st.telegram_username}
                          <span dir="ltr">@{st.telegram_username}</span>
                        {:else}
                          <span class="muted-text">—</span>
                        {/if}
                      </td>
                      <td>{new Date(st.created_at).toLocaleDateString('ar-EG')}</td>
                    </tr>
                  {/each}
                </tbody>
              </table>
            </div>
          {/if}
        </section>

      <!-- TAB 3: AUDIT LOG -->
      {:else if activeTab === 'activity'}
        <section class="dash-panel">
          <div class="panel-top-bar">
            <div>
              <h2>سجل العمليات والتدقيق (Activity Log)</h2>
              <p>سجل زمني لجميع عمليات الدفع، تعديل البيانات، والاعتمادات بالمنصة.</p>
            </div>
          </div>

          {#if overview.recent_activity.length === 0}
            <div class="empty-box">
              <p>لا توجد سجلات نشاط مسجلة بعد.</p>
            </div>
          {:else}
            <div class="activity-timeline">
              {#each overview.recent_activity as act (act.id)}
                <div class="activity-entry">
                  <div class="entry-dot" aria-hidden="true"></div>
                  <div class="entry-content">
                    <p class="entry-title">{act.description}</p>
                    <p class="entry-meta">
                      <span>بواسطة: <strong>{act.causer_name || 'النظام'}</strong></span>
                      <span class="sep">•</span>
                      <span>سجل: {act.log_name}</span>
                      <span class="sep">•</span>
                      <span>{new Date(act.created_at).toLocaleString('ar-EG')}</span>
                    </p>
                  </div>
                </div>
              {/each}
            </div>
          {/if}
        </section>

      <!-- TAB 4: SYSTEM SETTINGS & PAGES -->
      {:else if activeTab === 'system'}
        <section class="dash-panel">
          <div class="panel-top-bar">
            <div>
              <h2>إعدادات المنصة والروابط الإدارية</h2>
              <p>روابط مباشرة للأقسام التشغيلية والصفحات العامة.</p>
            </div>
          </div>

          <div class="system-modules-grid">
            <a href="/admin/payments" class="sys-module-card">
              <span class="sys-icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                  <line x1="2" y1="10" x2="22" y2="10"></line>
                </svg>
              </span>
              <h3>طابور مراجعة الإيصالات</h3>
              <p>مراجعة صور إيصالات فودافون كاش وإنستاباي والتحقق من التكرارات.</p>
            </a>

            <a href="/courses" class="sys-module-card">
              <span class="sys-icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                  <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
              </span>
              <h3>كتالوج المقررات العام</h3>
              <p>استعراض المقررات كما تظهر للطلاب مع إمكانية التعديل السريع.</p>
            </a>

            <a href="/teacher" class="sys-module-card">
              <span class="sys-icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                  <polyline points="16 11 18 13 22 9"></polyline>
                </svg>
              </span>
              <h3>لوحة المحاضرين</h3>
              <p>أرصدة المحاضرين، نسب الأرباح، وسجلات التحويل المالي.</p>
            </a>

            <a href="/terms" class="sys-module-card">
              <span class="sys-icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                  <polyline points="14 2 14 8 20 8"></polyline>
                  <line x1="16" y1="13" x2="8" y2="13"></line>
                  <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
              </span>
              <h3>الشروط والأحكام</h3>
              <p>معاينة صفحة بنود وشروط الاستخدام وسياسات المنصة.</p>
            </a>

            <a href="/privacy" class="sys-module-card">
              <span class="sys-icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
              </span>
              <h3>سياسة الخصوصية</h3>
              <p>معاينة بنود حماية خصوصية بيانات الطلاب وأرقام الهواتف.</p>
            </a>

            <a href="/refund" class="sys-module-card">
              <span class="sys-icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2.5 2v6h6M2.66 15.57a10 10 0 1 0 .57-8.38l-5.67-5.67"></path>
                </svg>
              </span>
              <h3>سياسة الاسترداد</h3>
              <p>معاينة شروط وإجراءات طلبات استرداد الرسوم الدراسية.</p>
            </a>
          </div>
        </section>
      {/if}
    </div>
  </div>
{/if}

<!-- Create / Edit Course Modal -->
{#if isCourseModalOpen}
  <div class="modal-backdrop" onclick={closeCourseModal} role="presentation">
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <div class="modal-card" onclick={(e) => e.stopPropagation()} role="dialog" aria-modal="true" dir="rtl" tabindex="-1">
      <div class="modal-header">
        <h2>{isEditingCourse ? 'تعديل بيانات المقرر' : 'إضافة مقرر دراسي جديد'}</h2>
        <button type="button" class="btn-close-modal" onclick={closeCourseModal} aria-label="إغلاق">&times;</button>
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
            <label for="admin-title-ar">اسم المقرر بالعربية *</label>
            <input
              id="admin-title-ar"
              type="text"
              bind:value={formTitleAr}
              required
              placeholder="مثال: هياكل البيانات"
            />
          </div>

          <div class="form-group">
            <label for="admin-title-en">اسم المقرر بالإنجليزية</label>
            <input
              id="admin-title-en"
              type="text"
              dir="ltr"
              bind:value={formTitleEn}
              onblur={autoSlug}
              placeholder="e.g. Data Structures"
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="admin-slug">الرابط الدائم (Slug) *</label>
            <input
              id="admin-slug"
              type="text"
              dir="ltr"
              bind:value={formSlug}
              required
              placeholder="data-structures"
            />
          </div>

          <div class="form-group">
            <label for="admin-price">السعر (بالجنيه المصري) *</label>
            <input
              id="admin-price"
              type="number"
              min="0"
              step="5"
              bind:value={formPricePounds}
              required
            />
          </div>
        </div>

        <div class="form-group">
          <label for="admin-desc-ar">الوصف التعريفي بالمقرر</label>
          <textarea
            id="admin-desc-ar"
            rows="3"
            bind:value={formDescAr}
            placeholder="أهداف المقرر وموضوعاته..."
          ></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="admin-status">حالة المقرر</label>
            <select id="admin-status" bind:value={formStatus}>
              <option value="published">منشور (يظهر للطلاب للتسجيل)</option>
              <option value="draft">مسودة (مخفي عن الطلاب)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="admin-teacher-share">نسبة أرباح المحاضر (%)</label>
            <input
              id="admin-teacher-share"
              type="number"
              min="0"
              max="100"
              bind:value={formTeacherShare}
            />
          </div>
        </div>

        <div class="form-group">
          <label for="admin-tel">رابط مجموعة تليجرام الخاصة</label>
          <input
            id="admin-tel"
            type="url"
            dir="ltr"
            bind:value={formTelegramLink}
            placeholder="https://t.me/+joinchat..."
          />
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick={closeCourseModal} disabled={modalLoading}>
            إلغاء
          </button>
          <button type="submit" class="btn-save" disabled={modalLoading}>
            {#if modalLoading}
              <span>جاري الحفظ...</span>
            {:else}
              <span>{isEditingCourse ? 'حفظ التعديلات' : 'إنشاء المقرر'}</span>
            {/if}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}

<style>
  .admin-page {
    background-color: var(--paper);
    min-height: calc(100vh - 140px);
    padding: 2rem 1.25rem 4rem;
  }

  .admin-container {
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

  .breadcrumbs .sep {
    color: var(--line);
  }

  .breadcrumbs .current {
    color: var(--storm);
    font-weight: 800;
  }

  .admin-header {
    background: var(--card);
    border: 2.5px solid var(--line);
    border-radius: 1.25rem;
    padding: 1.75rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
    box-shadow: 0 4px 15px rgba(15, 40, 47, 0.05);
  }

  .badge-row {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 0.5rem;
  }

  .role-badge {
    background: #fee2e2;
    color: #991b1b;
    font-weight: 900;
    font-size: 0.78rem;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
    border: 1px solid #f87171;
  }

  .role-sub {
    font-size: 0.82rem;
    color: var(--muted);
    font-weight: 600;
  }

  .header-titles h1 {
    font-size: 1.85rem;
    font-weight: 900;
    color: var(--storm);
    margin: 0 0 0.35rem;
  }

  .header-titles p {
    color: var(--muted);
    font-size: 0.95rem;
    margin: 0;
  }

  .header-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  .btn-create-course-header {
    background: var(--brand-accent);
    color: #ffffff;
    border: 2px solid var(--brand-accent);
    font-weight: 800;
    font-size: 0.92rem;
    padding: 0.65rem 1.25rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: transform 150ms ease, opacity 150ms ease;
  }

  .btn-create-course-header:hover {
    transform: translateY(-1px);
    opacity: 0.95;
  }

  .btn-payments-queue {
    background: var(--card);
    color: var(--storm);
    border: 2px solid var(--line);
    font-weight: 800;
    font-size: 0.9rem;
    padding: 0.65rem 1.15rem;
    border-radius: 0.5rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: background-color 150ms ease;
  }

  .btn-payments-queue:hover {
    background-color: var(--paper);
  }

  .badge-count-pulse {
    background: #f59e0b;
    color: white;
    font-size: 0.75rem;
    font-weight: 900;
    padding: 0.15rem 0.5rem;
    border-radius: 9999px;
    animation: pulse 1.5s infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.75; transform: scale(1.1); }
  }

  .btn-refresh {
    background: var(--card);
    border: 2px solid var(--line);
    padding: 0.65rem 0.85rem;
    border-radius: 0.5rem;
    cursor: pointer;
    font-size: 1rem;
  }

  /* KPI Grid */
  .kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
  }

  .kpi-card {
    background: var(--card);
    border: 2.5px solid var(--line);
    border-radius: 1rem;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 0.5rem;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(15, 40, 47, 0.04);
    transition: transform 150ms ease, border-color 150ms ease;
  }

  .kpi-card:hover {
    transform: translateY(-2px);
    border-color: var(--deep-cyan);
  }

  .kpi-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .kpi-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--muted);
  }

  .kpi-value {
    font-size: 2.2rem;
    font-weight: 900;
    line-height: 1;
    margin: 0.25rem 0;
  }

  .pending-val { color: #d97706; }
  .enroll-val { color: #059669; }
  .revenue-val { color: var(--storm); }
  .students-val { color: #0284c7; }

  .kpi-hint {
    font-size: 0.78rem;
    color: var(--muted);
    font-weight: 600;
  }

  .indicator-dot {
    width: 0.65rem;
    height: 0.65rem;
    border-radius: 50%;
    background: #cbd5e1;
  }

  .dot-pulse {
    background: #f59e0b;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.3);
  }

  /* Tabs */
  .admin-tabs {
    display: flex;
    gap: 0.5rem;
    border-bottom: 2.5px solid var(--line);
    padding-bottom: 0.25rem;
    overflow-x: auto;
  }

  .tab-btn {
    background: var(--card);
    border: 2px solid var(--line);
    border-bottom: none;
    border-radius: 0.6rem 0.6rem 0 0;
    padding: 0.75rem 1.25rem;
    font-size: 0.92rem;
    font-weight: 800;
    color: var(--muted);
    cursor: pointer;
    font-family: inherit;
    white-space: nowrap;
    transition: all 120ms ease;
  }

  .tab-btn:hover {
    color: var(--storm);
    background: var(--paper);
  }

  .tab-btn.active {
    background: var(--brand-navy);
    color: #FAF8F5;
    border-color: var(--brand-navy);
  }

  /* Panels */
  .dash-panel {
    background: var(--card);
    border: 2.5px solid var(--line);
    border-radius: 1.25rem;
    padding: 1.75rem;
    box-shadow: 0 4px 15px rgba(15, 40, 47, 0.04);
  }

  .panel-top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid var(--line);
    padding-bottom: 1.25rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
  }

  .panel-top-bar h2 {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0 0 0.25rem;
  }

  .panel-top-bar p {
    color: var(--muted);
    font-size: 0.88rem;
    margin: 0;
  }

  /* Tables */
  .table-responsive {
    overflow-x: auto;
  }

  .admin-table {
    width: 100%;
    border-collapse: collapse;
    border: 2px solid var(--line);
    font-size: 0.88rem;
  }

  .admin-table th, .admin-table td {
    padding: 0.85rem 1rem;
    text-align: right;
    border-bottom: 2px solid var(--line);
    border-inline-end: 1px solid #7da59d;
  }

  .admin-table th {
    background: var(--paper);
    color: var(--storm);
    font-weight: 800;
    font-size: 0.82rem;
  }

  .admin-table tr:hover td {
    background: var(--card-hover);
  }

  .en-sub {
    display: block;
    color: var(--muted);
    font-size: 0.78rem;
  }

  .slug-tag {
    background: #f1f5f9;
    padding: 0.15rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.78rem;
    color: #0f172a;
    border: 1px solid var(--line);
  }

  .status-pill {
    font-size: 0.75rem;
    font-weight: 800;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
  }

  .pill-green {
    background: #d1fae5;
    color: #065f46;
  }

  .pill-yellow {
    background: #fef3c7;
    color: #92400e;
  }

  .link-tel {
    color: var(--deep-cyan);
    font-weight: 700;
    text-decoration: none;
  }

  .muted-text {
    color: #94a3b8;
  }

  .action-buttons-group {
    display: flex;
    align-items: center;
    gap: 0.4rem;
  }

  .btn-sm {
    padding: 0.35rem 0.65rem;
    border-radius: 0.35rem;
    font-size: 0.78rem;
    font-weight: 800;
    cursor: pointer;
    text-decoration: none;
    font-family: inherit;
    transition: all 120ms ease;
  }

  .btn-edit {
    background: var(--brand-navy);
    color: #FAF8F5;
    border: 2px solid var(--brand-navy);
  }

  .btn-content {
    background: var(--paper);
    color: var(--deep-cyan);
    border: 2px solid var(--line);
  }

  .btn-view {
    background: var(--card);
    color: var(--storm);
    border: 2px solid var(--line);
  }

  .btn-delete {
    background: #fee2e2;
    color: #991b1b;
    border: 2px solid #f87171;
  }

  .btn-sm:hover {
    transform: translateY(-1px);
    opacity: 0.9;
  }

  .empty-box {
    text-align: center;
    padding: 3rem 1rem;
    background: var(--paper);
    border: 2px dashed var(--line);
    border-radius: 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    color: var(--muted);
    font-weight: 600;
  }

  /* Timeline */
  .activity-timeline {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .activity-entry {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    padding: 0.85rem 1rem;
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: 0.75rem;
  }

  .entry-dot {
    width: 0.75rem;
    height: 0.75rem;
    background: var(--deep-cyan);
    border-radius: 50%;
    margin-top: 0.4rem;
    flex-shrink: 0;
  }

  .entry-title {
    font-weight: 800;
    color: var(--storm);
    margin: 0 0 0.25rem;
    font-size: 0.92rem;
  }

  .entry-meta {
    font-size: 0.82rem;
    color: var(--muted);
    margin: 0;
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
  }

  /* System Modules Grid */
  .system-modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
  }

  .sys-module-card {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: 0.85rem;
    padding: 1.5rem;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    transition: transform 150ms ease, border-color 150ms ease;
  }

  .sys-module-card:hover {
    transform: translateY(-2px);
    border-color: var(--deep-cyan);
  }

  .sys-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.65rem;
    background: rgba(var(--brand-navy-rgb), 0.08);
    color: var(--brand-navy);
    margin-bottom: 0.35rem;
  }

  :global([data-theme='dark']) .sys-icon {
    background: rgba(91, 122, 199, 0.15);
    color: var(--deep-cyan);
  }

  .sys-module-card h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--storm);
  }

  .sys-module-card p {
    margin: 0;
    font-size: 0.85rem;
    color: var(--muted);
    line-height: 1.5;
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
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    padding: 2rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
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
    background: var(--paper);
    border: 2px solid var(--line);
    padding: 0.6rem 1.25rem;
    border-radius: 0.5rem;
    font-weight: 700;
    color: var(--storm);
    cursor: pointer;
  }

  .btn-save {
    background: var(--brand-navy);
    color: #FAF8F5;
    border: 2px solid var(--brand-navy);
    padding: 0.6rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 800;
    cursor: pointer;
  }

  .admin-loading-shell, .admin-error-shell {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 50vh;
    gap: 1rem;
    color: var(--muted);
    font-weight: 700;
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

  @media (max-width: 768px) {
    .admin-page { padding: 1.25rem 0.75rem 3rem; }
    .admin-header { padding: 1.25rem 1rem; }
    .header-actions { width: 100%; flex-direction: column; align-items: stretch; }
    .header-actions button, .header-actions a { width: 100%; text-align: center; justify-content: center; }
    .form-row { grid-template-columns: 1fr; }
    .kpi-grid { grid-template-columns: 1fr; }
    .admin-tabs { gap: 0.25rem; }
    .tab-btn { padding: 0.6rem 0.9rem; font-size: 0.85rem; }
  }
</style>
