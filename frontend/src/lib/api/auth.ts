import { apiGet, apiPost, setAuthToken, clearAuthToken, getAuthToken } from './client';

export type UserProfile = {
  id: number;
  name: string;
  email: string;
  branch: 'azhar_boys' | 'azhar_girls';
  academic_year: string;
  department: string;
  telegram_username: string | null;
  phone: string | null;
  roles?: string[];
  role?: string;
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

export async function login(fetcher: typeof fetch = fetch, credentials: LoginCredentials) {
  const res = await apiPost<AuthResponse>(fetcher, '/login', credentials);
  if (res.data?.token) {
    setAuthToken(res.data.token);
  }
  return res;
}

export async function register(fetcher: typeof fetch = fetch, data: RegisterData) {
  const res = await apiPost<AuthResponse>(fetcher, '/register', data);
  if (res.data?.token) {
    setAuthToken(res.data.token);
  }
  return res;
}

export function logout(): void {
  clearAuthToken();
}

export function isAuthenticated(): boolean {
  return getAuthToken() !== null;
}

export function getCurrentUser(fetcher: typeof fetch = fetch) {
  return apiGet<{ data: { user: UserProfile } }>(fetcher, '/me');
}
