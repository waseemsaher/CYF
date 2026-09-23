import { apiGet, apiPost, apiPut, apiDelete } from './client';

export type AdminOverviewData = {
  pending_payments_count: number;
  active_enrollments_count: number;
  term_revenue_cents: number;
  total_students_count: number;
  recent_activity: {
    id: number;
    description: string;
    log_name: string;
    causer_name: string;
    created_at: string;
  }[];
};

export type TeacherEarnings = {
  earned_cents: number;
  paid_out_cents: number;
  balance_cents: number;
};

export type TeacherDashboardData = {
  earnings: TeacherEarnings;
  courses: {
    id: number;
    slug: string;
    title: { ar: string; en: string };
    teacher_share_percent: number | null;
    active_students_count: number;
  }[];
  payouts: {
    id: number;
    amount_cents: number;
    paid_at: string;
    note: string | null;
  }[];
};

export type AdminCourse = {
  id: number;
  slug: string;
  title: { ar: string; en: string };
  description: { ar: string; en: string };
  cover_image_path?: string | null;
  price_cents: number;
  status: 'draft' | 'published' | 'archived';
  telegram_chat_id?: number | null;
  telegram_invite_link?: string | null;
  teacher_share_percent?: number | null;
  sort_order?: number;
  audiences?: { academic_year_id: number; department_id: number }[];
};

export type AdminStudent = {
  id: number;
  name: string;
  email: string;
  branch: string | null;
  academic_year: string | null;
  department: string | null;
  telegram_username: string | null;
  phone: string | null;
  is_active: boolean;
  active_enrollments_count?: number;
  created_at: string;
};

export function getAdminOverview(fetcher: typeof fetch = fetch) {
  return apiGet<{ data: AdminOverviewData }>(fetcher, '/admin/overview');
}

export function getTeacherDashboard(fetcher: typeof fetch = fetch) {
  return apiGet<{ data: TeacherDashboardData }>(fetcher, '/teacher/dashboard');
}

export function getContentBlock(fetcher: typeof fetch = fetch, key: string) {
  return apiGet<{ data: { key: string; content: { ar: string; en: string } } }>(
    fetcher,
    `/content-blocks/${key}`
  );
}

export function getAdminCourses(fetcher: typeof fetch = fetch, page = 1) {
  return apiGet<{ data: AdminCourse[]; meta: { current_page: number; last_page: number; total: number } }>(
    fetcher,
    `/admin/courses?page=${page}`
  );
}

export function createAdminCourse(fetcher: typeof fetch = fetch, data: Record<string, unknown>) {
  return apiPost<{ data: AdminCourse }>(fetcher, '/admin/courses', data);
}

export function updateAdminCourse(fetcher: typeof fetch = fetch, courseId: number, data: Record<string, unknown>) {
  return apiPut<{ data: AdminCourse }>(fetcher, `/admin/courses/${courseId}`, data);
}

export function deleteAdminCourse(fetcher: typeof fetch = fetch, courseId: number) {
  return apiDelete(fetcher, `/admin/courses/${courseId}`);
}

export function getAdminStudents(fetcher: typeof fetch = fetch, page = 1, search = '') {
  return apiGet<{ data: AdminStudent[]; meta: { current_page: number; last_page: number; total: number } }>(
    fetcher,
    `/admin/students?page=${page}${search ? `&search=${encodeURIComponent(search)}` : ''}`
  );
}

