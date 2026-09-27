<script lang="ts">
  import { login, currentUser } from '$lib/api/auth';
  import { t, currentLocale } from '$lib/i18n';

  let email = $state('');
  let password = $state('');
  let loading = $state(false);
  let errorType = $state<'required' | 'invalid' | 'generic' | 'custom' | null>(null);
  let customError = $state('');

  let errorMessage = $derived.by(() => {
    if (!errorType) return '';
    if (errorType === 'required') return $t.auth.login.errors.required;
    if (errorType === 'invalid') return $t.auth.login.errors.invalid;
    if (errorType === 'generic') return $t.auth.login.errors.generic;
    return customError;
  });

  function redirectUser(role?: string) {
    let dest = '/dashboard';
    if (role === 'superadmin' || role === 'admin') {
      dest = '/admin';
    } else if (role === 'teacher') {
      dest = '/teacher';
    }
    window.location.href = dest;
  }

  async function handleLogin(e: SubmitEvent) {
    e.preventDefault();
    errorType = null;
    customError = '';

    if (!email || !password) {
      errorType = 'required';
      return;
    }

    loading = true;

    try {
      const res = await login(fetch, { email, password });
      if (res.data?.token) {
        const user = res.data.user;
        const role = user?.role || (user?.roles && user.roles[0]) || '';
        redirectUser(role);
      }
    } catch (err: unknown) {
      if (err instanceof Error && err.message) {
        customError = err.message;
        errorType = 'custom';
      } else {
        errorType = 'invalid';
      }
    } finally {
      loading = false;
    }
  }
</script>

<svelte:head>
  <title>{$t.auth.login.metaTitle}</title>
  <meta name="description" content={$t.auth.login.metaDesc} />
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
          <button type="button" class="btn-submit" onclick={() => redirectUser($currentUser?.role)}>
            <span>{$t.auth.login.goToDashboard}</span>
            <span aria-hidden="true">{$currentLocale === 'ar' ? '←' : '→'}</span>
          </button>
          <a href="/courses" class="btn-secondary-link">{$t.auth.login.browseCourses}</a>
        </div>
      </div>
    {:else}
      <div class="auth-header">
        <span class="auth-badge">{$t.auth.login.badge}</span>
        <h1>{$t.auth.login.title}</h1>
        <p>{$t.auth.login.subtitle}</p>
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

      <form class="auth-form" onsubmit={handleLogin}>
        <div class="form-group">
          <label for="email">{$t.auth.login.emailLabel}</label>
          <input
            id="email"
            type="email"
            bind:value={email}
            required
            dir="ltr"
            placeholder={$t.auth.login.emailPlaceholder}
            autocomplete="email"
          />
        </div>

        <div class="form-group">
          <div class="label-row">
            <label for="password">{$t.auth.login.passwordLabel}</label>
          </div>
          <input
            id="password"
            type="password"
            bind:value={password}
            required
            dir="ltr"
            placeholder={$t.auth.login.passwordPlaceholder}
            autocomplete="current-password"
          />
        </div>

        <button type="submit" class="btn-submit" disabled={loading}>
          {#if loading}
            <span>{$t.auth.login.submitting}</span>
          {:else}
            <span>{$t.auth.login.submit}</span>
            <span aria-hidden="true">{$currentLocale === 'ar' ? '←' : '→'}</span>
          {/if}
        </button>
      </form>

      <div class="auth-footer">
        <p>
          {$t.auth.login.noAccount}
          <a href="/register">{$t.auth.login.registerLink}</a>
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

  .already-logged-in {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
  }

  .user-avatar {
    font-size: 3rem;
    background: rgba(var(--brand-navy-rgb), 0.08);
    width: 5rem;
    height: 5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(var(--brand-navy-rgb), 0.2);
  }

  .already-logged-in h2 {
    font-size: 1.4rem;
    font-weight: 800;
    margin: 0;
    color: var(--storm);
  }

  .already-logged-in p {
    color: var(--muted);
    font-size: 0.95rem;
    margin: 0;
  }

  .already-actions {
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

  .label-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
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
    transition: border-color 150ms ease, box-shadow 150ms ease;
  }

  input:focus {
    border-color: var(--deep-cyan);
    outline: none;
    box-shadow: 0 0 0 3px rgba(var(--brand-navy-rgb), 0.15);
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
    transition: opacity 150ms ease, transform 150ms ease, background-color 150ms ease;
    width: 100%;
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

  .auth-footer a {
    color: var(--deep-cyan);
    font-weight: 800;
    text-decoration: none;
    margin-inline-start: 0.25rem;
  }

  .auth-footer a:hover {
    text-decoration: underline;
  }
</style>
