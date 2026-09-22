import { error } from '@sveltejs/kit';
import type { PageServerLoad } from './$types';
import { getCourse } from '$lib/api/catalog';

export const load: PageServerLoad = async ({ fetch, params }) => {
  try {
    const response = await getCourse(fetch, params.slug);
    return { course: response.data };
  } catch {
    error(404, 'الدورة غير موجودة');
  }
};