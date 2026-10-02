import fs from 'node:fs';
import path from 'node:path';
import { sveltekit } from '@sveltejs/kit/vite';
import { SvelteKitPWA } from '@vite-pwa/sveltekit';
import { defineConfig } from 'vite';

const certPath = path.resolve('cert.pem');
const keyPath = path.resolve('../key.pem');
const useHttps = process.env.USE_HTTPS === 'true' && fs.existsSync(certPath) && fs.existsSync(keyPath);

export default defineConfig({
  plugins: [
    sveltekit(),
    SvelteKitPWA({
      srcDir: 'src',
      strategies: 'generateSW',
      registerType: 'autoUpdate',
      scope: '/',
      base: '/',
      manifest: {
        name: 'Codeera',
        short_name: 'Codeera',
        description: 'منصة تعليمية لشروحات ومقررات كلية الحاسبات والذكاء الاصطناعي بجامعة الأزهر',
        start_url: '/',
        scope: '/',
        display: 'standalone',
        orientation: 'portrait-primary',
        background_color: '#0F282F',
        theme_color: '#0F282F',
        lang: 'ar',
        dir: 'rtl',
        categories: ['education', 'productivity'],
        icons: [
          {
            src: '/pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png',
            purpose: 'any'
          },
          {
            src: '/pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any'
          },
          {
            src: '/maskable-icon-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'maskable'
          },
          {
            src: '/apple-touch-icon.png',
            sizes: '180x180',
            type: 'image/png',
            purpose: 'any'
          }
        ]
      },
      workbox: {
        globPatterns: ['client/**/*.{js,css,ico,png,svg,webp,woff,woff2}'],
        globIgnores: ['**/node_modules/**/*', 'server/**', '**/*.map'],
        cleanupOutdatedCaches: true,
        clientsClaim: true,
        skipWaiting: true,
        navigateFallback: undefined,
        navigateFallbackDenylist: [
          /^\/api\//,
          /^\/admin/,
          /^\/payments/,
          /checkout/,
          /^\/login/,
          /^\/register/
        ],
        runtimeCaching: [
          // Exclude API from caching completely - always network only
          {
            urlPattern: ({ url }) => url.pathname.startsWith('/api/'),
            handler: 'NetworkOnly'
          },
          // Exclude payment and auth routes from offline caching
          {
            urlPattern: ({ url }) =>
              url.pathname.startsWith('/admin') ||
              url.pathname.startsWith('/payments') ||
              url.pathname.includes('/checkout') ||
              url.pathname === '/login' ||
              url.pathname === '/register',
            handler: 'NetworkOnly'
          },
          // Cache Google Fonts stylesheets
          {
            urlPattern: ({ url }) => url.origin === 'https://fonts.googleapis.com',
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'google-fonts-stylesheets'
            }
          },
          // Cache Google Fonts webfonts (1 year)
          {
            urlPattern: ({ url }) => url.origin === 'https://fonts.gstatic.com',
            handler: 'CacheFirst',
            options: {
              cacheName: 'google-fonts-webfonts',
              expiration: {
                maxEntries: 30,
                maxAgeSeconds: 60 * 60 * 24 * 365
              },
              cacheableResponse: {
                statuses: [0, 200]
              }
            }
          },
          // Cache static images and videos (30 days)
          {
            urlPattern: ({ request }) => request.destination === 'image',
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'images-cache',
              expiration: {
                maxEntries: 60,
                maxAgeSeconds: 30 * 24 * 60 * 60
              }
            }
          },
          // App Shell page navigations: Network-first with short timeout, fallback to cache
          {
            urlPattern: ({ request, url }) =>
              request.mode === 'navigate' &&
              !url.pathname.startsWith('/api/') &&
              !url.pathname.startsWith('/admin') &&
              !url.pathname.startsWith('/payments') &&
              !url.pathname.includes('/checkout') &&
              url.pathname !== '/login' &&
              url.pathname !== '/register',
            handler: 'NetworkFirst',
            options: {
              cacheName: 'pages-cache',
              networkTimeoutSeconds: 3,
              expiration: {
                maxEntries: 50,
                maxAgeSeconds: 24 * 60 * 60
              }
            }
          }
        ]
      },
      devOptions: {
        enabled: true,
        type: 'module'
      }
    })
  ],
  server: {
    cors: false,
    host: '0.0.0.0',
    port: 5173
  },
  preview: {
    cors: false,
    port: 4173,
    https: useHttps
      ? {
          key: fs.readFileSync(keyPath),
          cert: fs.readFileSync(certPath)
        }
      : undefined
  }
});
