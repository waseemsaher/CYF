import { apiGet } from './client';

export type Translation = {
  ar: string;
  en: string;
};

export type Course = {
  id: number;
  slug: string;
  title: Translation;
  description: Translation;
  price_cents: number;
  list_price_cents: number;
  discount_cents: number;
  amount_due_cents: number;
  discount_id: number | null;
};

export type ReferenceOption = {
  id: number;
  name: Translation;
  sort_order: number;
};

type CollectionResponse<T> = { data: T[] };

type PaginatedResponse<T> = CollectionResponse<T> & {
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};

export function getCourses(fetcher: typeof fetch, params: URLSearchParams) {
  const query = params.toString();
  return apiGet<PaginatedResponse<Course>>(fetcher, `/courses${query ? `?${query}` : ''}`);
}

export function getCourse(fetcher: typeof fetch, slug: string) {
  return apiGet<{ data: Course }>(fetcher, `/courses/${encodeURIComponent(slug)}`);
}

export function getAcademicYears(fetcher: typeof fetch) {
  return apiGet<CollectionResponse<ReferenceOption>>(fetcher, '/reference/academic-years');
}

export function getDepartments(fetcher: typeof fetch) {
  return apiGet<CollectionResponse<ReferenceOption>>(fetcher, '/reference/departments');
}