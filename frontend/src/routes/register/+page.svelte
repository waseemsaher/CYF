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
  let academicYear = $state('');
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
    if (!academicYear && data.academicYears.length > 0) {
      academicYear = data.academicYears[0].name.ar;
    }
    if (!department && data.departments.length > 0) {
      department = data.departments[0].name.ar;
    }
  });

  async function handleRegister(e: SubmitEvent) {
    e.preventDefault();
    errorType = null;
    customError = '';
    phoneError = '';

    if (password !== passwordConfirmation) {
      errorType = 'passwordMismatch';
      return;
    }

    if (password.length < 8) {
      errorType = 'passwordMinLength';
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
        academic_year: academicYear,
        department,
        telegram_username: telegramUsername ? telegramUsername.replace(/^@/, '') : undefined,
        phone: phone || undefined,
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
          placeholder={$t.auth.register.namePlaceholder}
          autocomplete="name"
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
          placeholder={$t.auth.register.emailPlaceholder}
          autocomplete="email"
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
          <select id="academic_year" bind:value={academicYear} required>
            {#if data.academicYears.length === 0}
              <option value="السنة الأولى">{$t.auth.register.years.first}</option>
              <option value="السنة الثانية">{$t.auth.register.years.second}</option>
            {:else}
              {#each data.academicYears as year}
                <option value={year.name.ar}>
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
            placeholder={$t.auth.register.telegramPlaceholder}
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
            onbeforeinput={(e) => {
              if (e.data && !/^\d+$/.test(e.data)) {
                e.preventDefault();
              }
            }}
            oninput={(e) => {
              const target = e.currentTarget as HTMLInputElement;
              target.value = target.value.replace(/\D/g, '').slice(0, 11);
              phone = target.value;
              phoneError = '';
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
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    width: 100%;
    box-sizing: border-box;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    min-width: 0;
    width: 100%;
    box-sizing: border-box;
  }

  label, .field-label {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--storm);
  }

  input[type="text"],
  input[type="email"],
  input[type="password"],
  input[type="tel"],
  select {
    width: 100%;
    max-width: 100%;
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
  }
</style>
