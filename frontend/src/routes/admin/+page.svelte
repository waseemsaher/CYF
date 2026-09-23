<script lang="ts">
  import { onMount } from 'svelte';
  import { getAdminOverview, type AdminOverviewData } from '$lib/api/admin';
  import { getAuthToken } from '$lib/api/client';
  import { getCurrentUser } from '$lib/api/auth';
  import AuthGuardCard from '$lib/components/AuthGuardCard.svelte';

  let overview: AdminOverviewData | null = $state(null);
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

      const res = await getAdminOverview(fetch);
      overview = res.data;
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

  onMount(() => {
    loadData();
  });
</script>

<svelte:head>
  <title>لوحة التحكم الرئيسية | منصة دورات حاسبات الأزهر</title>
</svelte:head>

{#if isUnauthenticated || (currentRole && currentRole !== 'admin' && currentRole !== 'superadmin')}
  <AuthGuardCard
    requiredRole="admin"
    {isUnauthenticated}
    {currentRole}
    onRetry={loadData}
  />
{:else if loading}
  <div class="min-h-screen bg-background text-foreground py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-8">
      <div class="h-10 w-48 bg-muted rounded animate-pulse"></div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 animate-pulse">
        <div class="h-28 bg-muted rounded-2xl"></div>
        <div class="h-28 bg-muted rounded-2xl"></div>
        <div class="h-28 bg-muted rounded-2xl"></div>
        <div class="h-28 bg-muted rounded-2xl"></div>
      </div>
    </div>
  </div>
{:else if errorMsg}
  <div class="min-h-screen bg-background text-foreground py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-8">
      <AuthGuardCard
        requiredRole="admin"
        customError={errorMsg}
        onRetry={loadData}
      />
    </div>
  </div>
{:else if overview}
<div class="min-h-screen bg-background text-foreground py-10 px-4 sm:px-6 lg:px-8">
  <div class="max-w-6xl mx-auto space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border pb-6">
      <div>
        <h1 class="text-3xl font-extrabold tracking-tight">لوحة تحكم المسؤول</h1>
        <p class="text-sm text-muted-foreground mt-1">نظرة عامة على مؤشرات الأداء والمدفوعات والطلاب</p>
      </div>

      <div class="flex items-center gap-3">
        <a
          href="/admin/payments"
          class="px-4 py-2.5 rounded-xl bg-primary text-primary-foreground font-bold text-sm hover:bg-primary/90 transition shadow-sm"
        >
          طابور مراجعة الإيصالات
        </a>
      </div>
    </div>
      <!-- Metric Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1 -->
        <a href="/admin/payments" class="p-6 rounded-2xl border border-border bg-card shadow-sm hover:border-primary/50 transition space-y-2 block">
          <div class="flex items-center justify-between text-muted-foreground">
            <span class="text-xs font-semibold">طلبات الدفع المعلقة</span>
            <span class="inline-flex h-2.5 w-2.5 rounded-full {overview.pending_payments_count > 0 ? 'bg-amber-500 animate-pulse' : 'bg-muted'}"></span>
          </div>
          <p class="text-3xl font-black text-amber-500">{overview.pending_payments_count}</p>
          <p class="text-xs text-muted-foreground">تحتاج مراجعة وقبول</p>
        </a>

        <!-- Card 2 -->
        <div class="p-6 rounded-2xl border border-border bg-card shadow-sm space-y-2">
          <div class="text-xs font-semibold text-muted-foreground">الاشتراكات الفعالة</div>
          <p class="text-3xl font-black text-emerald-500">{overview.active_enrollments_count}</p>
          <p class="text-xs text-muted-foreground">طالب مسجل حالياً</p>
        </div>

        <!-- Card 3 -->
        <div class="p-6 rounded-2xl border border-border bg-card shadow-sm space-y-2">
          <div class="text-xs font-semibold text-muted-foreground">إيرادات هذا الفصل</div>
          <p class="text-3xl font-black text-foreground">{formatPrice(overview.term_revenue_cents)}</p>
          <p class="text-xs text-muted-foreground">إجمالي المبيعات المؤكدة</p>
        </div>

        <!-- Card 4 -->
        <div class="p-6 rounded-2xl border border-border bg-card shadow-sm space-y-2">
          <div class="text-xs font-semibold text-muted-foreground">إجمالي الطلاب</div>
          <p class="text-3xl font-black text-sky-500">{overview.total_students_count}</p>
          <p class="text-xs text-muted-foreground">حسابات طلاب مسجلة</p>
        </div>
      </div>

      <!-- Quick Navigation Modules -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
        <a href="/admin/payments" class="p-6 rounded-2xl border border-border bg-card hover:bg-muted/20 transition space-y-2">
          <h3 class="font-bold text-base">إدارة المدفوعات والتحويلات</h3>
          <p class="text-xs text-muted-foreground">مراجعة إيصالات فودافون كاش وإنستاباي والتحقق من التكرارات</p>
        </a>

        <a href="/courses" class="p-6 rounded-2xl border border-border bg-card hover:bg-muted/20 transition space-y-2">
          <h3 class="font-bold text-base">كتالوج المواد والمناهج</h3>
          <p class="text-xs text-muted-foreground">عرض المواد التعليمية والأقسام المخصصة لكل سنة دراسية</p>
        </a>

        <a href="/teacher" class="p-6 rounded-2xl border border-border bg-card hover:bg-muted/20 transition space-y-2">
          <h3 class="font-bold text-base">بوابة المحاضرين</h3>
          <p class="text-xs text-muted-foreground">متابعة أرصدة المدرسين ونسب الأرباح وسجلات التحويل</p>
        </a>
      </div>

      <!-- Recent Audit Log -->
      <div class="rounded-2xl border border-border bg-card overflow-hidden shadow-sm space-y-4 p-6">
        <h2 class="text-lg font-bold">آخر النشاطات والعمليات</h2>

        {#if overview.recent_activity.length === 0}
          <p class="text-xs text-muted-foreground py-4 text-center">لا توجد سجلات نشاط مسجلة بعد.</p>
        {:else}
          <div class="divide-y divide-border">
            {#each overview.recent_activity as act}
              <div class="py-3 flex items-center justify-between text-xs">
                <div class="space-y-0.5">
                  <span class="font-semibold text-foreground">{act.description}</span>
                  <span class="text-muted-foreground block">بواسطة: {act.causer_name} ({act.log_name})</span>
                </div>
                <span class="text-muted-foreground shrink-0">{new Date(act.created_at).toLocaleTimeString('ar-EG')}</span>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    </div>
  </div>
{/if}
