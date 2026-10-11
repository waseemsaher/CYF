import type { PageServerLoad } from './$types';
import { getCourses } from '$lib/api/catalog';
import { getContentBlock } from '$lib/api/admin';
import {
  getPublicCtas,
  DEFAULT_CTA_BLOCKS,
  resolveCtaSlot,
  resolveOptionalCtaSlot
} from '$lib/api/ctas';
import { HOME_SAMPLE_LESSON, getSampleLessonForCourse } from '$lib/data/sample-lessons';

export const load: PageServerLoad = async ({ fetch }) => {
  try {
    const [coursesRes, faqBlock, whyUsBlock, ctas] = await Promise.all([
      getCourses(fetch, new URLSearchParams()).catch(() => ({
        data: [],
        meta: { total: 0, current_page: 1, last_page: 1 }
      })),
      getContentBlock(fetch, 'landing.faq').catch(() => null),
      getContentBlock(fetch, 'landing.why_us').catch(() => null),
      getPublicCtas(fetch)
    ]);

    const courses = coursesRes.data.slice(0, 3);
    const configuredSampleLessons = [
      ...(HOME_SAMPLE_LESSON ? [HOME_SAMPLE_LESSON] : []),
      ...coursesRes.data
        .map((course: { slug: string }) => getSampleLessonForCourse(course.slug))
        .filter((item): item is NonNullable<typeof item> => Boolean(item))
    ];
    const hasSampleLessons = configuredSampleLessons.length > 0;

    return {
      courses,
      totalCoursesCount: coursesRes.meta?.total ?? coursesRes.data.length,
      ctas,
      configuredSampleLessons,
      hasSampleLessons,
      heroPrimaryCta: resolveCtaSlot(ctas, 'home_hero_primary'),
      heroSecondaryCta: resolveOptionalCtaSlot(ctas, 'home_hero_secondary', { hasSampleLessons }),
      homeBottomCta: resolveCtaSlot(ctas, 'home_bottom'),
      faq: faqBlock?.data?.content ?? null,
      whyUs: whyUsBlock?.data?.content ?? null,
      error: ''
    };
  } catch {
    const configuredSampleLessons = HOME_SAMPLE_LESSON ? [HOME_SAMPLE_LESSON] : [];
    const hasSampleLessons = configuredSampleLessons.length > 0;
    return {
      courses: [],
      totalCoursesCount: 0,
      ctas: DEFAULT_CTA_BLOCKS,
      configuredSampleLessons,
      hasSampleLessons,
      heroPrimaryCta: resolveCtaSlot(DEFAULT_CTA_BLOCKS, 'home_hero_primary'),
      heroSecondaryCta: resolveOptionalCtaSlot(DEFAULT_CTA_BLOCKS, 'home_hero_secondary', {
        hasSampleLessons
      }),
      homeBottomCta: resolveCtaSlot(DEFAULT_CTA_BLOCKS, 'home_bottom'),
      faq: null,
      whyUs: null,
      error: 'تعذر تحميل المقررات دلوقتي. تقدر تجرب تاني بعد شوية.'
    };
  }
};