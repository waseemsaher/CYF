import type { PageServerLoad } from './$types';
import { getCourses } from '$lib/api/catalog';

export const load: PageServerLoad = async ({ fetch }) => {
  try {
    const response = await getCourses(fetch, new URLSearchParams());

    return {
      courses: response.data.slice(0, 3),
      error: ''
    };
  } catch {
    return {
      courses: [],
      error: 'تعذر تحميل الدورات الآن. يمكنك تصفحها لاحقًا.'
    };
  }
};