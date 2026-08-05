async (page) => {
  const base = 'http://127.0.0.1:18765';
  const routes = [
    '/ru/components/',
    '/ru/components/framework/',
    '/ru/design/',
    '/ru/design/previews/',
    '/ru/settings/',
    '/ru/settings/site/',
    '/ru/development/agent-journey/',
    '/ru/examples/extensions/',
    '/ru/project-demos/',
  ];
  const scenarios = [
    { id: 'desktop-light', width: 1440, height: 1000, colorScheme: 'light', reducedMotion: 'no-preference' },
    { id: 'desktop-dark-reduced', width: 1920, height: 1080, colorScheme: 'dark', reducedMotion: 'reduce' },
    { id: 'mobile-light', width: 390, height: 844, colorScheme: 'light', reducedMotion: 'no-preference' },
  ];
  const issues = [];
  page.on('console', (message) => {
    if (message.type() === 'error' || message.type() === 'warning') {
      issues.push({ type: message.type(), text: message.text() });
    }
  });
  page.on('pageerror', (error) => issues.push({ type: 'pageerror', text: error.message }));

  const results = [];
  for (const scenario of scenarios) {
    await page.setViewportSize({ width: scenario.width, height: scenario.height });
    await page.emulateMedia({ colorScheme: scenario.colorScheme, reducedMotion: scenario.reducedMotion });
    for (const route of routes) {
      const response = await page.goto(base + route, { waitUntil: 'networkidle' });
      const state = await page.evaluate(() => ({
        title: document.title,
        h1: document.querySelectorAll('h1').length,
        canonical: document.querySelector('link[rel="canonical"]')?.getAttribute('href') ?? null,
        overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
        lang: document.documentElement.lang,
      }));
      results.push({ scenario: scenario.id, route, status: response?.status(), ...state });
    }
  }

  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto(base + '/ru/project-demos/', { waitUntil: 'networkidle' });
  await page.screenshot({
    path: 'source/workflow/evidence/2026-08-05-docara-goal-c-public-documentation/browser/project-demos-mobile.png',
    fullPage: true,
  });
  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.goto(base + '/ru/settings/', { waitUntil: 'networkidle' });
  await page.screenshot({
    path: 'source/workflow/evidence/2026-08-05-docara-goal-c-public-documentation/browser/settings-desktop.png',
    fullPage: true,
  });
  await page.goto(base + '/ru/design/', { waitUntil: 'networkidle' });
  await page.screenshot({
    path: 'source/workflow/evidence/2026-08-05-docara-goal-c-public-documentation/browser/design-desktop.png',
    fullPage: true,
  });

  const failed = results.filter((result) =>
    result.status !== 200 || result.h1 !== 1 || result.overflow || result.lang !== 'ru' || result.canonical === null
  );
  return { scenarios: scenarios.length, routes: routes.length, checks: results.length, failed, issues };
}
