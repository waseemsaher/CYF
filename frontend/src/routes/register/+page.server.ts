import type { PageServerLoad } from './$types';
import { getAcademicYears, getDepartments } from '$lib/api/catalog';

export const load: PageServerLoad = async ({ fetch }) => {
  try {
    const [yearsRes, deptsRes] = await Promise.all([
      getAcademicYears(fetch).catch(() => ({ data: [] })),
      getDepartments(fetch).catch(() => ({ data: [] }))
    ]);

    return {
      academicYears: yearsRes.data,
      departments: deptsRes.data
    };
  } catch {
    return {
      academicYears: [],
      departments: []
    };
  }
};
