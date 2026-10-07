<script lang="ts">
  import { logout } from '$lib/api/auth';
  import { t } from '$lib/i18n';

  interface Props {
    requiredRole: 'admin' | 'teacher' | 'student';
    isUnauthenticated?: boolean;
    currentRole?: string;
    customError?: string;
    onRetry?: () => void;
  }

  let {
    requiredRole,
    isUnauthenticated = false,
    currentRole = '',
    customError = '',
    onRetry,
  }: Props = $props();

  function getRoleLabel(role?: string): string {
    if (!role) return '';
    const roles = $t.auth.guard.roles;
    if (role === 'admin') return roles.admin;
    if (role === 'teacher') return roles.teacher;
    if (role === 'student') return roles.student;
    if (role === 'superadmin') return roles.superadmin;
    return role;
  }

  function handleLogout() {
    logout();
    window.location.href = '/login';
  }
</script>

<div class="auth-guard-container">
  <div class="guard-card">
    {#if isUnauthenticated}
      <div class="icon-bubble lock-bubble" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
      </div>

      <h2 class="guard-title">{$t.auth.guard.loginRequiredTitle}</h2>
      <p class="guard-desc">
        {$t.auth.guard.loginRequiredDescPrefix}
        <strong>{getRoleLabel(requiredRole)}</strong>
        {$t.auth.guard.loginRequiredDescSuffix}
      </p>

      <div class="guard-actions">
        <a href="/login" class="btn-guard-primary">
          {$t.auth.guard.loginBtn}
        </a>
        <a href="/" class="btn-guard-secondary">
          {$t.auth.guard.homeBtn}
        </a>
      </div>

    {:else if currentRole && currentRole !== requiredRole && currentRole !== 'superadmin'}
      <div class="icon-bubble shield-bubble" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
      </div>

      <h2 class="guard-title">{$t.auth.guard.accessDeniedTitle}</h2>
      <p class="guard-desc">
        {$t.auth.guard.accessDeniedDescCurrent}
        <strong class="role-highlight">({getRoleLabel(currentRole)})</strong>،
        {$t.auth.guard.accessDeniedDescRequired}
        <strong>{getRoleLabel(requiredRole)}</strong>.
      </p>

      <div class="guard-actions">
        <a href="/courses" class="btn-guard-primary">
          {$t.auth.guard.browseCoursesBtn}
        </a>
        <button type="button" class="btn-guard-secondary" onclick={handleLogout}>
          {$t.auth.guard.switchAccountBtn}
        </button>
      </div>

    {:else}
      <div class="icon-bubble alert-bubble" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
      </div>

      <h2 class="guard-title">{$t.auth.guard.cannotAccessTitle}</h2>
      <p class="guard-desc">
        {customError || $t.auth.guard.defaultError}
      </p>

      <div class="guard-actions">
        {#if onRetry}
          <button type="button" class="btn-guard-primary" onclick={onRetry}>
            {$t.auth.guard.retryBtn}
          </button>
        {/if}
        <a href="/" class="btn-guard-secondary">
          {$t.auth.guard.homeBtn}
        </a>
      </div>
    {/if}
  </div>
</div>

<style>
  .auth-guard-container {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1rem;
  }

  .guard-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1.25rem;
    box-shadow: 0 10px 30px -10px rgba(15, 40, 47, 0.08);
    max-width: 520px;
    width: 100%;
    padding: 2.5rem 2rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.25rem;
  }

  .icon-bubble {
    width: 4.5rem;
    height: 4.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .lock-bubble {
    background: rgba(var(--brand-navy-rgb), 0.08);
    color: var(--brand-navy);
    border: 2px solid rgba(var(--brand-navy-rgb), 0.25);
  }

  .shield-bubble {
    background: #fff8eb;
    color: #d97706;
    border: 2px solid #fed7aa;
  }

  .alert-bubble {
    background: #fef2f2;
    color: #dc2626;
    border: 2px solid #fecaca;
  }

  .guard-title {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .guard-desc {
    font-size: 0.98rem;
    line-height: 1.7;
    color: var(--muted);
    margin: 0;
    max-width: 440px;
  }

  .role-highlight {
    color: var(--storm);
    font-weight: 700;
  }

  .guard-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    justify-content: center;
    width: 100%;
    margin-top: 0.5rem;
  }

  .btn-guard-primary {
    background: var(--brand-navy);
    color: #FAF8F5;
    padding: 0.75rem 1.6rem;
    border-radius: 0.6rem;
    font-weight: 700;
    font-size: 0.95rem;
    text-decoration: none;
    border: 2px solid var(--brand-navy);
    cursor: pointer;
    transition: opacity 150ms ease, background-color 150ms ease;
  }

  .btn-guard-primary:hover {
    opacity: 0.95;
    background-color: var(--brand-accent);
    border-color: var(--brand-accent);
    color: #ffffff;
  }

  .btn-guard-secondary {
    background: var(--card);
    color: var(--storm);
    padding: 0.75rem 1.4rem;
    border-radius: 0.6rem;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    border: 2px solid var(--line);
    cursor: pointer;
    transition: background 150ms ease;
  }

  .btn-guard-secondary:hover {
    background: var(--card-hover);
  }
</style>
