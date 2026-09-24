import { env } from '$env/dynamic/public';

export function getApiBaseUrl(): string {
  if (env.PUBLIC_API_BASE_URL) {
    return env.PUBLIC_API_BASE_URL;
  }
  if (typeof window !== 'undefined' && window.location) {
    return `${window.location.protocol}//${window.location.hostname}:8000/api/v1`;
  }
  return 'http://127.0.0.1:8000/api/v1';
}

const TOKEN_KEY = 'codeera_auth_token';
const LEGACY_TOKEN_KEYS = ['coderaa_auth_token', 'fcai_auth_token'];

export function getAuthToken(): string | null {
  if (typeof window !== 'undefined' && window.localStorage) {
    const primary = localStorage.getItem(TOKEN_KEY);
    if (primary) return primary;
    for (const legacy of LEGACY_TOKEN_KEYS) {
      const val = localStorage.getItem(legacy);
      if (val) return val;
    }
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
    for (const legacy of LEGACY_TOKEN_KEYS) {
      localStorage.removeItem(legacy);
    }
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

  const response = await fetcher(`${getApiBaseUrl()}${path}`, {
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

  const response = await fetcher(`${getApiBaseUrl()}${path}`, {
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

export async function apiPut<T>(fetcher: typeof fetch, path: string, body?: unknown): Promise<T> {
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

  const response = await fetcher(`${getApiBaseUrl()}${path}`, {
    method: 'PUT',
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

export async function apiDelete<T = unknown>(fetcher: typeof fetch, path: string): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
  };

  const token = getAuthToken();
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const response = await fetcher(`${getApiBaseUrl()}${path}`, {
    method: 'DELETE',
    headers,
    credentials: 'include',
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message || `API request failed with status ${response.status}`);
  }

  if (response.status === 204) {
    return null as unknown as T;
  }

  return response.json().catch(() => null) as Promise<T>;
}