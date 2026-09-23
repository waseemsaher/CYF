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
  <title>لوحة المحاضر | منصة دورات حاسبات الأزهر</title>
</svelte:head>

{#if isUnauthenticated || (currentRole && currentRole !== 'teacher' && currentRole !== 'admin' && currentRole !== 'superadmin')}
  <AuthGuardCard
    requiredRole="teacher"
    {isUnauthenticated}
    {currentRole}
    onRetry={loadData}
  />
{:else if loading}
  <div class="min-h-screen bg-background text-foreground py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-8">
      <div class="h-10 w-48 bg-muted rounded animate-pulse"></div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-pulse">
        <div class="h-32 bg-muted rounded-2xl"></div>
        <div class="h-32 bg-muted rounded-2xl"></div>
        <div class="h-32 bg-muted rounded-2xl"></div>
      </div>
    </div>
  </div>
{:else if errorMsg}
  <div class="min-h-screen bg-background text-foreground py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-8">
      <AuthGuardCard
        requiredRole="teacher"
        customError={errorMsg}
        onRetry={loadData}
      />
    </div>
  </div>
{:else if data}
<div class="min-h-screen bg-background text-foreground py-10 px-4 sm:px-6 lg:px-8">
  <div class="max-w-6xl mx-auto space-y-8">
    <div class="border-b border-border pb-6">
      <h1 class="text-3xl font-extrabold tracking-tight">لوحة تحكم المحاضر</h1>
      <p class="text-sm text-muted-foreground mt-1">متابعة المواد المكلف بتدريسها، أعداد الطلاب، وأرصدة الأرباح</p>
    </div>
      <!-- Earnings Balance Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="p-6 rounded-2xl border border-border bg-card shadow-sm space-y-2">
          <span class="text-xs font-semibold text-muted-foreground">الرصيد المتاح للتحويل</span>
          <p class="text-3xl font-black text-emerald-500">{formatPrice(data.earnings.balance_cents)}</p>
          <p class="text-xs text-muted-foreground">أرباح مستحقة الدفع</p>
        </div>

        <div class="p-6 rounded-2xl border border-border bg-card shadow-sm space-y-2">
          <span class="text-xs font-semibold text-muted-foreground">إجمالي الأرباح المحققة</span>
          <p class="text-3xl font-black text-foreground">{formatPrice(data.earnings.earned_cents)}</p>
          <p class="text-xs text-muted-foreground">حصة المدرس من الاشتراكات المقبولة</p>
        </div>

        <div class="p-6 rounded-2xl border border-border bg-card shadow-sm space-y-2">
          <span class="text-xs font-semibold text-muted-foreground">المسحوبات السابقة</span>
          <p class="text-3xl font-black text-muted-foreground">{formatPrice(data.earnings.paid_out_cents)}</p>
          <p class="text-xs text-muted-foreground">تم تحويلها لحسابك البنكي أو المحفظة</p>
        </div>
      </div>

      <!-- Taught Courses -->
      <div class="space-y-4">
        <h2 class="text-xl font-bold">المواد المسندة إليك</h2>

        {#if data.courses.length === 0}
          <div class="p-8 rounded-2xl border border-dashed border-border text-center text-muted-foreground">
            لم يتم إسناد أي مواد إلى حسابك حتى الآن.
          </div>
        {:else}
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {#each data.courses as course}
              <div class="p-6 rounded-2xl border border-border bg-card shadow-sm space-y-4 flex flex-col justify-between">
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded bg-primary/10 text-primary">
                      نسبة الأرباح: {course.teacher_share_percent ?? 70}%
                    </span>
                    <span class="text-xs font-medium text-emerald-600 bg-emerald-500/10 px-2 py-0.5 rounded">
                      {course.active_students_count} طالب مشترك
                    </span>
                  </div>
                  <h3 class="text-lg font-bold">{course.title.ar}</h3>
                </div>

                <div class="flex items-center gap-3 pt-2">
                  <a
                    href="/my-courses/{course.slug}"
                    class="px-4 py-2 rounded-xl bg-primary text-primary-foreground text-xs font-bold hover:bg-primary/90 transition text-center flex-1"
                  >
                    عرض محتوى المادة
                  </a>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>

      <!-- Payouts History -->
      <div class="rounded-2xl border border-border bg-card p-6 shadow-sm space-y-4">
        <h2 class="text-lg font-bold">سجل التحويلات والمسحوبات</h2>

        {#if data.payouts.length === 0}
          <p class="text-xs text-muted-foreground py-4 text-center">لا توجد عمليات تحويل مسجلة بعد.</p>
        {:else}
          <div class="overflow-x-auto">
            <table class="w-full text-start text-xs">
              <thead class="bg-muted/50 border-b border-border text-muted-foreground">
                <tr>
                  <th class="p-3 text-start">تاريخ التحويل</th>
                  <th class="p-3 text-start">المبلغ</th>
                  <th class="p-3 text-start">ملاحظات</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                {#each data.payouts as payout}
                  <tr>
                    <td class="p-3">{new Date(payout.paid_at).toLocaleDateString('ar-EG')}</td>
                    <td class="p-3 font-bold text-foreground">{formatPrice(payout.amount_cents)}</td>
                    <td class="p-3 text-muted-foreground">{payout.note || '—'}</td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        {/if}
      </div>
    </div>
  </div>
{/if}
