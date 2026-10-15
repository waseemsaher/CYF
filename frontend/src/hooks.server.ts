import type { Handle } from '@sveltejs/kit';
import { handleMaintenance } from '$lib/server/maintenance';

export const handle: Handle = async ({ event, resolve }) => {
  return handleMaintenance(event, resolve);
};
