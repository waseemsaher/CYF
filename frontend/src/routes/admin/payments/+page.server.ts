import type { PageServerLoad } from './$types';

export const load: PageServerLoad = async () => {
  // Admin payments are loaded client-side since they require auth cookies
  return {};
};
