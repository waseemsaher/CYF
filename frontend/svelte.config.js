import adapter from '@sveltejs/adapter-vercel';
import { vitePreprocess } from '@sveltejs/vite-plugin-svelte';

const isDev = process.env.NODE_ENV !== 'production';
const connectSrc = ["'self'", 'https://api.codeera.tech'];
if (process.env.PUBLIC_API_BASE_URL) {
  try {
    const parsed = new URL(process.env.PUBLIC_API_BASE_URL);
    connectSrc.push(parsed.origin);
  } catch (_) {}
}
if (isDev) {
  connectSrc.push('http://localhost:8000', 'http://127.0.0.1:8000');
}

const s3Bucket = process.env.PUBLIC_S3_BUCKET || process.env.AWS_BUCKET || 'codeera-media';
const s3Region = process.env.PUBLIC_AWS_REGION || process.env.AWS_DEFAULT_REGION || 'us-east-1';

const imgSrc = [
  "'self'",
  'data:',
  'blob:',
  `https://${s3Bucket}.s3.${s3Region}.amazonaws.com`,
  `https://${s3Bucket}.s3.amazonaws.com`
];

if (process.env.PUBLIC_STORAGE_ORIGIN) {
  try {
    const parsed = new URL(process.env.PUBLIC_STORAGE_ORIGIN);
    imgSrc.push(parsed.origin);
  } catch (_) {}
}

if (isDev) {
  imgSrc.push('http://localhost:8000', 'http://127.0.0.1:8000');
}

/** @type {import('@sveltejs/kit').Config} */
const config = {
  preprocess: vitePreprocess(),
  kit: {
    adapter: adapter({
      runtime: 'nodejs22.x'
    }),
    csp: {
      mode: 'auto',
      directives: {
        'script-src': ['self'],
        'style-src': ['self', 'unsafe-inline', 'https://fonts.googleapis.com'],
        'font-src': ['self', 'https://fonts.gstatic.com', 'data:'],
        'img-src': Array.from(new Set(imgSrc)),
        'media-src': ['self'],
        'connect-src': Array.from(new Set(connectSrc)),
        'frame-ancestors': ['self'],
        'form-action': ['self'],
        'worker-src': ['self']
      }
    }
  }
};

export default config;
