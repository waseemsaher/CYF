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

<div class="rounded-2xl border border-border bg-card p-6 shadow-sm">
  <div class="flex items-center gap-3 mb-4">
    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500">
      <svg class="h-6 w-6 fill-current" viewBox="0 0 24 24">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .37z"/>
      </svg>
    </div>
    <div>
      <h3 class="font-bold text-foreground">تكامل التليجرام (Telegram)</h3>
      <p class="text-xs text-muted-foreground">للوصول التلقائي إلى مجموعات المواد المقفولة وتلقي الإشعارات</p>
    </div>
  </div>

  {#if loading}
    <div class="animate-pulse h-10 bg-muted rounded-xl"></div>
  {:else if status.is_linked}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
      <div class="flex items-center gap-2">
        <span class="inline-flex h-3 w-3 rounded-full bg-emerald-500"></span>
        <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
          الحساب مربوط: {status.telegram_username ? `@${status.telegram_username}` : `معرّف ${status.telegram_user_id}`}
        </span>
      </div>
      <button
        onclick={handleUnlink}
        disabled={actionLoading}
        class="text-xs font-medium text-destructive hover:underline self-start sm:self-auto"
      >
        إلغاء الربط
      </button>
    </div>
  {:else}
    <div class="space-y-4">
      <p class="text-sm text-foreground/80 leading-relaxed">
        اربط حسابك مع بوت المنصة لتتمكن من الانضمام تلقائياً إلى مجموعات التليجرام الخاصة بالمواد المشترك بها فور قبول طلبك.
      </p>

      {#if deepLink}
        <div class="p-4 rounded-xl bg-sky-500/10 border border-sky-500/20 space-y-3">
          <p class="text-xs font-medium text-sky-700 dark:text-sky-300">
            اضغط على الزر أدناه لفتح التليجرام وإتمام الربط (الرابط صالح لمدة 15 دقيقة):
          </p>
          <a
            href={deepLink}
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl bg-sky-500 text-white font-semibold text-sm hover:bg-sky-600 transition"
          >
            فتح البوت في التليجرام
          </a>
        </div>
      {:else}
        <button
          onclick={handleLink}
          disabled={actionLoading}
          class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl bg-sky-500 text-white font-semibold text-sm hover:bg-sky-600 transition disabled:opacity-50"
        >
          {actionLoading ? 'جاري إنشاء الرابط...' : 'ربط حساب التليجرام الآن'}
        </button>
      {/if}

      {#if errorMsg}
        <div class="text-xs text-destructive bg-destructive/10 p-2.5 rounded-lg">
          {errorMsg}
        </div>
      {/if}
    </div>
  {/if}
</div>
