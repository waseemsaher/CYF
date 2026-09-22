<script lang="ts">
  import type { PageData } from './$types';

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

      const apiBase = 'http://localhost:8000/api/v1';
      const response = await fetch(`${apiBase}/payments`, {
        method: 'POST',
        body: formData,
        credentials: 'include'
      });

      if (!response.ok) {
        const body = await response.json();
        errorMsg = body.message || 'حدث خطأ أثناء إرسال الدفعة.';
        return;
      }

      submitted = true;
    } catch {
      errorMsg = 'تعذر الاتصال بالخادم. حاول مرة أخرى.';
    } finally {
      submitting = false;
    }
  }

  const selectedMethod = $derived(methods.find(m => m.key === method));
</script>

<svelte:head>
  <title>الدفع — {data.course.title.ar} | منصة FCAI</title>
  <meta name="description" content="إتمام عملية الدفع لدورة {data.course.title.ar}" />
</svelte:head>

<main class="checkout-shell">
  <header class="checkout-header">
    <a class="brand" href="/">FCAI <span>COURSES</span></a>
    <a class="back-link" href={`/courses/${data.course.slug}`}>العودة للدورة <span aria-hidden="true">→</span></a>
  </header>

  {#if submitted}
    <section class="success-panel">
      <div class="success-icon" aria-hidden="true">✓</div>
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
</main>

<style>
  :global(body) { margin: 0; background: #f3f7f6; color: #0f282f; font-family: 'IBM Plex Sans Arabic', Tahoma, sans-serif; }
  :global(*) { box-sizing: border-box; }
  .checkout-shell { margin: 0 auto; max-width: 1180px; padding: 1.25rem 1.25rem 4rem; }
  .checkout-header { align-items: center; display: flex; justify-content: space-between; padding: 0.5rem 0 3rem; }
  .brand { color: #0f282f; font-size: 1.05rem; font-weight: 800; letter-spacing: 0.08em; text-decoration: none; }
  .brand span { color: #17777a; font-size: 0.68rem; margin-inline-start: 0.35rem; }
  .back-link { color: #17777a; font-weight: 800; text-decoration: none; }
  .checkout-grid { display: grid; gap: 2rem; grid-template-columns: 1fr 360px; }
  .checkout-form { background: white; border: 1px solid #d9e6e4; border-radius: 0.75rem; padding: 2rem; }
  h1 { font-size: 2rem; margin: 0 0 1.5rem; }
  .error-banner { background: #fef2f2; border: 1px solid #e8b5b2; border-radius: 0.5rem; color: #8a302b; margin-bottom: 1.5rem; padding: 0.85rem 1rem; }
  fieldset { border: 0; margin: 0 0 1.5rem; padding: 0; }
  legend { font-size: 0.95rem; font-weight: 800; margin-bottom: 0.75rem; }
  .method-options { display: grid; gap: 0.5rem; grid-template-columns: repeat(3, 1fr); }
  .method-option { align-items: center; background: #f3f7f6; border: 2px solid transparent; border-radius: 0.5rem; cursor: pointer; display: flex; font-weight: 700; gap: 0.5rem; padding: 0.75rem 1rem; transition: border-color 150ms; }
  .method-option.active { background: #e6fafa; border-color: #17777a; }
  .method-option input { accent-color: #17777a; }
  .method-instructions { background: #f0f9f9; border-radius: 0.35rem; color: #2d5c5f; font-size: 0.88rem; line-height: 1.7; margin-top: 0.75rem; padding: 0.75rem 1rem; }
  .field { display: grid; gap: 0.4rem; margin-bottom: 1.25rem; }
  .field > span { font-size: 0.88rem; font-weight: 700; }
  .required { color: #c2453e; }
  input[type="text"], textarea { background: #f3f7f6; border: 1px solid #d9e6e4; border-radius: 0.4rem; font: inherit; min-height: 2.8rem; padding: 0.6rem 0.85rem; }
  input[type="text"]:focus, textarea:focus { border-color: #17777a; outline: 3px solid rgba(2, 239, 240, 0.25); outline-offset: 1px; }
  .upload-area { background: #f3f7f6; border: 2px dashed #b9d4d2; border-radius: 0.5rem; padding: 1.5rem; position: relative; text-align: center; }
  .upload-area input[type="file"] { cursor: pointer; height: 100%; left: 0; opacity: 0; position: absolute; top: 0; width: 100%; }
  .upload-hint { color: #597078; font-size: 0.85rem; margin: 0; }
  .file-name { color: #17777a; font-weight: 700; margin: 0; }
  .submit-btn { background: #02eff0; border: 0; border-radius: 0.5rem; color: #0f282f; cursor: pointer; font: inherit; font-weight: 900; min-height: 3.2rem; transition: opacity 150ms; width: 100%; }
  .submit-btn:disabled { cursor: not-allowed; opacity: 0.55; }
  .submit-btn:hover:not(:disabled) { opacity: 0.9; }
  .order-summary { align-self: start; background: #0f282f; border-radius: 0.75rem; color: white; padding: 1.5rem; position: sticky; top: 2rem; }
  .order-summary h2 { color: #02eff0; font-size: 0.88rem; font-weight: 900; letter-spacing: 0.04em; margin: 0 0 1.25rem; }
  .summary-course { border-bottom: 1px solid rgba(255,255,255,0.15); margin-bottom: 1rem; padding-bottom: 1rem; }
  .summary-label { color: #9dbfbe; display: block; font-size: 0.78rem; margin-bottom: 0.35rem; }
  .summary-course strong { display: block; font-size: 1.2rem; }
  .summary-row { align-items: center; display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
  .summary-row span { color: #9dbfbe; }
  .discount span:last-child { color: #02eff0; }
  .summary-total { align-items: center; border-top: 1px solid rgba(255,255,255,0.15); display: flex; justify-content: space-between; margin-top: 0.75rem; padding-top: 0.75rem; }
  .summary-total strong { color: #02eff0; font-size: 1.5rem; }

  /* Success */
  .success-panel { background: white; border: 1px solid #d9e6e4; border-radius: 0.75rem; margin: 0 auto; max-width: 580px; padding: 3rem 2rem; text-align: center; }
  .success-icon { background: #e6fafa; border-radius: 50%; color: #17777a; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; height: 4rem; margin-bottom: 1.5rem; width: 4rem; }
  .success-panel h1 { font-size: 1.8rem; margin: 0 0 0.75rem; }
  .success-panel p { color: #49636a; line-height: 1.7; margin: 0 0 0.5rem; }
  .wait-note { background: #f0f9f9; border-radius: 0.35rem; color: #2d5c5f; font-size: 0.9rem; margin-top: 1rem !important; padding: 0.75rem; }
  .success-actions { display: flex; gap: 0.75rem; justify-content: center; margin-top: 1.5rem; }
  .success-actions a { background: #02eff0; border-radius: 0.4rem; color: #0f282f; font-weight: 800; padding: 0.7rem 1.25rem; text-decoration: none; }
  .success-actions .secondary { background: transparent; border: 1px solid #b9d4d2; color: #17777a; }

  @media (max-width: 760px) {
    .checkout-grid { grid-template-columns: 1fr; }
    .method-options { grid-template-columns: 1fr; }
    .order-summary { position: static; }
  }
</style>
