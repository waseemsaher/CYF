import type { PageServerLoad } from './$types';

export const load: PageServerLoad = async () => {
  // Payments are loaded client-side since they require auth cookies
  return {};
};
