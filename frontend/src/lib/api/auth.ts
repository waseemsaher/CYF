import { apiGet, apiPost } from './client';

export type UserProfile = {
  id: number;
  name: string;
  email: string;
  branch: 'azhar_boys' | 'azhar_girls';
  academic_year: string;
  department: string;
  telegram_username: string | null;
  phone: string | null;
  roles: string[];
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

export function login(fetcher: typeof fetch = fetch, credentials: LoginCredentials) {
  return apiPost<AuthResponse>(fetcher, '/login', credentials);
}

export function register(fetcher: typeof fetch = fetch, data: RegisterData) {
  return apiPost<AuthResponse>(fetcher, '/register', data);
}

export function getCurrentUser(fetcher: typeof fetch = fetch) {
  return apiGet<{ data: { user: UserProfile } }>(fetcher, '/me');
}
