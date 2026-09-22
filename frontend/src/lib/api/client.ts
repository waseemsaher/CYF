import { env } from '$env/dynamic/public';

const apiBaseUrl = env.PUBLIC_API_BASE_URL || 'http://localhost:8000/api/v1';

export async function apiGet<T>(fetcher: typeof fetch, path: string): Promise<T> {
  const response = await fetcher(`${apiBaseUrl}${path}`);

  if (!response.ok) {
    throw new Error(`API request failed with status ${response.status}`);
  }

  return response.json() as Promise<T>;
}