export const COOKIE_NAME = 'maintenance_bypass';

export interface MaintenanceEnv {
  MAINTENANCE_MODE?: string;
  MAINTENANCE_ALLOWED_IPS?: string;
  MAINTENANCE_BYPASS_KEY?: string;
}

/**
 * Returns true if MAINTENANCE_MODE is set to 'true' (case-insensitive).
 */
export function isMaintenanceEnabled(env: MaintenanceEnv = process.env): boolean {
  const val = env.MAINTENANCE_MODE ?? '';
  return val.trim().toLowerCase() === 'true';
}

/**
 * Returns array of allowed IP addresses from MAINTENANCE_ALLOWED_IPS (comma-separated).
 */
export function getAllowedIps(env: MaintenanceEnv = process.env): string[] {
  const val = env.MAINTENANCE_ALLOWED_IPS ?? '';
  return val
    .split(',')
    .map(ip => ip.trim())
    .filter(Boolean);
}

/**
 * Returns the secret bypass key from MAINTENANCE_BYPASS_KEY.
 */
export function getBypassKey(env: MaintenanceEnv = process.env): string {
  return (env.MAINTENANCE_BYPASS_KEY ?? '').trim();
}

/**
 * Normalizes an IP string (strips whitespace and IPv6 IPv4-mapped prefix ::ffff:).
 */
export function normalizeIp(ip: string): string {
  const trimmed = ip.trim();
  if (trimmed.startsWith('::ffff:')) {
    return trimmed.slice(7);
  }
  return trimmed;
}

/**
 * Resolves the client IP address behind proxies.
 *
 * Header resolution priority:
 * 1. x-forwarded-for: Standard proxy header used by Vercel and reverse proxies.
 *    Takes the first comma-separated IP (the client's true IP before proxies).
 * 2. x-real-ip: Standard header populated by reverse proxies like Nginx.
 * 3. cf-connecting-ip: Populated by Cloudflare if placed in front of Vercel.
 * 4. getClientAddress(): Native SvelteKit method (uses adapter-specific IP resolution).
 */
export function getClientIp(
  request: { headers: { get: (name: string) => string | null } },
  getClientAddress?: () => string
): string {
  // 1. x-forwarded-for: Vercel sets this with client IP as the first element
  const xForwardedFor = request.headers.get('x-forwarded-for');
  if (xForwardedFor) {
    const firstIp = xForwardedFor.split(',')[0].trim();
    if (firstIp) return firstIp;
  }

  // 2. x-real-ip
  const xRealIp = request.headers.get('x-real-ip');
  if (xRealIp && xRealIp.trim()) {
    return xRealIp.trim();
  }

  // 3. cf-connecting-ip (Cloudflare)
  const cfConnectingIp = request.headers.get('cf-connecting-ip');
  if (cfConnectingIp && cfConnectingIp.trim()) {
    return cfConnectingIp.trim();
  }

  // 4. Native SvelteKit getClientAddress fallback
  if (typeof getClientAddress === 'function') {
    try {
      const address = getClientAddress();
      if (address && address.trim()) {
        return address.trim();
      }
    } catch {
      // Ignored if unsupported in test environment
    }
  }

  return '';
}

/**
 * Checks whether client IP matches any IP in the allowed list.
 */
export function isIpAllowed(clientIp: string, allowedIps: string[]): boolean {
  if (!clientIp || allowedIps.length === 0) return false;
  const normalizedClient = normalizeIp(clientIp);
  return allowedIps.some(allowed => normalizeIp(allowed) === normalizedClient);
}

/**
 * Determines whether a URL path points to static assets needed by the page.
 */
export function isStaticAsset(pathname: string): boolean {
  if (
    pathname.startsWith('/_app/') ||
    pathname.startsWith('/static/') ||
    pathname.startsWith('/logo/') ||
    pathname.startsWith('/images/') ||
    pathname.startsWith('/videos/') ||
    pathname.startsWith('/favicon') ||
    pathname.startsWith('/apple-touch-icon') ||
    pathname.startsWith('/pwa-') ||
    pathname.startsWith('/manifest')
  ) {
    return true;
  }

  const staticExtensions = [
    '.css',
    '.js',
    '.png',
    '.jpg',
    '.jpeg',
    '.gif',
    '.svg',
    '.webp',
    '.ico',
    '.woff',
    '.woff2',
    '.ttf',
    '.webmanifest',
    '.json'
  ];

  const lower = pathname.toLowerCase();
  return staticExtensions.some(ext => lower.endsWith(ext));
}

/**
 * Renders the self-contained Arabic + English HTML maintenance page.
 */
export function renderMaintenanceHtml(): string {
  return `<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CodeEra | تحت الصيانة • Under Maintenance</title>
  <link rel="icon" type="image/png" href="/favicon.png" />
  <style>
    :root {
      --bg: #0b0f19;
      --card-bg: rgba(17, 24, 39, 0.85);
      --card-border: rgba(30, 41, 59, 0.8);
      --cyan: #06b6d4;
      --cyan-glow: rgba(6, 182, 212, 0.25);
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --border-subtle: #1e293b;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background-color: var(--bg);
      background-image: 
        radial-gradient(circle at 50% 20%, rgba(6, 182, 212, 0.12) 0%, transparent 60%),
        radial-gradient(circle at 80% 80%, rgba(14, 165, 233, 0.08) 0%, transparent 50%);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      line-height: 1.6;
    }
    .card {
      width: 100%;
      max-width: 620px;
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-radius: 20px;
      padding: 2.75rem 2.25rem;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 40px var(--cyan-glow);
      text-align: center;
    }
    .icon-wrapper {
      width: 72px;
      height: 72px;
      margin: 0 auto 1.75rem;
      background: rgba(6, 182, 212, 0.1);
      border: 1px solid rgba(6, 182, 212, 0.3);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--cyan);
      box-shadow: 0 0 20px rgba(6, 182, 212, 0.2);
    }
    .icon-wrapper svg {
      width: 36px;
      height: 36px;
    }
    .badge {
      display: inline-block;
      font-size: 0.8rem;
      font-weight: 600;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      background: rgba(6, 182, 212, 0.15);
      color: var(--cyan);
      border: 1px solid rgba(6, 182, 212, 0.3);
      padding: 0.35rem 0.85rem;
      border-radius: 9999px;
      margin-bottom: 1.25rem;
    }
    h1 {
      font-size: 1.75rem;
      font-weight: 700;
      margin-bottom: 0.75rem;
      color: var(--text-main);
    }
    p {
      color: var(--text-muted);
      font-size: 1rem;
      margin-bottom: 1.5rem;
    }
    .divider {
      height: 1px;
      background: var(--border-subtle);
      margin: 1.75rem 0;
    }
    .en-section {
      direction: ltr;
      text-align: center;
    }
    .en-section h2 {
      font-size: 1.35rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: #e2e8f0;
    }
    .en-section p {
      font-size: 0.95rem;
      margin-bottom: 1.25rem;
    }
    .actions {
      margin-top: 2rem;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      background: var(--cyan);
      color: #0b0f19;
      font-weight: 600;
      font-size: 0.95rem;
      padding: 0.75rem 1.75rem;
      border-radius: 10px;
      text-decoration: none;
      cursor: pointer;
      border: none;
      transition: all 0.2s ease;
    }
    .btn:hover {
      background: #22d3ee;
      box-shadow: 0 0 20px rgba(6, 182, 212, 0.4);
      transform: translateY(-1px);
    }
    .footer {
      margin-top: 1.75rem;
      font-size: 0.825rem;
      color: #64748b;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon-wrapper">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
    </div>

    <div class="badge">HTTP 503 • أعمال صيانة مجدولة</div>

    <h1>المنصة تحت الصيانة حالياً</h1>
    <p>
      نعمل حالياً على تحديث المنصة وتحسين بنيتها التحتية لتقديم أفضل أداء ممكن. سنعود للعمل بكامل طاقتنا في أقرب وقت.
    </p>

    <div class="divider"></div>

    <div class="en-section">
      <h2>Scheduled Maintenance in Progress</h2>
      <p>
        We are currently upgrading our platform to enhance reliability and performance. We apologize for the temporary inconvenience and will be back online shortly.
      </p>
    </div>

    <div class="actions">
      <button class="btn" onclick="window.location.reload()">
        <span>إعادة المحاولة • Reload Page</span>
      </button>
    </div>

    <div class="footer">
      CodeEra Platform &copy; ${new Date().getFullYear()} • شكراً لتفهمكم
    </div>
  </div>
</body>
</html>`;
}

/**
 * Creates the HTTP 503 Response with Retry-After header.
 */
export function createMaintenanceResponse(): Response {
  return new Response(renderMaintenanceHtml(), {
    status: 503,
    headers: {
      'Content-Type': 'text/html; charset=utf-8',
      'Retry-After': '300',
      'Cache-Control': 'no-store, no-cache, must-revalidate, max-age=0',
      'Pragma': 'no-cache'
    }
  });
}

export interface MaintenanceEventLike {
  url: URL;
  request: { headers: { get: (name: string) => string | null } };
  cookies: {
    get: (name: string) => string | undefined;
    set: (name: string, value: string, opts?: any) => void;
  };
  getClientAddress?: () => string;
}

/**
 * Core maintenance handling logic.
 */
export async function handleMaintenanceCore(
  event: MaintenanceEventLike,
  resolve: (event: any) => Promise<Response> | Response,
  env: MaintenanceEnv = process.env
): Promise<Response> {
  // If maintenance mode is off, pass through immediately
  if (!isMaintenanceEnabled(env)) {
    return resolve(event);
  }

  // Allow static assets so maintenance page and resources load
  if (isStaticAsset(event.url.pathname)) {
    return resolve(event);
  }

  const bypassKey = getBypassKey(env);

  // 1. Query parameter bypass: /?maintenance_key=<key>
  if (bypassKey !== '') {
    const queryKey = event.url.searchParams.get('maintenance_key');
    if (queryKey === bypassKey) {
      event.cookies.set(COOKIE_NAME, bypassKey, {
        path: '/',
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'lax',
        maxAge: 60 * 60 * 24 * 7 // 7 days
      });
      return resolve(event);
    }

    // 2. Cookie bypass: browser previously authenticated with bypass key
    const cookieKey = event.cookies.get(COOKIE_NAME);
    if (cookieKey === bypassKey) {
      return resolve(event);
    }
  }

  // 3. IP address whitelist check
  const allowedIps = getAllowedIps(env);
  if (allowedIps.length > 0) {
    const clientIp = getClientIp(event.request, event.getClientAddress);
    if (isIpAllowed(clientIp, allowedIps)) {
      return resolve(event);
    }
  }

  // Otherwise, block with 503 maintenance page
  return createMaintenanceResponse();
}
