import type { LayoutServerLoad } from './$types';
import { getPublicCtas } from '$lib/api/ctas';

export const load: LayoutServerLoad = async ({ fetch }) => {
  const ctas = await getPublicCtas(fetch);
  return {
    ctas
  };
};
