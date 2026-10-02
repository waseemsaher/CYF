import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [sveltekit()],
  server: {
    cors: false,
    host: '0.0.0.0',
    port: 5173
  },
  preview: {
    cors: false,
    port: 4173
  }
});
