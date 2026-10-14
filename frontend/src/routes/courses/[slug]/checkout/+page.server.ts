import type { PageServerLoad } from './$types';
import { getCourse, getTerms, type TermOption } from '$lib/api/catalog';
import { getPaymentMethods, type PaymentMethod } from '$lib/api/payments';
import { error } from '@sveltejs/kit';

export const load: PageServerLoad = async ({ fetch, params }) => {
  try {
    const [courseData, termsRes, paymentMethodsRes] = await Promise.all([
      getCourse(fetch, params.slug),
      getTerms(fetch).catch(() => ({ data: [] })),
      getPaymentMethods(fetch).catch(() => ({ data: [] as PaymentMethod[] }))
    ]);

    const terms: TermOption[] = termsRes.data || [];
    const currentTerm = terms.find((t: TermOption) => Boolean(t.is_current)) || terms[0] || null;

    return {
      course: courseData.data,
      currentTerm,
      paymentMethods: (paymentMethodsRes.data as PaymentMethod[]) || []
    };
  } catch {
    throw error(404, 'Course not found');
  }
};
