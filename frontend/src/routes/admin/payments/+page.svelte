<script lang="ts">
  import {
    getAdminPayments,
    approveAdminPayment,
    rejectAdminPayment,
    type Payment
  } from '$lib/api/payments';
  import { getAuthToken } from '$lib/api/client';
  import { getCurrentUser } from '$lib/api/auth';
  import AuthGuardCard from '$lib/components/AuthGuardCard.svelte';

  let payments: Payment[] = $state([]);
  let counts: Record<string, number> = $state({});
  let activeStatus = $state('pending');
  let loading = $state(true);
  let errorMsg = $state('');
  let isUnauthenticated = $state(false);
  let currentRole = $state('');
  let actionLoading = $state<number | null>(null);
  let rejectingId = $state<number | null>(null);
  let rejectionReason = $state('');

  const formatPrice = (cents: number) => `${(cents / 100).toLocaleString('ar-EG')} جنيه`;
  const formatDate = (iso: string) => new Date(iso).toLocaleDateString('ar-EG', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });

  const statusTabs = [
    { key: 'pending', label: 'قيد المراجعة' },
    { key: 'approved', label: 'تمت الموافقة' },
    { key: 'rejected', label: 'مرفوض' },
    { key: 'cancelled', label: 'ملغى' }
  ];

  async function loadPayments() {
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

      const res = await getAdminPayments(fetch, activeStatus);
      payments = res.data;
      counts = res.counts ?? {};
    } catch (e: any) {
      const msg = e?.message || '';
      if (msg.includes('401') || msg.includes('Unauthenticated')) {
        isUnauthenticated = true;
      } else if (msg.includes('403') || msg.includes('Unauthorized')) {
        if (!currentRole) currentRole = 'student';
      } else {
        errorMsg = 'تعذر تحميل الدفعات. تأكد من صلاحيات الوصول.';
      }
    } finally {
      loading = false;
    }
  }

  async function approvePayment(id: number) {
    actionLoading = id;
    try {
      await approveAdminPayment(fetch, id);
      await loadPayments();
    } catch {
      errorMsg = 'فشلت عملية الموافقة.';
    } finally {
      actionLoading = null;
    }
  }

  async function rejectPayment(id: number) {
    if (!rejectionReason.trim()) return;
    actionLoading = id;
    try {
      await rejectAdminPayment(fetch, id, rejectionReason);
      rejectingId = null;
      rejectionReason = '';
      await loadPayments();
    } catch {
      errorMsg = 'فشلت عملية الرفض.';
    } finally {
      actionLoading = null;
    }
  }

  function switchTab(status: string) {
    activeStatus = status;
    loadPayments();
  }

  $effect(() => {
    loadPayments();
  });
</script>

<svelte:head>
  <title>مراجعة الدفعات | منصة Codeera</title>
</svelte:head>

{#if isUnauthenticated || (currentRole && currentRole !== 'admin' && currentRole !== 'superadmin')}
  <AuthGuardCard
    requiredRole="admin"
    {isUnauthenticated}
    {currentRole}
    onRetry={loadPayments}
  />
{:else}
<main class="admin-shell">
  <header class="admin-header">
    <a class="brand" href="/">CODEERA <span>ADMIN</span></a>
    <span class="header-title">مراجعة الدفعات</span>
  </header>

  <nav class="status-tabs" aria-label="تصفية حسب الحالة">
    {#each statusTabs as tab}
      <button
        class:active={activeStatus === tab.key}
        onclick={() => switchTab(tab.key)}
      >
        {tab.label}
        {#if counts[tab.key] !== undefined}
          <span class="tab-count">{counts[tab.key]}</span>
        {/if}
      </button>
    {/each}
  </nav>

  {#if loading}
    <div class="loading-state">
      <div class="spinner" aria-hidden="true"></div>
      <p>جاري تحميل الدفعات...</p>
    </div>
  {:else if errorMsg}
    <div class="notice error" role="alert">{errorMsg}</div>
  {:else if payments.length === 0}
    <div class="empty-state">
      <p>لا توجد دفعات في هذه الحالة.</p>
    </div>
  {:else}
    <div class="payment-queue">
      {#each payments as payment}
        <article class="queue-card" class:duplicate-warning={payment.has_duplicate_proof}>
          {#if payment.has_duplicate_proof}
            <div class="duplicate-banner" role="alert">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
              </svg>
              <span>إثبات دفع مكرر — نفس الصورة مستخدمة في دفعة أخرى</span>
            </div>
          {/if}

          <div class="card-grid">
            <div class="student-info">
              <h2>{payment.user?.name ?? 'طالب'}</h2>
              <dl>
                <dt>البريد</dt><dd>{payment.user?.email ?? '-'}</dd>
                <dt>الفرع</dt><dd>{payment.user?.branch ?? '-'}</dd>
                <dt>السنة</dt><dd>{payment.user?.academic_year ?? '-'}</dd>
                <dt>القسم</dt><dd>{payment.user?.department ?? '-'}</dd>
              </dl>
            </div>

            <div class="payment-info">
              <h3>{payment.course?.title?.ar ?? 'الدورة'}</h3>
              <div class="info-row"><span>المبلغ المطلوب</span><strong>{formatPrice(payment.amount_due_cents)}</strong></div>
              <div class="info-row"><span>طريقة الدفع</span><span>{payment.method}</span></div>
              <div class="info-row"><span>المرسل</span><span>{payment.sender_identifier}</span></div>
              <div class="info-row"><span>التاريخ</span><span>{formatDate(payment.created_at)}</span></div>
              {#if payment.student_note}
                <div class="student-note"><strong>ملاحظة الطالب:</strong> {payment.student_note}</div>
              {/if}
            </div>
          </div>

          {#if activeStatus === 'pending'}
            <div class="card-actions">
              {#if rejectingId === payment.id}
                <div class="reject-form">
                  <textarea
                    bind:value={rejectionReason}
                    placeholder="سبب الرفض (مطلوب)"
                    rows="2"
                  ></textarea>
                  <div class="reject-buttons">
                    <button
                      class="btn-reject-confirm"
                      onclick={() => rejectPayment(payment.id)}
                      disabled={actionLoading === payment.id || !rejectionReason.trim()}
                    >
                      تأكيد الرفض
                    </button>
                    <button class="btn-cancel" onclick={() => { rejectingId = null; rejectionReason = ''; }}>
                      إلغاء
                    </button>
                  </div>
                </div>
              {:else}
                <button
                  class="btn-approve"
                  onclick={() => approvePayment(payment.id)}
                  disabled={actionLoading === payment.id}
                >
                  {actionLoading === payment.id ? 'جاري...' : 'موافقة'}
                </button>
                <button
                  class="btn-reject"
                  onclick={() => { rejectingId = payment.id; }}
                  disabled={actionLoading === payment.id}
                >
                  رفض
                </button>
              {/if}
            </div>
          {/if}
        </article>
      {/each}
    </div>
  {/if}
</main>
{/if}

<style>
  :global(*) { box-sizing: border-box; }
  .admin-shell { margin: 0 auto; max-width: 1100px; padding: 1.25rem 1.25rem 4rem; }
  .admin-header { align-items: center; display: flex; gap: 1.5rem; padding: 0.5rem 0 2rem; }
  .brand { color: var(--storm); font-size: 1.05rem; font-weight: 800; letter-spacing: 0.08em; text-decoration: none; }
  .brand span { color: var(--brand-accent); font-size: 0.68rem; margin-inline-start: 0.35rem; }
  .header-title { color: var(--muted); font-size: 0.95rem; font-weight: 700; }
  .status-tabs { background: var(--card); border: 2px solid var(--line); border-radius: 0.6rem; display: flex; margin-bottom: 1.5rem; overflow: hidden; }
  .status-tabs button { background: none; border: 0; cursor: pointer; flex: 1; font: inherit; font-weight: 700; padding: 0.85rem 1rem; position: relative; transition: background 150ms, color 150ms; color: var(--storm); }
  .status-tabs button:not(:last-child) { border-inline-end: 2px solid var(--line); }
  .status-tabs button.active { background: var(--brand-navy); color: #FAF8F5; }
  .tab-count { background: rgba(255,255,255,0.2); border-radius: 9rem; font-size: 0.72rem; margin-inline-start: 0.4rem; padding: 0.15rem 0.5rem; }
  .status-tabs button.active .tab-count { background: var(--brand-accent); color: #ffffff; }
  .loading-state { align-items: center; display: flex; flex-direction: column; gap: 1rem; padding: 4rem 0; }
  .spinner { animation: spin 800ms linear infinite; border: 3px solid var(--line); border-radius: 50%; border-top-color: var(--deep-cyan); height: 2.5rem; width: 2.5rem; }
  @keyframes spin { to { transform: rotate(360deg); } }
  .notice { background: var(--card); border: 2px solid var(--line); border-radius: 0.6rem; padding: 1.25rem; color: var(--storm); }
  .error { border-color: #e8b5b2; color: #8a302b; }
  .empty-state { background: var(--card); border: 2px solid var(--line); border-radius: 0.6rem; padding: 3rem; text-align: center; }
  .empty-state p { color: var(--muted); margin: 0; }
  .payment-queue { display: grid; gap: 1rem; }
  .queue-card { background: var(--card); border: 2px solid var(--line); border-radius: 0.75rem; overflow: hidden; }
  .queue-card.duplicate-warning { border-color: #f59e0b; }
  .duplicate-banner { background: #fef3c7; color: #92400e; font-size: 0.85rem; font-weight: 700; padding: 0.6rem 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
  .card-grid { display: grid; gap: 1.5rem; grid-template-columns: 1fr 1.3fr; padding: 1.25rem; }
  .student-info h2 { font-size: 1.1rem; margin: 0 0 0.75rem; }
  dl { display: grid; gap: 0.3rem; grid-template-columns: 4rem 1fr; margin: 0; }
  dt { color: var(--muted); font-size: 0.82rem; }
  dd { font-size: 0.88rem; margin: 0; }
  .payment-info h3 { font-size: 1rem; margin: 0 0 0.75rem; }
  .info-row { display: flex; justify-content: space-between; margin-bottom: 0.35rem; }
  .info-row span { color: var(--muted); font-size: 0.88rem; }
  .info-row strong { color: var(--storm); }
  .student-note { background: var(--paper); border-radius: 0.35rem; font-size: 0.85rem; margin-top: 0.75rem; padding: 0.6rem 0.85rem; }
  .card-actions { border-top: 2px solid var(--line); display: flex; gap: 0.75rem; padding: 1rem 1.25rem; }
  .btn-approve { background: var(--brand-navy); border: 0; border-radius: 0.4rem; color: #FAF8F5; cursor: pointer; flex: 1; font: inherit; font-weight: 800; min-height: 2.8rem; transition: background-color 150ms ease, opacity 150ms ease; }
  .btn-approve:hover { background: #1E2B4D; opacity: 0.95; }
  .btn-reject { background: transparent; border: 2px solid #e8b5b2; border-radius: 0.4rem; color: #8a302b; cursor: pointer; flex: 1; font: inherit; font-weight: 800; min-height: 2.8rem; }
  .btn-approve:disabled, .btn-reject:disabled { cursor: not-allowed; opacity: 0.5; }
  .reject-form { display: grid; gap: 0.75rem; width: 100%; }
  .reject-form textarea { background: #fef2f2; border: 2px solid #e8b5b2; border-radius: 0.4rem; font: inherit; padding: 0.6rem 0.85rem; resize: vertical; }
  .reject-buttons { display: flex; gap: 0.5rem; }
  .btn-reject-confirm { background: #991b1b; border: 0; border-radius: 0.4rem; color: white; cursor: pointer; flex: 1; font: inherit; font-weight: 800; min-height: 2.5rem; }
  .btn-cancel { background: transparent; border: 2px solid var(--line); border-radius: 0.4rem; color: var(--muted); cursor: pointer; font: inherit; font-weight: 700; min-height: 2.5rem; padding: 0 1rem; }
  .btn-reject-confirm:disabled { cursor: not-allowed; opacity: 0.5; }
  @media (max-width: 760px) { .card-grid { grid-template-columns: 1fr; } .status-tabs { flex-wrap: wrap; } .status-tabs button { flex-basis: 50%; } }
</style>
