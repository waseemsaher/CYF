<script lang="ts">
  import { forgotPassword, currentUser } from '$lib/api/auth';
  import { ApiError } from '$lib/api/client';
  import { t, currentLocale } from '$lib/i18n';

  let email = $state('');
  let loading = $state(false);
  let submitted = $state(false);
  let errorMessage = $state('');

  function validateEmail(val: string): boolean {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(val);
  }

  async function handleSubmit(e: SubmitEvent) {
    e.preventDefault();
    errorMessage = '';

    const trimmedEmail = email.trim();
    if (!trimmedEmail) {
      errorMessage = $t.auth.forgotPassword.errors.required;
      return;
    }

    if (!validateEmail(trimmedEmail)) {
      errorMessage = $t.auth.forgotPassword.errors.invalidEmail;
      return;
    }

    loading = true;

    try {
      await forgotPassword(fetch, trimmedEmail);
      // Always transition to success state to protect against account enumeration
      submitted = true;
    } catch (err: unknown) {
      if (err instanceof ApiError) {
        if (err.status === 429) {
          errorMessage = $t.auth.forgotPassword.errors.throttled;
        } else if (err.status === 422) {
          const validationMsg = err.data?.errors?.email?.[0] || err.message;
          errorMessage = validationMsg || $t.auth.forgotPassword.errors.invalidEmail;
        } else {
          errorMessage = err.message || $t.auth.forgotPassword.errors.generic;
        }
      } else if (err instanceof Error) {
        if (err.message.includes('429') || err.message.toLowerCase().includes('too many')) {
          errorMessage = $t.auth.forgotPassword.errors.throttled;
        } else {
          errorMessage = err.message || $t.auth.forgotPassword.errors.generic;
        }
      } else {
        errorMessage = $t.auth.forgotPassword.errors.generic;
      }
    } finally {
      loading = false;
    }
  }

  function handleReset() {
    submitted = false;
    errorMessage = '';
  }
</script>

<svelte:head>
  <title>{$t.auth.forgotPassword.metaTitle}</title>
  <meta name="description" content={$t.auth.forgotPassword.metaDesc} />
</svelte:head>

<div class="auth-page">
  <div class="auth-card">
    {#if $currentUser}
      <div class="already-logged-in">
        <div class="user-avatar" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </div>
        <h2>{$t.auth.login.alreadyLoggedInTitle}</h2>
        <p>{$t.auth.login.alreadyLoggedInWelcome} <strong>{$currentUser.name}</strong> ({$currentUser.email})</p>
        <div class="already-actions">
          <a href="/dashboard" class="btn-submit">
            <span>{$t.auth.login.goToDashboard}</span>
            <span aria-hidden="true">{$currentLocale === 'ar' ? '←' : '→'}</span>
          </a>
          <a href="/courses" class="btn-secondary-link">{$t.auth.login.browseCourses}</a>
        </div>
      </div>
    {:else if submitted}
      <div class="success-state" role="status" aria-live="polite">
        <div class="success-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
        <h2>{$t.auth.forgotPassword.successTitle}</h2>
        <p class="success-desc">
          {$t.auth.forgotPassword.successMessage}
        </p>
        <p class="resend-note">
          {$t.auth.forgotPassword.resendNote}
        </p>

        <div class="success-actions">
          <a href="/login" class="btn-submit">
            <span>{$t.auth.forgotPassword.backToLogin}</span>
            <span aria-hidden="true">{$currentLocale === 'ar' ? '←' : '→'}</span>
          </a>
          <button type="button" class="btn-link" onclick={handleReset}>
            {$currentLocale === 'ar' ? 'إرسال بريد إلكتروني آخر' : 'Send to another email'}
          </button>
        </div>
      </div>
    {:else}
      <div class="auth-header">
        <span class="auth-badge">{$t.auth.forgotPassword.badge}</span>
        <h1>{$t.auth.forgotPassword.title}</h1>
        <p>{$t.auth.forgotPassword.subtitle}</p>
      </div>

      {#if errorMessage}
        <div class="error-banner" role="alert" aria-live="assertive">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
          <p>{errorMessage}</p>
        </div>
      {/if}

      <form class="auth-form" onsubmit={handleSubmit} novalidate>
        <div class="form-group">
          <label for="email">{$t.auth.forgotPassword.emailLabel}</label>
          <input
            id="email"
            type="email"
            bind:value={email}
            required
            dir="ltr"
            placeholder={$t.auth.forgotPassword.emailPlaceholder}
            autocomplete="email"
            disabled={loading}
          />
        </div>

        <button type="submit" class="btn-submit" disabled={loading}>
          {#if loading}
            <span>{$t.auth.forgotPassword.submitting}</span>
          {:else}
            <span>{$t.auth.forgotPassword.submit}</span>
            <span aria-hidden="true">{$currentLocale === 'ar' ? '←' : '→'}</span>
          {/if}
        </button>
      </form>

      <div class="auth-footer">
        <p>
          <a href="/login" class="back-link">
            <span aria-hidden="true">{$currentLocale === 'ar' ? '→' : '←'}</span>
            <span>{$t.auth.forgotPassword.backToLogin}</span>
          </a>
        </p>
      </div>
    {/if}
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
    padding: clamp(2rem, 5vw, 3rem);
    width: 100%;
    max-width: 480px;
    box-shadow: 0 10px 30px -10px rgba(15, 40, 47, 0.08);
    box-sizing: border-box;
  }

  .already-logged-in,
  .success-state {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem 0;
  }

  .user-avatar,
  .success-icon {
    font-size: 3rem;
    width: 5rem;
    height: 5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .user-avatar {
    background: rgba(var(--brand-navy-rgb), 0.08);
    border: 2px solid rgba(var(--brand-navy-rgb), 0.2);
    color: var(--brand-navy);
  }

  .success-icon {
    background: rgba(22, 101, 52, 0.1);
    border: 2px solid rgba(22, 101, 52, 0.25);
    color: #166534;
  }

  :global([data-theme='dark']) .success-icon {
    background: rgba(34, 197, 94, 0.15);
    border-color: rgba(34, 197, 94, 0.3);
    color: #4ade80;
  }

  .already-logged-in h2,
  .success-state h2 {
    font-size: 1.4rem;
    font-weight: 800;
    margin: 0;
    color: var(--storm);
  }

  .already-logged-in p,
  .success-desc {
    color: var(--muted);
    font-size: 0.95rem;
    margin: 0;
    line-height: 1.6;
  }

  .resend-note {
    font-size: 0.85rem;
    color: var(--muted);
    background: rgba(0, 0, 0, 0.03);
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    border: 1px dashed var(--line);
    margin: 0.5rem 0 0;
    line-height: 1.5;
  }

  :global([data-theme='dark']) .resend-note {
    background: rgba(255, 255, 255, 0.03);
  }

  .already-actions,
  .success-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    width: 100%;
    margin-top: 1rem;
  }

  .btn-secondary-link {
    color: var(--deep-cyan);
    font-weight: 700;
    text-decoration: none;
    font-size: 0.95rem;
    padding: 0.5rem;
    text-align: center;
  }

  .btn-link {
    background: none;
    border: none;
    color: var(--muted);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    padding: 0.5rem;
    text-decoration: underline;
    transition: color 150ms ease;
  }

  .btn-link:hover {
    color: var(--storm);
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

  .auth-header h1 {
    font-size: 1.85rem;
    font-weight: 900;
    color: var(--storm);
    margin: 0 0 0.5rem;
    letter-spacing: -0.02em;
  }

  .auth-header p {
    color: var(--muted);
    font-size: 0.95rem;
    margin: 0;
    line-height: 1.5;
  }

  .error-banner {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    background: #fdf2f2;
    border: 2px solid #f8b4b4;
    color: #9b1c1c;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
  }

  :global([data-theme='dark']) .error-banner {
    background: rgba(239, 68, 68, 0.12);
    border-color: rgba(239, 68, 68, 0.3);
    color: #fca5a5;
  }

  .error-banner p {
    margin: 0;
  }

  .auth-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    min-width: 0;
    width: 100%;
    box-sizing: border-box;
  }

  label {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--storm);
  }

  input {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    border: 2px solid var(--line);
    border-radius: 0.5rem;
    padding: 0.75rem 0.9rem;
    font-size: 0.95rem;
    font-family: inherit;
    background: var(--card);
    color: var(--storm);
    transition: border-color 150ms ease, box-shadow 150ms ease;
  }

  input:focus {
    border-color: var(--deep-cyan);
    outline: none;
    box-shadow: 0 0 0 3px rgba(var(--brand-navy-rgb), 0.15);
  }

  input:disabled {
    opacity: 0.6;
    cursor: not-allowed;
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
    margin-top: 0.5rem;
    text-decoration: none;
    transition: opacity 150ms ease, transform 150ms ease, background-color 150ms ease;
    width: 100%;
    box-sizing: border-box;
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
    margin-top: 1.5rem;
    padding-top: 1.25rem;
  }

  .auth-footer p {
    color: var(--muted);
    font-size: 0.9rem;
    margin: 0;
  }

  .back-link {
    color: var(--deep-cyan);
    font-weight: 800;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    transition: text-decoration 150ms ease;
  }

  .back-link:hover {
    text-decoration: underline;
  }
</style>

