#!/usr/bin/env node
// Renders every :::diagram block of the given Markdown pages with the Mermaid
// that Docara bundles, in headless Chrome, with the options the site shell
// uses (securityLevel 'strict', theme 'default'). Exits 1 when a diagram does
// not render, 2 when no Chrome is found.
//
//   node scripts/verify-mermaid-gallery.mjs [page.md ...]
//
// Without arguments it checks the diagram page and the diagram gallery of the
// documentation site. Set CHROME_BIN to pick the browser. Only Node built-ins
// are used: the Chrome DevTools Protocol is driven over Node's WebSocket.

import { spawn } from 'node:child_process';
import { copyFileSync, existsSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, relative, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const bundle = join(root, 'resources/portable/vendor/mermaid/mermaid.min.js');
const pages = process.argv.slice(2).length > 0
    ? process.argv.slice(2).map((page) => resolve(page))
    : [
        join(root, 'docs/site/content/ru/components/diagram.md'),
        join(root, 'docs/site/content/ru/components/diagram-gallery.md'),
    ];

export function extractDiagrams(markdown) {
    const diagrams = [];
    const lines = markdown.split('\n');
    for (let index = 0; index < lines.length; index++) {
        const open = lines[index].match(/^\s*:::diagram\b(.*)$/);
        if (!open) {
            continue;
        }
        const title = open[1].match(/title="([^"]*)"/)?.[1] ?? 'Diagram';
        const body = [];
        let end = index + 1;
        while (end < lines.length && lines[end].trim() !== ':::') {
            body.push(lines[end]);
            end++;
        }
        diagrams.push({ title, line: index + 1, source: body.join('\n').trim() });
        index = end;
    }

    return diagrams;
}

function findChrome() {
    const candidates = [
        process.env.CHROME_BIN,
        '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        '/Applications/Chromium.app/Contents/MacOS/Chromium',
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
    ];

    return candidates.find((candidate) => candidate && existsSync(candidate)) ?? null;
}

const sleep = (ms) => new Promise((done) => setTimeout(done, ms));

async function main() {
    const diagrams = pages.flatMap((page) => extractDiagrams(readFileSync(page, 'utf8'))
        .map((diagram) => ({ ...diagram, page: relative(root, page).startsWith('..') ? page : relative(root, page) })));
    if (diagrams.length === 0) {
        console.error('No :::diagram blocks found in ' + pages.join(', '));
        process.exit(1);
    }
    const chrome = findChrome();
    if (chrome === null) {
        console.error('No Chrome or Chromium found; set CHROME_BIN.');
        process.exit(2);
    }

    const work = mkdtempSync(join(tmpdir(), 'docara-mermaid-'));
    copyFileSync(bundle, join(work, 'mermaid.min.js'));
    const sources = JSON.stringify(diagrams.map((diagram) => diagram.source)).replace(/</g, '\\u003c');
    writeFileSync(join(work, 'index.html'), '<!doctype html><meta charset="utf-8">'
        + '<style>body{width:1200px;font-family:sans-serif}</style>'
        + '<script src="mermaid.min.js"></script><div id="out"></div>'
        + '<script id="sources" type="application/json">' + sources + '</script>');

    const browser = spawn(chrome, [
        '--headless=new', '--remote-debugging-port=0', '--user-data-dir=' + join(work, 'profile'),
        '--no-first-run', '--no-default-browser-check', '--window-size=1280,900', 'about:blank',
    ], { stdio: 'ignore' });
    let failures = 0;
    try {
        let port = null;
        for (let attempt = 0; attempt < 100 && port === null; attempt++) {
            const file = join(work, 'profile', 'DevToolsActivePort');
            if (existsSync(file)) {
                port = readFileSync(file, 'utf8').split('\n')[0].trim() || null;
            }
            if (port === null) {
                await sleep(100);
            }
        }
        if (port === null) {
            throw new Error('Chrome did not open a DevTools port.');
        }
        let target = null;
        for (let attempt = 0; attempt < 50 && target === null; attempt++) {
            try {
                const targets = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
                target = targets.find((candidate) => candidate.type === 'page') ?? null;
            } catch {
                // Chrome is still starting.
            }
            if (target === null) {
                await sleep(100);
            }
        }
        const socket = new WebSocket(target.webSocketDebuggerUrl);
        await new Promise((done) => socket.addEventListener('open', done));
        let id = 0;
        const pending = new Map();
        const consoleErrors = [];
        socket.addEventListener('message', (message) => {
            const data = JSON.parse(message.data);
            if (data.id && pending.has(data.id)) {
                pending.get(data.id)(data);
                pending.delete(data.id);
            } else if (data.method === 'Runtime.exceptionThrown') {
                consoleErrors.push(data.params.exceptionDetails.exception?.description ?? data.params.exceptionDetails.text);
            } else if (data.method === 'Runtime.consoleAPICalled' && data.params.type === 'error') {
                consoleErrors.push(data.params.args.map((arg) => arg.value ?? arg.description ?? '').join(' '));
            }
        });
        const send = (method, params = {}) => new Promise((done) => {
            const call = ++id;
            pending.set(call, done);
            socket.send(JSON.stringify({ id: call, method, params }));
        });
        await send('Runtime.enable');
        await send('Page.enable');
        await send('Page.navigate', { url: pathToFileURL(join(work, 'index.html')).href });
        for (let attempt = 0; attempt < 100; attempt++) {
            const ready = await send('Runtime.evaluate', { expression: 'typeof window.mermaid === "object"', returnByValue: true });
            if (ready.result?.result?.value === true) {
                break;
            }
            await sleep(100);
        }
        const evaluation = await send('Runtime.evaluate', {
            awaitPromise: true,
            returnByValue: true,
            expression: `(async () => {
                mermaid.initialize({ startOnLoad: false, securityLevel: 'strict', theme: 'default' });
                const sources = JSON.parse(document.getElementById('sources').textContent);
                const out = document.getElementById('out');
                const results = [];
                for (let index = 0; index < sources.length; index++) {
                    const started = performance.now();
                    try {
                        const { svg } = await mermaid.render('docara-gallery-' + index, sources[index]);
                        const ms = Math.round(performance.now() - started);
                        const holder = document.createElement('div');
                        holder.innerHTML = svg;
                        out.append(holder);
                        const box = holder.querySelector('svg')?.getBoundingClientRect();
                        const broken = /Syntax error|Parse error/i.test(holder.textContent);
                        results.push({ ok: !broken && !!box && box.width > 0 && box.height > 0, ms,
                            size: box ? Math.round(box.width) + 'x' + Math.round(box.height) : '',
                            error: broken ? 'Mermaid drew an error diagram' : '' });
                    } catch (error) {
                        results.push({ ok: false, ms: Math.round(performance.now() - started), size: '',
                            error: String(error && error.message || error).split('\\n').slice(0, 3).join(' ') });
                    }
                }
                return results;
            })()`,
        });
        const results = evaluation.result?.result?.value;
        if (!Array.isArray(results)) {
            throw new Error('Rendering did not finish: ' + JSON.stringify(evaluation.result?.exceptionDetails ?? evaluation));
        }
        let total = 0;
        results.forEach((result, index) => {
            const diagram = diagrams[index];
            const type = diagram.source.split('\n').find((line) => line.trim() !== '' && !line.trim().startsWith('%%'))?.trim().split(/\s/)[0];
            total += result.ms;
            if (!result.ok) {
                failures++;
            }
            console.log(`${result.ok ? 'ok  ' : 'FAIL'} ${String(result.ms).padStart(5)} ms  ${type.padEnd(20)} ${diagram.page}:${diagram.line} ${diagram.title}`
                + (result.ok ? `  ${result.size}` : `  ${result.error}`));
        });
        console.log(`${results.length - failures}/${results.length} diagrams rendered in ${total} ms with Mermaid ${bundledVersion()}.`);
        if (consoleErrors.length > 0) {
            console.log('Console errors:\n' + consoleErrors.map((error) => '  ' + error).join('\n'));
        }
        socket.close();
    } finally {
        browser.kill();
        await sleep(300);
        rmSync(work, { recursive: true, force: true });
    }
    process.exit(failures === 0 ? 0 : 1);
}

function bundledVersion() {
    return readFileSync(bundle, 'utf8').match(/version:"(\d+\.\d+\.\d+)"/)?.[1] ?? 'unknown';
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
    await main();
}
