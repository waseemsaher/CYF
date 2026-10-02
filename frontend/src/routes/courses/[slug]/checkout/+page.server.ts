import type { PageServerLoad } from './$types';
import { getCourse, getTerms, type TermOption } from '$lib/api/catalog';
import { error } from '@sveltejs/kit';

export const load: PageServerLoad = async ({ fetch, params }) => {
  try {
    const [courseData, termsRes] = await Promise.all([
      getCourse(fetch, params.slug),
      getTerms(fetch).catch(() => ({ data: [] }))
    ]);

    const terms: TermOption[] = termsRes.data || [];
    const currentTerm = terms.find((t: TermOption) => Boolean(t.is_current)) || terms[0] || null;

    return {
      course: courseData.data,
      currentTerm
    };
  } catch {
    throw error(404, 'Course not found');
  }
};
