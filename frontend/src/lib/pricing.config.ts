/**
 * Central Pricing, Review Time, Subscription Validity, Refund Policy & Trust Configuration.
 * Edit the constants below without touching any UI components.
 *
 * Safety net: `isPlaceholder()` and `getSafeConfig()` ensure that any unfinished
 * square-bracket placeholder (such as `[X]`) never renders publicly to students.
 */

export const SITE_ORIGIN = 'https://codeera.tech';
export const BRAND_NAME_DISPLAY = 'Codeera - كوديرا';
export const BRAND_DISCLAIMER_AR = 'Codeera منصة مستقلة وغير تابعة رسمياً لجامعة الأزهر.';

/**
 * Neutral fallback used automatically whenever REVIEW_TIME_TEXT still contains "[X]"
 * or any square-bracket placeholder.
 */
export const NEUTRAL_REVIEW_FALLBACK_AR =
  'بنراجع الإيصال يدوياً وبنفتحلك المحتوى بعد الاعتماد';

/**
 * Honest manual payment review time shown across hero stats, steps, FAQ, and checkout.
 * Placeholder: replace [X] with the real review window in hours (e.g. "غالباً خلال 2 إلى 4 ساعات").
 * While "[X]" is present, `getSafeConfig().reviewTimeText` safely falls back to NEUTRAL_REVIEW_FALLBACK_AR.
 */
export const REVIEW_TIME_TEXT = 'غالباً خلال [X] ساعات';

/**
 * Subscription validity period shown in FAQ and course details.
 */
export const SUBSCRIPTION_VALIDITY_TEXT =
  'طول الفصل الدراسي الحالي ولحد نهاية امتحانات الفاينل الخاصة بالمقرر';
export const DEFAULT_SUBSCRIPTION_VALIDITY_FALLBACK_AR = 'طول الفصل الدراسي الحالي';

/**
 * Standard single-course price in EGP.
 */
export const COURSE_PRICE_EGP = 300;

/**
 * Bundle ("باقة") configuration.
 * Set BUNDLE_COURSES_COUNT and BUNDLE_PRICE (in EGP).
 * Bundle UI only renders if BUNDLE_PRICE is a positive number strictly lower than
 * COURSE_PRICE_EGP * BUNDLE_COURSES_COUNT.
 */
export const BUNDLE_COURSES_COUNT = 3;
export const BUNDLE_PRICE = 750;

/**
 * Early-bird / Exam-season offer configuration.
 * Set EARLY_BIRD_END_DATE to an ISO date string in the future (e.g. '2026-11-15T23:59:59+02:00')
 * and EARLY_BIRD_PRICE to a valid offer price in EGP lower than COURSE_PRICE_EGP (e.g. 250).
 * Leave EARLY_BIRD_END_DATE as '' (empty) when no seasonal offer is active.
 */
export const EARLY_BIRD_PRICE = 250;
export const EARLY_BIRD_END_DATE: string = '';
export const EARLY_BIRD_LABEL_SUFFIX = 'قبل الميد';

// TODO(OWNER): confirm refund policy values
export const REFUND_WINDOW_DAYS = 7;
export const REFUND_MAX_WATCHED_PERCENT = 20;
export const REFUND_PROCESSING_DAYS_TEXT = '2 إلى 3 أيام عمل';

/**
 * Derived refund policy lines (single source of truth across homepage, course page,
 * checkout, FAQ, /refund, and /llms.txt).
 */
export const REFUND_GUARANTEE_SHORT = `ضمان استرداد كامل المبلغ لو الإيصال اترفض، أو خلال ${REFUND_WINDOW_DAYS} أيام من التفعيل قبل مشاهدة أكتر من ${REFUND_MAX_WATCHED_PERCENT}% من المقرر.`;

export const REFUND_WINDOW_RULE_AR = `استرجاع خلال ${REFUND_WINDOW_DAYS} أيام من التفعيل لو المشاهدة أقل من ${REFUND_MAX_WATCHED_PERCENT}%.`;

export const REFUND_PROCESSING_RULE_AR = `رد المبلغ بيتم على نفس رقم المحفظة أو حساب إنستاباي خلال ${REFUND_PROCESSING_DAYS_TEXT}.`;

/**
 * Referral field configuration.
 * Note: Referral codes are reviewed manually by the admin team alongside the payment receipt;
 * no automatic discount is applied at checkout.
 */
export const REFERRAL_CODE_MAX_LENGTH = 32;
export const REFERRAL_FIELD_HELPER_AR =
  'عندك كود ترشيح من زميل؟ اكتبه هنا وهنراجعه مع الإيصال (يتم تأكيد أي مزايا أو خصم يدوياً من فريق المراجعة بعد التحقق من الكود، ومش بيخصم تلقائياً).';

/**
 * Founder / "Why trust us" configuration on the homepage.
 * While `storyPlaceholder` still contains square brackets `[...]`, `getSafeConfig().founderStory`
 * returns `null` so the placeholder paragraph is hidden in production while keeping the rest of the trust card.
 */
export const FOUNDER_TRUST_CONFIG = {
  eyebrow: 'ليه تثق في Codeera - كوديرا؟',
  title: 'منصة مبنية من مبرمج فاهم طبيعة دراستك في حاسبات الأزهر',
  headlineAr: 'مبنية بواسطة مبرمج زميلك فاهم اللي محتاجه',
  roleAr: 'مؤسس ومطور منصة Codeera - كوديرا',
  storyPlaceholder:
    '[مساحة مخصصة لقصة مؤسس المنصة: اكتب هنا نبذة قصيرة عنك كمطور وباني المنصة، تجربتك مع مواد الكلية، وهدفك إن زمايلك يلاقوا شرح منظم وواضح بدل التشتت بين المصادر.]',
  refundSummary: `ضمان استرداد واضح: لو إيصال الدفع اترفض لأي سبب، أو لو طلبت استرداد خلال ${REFUND_WINDOW_DAYS} أيام من التفعيل (وقبل مشاهدة أكتر من ${REFUND_MAX_WATCHED_PERCENT}% من المحتوى)، بيتم رد المبلغ بالكامل على نفس محفظتك.`,
  paymentSafetyNote: 'بتدفع على رقم رسمي وبتستلم تأكيد بعد مراجعة الإيصال.',
  disclaimer: BRAND_DISCLAIMER_AR
};

/**
 * Detect whether a config string still contains an unfinished square-bracket placeholder
 * such as `[X]` or `[مساحة مخصصة ...]`.
 */
export function isPlaceholder(value: unknown): boolean {
  if (typeof value !== 'string') return false;
  return /\[[^\]]+\]/.test(value);
}

export interface SafeConfigOverrides {
  reviewTimeText?: string;
  founderStory?: string;
  subscriptionValidityText?: string;
  coursePriceEgp?: number;
  bundleCoursesCount?: number;
  bundlePrice?: number;
  earlyBirdPrice?: number;
  earlyBirdEndDate?: string;
  now?: Date;
}

export interface SafePricingConfig {
  reviewTimeText: string;
  hasSpecificReviewTime: boolean;
  founderStory: string | null;
  subscriptionValidityText: string;
  coursePriceEgp: number;
  bundleCoursesCount: number;
  bundlePrice: number;
  isBundleValid: boolean;
  bundleSavingEgp: number;
  earlyBirdPrice: number;
  earlyBirdEndDate: string;
  isEarlyBirdValid: boolean;
  earlyBirdDateLabel: string;
  refundWindowDays: number;
  refundMaxWatchedPercent: number;
  refundProcessingDaysText: string;
  refundGuaranteeShort: string;
  refundWindowRuleAr: string;
  refundProcessingRuleAr: string;
  placeholderWarnings: string[];
}

/**
 * Return validated, student-safe configuration values with automatic fallbacks
 * whenever a placeholder or invalid number/date is present.
 */
export function getSafeConfig(overrides: SafeConfigOverrides = {}): SafePricingConfig {
  const rawReviewTime = overrides.reviewTimeText ?? REVIEW_TIME_TEXT;
  const rawFounderStory = overrides.founderStory ?? FOUNDER_TRUST_CONFIG.storyPlaceholder;
  const rawValidity = overrides.subscriptionValidityText ?? SUBSCRIPTION_VALIDITY_TEXT;
  const rawCoursePrice = overrides.coursePriceEgp ?? COURSE_PRICE_EGP;
  const rawBundleCount = overrides.bundleCoursesCount ?? BUNDLE_COURSES_COUNT;
  const rawBundlePrice = overrides.bundlePrice ?? BUNDLE_PRICE;
  const rawEarlyBirdPrice = overrides.earlyBirdPrice ?? EARLY_BIRD_PRICE;
  const rawEarlyBirdEndDate = overrides.earlyBirdEndDate ?? EARLY_BIRD_END_DATE;
  const now = overrides.now ?? new Date();

  const placeholderWarnings: string[] = [];

  const reviewIsPlaceholder =
    typeof rawReviewTime !== 'string' ||
    !rawReviewTime.trim() ||
    isPlaceholder(rawReviewTime);
  if (reviewIsPlaceholder) {
    placeholderWarnings.push(
      `REVIEW_TIME_TEXT contains a placeholder ("${rawReviewTime}") -> falling back to "${NEUTRAL_REVIEW_FALLBACK_AR}".`
    );
  }
  const reviewTimeText = reviewIsPlaceholder
    ? NEUTRAL_REVIEW_FALLBACK_AR
    : rawReviewTime.trim();

  const storyIsPlaceholder =
    typeof rawFounderStory !== 'string' ||
    !rawFounderStory.trim() ||
    isPlaceholder(rawFounderStory);
  if (storyIsPlaceholder) {
    placeholderWarnings.push(
      'FOUNDER_TRUST_CONFIG.storyPlaceholder contains a bracketed placeholder -> hiding founder story paragraph.'
    );
  }
  const founderStory = storyIsPlaceholder ? null : rawFounderStory.trim();

  const validityIsPlaceholder =
    typeof rawValidity !== 'string' || !rawValidity.trim() || isPlaceholder(rawValidity);
  if (validityIsPlaceholder) {
    placeholderWarnings.push(
      `SUBSCRIPTION_VALIDITY_TEXT is empty or placeholder -> falling back to "${DEFAULT_SUBSCRIPTION_VALIDITY_FALLBACK_AR}".`
    );
  }
  const subscriptionValidityText = validityIsPlaceholder
    ? DEFAULT_SUBSCRIPTION_VALIDITY_FALLBACK_AR
    : rawValidity.trim();

  const coursePriceEgp =
    typeof rawCoursePrice === 'number' && Number.isFinite(rawCoursePrice) && rawCoursePrice > 0
      ? rawCoursePrice
      : 300;

  const bundleCoursesCount =
    typeof rawBundleCount === 'number' && Number.isInteger(rawBundleCount) && rawBundleCount > 1
      ? rawBundleCount
      : 3;

  const separateTotal = coursePriceEgp * bundleCoursesCount;
  const isBundleValid =
    typeof rawBundlePrice === 'number' &&
    Number.isFinite(rawBundlePrice) &&
    rawBundlePrice > 0 &&
    rawBundlePrice < separateTotal;

  const bundlePrice = isBundleValid ? rawBundlePrice : separateTotal;
  const bundleSavingEgp = isBundleValid ? separateTotal - rawBundlePrice : 0;

  const trimmedEndDate = typeof rawEarlyBirdEndDate === 'string' ? rawEarlyBirdEndDate.trim() : '';
  const parsedEndDate = trimmedEndDate && !isPlaceholder(trimmedEndDate) ? new Date(trimmedEndDate) : null;
  const isEarlyBirdDateValidFuture =
    parsedEndDate !== null &&
    !Number.isNaN(parsedEndDate.getTime()) &&
    parsedEndDate.getTime() > now.getTime();

  const isEarlyBirdPriceValid =
    typeof rawEarlyBirdPrice === 'number' &&
    Number.isFinite(rawEarlyBirdPrice) &&
    rawEarlyBirdPrice > 0 &&
    rawEarlyBirdPrice < coursePriceEgp;

  const isEarlyBirdValid = isEarlyBirdPriceValid && isEarlyBirdDateValidFuture;

  if (!trimmedEndDate || isPlaceholder(trimmedEndDate)) {
    placeholderWarnings.push(
      'EARLY_BIRD_END_DATE is empty or placeholder -> early-bird offer is hidden.'
    );
  }

  return {
    reviewTimeText,
    hasSpecificReviewTime: !reviewIsPlaceholder,
    founderStory,
    subscriptionValidityText,
    coursePriceEgp,
    bundleCoursesCount,
    bundlePrice,
    isBundleValid,
    bundleSavingEgp,
    earlyBirdPrice: rawEarlyBirdPrice,
    earlyBirdEndDate: trimmedEndDate,
    isEarlyBirdValid,
    earlyBirdDateLabel: isEarlyBirdValid ? formatEarlyBirdEndDate(trimmedEndDate) : '',
    refundWindowDays: REFUND_WINDOW_DAYS,
    refundMaxWatchedPercent: REFUND_MAX_WATCHED_PERCENT,
    refundProcessingDaysText: REFUND_PROCESSING_DAYS_TEXT,
    refundGuaranteeShort: REFUND_GUARANTEE_SHORT,
    refundWindowRuleAr: REFUND_WINDOW_RULE_AR,
    refundProcessingRuleAr: REFUND_PROCESSING_RULE_AR,
    placeholderWarnings
  };
}

/**
 * Calculate bundle savings in EGP compared to buying courses individually.
 * Returns 0 if the bundle configuration is invalid.
 */
export function getBundleSavingEgp(singleCoursePriceEgp: number = COURSE_PRICE_EGP): number {
  return getSafeConfig({ coursePriceEgp: singleCoursePriceEgp }).bundleSavingEgp;
}

/**
 * Check whether the early-bird offer is currently active (price is valid AND date is a valid future date).
 */
export function isEarlyBirdActive(now: Date = new Date()): boolean {
  return getSafeConfig({ now }).isEarlyBirdValid;
}

/**
 * Format the early-bird end date in readable Egyptian Arabic.
 */
export function formatEarlyBirdEndDate(dateStr: string = EARLY_BIRD_END_DATE): string {
  if (!dateStr || isPlaceholder(dateStr)) return '';
  const parsed = new Date(dateStr);
  if (Number.isNaN(parsed.getTime())) return '';
  return parsed.toLocaleDateString('ar-EG', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
}

/**
 * Calculate honest per-lesson cost in EGP when lesson count is known (> 0).
 */
export function getPerLessonCostEgp(priceEgp: number, lessonsCount?: number | null): number | null {
  if (!lessonsCount || lessonsCount <= 0 || priceEgp <= 0) return null;
  return Math.ceil(priceEgp / lessonsCount);
}

/**
 * Sanitize and limit student referral code input so it cannot inject markup,
 * break the `[كود ترشيح: ...]` delimiter, or exceed REFERRAL_CODE_MAX_LENGTH (32 chars).
 */
export function sanitizeReferralCode(raw: string | null | undefined): string {
  if (!raw) return '';
  return raw
    .replace(/<[^>]*>/g, '')
    .replace(/[\[\]\r\n\t<>]/g, '')
    .trim()
    .slice(0, REFERRAL_CODE_MAX_LENGTH);
}

/**
 * Parse a `student_note` string into a separate `referralCode` (if present)
 * and `cleanNote` (the remaining student note text) for clear display in the admin view.
 */
export function parseReferralFromStudentNote(note: string | null | undefined): {
  referralCode: string | null;
  cleanNote: string;
} {
  if (!note) {
    return { referralCode: null, cleanNote: '' };
  }

  const match = note.match(/\[كود ترشيح:\s*([^\]]+?)\s*\]/);
  if (!match) {
    return { referralCode: null, cleanNote: note.trim() };
  }

  const referralCode = sanitizeReferralCode(match[1]) || null;
  const cleanNote = note
    .replace(match[0], '')
    .replace(/^\s*—\s*/, '')
    .replace(/\s*—\s*$/, '')
    .trim();

  return { referralCode, cleanNote };
}
