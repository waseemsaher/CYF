import { error } from '@sveltejs/kit';
import type { PageServerLoad } from './$types';
import { getCourse } from '$lib/api/catalog';
import { getCourseContent } from '$lib/api/learning';
import { getPublicCtas } from '$lib/api/ctas';

export const load: PageServerLoad = async ({ fetch, params }) => {
  try {
    const [courseRes, contentRes, ctas] = await Promise.all([
      getCourse(fetch, params.slug),
      getCourseContent(fetch, params.slug).catch(() => null),
      getPublicCtas(fetch)
    ]);
    return {
      course: courseRes.data,
      content: contentRes?.data ?? null,
      ctas
    };
  } catch {
    error(404, 'المقرر غير موجود');
  }
};