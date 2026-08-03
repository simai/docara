#!/usr/bin/env node

import {createHash} from 'node:crypto';
import {existsSync, lstatSync, mkdirSync, readFileSync, realpathSync, writeFileSync} from 'node:fs';
import {createServer} from 'node:http';
import {extname, join, normalize, relative, resolve, sep} from 'node:path';
import {chromium} from 'playwright';

const [projectArgument, ...planIds] = process.argv.slice(2);
if (!projectArgument || planIds.length === 0) {
  throw new Error('Usage: run-browser-qa.mjs <project-root> <plan-id>...');
}

const project = realpathSync(projectArgument);
if (lstatSync(project).isSymbolicLink()) throw new Error('Project root symlink is forbidden.');

function contained(path) {
  const absolute = resolve(path);
  if (absolute !== project && !absolute.startsWith(project + sep)) throw new Error('Path escaped project root.');
  const rel = relative(project, absolute).split(sep).filter(Boolean);
  let current = project;
  for (const part of rel) {
    current = join(current, part);
    if (!existsSync(current)) continue;
    const stat = lstatSync(current);
    if (stat.isSymbolicLink() || (!stat.isDirectory() && !stat.isFile()) || (stat.isFile() && stat.nlink !== 1)) {
      throw new Error(`Unsafe QA path: ${relative(project, current)}`);
    }
    if (!realpathSync(current).startsWith(project + sep)) throw new Error('QA path resolved outside project root.');
  }
  return absolute;
}

function ensure(path) {
  const absolute = contained(path);
  const rel = relative(project, absolute).split(sep).filter(Boolean);
  let current = project;
  for (const part of rel) {
    current = join(current, part);
    contained(current);
    if (!existsSync(current)) mkdirSync(current, {mode: 0o755});
    contained(current);
  }
  return absolute;
}

function putNew(path, value) {
  const absolute = contained(path);
  ensure(resolve(absolute, '..'));
  if (existsSync(absolute)) throw new Error(`QA output collision: ${relative(project, absolute)}`);
  writeFileSync(absolute, value, {flag: 'wx', mode: 0o644});
  contained(absolute);
}

function mime(path) {
  return ({'.html':'text/html; charset=utf-8','.css':'text/css; charset=utf-8','.js':'text/javascript; charset=utf-8','.json':'application/json; charset=utf-8','.svg':'image/svg+xml','.png':'image/png','.ico':'image/x-icon','.woff2':'font/woff2'}[extname(path).toLowerCase()] || 'application/octet-stream');
}

function sha(value) { return createHash('sha256').update(value).digest('hex'); }

for (const planId of planIds) {
  if (!/^[a-f0-9]{64}$/.test(planId)) throw new Error('Plan id must be SHA-256.');
  const planPath = contained(join(project, '.docara-qa', 'plans', `${planId}.json`));
  const planBytes = readFileSync(planPath);
  const plan = JSON.parse(planBytes);
  if (plan.plan_id !== planId) throw new Error('Plan binding mismatch.');
  const previewRoot = contained(join(project, plan.preview.replace(/\/index\.html$/, '')));
  const resultRoot = contained(join(project, '.docara-qa', 'results', planId));
  if (existsSync(resultRoot)) throw new Error(`Result already exists for ${planId}.`);
  ensure(join(resultRoot, 'screenshots'));

  const server = createServer((request, response) => {
    try {
      const url = new URL(request.url || '/', 'http://127.0.0.1');
      const requested = decodeURIComponent(url.pathname === '/' ? '/index.html' : url.pathname);
      const relativePath = normalize(requested).replace(/^[/\\]+/, '');
      const file = resolve(previewRoot, relativePath);
      if (file !== previewRoot && !file.startsWith(previewRoot + sep)) throw new Error('HTTP path escape.');
      const safe = contained(file);
      const stat = lstatSync(safe);
      if (!stat.isFile() || stat.isSymbolicLink() || stat.nlink !== 1) throw new Error('Unsafe HTTP file.');
      response.writeHead(200, {'content-type': mime(safe), 'cache-control':'no-store'});
      response.end(readFileSync(safe));
    } catch {
      response.writeHead(404, {'content-type':'text/plain; charset=utf-8'});
      response.end('Not found');
    }
  });
  await new Promise((resolveListen) => server.listen(0, '127.0.0.1', resolveListen));
  const port = server.address().port;
  const browser = await chromium.launch({headless: true});
  const scenarios = [];
  try {
    for (const scenario of plan.scenarios) {
      const context = await browser.newContext({
        viewport: scenario.viewport,
        colorScheme: scenario.theme,
        reducedMotion: 'reduce',
        locale: scenario.direction === 'rtl' ? 'ar' : 'ru',
      });
      const page = await context.newPage();
      const consoleErrors = [];
      const consoleWarnings = [];
      const failed = [];
      page.on('console', (message) => {
        if (message.type() === 'error') consoleErrors.push(message.text());
        if (message.type() === 'warning') consoleWarnings.push(message.text());
      });
      page.on('pageerror', (error) => consoleErrors.push(error.message));
      page.on('requestfailed', (request) => failed.push(`${request.url()}:${request.failure()?.errorText || 'failed'}`));
      page.on('response', (response) => { if (response.status() >= 400) failed.push(`${response.url()}:${response.status()}`); });
      await page.goto(`http://127.0.0.1:${port}/index.html`, {waitUntil: 'networkidle'});
      await page.evaluate(({theme, direction}) => {
        document.documentElement.classList.remove('theme-light', 'theme-dark');
        document.documentElement.classList.add(`theme-${theme}`);
        document.documentElement.dir = direction;
      }, {theme: scenario.theme, direction: scenario.direction});
      await page.keyboard.press('Tab');
      const acceptance = await page.evaluate(() => {
        const active = document.activeElement;
        const activeBox = active instanceof HTMLElement ? active.getBoundingClientRect() : null;
        const controls = [...document.querySelectorAll('button,a[href],input,select,textarea')];
        const accessibleText = (node) => node.getAttribute('aria-label') || node.getAttribute('title') || node.textContent || node.querySelector('img')?.getAttribute('alt') || [...(node.labels || [])].map((label) => label.textContent).join(' ');
        const unnamedNodes = controls.filter((node) => !(accessibleText(node) || '').trim());
        const imagesWithoutAlt = [...document.images].filter((image) => !image.hasAttribute('alt')).length;
        const ids = [...document.querySelectorAll('[id]')].map((node) => node.id);
        const duplicateIdValues = [...new Set(ids.filter((id, index) => ids.indexOf(id) !== index))];
        return {
          overflow: Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth),
          keyboard: active !== document.body && activeBox !== null && activeBox.width > 0 && activeBox.height > 0,
          a11y: Number(!document.title.trim()) + Number(!document.documentElement.lang) + unnamedNodes.length + imagesWithoutAlt + duplicateIdValues.length,
          a11yDetails: {
            title: document.title,
            lang: document.documentElement.lang,
            unnamed: unnamedNodes.map((node) => node.outerHTML.slice(0, 240)),
            imagesWithoutAlt,
            duplicateIds: duplicateIdValues.map((id) => ({id, nodes:[...document.querySelectorAll(`[id="${CSS.escape(id)}"]`)].map((node) => node.outerHTML.slice(0, 280))})),
          },
          reducedMotion: matchMedia('(prefers-reduced-motion: reduce)').matches,
        };
      });
      await page.keyboard.press('Escape');
      await page.waitForTimeout(80);
      const screenshot = await page.screenshot({fullPage: true, animations: 'disabled'});
      const comparison = await page.screenshot({fullPage: true, animations: 'disabled'});
      const relativeScreenshot = scenario.screenshot;
      putNew(join(resultRoot, relativeScreenshot), screenshot);
      if (failed.length || consoleErrors.length || consoleWarnings.length || acceptance.overflow || !acceptance.keyboard || !acceptance.reducedMotion || acceptance.a11y || !screenshot.equals(comparison)) {
        throw new Error(JSON.stringify({scenario:scenario.id, failed, consoleErrors, consoleWarnings, acceptance, stable:screenshot.equals(comparison)}));
      }
      scenarios.push({
        id: scenario.id,
        screenshot: relativeScreenshot,
        screenshot_sha256: sha(screenshot),
        a11y_violations: 0,
        console_errors: 0,
        console_warnings: 0,
        overflow: 0,
        keyboard: 'pass',
        reduced_motion: 'pass',
        visual_diff_pixels: 0,
      });
      await context.close();
    }
  } finally {
    await browser.close();
    await new Promise((resolveClose) => server.close(resolveClose));
  }
  const report = {schema:'docara.qa_report.v1', plan_id:planId, artifact_sha256:plan.artifact_sha256, scenarios};
  const reportBytes = JSON.stringify(report, null, 4) + '\n';
  putNew(join(resultRoot, 'report.json'), reportBytes);
  process.stdout.write(JSON.stringify({plan_id:planId, plan_sha256:sha(planBytes), artifact_sha256:plan.artifact_sha256, report_sha256:sha(reportBytes), scenarios:scenarios.length}) + '\n');
}
