import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
  isMaintenanceEnabled,
  getAllowedIps,
  getBypassKey,
  normalizeIp,
  getClientIp,
  isIpAllowed,
  isStaticAsset,
  createMaintenanceResponse,
  renderMaintenanceHtml,
  handleMaintenanceCore
} from '../src/lib/server/maintenance-core.ts';

test('isMaintenanceEnabled checks MAINTENANCE_MODE environment variable', () => {
  assert.equal(isMaintenanceEnabled({ MAINTENANCE_MODE: 'true' }), true);
  assert.equal(isMaintenanceEnabled({ MAINTENANCE_MODE: 'TRUE' }), true);
  assert.equal(isMaintenanceEnabled({ MAINTENANCE_MODE: ' True ' }), true);
  assert.equal(isMaintenanceEnabled({ MAINTENANCE_MODE: 'false' }), false);
  assert.equal(isMaintenanceEnabled({ MAINTENANCE_MODE: '0' }), false);
  assert.equal(isMaintenanceEnabled({}), false);
});

test('getAllowedIps parses and trims comma-separated list of IPs', () => {
  assert.deepEqual(getAllowedIps({ MAINTENANCE_ALLOWED_IPS: '1.2.3.4, 5.6.7.8 , 9.10.11.12' }), [
    '1.2.3.4',
    '5.6.7.8',
    '9.10.11.12'
  ]);
  assert.deepEqual(getAllowedIps({ MAINTENANCE_ALLOWED_IPS: '   ' }), []);
  assert.deepEqual(getAllowedIps({}), []);
});

test('getBypassKey retrieves and trims secret key', () => {
  assert.equal(getBypassKey({ MAINTENANCE_BYPASS_KEY: '  my_secret_key_123  ' }), 'my_secret_key_123');
  assert.equal(getBypassKey({}), '');
});

test('normalizeIp handles IPv4, IPv6, and IPv4-mapped IPv6 addresses', () => {
  assert.equal(normalizeIp('  192.168.1.1  '), '192.168.1.1');
  assert.equal(normalizeIp('::ffff:192.168.1.1'), '192.168.1.1');
  assert.equal(normalizeIp('2001:db8::1'), '2001:db8::1');
});

test('getClientIp respects proxy header priority and parses comma-separated x-forwarded-for', () => {
  // 1. x-forwarded-for takes precedence (first IP in chain is the client IP)
  const req1 = {
    headers: new Map([
      ['x-forwarded-for', '203.0.113.195, 70.41.3.18, 150.172.238.178'],
      ['x-real-ip', '10.0.0.1']
    ])
  };
  assert.equal(getClientIp(req1), '203.0.113.195');

  // 2. x-real-ip used if x-forwarded-for is absent
  const req2 = {
    headers: new Map([
      ['x-real-ip', '198.51.100.42']
    ])
  };
  assert.equal(getClientIp(req2), '198.51.100.42');

  // 3. cf-connecting-ip used if previous headers absent
  const req3 = {
    headers: new Map([
      ['cf-connecting-ip', '198.51.100.99']
    ])
  };
  assert.equal(getClientIp(req3), '198.51.100.99');

  // 4. Fallback to getClientAddress callback
  const req4 = {
    headers: new Map()
  };
  assert.equal(getClientIp(req4, () => '127.0.0.1'), '127.0.0.1');

  // 5. Empty when nothing provided
  assert.equal(getClientIp(req4), '');
});

test('isIpAllowed correctly checks client IP against whitelist including IPv6-mapped addresses', () => {
  const allowed = ['192.168.1.100', '10.0.0.5', '203.0.113.50'];

  assert.equal(isIpAllowed('192.168.1.100', allowed), true);
  assert.equal(isIpAllowed('::ffff:192.168.1.100', allowed), true);
  assert.equal(isIpAllowed('10.0.0.5', allowed), true);
  assert.equal(isIpAllowed('192.168.1.101', allowed), false);
  assert.equal(isIpAllowed('', allowed), false);
  assert.equal(isIpAllowed('192.168.1.100', []), false);
});

test('isStaticAsset recognizes assets needed by the maintenance page vs application routes', () => {
  // Static assets allowed
  assert.equal(isStaticAsset('/_app/immutable/chunks/bundle.js'), true);
  assert.equal(isStaticAsset('/_app/immutable/assets/style.css'), true);
  assert.equal(isStaticAsset('/favicon.png'), true);
  assert.equal(isStaticAsset('/apple-touch-icon.png'), true);
  assert.equal(isStaticAsset('/logo/logo.png'), true);
  assert.equal(isStaticAsset('/images/bg.webp'), true);
  assert.equal(isStaticAsset('/manifest.webmanifest'), true);

  // Application routes blocked by maintenance
  assert.equal(isStaticAsset('/'), false);
  assert.equal(isStaticAsset('/login'), false);
  assert.equal(isStaticAsset('/register'), false);
  assert.equal(isStaticAsset('/courses'), false);
  assert.equal(isStaticAsset('/courses/web-dev'), false);
  assert.equal(isStaticAsset('/admin/payments'), false);
});

test('createMaintenanceResponse returns 503 with Retry-After header and bilingual content', async () => {
  const response = createMaintenanceResponse();

  assert.equal(response.status, 503);
  assert.equal(response.headers.get('Retry-After'), '300');
  assert.equal(response.headers.get('Content-Type'), 'text/html; charset=utf-8');
  assert.equal(response.headers.get('Cache-Control'), 'no-store, no-cache, must-revalidate, max-age=0');

  const html = await response.text();
  assert.ok(html.includes('المنصة تحت الصيانة حالياً'));
  assert.ok(html.includes('Scheduled Maintenance in Progress'));
  assert.ok(html.includes('HTTP 503'));
});

test('handleMaintenanceCore hook integration scenarios', async () => {
  let resolved = false;
  const mockResolve = () => {
    resolved = true;
    return new Response('OK', { status: 200 });
  };

  // Helper to create mock event
  const createMockEvent = ({
    pathname = '/',
    searchParams = new URLSearchParams(),
    headers = new Headers(),
    cookies = new Map()
  } = {}) => ({
    url: new URL(`https://example.com${pathname}?${searchParams.toString()}`),
    request: new Request(`https://example.com${pathname}?${searchParams.toString()}`, { headers }),
    cookies: {
      get: (name) => cookies.get(name),
      set: (name, val, opts) => cookies.set(name, val)
    },
    getClientAddress: () => headers.get('x-forwarded-for')?.split(',')[0].trim() || '127.0.0.1'
  });

  // Scenario 1: Maintenance disabled
  const envDisabled = {
    MAINTENANCE_MODE: 'false',
    MAINTENANCE_ALLOWED_IPS: '1.1.1.1',
    MAINTENANCE_BYPASS_KEY: 'secret123'
  };
  resolved = false;
  let res = await handleMaintenanceCore(createMockEvent({ pathname: '/' }), mockResolve, envDisabled);
  assert.equal(res.status, 200);
  assert.equal(resolved, true);

  // Maintenance enabled configuration for subsequent scenarios
  const envEnabled = {
    MAINTENANCE_MODE: 'true',
    MAINTENANCE_ALLOWED_IPS: '1.1.1.1',
    MAINTENANCE_BYPASS_KEY: 'secret123'
  };

  // Scenario 2: Static asset bypass
  resolved = false;
  res = await handleMaintenanceCore(createMockEvent({ pathname: '/logo/logo.png' }), mockResolve, envEnabled);
  assert.equal(res.status, 200);
  assert.equal(resolved, true);

  // Scenario 3: Blocked client (IP not allowed, no key)
  resolved = false;
  const blockedHeaders = new Headers({ 'x-forwarded-for': '9.9.9.9' });
  res = await handleMaintenanceCore(createMockEvent({ pathname: '/courses', headers: blockedHeaders }), mockResolve, envEnabled);
  assert.equal(res.status, 503);
  assert.equal(res.headers.get('Retry-After'), '300');
  assert.equal(resolved, false);

  // Scenario 4: Allowed IP bypass
  resolved = false;
  const allowedHeaders = new Headers({ 'x-forwarded-for': '1.1.1.1' });
  res = await handleMaintenanceCore(createMockEvent({ pathname: '/courses', headers: allowedHeaders }), mockResolve, envEnabled);
  assert.equal(res.status, 200);
  assert.equal(resolved, true);

  // Scenario 5: Secret key query param bypass (sets cookie and allows through)
  resolved = false;
  const cookieJar = new Map();
  const searchParams = new URLSearchParams({ maintenance_key: 'secret123' });
  res = await handleMaintenanceCore(
    createMockEvent({ pathname: '/', searchParams, headers: blockedHeaders, cookies: cookieJar }),
    mockResolve,
    envEnabled
  );
  assert.equal(res.status, 200);
  assert.equal(resolved, true);
  assert.equal(cookieJar.get('maintenance_bypass'), 'secret123');

  // Scenario 6: Cookie bypass allows subsequent requests through
  resolved = false;
  res = await handleMaintenanceCore(
    createMockEvent({ pathname: '/admin', headers: blockedHeaders, cookies: cookieJar }),
    mockResolve,
    envEnabled
  );
  assert.equal(res.status, 200);
  assert.equal(resolved, true);
});
