<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { currentUser } from '$lib/api/auth';
  import {
    getAdminCtas,
    updateAdminCta,
    toggleAdminCta,
    CTA_SLOT_METADATA,
    DEFAULT_CTA_BLOCKS,
    isValidCtaUrl,
    type CtaBlockItem,
    type CtaStyle
  } from '$lib/api/ctas';

  let loading = $state(true);
  let errorMsg = $state('');
  let successMsg = $state('');
  let ctas = $state<CtaBlockItem[]>([]);
  let togglingKey = $state<string | null>(null);

  // Edit form state
  let editingSlotKey = $state<string | null>(null);
  let formLabelAr = $state('');
  let formHeadlineAr = $state('');
  let formSubtextAr = $state('');
  let formUrl = $state('');
  let formStyle = $state<CtaStyle>('primary');
  let formIsActive = $state(true);
  let formStartsAt = $state('');
  let formEndsAt = $state('');
  let formOpenInNewTab = $state(false);
  let formError = $state('');
  let saving = $state(false);

  onMount(async () => {
    const user = $currentUser;
    if (!user || (user.role !== 'admin' && user.role !== 'superadmin')) {
      goto('/login');
      return;
    }
    await loadCtas();
  });

  async function loadCtas() {
    loading = true;
    errorMsg = '';
    try {
      ctas = await getAdminCtas(fetch);
    } catch (err) {
      errorMsg = err instanceof Error ? err.message : 'تعذر تحميل بيانات أزرار الدعوة (CTAs).';
    } finally {
      loading = false;
    }
  }

  function toLocalDatetimeInput(iso: string | null | undefined): string {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  function openEditSlot(item: CtaBlockItem) {
    editingSlotKey = item.key;
    formLabelAr = item.label_ar || '';
    formHeadlineAr = item.headline_ar || '';
    formSubtextAr = item.subtext_ar || '';
    formUrl = item.url || '';
    formStyle = item.style || 'primary';
    formIsActive = Boolean(item.is_active);
    formStartsAt = toLocalDatetimeInput(item.starts_at);
    formEndsAt = toLocalDatetimeInput(item.ends_at);
    formOpenInNewTab = Boolean(item.open_in_new_tab);
    formError = '';
    successMsg = '';
  }

  function closeEditModal() {
    editingSlotKey = null;
    formError = '';
  }

  async function handleToggle(item: CtaBlockItem) {
    togglingKey = item.key;
    errorMsg = '';
    successMsg = '';
    try {
      const updated = await toggleAdminCta(fetch, item.key);
      ctas = ctas.map((c) => (c.key === item.key ? updated : c));
      successMsg = `تم تحديث حالة "${CTA_SLOT_METADATA[item.key]?.titleAr || item.key}" بنجاح.`;
    } catch (err) {
      errorMsg = err instanceof Error ? err.message : 'تعذر تغيير حالة الزر.';
    } finally {
      togglingKey = null;
    }
  }

  function validateForm(): string | null {
    const cleanLabel = formLabelAr.trim();
    if (!cleanLabel) {
      return 'نص الزر (label_ar) مطلوب.';
    }
    if (cleanLabel.length > 80) {
      return 'نص الزر يجب ألا يتجاوز 80 حرفاً.';
    }
    if (formHeadlineAr.trim().length > 160) {
      return 'العنوان الرئيسي يجب ألا يتجاوز 160 حرفاً.';
    }
    if (formSubtextAr.trim().length > 240) {
      return 'النص الفرعي يجب ألا يتجاوز 240 حرفاً.';
    }
    const cleanUrl = formUrl.trim();
    if (!cleanUrl || !isValidCtaUrl(cleanUrl)) {
      return 'الرابط غير صالح. يُسمح فقط بالروابط التي تبدأ بـ https:// أو المسارات الداخلية التي تبدأ بـ / أو #.';
    }
    if (cleanUrl.length > 500) {
      return 'الرابط يجب ألا يتجاوز 500 حرف.';
    }
    if (formStartsAt && formEndsAt) {
      const startMs = Date.parse(formStartsAt);
      const endMs = Date.parse(formEndsAt);
      if (!Number.isNaN(startMs) && !Number.isNaN(endMs) && endMs <= startMs) {
        return 'تاريخ الانتهاء (ends_at) يجب أن يكون بعد تاريخ البدء (starts_at).';
      }
    }
    return null;
  }

  async function handleSave(e: SubmitEvent) {
    e.preventDefault();
    if (!editingSlotKey) return;

    const validationError = validateForm();
    if (validationError) {
      formError = validationError;
      return;
    }

    saving = true;
    formError = '';
    successMsg = '';

    try {
      const updated = await updateAdminCta(fetch, editingSlotKey, {
        label_ar: formLabelAr.trim(),
        headline_ar: formHeadlineAr.trim() || null,
        subtext_ar: formSubtextAr.trim() || null,
        url: formUrl.trim(),
        style: formStyle,
        is_active: formIsActive,
        starts_at: formStartsAt ? new Date(formStartsAt).toISOString() : null,
        ends_at: formEndsAt ? new Date(formEndsAt).toISOString() : null,
        open_in_new_tab: formOpenInNewTab
      });

      ctas = ctas.map((c) => (c.key === editingSlotKey ? updated : c));
      successMsg = `تم حفظ تعديلات "${CTA_SLOT_METADATA[editingSlotKey]?.titleAr || editingSlotKey}" بنجاح.`;
      closeEditModal();
    } catch (err) {
      formError = err instanceof Error ? err.message : 'تعذر حفظ التعديلات. تأكد من صحة الحقول.';
    } finally {
      saving = false;
    }
  }

  function statusBadgeInfo(status: string | undefined) {
    switch (status) {
      case 'active':
        return { label: 'نشط (Active)', classes: 'bg-emerald-100 text-emerald-800 border-emerald-200' };
      case 'scheduled':
        return { label: 'مجدول (Scheduled)', classes: 'bg-amber-100 text-amber-800 border-amber-200' };
      case 'expired':
        return { label: 'منتهي الصلاحية (Expired)', classes: 'bg-rose-100 text-rose-800 border-rose-200' };
      default:
        return { label: 'غير نشط (Inactive)', classes: 'bg-slate-100 text-slate-700 border-slate-200' };
    }
  }
</script>

<svelte:head>
  <title>إدارة أزرار الدعوة (CTA Management) | Codeera Admin</title>
</svelte:head>

<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8" dir="rtl">
  <!-- Header -->
  <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
    <div>
      <div class="flex items-center gap-2 text-xs font-extrabold text-brand-navy mb-1">
        <a href="/admin" class="hover:underline">لوحة الإدارة</a>
        <span>/</span>
        <span>إدارة أزرار الدعوة والإعلانات (CTA Management)</span>
      </div>
      <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-navy">
        إدارة أزرار الدعوة (CTA Management)
      </h1>
      <p class="mt-1 text-sm text-slate-600">
        تحكم في نصوص وروابط أزرار الصفحة الرئيسية وصفحات المقررات وشريط العروض العلوي بدون تعديل الكود.
      </p>
    </div>

    <div class="flex items-center gap-3 self-start sm:self-auto">
      <button
        type="button"
        onclick={loadCtas}
        class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50"
      >
        تحديث القائمة
      </button>
      <a
        href="/admin"
        class="rounded-xl bg-brand-navy px-4 py-2 text-xs font-bold text-white hover:bg-brand-blue"
      >
        العودة للوحة الإدارة
      </a>
    </div>
  </div>

  <!-- Fallback Rule Notice -->
  <div class="mb-6 rounded-2xl border border-cyan-200 bg-cyan-50/70 p-4 text-xs leading-relaxed text-slate-700">
    <strong class="text-brand-navy">قاعدة الأمان التلقائية (Safe Fallback):</strong>
    لو أي زر أصلح غير نشط أو انتهى تاريخ عرضه (Expired)، الموقع بيعرض تلقائياً النص الافتراضي الآمن المدمج في الكود عشان مفيش أي زر يظهر فاضي للطلاب، ما عدا شريط الإعلان العلوي (`announcement_bar`) فبيختفي تماماً.
  </div>

  {#if errorMsg}
    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-800" role="alert">
      {errorMsg}
    </div>
  {/if}

  {#if successMsg}
    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800" role="status">
      {successMsg}
    </div>
  {/if}

  {#if loading}
    <div class="space-y-4">
      {#each [1, 2, 3, 4] as _}
        <div class="h-36 animate-pulse rounded-2xl bg-slate-100"></div>
      {/each}
    </div>
  {:else}
    <div class="grid gap-6">
      {#each ctas as item (item.key)}
        {@const meta = CTA_SLOT_METADATA[item.key]}
        {@const badge = statusBadgeInfo(item.status)}
        {@const fallback = DEFAULT_CTA_BLOCKS[item.key]}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-slate-300">
          <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
            <!-- Slot Info -->
            <div class="flex-1 space-y-2">
              <div class="flex flex-wrap items-center gap-2.5">
                <span class="rounded-md bg-slate-100 px-2.5 py-1 font-mono text-xs font-bold text-slate-700" dir="ltr">
                  {item.key}
                </span>
                <span class="rounded-full border px-3 py-0.5 text-xs font-extrabold {badge.classes}">
                  {badge.label}
                </span>
                <span class="rounded-full bg-brand-navy/10 px-2.5 py-0.5 text-xs font-bold text-brand-navy">
                  النمط: {item.style}
                </span>
              </div>

              <h2 class="text-lg font-extrabold text-brand-navy">
                {meta?.titleAr || item.key}
              </h2>
              {#if meta?.descriptionAr}
                <p class="text-xs text-slate-500">{meta.descriptionAr}</p>
              {/if}

              <div class="mt-3 grid gap-2 sm:grid-cols-2 text-xs text-slate-700 bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                <div>
                  <span class="font-bold text-slate-500 block">نص الزر (label_ar):</span>
                  <span class="font-extrabold text-brand-navy">{item.label_ar}</span>
                </div>
                <div>
                  <span class="font-bold text-slate-500 block">الرابط (url):</span>
                  <span class="font-mono text-slate-800 break-all" dir="ltr">{item.url}</span>
                </div>
                {#if item.headline_ar}
                  <div class="sm:col-span-2">
                    <span class="font-bold text-slate-500 block">العنوان الرئيسي (headline_ar):</span>
                    <span>{item.headline_ar}</span>
                  </div>
                {/if}
                {#if item.subtext_ar}
                  <div class="sm:col-span-2">
                    <span class="font-bold text-slate-500 block">النص الفرعي (subtext_ar):</span>
                    <span>{item.subtext_ar}</span>
                  </div>
                {/if}
                {#if item.starts_at || item.ends_at}
                  <div class="sm:col-span-2 flex flex-wrap gap-4 pt-1 border-t border-slate-200/70 text-[11px] text-slate-500">
                    {#if item.starts_at}
                      <span>يبدأ: <strong dir="ltr">{new Date(item.starts_at).toLocaleString('ar-EG')}</strong></span>
                    {/if}
                    {#if item.ends_at}
                      <span>ينتهي: <strong dir="ltr">{new Date(item.ends_at).toLocaleString('ar-EG')}</strong></span>
                    {/if}
                  </div>
                {/if}
              </div>
            </div>

            <!-- Live Preview & Actions -->
            <div class="flex flex-col justify-between gap-4 lg:w-80 shrink-0 rounded-xl border border-slate-200 bg-slate-900 p-4 text-white">
              <div>
                <span class="text-[11px] font-bold text-slate-400 block mb-2">
                  معاينة حية (Live Preview):
                </span>

                {#if item.key === 'announcement_bar'}
                  {#if item.status === 'active'}
                    <div class="rounded-lg bg-brand-navy border border-brand-cyan/30 p-2.5 text-xs">
                      <p class="font-bold text-white">{item.headline_ar || item.label_ar}</p>
                      {#if item.subtext_ar}
                        <p class="text-[11px] text-slate-300 mt-0.5">{item.subtext_ar}</p>
                      {/if}
                      <span class="mt-2 inline-block rounded-full bg-brand-cyan px-3 py-0.5 text-[11px] font-extrabold text-brand-navy">
                        {item.label_ar}
                      </span>
                    </div>
                  {:else}
                    <p class="text-xs text-slate-400 italic">
                      شريط الإعلان مخفي حالياً للزوار (لأنه غير نشط أو خارج فترة العرض).
                    </p>
                  {/if}
                {:else}
                  <div class="flex flex-col items-start gap-2">
                    <span
                      class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-extrabold transition {item.style === 'primary'
                        ? 'bg-brand-cyan text-brand-navy'
                        : item.style === 'secondary'
                          ? 'border border-white/30 bg-white/10 text-white'
                          : 'border-2 border-brand-cyan text-brand-cyan'}"
                    >
                      <span>{item.status === 'active' ? item.label_ar : fallback?.label_ar || item.label_ar}</span>
                      <span>←</span>
                    </span>
                    {#if item.status !== 'active' && fallback}
                      <span class="text-[11px] text-amber-300">
                        * يظهر حالياً بالنص الافتراضي الآمن ("{fallback.label_ar}") لأن هذا الزر {badge.label}.
                      </span>
                    {/if}
                  </div>
                {/if}
              </div>

              <div class="flex items-center gap-2 pt-3 border-t border-white/10">
                <button
                  type="button"
                  onclick={() => openEditSlot(item)}
                  class="flex-1 rounded-lg bg-white px-3 py-2 text-xs font-extrabold text-brand-navy transition hover:bg-brand-cyan"
                >
                  تعديل المحتوى
                </button>
                <button
                  type="button"
                  disabled={togglingKey === item.key}
                  onclick={() => handleToggle(item)}
                  class="rounded-lg border px-3 py-2 text-xs font-bold transition {item.is_active
                    ? 'border-rose-400/50 bg-rose-500/20 text-rose-200 hover:bg-rose-500/30'
                    : 'border-emerald-400/50 bg-emerald-500/20 text-emerald-200 hover:bg-emerald-500/30'}"
                >
                  {togglingKey === item.key ? '...' : item.is_active ? 'إيقاف' : 'تفعيل'}
                </button>
              </div>
            </div>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>

<!-- Edit Modal -->
{#if editingSlotKey}
  {@const meta = CTA_SLOT_METADATA[editingSlotKey]}
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs" role="dialog" aria-modal="true" dir="rtl">
    <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
      <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-4">
        <div>
          <span class="font-mono text-xs font-bold text-slate-500" dir="ltr">{editingSlotKey}</span>
          <h2 class="text-xl font-extrabold text-brand-navy">
            تعديل: {meta?.titleAr || editingSlotKey}
          </h2>
        </div>
        <button
          type="button"
          onclick={closeEditModal}
          class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
          aria-label="إغلاق"
        >
          ✕
        </button>
      </div>

      {#if formError}
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs font-bold text-rose-800" role="alert">
          {formError}
        </div>
      {/if}

      <form onsubmit={handleSave} class="space-y-4">
        <div>
          <label for="cta-label" class="block text-xs font-bold text-slate-700 mb-1">
            نص الزر بالعربية (label_ar) * <span class="text-slate-400">(بحد أقصى 80 حرفاً)</span>
          </label>
          <input
            id="cta-label"
            type="text"
            maxlength="80"
            required
            bind:value={formLabelAr}
            class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-navy focus:outline-none"
            placeholder="مثال: ابدأ بمادة واحدة"
          />
        </div>

        <div>
          <label for="cta-url" class="block text-xs font-bold text-slate-700 mb-1">
            الرابط (url) * <span class="text-slate-400">(يقبل فقط https:// أو مسار يبدأ بـ / أو #)</span>
          </label>
          <input
            id="cta-url"
            type="text"
            dir="ltr"
            maxlength="500"
            required
            bind:value={formUrl}
            class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-brand-navy focus:outline-none"
            placeholder="/courses"
          />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label for="cta-style" class="block text-xs font-bold text-slate-700 mb-1">
              تصميم الزر (Style)
            </label>
            <select
              id="cta-style"
              bind:value={formStyle}
              class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-navy focus:outline-none"
            >
              <option value="primary">Primary (أساسي بارز)</option>
              <option value="secondary">Secondary (ثانوي شفاف)</option>
              <option value="outline">Outline (إطار خارجي)</option>
            </select>
          </div>

          <div class="flex flex-col justify-center gap-2 pt-4">
            <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
              <input type="checkbox" bind:checked={formIsActive} class="rounded text-brand-navy" />
              <span>تفعيل هذا الزر (is_active)</span>
            </label>
            <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
              <input type="checkbox" bind:checked={formOpenInNewTab} class="rounded text-brand-navy" />
              <span>فتح في نافذة جديدة (open_in_new_tab)</span>
            </label>
          </div>
        </div>

        <div>
          <label for="cta-headline" class="block text-xs font-bold text-slate-700 mb-1">
            العنوان الرئيسي للبانر (headline_ar — اختياري، بحد أقصى 160 حرفاً)
          </label>
          <input
            id="cta-headline"
            type="text"
            maxlength="160"
            bind:value={formHeadlineAr}
            class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-navy focus:outline-none"
            placeholder="مثال: جاهز تبدأ مذاكرة مواد فرقتك بترتيب واضح؟"
          />
        </div>

        <div>
          <label for="cta-subtext" class="block text-xs font-bold text-slate-700 mb-1">
            النص الفرعي للبانر (subtext_ar — اختياري، بحد أقصى 240 حرفاً)
          </label>
          <textarea
            id="cta-subtext"
            rows="2"
            maxlength="240"
            bind:value={formSubtextAr}
            class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-navy focus:outline-none"
            placeholder="وصف مختصر يظهر تحت العنوان في البانر أو شريط الإعلان"
          ></textarea>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label for="cta-starts" class="block text-xs font-bold text-slate-700 mb-1">
              يبدأ العرض في (starts_at — اختياري)
            </label>
            <input
              id="cta-starts"
              type="datetime-local"
              dir="ltr"
              bind:value={formStartsAt}
              class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-brand-navy focus:outline-none"
            />
          </div>

          <div>
            <label for="cta-ends" class="block text-xs font-bold text-slate-700 mb-1">
              ينتهي العرض في (ends_at — اختياري)
            </label>
            <input
              id="cta-ends"
              type="datetime-local"
              dir="ltr"
              bind:value={formEndsAt}
              class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-brand-navy focus:outline-none"
            />
          </div>
        </div>

        <!-- Live Preview inside Modal -->
        <div class="rounded-xl bg-slate-900 p-4 text-white">
          <span class="text-[11px] font-bold text-slate-400 block mb-2">معاينة مباشرة للتعديل:</span>
          {#if formHeadlineAr.trim()}
            <p class="text-sm font-extrabold text-white mb-1">{formHeadlineAr}</p>
          {/if}
          {#if formSubtextAr.trim()}
            <p class="text-xs text-slate-300 mb-3">{formSubtextAr}</p>
          {/if}
          <span
            class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-extrabold {formStyle === 'primary'
              ? 'bg-brand-cyan text-brand-navy'
              : formStyle === 'secondary'
                ? 'border border-white/30 bg-white/10 text-white'
                : 'border-2 border-brand-cyan text-brand-cyan'}"
          >
            <span>{formLabelAr.trim() || 'نص الزر'}</span>
            <span>←</span>
          </span>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
          <button
            type="button"
            onclick={closeEditModal}
            class="rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50"
          >
            إلغاء
          </button>
          <button
            type="submit"
            disabled={saving}
            class="rounded-xl bg-brand-navy px-5 py-2.5 text-xs font-extrabold text-white hover:bg-brand-blue disabled:opacity-50"
          >
            {saving ? 'جاري الحفظ...' : 'حفظ التغييرات'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}
