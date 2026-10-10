<script lang="ts">
  import type { PageData } from './$types';
  import { register } from '$lib/api/auth';
  import { ApiError } from '$lib/api/client';
  import { t, currentLocale } from '$lib/i18n';

  let { data }: { data: PageData } = $props();

  let name = $state('');
  let email = $state('');
  let password = $state('');
  let passwordConfirmation = $state('');
  let branch = $state<'azhar_boys' | 'azhar_girls'>('azhar_boys');
  let academicYearId = $state<number | null>(null);
  let department = $state('');
  let telegramUsername = $state('');
  let phone = $state('');
  let phoneError = $state('');

  let loading = $state(false);
  let errorType = $state<'passwordMismatch' | 'passwordMinLength' | 'failed' | 'generic' | 'custom' | null>(null);
  let customError = $state('');

  let errorMessage = $derived.by(() => {
    if (!errorType) return '';
    if (errorType === 'passwordMismatch') return $t.auth.register.errors.passwordMismatch;
    if (errorType === 'passwordMinLength') return $t.auth.register.errors.passwordMinLength;
    if (errorType === 'failed') return $t.auth.register.errors.failed;
    if (errorType === 'generic') return $t.auth.register.errors.generic;
    return customError;
  });

  // Default dropdown selections when loaded
  $effect(() => {
    if (academicYearId === null && data.academicYears.length > 0) {
      academicYearId = data.academicYears[0].id;
    }
    if (!department) {
      department = data.departments.length > 0 ? data.departments[0].name.ar : 'علوم الحاسب';
    }
  });

  function sanitizePhone(raw: string): string {
    const arabicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    let clean = raw;
    for (let i = 0; i < 10; i++) {
      clean = clean.replaceAll(arabicDigits[i], String(i));
      clean = clean.replaceAll(persianDigits[i], String(i));
    }
    clean = clean.replace(/\D/g, '');
    if (clean.startsWith('0020') && clean.length === 14) {
      clean = '0' + clean.slice(4);
    } else if (clean.startsWith('20') && clean.length === 12) {
      clean = '0' + clean.slice(2);
    } else if (clean.startsWith('20') && clean.length === 13) {
      clean = clean.slice(2);
    } else if (clean.length === 10 && ['10', '11', '12', '15'].includes(clean.slice(0, 2))) {
      clean = '0' + clean;
    }
    return clean.slice(0, 11);
  }

  function sanitizeName(raw: string): string {
    return raw.replace(/[^\p{L}\s\-']/gu, '').replace(/\s+/g, ' ');
  }

  function sanitizeEmail(raw: string): string {
    return raw.trim().toLowerCase();
  }

  function sanitizeTelegram(raw: string): string {
    let clean = raw.trim();
    clean = clean.replace(/^https?:\/\/t\.me\//i, '');
    clean = clean.replace(/^@+/, '');
    clean = clean.replace(/[^a-zA-Z0-9_]/g, '');
    return clean.slice(0, 32);
  }

  async function handleRegister(e: SubmitEvent) {
    e.preventDefault();
    errorType = null;
    customError = '';
    phoneError = '';

    name = sanitizeName(name).trim();
    email = sanitizeEmail(email);
    telegramUsername = sanitizeTelegram(telegramUsername);
    phone = sanitizePhone(phone);

    if (name.length < 3) {
      customError = $currentLocale === 'en' ? 'Name must be at least 3 characters.' : 'يجب ألا يقل الاسم عن 3 أحرف.';
      errorType = 'custom';
      return;
    }

    if (password !== passwordConfirmation) {
      errorType = 'passwordMismatch';
      return;
    }

    if (password.length < 8) {
      errorType = 'passwordMinLength';
      return;
    }

    if (!/^01[0125][0-9]{8}$/.test(phone)) {
      phoneError = $currentLocale === 'en'
        ? 'Phone number must be a valid 11-digit Egyptian mobile number (e.g. 01012345678).'
        : 'رقم المحفظة يجب أن يكون رقم موبايل مصري مكوّن من 11 رقمًا (مثال: 01012345678).';
      return;
    }

    if (academicYearId === null) {
      customError = 'يرجى اختيار السنة الدراسية.';
      errorType = 'custom';
      return;
    }

    loading = true;

    try {
      const res = await register(fetch, {
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
        branch,
        academic_year_id: Number(academicYearId),
        department,
        telegram_username: telegramUsername || undefined,
        phone,
      });

      if (res.data?.token) {
        window.location.href = '/link-telegram';
      }
    } catch (err: unknown) {
      if (err instanceof ApiError) {
        if (err.data?.errors?.phone?.[0]) {
          phoneError = err.data.errors.phone[0];
        }
        if (err.data?.errors) {
          const firstKey = Object.keys(err.data.errors)[0];
          customError = err.data.errors[firstKey]?.[0] || err.message;
        } else {
          customError = err.message;
        }
        errorType = 'custom';
      } else if (err instanceof Error && err.message) {
        customError = err.message;
        errorType = 'custom';
      } else {
        errorType = 'failed';
      }
    } finally {
      loading = false;
    }
  }
</script>

<svelte:head>
  <title>{$t.auth.register.metaTitle}</title>
  <meta name="description" content={$t.auth.register.metaDesc} />
</svelte:head>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-header">
      <span class="auth-badge">{$t.auth.register.badge}</span>
      <h1>{$t.auth.register.title}</h1>
      <p>{$t.auth.register.subtitle}</p>
    </div>

    {#if errorMessage}
      <div class="error-banner" role="alert">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
          <line x1="12" y1="9" x2="12" y2="13"></line>
          <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
        <p>{errorMessage}</p>
      </div>
    {/if}

    <form class="auth-form" onsubmit={handleRegister}>
      <div class="form-group">
        <label for="name">{$t.auth.register.nameLabel}</label>
        <input
          id="name"
          type="text"
          bind:value={name}
          required
          maxlength="100"
          placeholder={$t.auth.register.namePlaceholder}
          autocomplete="name"
          oninput={(e) => { name = sanitizeName((e.currentTarget as HTMLInputElement).value); }}
          onblur={() => { name = name.trim(); }}
        />
      </div>

      <div class="form-group">
        <label for="email">{$t.auth.register.emailLabel}</label>
        <input
          id="email"
          type="email"
          bind:value={email}
          required
          dir="ltr"
          maxlength="255"
          placeholder={$t.auth.register.emailPlaceholder}
          autocomplete="email"
          oninput={(e) => { email = sanitizeEmail((e.currentTarget as HTMLInputElement).value); }}
          onblur={() => { email = sanitizeEmail(email); }}
        />
      </div>

      <div class="form-group">
        <label for="password">{$t.auth.register.passwordLabel}</label>
        <input
          id="password"
          type="password"
          bind:value={password}
          required
          dir="ltr"
          placeholder={$t.auth.register.passwordPlaceholder}
          autocomplete="new-password"
        />
      </div>

      <div class="form-group">
        <label for="password_confirmation">{$t.auth.register.confirmPasswordLabel}</label>
        <input
          id="password_confirmation"
          type="password"
          bind:value={passwordConfirmation}
          required
          dir="ltr"
          placeholder={$t.auth.register.confirmPasswordPlaceholder}
          autocomplete="new-password"
        />
      </div>

      <div class="form-group">
        <span class="field-label">{$t.auth.register.branchLabel}</span>
        <div class="radio-toggle" role="radiogroup" aria-label={$t.auth.register.branchLabel}>
          <label class="radio-label" class:selected={branch === 'azhar_boys'}>
            <input
              type="radio"
              name="branch"
              value="azhar_boys"
              bind:group={branch}
            />
            <span>{$t.auth.register.branchBoys}</span>
          </label>
          <label class="radio-label" class:selected={branch === 'azhar_girls'}>
            <input
              type="radio"
              name="branch"
              value="azhar_girls"
              bind:group={branch}
            />
            <span>{$t.auth.register.branchGirls}</span>
          </label>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="academic_year">{$t.auth.register.academicYearLabel}</label>
          <select id="academic_year" bind:value={academicYearId} required>
            {#if data.academicYears.length === 0}
              <option value={null}>{$t.auth.register.years.first}</option>
            {:else}
              {#each data.academicYears as year}
                <option value={year.id}>
                  {$currentLocale === 'en' ? (year.name.en || year.name.ar) : (year.name.ar + (year.name.en ? ` (${year.name.en})` : ''))}
                </option>
              {/each}
            {/if}
          </select>
        </div>

        <div class="form-group">
          <label for="department">{$t.auth.register.departmentLabel}</label>
          <select id="department" bind:value={department} required>
            {#if data.departments.length === 0}
              <option value="علوم الحاسب">{$t.auth.register.departments.cs}</option>
              <option value="الأمن السيبراني">{$t.auth.register.departments.cy}</option>
              <option value="علم البيانات">{$t.auth.register.departments.ds}</option>
              <option value="الذكاء الاصطناعي">{$t.auth.register.departments.ai}</option>
            {:else}
              {#each data.departments as dept}
                <option value={dept.name.ar}>
                  {$currentLocale === 'en' ? (dept.name.en || dept.name.ar) : (dept.name.ar + (dept.name.en ? ` (${dept.name.en})` : ''))}
                </option>
              {/each}
            {/if}
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="telegram">{$t.auth.register.telegramLabel}</label>
          <input
            id="telegram"
            type="text"
            bind:value={telegramUsername}
            dir="ltr"
            maxlength="32"
            placeholder={$t.auth.register.telegramPlaceholder}
            oninput={(e) => { telegramUsername = sanitizeTelegram((e.currentTarget as HTMLInputElement).value); }}
            onblur={() => { telegramUsername = sanitizeTelegram(telegramUsername); }}
          />
        </div>

        <div class="form-group">
          <label for="phone">{$t.auth.register.phoneLabel}</label>
          <input
            id="phone"
            type="tel"
            bind:value={phone}
            required
            maxlength="11"
            inputmode="numeric"
            dir="ltr"
            class:has-error={!!phoneError}
            placeholder={$t.auth.register.phonePlaceholder}
            oninput={(e) => {
              phone = sanitizePhone((e.currentTarget as HTMLInputElement).value);
              phoneError = '';
            }}
            onblur={() => {
              phone = sanitizePhone(phone);
              if (phone && !/^01[0125][0-9]{8}$/.test(phone)) {
                phoneError = $currentLocale === 'en'
                  ? 'Phone number must be a valid 11-digit Egyptian mobile number (e.g. 01012345678).'
                  : 'رقم المحفظة يجب أن يكون رقم موبايل مصري مكوّن من 11 رقمًا (مثال: 01012345678).';
              } else {
                phoneError = '';
              }
            }}
          />
          {#if phoneError}
            <span class="field-error" role="alert">{phoneError}</span>
          {/if}
        </div>
      </div>

      <button type="submit" class="btn-submit" disabled={loading}>
        {#if loading}
          <span>{$t.auth.register.submitting}</span>
        {:else}
          <span>{$t.auth.register.submit}</span>
          <span aria-hidden="true">{$currentLocale === 'ar' ? '←' : '→'}</span>
        {/if}
      </button>
    </form>

    <div class="auth-footer">
      <p>
        {$t.auth.register.hasAccount}
        <a href="/login">{$t.auth.register.loginLink}</a>
      </p>
    </div>
  </div>
</div>

<style>
  .auth-page {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 3rem 1.25rem 5rem;
    min-height: calc(100vh - 180px);
  }

  .auth-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    padding: clamp(1.75rem, 5vw, 2.75rem);
    width: 100%;
    max-width: 620px;
    box-shadow: 0 10px 30px -10px rgba(15, 40, 47, 0.08);
    box-sizing: border-box;
  }

  .auth-header {
    text-align: center;
    margin-bottom: 2rem;
  }

  .auth-badge {
    display: inline-block;
    color: var(--deep-cyan);
    font-size: 0.8rem;
    font-weight: 800;
    background: rgba(var(--brand-navy-rgb), 0.08);
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    border: 1px solid rgba(var(--brand-navy-rgb), 0.2);
    margin-bottom: 0.75rem;
  }

  h1 {
    font-size: 1.85rem;
    font-weight: 900;
    color: var(--storm);
    margin: 0 0 0.5rem;
  }

  .auth-header p {
    color: var(--muted);
    font-size: 0.95rem;
    line-height: 1.6;
    margin: 0;
  }

  .error-banner {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    background: #fff5f5;
    border: 2px solid #feb2b2;
    color: #c53030;
    padding: 0.85rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
  }

  .error-banner p {
    margin: 0;
  }

  .auth-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    width: 100%;
    box-sizing: border-box;
    align-items: end;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    gap: 0.4rem;
    min-width: 0;
    width: 100%;
    box-sizing: border-box;
  }

  label, .field-label {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--storm);
    display: flex;
    align-items: flex-end;
    min-height: 2.4rem;
  }

  input[type="text"],
  input[type="email"],
  input[type="password"],
  input[type="tel"],
  select {
    width: 100%;
    max-width: 100%;
    height: 48px;
    min-height: 48px;
    box-sizing: border-box;
    border: 2px solid var(--line);
    border-radius: 0.5rem;
    padding: 0.75rem 0.9rem;
    font-size: 0.95rem;
    font-family: inherit;
    background: var(--paper);
    color: var(--storm);
    transition: border-color 150ms ease, box-shadow 150ms ease;
  }

  input:focus,
  select:focus {
    border-color: var(--deep-cyan);
    outline: none;
    box-shadow: 0 0 0 3px rgba(var(--brand-navy-rgb), 0.15);
  }

  input.has-error {
    border-color: #ef4444;
  }

  input.has-error:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
  }

  .field-error {
    font-size: 0.82rem;
    color: #dc2626;
    font-weight: 600;
  }

  :global([data-theme='dark']) .field-error {
    color: #f87171;
  }

  .radio-toggle {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
  }

  .radio-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.65rem;
    border: 2px solid var(--line);
    border-radius: 0.5rem;
    cursor: pointer;
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--muted);
    transition: border-color 150ms ease, background 150ms ease, color 150ms ease;
  }

  .radio-label input[type="radio"] {
    margin: 0;
    accent-color: var(--deep-cyan);
  }

  .radio-label.selected {
    border-color: var(--deep-cyan);
    background: rgba(var(--brand-navy-rgb), 0.08);
    color: var(--deep-cyan);
  }

  .btn-submit {
    background: var(--brand-navy);
    color: #FAF8F5;
    border: none;
    font-weight: 800;
    font-size: 1.05rem;
    padding: 0.9rem;
    border-radius: 0.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 0.75rem;
    transition: opacity 150ms ease, transform 150ms ease, background-color 150ms ease;
  }

  .btn-submit:hover:not(:disabled) {
    transform: translateY(-2px);
    opacity: 0.95;
    background-color: #1E2B4D;
  }

  .btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .auth-footer {
    text-align: center;
    border-top: 1px solid var(--line);
    margin-top: 2rem;
    padding-top: 1.25rem;
  }

  .auth-footer p {
    color: var(--muted);
    font-size: 0.9rem;
    margin: 0;
  }

  .auth-footer a {
    color: var(--deep-cyan);
    font-weight: 800;
    text-decoration: none;
    margin-inline-start: 0.25rem;
  }

  .auth-footer a:hover {
    text-decoration: underline;
  }

  @media (max-width: 600px) {
    .form-row {
      grid-template-columns: 1fr;
    }

    label, .field-label {
      min-height: unset;
    }
  }
</style>
