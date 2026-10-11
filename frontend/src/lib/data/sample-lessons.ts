/**
 * Free Sample Lesson ("درس تجريبي") configuration.
 * Optional, data-driven map by course slug.
 * If a course has no configured sample lesson here (and no `is_free` preview item in its curriculum),
 * the sample lesson section stays hidden automatically. Do NOT invent content.
 *
 * Example entry when you are ready to add a sample lesson link:
 * 'cpp': {
 *   titleAr: 'مقدمة في أساسيات لغة ++C وأول برنامج عملي',
 *   durationText: '25 دقيقة',
 *   videoUrl: 'https://www.youtube.com/watch?v=YOUR_VIDEO_ID',
 *   descriptionAr: 'جرب أسلوب الشرح قبل ما تشترك في المقرر الكامل.'
 * }
 */
export interface SampleLessonConfig {
  titleAr: string;
  descriptionAr?: string;
  durationText?: string;
  videoUrl: string;
}

export const SAMPLE_LESSONS_BY_COURSE_SLUG: Record<string, SampleLessonConfig> = {
  // Placeholder: add real sample lesson URLs keyed by course slug (e.g. 'cpp', 'discrete-mathematics')
};

/**
 * Optional homepage featured sample lesson.
 * Leave null until a real sample video URL is provided.
 */
export const HOME_SAMPLE_LESSON: (SampleLessonConfig & { courseSlug?: string; courseNameAr?: string }) | null = null;
