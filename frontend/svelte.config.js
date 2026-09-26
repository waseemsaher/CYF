import adapter from '@sveltejs/adapter-node';
import { vitePreprocess } from '@sveltejs/vite-plugin-svelte';

const isDev = process.env.NODE_ENV !== 'production';
const connectSrc = ["'self'"];
if (process.env.PUBLIC_API_BASE_URL) {
  try {
    const parsed = new URL(process.env.PUBLIC_API_BASE_URL);
    connectSrc.push(parsed.origin);
  } catch (_) {}
}
if (isDev) {
  connectSrc.push('http://localhost:8000', 'http://127.0.0.1:8000');
}

/** @type {import('@sveltejs/kit').Config} */
const config = {
  preprocess: vitePreprocess(),
  kit: {
    adapter: adapter({
      out: 'build',
      precompress: true
    }),
    csp: {
      mode: 'auto',
      directives: {
        'script-src': ['self'],
        'style-src': ['self', 'unsafe-inline', 'https://fonts.googleapis.com'],
        'font-src': ['self', 'https://fonts.gstatic.com', 'data:'],
        'img-src': ['self', 'data:', 'blob:'],
        'media-src': ['self'],
        'connect-src': connectSrc,
        'frame-ancestors': ['self'],
        'form-action': ['self']
      }
    }
  }
};

export default config;
