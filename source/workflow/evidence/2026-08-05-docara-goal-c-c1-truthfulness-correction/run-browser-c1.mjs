import { createHash } from 'node:crypto';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { chromium } from 'playwright';

const base = process.env.DOCARA_BROWSER_BASE ?? 'http://127.0.0.1:18766';
const buildRoot = process.env.DOCARA_BUILD_ROOT;
const outputRoot = process.env.DOCARA_BROWSER_OUTPUT;
const candidate = process.env.DOCARA_PRODUCT_CANDIDATE;

if (!buildRoot || !outputRoot || !candidate) {
  throw new Error('DOCARA_BUILD_ROOT, DOCARA_BROWSER_OUTPUT and DOCARA_PRODUCT_CANDIDATE are required.');
}

await mkdir(outputRoot, { recursive: true });

const metadata = JSON.parse(await readFile(path.join(buildRoot, '_docara/page-metadata.json'), 'utf8'));
const publicRoutes = metadata.pages.map((page) => page.url);
const representativeRoutes = [
  '/ru/components/native-markdown/',
  '/ru/components/inline-docara/',
  '/ru/components/block-docara/',
  '/ru/components/containers/',
  '/ru/components/framework/',
  '/ru/components/project/',
  '/ru/settings/',
  '/ru/settings/site/',
  '/ru/development/agent-journey/',
];
const scenarios = [
  { id: 'desktop-light-ltr', width: 1440, height: 1000, colorScheme: 'light', reducedMotion: 'no-preference', direction: 'ltr' },
  { id: 'desktop-dark-rtl-reduced', width: 1920, height: 1080, colorScheme: 'dark', reducedMotion: 'reduce', direction: 'rtl' },
  { id: 'mobile-light-ltr', width: 390, height: 844, colorScheme: 'light', reducedMotion: 'no-preference', direction: 'ltr' },
];

const browser = await chromium.launch({
  headless: true,
  executablePath: process.env.DOCARA_BROWSER_EXECUTABLE || undefined,
});
const context = await browser.newContext();
const page = await context.newPage();
const consoleIssues = [];
const requestFailures = [];
page.on('console', (message) => {
  if (message.type() === 'error' || message.type() === 'warning') {
    consoleIssues.push({ type: message.type(), text: message.text() });
  }
});
page.on('pageerror', (error) => consoleIssues.push({ type: 'pageerror', text: error.message }));
page.on('requestfailed', (request) => requestFailures.push({ url: request.url(), error: request.failure()?.errorText ?? 'unknown' }));

const httpFailures = [];
for (const route of publicRoutes) {
  const response = await page.goto(base + route, { waitUntil: 'networkidle' });
  if (response?.status() !== 200) httpFailures.push({ route, status: response?.status() ?? null });
}

const checks = [];
for (const scenario of scenarios) {
  await page.setViewportSize({ width: scenario.width, height: scenario.height });
  await page.emulateMedia({ colorScheme: scenario.colorScheme, reducedMotion: scenario.reducedMotion });
  for (const route of representativeRoutes) {
    const response = await page.goto(base + route, { waitUntil: 'networkidle' });
    if (scenario.direction === 'rtl') {
      await page.evaluate(() => document.documentElement.setAttribute('dir', 'rtl'));
    }
    const state = await page.evaluate(() => ({
      h1: document.querySelectorAll('main h1').length,
      canonical: document.querySelector('link[rel="canonical"]')?.getAttribute('href') ?? null,
      overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
      direction: document.documentElement.dir,
      main: Boolean(document.querySelector('main')),
    }));
    checks.push({ scenario: scenario.id, route, status: response?.status() ?? null, ...state });
  }
}

async function escapeReturnsFocus(triggerSelector, dialogSelector) {
  await page.goto(base + '/ru/components/framework/', { waitUntil: 'networkidle' });
  const trigger = page.locator(triggerSelector).first();
  await trigger.focus();
  await trigger.click();
  await page.locator(`${dialogSelector}[open]`).waitFor({ state: 'attached' });
  await page.keyboard.press('Escape');
  await page.waitForFunction((selector) => !document.querySelector(selector)?.hasAttribute('open'), dialogSelector);
  return trigger.evaluate((node) => document.activeElement === node);
}

const keyboard = {
  search_escape_focus_return: await escapeReturnsFocus('[data-docara-search-trigger]', '[data-docara-search-dialog]'),
  settings_escape_focus_return: await escapeReturnsFocus('[data-docara-reader-settings-trigger]', '[data-docara-reader-settings-dialog]'),
};

const screenshots = {};
for (const [name, route, width, height] of [
  ['framework-desktop.png', '/ru/components/framework/', 1440, 1000],
  ['settings-desktop.png', '/ru/settings/', 1440, 1000],
  ['catalog-mobile.png', '/ru/components/', 390, 844],
]) {
  await page.setViewportSize({ width, height });
  await page.goto(base + route, { waitUntil: 'networkidle' });
  const bytes = await page.screenshot({ path: path.join(outputRoot, name), fullPage: true });
  screenshots[name] = createHash('sha256').update(bytes).digest('hex');
}

await browser.close();

const failedChecks = checks.filter((check) =>
  check.status !== 200 || check.h1 !== 1 || check.canonical === null || check.overflow || !check.main || check.direction === ''
);
const result = {
  schema: 'docara.goal_c_c1_browser_acceptance.v1',
  product_candidate: candidate,
  build_tree_sha256: '44d827a6576d16ec39a9787887ed123a5447ecb484c98780c696121584d2b7b4',
  route_http: { checked: publicRoutes.length, failed: httpFailures },
  representative_matrix: {
    routes: representativeRoutes.length,
    scenarios: scenarios.map((scenario) => scenario.id),
    checks: checks.length,
    failed: failedChecks,
    console_issues: consoleIssues,
    request_failures: requestFailures,
  },
  keyboard,
  screenshots,
  rtl_note: 'The exact RU production page is switched to dir=rtl after load to exercise logical CSS; generated lang=ar/dir=rtl output remains covered by the PHP product suite.',
};

await writeFile(path.join(outputRoot, 'result.json'), JSON.stringify(result, null, 2) + '\n');

if (httpFailures.length || failedChecks.length || consoleIssues.length || requestFailures.length || !Object.values(keyboard).every(Boolean)) {
  process.stderr.write(JSON.stringify(result, null, 2) + '\n');
  process.exitCode = 1;
} else {
  process.stdout.write(JSON.stringify(result, null, 2) + '\n');
}
