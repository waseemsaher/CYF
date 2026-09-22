import type { PageServerLoad } from './$types';
import { getAcademicYears, getCourses, getDepartments } from '$lib/api/catalog';

export const load: PageServerLoad = async ({ fetch, url }) => {
  const query = new URLSearchParams();
  const academicYearId = url.searchParams.get('academic_year_id');
  const departmentId = url.searchParams.get('department_id');
  const page = url.searchParams.get('page');

  if (academicYearId) query.set('academic_year_id', academicYearId);
  if (departmentId) query.set('department_id', departmentId);
  if (page) query.set('page', page);

  try {
    const [courses, academicYears, departments] = await Promise.all([
      getCourses(fetch, query),
      getAcademicYears(fetch),
      getDepartments(fetch)
    ]);

    return {
      courses: courses.data,
      pagination: courses.meta,
      academicYears: academicYears.data,
      departments: departments.data,
      selectedAcademicYear: academicYearId ?? '',
      selectedDepartment: departmentId ?? '',
      error: ''
    };
  } catch {
    return {
      courses: [],
      pagination: { current_page: 1, last_page: 1, per_page: 12, total: 0 },
      academicYears: [],
      departments: [],
      selectedAcademicYear: academicYearId ?? '',
      selectedDepartment: departmentId ?? '',
      error: 'تعذر تحميل الدورات الآن. حاول مرة أخرى.'
    };
  }
};