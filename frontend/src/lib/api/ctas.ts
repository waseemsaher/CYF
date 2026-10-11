import { apiGet, apiPost, apiPut } from './client';

export type CtaStyle = 'primary' | 'secondary' | 'outline';
export type CtaStatus = 'active' | 'scheduled' | 'expired' | 'inactive';

export interface CtaBlock {
  id?: number;
  key: string;
  label_ar: string;
  headline_ar: string | null;
  subtext_ar: string | null;
  url: string;
  style: CtaStyle;
  is_active: boolean;
  starts_at: string | null;
  ends_at: string | null;
  open_in_new_tab: boolean;
  updated_by?: number | null;
  updated_at?: string | null;
  status?: CtaStatus;
  is_visible?: boolean;
}

/**
 * Safe default CTA definitions in the codebase.
 * Used whenever a CTA slot is inactive, expired, or missing from the backend.
 */
export const DEFAULT_CTA_BLOCKS: Record<string, CtaBlock> = {
  home_hero_primary: {
    key: 'home_hero_primary',
    label_ar: 'ابدأ بمادة واحدة',
    headline_ar: null,
    subtext_ar: null,
    url: '/courses',
    style: 'primary',
    is_active: true,
    starts_at: null,
    ends_at: null,
    open_in_new_tab: false,
    status: 'active',
    is_visible: true
  },
  home_hero_secondary: {
    key: 'home_hero_secondary',
    label_ar: 'شوف الدرس التجريبي',
    headline_ar: null,
    subtext_ar: null,
    url: '#sample-lesson',
    style: 'secondary',
    is_active: true,
    starts_at: null,
    ends_at: null,
    open_in_new_tab: false,
    status: 'active',
    is_visible: true
  },
  home_bottom: {
    key: 'home_bottom',
    label_ar: 'شوف مواد فرقتك',
    headline_ar: 'جاهز تبدأ مذاكرة بنظام واضح؟',
    subtext_ar:
      'اختار مادتك، ادفع بفودافون كاش أو إنستاباي على رقم رسمي، وهنراجع الإيصال ونفتحلك المحتوى.',
    url: '/courses',
    style: 'primary',
    is_active: true,
    starts_at: null,
    ends_at: null,
    open_in_new_tab: false,
    status: 'active',
    is_visible: true
  },
  course_page_default: {
    key: 'course_page_default',
    label_ar: 'اشترك في المقرر الآن',
    headline_ar: null,
    subtext_ar: null,
    url: '/checkout',
    style: 'primary',
    is_active: true,
    starts_at: null,
    ends_at: null,
    open_in_new_tab: false,
    status: 'active',
    is_visible: true
  },
  announcement_bar: {
    key: 'announcement_bar',
    label_ar: 'تصفح المواد',
    headline_ar: 'عرض بداية الترم متاح لفترة محدودة',
    subtext_ar: 'سعر خاص لطلاب حاسبات الأزهر قبل امتحانات الميدتيرم',
    url: '/courses',
    style: 'outline',
    is_active: false,
    starts_at: null,
    ends_at: null,
    open_in_new_tab: false,
    status: 'inactive',
    is_visible: false
  }
};

export const CTA_SLOT_METADATA: Record<
  string,
  { titleAr: string; locationAr: string; supportsBannerText: boolean }
> = {
  home_hero_primary: {
    titleAr: 'الزر الرئيسي في واجهة الصفحة الرئيسية (Hero Primary)',
    locationAr: 'أعلى الصفحة الرئيسية — الزر الأول بجانب العنوان الرئيسي',
    supportsBannerText: false
  },
  home_hero_secondary: {
    titleAr: 'الزر الثانوي في واجهة الصفحة الرئيسية (Hero Secondary)',
    locationAr: 'أعلى الصفحة الرئيسية — الزر الثاني بجانب الزر الرئيسي',
    supportsBannerText: false
  },
  home_bottom: {
    titleAr: 'بانر الدعوة لاتخاذ إجراء أسفل الصفحة الرئيسية (Home Bottom CTA)',
    locationAr: 'نهاية الصفحة الرئيسية بعد قسم الأسئلة الشائعة',
    supportsBannerText: true
  },
  course_page_default: {
    titleAr: 'زر الاشتراك في صفحة تفاصيل المقرر (Course Page Default)',
    locationAr: 'صندوق التسجيل الجانبي داخل صفحة كل مقرر دراسي (/courses/[slug])',
    supportsBannerText: false
  },
  announcement_bar: {
    titleAr: 'شريط الإعلانات العلوي (Announcement Bar)',
    locationAr: 'أعلى الموقع فوق القائمة الرئيسية (يظهر فقط عند التفعيل وداخل فترة العرض)',
    supportsBannerText: true
  }
};

/**
 * Strip any HTML tags from plain text fields as defense-in-depth on output.
 */
export function sanitizeCtaText(value: string | null | undefined): string {
  if (!value) return '';
  return value.replace(/<[^>]*>/g, '').trim();
}

/**
 * Validate URL is either a safe relative path (`/path` or `#anchor`) or `https://`.
 */
export function isValidCtaUrl(url: string | null | undefined): boolean {
  if (!url) return false;
  const trimmed = url.trim();
  if (!trimmed || /[\s<>"']/.test(trimmed)) return false;
  if (trimmed.startsWith('/') && !trimmed.startsWith('//')) return true;
  if (/^#[a-zA-Z0-9_-]+$/.test(trimmed)) return true;
  if (trimmed.startsWith('https://')) {
    try {
      const parsed = new URL(trimmed);
      return parsed.protocol === 'https:';
    } catch {
      return false;
    }
  }
  return false;
}

/**
 * Check if a CTA block is active and within its scheduled time window.
 */
export function isCtaBlockCurrentlyVisible(block: CtaBlock | null | undefined, now = new Date()): boolean {
  if (!block || !block.is_active) return false;
  if (block.starts_at) {
    const start = new Date(block.starts_at);
    if (!Number.isNaN(start.getTime()) && now < start) return false;
  }
  if (block.ends_at) {
    const end = new Date(block.ends_at);
    if (!Number.isNaN(end.getTime()) && now > end) return false;
  }
  const cleanLabel = sanitizeCtaText(block.label_ar);
  return cleanLabel.length > 0 && isValidCtaUrl(block.url);
}

/**
 * Resolve a button CTA slot with safe fallback if missing, inactive, or expired.
 * Never returns an empty or broken button.
 */
export function resolveCtaSlot(
  ctasMap: Record<string, CtaBlock> | null | undefined,
  key: string
): CtaBlock {
  const fallback = DEFAULT_CTA_BLOCKS[key] ?? DEFAULT_CTA_BLOCKS.home_hero_primary;
  const candidate = ctasMap?.[key];

  if (!candidate || !isCtaBlockCurrentlyVisible(candidate)) {
    return fallback;
  }

  return {
    ...fallback,
    ...candidate,
    label_ar: sanitizeCtaText(candidate.label_ar) || fallback.label_ar,
    headline_ar: sanitizeCtaText(candidate.headline_ar) || fallback.headline_ar,
    subtext_ar: sanitizeCtaText(candidate.subtext_ar) || fallback.subtext_ar,
    url: isValidCtaUrl(candidate.url) ? candidate.url.trim() : fallback.url
  };
}

/**
 * Resolve the optional announcement_bar slot.
 * Unlike button slots, announcement_bar is hidden by default unless explicitly active and within starts_at / ends_at.
 */
export function resolveAnnouncementBar(
  ctasMap: Record<string, CtaBlock> | null | undefined
): CtaBlock | null {
  const candidate = ctasMap?.announcement_bar;
  if (!candidate || !isCtaBlockCurrentlyVisible(candidate)) {
    return null;
  }
  return {
    ...candidate,
    label_ar: sanitizeCtaText(candidate.label_ar),
    headline_ar: sanitizeCtaText(candidate.headline_ar),
    subtext_ar: sanitizeCtaText(candidate.subtext_ar),
    url: candidate.url.trim()
  };
}

// Short-lived in-memory SSR cache (30 seconds TTL) so layout/page SSR loads stay fast
let ssrCache: { data: Record<string, CtaBlock>; expiresAt: number } | null = null;
const SSR_CACHE_TTL_MS = 30_000;

export async function getPublicCtas(
  fetcher: typeof fetch = fetch,
  bypassCache = false
): Promise<Record<string, CtaBlock>> {
  const now = Date.now();
  if (!bypassCache && ssrCache && ssrCache.expiresAt > now) {
    return ssrCache.data;
  }

  try {
    const res = await apiGet<{ data: Record<string, CtaBlock> }>(fetcher, '/ctas');
    const data = res?.data ?? {};
    ssrCache = { data, expiresAt: now + SSR_CACHE_TTL_MS };
    return data;
  } catch {
    return DEFAULT_CTA_BLOCKS;
  }
}

export function clearCtaCache(): void {
  ssrCache = null;
}

export function getAdminCtas(fetcher: typeof fetch = fetch) {
  return apiGet<{ data: CtaBlock[] }>(fetcher, '/admin/ctas');
}

export function updateAdminCta(
  fetcher: typeof fetch = fetch,
  key: string,
  payload: {
    label_ar: string;
    headline_ar?: string | null;
    subtext_ar?: string | null;
    url: string;
    style: CtaStyle;
    is_active: boolean;
    starts_at?: string | null;
    ends_at?: string | null;
    open_in_new_tab: boolean;
  }
) {
  clearCtaCache();
  return apiPut<{ data: CtaBlock }>(fetcher, `/admin/ctas/${encodeURIComponent(key)}`, payload);
}

export function toggleAdminCta(fetcher: typeof fetch = fetch, key: string) {
  clearCtaCache();
  return apiPost<{ data: CtaBlock }>(fetcher, `/admin/ctas/${encodeURIComponent(key)}/toggle`);
}

