import { apiGet, apiPost } from './client';

export interface TelegramStatus {
  is_linked: boolean;
  telegram_user_id?: number | null;
  telegram_username?: string | null;
}

export interface TelegramLinkData {
  token: string;
  deep_link: string;
  expires_at: string;
}

export async function getTelegramStatus(fetcher: typeof fetch = fetch): Promise<TelegramStatus> {
  const res = await apiGet<{ data: TelegramStatus }>(fetcher, '/telegram/status');
  return res.data;
}

export async function generateTelegramLink(fetcher: typeof fetch = fetch): Promise<TelegramLinkData> {
  const res = await apiPost<{ data: TelegramLinkData }>(fetcher, '/telegram/link-token', {});
  return res.data;
}

export async function unlinkTelegram(fetcher: typeof fetch = fetch): Promise<{ message: string }> {
  return apiPost<{ message: string }>(fetcher, '/telegram/unlink', {});
}
