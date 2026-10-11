/**
 * Central Pricing, Review Time, Subscription Validity & Trust Configuration.
 * Edit the placeholder constants below without touching any UI components.
 */

export const SITE_ORIGIN = 'https://codeera.tech';
export const BRAND_NAME_DISPLAY = 'Codeera - كوديرا';
export const BRAND_DISCLAIMER_AR = 'Codeera منصة مستقلة وغير تابعة رسمياً لجامعة الأزهر.';

/**
 * Honest manual payment review time shown across hero stats, steps, FAQ, and checkout.
 * Placeholder: replace [X] with the real review window in hours (e.g. "غالباً خلال 2 إلى 4 ساعات").
 */
export const REVIEW_TIME_TEXT = 'غالباً خلال [X] ساعات';

/**
 * Subscription validity period shown in FAQ and course details.
 * Placeholder: customize with exact term/exam end details if needed.
 */
export const SUBSCRIPTION_VALIDITY_TEXT =
  'طول الفصل الدراسي الحالي ولحد نهاية امتحانات الفاينل الخاصة بالمقرر';

/**
 * Standard single-course price in EGP.
 */
export const COURSE_PRICE_EGP = 300;

/**
 * Bundle ("باقة") configuration.
 * Set BUNDLE_COURSES_COUNT and BUNDLE_PRICE (in EGP).
 * Savings vs buying separately are calculated automatically:
 *   (COURSE_PRICE_EGP * BUNDLE_COURSES_COUNT) - BUNDLE_PRICE
 */
export const BUNDLE_COURSES_COUNT = 3;
export const BUNDLE_PRICE = 750; // Placeholder (e.g., 750 EGP for 3 courses instead of 900 EGP)

/**
 * Early-bird / Exam-season offer configuration.
 * Set EARLY_BIRD_END_DATE to an ISO date string in the future (e.g. '2026-11-15T23:59:59+02:00')
 * and EARLY_BIRD_PRICE to the offer price in EGP (e.g. 250).
 * Leave EARLY_BIRD_END_DATE as '' (empty) when no seasonal offer is active.
 * The offer automatically disappears once EARLY_BIRD_END_DATE is in the past.
 */
export const EARLY_BIRD_PRICE = 250; // Placeholder offer price in EGP
export const EARLY_BIRD_END_DATE: string = ''; // Placeholder ISO date, e.g. '2026-11-15' (empty = hidden)
export const EARLY_BIRD_LABEL_SUFFIX = 'قبل الميد';

/**
 * Referral discount configuration (UI & data model plan).
 * Placeholder discount percentage or EGP amount for student referrals.
 */
export const REFERRAL_DISCOUNT_EGP = 50;

/**
 * Founder / "Why trust us" placeholder copy on the homepage.
 * Edit this text with the founder's real story and background.
 */
export const FOUNDER_TRUST_CONFIG = {
  eyebrow: 'ليه تثق في Codeera - كوديرا؟',
  title: 'منصة مبنية من مبرمج فاهم طبيعة دراستك في حاسبات الأزهر',
  headlineAr: 'مبنية بواسطة مبرمج زميلك فاهم اللي محتاجه',
  roleAr: 'مؤسس ومطور منصة Codeera - كوديرا',
  storyPlaceholder:
    '[مساحة مخصصة لقصة مؤسس المنصة: اكتب هنا نبذة قصيرة عنك كمطور وباني المنصة، تجربتك مع مواد الكلية، وهدفك إن زمايلك يلاقوا شرح منظم وواضح بدل التشتت بين المصادر.]',
  refundSummary:
    'ضمان استرداد واضح: لو إيصال الدفع اترفض لأي سبب، أو لو طلبت استرداد خلال 7 أيام من التفعيل (وقبل مشاهدة أكتر من 20% من المحتوى)، بيتم رد المبلغ بالكامل على نفس محفظتك.',
  paymentSafetyNote: 'بتدفع على رقم رسمي وبتستلم تأكيد بعد مراجعة الإيصال.',
  disclaimer: 'Codeera منصة مستقلة وغير تابعة رسمياً لجامعة الأزهر.'
};

/**
 * Short refund guarantee line used on course pages and checkout.
 * Must stay 100% consistent with the /refund policy page.
 */
export const REFUND_GUARANTEE_SHORT =
  'ضمان استرداد كامل المبلغ لو الإيصال اترفض، أو خلال 7 أيام من التفعيل قبل مشاهدة أكتر من 20% من المقرر.';

/**
 * Calculate bundle savings in EGP compared to buying courses individually.
 */
export function getBundleSavingEgp(singleCoursePriceEgp: number = COURSE_PRICE_EGP): number {
  const separateTotal = singleCoursePriceEgp * BUNDLE_COURSES_COUNT;
  return Math.max(0, separateTotal - BUNDLE_PRICE);
}

/**
 * Check whether the early-bird offer is currently active (date is valid and in the future).
 */
export function isEarlyBirdActive(now: Date = new Date()): boolean {
  if (!EARLY_BIRD_END_DATE || !EARLY_BIRD_END_DATE.trim()) return false;
  const parsed = new Date(EARLY_BIRD_END_DATE);
  if (Number.isNaN(parsed.getTime())) return false;
  return parsed.getTime() > now.getTime() && EARLY_BIRD_PRICE > 0;
}

/**
 * Format the early-bird end date in readable Egyptian Arabic.
 */
export function formatEarlyBirdEndDate(dateStr: string = EARLY_BIRD_END_DATE): string {
  if (!dateStr) return '';
  const parsed = new Date(dateStr);
  if (Number.isNaN(parsed.getTime())) return dateStr;
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
