import type { RequestHandler } from './$types';
import { getCourses } from '$lib/api/catalog';

const SITE_ORIGIN = 'https://codeera.tech';

function escapeXml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}

export const GET: RequestHandler = async ({ fetch }) => {
  const staticPages = [
    { path: '/', changefreq: 'daily', priority: '1.0' },
    { path: '/courses', changefreq: 'daily', priority: '0.9' },
    { path: '/refund', changefreq: 'monthly', priority: '0.6' },
    { path: '/terms', changefreq: 'monthly', priority: '0.5' },
    { path: '/privacy', changefreq: 'monthly', priority: '0.5' }
  ];

  let courseSlugs: string[] = [];
  try {
    const res = await getCourses(fetch, new URLSearchParams());
    courseSlugs = (res.data ?? [])
      .filter((c) => !c.status || c.status === 'published')
      .map((c) => c.slug)
      .filter(Boolean);
  } catch {
    courseSlugs = [];
  }

  const today = new Date().toISOString().split('T')[0];

  const urlsXml = [
    ...staticPages.map(
      (page) => `  <url>
    <loc>${escapeXml(`${SITE_ORIGIN}${page.path}`)}</loc>
    <lastmod>${today}</lastmod>
    <changefreq>${page.changefreq}</changefreq>
    <priority>${page.priority}</priority>
  </url>`
    ),
    ...courseSlugs.map(
      (slug) => `  <url>
    <loc>${escapeXml(`${SITE_ORIGIN}/courses/${encodeURIComponent(slug)}`)}</loc>
    <lastmod>${today}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>`
    )
  ].join('\n');

  const xml = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${urlsXml}
</urlset>`;

  return new Response(xml, {
    headers: {
      'Content-Type': 'application/xml; charset=utf-8',
      'Cache-Control': 'public, max-age=1800'
    }
  });
};

