import { apiGet, apiPost } from './client';

export type Translation = {
  ar: string;
  en: string;
};

export type Payment = {
  id: number;
  user_id: number;
  course_id: number;
  term_id: number;
  method: string;
  list_price_cents: number;
  discount_cents: number;
  amount_due_cents: number;
  sender_identifier: string;
  student_note: string | null;
  status: 'pending' | 'approved' | 'rejected' | 'cancelled';
  rejection_reason: string | null;
  teacher_share_percent: number | null;
  teacher_share_cents: number | null;
  platform_share_cents: number | null;
  reviewed_at: string | null;
  has_duplicate_proof: boolean;
  course?: {
    id: number;
    slug: string;
    title: Translation;
  };
  user?: {
    id: number;
    name: string;
    email: string;
    branch: string;
    academic_year: string;
    department: string;
  };
  created_at: string;
};

type PaginatedResponse<T> = {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  counts?: Record<string, number>;
};

export function getMyPayments(fetcher: typeof fetch, page = 1) {
  return apiGet<PaginatedResponse<Payment>>(fetcher, `/payments?page=${page}`);
}

export function getAdminPayments(fetcher: typeof fetch, status = 'pending', page = 1) {
  return apiGet<PaginatedResponse<Payment>>(
    fetcher,
    `/admin/payments?status=${encodeURIComponent(status)}&page=${page}`
  );
}

export function submitPayment(fetcher: typeof fetch, formData: FormData) {
  return apiPost<{ data: Payment }>(fetcher, '/payments', formData);
}

export function approveAdminPayment(fetcher: typeof fetch, id: number) {
  return apiPost<{ data: Payment }>(fetcher, `/admin/payments/${id}/approve`);
}

export function rejectAdminPayment(fetcher: typeof fetch, id: number, reason: string) {
  return apiPost<{ data: Payment }>(fetcher, `/admin/payments/${id}/reject`, {
    rejection_reason: reason,
  });
}

