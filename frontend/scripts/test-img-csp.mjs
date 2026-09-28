import puppeteer from 'puppeteer-core';

async function run() {
  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/chromium',
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });
  const page = await browser.newPage();
  const violations = [];
  page.on('console', msg => {
    if (msg.text().includes('CSP Violation')) {
      violations.push(msg.text());
    }
  });

  await page.evaluateOnNewDocument(() => {
    window.addEventListener('securitypolicyviolation', (e) => {
      console.error('[CSP Violation] directive: ' + e.effectiveDirective + ', blockedURI: ' + e.blockedURI);
    });
  });

  await page.goto('http://127.0.0.1:4173/admin', { waitUntil: 'networkidle0' });

  // 1. Allowed S3 origin
  const s3Result = await page.evaluate(async () => {
    return new Promise(resolve => {
      const img = new Image();
      img.onload = () => resolve('loaded');
      img.onerror = () => resolve('network_or_404_not_csp');
      img.src = 'https://codeera-media.s3.us-east-1.amazonaws.com/payment_proofs/sample.jpg';
    });
  });
  console.log('Allowed S3 Image Result:', s3Result);

  // 2. Disallowed origin (random untrusted domain)
  const untrustedResult = await page.evaluate(async () => {
    return new Promise(resolve => {
      const img = new Image();
      img.onload = () => resolve('loaded');
      img.onerror = () => resolve('blocked_as_expected');
      img.src = 'https://random-untrusted-cdn.com/malicious.jpg';
      setTimeout(() => resolve('timeout'), 500);
    });
  });
  console.log('Disallowed Origin Result:', untrustedResult);
  console.log('Recorded CSP Violations:', violations);

  await browser.close();

  const blockedUntrusted = violations.some(v => v.includes('random-untrusted-cdn.com'));
  const blockedS3 = violations.some(v => v.includes('codeera-media.s3.us-east-1.amazonaws.com'));

  if (blockedUntrusted && !blockedS3) {
    console.log('✅ CSP img-src SCOPING VERIFIED IN REAL BROWSER:');
    console.log('   - Scoped S3 origin (https://codeera-media.s3.us-east-1.amazonaws.com) was ALLOWED (no CSP violation).');
    console.log('   - Untrusted origin (https://random-untrusted-cdn.com) was STRICTLY BLOCKED by CSP.');
    process.exit(0);
  } else {
    console.error('❌ Verification failed. blockedUntrusted:', blockedUntrusted, 'blockedS3:', blockedS3);
    process.exit(1);
  }
}

run().catch(err => {
  console.error(err);
  process.exit(1);
});
