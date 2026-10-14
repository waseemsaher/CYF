<script lang="ts">
  import type { PageData } from './$types';
  import { submitPayment, type PaymentMethod, type Translation } from '$lib/api/payments';
  import { validateCoupon } from '$lib/api/coupons';
  import { currentLocale, formatPrice } from '$lib/i18n';

  let { data }: { data: PageData } = $props();

  const DEFAULT_METHODS: PaymentMethod[] = [
    {
      key: 'vodafone_cash',
      label: { ar: 'فودافون كاش', en: 'Vodafone Cash' },
      type: 'ewallet',
      number: '01009971545',
      instructions: {
        ar: 'حوّل المبلغ المطلوب على رقم فودافون كاش الموضح، ثم ارفع صورة الإيصال.',
        en: 'Transfer the required amount to the Vodafone Cash number shown, then upload the receipt screenshot.'
      }
    },
    {
      key: 'instapay',
      label: { ar: 'إنستاباي', en: 'InstaPay' },
      type: 'instapay',
      number: '01009971545',
      instructions: {
        ar: 'حوّل المبلغ عبر إنستاباي إلى الرقم الموضح، ثم ارفع صورة الإيصال.',
        en: 'Transfer the required amount via InstaPay to the number shown, then upload the receipt screenshot.'
      }
    },
    {
      key: 'other_ewallet',
      label: { ar: 'محفظة إلكترونية أخرى', en: 'Other e-wallet' },
      type: 'ewallet',
      number: '01009971545',
      instructions: {
        ar: 'حوّل المبلغ المطلوب بأي محفظة إلكترونية، ثم ارفع صورة الإيصال مع رقم المحفظة المرسل منها.',
        en: 'Transfer the required amount using any e-wallet, then upload the receipt screenshot with the sender wallet number.'
      }
    }
  ];

  const methods = $derived<PaymentMethod[]>(
    data.paymentMethods && data.paymentMethods.length > 0
      ? data.paymentMethods
      : DEFAULT_METHODS
  );

  function getLocalized(val: Translation | string | undefined | null, locale: 'ar' | 'en'): string {
    if (!val) return '';
    if (typeof val === 'string') return val;
    return val[locale] || val.ar || val.en || '';
  }

  let method = $state('vodafone_cash');
  let senderIdentifier = $state('');
  let proof: File | null = $state(null);
  let studentNote = $state('');
  let submitting = $state(false);
  let submitted = $state(false);
  let errorMsg = $state('');
  let copied = $state(false);
  let copyTimeout: ReturnType<typeof setTimeout> | null = null;
  let showNumber = $state(false);

  function maskNumber(num?: string): string {
    if (!num) return '•••••••••••';
    if (num.length >= 7) {
      return num.slice(0, 3) + ' •••• ••' + num.slice(-2);
    }
    return '•••••••••••';
  }

  // Coupon state
  let couponInput = $state('');
  let couponLoading = $state(false);
  let couponError = $state('');
  let couponSuccess = $state('');
  let appliedCoupon = $state<{ id: number; code: string; type: 'percent' | 'fixed'; value: number } | null>(null);
  let appliedPricing = $state<{ list_price_cents: number; discount_cents: number; amount_due_cents: number } | null>(null);

  const effectiveListPrice = $derived(appliedPricing ? appliedPricing.list_price_cents : data.course.list_price_cents);
  const effectiveDiscount = $derived(appliedPricing ? appliedPricing.discount_cents : (data.course.discount_cents || 0));
  const effectiveAmountDue = $derived(appliedPricing ? appliedPricing.amount_due_cents : data.course.amount_due_cents);

  async function handleCopyNumber(num: string) {
    if (!num) return;
    try {
      if (typeof navigator !== 'undefined' && navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(num);
      } else if (typeof document !== 'undefined') {
        const input = document.createElement('input');
        input.value = num;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
      }
      copied = true;
      if (copyTimeout) clearTimeout(copyTimeout);
      copyTimeout = setTimeout(() => {
        copied = false;
      }, 2500);
    } catch {
      // Fallback
    }
  }

  function handleFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    if (input.files && input.files[0]) {
      proof = input.files[0];
    }
  }

  async function handleApplyCoupon() {
    const code = couponInput.trim().toUpperCase();
    if (!code) {
      couponError = 'يرجى إدخال رمز الكوبون أولاً.';
      return;
    }

    if (!data.course?.id) {
      couponError = 'تعذر العثور على بيانات الدورة.';
      return;
    }

    couponLoading = true;
    couponError = '';
    couponSuccess = '';

    try {
      const res = await validateCoupon(fetch, data.course.id, code);
      appliedCoupon = res.coupon;
      appliedPricing = res.pricing;
      couponSuccess = res.coupon.type === 'percent'
        ? `تم تطبيق كوبون الخصم (${res.coupon.code}) بنسبة ${res.coupon.value}% بنجاح!`
        : `تم تطبيق كوبون الخصم (${res.coupon.code}) بقيمة ${formatPrice(res.coupon.value, $currentLocale)} بنجاح!`;
    } catch (err: any) {
      appliedCoupon = null;
      appliedPricing = null;
      couponError = err instanceof Error ? err.message : 'كوبون الخصم غير صالح.';
    } finally {
      couponLoading = false;
    }
  }

  function handleRemoveCoupon() {
    appliedCoupon = null;
    appliedPricing = null;
    couponInput = '';
    couponError = '';
    couponSuccess = '';
  }

  async function handleSubmit() {
    const cleanedSender = senderIdentifier
      .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d).toString())
      .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d).toString())
      .trim();

    if (!proof || !cleanedSender) {
      errorMsg = 'يرجى ملء جميع الحقول المطلوبة وإرفاق صورة الإيصال.';
      return;
    }

    if (!/^[0-9]{11}$/.test(cleanedSender)) {
      errorMsg = 'رقم المرسل يجب أن يتكون من 11 رقمًا فقط.';
      return;
    }

    submitting = true;
    errorMsg = '';

    try {
      if (!data.course?.id) {
        errorMsg = 'لم يتم العثور على بيانات الدورة. يرجى إعادة تحميل الصفحة.';
        return;
      }

      const formData = new FormData();
      formData.append('course_id', String(data.course.id));
      const termId = data.currentTerm?.id ? String(data.currentTerm.id) : '1';
      formData.append('term_id', termId);
      formData.append('method', method);
      formData.append('sender_identifier', cleanedSender);
      formData.append('proof', proof);
      if (studentNote.trim()) {
        formData.append('student_note', studentNote);
      }
      if (appliedCoupon) {
        formData.append('coupon_code', appliedCoupon.code);
      }

      await submitPayment(fetch, formData);
      submitted = true;
    } catch (err) {
      errorMsg = err instanceof Error ? err.message : 'حدث خطأ أثناء إرسال الدفعة.';
    } finally {
      submitting = false;
    }
  }

  const selectedMethod = $derived(methods.find(m => m.key === method) || methods[0]);
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

        <!-- Coupon Code Section -->
        <div class="coupon-box">
          <div class="coupon-header">
            <span class="coupon-title">كود الخصم (كوبون)</span>
            <span class="coupon-hint">اختياري</span>
          </div>

          {#if appliedCoupon}
            <div class="coupon-applied-pill">
              <div class="pill-info">
                <span class="pill-tag">مُطبّق</span>
                <strong class="pill-code" dir="ltr">{appliedCoupon.code}</strong>
                <span class="pill-val">
                  {appliedCoupon.type === 'percent'
                    ? `(خصم ${appliedCoupon.value}%)`
                    : `(خصم ${formatPrice(appliedCoupon.value, $currentLocale)})`}
                </span>
              </div>
              <button type="button" class="btn-remove-coupon" onclick={handleRemoveCoupon} aria-label="إزالة الكوبون">
                إلغاء
              </button>
            </div>
            {#if couponSuccess}
              <p class="coupon-success-text">{couponSuccess}</p>
            {/if}
          {:else}
            <div class="coupon-input-row">
              <input
                type="text"
                class="coupon-input"
                placeholder="أدخل كود الكوبون..."
                bind:value={couponInput}
                onkeydown={(e) => { if (e.key === 'Enter') { e.preventDefault(); handleApplyCoupon(); } }}
                dir="ltr"
              />
              <button
                type="button"
                class="btn-apply-coupon"
                disabled={couponLoading || !couponInput.trim()}
                onclick={handleApplyCoupon}
              >
                {couponLoading ? 'التحقق...' : 'تطبيق'}
              </button>
            </div>
            {#if couponError}
              <p class="coupon-error-text" role="alert">{couponError}</p>
            {/if}
          {/if}
        </div>

        <fieldset>
          <legend>طريقة الدفع</legend>
          <div class="method-options">
            {#each methods as m}
              <label class="method-option" class:active={method === m.key}>
                <input type="radio" name="method" value={m.key} bind:group={method} />
                <span class="method-name">{getLocalized(m.label, $currentLocale)}</span>
              </label>
            {/each}
          </div>

          {#if selectedMethod}
            <div class="payment-details-card">
              <div class="details-header">
                <div class="details-label-group">
                  <span class="details-badge">
                    {selectedMethod.type === 'instapay' ? 'بيانات التحويل عبر إنستاباي' : 'رقم التحويل المعتمد'}
                  </span>
                  <span class="details-method-name">
                    {getLocalized(selectedMethod.label, $currentLocale)}
                  </span>
                </div>
                <div class="amount-to-pay">
                  <span class="amount-label">المبلغ المطلوب تحويله:</span>
                  <strong class="amount-val">{formatPrice(effectiveAmountDue, $currentLocale)}</strong>
                </div>
              </div>

              {#if selectedMethod.number}
                <div class="number-display-box">
                  <div class="number-info">
                    <span class="number-label">
                      {selectedMethod.type === 'instapay' ? 'رقم إنستاباي المعتمد للتحويل' : 'رقم فودافون كاش / المحفظة للتحويل'}
                    </span>
                    <div class="number-reveal-row">
                      {#if showNumber}
                        <strong class="number-value revealed" dir="ltr">{selectedMethod.number}</strong>
                      {:else}
                        <span class="number-value masked" dir="ltr" aria-label="رقم التحويل مخفي">
                          {maskNumber(selectedMethod.number)}
                        </span>
                      {/if}
                    </div>
                  </div>

                  <div class="number-actions">
                    <button
                      type="button"
                      class="btn-toggle-number"
                      onclick={() => { showNumber = !showNumber; }}
                      aria-label={showNumber ? 'إخفاء الرقم' : 'إظهار الرقم'}
                    >
                      {#if showNumber}
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                          <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                        <span>إخفاء</span>
                      {:else}
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                          <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <span>إظهار الرقم</span>
                      {/if}
                    </button>

                    <button
                      type="button"
                      class="btn-copy-number"
                      onclick={() => {
                        showNumber = true;
                        handleCopyNumber(selectedMethod?.number || '');
                      }}
                      aria-label="نسخ رقم التحويل"
                    >
                      {#if copied}
                        <span class="copy-success-text">تم النسخ ✓</span>
                      {:else}
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                          <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <span>نسخ الرقم</span>
                      {/if}
                    </button>
                  </div>
                </div>
              {/if}

              {#if selectedMethod.instructions}
                <p class="method-instructions">
                  {getLocalized(selectedMethod.instructions, $currentLocale)}
                </p>
              {/if}
            </div>
          {/if}
        </fieldset>

        <label class="field">
          <span>رقم المحفظة / الهاتف المرسل منه <span class="required">*</span></span>
          <input
            type="tel"
            inputmode="numeric"
            pattern="[0-9]{11}"
            maxlength="11"
            bind:value={senderIdentifier}
            placeholder="أدخل رقمك الذي قمت بالتحويل منه (11 رقمًا، مثال: 01012345678)"
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
          <span>{formatPrice(effectiveListPrice, $currentLocale)}</span>
        </div>
        {#if effectiveDiscount > 0}
          <div class="summary-row discount">
            <span>
              {appliedCoupon ? `الخصم (كوبون ${appliedCoupon.code})` : 'الخصم'}
            </span>
            <span>-{formatPrice(effectiveDiscount, $currentLocale)}</span>
          </div>
        {/if}
        <div class="summary-total">
          <span>المطلوب</span>
          <strong>{formatPrice(effectiveAmountDue, $currentLocale)}</strong>
        </div>
      </aside>
    </section>
  {/if}
</div>

<style>
  .checkout-shell { margin: 0 auto; max-width: 1180px; padding: 2rem 1.25rem 4rem; width: 100%; box-sizing: border-box; overflow-x: hidden; }
  .breadcrumb-nav { margin-bottom: 1.5rem; }
  .back-link { color: var(--deep-cyan); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem; word-break: break-word; }
  .back-link:hover { text-decoration: underline; }
  .checkout-grid { display: grid; gap: 2rem; grid-template-columns: 1fr 360px; min-width: 0; width: 100%; }
  .checkout-form { background: var(--card); border: 2px solid var(--line); border-radius: 0.75rem; padding: 2rem; color: var(--ink); min-width: 0; width: 100%; box-sizing: border-box; overflow: hidden; }
  h1 { font-size: 2rem; margin: 0 0 1.5rem; color: var(--storm); word-break: break-word; }
  .error-banner { background: #fef2f2; border: 2px solid #e8b5b2; border-radius: 0.5rem; color: #8a302b; margin-bottom: 1.5rem; padding: 0.85rem 1rem; word-break: break-word; }
  
  /* Coupon section styles */
  .coupon-box { background: var(--paper); border: 1.5px solid var(--line); border-radius: 0.6rem; padding: 1.25rem; margin-bottom: 1.5rem; }
  .coupon-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem; }
  .coupon-title { font-size: 0.92rem; font-weight: 800; color: var(--storm); }
  .coupon-hint { font-size: 0.78rem; color: var(--muted); background: rgba(0,0,0,0.05); padding: 0.15rem 0.5rem; border-radius: 0.25rem; }
  .coupon-input-row { display: flex; gap: 0.5rem; }
  .coupon-input { flex: 1; text-transform: uppercase; font-family: monospace; letter-spacing: 0.05em; font-weight: 700; }
  .btn-apply-coupon { background: var(--brand-navy); border: 2px solid var(--brand-navy); border-radius: 0.4rem; color: #ffffff; cursor: pointer; font-weight: 800; padding: 0.6rem 1.25rem; white-space: nowrap; transition: background-color 150ms ease, border-color 150ms ease, opacity 150ms ease; }
  .btn-apply-coupon:hover:not(:disabled) { background: var(--brand-accent); border-color: var(--brand-accent); color: #0a0f1a; }
  .btn-apply-coupon:disabled { opacity: 0.5; cursor: not-allowed; }
  :global([data-theme='dark']) .btn-apply-coupon { background: var(--brand-accent) !important; border-color: var(--brand-accent) !important; color: #0a0f1a !important; }
  :global([data-theme='dark']) .btn-apply-coupon:hover:not(:disabled) { background: #ffffff !important; border-color: #ffffff !important; color: #0a0f1a !important; }
  .coupon-applied-pill { display: flex; align-items: center; justify-content: space-between; background: rgba(16, 185, 129, 0.1); border: 1.5px solid #10b981; border-radius: 0.45rem; padding: 0.6rem 0.85rem; }
  .pill-info { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
  .pill-tag { background: #10b981; color: white; font-size: 0.72rem; font-weight: 800; padding: 0.15rem 0.45rem; border-radius: 0.25rem; }
  .pill-code { font-family: monospace; color: var(--storm); font-size: 1.05rem; letter-spacing: 0.04em; }
  .pill-val { font-size: 0.88rem; color: #047857; font-weight: 700; }
  .btn-remove-coupon { background: transparent; border: 1px solid #ef4444; color: #dc2626; border-radius: 0.35rem; font-size: 0.82rem; font-weight: 700; padding: 0.3rem 0.65rem; cursor: pointer; }
  .btn-remove-coupon:hover { background: #fef2f2; }
  .coupon-success-text { color: #047857; font-size: 0.85rem; font-weight: 700; margin: 0.5rem 0 0; }
  .coupon-error-text { color: #b91c1c; font-size: 0.85rem; font-weight: 700; margin: 0.5rem 0 0; }

  fieldset { border: 0; margin: 0 0 1.5rem; padding: 0; min-width: 0; width: 100%; box-sizing: border-box; }
  legend { font-size: 0.95rem; font-weight: 800; margin-bottom: 0.75rem; color: var(--storm); }
  .method-options { display: grid; gap: 0.65rem; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); width: 100%; box-sizing: border-box; }
  .method-option { align-items: center; background: var(--paper); border: 2px solid var(--line); border-radius: 0.5rem; cursor: pointer; display: flex; font-weight: 700; gap: 0.65rem; padding: 0.85rem 1.1rem; transition: border-color 150ms; color: var(--storm); min-width: 0; box-sizing: border-box; word-break: break-word; overflow-wrap: break-word; }
  .method-option.active { background: rgba(var(--brand-navy-rgb), 0.08); border-color: var(--deep-cyan); }
  .method-option input { accent-color: var(--deep-cyan); flex-shrink: 0; margin: 0; }
  .method-name { font-weight: 800; font-size: 0.95rem; color: var(--storm); }

  .payment-details-card {
    background: var(--paper);
    border: 2px solid var(--deep-cyan);
    border-radius: 0.65rem;
    padding: 1.25rem;
    margin-top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    box-sizing: border-box;
    width: 100%;
  }
  .details-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
    border-bottom: 1px dashed var(--line);
    padding-bottom: 0.75rem;
  }
  .details-label-group { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
  .details-badge {
    background: rgba(var(--brand-navy-rgb), 0.12);
    color: var(--deep-cyan);
    font-size: 0.76rem;
    font-weight: 800;
    padding: 0.25rem 0.6rem;
    border-radius: 0.3rem;
  }
  .details-method-name { font-weight: 800; font-size: 0.95rem; color: var(--storm); }
  .amount-to-pay { font-size: 0.88rem; color: var(--storm); display: flex; align-items: baseline; gap: 0.35rem; }
  .amount-label { color: var(--muted); }
  .amount-val { color: var(--brand-accent); font-size: 1.2rem; font-weight: 900; }

  .number-display-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    background: var(--card);
    border: 1.5px solid var(--line);
    border-radius: 0.5rem;
    padding: 0.85rem 1.1rem;
    flex-wrap: wrap;
  }
  .number-info { display: flex; flex-direction: column; gap: 0.35rem; min-width: 0; }
  .number-label { font-size: 0.78rem; font-weight: 700; color: var(--muted); }
  .number-reveal-row { display: flex; align-items: center; gap: 0.5rem; min-height: 2rem; }
  .number-value {
    font-size: 1.35rem;
    font-family: monospace;
    font-weight: 800;
    letter-spacing: 0.05em;
    color: var(--storm);
    word-break: break-all;
    user-select: all;
  }
  .number-value.masked {
    color: var(--muted);
    letter-spacing: 0.08em;
    font-size: 1.25rem;
    user-select: none;
    opacity: 0.85;
  }
  .number-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  .btn-toggle-number {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: var(--paper);
    border: 1.5px solid var(--line);
    border-radius: 0.4rem;
    padding: 0.55rem 0.9rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--storm);
    cursor: pointer;
    transition: all 150ms ease;
    white-space: nowrap;
  }
  .btn-toggle-number:hover {
    background: rgba(var(--brand-navy-rgb), 0.08);
    border-color: var(--deep-cyan);
    color: var(--deep-cyan);
  }
  .btn-copy-number {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: var(--paper);
    border: 1.5px solid var(--line);
    border-radius: 0.4rem;
    padding: 0.55rem 1rem;
    font-size: 0.85rem;
    font-weight: 800;
    color: var(--storm);
    cursor: pointer;
    transition: all 150ms ease;
    white-space: nowrap;
  }
  .btn-copy-number:hover {
    background: rgba(var(--brand-navy-rgb), 0.08);
    border-color: var(--deep-cyan);
    color: var(--deep-cyan);
  }
  .copy-success-text {
    color: #059669;
    font-weight: 800;
  }
  .method-instructions {
    background: rgba(0,0,0,0.03);
    border-radius: 0.35rem;
    color: var(--storm);
    font-size: 0.88rem;
    line-height: 1.7;
    margin: 0;
    padding: 0.75rem 1rem;
    border: 1.5px solid var(--line);
    word-break: break-word;
    overflow-wrap: break-word;
  }
  .field { display: grid; gap: 0.4rem; margin-bottom: 1.25rem; min-width: 0; width: 100%; box-sizing: border-box; }
  .field > span { font-size: 0.88rem; font-weight: 700; color: var(--storm); }
  .required { color: #c2453e; }
  input[type="text"], input[type="tel"], textarea { background: var(--paper); border: 2px solid var(--line); border-radius: 0.4rem; font: inherit; min-height: 2.8rem; padding: 0.6rem 0.85rem; color: var(--storm); width: 100%; max-width: 100%; box-sizing: border-box; min-width: 0; }
  input[type="text"]:focus, input[type="tel"]:focus, textarea:focus { border-color: var(--deep-cyan); outline: 3px solid rgba(var(--brand-navy-rgb), 0.25); outline-offset: 1px; }
  .upload-area { background: var(--paper); border: 2px dashed var(--line); border-radius: 0.5rem; padding: 1.5rem; position: relative; text-align: center; width: 100%; max-width: 100%; box-sizing: border-box; overflow: hidden; }
  .upload-area input[type="file"] { cursor: pointer; height: 100%; left: 0; opacity: 0; position: absolute; top: 0; width: 100%; max-width: 100%; }
  .upload-hint { color: var(--muted); font-size: 0.85rem; margin: 0; word-break: break-word; }
  .file-name { color: var(--deep-cyan); font-weight: 700; margin: 0; word-break: break-all; overflow-wrap: anywhere; }
  .submit-btn { background: var(--brand-accent); border: 2px solid var(--brand-accent); border-radius: 0.5rem; color: #ffffff; cursor: pointer; font: inherit; font-weight: 900; min-height: 3.2rem; transition: opacity 150ms; width: 100%; }
  .submit-btn:disabled { cursor: not-allowed; opacity: 0.55; }
  .submit-btn:hover:not(:disabled) { opacity: 0.9; }
  .order-summary { align-self: start; background: #1A1918; border: 2px solid var(--line); border-radius: 0.75rem; color: #FAF8F5; padding: 1.5rem; position: sticky; top: 2rem; min-width: 0; width: 100%; box-sizing: border-box; word-break: break-word; }
  .order-summary h2 { color: var(--brand-accent); font-size: 0.88rem; font-weight: 900; letter-spacing: 0.04em; margin: 0 0 1.25rem; }
  .summary-course { border-bottom: 1px solid rgba(255,255,255,0.15); margin-bottom: 1rem; padding-bottom: 1rem; }
  .summary-label { color: #D5CBC1; display: block; font-size: 0.78rem; margin-bottom: 0.35rem; }
  .summary-course strong { display: block; font-size: 1.2rem; color: white; word-break: break-word; }
  .summary-row { align-items: center; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem; }
  .summary-row span { color: #D5CBC1; }
  .discount span:last-child { color: var(--brand-accent); }
  .summary-total { align-items: center; border-top: 1px solid rgba(255,255,255,0.15); display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; padding-top: 0.75rem; }
  .summary-total strong { color: var(--brand-accent); font-size: 1.5rem; }

  /* Success */
  .success-panel { background: var(--card); border: 2px solid var(--line); border-radius: 0.75rem; margin: 0 auto; max-width: 580px; width: 100%; box-sizing: border-box; padding: 3rem 2rem; text-align: center; }
  .success-icon { background: rgba(var(--brand-navy-rgb), 0.08); border-radius: 50%; color: var(--deep-cyan); display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; height: 4rem; margin-bottom: 1.5rem; width: 4rem; }
  .success-panel h1 { font-size: 1.8rem; margin: 0 0 0.75rem; color: var(--storm); word-break: break-word; }
  .success-panel p { color: var(--muted); line-height: 1.7; margin: 0 0 0.5rem; word-break: break-word; }
  .wait-note { background: var(--paper); border-radius: 0.35rem; color: var(--storm); font-size: 0.9rem; margin-top: 1rem !important; padding: 0.75rem; border: 1.5px solid var(--line); word-break: break-word; }
  .success-actions { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; margin-top: 1.5rem; }
  .success-actions a { background: var(--brand-accent); border-radius: 0.4rem; color: #ffffff; font-weight: 800; padding: 0.7rem 1.25rem; text-decoration: none; border: 2px solid var(--brand-accent); }
  .success-actions .secondary { background: var(--paper); border: 2px solid var(--line); color: var(--storm); }

  @media (max-width: 860px) {
    .checkout-shell { padding: 1.25rem 1rem 3rem; }
    .checkout-grid { grid-template-columns: 1fr; gap: 1.5rem; }
    .checkout-form { padding: 1.25rem 1rem; }
    .method-options { grid-template-columns: 1fr; }
    .order-summary { position: static; padding: 1.25rem 1rem; }
    .success-panel { padding: 2rem 1rem; }
    .success-actions { flex-direction: column; width: 100%; }
    .success-actions a { text-align: center; }
    h1 { font-size: 1.6rem; }
  }
</style>
