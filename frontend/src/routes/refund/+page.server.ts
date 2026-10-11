import type { PageServerLoad } from './$types';
import { getContentBlock } from '$lib/api/admin';

export const load: PageServerLoad = async ({ fetch }) => {
  try {
    const res = await getContentBlock(fetch, 'legal.refund');
    return {
      block: res?.data?.content ?? null
    };
  } catch {
    return {
      block: null
    };
  }
};

