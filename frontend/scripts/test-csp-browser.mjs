import puppeteer from 'puppeteer-core';

const BASE_URL = 'http://127.0.0.1:4173';
const ROUTES = ['/', '/login', '/register', '/courses', '/admin'];

async function run() {
  console.log('=== Launching Headless Chromium with Real Browser Verification ===');
  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/chromium',
    headless: 'new',
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-gpu',
    ],
  });

  const results = [];

  for (const route of ROUTES) {
    const page = await browser.newPage();
    const consoleLogs = [];
    const errors = [];
    const cspViolations = [];

    // Capture browser console logs
    page.on('console', (msg) => {
      const type = msg.type();
      const text = msg.text();
      consoleLogs.push({ type, text });
      if (text.toLowerCase().includes('content security policy') ||
          text.toLowerCase().includes('refused to') ||
          text.toLowerCase().includes('violates the')) {
        cspViolations.push(text);
      }
    });

    // Capture uncaught exceptions
    page.on('pageerror', (err) => {
      errors.push(err.message);
    });

    // Capture security policy violation events in the browser context
    await page.evaluateOnNewDocument(() => {
      window.addEventListener('securitypolicyviolation', (e) => {
        console.error(
          `[CSP Violation] directive: ${e.effectiveDirective}, blockedURI: ${e.blockedURI}, sample: ${e.sample}`
        );
      });
    });

    const targetUrl = `${BASE_URL}${route}`;
    let responseStatus = null;
    let cspHeader = null;

    try {
      const response = await page.goto(targetUrl, {
        waitUntil: 'networkidle0',
        timeout: 15000,
      });
      responseStatus = response.status();
      cspHeader = response.headers()['content-security-policy'] || null;
    } catch (e) {
      errors.push(`Navigation failed: ${e.message}`);
    }

    // Specific interactive tests on root page '/'
    let interactions = null;
    if (route === '/') {
      interactions = {
        canvasFound: false,
        mouseEventFired: false,
        initialTheme: null,
        toggledTheme: null,
        persistedTheme: null,
        themeSuccess: false,
      };

      try {
        // 1. Check canvas existence and trigger mouse interaction
        const canvas = await page.$('canvas.hero-canvas');
        if (canvas) {
          interactions.canvasFound = true;
          const box = await canvas.boundingBox();
          if (box) {
            await page.mouse.move(box.x + 50, box.y + 50);
            await new Promise((r) => setTimeout(r, 100));
            await page.mouse.move(box.x + 150, box.y + 120);
            await new Promise((r) => setTimeout(r, 100));
            interactions.mouseEventFired = true;
          }
        }

        // 2. Check and click theme toggle
        interactions.initialTheme = await page.evaluate(() =>
          document.documentElement.getAttribute('data-theme')
        );

        const themeBtn = await page.$('.btn-theme-toggle');
        if (themeBtn) {
          await themeBtn.click();
          await new Promise((r) => setTimeout(r, 200));

          interactions.toggledTheme = await page.evaluate(() =>
            document.documentElement.getAttribute('data-theme')
          );

          interactions.persistedTheme = await page.evaluate(() =>
            localStorage.getItem('codeera_theme')
          );

          interactions.themeSuccess =
            interactions.toggledTheme !== interactions.initialTheme;
        }
      } catch (err) {
        errors.push(`Interactive test error: ${err.message}`);
      }
    }

    results.push({
      route,
      targetUrl,
      status: responseStatus,
      cspHeader,
      consoleLogs,
      errors,
      cspViolations,
      interactions,
    });

    await page.close();
  }

  await browser.close();

  console.log('\n=== FINAL BROWSER VERIFICATION REPORT ===');
  console.log(JSON.stringify(results, null, 2));

  let totalCspViolations = results.reduce(
    (acc, r) => acc + r.cspViolations.length,
    0
  );
  let totalErrors = results.reduce((acc, r) => acc + r.errors.length, 0);

  console.log('\n================ SUMMARY ================');
  console.log(`Routes tested: ${results.length}`);
  console.log(`Total CSP Violations: ${totalCspViolations}`);
  console.log(`Total Uncaught Page Errors: ${totalErrors}`);

  if (totalCspViolations === 0 && totalErrors === 0) {
    console.log('✅ ALL PAGES PASSED WITH ZERO CONSOLE ERRORS AND ZERO CSP VIOLATIONS.');
    process.exit(0);
  } else {
    console.error('❌ FAILURES DETECTED IN BROWSER CONSOLE.');
    process.exit(1);
  }
}

run().catch((err) => {
  console.error('Test runner fatal error:', err);
  process.exit(1);
});
