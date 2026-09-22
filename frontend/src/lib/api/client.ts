import { env } from '$env/dynamic/public';

const apiBaseUrl = env.PUBLIC_API_BASE_URL || 'http://localhost:8000/api/v1';

export async function apiGet<T>(fetcher: typeof fetch, path: string): Promise<T> {
  const response = await fetcher(`${apiBaseUrl}${path}`, {
    headers: {
      Accept: 'application/json',
    },
    credentials: 'include',
  });

  if (!response.ok) {
    throw new Error(`API request failed with status ${response.status}`);
  }

  return response.json() as Promise<T>;
}

export async function apiPost<T>(fetcher: typeof fetch, path: string, body?: unknown): Promise<T> {
  const isFormData = typeof FormData !== 'undefined' && body instanceof FormData;
  const headers: Record<string, string> = {
    Accept: 'application/json',
  };

  if (!isFormData) {
    headers['Content-Type'] = 'application/json';
  }

  const response = await fetcher(`${apiBaseUrl}${path}`, {
    method: 'POST',
    headers,
    body: isFormData ? (body as FormData) : body !== undefined ? JSON.stringify(body) : undefined,
    credentials: 'include',
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message || `API request failed with status ${response.status}`);
  }

  return response.json() as Promise<T>;
}