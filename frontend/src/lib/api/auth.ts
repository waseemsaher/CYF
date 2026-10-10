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
  telegram_is_linked?: boolean;
  phone: string | null;
  roles?: string[];
  role?: string;
  locale?: string;
  email_verified_at?: string | null;
  is_active?: boolean;
  enrolled_courses?: Array<{
    id: number;
    slug: string;
    title: { ar: string; en?: string };
  }>;
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

const USER_KEY = 'codeera_user_profile';

export function getStoredUser(): UserProfile | null {
  if (typeof window !== 'undefined' && window.localStorage) {
    try {
      const raw = localStorage.getItem(USER_KEY);
      if (raw) return JSON.parse(raw) as UserProfile;
    } catch (_) {}
  }
  return null;
}

export function setStoredUser(user: UserProfile | null): void {
  if (typeof window !== 'undefined' && window.localStorage) {
    if (user) {
      localStorage.setItem(USER_KEY, JSON.stringify(user));
    } else {
      localStorage.removeItem(USER_KEY);
    }
  }
}

export function isUnlinkedStudentUser(user: UserProfile | null | undefined): boolean {
  if (!user) return false;
  const role = (user.role || (user.roles && user.roles[0]) || '').toLowerCase();
  const isStaff = role === 'admin' || role === 'superadmin' || role === 'teacher';
  if (isStaff) return false;
  return user.telegram_is_linked !== true;
}

// Global reactive user state accessible across all layouts and pages
export const currentUser = writable<UserProfile | null>(getStoredUser());
export const authChecked = writable<boolean>(false);

export async function getCurrentUser(fetcher: typeof fetch = fetch) {
  return apiGet<{ data: { user: UserProfile } }>(fetcher, '/me');
}

export async function refreshUser(fetcher: typeof fetch = fetch): Promise<UserProfile | null> {
  const token = getAuthToken();
  if (!token) {
    setStoredUser(null);
    currentUser.set(null);
    authChecked.set(true);
    return null;
  }

  try {
    const res = await getCurrentUser(fetcher);
    const user = res.data?.user ?? null;
    if (user && user.telegram_is_linked === undefined) {
      const prev = getStoredUser();
      if (prev && prev.id === user.id && prev.telegram_is_linked === true) {
        user.telegram_is_linked = true;
      } else {
        try {
          const statusRes = await apiGet<{ data: { is_linked: boolean } }>(fetcher, '/telegram/status');
          user.telegram_is_linked = Boolean(statusRes.data?.is_linked);
        } catch (_) {}
      }
    }
    setStoredUser(user);
    currentUser.set(user);
    authChecked.set(true);
    return user;
  } catch (err: unknown) {
    const message = err instanceof Error ? err.message : String(err);
    if (message.includes('401') || message.includes('Unauthenticated')) {
      clearAuthToken();
      setStoredUser(null);
      currentUser.set(null);
    }
    authChecked.set(true);
    return null;
  }
}

export async function login(fetcher: typeof fetch = fetch, credentials: LoginCredentials) {
  clearAuthToken();
  setStoredUser(null);
  currentUser.set(null);

  const res = await apiPost<AuthResponse>(fetcher, '/login', credentials);
  if (res.data?.token) {
    setAuthToken(res.data.token);
    if (res.data.user) {
      setStoredUser(res.data.user);
      currentUser.set(res.data.user);
      authChecked.set(true);
    } else {
      await refreshUser(fetcher);
    }
  }
  return res;
}

export async function register(fetcher: typeof fetch = fetch, data: RegisterData) {
  clearAuthToken();
  setStoredUser(null);
  currentUser.set(null);

  const res = await apiPost<AuthResponse>(fetcher, '/register', data);
  if (res.data?.token) {
    setAuthToken(res.data.token);
    if (res.data.user) {
      setStoredUser(res.data.user);
      currentUser.set(res.data.user);
      authChecked.set(true);
    } else {
      await refreshUser(fetcher);
    }
  }
  return res;
}

export async function logout(fetcher: typeof fetch = fetch): Promise<void> {
  try {
    await apiPost(fetcher, '/logout');
  } catch (_) {
    // If backend request fails or offline, continue to clear local auth state
  } finally {
    clearAuthToken();
    setStoredUser(null);
    currentUser.set(null);
    authChecked.set(true);
  }
}

export function isAuthenticated(): boolean {
  return getAuthToken() !== null;
}

export type ResetPasswordCredentials = {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
};

export type MessageResponse = {
  message: string;
};

export async function forgotPassword(
  fetcher: typeof fetch = fetch,
  email: string
): Promise<MessageResponse> {
  return apiPost<MessageResponse>(fetcher, '/forgot-password', { email });
}

export async function resetPassword(
  fetcher: typeof fetch = fetch,
  credentials: ResetPasswordCredentials
): Promise<MessageResponse> {
  return apiPost<MessageResponse>(fetcher, '/reset-password', credentials);
}
