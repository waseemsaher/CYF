import { apiGet, apiPost, apiPut } from './client';
import {
  DEFAULT_CTA_BLOCKS,
  type CtaBlock,
  type CtaStyle
} from './ctas-core';

export * from './ctas-core';

// Short-lived in-memory SSR cache (30 seconds TTL) so layout/page SSR loads stay fast
let ssrCache: { data: Record<string, CtaBlock>; expiresAt: number } | null = null;
const SSR_CACHE_TTL_MS = 30_000;

export async function getPublicCtas(
  fetcher: typeof fetch = fetch,
  bypassCache = false
): Promise<Record<string, CtaBlock>> {
  const now = Date.now();
  if (!bypassCache && ssrCache && ssrCache.expiresAt > now) {
    return ssrCache.data;
  }

  try {
    const res = await apiGet<{ data: Record<string, CtaBlock> }>(fetcher, '/ctas');
    const data = res?.data ?? {};
    ssrCache = { data, expiresAt: now + SSR_CACHE_TTL_MS };
    return data;
  } catch {
    return DEFAULT_CTA_BLOCKS;
  }
}

export function clearCtaCache(): void {
  ssrCache = null;
}

export function getAdminCtas(fetcher: typeof fetch = fetch) {
  return apiGet<{ data: CtaBlock[] }>(fetcher, '/admin/ctas');
}

export function updateAdminCta(
  fetcher: typeof fetch = fetch,
  key: string,
  payload: {
    label_ar: string;
    headline_ar?: string | null;
    subtext_ar?: string | null;
    url: string;
    style: CtaStyle;
    is_active: boolean;
    starts_at?: string | null;
    ends_at?: string | null;
    open_in_new_tab: boolean;
  }
) {
  clearCtaCache();
  return apiPut<{ data: CtaBlock }>(fetcher, `/admin/ctas/${encodeURIComponent(key)}`, payload);
}

export function toggleAdminCta(fetcher: typeof fetch = fetch, key: string) {
  clearCtaCache();
  return apiPost<{ data: CtaBlock }>(fetcher, `/admin/ctas/${encodeURIComponent(key)}/toggle`);
}
