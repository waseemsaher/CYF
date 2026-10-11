/**
 * Real student testimonials data file.
 * IMPORTANT:
 * - Do NOT invent testimonials.
 * - Leave `testimonials` empty (`[]`) by default; the Testimonials section on the homepage
 *   automatically stays hidden when this array is empty.
 * - Only add real student feedback with their explicit consent, using name initials only.
 */
export interface TestimonialItem {
  id: string;
  /** Student initials only (e.g. "م. أ.") */
  initials: string;
  /** Academic year and department (e.g. "فرقة أولى — علوم الحاسب") */
  yearAndDept: string;
  /** Course name (e.g. "الرياضيات المتقطعة") */
  courseName?: string;
  /** Verbatim student quote (consented) */
  quote: string;
}

export const testimonials: TestimonialItem[] = [];

