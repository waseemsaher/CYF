import type { RequestHandler } from './$types';

export const GET: RequestHandler = async () => {
  const body = [
    'User-agent: *',
    'Allow: /',
    'Allow: /courses',
    'Allow: /terms',
    'Allow: /privacy',
    'Allow: /refund',
    'Allow: /llms.txt',
    '',
    '# Disallow authentication, student dashboard, checkout, and admin routes',
    'Disallow: /login',
    'Disallow: /register',
    'Disallow: /forgot-password',
    'Disallow: /reset-password',
    'Disallow: /dashboard',
    'Disallow: /payments',
    'Disallow: /my-courses',
    'Disallow: /quizzes',
    'Disallow: /link-telegram',
    'Disallow: /teacher',
    'Disallow: /admin',
    'Disallow: /courses/*/checkout',
    '',
    'Sitemap: https://codeera.tech/sitemap.xml'
  ].join('\n');

  return new Response(body, {
    headers: {
      'Content-Type': 'text/plain; charset=utf-8',
      'Cache-Control': 'public, max-age=3600'
    }
  });
};

