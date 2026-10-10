import { error } from '@sveltejs/kit';
import type { PageServerLoad } from './$types';
import { getCourse } from '$lib/api/catalog';
import { getCourseContent } from '$lib/api/learning';

export const load: PageServerLoad = async ({ fetch, params }) => {
  try {
    const [courseRes, contentRes] = await Promise.all([
      getCourse(fetch, params.slug),
      getCourseContent(fetch, params.slug).catch(() => null),
    ]);
    return {
      course: courseRes.data,
      content: contentRes?.data ?? null,
    };
  } catch {
    error(404, 'الدورة غير موجودة');
  }
};