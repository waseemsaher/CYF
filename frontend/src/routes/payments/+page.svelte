<script lang="ts">
  import { getMyPayments, type Payment } from '$lib/api/payments';
  import { getAuthToken } from '$lib/api/client';

  let payments: Payment[] = $state([]);
  let loading = $state(true);
  let errorMsg = $state('');
  let isUnauthenticated = $state(false);

  const formatPrice = (cents: number) => `${(cents / 100).toLocaleString('ar-EG')} جنيه`;
  const formatDate = (iso: string) => {
    const d = new Date(iso);
    return d.toLocaleDateString('ar-EG', { year: 'numeric', month: 'short', day: 'numeric' });
  };

  const statusLabels: Record<string, { label: string; cls: string }> = {
    pending: { label: 'قيد المراجعة', cls: 'badge-pending' },
    approved: { label: 'تمت الموافقة', cls: 'badge-approved' },
    rejected: { label: 'مرفوض', cls: 'badge-rejected' },
    cancelled: { label: 'ملغى', cls: 'badge-cancelled' }
  };

  async function loadPayments() {
    loading = true;
    errorMsg = '';
    isUnauthenticated = false;

    if (!getAuthToken()) {
      isUnauthenticated = true;
      loading = false;
      return;
    }

    try {
      const data = await getMyPayments(fetch);
      payments = data.data;
    } catch {
      isUnauthenticated = true;
    } finally {
      loading = false;
    }
  }

  $effect(() => {
    loadPayments();
  });
</script>

<svelte:head>
  <title>دفعاتي | منصة Codeera</title>
  <meta name="description" content="عرض حالة الدفعات الخاصة بك" />
</svelte:head>

<div class="payments-shell">

  <section class="intro">
    <h1>دفعاتي</h1>
    <p class="lede">متابعة حالة جميع الدفعات التي قمت بها.</p>
  </section>

  {#if loading}
    <div class="loading-state">
      <div class="spinner" aria-hidden="true"></div>
      <p>جاري تحميل الدفعات...</p>
    </div>
  {:else if isUnauthenticated}
    <div class="empty-state">
      <p>يجب تسجيل الدخول لمتابعة حالة مدفوعاتك واشتراكاتك في المقررات.</p>
      <a href="/login" class="btn-login-cta">تسجيل الدخول</a>
    </div>
  {:else if errorMsg}
    <div class="notice error" role="alert">{errorMsg}</div>
  {:else if payments.length === 0}
    <div class="empty-state">
      <p>لا توجد دفعات بعد.</p>
      <a href="/courses">تصفح الدورات</a>
    </div>
  {:else}
    <div class="payments-list">
      {#each payments as payment}
        <article class="payment-card">
          <div class="payment-header">
            <div>
              <span class={`badge ${statusLabels[payment.status]?.cls ?? ''}`}>
                {statusLabels[payment.status]?.label ?? payment.status}
              </span>
              <span class="payment-date">{formatDate(payment.created_at)}</span>
            </div>
            <span class="payment-method">{payment.method}</span>
          </div>

          <div class="payment-body">
            {#if payment.course}
              <h2>{payment.course.title.ar}</h2>
            {/if}
            <div class="price-row">
              <span>المبلغ المطلوب</span>
              <strong>{formatPrice(payment.amount_due_cents)}</strong>
            </div>
            {#if payment.discount_cents > 0}
              <div class="price-row muted">
                <span>الخصم</span>
                <span>-{formatPrice(payment.discount_cents)}</span>
              </div>
            {/if}
          </div>

          {#if payment.status === 'rejected' && payment.rejection_reason}
            <div class="rejection-box">
              <p><strong>سبب الرفض:</strong> {payment.rejection_reason}</p>
              {#if payment.course}
                <a href={`/courses/${payment.course.slug}/checkout`}>إعادة التقديم ←</a>
              {/if}
            </div>
          {/if}
        </article>
      {/each}
    </div>
  {/if}
</div>

<style>
  .payments-shell { margin: 0 auto; max-width: 900px; padding: 2rem 1.25rem 4rem; }
  .intro { margin-bottom: 2rem; }
  h1 { font-size: 2.2rem; font-weight: 800; margin: 0; color: var(--storm); }
  .lede { color: var(--muted); margin: 0.5rem 0 0; }
  .loading-state { align-items: center; display: flex; flex-direction: column; gap: 1rem; padding: 4rem 0; }
  .spinner { animation: spin 800ms linear infinite; border: 3px solid var(--line); border-radius: 50%; border-top-color: var(--deep-cyan); height: 2.5rem; width: 2.5rem; }
  @keyframes spin { to { transform: rotate(360deg); } }
  .notice { background: var(--card); border: 2px solid var(--line); border-radius: 0.75rem; padding: 1.25rem; }
  .error { border-color: #e8b5b2; color: #8a302b; }
  .empty-state { background: var(--card); border: 2px solid var(--line); border-radius: 0.75rem; padding: 3rem; text-align: center; }
  .empty-state p { color: var(--muted); margin: 0 0 1rem; }
  .empty-state a { color: var(--deep-cyan); font-weight: 700; text-decoration: none; }
  .btn-login-cta {
    display: inline-block;
    margin-top: 1rem;
    padding: 0.65rem 1.6rem;
    background: var(--brand-navy);
    color: #FAF8F5;
    text-decoration: none;
    border-radius: 0.5rem;
    font-weight: 700;
    border: 2px solid var(--brand-navy);
    transition: opacity 150ms ease, background-color 150ms ease;
  }
  .btn-login-cta:hover {
    opacity: 0.95;
    background-color: var(--brand-accent);
    border-color: var(--brand-accent);
    color: #ffffff;
  }
  .payments-list { display: grid; gap: 0.75rem; }
  .payment-card { background: var(--card); border: 2px solid var(--line); border-radius: 0.75rem; padding: 1.25rem; }
  .payment-header { align-items: center; display: flex; justify-content: space-between; margin-bottom: 1rem; }
  .payment-header > div { align-items: center; display: flex; gap: 0.75rem; }
  .badge { border-radius: 9rem; font-size: 0.78rem; font-weight: 700; padding: 0.3rem 0.75rem; }
  .badge-pending { background: #fef3c7; color: #92400e; }
  .badge-approved { background: #d1fae5; color: #065f46; }
  .badge-rejected { background: #fee2e2; color: #991b1b; }
  .badge-cancelled { background: #e5e7eb; color: #374151; }
  .payment-date { color: var(--muted); font-size: 0.82rem; }
  .payment-method { background: var(--paper); border-radius: 0.3rem; color: var(--muted); font-size: 0.78rem; font-weight: 700; padding: 0.25rem 0.6rem; border: 2px solid var(--line); }
  .payment-body h2 { font-size: 1.15rem; font-weight: 800; margin: 0 0 0.75rem; color: var(--storm); }
  .price-row { display: flex; justify-content: space-between; margin-bottom: 0.35rem; }
  .price-row span { color: var(--muted); }
  .muted span { color: var(--muted); font-size: 0.85rem; }
  .rejection-box { background: #fef2f2; border-radius: 0.4rem; margin-top: 1rem; padding: 0.85rem 1rem; border: 2px solid #fecaca; }
  .rejection-box p { color: #8a302b; margin: 0 0 0.5rem; }
  .rejection-box a { color: var(--deep-cyan); font-weight: 700; text-decoration: none; }
  @media (max-width: 760px) { .payments-shell { padding-inline: 1rem; } }
</style>
