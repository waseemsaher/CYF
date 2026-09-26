<script lang="ts">
  import { onMount } from 'svelte';
  import { getTelegramStatus, generateTelegramLink, unlinkTelegram, type TelegramStatus } from '$lib/api/telegram';

  let status: TelegramStatus = $state({ is_linked: false });
  let loading = $state(true);
  let actionLoading = $state(false);
  let deepLink = $state('');
  let expiresAt = $state('');
  let errorMsg = $state('');

  async function loadStatus() {
    try {
      loading = true;
      errorMsg = '';
      status = await getTelegramStatus();
    } catch {
      // If not authenticated or error, ignore
    } finally {
      loading = false;
    }
  }

  async function handleLink() {
    try {
      actionLoading = true;
      errorMsg = '';
      const res = await generateTelegramLink();
      deepLink = res.deep_link;
      expiresAt = res.expires_at;
      // Open telegram in new tab
      window.open(res.deep_link, '_blank');
    } catch (e: any) {
      errorMsg = e?.message || 'تعذر إنشاء رابط التليجرام. يرجى المحاولة مرة أخرى.';
    } finally {
      actionLoading = false;
    }
  }

  async function handleUnlink() {
    if (!confirm('هل أنت متأكد من رغبتك في إلغاء ربط حساب التليجرام؟')) return;
    try {
      actionLoading = true;
      errorMsg = '';
      await unlinkTelegram();
      status = { is_linked: false, telegram_user_id: null, telegram_username: null };
      deepLink = '';
    } catch (e: any) {
      errorMsg = e?.message || 'تعذر إلغاء الربط.';
    } finally {
      actionLoading = false;
    }
  }

  onMount(() => {
    loadStatus();
  });
</script>

<div class="telegram-card">
  <div class="telegram-header">
    <div class="telegram-icon-wrapper" aria-hidden="true">
      <svg class="telegram-icon" viewBox="0 0 24 24" width="24" height="24">
        <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .37z"/>
      </svg>
    </div>
    <div class="telegram-titles">
      <h3 class="telegram-title">ربط حساب تليجرام (Telegram)</h3>
      <p class="telegram-sub">للانضمام تلقائياً إلى مجموعات المقررات وتلقي الإشعارات الفورية</p>
    </div>
  </div>

  {#if loading}
    <div class="loading-placeholder">
      <div class="spinner-sm"></div>
      <span>جاري التحقق من حالة الربط...</span>
    </div>
  {:else if status.is_linked}
    <div class="status-linked-box">
      <div class="linked-info">
        <span class="linked-indicator"></span>
        <span class="linked-text">
          تم ربط الحساب بنجاح:
          <strong>{status.telegram_username ? `@${status.telegram_username}` : `معرّف ${status.telegram_user_id}`}</strong>
        </span>
      </div>
      <button
        type="button"
        onclick={handleUnlink}
        disabled={actionLoading}
        class="btn-unlink"
      >
        {actionLoading ? 'جاري الإلغاء...' : 'إلغاء الربط'}
      </button>
    </div>
  {:else}
    <div class="link-action-body">
      <p class="link-description">
        عند اشتراكك في أي مقرر دراسي وقبول الدفع، يمكنك الانضمام فوراً لمجموعة التليجرام الخاصة به. اربط حسابك الآن مع بوت المنصة ليتعرف عليك النظام تلقائياً عند طلب الانضمام.
      </p>

      {#if deepLink}
        <div class="deep-link-box">
          <p class="deep-link-hint">
            تم توليد رابط الربط بنجاح (صالح لمدة 15 دقيقة). اضغط لفتح تطبيق تليجرام وتأكيد الربط:
          </p>
          <a
            href={deepLink}
            target="_blank"
            rel="noopener noreferrer"
            class="btn-open-telegram"
          >
            <svg class="btn-icon" viewBox="0 0 24 24" width="18" height="18">
              <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .37z"/>
            </svg>
            فتح البوت في تليجرام (@Facultylmsbot)
          </a>
        </div>
      {:else}
        <button
          type="button"
          onclick={handleLink}
          disabled={actionLoading}
          class="btn-start-link"
        >
          <svg class="btn-icon" viewBox="0 0 24 24" width="18" height="18">
            <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .37z"/>
          </svg>
          {actionLoading ? 'جاري إنشاء الرابط...' : 'ربط حساب التليجرام الآن'}
        </button>
      {/if}

      {#if errorMsg}
        <div class="error-banner" role="alert">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
          <span>{errorMsg}</span>
        </div>
      {/if}
    </div>
  {/if}
</div>

<style>
  .telegram-card {
    background: var(--card);
    border: 2px solid var(--line);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(15, 40, 47, 0.04);
  }

  .telegram-header {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    margin-bottom: 1.25rem;
  }

  .telegram-icon-wrapper {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.75rem;
    background: #e7f5fb;
    color: #0088cc;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  :global([data-theme='dark']) .telegram-icon-wrapper {
    background: rgba(0, 136, 204, 0.2);
    color: #38bdf8;
    border: 1px solid rgba(0, 136, 204, 0.35);
  }

  .telegram-icon {
    width: 1.6rem;
    height: 1.6rem;
  }

  .telegram-titles {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
  }

  .telegram-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--storm);
    margin: 0;
  }

  .telegram-sub {
    font-size: 0.82rem;
    color: var(--muted);
    margin: 0;
  }

  .loading-placeholder {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 1rem;
    background: var(--paper);
    border-radius: 0.6rem;
    color: var(--muted);
    font-size: 0.88rem;
    font-weight: 600;
  }

  .spinner-sm {
    width: 1rem;
    height: 1rem;
    border: 2px solid var(--line);
    border-top-color: #0088cc;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .status-linked-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: #ecfdf5;
    border: 2px solid #a7f3d0;
    border-radius: 0.75rem;
    flex-wrap: wrap;
  }

  .linked-info {
    display: flex;
    align-items: center;
    gap: 0.6rem;
  }

  .linked-indicator {
    width: 0.65rem;
    height: 0.65rem;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
  }

  .linked-text {
    font-size: 0.9rem;
    color: #065f46;
  }

  .linked-text strong {
    font-weight: 800;
    color: #047857;
  }

  .btn-unlink {
    background: var(--card);
    color: #b91c1c;
    border: 2px solid #fecaca;
    padding: 0.4rem 0.85rem;
    border-radius: 0.45rem;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: background-color 150ms ease;
  }

  .btn-unlink:hover {
    background: #fee2e2;
  }

  .btn-unlink:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .link-action-body {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .link-description {
    font-size: 0.9rem;
    color: var(--storm);
    line-height: 1.6;
    margin: 0;
  }

  .deep-link-box {
    padding: 1.15rem;
    background: #f0f9ff;
    border: 2px solid #bae6fd;
    border-radius: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .deep-link-hint {
    font-size: 0.85rem;
    color: #0369a1;
    margin: 0;
    font-weight: 600;
  }

  .btn-open-telegram, .btn-start-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.75rem 1.25rem;
    background: #0088cc;
    color: white;
    font-weight: 800;
    font-size: 0.92rem;
    border-radius: 0.6rem;
    border: 2px solid #0077b5;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
    transition: background-color 150ms ease, transform 120ms ease;
    box-sizing: border-box;
  }

  .btn-open-telegram:hover, .btn-start-link:hover {
    background: #0077b5;
    transform: translateY(-1px);
  }

  .btn-start-link:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }

  .btn-icon {
    flex-shrink: 0;
  }

  .error-banner {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #fdf2f2;
    border: 2px solid #f8b4b4;
    color: #9b1c1c;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.88rem;
  }

  :global([data-theme='dark']) .error-banner {
    background: rgba(239, 68, 68, 0.12);
    border-color: rgba(239, 68, 68, 0.4);
    color: #fca5a5;
  }
</style>
