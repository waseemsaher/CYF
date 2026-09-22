import type { PageServerLoad } from './$types';
import { getCourse } from '$lib/api/catalog';
import { error } from '@sveltejs/kit';

export const load: PageServerLoad = async ({ fetch, params }) => {
  try {
    const courseData = await getCourse(fetch, params.slug);
    return {
      course: courseData.data
    };
  } catch {
    throw error(404, 'Course not found');
  }
};
