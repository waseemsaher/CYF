import { apiGet, apiPost, apiPut, apiDelete } from './client';

export type AdminCouponCourse = {
  id: number;
  slug: string;
  title: { ar: string; en?: string };
};

export type AdminCouponRedemption = {
  id: number;
  user: {
    id: number;
    name: string;
    email: string;
    branch?: string;
  } | null;
  payment: {
    id: number;
    amount_due_cents: number;
    status: string;
    created_at: string;
  } | null;
  created_at: string;
};

export type AdminCoupon = {
  id: number;
  code: string;
  type: 'percent' | 'fixed';
  value: number;
  scope: 'all' | 'courses';
  max_redemptions: number | null;
  max_redemptions_per_user: number;
  starts_at: string | null;
  ends_at: string | null;
  is_active: boolean;
  times_redeemed: number;
  courses?: AdminCouponCourse[];
  redemptions?: AdminCouponRedemption[];
  created_at: string;
  updated_at?: string;
};

export type CouponPayload = {
  code: string;
  type: 'percent' | 'fixed';
  value: number;
  scope: 'all' | 'courses';
  course_ids?: number[];
  max_redemptions?: number | null;
  max_redemptions_per_user?: number;
  starts_at?: string | null;
  ends_at?: string | null;
  is_active?: boolean;
};

export type ValidateCouponResponse = {
  valid: boolean;
  coupon: {
    id: number;
    code: string;
    type: 'percent' | 'fixed';
    value: number;
  };
  pricing: {
    list_price_cents: number;
    discount_cents: number;
    amount_due_cents: number;
    discount_id: number | null;
    coupon_id: number | null;
  };
};

export function validateCoupon(fetcher: typeof fetch = fetch, courseId: number, code: string) {
  return apiPost<ValidateCouponResponse>(fetcher, '/coupons/validate', {
    course_id: courseId,
    code,
  });
}

export function getAdminCoupons(fetcher: typeof fetch = fetch, page = 1) {
  return apiGet<{
    data: AdminCoupon[];
    meta: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
  }>(fetcher, `/admin/coupons?page=${page}`);
}

export function getAdminCoupon(fetcher: typeof fetch = fetch, id: number) {
  return apiGet<{ data: AdminCoupon }>(fetcher, `/admin/coupons/${id}`);
}

export function createAdminCoupon(fetcher: typeof fetch = fetch, data: CouponPayload) {
  return apiPost<{ data: AdminCoupon }>(fetcher, '/admin/coupons', data);
}

export function updateAdminCoupon(fetcher: typeof fetch = fetch, id: number, data: CouponPayload) {
  return apiPut<{ data: AdminCoupon }>(fetcher, `/admin/coupons/${id}`, data);
}

export function deleteAdminCoupon(fetcher: typeof fetch = fetch, id: number) {
  return apiDelete<void>(fetcher, `/admin/coupons/${id}`);
}

export function toggleAdminCouponActive(fetcher: typeof fetch = fetch, id: number) {
  return apiPost<{ data: AdminCoupon }>(fetcher, `/admin/coupons/${id}/toggle-active`, {});
}
