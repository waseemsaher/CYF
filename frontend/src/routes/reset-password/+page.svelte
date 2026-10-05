<script lang="ts">
  import { page } from '$app/state';
  import { resetPassword, currentUser } from '$lib/api/auth';
  import { ApiError } from '$lib/api/client';
  import { t, currentLocale } from '$lib/i18n';

  const token = $derived(page.url.searchParams.get('token') || '');
  const email = $derived(page.url.searchParams.get('email') || '');

  let password = $state('');
  let passwordConfirmation = $state('');
  let loading = $state(false);
  let success = $state(false);
  let errorMessage = $state('');
  let isTokenInvalid = $state(false);

  // Field validation errors
  let passwordError = $state('');
  let confirmationError = $state('');

  let hasValidParams = $derived(Boolean(token.trim() && email.trim()));

  function validateForm(): boolean {
    passwordError = '';
    confirmationError = '';
    errorMessage = '';

    let valid = true;

    if (!password) {
      passwordError = $t.auth.resetPassword.errors.required;
      valid = false;
    } else if (password.length < 8) {
      passwordError = $t.auth.resetPassword.errors.passwordMin;
      valid = false;
    }

    if (!passwordConfirmation) {
      confirmationError = $t.auth.resetPassword.errors.required;
      valid = false;
    } else if (password !== passwordConfirmation) {
      confirmationError = $t.auth.resetPassword.errors.passwordMismatch;
      valid = false;
    }

    return valid;
  }

  async function handleReset(e: SubmitEvent) {
    e.preventDefault();

    if (!hasValidParams) {
      isTokenInvalid = true;
      errorMessage = $t.auth.resetPassword.errors.tokenInvalid;
      return;
    }

    if (!validateForm()) {
      return;
    }

    loading = true;
    errorMessage = '';
    isTokenInvalid = false;

    try {
      await resetPassword(fetch, {
        token,
        email,
        password,
        password_confirmation: passwordConfirmation,
      });

      success = true;

      // Automatically redirect to login page after 2.5 seconds
      setTimeout(() => {
        window.location.href = `/login?reset=success&email=${encodeURIComponent(email)}`;
      }, 2500);
    } catch (err: unknown) {
      if (err instanceof ApiError) {
        if (err.status === 429) {
          errorMessage = $t.auth.resetPassword.errors.throttled;
        } else if (err.status === 400) {
          // Token invalid, expired, or email mismatch
          isTokenInvalid = true;
          errorMessage = err.message || $t.auth.resetPassword.errors.tokenInvalid;
        } else if (err.status === 422) {
          const errors = err.data?.errors;
          if (errors?.password) {
            passwordError = Array.isArray(errors.password) ? errors.password[0] : errors.password;
          }
          if (errors?.token) {
            isTokenInvalid = true;
            errorMessage = Array.isArray(errors.token) ? errors.token[0] : errors.token;
          } else {
            errorMessage = err.message || $t.auth.resetPassword.errors.generic;
          }
        } else {
          errorMessage = err.message || $t.auth.resetPassword.errors.generic;
        }
      } else if (err instanceof Error) {
        if (err.message.includes('429') || err.message.toLowerCase().includes('too many')) {
          errorMessage = $t.auth.resetPassword.errors.throttled;
        } else if (err.message.toLowerCase().includes('token') || err.message.toLowerCase().includes('invalid')) {
          isTokenInvalid = true;
          errorMessage = err.message;
        } else {
          errorMessage = err.message || $t.auth.resetPassword.errors.generic;
        }
      } else {
        errorMessage = $t.auth.resetPassword.errors.generic;
      }
    } finally {
      loading = false;
    }
  }
</script>

<svelte:head>
  <title>{$t.auth.resetPassword.metaTitle}</title>
  <meta name="description" content={$t.auth.resetPassword.metaDesc} />
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
    {:else if !hasValidParams || isTokenInvalid}
      <div class="invalid-token-state" role="alert">
        <div class="warning-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>
        <h2>{$t.auth.resetPassword.invalidLinkTitle}</h2>
        <p class="invalid-desc">
          {errorMessage || $t.auth.resetPassword.invalidLinkMessage}
        </p>
        <div class="invalid-actions">
          <a href="/forgot-password" class="btn-submit">
            <span>{$t.auth.resetPassword.requestNewLink}</span>
            <span aria-hidden="true">{$currentLocale === 'ar' ? '←' : '→'}</span>
          </a>
          <a href="/login" class="btn-secondary-link">
            {$t.auth.resetPassword.backToLogin}
          </a>
        </div>
      </div>
    {:else if success}
      <div class="success-state" role="status" aria-live="polite">
        <div class="success-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
        <h2>{$t.auth.resetPassword.successTitle}</h2>
        <p class="success-desc">
          {$t.auth.resetPassword.successMessage}
        </p>
        <p class="redirecting-notice">
          {$t.auth.resetPassword.redirecting}
        </p>
        <div class="success-actions">
          <a href={`/login?reset=success&email=${encodeURIComponent(email)}`} class="btn-submit">
            <span>{$t.auth.resetPassword.backToLogin}</span>
            <span aria-hidden="true">{$currentLocale === 'ar' ? '←' : '→'}</span>
          </a>
        </div>
      </div>
    {:else}
      <div class="auth-header">
        <span class="auth-badge">{$t.auth.resetPassword.badge}</span>
        <h1>{$t.auth.resetPassword.title}</h1>
        <p>{$t.auth.resetPassword.subtitle}</p>
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

      <form class="auth-form" onsubmit={handleReset} novalidate>
        <!-- Account Email Display -->
        <div class="form-group">
          <label for="account-email">{$t.auth.resetPassword.emailLabel}</label>
          <input
            id="account-email"
            type="email"
            value={email}
            readonly
            disabled
            dir="ltr"
            class="readonly-input"
          />
        </div>

        <!-- New Password -->
        <div class="form-group">
          <label for="password">{$t.auth.resetPassword.passwordLabel}</label>
          <input
            id="password"
            type="password"
            bind:value={password}
            required
            dir="ltr"
            placeholder={$t.auth.resetPassword.passwordPlaceholder}
            autocomplete="new-password"
            disabled={loading}
            class:has-error={Boolean(passwordError)}
          />
          {#if passwordError}
            <span class="field-error" role="alert">{passwordError}</span>
          {/if}
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
          <label for="password-confirmation">{$t.auth.resetPassword.confirmPasswordLabel}</label>
          <input
            id="password-confirmation"
            type="password"
            bind:value={passwordConfirmation}
            required
            dir="ltr"
            placeholder={$t.auth.resetPassword.confirmPasswordPlaceholder}
            autocomplete="new-password"
            disabled={loading}
            class:has-error={Boolean(confirmationError)}
          />
          {#if confirmationError}
            <span class="field-error" role="alert">{confirmationError}</span>
          {/if}
        </div>

        <button type="submit" class="btn-submit" disabled={loading}>
          {#if loading}
            <span>{$t.auth.resetPassword.submitting}</span>
          {:else}
            <span>{$t.auth.resetPassword.submit}</span>
            <span aria-hidden="true">{$currentLocale === 'ar' ? '←' : '→'}</span>
          {/if}
        </button>
      </form>

      <div class="auth-footer">
        <p>
          <a href="/login" class="back-link">
            <span aria-hidden="true">{$currentLocale === 'ar' ? '→' : '←'}</span>
            <span>{$t.auth.resetPassword.backToLogin}</span>
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
  .invalid-token-state,
  .success-state {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem 0;
  }

  .user-avatar,
  .warning-icon,
  .success-icon {
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

  .warning-icon {
    background: rgba(239, 68, 68, 0.1);
    border: 2px solid rgba(239, 68, 68, 0.25);
    color: #dc2626;
  }

  :global([data-theme='dark']) .warning-icon {
    background: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.3);
    color: #f87171;
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
  .invalid-token-state h2,
  .success-state h2 {
    font-size: 1.4rem;
    font-weight: 800;
    margin: 0;
    color: var(--storm);
  }

  .already-logged-in p,
  .invalid-desc,
  .success-desc {
    color: var(--muted);
    font-size: 0.95rem;
    margin: 0;
    line-height: 1.6;
  }

  .redirecting-notice {
    font-size: 0.85rem;
    color: var(--deep-cyan);
    font-weight: 700;
    margin: 0.25rem 0 0;
  }

  .already-actions,
  .invalid-actions,
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

  input.readonly-input {
    background: rgba(0, 0, 0, 0.04);
    color: var(--muted);
    cursor: default;
    border-style: dashed;
  }

  :global([data-theme='dark']) input.readonly-input {
    background: rgba(255, 255, 255, 0.04);
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
