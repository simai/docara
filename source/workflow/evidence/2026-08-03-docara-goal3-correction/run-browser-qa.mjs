#!/usr/bin/env node

import {createHash} from 'node:crypto';
import {existsSync, lstatSync, mkdirSync, readFileSync, realpathSync, writeFileSync} from 'node:fs';
import {createServer} from 'node:http';
import {extname, join, normalize, relative, resolve, sep} from 'node:path';
import {chromium} from 'playwright';

const rawArguments = process.argv.slice(2);
const recordReferences = rawArguments[0] === '--record-references';
if (recordReferences) rawArguments.shift();
const [projectArgument, ...planIds] = rawArguments;
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

function canonical(value) {
  if (Array.isArray(value)) return value.map(canonical);
  if (value !== null && typeof value === 'object') {
    return Object.fromEntries(Object.keys(value).sort().map((key) => [key, canonical(value[key])]));
  }
  return value;
}

function contentId(value, omittedKey) {
  const core = {...value};
  delete core[omittedKey];
  return sha(JSON.stringify(canonical(core)));
}

async function pixelDiff(context, reference, candidate) {
  const comparison = await context.newPage();
  try {
    return await comparison.evaluate(async ({reference, candidate}) => {
      const decode = async (source) => {
        const response = await fetch(`data:image/png;base64,${source}`);
        return createImageBitmap(await response.blob());
      };
      const [left, right] = await Promise.all([decode(reference), decode(candidate)]);
      if (left.width !== right.width || left.height !== right.height) return Math.max(left.width * left.height, right.width * right.height);
      const canvas = document.createElement('canvas');
      canvas.width = left.width; canvas.height = left.height;
      const ctx = canvas.getContext('2d', {willReadFrequently: true});
      ctx.drawImage(left, 0, 0);
      const a = ctx.getImageData(0, 0, left.width, left.height).data;
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(right, 0, 0);
      const b = ctx.getImageData(0, 0, right.width, right.height).data;
      let changed = 0;
      for (let i = 0; i < a.length; i += 4) {
        if (a[i] !== b[i] || a[i + 1] !== b[i + 1] || a[i + 2] !== b[i + 2] || a[i + 3] !== b[i + 3]) changed++;
      }
      return changed;
    }, {reference: reference.toString('base64'), candidate: candidate.toString('base64')});
  } finally {
    await comparison.close();
  }
}

for (const planId of planIds) {
  if (!/^[a-f0-9]{64}$/.test(planId)) throw new Error('Plan id must be SHA-256.');
  const planPath = contained(join(project, '.docara-qa', 'plans', `${planId}.json`));
  const planBytes = readFileSync(planPath);
  const plan = JSON.parse(planBytes);
  if (plan.plan_id !== planId) throw new Error('Plan binding mismatch.');
  if (contentId(plan, 'plan_id') !== planId) throw new Error('Plan content-address mismatch.');
  if (!plan.target?.locator) throw new Error('Plan target binding is missing.');
  if (recordReferences) {
    if (plan.schema !== 'docara.qa_plan_draft.v1' || plan.phase !== 'draft' || plan.reference?.reference_id) {
      throw new Error('Reference recording requires an immutable draft plan.');
    }
  } else if (plan.schema !== 'docara.qa_plan.v2' || plan.phase !== 'finalized' || !plan.reference?.reference_id || !plan.reference?.manifest_sha256) {
    throw new Error('Browser comparison requires a PHP-finalized immutable plan.');
  }
  const [subjectKind, subjectId] = String(plan.subject || '').split(':', 2);
  if (subjectKind !== plan.target.kind || subjectId !== plan.target.id) throw new Error('Subject/target binding mismatch.');
  const previewRoot = contained(join(project, plan.preview.replace(/\/index\.html$/, '')));
  const previewArtifact = readFileSync(contained(join(previewRoot, 'artifact.html')));
  const previewPage = readFileSync(contained(join(previewRoot, 'index.html')));
  if (sha(previewArtifact) !== plan.artifact_sha256
    || sha(previewArtifact) !== plan.target.html_sha256
    || sha(previewArtifact) !== plan.reference.artifact_sha256
    || sha(previewPage) !== plan.reference.page_html_sha256) {
    throw new Error('Preview byte binding mismatch.');
  }
  const resultRoot = contained(join(project, '.docara-qa', 'results', planId));
  const referenceRoot = contained(recordReferences
    ? join(project, '.docara-qa', 'reference-drafts', planId)
    : join(project, '.docara-qa', 'references', plan.reference.reference_id));
  if (recordReferences) {
    if (existsSync(referenceRoot)) throw new Error(`Reference draft already exists for ${planId}.`);
    ensure(join(referenceRoot, 'screenshots'));
  } else {
    if (existsSync(resultRoot)) throw new Error(`Result already exists for ${planId}.`);
    ensure(join(resultRoot, 'screenshots'));
  }

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
  const referenceManifest = recordReferences ? null : JSON.parse(readFileSync(contained(join(referenceRoot, 'reference.json'))));
  if (!recordReferences && (referenceManifest.reference_id !== plan.reference.reference_id
    || contentId(referenceManifest, 'reference_id') !== plan.reference.reference_id
    || sha(JSON.stringify(canonical(referenceManifest))) !== plan.reference.manifest_sha256
    || referenceManifest.source_plan_id !== plan.source_plan_id
    || referenceManifest.subject !== plan.subject
    || JSON.stringify(referenceManifest.target) !== JSON.stringify(plan.target)
    || referenceManifest.artifact_sha256 !== plan.artifact_sha256
    || referenceManifest.page_html_sha256 !== plan.reference.page_html_sha256)) {
    throw new Error('Production reference binding mismatch.');
  }
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
      const target = page.locator(plan.target.locator).first();
      if (await target.count() !== 1 || !await target.isVisible()) throw new Error(`Target locator is not visible: ${plan.target.locator}`);
      const screenshot = await target.screenshot({animations: 'disabled'});
      const relativeScreenshot = scenario.screenshot;
      if (failed.length || consoleErrors.length || consoleWarnings.length || acceptance.overflow || !acceptance.keyboard || !acceptance.reducedMotion || acceptance.a11y) {
        throw new Error(JSON.stringify({scenario:scenario.id, failed, consoleErrors, consoleWarnings, acceptance}));
      }
      if (recordReferences) {
        putNew(join(referenceRoot, relativeScreenshot), screenshot);
        scenarios.push({id: scenario.id, screenshot: relativeScreenshot, screenshot_sha256: sha(screenshot)});
        await context.close();
        continue;
      }
      const declaredReference = referenceManifest.scenarios.find((item) => item.id === scenario.id);
      if (!declaredReference) throw new Error(`Reference scenario is missing: ${scenario.id}`);
      const referenceBytes = readFileSync(contained(join(referenceRoot, declaredReference.screenshot)));
      if (sha(referenceBytes) !== declaredReference.screenshot_sha256) throw new Error(`Reference hash mismatch: ${scenario.id}`);
      const visualDiffPixels = await pixelDiff(context, referenceBytes, screenshot);
      putNew(join(resultRoot, relativeScreenshot), screenshot);
      if (failed.length || consoleErrors.length || consoleWarnings.length || acceptance.overflow || !acceptance.keyboard || !acceptance.reducedMotion || acceptance.a11y || visualDiffPixels !== 0) {
        throw new Error(JSON.stringify({scenario:scenario.id, failed, consoleErrors, consoleWarnings, acceptance, visualDiffPixels}));
      }
      scenarios.push({
        id: scenario.id,
        screenshot: relativeScreenshot,
        screenshot_sha256: sha(screenshot),
        reference_sha256: declaredReference.screenshot_sha256,
        a11y_violations: 0,
        console_errors: 0,
        console_warnings: 0,
        overflow: 0,
        keyboard: 'pass',
        reduced_motion: 'pass',
        visual_diff_pixels: visualDiffPixels,
      });
      await context.close();
    }
  } finally {
    await browser.close();
    await new Promise((resolveClose) => server.close(resolveClose));
  }
  if (recordReferences) {
    const reference = {schema:'docara.qa_reference_draft.v1', draft_plan_id:planId, subject:plan.subject, target:plan.target, artifact_sha256:plan.artifact_sha256, page_html_sha256:plan.reference.page_html_sha256, scenarios};
    const referenceBytes = JSON.stringify(reference, null, 4) + '\n';
    putNew(join(referenceRoot, 'reference.json'), referenceBytes);
    process.stdout.write(JSON.stringify({draft_plan_id:planId, target:plan.target, reference_draft_sha256:sha(JSON.stringify(canonical(reference))), scenarios:scenarios.length, next_operation:'qa.finalize_reference'}) + '\n');
  } else {
    const report = {schema:'docara.qa_report.v2', plan_id:planId, artifact_sha256:plan.artifact_sha256, target:plan.target, reference:plan.reference, scenarios};
    const reportBytes = JSON.stringify(report, null, 4) + '\n';
    putNew(join(resultRoot, 'report.json'), reportBytes);
    process.stdout.write(JSON.stringify({plan_id:planId, plan_sha256:sha(planBytes), target:plan.target, reference_id:plan.reference.reference_id, artifact_sha256:plan.artifact_sha256, report_sha256:sha(reportBytes), scenarios:scenarios.length}) + '\n');
  }
}
