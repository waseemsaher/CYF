# No Browser Usage

- NEVER use `browser_subagent`, `open_browser_url`, or any browser automation tools in this project.
- Headless browser / Chrome DevTools Protocol (CDP) is disabled and unsupported in this environment.
- Verify frontend changes using:
  - `npm run check` (svelte-check)
  - `npm run build`
  - Automated tests (`vitest`, Pest)
  - Direct HTTP/SSR inspection via `curl`
- Visual preview and verification is handled directly by the owner in their browser at `http://localhost:5173`.
