import type { PageServerLoad } from './$types';
import { getCourses } from '$lib/api/catalog';
import { getContentBlock } from '$lib/api/admin';
import { getPublicCtas, DEFAULT_CTA_BLOCKS } from '$lib/api/ctas';

export const load: PageServerLoad = async ({ fetch }) => {
  try {
    const [coursesRes, faqBlock, whyUsBlock, ctas] = await Promise.all([
      getCourses(fetch, new URLSearchParams()).catch(() => ({ data: [], meta: { total: 0, current_page: 1, last_page: 1 } })),
      getContentBlock(fetch, 'landing.faq').catch(() => null),
      getContentBlock(fetch, 'landing.why_us').catch(() => null),
      getPublicCtas(fetch)
    ]);

    return {
      courses: coursesRes.data.slice(0, 3),
      totalCoursesCount: coursesRes.meta?.total ?? coursesRes.data.length,
      ctas,
      faq: faqBlock?.data?.content ?? null,
      whyUs: whyUsBlock?.data?.content ?? null,
      error: ''
    };
  } catch {
    return {
      courses: [],
      totalCoursesCount: 0,
      ctas: DEFAULT_CTA_BLOCKS,
      faq: null,
      whyUs: null,
      error: 'تعذر تحميل المقررات دلوقتي. تقدر تجرب تاني بعد شوية.'
    };
  }
};