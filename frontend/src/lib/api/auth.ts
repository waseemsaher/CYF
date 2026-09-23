import { writable } from 'svelte/store';
import { apiGet, apiPost, setAuthToken, clearAuthToken, getAuthToken } from './client';

export type UserProfile = {
  id: number;
  name: string;
  email: string;
  branch: 'azhar_boys' | 'azhar_girls' | null;
  academic_year: string | null;
  department: string | null;
  telegram_username: string | null;
  phone: string | null;
  roles?: string[];
  role?: string;
  locale?: string;
  email_verified_at?: string | null;
  is_active?: boolean;
};

export type AuthResponse = {
  data: {
    user: UserProfile;
    token: string;
  };
};

export type LoginCredentials = {
  email: string;
  password: string;
};

export type RegisterData = {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  branch: 'azhar_boys' | 'azhar_girls';
  academic_year: string;
  department: string;
  telegram_username?: string;
  phone?: string;
};

// Global reactive user state accessible across all layouts and pages
export const currentUser = writable<UserProfile | null>(null);
export const authChecked = writable<boolean>(false);

export async function getCurrentUser(fetcher: typeof fetch = fetch) {
  return apiGet<{ data: { user: UserProfile } }>(fetcher, '/me');
}

export async function refreshUser(fetcher: typeof fetch = fetch): Promise<UserProfile | null> {
  const token = getAuthToken();
  if (!token) {
    currentUser.set(null);
    authChecked.set(true);
    return null;
  }

  try {
    const res = await getCurrentUser(fetcher);
    const user = res.data?.user ?? null;
    currentUser.set(user);
    authChecked.set(true);
    return user;
  } catch (err: unknown) {
    const message = err instanceof Error ? err.message : String(err);
    if (message.includes('401') || message.includes('Unauthenticated')) {
      clearAuthToken();
      currentUser.set(null);
    }
    authChecked.set(true);
    return null;
  }
}

export async function login(fetcher: typeof fetch = fetch, credentials: LoginCredentials) {
  const res = await apiPost<AuthResponse>(fetcher, '/login', credentials);
  if (res.data?.token) {
    setAuthToken(res.data.token);
    if (res.data.user) {
      currentUser.set(res.data.user);
      authChecked.set(true);
    } else {
      await refreshUser(fetcher);
    }
  }
  return res;
}

export async function register(fetcher: typeof fetch = fetch, data: RegisterData) {
  const res = await apiPost<AuthResponse>(fetcher, '/register', data);
  if (res.data?.token) {
    setAuthToken(res.data.token);
    if (res.data.user) {
      currentUser.set(res.data.user);
      authChecked.set(true);
    } else {
      await refreshUser(fetcher);
    }
  }
  return res;
}

export function logout(): void {
  clearAuthToken();
  currentUser.set(null);
  authChecked.set(true);
}

export function isAuthenticated(): boolean {
  return getAuthToken() !== null;
}
