import { env } from '$env/dynamic/public';

const apiBaseUrl = env.PUBLIC_API_BASE_URL || 'http://localhost:8000/api/v1';

const TOKEN_KEY = 'fcai_auth_token';

export function getAuthToken(): string | null {
  if (typeof window !== 'undefined' && window.localStorage) {
    return localStorage.getItem(TOKEN_KEY);
  }
  return null;
}

export function setAuthToken(token: string): void {
  if (typeof window !== 'undefined' && window.localStorage) {
    localStorage.setItem(TOKEN_KEY, token);
  }
}

export function clearAuthToken(): void {
  if (typeof window !== 'undefined' && window.localStorage) {
    localStorage.removeItem(TOKEN_KEY);
  }
}

export async function apiGet<T>(fetcher: typeof fetch, path: string): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
  };

  const token = getAuthToken();
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const response = await fetcher(`${apiBaseUrl}${path}`, {
    headers,
    credentials: 'include',
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message || `API request failed with status ${response.status}`);
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

  const token = getAuthToken();
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
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