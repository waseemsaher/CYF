<script lang="ts">
  import type { PageData } from './$types';
  import { submitPayment } from '$lib/api/payments';

  let { data }: { data: PageData } = $props();

  const formatPrice = (cents: number) => `${(cents / 100).toLocaleString('ar-EG')} جنيه`;

  let method = $state('vodafone_cash');
  let senderIdentifier = $state('');
  let proof: File | null = $state(null);
  let studentNote = $state('');
  let submitting = $state(false);
  let submitted = $state(false);
  let errorMsg = $state('');

  const methods = [
    { key: 'vodafone_cash', label: 'فودافون كاش', instructions: 'حوّل المبلغ المطلوب على رقم فودافون كاش، ثم ارفع صورة الإيصال.' },
    { key: 'instapay', label: 'إنستاباي', instructions: 'حوّل المبلغ عبر إنستاباي إلى الحساب الموضح، ثم ارفع صورة الإيصال.' },
    { key: 'other_ewallet', label: 'محفظة إلكترونية أخرى', instructions: 'حوّل المبلغ بأي محفظة إلكترونية وارفع صورة الإيصال مع رقم المحفظة.' }
  ];

  function handleFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    if (input.files && input.files[0]) {
      proof = input.files[0];
    }
  }

  async function handleSubmit() {
    if (!proof || !senderIdentifier.trim()) {
      errorMsg = 'يرجى ملء جميع الحقول المطلوبة وإرفاق صورة الإيصال.';
      return;
    }

    submitting = true;
    errorMsg = '';

    try {
      const formData = new FormData();
      formData.append('course_id', String(data.course.id));
      formData.append('term_id', '1'); // TODO: use current term from API
      formData.append('method', method);
      formData.append('sender_identifier', senderIdentifier);
      formData.append('proof', proof);
      if (studentNote.trim()) {
        formData.append('student_note', studentNote);
      }

      await submitPayment(fetch, formData);
      submitted = true;
    } catch (err) {
      errorMsg = err instanceof Error ? err.message : 'حدث خطأ أثناء إرسال الدفعة.';
    } finally {
      submitting = false;
    }
  }

  const selectedMethod = $derived(methods.find(m => m.key === method));
</script>

<svelte:head>
  <title>الدفع — {data.course.title.ar} | منصة Codeera</title>
  <meta name="description" content="إتمام عملية الدفع لدورة {data.course.title.ar}" />
</svelte:head>

<div class="checkout-shell">
  <nav class="breadcrumb-nav" aria-label="مسار التنقل">
    <a class="back-link" href={`/courses/${data.course.slug}`}><span aria-hidden="true">→</span> العودة إلى تفاصيل الدورة</a>
  </nav>

  {#if submitted}
    <section class="success-panel">
      <div class="success-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
      <h1>تم إرسال الدفعة بنجاح</h1>
      <p>دفعتك قيد المراجعة الآن. سيتم إخطارك فور الموافقة عليها.</p>
      <p class="wait-note">عادةً ما تتم المراجعة خلال فترة قصيرة.</p>
      <div class="success-actions">
        <a href="/payments">عرض دفعاتي</a>
        <a href="/courses" class="secondary">تصفح الدورات</a>
      </div>
    </section>
  {:else}
    <section class="checkout-grid">
      <div class="checkout-form">
        <h1>إتمام الدفع</h1>

        {#if errorMsg}
          <div class="error-banner" role="alert">{errorMsg}</div>
        {/if}

        <fieldset>
          <legend>طريقة الدفع</legend>
          <div class="method-options">
            {#each methods as m}
              <label class="method-option" class:active={method === m.key}>
                <input type="radio" name="method" value={m.key} bind:group={method} />
                <span>{m.label}</span>
              </label>
            {/each}
          </div>
          {#if selectedMethod}
            <p class="method-instructions">{selectedMethod.instructions}</p>
          {/if}
        </fieldset>

        <label class="field">
          <span>رقم / حساب المرسل <span class="required">*</span></span>
          <input
            type="text"
            bind:value={senderIdentifier}
            placeholder="أدخل رقم المحفظة أو حساب إنستاباي"
            required
          />
        </label>

        <label class="field">
          <span>صورة إثبات الدفع <span class="required">*</span></span>
          <div class="upload-area">
            <input type="file" accept="image/jpeg,image/png,image/webp" onchange={handleFileChange} required />
            {#if proof}
              <p class="file-name">{proof.name}</p>
            {:else}
              <p class="upload-hint">اسحب الصورة أو انقر للرفع (JPG, PNG, WebP — حد أقصى 5 ميجا)</p>
            {/if}
          </div>
        </label>

        <label class="field">
          <span>ملاحظة (اختياري)</span>
          <textarea bind:value={studentNote} rows="3" placeholder="أي ملاحظة تريد إرسالها للمراجع"></textarea>
        </label>

        <button type="button" onclick={handleSubmit} disabled={submitting} class="submit-btn">
          {submitting ? 'جاري الإرسال...' : 'إرسال الدفعة'}
        </button>
      </div>

      <aside class="order-summary">
        <h2>ملخص الطلب</h2>
        <div class="summary-course">
          <span class="summary-label">الدورة</span>
          <strong>{data.course.title.ar}</strong>
        </div>
        <div class="summary-row">
          <span>السعر الأصلي</span>
          <span>{formatPrice(data.course.list_price_cents)}</span>
        </div>
        {#if data.course.discount_cents > 0}
          <div class="summary-row discount">
            <span>الخصم</span>
            <span>-{formatPrice(data.course.discount_cents)}</span>
          </div>
        {/if}
        <div class="summary-total">
          <span>المطلوب</span>
          <strong>{formatPrice(data.course.amount_due_cents)}</strong>
        </div>
      </aside>
    </section>
  {/if}
</div>

<style>
  .checkout-shell { margin: 0 auto; max-width: 1180px; padding: 2rem 1.25rem 4rem; }
  .breadcrumb-nav { margin-bottom: 1.5rem; }
  .back-link { color: var(--deep-cyan); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem; }
  .back-link:hover { text-decoration: underline; }
  .checkout-grid { display: grid; gap: 2rem; grid-template-columns: 1fr 360px; }
  .checkout-form { background: var(--card); border: 2px solid var(--line); border-radius: 0.75rem; padding: 2rem; color: var(--ink); }
  h1 { font-size: 2rem; margin: 0 0 1.5rem; color: var(--storm); }
  .error-banner { background: #fef2f2; border: 2px solid #e8b5b2; border-radius: 0.5rem; color: #8a302b; margin-bottom: 1.5rem; padding: 0.85rem 1rem; }
  fieldset { border: 0; margin: 0 0 1.5rem; padding: 0; }
  legend { font-size: 0.95rem; font-weight: 800; margin-bottom: 0.75rem; color: var(--storm); }
  .method-options { display: grid; gap: 0.5rem; grid-template-columns: repeat(3, 1fr); }
  .method-option { align-items: center; background: var(--paper); border: 2px solid var(--line); border-radius: 0.5rem; cursor: pointer; display: flex; font-weight: 700; gap: 0.5rem; padding: 0.75rem 1rem; transition: border-color 150ms; color: var(--storm); }
  .method-option.active { background: rgba(var(--brand-navy-rgb), 0.08); border-color: var(--deep-cyan); }
  .method-option input { accent-color: var(--deep-cyan); }
  .method-instructions { background: var(--paper); border-radius: 0.35rem; color: var(--storm); font-size: 0.88rem; line-height: 1.7; margin-top: 0.75rem; padding: 0.75rem 1rem; border: 1.5px solid var(--line); }
  .field { display: grid; gap: 0.4rem; margin-bottom: 1.25rem; }
  .field > span { font-size: 0.88rem; font-weight: 700; color: var(--storm); }
  .required { color: #c2453e; }
  input[type="text"], textarea { background: var(--paper); border: 2px solid var(--line); border-radius: 0.4rem; font: inherit; min-height: 2.8rem; padding: 0.6rem 0.85rem; color: var(--storm); }
  input[type="text"]:focus, textarea:focus { border-color: var(--deep-cyan); outline: 3px solid rgba(var(--brand-navy-rgb), 0.25); outline-offset: 1px; }
  .upload-area { background: var(--paper); border: 2px dashed var(--line); border-radius: 0.5rem; padding: 1.5rem; position: relative; text-align: center; }
  .upload-area input[type="file"] { cursor: pointer; height: 100%; left: 0; opacity: 0; position: absolute; top: 0; width: 100%; }
  .upload-hint { color: var(--muted); font-size: 0.85rem; margin: 0; }
  .file-name { color: var(--deep-cyan); font-weight: 700; margin: 0; }
  .submit-btn { background: var(--brand-accent); border: 2px solid var(--brand-accent); border-radius: 0.5rem; color: #ffffff; cursor: pointer; font: inherit; font-weight: 900; min-height: 3.2rem; transition: opacity 150ms; width: 100%; }
  .submit-btn:disabled { cursor: not-allowed; opacity: 0.55; }
  .submit-btn:hover:not(:disabled) { opacity: 0.9; }
  .order-summary { align-self: start; background: #1A1918; border: 2px solid var(--line); border-radius: 0.75rem; color: #FAF8F5; padding: 1.5rem; position: sticky; top: 2rem; }
  .order-summary h2 { color: var(--brand-accent); font-size: 0.88rem; font-weight: 900; letter-spacing: 0.04em; margin: 0 0 1.25rem; }
  .summary-course { border-bottom: 1px solid rgba(255,255,255,0.15); margin-bottom: 1rem; padding-bottom: 1rem; }
  .summary-label { color: #D5CBC1; display: block; font-size: 0.78rem; margin-bottom: 0.35rem; }
  .summary-course strong { display: block; font-size: 1.2rem; color: white; }
  .summary-row { align-items: center; display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
  .summary-row span { color: #D5CBC1; }
  .discount span:last-child { color: var(--brand-accent); }
  .summary-total { align-items: center; border-top: 1px solid rgba(255,255,255,0.15); display: flex; justify-content: space-between; margin-top: 0.75rem; padding-top: 0.75rem; }
  .summary-total strong { color: var(--brand-accent); font-size: 1.5rem; }

  /* Success */
  .success-panel { background: var(--card); border: 2px solid var(--line); border-radius: 0.75rem; margin: 0 auto; max-width: 580px; padding: 3rem 2rem; text-align: center; }
  .success-icon { background: rgba(var(--brand-navy-rgb), 0.08); border-radius: 50%; color: var(--deep-cyan); display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; height: 4rem; margin-bottom: 1.5rem; width: 4rem; }
  .success-panel h1 { font-size: 1.8rem; margin: 0 0 0.75rem; color: var(--storm); }
  .success-panel p { color: var(--muted); line-height: 1.7; margin: 0 0 0.5rem; }
  .wait-note { background: var(--paper); border-radius: 0.35rem; color: var(--storm); font-size: 0.9rem; margin-top: 1rem !important; padding: 0.75rem; border: 1.5px solid var(--line); }
  .success-actions { display: flex; gap: 0.75rem; justify-content: center; margin-top: 1.5rem; }
  .success-actions a { background: var(--brand-accent); border-radius: 0.4rem; color: #ffffff; font-weight: 800; padding: 0.7rem 1.25rem; text-decoration: none; border: 2px solid var(--brand-accent); }
  .success-actions .secondary { background: var(--paper); border: 2px solid var(--line); color: var(--storm); }

  @media (max-width: 760px) {
    .checkout-grid { grid-template-columns: 1fr; }
    .method-options { grid-template-columns: 1fr; }
    .order-summary { position: static; }
  }
</style>
