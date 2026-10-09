import type { RequestEvent, ResolveOptions } from '@sveltejs/kit';
import { env } from '$env/dynamic/private';
import {
  COOKIE_NAME,
  isMaintenanceEnabled as isMaintenanceEnabledCore,
  getAllowedIps as getAllowedIpsCore,
  getBypassKey as getBypassKeyCore,
  normalizeIp,
  getClientIp,
  isIpAllowed,
  isStaticAsset,
  renderMaintenanceHtml,
  createMaintenanceResponse,
  handleMaintenanceCore,
  type MaintenanceEnv
} from './maintenance-core';

export {
  COOKIE_NAME,
  normalizeIp,
  getClientIp,
  isIpAllowed,
  isStaticAsset,
  renderMaintenanceHtml,
  createMaintenanceResponse,
  handleMaintenanceCore,
  type MaintenanceEnv
};

function getRuntimeEnv(): MaintenanceEnv {
  return {
    MAINTENANCE_MODE: env.MAINTENANCE_MODE ?? process.env.MAINTENANCE_MODE,
    MAINTENANCE_ALLOWED_IPS: env.MAINTENANCE_ALLOWED_IPS ?? process.env.MAINTENANCE_ALLOWED_IPS,
    MAINTENANCE_BYPASS_KEY: env.MAINTENANCE_BYPASS_KEY ?? process.env.MAINTENANCE_BYPASS_KEY
  };
}

export function isMaintenanceEnabled(): boolean {
  return isMaintenanceEnabledCore(getRuntimeEnv());
}

export function getAllowedIps(): string[] {
  return getAllowedIpsCore(getRuntimeEnv());
}

export function getBypassKey(): string {
  return getBypassKeyCore(getRuntimeEnv());
}

/**
 * Handle function to be called from hooks.server.ts.
 */
export async function handleMaintenance(
  event: RequestEvent,
  resolve: (event: RequestEvent, opts?: ResolveOptions) => Promise<Response> | Response
): Promise<Response> {
  return handleMaintenanceCore(event, resolve, getRuntimeEnv());
}
