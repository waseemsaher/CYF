/**
 * Central Pricing, Review Time, Plan-Driven Subscription Validity, Refund Policy & Trust Configuration.
 * Edit the constants below without touching any UI components.
 *
 * Safety net: `isPlaceholder()` and `getSafeConfig()` ensure that any unfinished
 * square-bracket placeholder or empty optional field never renders broken UI to students.
 */

export const SITE_ORIGIN = 'https://codeera.tech';
export const BRAND_NAME_DISPLAY = 'Codeera - كوديرا';
export const BRAND_DISCLAIMER_AR = 'Codeera منصة مستقلة وغير تابعة رسمياً لجامعة الأزهر.';

/**
 * Neutral fallback used automatically if REVIEW_TIME_TEXT ever contains a bracketed placeholder.
 */
export const NEUTRAL_REVIEW_FALLBACK_AR =
  'بنراجع الإيصال يدوياً وبنفتحلك المحتوى بعد الاعتماد';

/**
 * Confirmed by owner: Honest manual payment review time shown across hero stats, steps, FAQ, and checkout.
 * Do not promise a specific number of hours.
 */
export const REVIEW_TIME_TEXT = 'غالباً خلال ساعات';

/**
 * Plan-driven subscription validity configuration.
 *
 * How to add a new subscription plan later (e.g. monthly, exam_revision, annual):
 * 1. Add a new entry to `SUBSCRIPTION_PLANS` below with `id`, `labelAr`, `validityTextAr`, and `validityTextEn`.
 * 2. Call `getValidityText('your_plan_id')` where plan-specific validity is needed, or change `DEFAULT_SUBSCRIPTION_PLAN_ID`.
 */
export interface SubscriptionPlanConfig {
  id: string;
  labelAr: string;
  validityTextAr: string;
  validityTextEn: string;
}

export const SUBSCRIPTION_PLANS: Record<string, SubscriptionPlanConfig> = {
  semester: {
    id: 'semester',
    labelAr: 'اشتراك الفصل الدراسي',
    validityTextAr: 'لحد نهاية الفصل الدراسي الحالي',
    validityTextEn: 'until the end of the current academic semester'
  }
};

export const DEFAULT_SUBSCRIPTION_PLAN_ID = 'semester';
export const DEFAULT_SUBSCRIPTION_VALIDITY_FALLBACK_AR = 'لحد نهاية الفصل الدراسي الحالي';

/**
 * Return the subscription validity text for a given plan ID, falling back safely
 * to the default plan (`semester`) if the plan ID is omitted or unknown.
 */
export function getValidityText(
  planId: string = DEFAULT_SUBSCRIPTION_PLAN_ID,
  locale: 'ar' | 'en' = 'ar'
): string {
  const plan =
    (planId && SUBSCRIPTION_PLANS[planId]) || SUBSCRIPTION_PLANS[DEFAULT_SUBSCRIPTION_PLAN_ID];
  const raw =
    locale === 'en'
      ? plan?.validityTextEn ?? 'until the end of the current academic semester'
      : plan?.validityTextAr ?? DEFAULT_SUBSCRIPTION_VALIDITY_FALLBACK_AR;

  if (typeof raw !== 'string' || !raw.trim() || /\[[^\]]+\]/.test(raw)) {
    return locale === 'en'
      ? 'until the end of the current academic semester'
      : DEFAULT_SUBSCRIPTION_VALIDITY_FALLBACK_AR;
  }

  return raw.trim();
}

/**
 * Standard single-course price in EGP (confirmed by owner: 300 EGP).
 */
export const COURSE_PRICE_EGP = 300;

/**
 * Bundle ("باقة") configuration (confirmed by owner: 750 EGP for 3 courses).
 * Bundle UI only renders if BUNDLE_PRICE is a positive number strictly lower than
 * COURSE_PRICE_EGP * BUNDLE_COURSES_COUNT.
 */
export const BUNDLE_COURSES_COUNT = 3;
export const BUNDLE_PRICE = 750;

/**
 * Early-bird / Exam-season offer configuration.
 * Intentionally left empty until the owner schedules an offer before exams.
 * Set EARLY_BIRD_PRICE to a positive number lower than COURSE_PRICE_EGP (e.g. 250)
 * and EARLY_BIRD_END_DATE to an ISO date string in the future (e.g. '2026-12-15T23:59:59+02:00').
 */
export const EARLY_BIRD_PRICE: number = 0;
export const EARLY_BIRD_END_DATE: string = '';
export const EARLY_BIRD_LABEL_SUFFIX = 'قبل الميد';

/**
 * Refund policy values — confirmed by owner:
 * - 7-day refund window from course activation date (no condition on course progress).
 * - Full refund if a payment receipt is rejected or transferred in error.
 * - Requested via official WhatsApp support and processed within 2 to 3 business days.
 */
export const REFUND_WINDOW_DAYS = 7;
export const REFUND_PROCESSING_DAYS_TEXT = '2 إلى 3 أيام عمل';

export const REFUND_MISUSE_CLAUSE_AR =
  'بيحتفظ فريق المنصة بحق مراجعة الطلبات في حالات سوء الاستخدام الواضح (زي تحميل المحتوى بالكامل أو مشاركة بيانات الحساب).';

/**
 * Derived refund policy lines (single source of truth across homepage, course page,
 * checkout, FAQ, /refund, /terms, and /llms.txt).
 */
export const REFUND_GUARANTEE_SHORT = `ضمان استرداد كامل المبلغ لو الإيصال اترفض أو حصل خطأ في التحويل، أو لو طلبت استرداد خلال ${REFUND_WINDOW_DAYS} أيام من تاريخ تفعيل المقرر.`;

export const REFUND_WINDOW_RULE_AR = `تقدر تطلب استرداد رسوم المقرر خلال ${REFUND_WINDOW_DAYS} أيام من تاريخ تفعيل الاشتراك.`;

export const REFUND_PROCESSING_RULE_AR = `لطلب الاسترداد ابعتلنا على واتساب الدعم الفني برقم الموبايل المسجل وصورة الإيصال، وبيتم رد المبلغ خلال ${REFUND_PROCESSING_DAYS_TEXT}.`;

export const REFUND_POLICY_BODY_AR = `سياسة الاسترداد في Codeera - كوديرا واضحة ومباشرة عشان تكون مطمن قبل ما تشترك:

1. في حالة رفض الإيصال أو وجود خطأ في التحويل:
لو إيصال الدفع اترفض لأي سبب أو حصل خطأ في التحويل ومتمش تفعيل المقرر، بنرجعلك المبلغ بالكامل على نفس رقم المحفظة أو حساب إنستاباي اللي حولت منه.

2. طلب الاسترداد بعد تفعيل المقرر:
تقدر تطلب استرداد كامل رسوم المقرر خلال ${REFUND_WINDOW_DAYS} أيام من تاريخ تفعيل الاشتراك بدون أي شرط متعلق بنسبة المشاهدة.

3. طريقة تقديم طلب الاسترداد:
تواصل معانا عبر واتساب الدعم الفني الموضح في المنصة برقم الموبايل المسجل وصورة الإيصال، وبيتم مراجعة الطلب ورد المبلغ على نفس محفظتك خلال ${REFUND_PROCESSING_DAYS_TEXT}.

4. الاستخدام العادل للمنصة:
${REFUND_MISUSE_CLAUSE_AR}`;

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
 *
 * Writing guide for `storyAr` (3 short lines in friendly Egyptian Arabic):
 *   1. Who I am: عرف بنفسك باختصار كمبرمج وزميل من كلية الحاسبات والذكاء الاصطناعي.
 *   2. Why I built it: ليه بنيت Codeera (عشان زمايلك يلاقوا شرح مواد الكلية مترتب وواضح في مكان واحد).
 *   3. My promise to students: وعدك للطلاب (محتوى مبني على توصيف المقرر، كويزات عملية، وتواصل مباشر لو احتجت مساعدة).
 *
 * While `storyAr` is empty `''` or contains a bracketed placeholder, `getSafeConfig().founderStory`
 * returns `null` so the story paragraph stays hidden in production.
 */
export const FOUNDER_TRUST_CONFIG = {
  eyebrow: 'ليه تثق في Codeera - كوديرا؟',
  title: 'منصة مبنية من مبرمج فاهم طبيعة دراستك في حاسبات الأزهر',
  headlineAr: 'مبنية بواسطة مبرمج زميلك فاهم اللي محتاجه',
  founderName: '',
  founderRoleAr: 'مؤسس ومطور منصة Codeera - كوديرا',
  photoUrl: '',
  storyAr: '',
  refundSummary: `ضمان استرداد واضح: لو إيصال الدفع اترفض لأي سبب أو حصل خطأ في التحويل، أو لو طلبت استرداد خلال ${REFUND_WINDOW_DAYS} أيام من تاريخ تفعيل المقرر، بنرجعلك المبلغ بالكامل على نفس محفظتك.`,
  paymentSafetyNote: 'بتدفع على رقم رسمي وبتستلم تأكيد بعد مراجعة الإيصال.',
  disclaimer: BRAND_DISCLAIMER_AR
};

/**
 * Detect whether a config string still contains an unfinished square-bracket placeholder.
 */
export function isPlaceholder(value: unknown): boolean {
  if (typeof value !== 'string') return false;
  return /\[[^\]]+\]/.test(value);
}

export interface SafeConfigOverrides {
  reviewTimeText?: string;
  founderStory?: string;
  planId?: string;
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
  founderName: string | null;
  founderRoleAr: string;
  founderPhotoUrl: string | null;
  subscriptionValidityText: string;
  subscriptionValidityTextEn: string;
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
  refundProcessingDaysText: string;
  refundGuaranteeShort: string;
  refundWindowRuleAr: string;
  refundProcessingRuleAr: string;
  refundMisuseClauseAr: string;
  refundPolicyBodyAr: string;
  placeholderWarnings: string[];
}

/**
 * Return validated, student-safe configuration values with automatic fallbacks
 * whenever a placeholder or invalid number/date is present.
 */
export function getSafeConfig(overrides: SafeConfigOverrides = {}): SafePricingConfig {
  const rawReviewTime = overrides.reviewTimeText ?? REVIEW_TIME_TEXT;
  const rawFounderStory = overrides.founderStory ?? FOUNDER_TRUST_CONFIG.storyAr;
  const rawValidity =
    overrides.subscriptionValidityText ?? getValidityText(overrides.planId ?? DEFAULT_SUBSCRIPTION_PLAN_ID, 'ar');
  const subscriptionValidityTextEn = getValidityText(overrides.planId ?? DEFAULT_SUBSCRIPTION_PLAN_ID, 'en');
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
      'FOUNDER_TRUST_CONFIG.storyAr is empty or placeholder -> hiding founder story paragraph until filled.'
    );
  }
  const founderStory = storyIsPlaceholder ? null : rawFounderStory.trim();

  const rawFounderName = FOUNDER_TRUST_CONFIG.founderName?.trim() || '';
  const founderName = rawFounderName && !isPlaceholder(rawFounderName) ? rawFounderName : null;

  const rawFounderPhoto = FOUNDER_TRUST_CONFIG.photoUrl?.trim() || '';
  const founderPhotoUrl = rawFounderPhoto && !isPlaceholder(rawFounderPhoto) ? rawFounderPhoto : null;

  const validityIsPlaceholder =
    typeof rawValidity !== 'string' || !rawValidity.trim() || isPlaceholder(rawValidity);
  if (validityIsPlaceholder) {
    placeholderWarnings.push(
      `Subscription validity text is empty or placeholder -> falling back to "${DEFAULT_SUBSCRIPTION_VALIDITY_FALLBACK_AR}".`
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
    founderName,
    founderRoleAr: FOUNDER_TRUST_CONFIG.founderRoleAr,
    founderPhotoUrl,
    subscriptionValidityText,
    subscriptionValidityTextEn,
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
    refundProcessingDaysText: REFUND_PROCESSING_DAYS_TEXT,
    refundGuaranteeShort: REFUND_GUARANTEE_SHORT,
    refundWindowRuleAr: REFUND_WINDOW_RULE_AR,
    refundProcessingRuleAr: REFUND_PROCESSING_RULE_AR,
    refundMisuseClauseAr: REFUND_MISUSE_CLAUSE_AR,
    refundPolicyBodyAr: REFUND_POLICY_BODY_AR,
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
