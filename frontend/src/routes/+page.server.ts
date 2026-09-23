import type { PageServerLoad } from './$types';
import { getCourses } from '$lib/api/catalog';
import { getContentBlock } from '$lib/api/admin';

export const load: PageServerLoad = async ({ fetch }) => {
  try {
    const [coursesRes, faqBlock, whyUsBlock] = await Promise.all([
      getCourses(fetch, new URLSearchParams()).catch(() => ({ data: [] })),
      getContentBlock(fetch, 'landing.faq').catch(() => null),
      getContentBlock(fetch, 'landing.why_us').catch(() => null)
    ]);

    return {
      courses: coursesRes.data.slice(0, 3),
      faq: faqBlock?.data?.content ?? null,
      whyUs: whyUsBlock?.data?.content ?? null,
      error: ''
    };
  } catch {
    return {
      courses: [],
      faq: null,
      whyUs: null,
      error: 'تعذر تحميل الدورات الآن. يمكنك تصفحها لاحقًا.'
    };
  }
};