// Docara's adapter to the SIMAI Framework theme palette (SF.ThemePalette).
//
// Reads one JSON request on stdin:
//   {"source": "<bytes of smart/theme-generator/js/theme-generator.js>",
//    "filename": "...", "color": "#rrggbb", "hue": int|null, "chroma": int|null}
// and writes one JSON result on stdout:
//   {"source": {"hex", "hue", "chroma"}, "variables": {...}, "css": "..."}
//
// The palette ships inside the pinned release's sf-theme-generator bundle,
// which publishes it as the public global SF.ThemePalette before it waits for
// the browser runtime (lit, smart-base). Docara runs those exact, hash-checked
// bytes in an isolated context with no browser: the element never defines
// itself, and nothing but the palette is used. No file is read or written.
import vm from 'node:vm';

const chunks = [];
for await (const chunk of process.stdin) chunks.push(chunk);
const request = JSON.parse(Buffer.concat(chunks).toString('utf8'));

const fail = (code, message) => {
  process.stderr.write(`${code}: ${message}\n`);
  process.exit(1);
};

if (typeof request?.source !== 'string' || typeof request?.color !== 'string') {
  fail('THEME_PALETTE_REQUEST_INVALID', 'a source and a colour are required');
}

// The bundle reaches the page only through `window`, `globalThis` and
// `addEventListener` (the runtime-ready event it waits for, which never comes
// here).
const sandbox = {addEventListener() {}, removeEventListener() {}};
sandbox.window = sandbox;
vm.createContext(sandbox, {codeGeneration: {strings: false, wasm: false}});
vm.runInContext(request.source, sandbox, {filename: request.filename || 'theme-generator.js', timeout: 10000});

const palette = sandbox.SF?.ThemePalette;
for (const name of ['colorToHct', 'themeFromHct', 'themeFromColor', 'themeVariables', 'themeToCss']) {
  if (typeof palette?.[name] !== 'function') fail('THEME_PALETTE_UNAVAILABLE', `SF.ThemePalette.${name} is missing`);
}
if (!palette.isThemeColor(request.color)) fail('THEME_COLOR_INVALID', request.color);

const read = palette.colorToHct(request.color);
const hue = Number.isInteger(request.hue) ? request.hue : read.hue;
const chroma = Number.isInteger(request.chroma) ? request.chroma : read.chroma;
// The colour alone is themeFromColor; hue and chroma, when given, are the
// generator's own inputs and win over how the colour is read.
const theme = Number.isInteger(request.hue) || Number.isInteger(request.chroma)
  ? palette.themeFromHct({hue, chroma})
  : palette.themeFromColor(request.color);

process.stdout.write(`${JSON.stringify({
  source: {hex: read.hex, hue, chroma},
  variables: palette.themeVariables(theme),
  css: palette.themeToCss(theme, ':root'),
})}\n`);
