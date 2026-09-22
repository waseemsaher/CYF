import { apiGet, apiPost } from './client';

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
