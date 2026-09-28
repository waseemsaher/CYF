import type { PageServerLoad } from './$types';
import { getCourse } from '$lib/api/catalog';
import { error } from '@sveltejs/kit';

export const load: PageServerLoad = async ({ fetch, params }) => {
  try {
    const [courseData, termsRes] = await Promise.all([
      getCourse(fetch, params.slug),
      fetch('/api/v1/terms')
        .then((r) => (r.ok ? r.json() : { data: [] }))
        .catch(() => ({ data: [] }))
    ]);

    const terms = termsRes.data || [];
    const currentTerm = terms.find((t: { is_current: boolean }) => t.is_current) || terms[0] || null;

    return {
      course: courseData.data,
      currentTerm
    };
  } catch {
    throw error(404, 'Course not found');
  }
};
