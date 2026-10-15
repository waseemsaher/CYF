<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import { currentUser, isAuthenticated, refreshUser, logout, setStoredUser } from '$lib/api/auth';
  import { getTelegramStatus, generateTelegramLink } from '$lib/api/telegram';
  import { currentLocale } from '$lib/i18n';

  let loading = $state(true);
  let actionLoading = $state(false);
  let checkingStatus = $state(false);
  let deepLink = $state('');
  let errorMsg = $state('');
  let pollInterval: ReturnType<typeof setInterval> | null = null;

  function getTargetRoute(role?: string): string {
    if (role === 'superadmin' || role === 'admin') {
      return '/admin';
    } else if (role === 'teacher') {
      return '/teacher';
    }
    return '/dashboard';
  }

  async function proceedIfLinked() {
    let user = await refreshUser(fetch);
    if (user) {
      user.telegram_is_linked = true;
      setStoredUser(user);
      currentUser.set(user);
    }
    const role = user?.role || (user?.roles && user.roles[0]) || '';
    window.location.href = getTargetRoute(role);
  }

  async function checkStatusNow() {
    try {
      checkingStatus = true;
      errorMsg = '';
      const status = await getTelegramStatus(fetch);
      if (status.is_linked) {
        if (pollInterval) {
          clearInterval(pollInterval);
          pollInterval = null;
        }
        await proceedIfLinked();
      } else {
        errorMsg = $currentLocale === 'en'
          ? 'Telegram account not yet linked. Please open the bot in Telegram and press "Start".'
          : 'لم يتم تأكيد الربط بعد. تأكد من فتح البوت في تليجرام والضغط على زر "Start / ابدأ".';
      }
    } catch (e: unknown) {
      errorMsg = e instanceof Error ? e.message : 'تعذر التحقق من حالة الربط. يرجى المحاولة لاحقاً.';
    } finally {
      checkingStatus = false;
    }
  }

  function startPolling() {
    if (pollInterval) return;
    pollInterval = setInterval(async () => {
      try {
        const status = await getTelegramStatus(fetch);
        if (status.is_linked) {
          if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
          }
          await proceedIfLinked();
        }
      } catch {
        // Transient error, continue polling
      }
    }, 3000);
  }

  async function handleLink() {
    try {
      actionLoading = true;
      errorMsg = '';
      const res = await generateTelegramLink(fetch);
      deepLink = res.deep_link;
      window.open(res.deep_link, '_blank', 'noopener,noreferrer');
    } catch (e: unknown) {
      errorMsg = e instanceof Error ? e.message : 'تعذر إنشاء رابط تليجرام. يرجى المحاولة مرة أخرى.';
    } finally {
      actionLoading = false;
    }
  }

  async function handleLogout() {
    if (pollInterval) {
      clearInterval(pollInterval);
      pollInterval = null;
    }
    await logout(fetch);
    window.location.href = '/login';
  }

  onMount(async () => {
    if (!isAuthenticated()) {
      window.location.href = '/login';
      return;
    }

    const user = await refreshUser(fetch);
    if (!user) {
      window.location.href = '/login';
      return;
    }

    const role = user.role || (user.roles && user.roles[0]) || '';

    if (user.telegram_is_linked === true) {
      window.location.href = getTargetRoute(role);
      return;
    }

    try {
      const status = await getTelegramStatus(fetch);
      if (status.is_linked) {
        await proceedIfLinked();
        return;
      }
    } catch {
      // Continue to show linking screen
    }

    loading = false;
    startPolling();
  });

  onDestroy(() => {
    if (pollInterval) {
      clearInterval(pollInterval);
      pollInterval = null;
    }
  });
</script>

<svelte:head>
  <title>{$currentLocale === 'en' ? 'Link Telegram Account | Codeera' : 'ربط حساب تليجرام | كوديرا'}</title>
</svelte:head>

<div class="auth-guard-container">
  <div class="guard-card">
    <div class="icon-bubble telegram-bubble" aria-hidden="true">
      <svg class="telegram-icon" viewBox="0 0 24 24" width="34" height="34" fill="currentColor">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .37z"/>
      </svg>
    </div>

    <h2 class="guard-title">
      {$currentLocale === 'en' ? 'Link Your Telegram Account' : 'ربط حساب تليجرام مطلوب'}
    </h2>

    <p class="guard-desc">
      {$currentLocale === 'en'
        ? 'To access courses, study materials, and receive notifications, you must link your Telegram account to Codeera.'
        : 'للوصول إلى مقرراتك الدراسية والمحاضرات وتلقي الإشعارات الفورية، يجب ربط حسابك في تليجرام بمنصة كوديرا للمتابعة.'}
    </p>

    {#if errorMsg}
      <div class="error-banner" role="alert">
        <span>{errorMsg}</span>
      </div>
    {/if}

    {#if deepLink}
      <div class="link-hint-box">
        <p class="link-hint-text">
          {$currentLocale === 'en'
            ? 'Link generated! If Telegram did not open automatically, click the button below:'
            : 'تم إنشاء رابط التوجيه. إذا لم يفتح تطبيق تليجرام تلقائياً، اضغط أدناه:'}
        </p>
        <a href={deepLink} target="_blank" rel="noopener noreferrer" class="btn-guard-primary btn-telegram-action">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .37z"/>
          </svg>
          {$currentLocale === 'en' ? 'Open in Telegram App' : 'فتح في تطبيق تليجرام'}
        </a>
      </div>
    {/if}

    <div class="polling-status">
      <div class="spinner-sm" aria-hidden="true"></div>
      <span>
        {$currentLocale === 'en'
          ? 'Waiting for link confirmation in Telegram...'
          : 'في انتظار إتمام الربط عبر تطبيق تليجرام...'}
      </span>
    </div>

    <div class="guard-actions">
      {#if !deepLink}
        <button
          type="button"
          class="btn-guard-primary"
          onclick={handleLink}
          disabled={actionLoading}
        >
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .37z"/>
          </svg>
          {actionLoading
            ? ($currentLocale === 'en' ? 'Generating link...' : 'جاري إنشاء الرابط...')
            : ($currentLocale === 'en' ? 'Open Telegram & Link Account' : 'فتح تليجرام وربط الحساب')}
        </button>
      {/if}
      <button
        type="button"
        class="btn-guard-secondary"
        onclick={checkStatusNow}
        disabled={actionLoading || checkingStatus}
      >
        {checkingStatus
          ? ($currentLocale === 'en' ? 'Checking status...' : 'جاري التحقق من الربط...')
          : ($currentLocale === 'en' ? 'Already linked? Click here to proceed' : 'تم ربط الحساب؟ اضغط للمتابعة')}
      </button>

      <button type="button" class="btn-guard-text" onclick={handleLogout}>
        {$currentLocale === 'en' ? 'Log Out' : 'تسجيل الخروج'}
      </button>
    </div>
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
    box-sizing: border-box;
  }

  .icon-bubble {
    width: 4.5rem;
    height: 4.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .telegram-bubble {
    background: rgba(0, 136, 204, 0.1);
    color: #0088cc;
    border: 2px solid rgba(0, 136, 204, 0.25);
  }

  :global([data-theme='dark']) .telegram-bubble {
    background: rgba(0, 136, 204, 0.2);
    color: #38bdf8;
    border-color: rgba(0, 136, 204, 0.35);
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

  .link-hint-box {
    width: 100%;
    padding: 1rem;
    background: rgba(var(--brand-navy-rgb), 0.05);
    border: 1px solid var(--line);
    border-radius: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    align-items: center;
    box-sizing: border-box;
  }

  .link-hint-text {
    font-size: 0.88rem;
    color: var(--storm);
    margin: 0;
    line-height: 1.5;
  }

  .btn-telegram-action {
    width: 100%;
    max-width: 320px;
  }

  .polling-status {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.88rem;
    color: var(--muted);
    padding: 0.6rem 1rem;
    background: var(--paper);
    border-radius: 0.5rem;
    font-weight: 600;
  }

  .spinner-sm {
    width: 1rem;
    height: 1rem;
    border: 2px solid var(--line);
    border-top-color: #0088cc;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    flex-shrink: 0;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
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
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: opacity 150ms ease, background-color 150ms ease;
  }

  .btn-guard-primary:hover:not(:disabled) {
    opacity: 0.95;
    background-color: var(--brand-accent);
    border-color: var(--brand-accent);
    color: #0a0f1a;
  }

  .btn-guard-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
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

  .btn-guard-text {
    background: transparent;
    border: none;
    color: var(--muted);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    padding: 0.5rem;
    text-decoration: underline;
    transition: color 150ms ease;
  }

  .btn-guard-text:hover {
    color: var(--storm);
  }

  .error-banner {
    background: #fdf2f2;
    border: 2px solid #f8b4b4;
    color: #9b1c1c;
    padding: 0.65rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.88rem;
    width: 100%;
    box-sizing: border-box;
  }

  :global([data-theme='dark']) .error-banner {
    background: rgba(239, 68, 68, 0.12);
    border-color: rgba(239, 68, 68, 0.4);
    color: #fca5a5;
  }
</style>
