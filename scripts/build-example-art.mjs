#!/usr/bin/env node
/*
 * Regenerate the illustrations the documentation examples show:
 *
 *   node scripts/build-example-art.mjs            write SVG sources and WebP images
 *   node scripts/build-example-art.mjs --check    fail if a committed SVG source differs
 *
 * Every image is drawn here as SVG from one art direction (palette, lighting,
 * shadow, grain and the red ring of the Docara mark), so the set stays
 * consistent and reproducible. Images carry no letters, digits or interface
 * text: bars and blocks stand in for words, so one file serves every locale.
 *
 * Images that pages name with a site path (/assets/...) go to docs/site/assets,
 * the others next to the Russian pages and into the component catalogue.
 * The SVG sources go to docs/example-art/. Rendering needs Google Chrome
 * (headless, through the DevTools protocol; set CHROME to another binary) and
 * cwebp (set CWEBP). Illustrations keep a transparent background, so they sit
 * on light and dark surfaces alike; the two backdrops are opaque because text
 * is laid over them.
 */
import { spawn, execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, mkdtempSync, readFileSync, rmSync, writeFileSync, copyFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const SOURCE_DIR = join(ROOT, 'docs/example-art');
const DOCS = 'docs/site/content/ru/assets';
const SITE = 'docs/site/assets';
const CATALOG = 'resources/component-catalog/assets';

/* ------------------------------------------------------------------ */
/* Art direction                                                       */
/* ------------------------------------------------------------------ */

const C = {
  ink: '#0D0F12',
  night: '#14171C',
  graphite: '#1D2026',
  slate: '#2A2E36',
  steel: '#3B414B',
  iron: '#555C68',
  mist: '#8D95A1',
  fog: '#C4C9D1',
  cloud: '#E3E6EA',
  paper: '#F5F6F8',
  white: '#FFFFFF',
  red: '#E81123',
  redLight: '#FF4A5B',
  redDeep: '#A50B19',
  cool: '#6F7F96',
};

function rng(seed) {
  let s = seed >>> 0;
  return () => {
    s = (s + 0x6d2b79f5) >>> 0;
    let t = s;
    t = Math.imul(t ^ (t >>> 15), t | 1);
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}

const n = (v) => Number(v.toFixed(2));

function defs() {
  return `<defs>
<linearGradient id="dark" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#30353D"/><stop offset="1" stop-color="${C.graphite}"/></linearGradient>
<linearGradient id="darker" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#22262C"/><stop offset="1" stop-color="${C.night}"/></linearGradient>
<linearGradient id="light" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${C.white}"/><stop offset="1" stop-color="#ECEEF2"/></linearGradient>
<linearGradient id="lightSoft" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#F1F3F6"/><stop offset="1" stop-color="#E2E5EA"/></linearGradient>
<linearGradient id="red" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${C.redLight}"/><stop offset=".55" stop-color="${C.red}"/><stop offset="1" stop-color="${C.redDeep}"/></linearGradient>
<linearGradient id="ring" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FF6B78"/><stop offset=".45" stop-color="${C.red}"/><stop offset="1" stop-color="#9E0A17"/></linearGradient>
<linearGradient id="tile" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#3A3F48"/><stop offset="1" stop-color="#23272D"/></linearGradient>
<linearGradient id="tileLight" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#E7EAEE"/><stop offset="1" stop-color="#D3D8DF"/></linearGradient>
<linearGradient id="sheen" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".16"/><stop offset=".35" stop-color="#fff" stop-opacity="0"/></linearGradient>
<radialGradient id="glowRed"><stop offset="0" stop-color="${C.red}" stop-opacity=".55"/><stop offset=".45" stop-color="${C.red}" stop-opacity=".16"/><stop offset="1" stop-color="${C.red}" stop-opacity="0"/></radialGradient>
<filter id="shadow" x="-30%" y="-30%" width="160%" height="170%" color-interpolation-filters="sRGB">
<feGaussianBlur in="SourceAlpha" stdDeviation="22"/><feOffset dy="22" result="a"/><feComponentTransfer in="a" result="a"><feFuncA type="linear" slope=".30"/></feComponentTransfer>
<feGaussianBlur in="SourceAlpha" stdDeviation="3"/><feOffset dy="3" result="b"/><feComponentTransfer in="b" result="b"><feFuncA type="linear" slope=".22"/></feComponentTransfer>
<feFlood flood-color="#06080B"/><feComposite in2="a" operator="in" result="a2"/>
<feFlood flood-color="#06080B"/><feComposite in2="b" operator="in" result="b2"/>
<feMerge><feMergeNode in="a2"/><feMergeNode in="b2"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
<filter id="soft" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="14"/></filter>
<filter id="grain" x="0" y="0" width="100%" height="100%"><feTurbulence type="fractalNoise" baseFrequency=".85" numOctaves="2" seed="7" stitchTiles="stitch"/><feColorMatrix values="0 0 0 0 1  0 0 0 0 1  0 0 0 0 1  0 0 0 .55 0"/></filter>
</defs>`;
}

/** view crops the drawing ([x, y, w, h]) so a composition can fill more of the frame. */
function svg(width, height, body, view = [0, 0, width, height]) {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="${view.join(' ')}">\n${defs()}\n${body}\n</svg>\n`;
}

/* Primitives -------------------------------------------------------- */

let canvas = { w: 0, h: 0 };

/** A soft light; it is kept inside the canvas so no edge cuts it off. */
function glow(cx, cy, r, kind = 'Red', opacity = 1) {
  const rx = Math.min(r, cx, canvas.w - cx);
  const ry = Math.min(r * 0.8, cy, canvas.h - cy);
  return `<ellipse cx="${n(cx)}" cy="${n(cy)}" rx="${n(rx)}" ry="${n(ry)}" fill="url(#glow${kind})" opacity="${opacity}"/>`;
}

let hazeId = 0;

/** A large, soft colour field for the opaque backdrops. */
function haze(cx, cy, rx, ry, color, opacity) {
  const id = `haze${++hazeId}`;
  return `<radialGradient id="${id}"><stop offset="0" stop-color="${color}" stop-opacity="${opacity}"/><stop offset=".5" stop-color="${color}" stop-opacity="${n(opacity * 0.45)}"/><stop offset="1" stop-color="${color}" stop-opacity="0"/></radialGradient>`
    + `<ellipse cx="${cx}" cy="${cy}" rx="${rx}" ry="${ry}" fill="url(#${id})"/>`;
}

/** A card: tone is dark, darker, light or red. */
function card(x, y, w, h, tone = 'dark', { r = 18, shadow = true } = {}) {
  const fill = { dark: 'url(#dark)', darker: 'url(#darker)', light: 'url(#light)', red: 'url(#red)', soft: 'url(#lightSoft)' }[tone];
  const edge = tone === 'light' || tone === 'soft' ? 'rgba(13,15,18,.10)' : 'rgba(255,255,255,.10)';
  return `<g${shadow ? ' filter="url(#shadow)"' : ''}><rect x="${n(x)}" y="${n(y)}" width="${n(w)}" height="${n(h)}" rx="${r}" fill="${fill}"/>`
    + `<rect x="${n(x + 0.75)}" y="${n(y + 0.75)}" width="${n(w - 1.5)}" height="${n(h - 1.5)}" rx="${r - 0.75}" fill="url(#sheen)" stroke="${edge}" stroke-width="1.5"/></g>`;
}

function bar(x, y, w, h, color, opacity = 1) {
  return `<rect x="${n(x)}" y="${n(y)}" width="${n(Math.max(w, h))}" height="${n(h)}" rx="${n(h / 2)}" fill="${color}"${opacity < 1 ? ` opacity="${opacity}"` : ''}/>`;
}

/** Lines of "text": widths are fractions of w. */
function lines(x, y, w, fractions, { h = 10, gap = 12, color = C.fog, accent = null, accentAt = -1 } = {}) {
  return fractions.map((f, i) => bar(x, y + i * (h + gap), w * f, h, i === accentAt && accent ? accent : color)).join('');
}

/** Indented "code" lines on a dark panel. */
function code(x, y, w, rows, { h = 9, gap = 13, seed = 3 } = {}) {
  const r = rng(seed);
  let out = '';
  rows.forEach((indent, i) => {
    let cx = x + indent * 22;
    const yy = y + i * (h + gap);
    const parts = 1 + Math.floor(r() * 3);
    for (let p = 0; p < parts; p++) {
      const pw = 24 + r() * (w * 0.28);
      const color = p === 0 && r() > 0.55 ? C.red : (r() > 0.5 ? C.iron : C.mist);
      if (cx + pw > x + w) break;
      out += bar(cx, yy, pw, h, color, color === C.mist ? 0.75 : 1);
      cx += pw + 10;
    }
  });
  return out;
}

/** The Docara ring: a red ring with a soft glow and a highlight arc. */
function ring(cx, cy, r, sw, { glowing = true } = {}) {
  const hr = r - sw * 0.18;
  const a0 = (205 * Math.PI) / 180;
  const a1 = (290 * Math.PI) / 180;
  const arc = `M ${n(cx + hr * Math.cos(a0))} ${n(cy + hr * Math.sin(a0))} A ${n(hr)} ${n(hr)} 0 0 1 ${n(cx + hr * Math.cos(a1))} ${n(cy + hr * Math.sin(a1))}`;
  return (glowing ? `<circle cx="${n(cx)}" cy="${n(cy)}" r="${n(r)}" fill="none" stroke="${C.red}" stroke-width="${n(sw * 1.6)}" opacity=".45" filter="url(#soft)"/>` : '')
    + `<circle cx="${n(cx)}" cy="${n(cy + sw * 0.18)}" r="${n(r)}" fill="none" stroke="#5E0610" stroke-width="${n(sw)}" opacity=".55"/>`
    + `<circle cx="${n(cx)}" cy="${n(cy)}" r="${n(r)}" fill="none" stroke="url(#ring)" stroke-width="${n(sw)}"/>`
    + `<path d="${arc}" fill="none" stroke="#fff" stroke-opacity=".38" stroke-width="${n(sw * 0.22)}" stroke-linecap="round"/>`;
}

/** Abstract picture tile: gradient with ring arcs instead of a stock landscape. */
function picture(x, y, w, h, { tone = 'dark', r = 12, seed = 1 } = {}) {
  const id = `clip${seed}${Math.round(x)}${Math.round(y)}`;
  const base = tone === 'dark' ? 'url(#tile)' : 'url(#tileLight)';
  const line = tone === 'dark' ? 'rgba(255,255,255,.10)' : 'rgba(13,15,18,.10)';
  const cx = x + w * 0.72;
  const cy = y + h * 0.62;
  let arcs = '';
  for (let i = 1; i <= 4; i++) arcs += `<circle cx="${n(cx)}" cy="${n(cy)}" r="${n(h * 0.16 * i)}" fill="none" stroke="${line}" stroke-width="2"/>`;
  return `<clipPath id="${id}"><rect x="${n(x)}" y="${n(y)}" width="${n(w)}" height="${n(h)}" rx="${r}"/></clipPath>`
    + `<g clip-path="url(#${id})"><rect x="${n(x)}" y="${n(y)}" width="${n(w)}" height="${n(h)}" fill="${base}"/>`
    + `<ellipse cx="${n(cx)}" cy="${n(cy)}" rx="${n(w * 0.5)}" ry="${n(h * 0.5)}" fill="url(#glowRed)" opacity=".55"/>${arcs}`
    + ring(cx, cy, h * 0.16, Math.max(4, h * 0.05), { glowing: false })
    + `<path d="M ${n(x)} ${n(y + h * 0.82)} C ${n(x + w * 0.3)} ${n(y + h * 0.62)} ${n(x + w * 0.55)} ${n(y + h * 0.98)} ${n(x + w)} ${n(y + h * 0.74)} L ${n(x + w)} ${n(y + h)} L ${n(x)} ${n(y + h)} Z" fill="${tone === 'dark' ? '#16191E' : '#C9CED6'}" opacity=".8"/></g>`;
}

function dots(x, y, colors = ['#FF5F57', '#3B414B', '#3B414B'], r = 5) {
  return colors.map((c, i) => `<circle cx="${n(x + i * r * 3.2)}" cy="${n(y)}" r="${r}" fill="${c}"/>`).join('');
}

function connector(points, { color = C.mist, dashed = true, width = 3, opacity = 0.8, end = true } = {}) {
  const [a, b] = points;
  const mx = (a[0] + b[0]) / 2;
  const d = `M ${n(a[0])} ${n(a[1])} C ${n(mx)} ${n(a[1])} ${n(mx)} ${n(b[1])} ${n(b[0])} ${n(b[1])}`;
  return `<path d="${d}" fill="none" stroke="${color}" stroke-width="${width}" stroke-linecap="round"${dashed ? ' stroke-dasharray="2 10"' : ''} opacity="${opacity}"/>`
    + (end ? `<circle cx="${n(b[0])}" cy="${n(b[1])}" r="${width + 3}" fill="${color}"/>` : '');
}

function check(cx, cy, r) {
  return `<circle cx="${n(cx)}" cy="${n(cy)}" r="${n(r)}" fill="url(#red)"/>`
    + `<path d="M ${n(cx - r * 0.42)} ${n(cy + r * 0.02)} L ${n(cx - r * 0.1)} ${n(cy + r * 0.34)} L ${n(cx + r * 0.46)} ${n(cy - r * 0.3)}" fill="none" stroke="#fff" stroke-width="${n(r * 0.2)}" stroke-linecap="round" stroke-linejoin="round"/>`;
}

function toggle(x, y, w, h, on = true) {
  return card(x, y, w, h, on ? 'red' : 'darker', { r: h / 2 })
    + `<circle cx="${n(on ? x + w - h / 2 : x + h / 2)}" cy="${n(y + h / 2)}" r="${n(h * 0.36)}" fill="#fff"/>`;
}

/** A documentation page in a browser frame. theme: light or dark. */
function page(x, y, w, h, { theme = 'light', seed = 5, sidebar = true, toc = false, hero = true, codeBlock = true } = {}) {
  const dark = theme === 'dark';
  const ink = dark ? C.steel : C.fog;
  const soft = dark ? C.slate : C.cloud;
  const strong = dark ? C.iron : C.mist;
  const pad = w * 0.045;
  const top = h * 0.11;
  let out = card(x, y, w, h, dark ? 'darker' : 'light', { r: 22 });
  out += dots(x + pad, y + top * 0.5, dark ? ['#FF5F57', C.steel, C.steel] : ['#FF5F57', C.fog, C.fog], Math.max(4, w * 0.006));
  out += `<line x1="${n(x)}" y1="${n(y + top)}" x2="${n(x + w)}" y2="${n(y + top)}" stroke="${dark ? 'rgba(255,255,255,.07)' : 'rgba(13,15,18,.07)'}" stroke-width="2"/>`;
  out += ring(x + pad + w * 0.075, y + top * 0.5, top * 0.17, top * 0.075, { glowing: false });
  out += bar(x + pad + w * 0.105, y + top * 0.5 - 5, w * 0.08, 10, strong);
  out += [0.07, 0.06, 0.08].map((f, i) => bar(x + w - pad - w * 0.27 + i * w * 0.095, y + top * 0.5 - 5, w * f, 10, ink)).join('');
  let cx = x + pad;
  const cy = y + top + h * 0.07;
  if (sidebar) {
    const sw = w * 0.2;
    out += lines(cx, cy, sw, [0.8, 0.6, 0.72, 0.5, 0.66, 0.58, 0.74], { h: 9, gap: h * 0.045, color: ink, accent: C.red, accentAt: 2 });
    cx += sw + w * 0.05;
  }
  const right = toc ? x + w - pad - w * 0.16 : x + w - pad;
  const cw = right - cx;
  out += bar(cx, cy, cw * 0.55, h * 0.04, strong);
  out += lines(cx, cy + h * 0.085, cw, [0.96, 0.88, 0.7], { h: 9, gap: 11, color: ink });
  let yy = cy + h * 0.19;
  if (hero) {
    out += picture(cx, yy, cw, h * 0.3, { tone: dark ? 'dark' : 'light', seed });
    yy += h * 0.34;
  }
  if (codeBlock) {
    out += `<rect x="${n(cx)}" y="${n(yy)}" width="${n(cw)}" height="${n(h * 0.2)}" rx="12" fill="${dark ? '#0F1115' : C.graphite}"/>`;
    out += code(cx + 22, yy + 22, cw - 44, [0, 1, 1, 2, 1, 0].slice(0, Math.max(2, Math.floor((h * 0.2 - 30) / 22))), { seed });
    yy += h * 0.24;
  }
  const cards = 3;
  const gap = cw * 0.04;
  const cwid = (cw - gap * (cards - 1)) / cards;
  for (let i = 0; i < cards && yy + h * 0.12 < y + h - pad * 0.6; i++) {
    out += `<rect x="${n(cx + i * (cwid + gap))}" y="${n(yy)}" width="${n(cwid)}" height="${n(Math.min(h * 0.16, y + h - pad * 0.8 - yy))}" rx="12" fill="${soft}"/>`;
    out += bar(cx + i * (cwid + gap) + 16, yy + 18, cwid * 0.5, 9, i === 1 ? C.red : strong);
    out += bar(cx + i * (cwid + gap) + 16, yy + 38, cwid * 0.75, 8, ink);
  }
  if (toc) out += lines(right + w * 0.03, cy, w * 0.13, [0.9, 0.7, 0.8, 0.6, 0.75], { h: 8, gap: 14, color: ink, accent: C.red, accentAt: 1 });
  return out;
}

function sheet(x, y, w, h, tone, inner, { stack = 0 } = {}) {
  let out = '';
  for (let i = stack; i > 0; i--) out += card(x + i * 10, y + i * 10, w, h, tone === 'light' ? 'soft' : 'darker', { r: 16 });
  return out + card(x, y, w, h, tone, { r: 16 }) + inner;
}

function grain(width, height, opacity) {
  return `<rect width="${width}" height="${height}" filter="url(#grain)" opacity="${opacity}"/>`;
}

/* ------------------------------------------------------------------ */
/* Scenes                                                              */
/* ------------------------------------------------------------------ */

const scenes = {};

/* Surface background: quiet on the left, rings and light on the right. */
scenes['surface-backdrop'] = { w: 1600, h: 900, opaque: true, out: [SITE], draw: (W, H) => {
  let s = `<rect width="${W}" height="${H}" fill="${C.ink}"/>`;
  s += haze(1260, 440, 760, 560, '#2A303A', 0.9) + haze(1460, 820, 520, 380, C.red, 0.32) + haze(980, 80, 520, 300, C.cool, 0.22);
  for (let i = 1; i <= 9; i++) s += `<circle cx="1250" cy="470" r="${70 * i}" fill="none" stroke="#fff" stroke-opacity="${n(0.075 - i * 0.006)}" stroke-width="1.5"/>`;
  s += `<circle cx="1250" cy="470" r="355" fill="none" stroke="${C.red}" stroke-opacity=".25" stroke-width="1.5" stroke-dasharray="3 14"/>`;
  s += ring(1250, 470, 150, 26);
  s += ring(1490, 190, 38, 9);
  s += `<circle cx="1018" cy="700" r="6" fill="${C.red}"/><circle cx="1395" cy="640" r="4" fill="#fff" opacity=".5"/><circle cx="1080" cy="300" r="3" fill="#fff" opacity=".35"/>`;
  const r = rng(11);
  for (let i = 0; i < 70; i++) {
    const x = 860 + r() * 740;
    const y = r() * H;
    s += `<circle cx="${n(x)}" cy="${n(y)}" r="${n(0.8 + r() * 1.6)}" fill="#fff" opacity="${n(0.08 + r() * 0.25 * ((x - 800) / 800))}"/>`;
  }
  s += `<linearGradient id="fadeLeft" x1="0" x2="1"><stop offset="0" stop-color="${C.ink}" stop-opacity=".85"/><stop offset=".45" stop-color="${C.ink}" stop-opacity=".2"/><stop offset=".6" stop-color="${C.ink}" stop-opacity="0"/></linearGradient>`;
  s += `<rect width="${W}" height="${H}" fill="url(#fadeLeft)"/>`;
  return s + grain(W, H, 0.06);
} };

/* Hero background: ribbons of light and a large ring cut by the edge. */
scenes['hero-backdrop'] = { w: 1600, h: 900, opaque: true, out: [SITE], draw: (W, H) => {
  let s = `<rect width="${W}" height="${H}" fill="#101318"/>`;
  s += haze(800, 460, 900, 420, '#232832', 0.9) + haze(300, 100, 620, 320, C.cool, 0.26) + haze(1350, 900, 760, 380, C.red, 0.34);
  for (let i = 0; i < 14; i++) {
    const y0 = 560 + i * 16;
    s += `<path d="M -40 ${y0} C 420 ${y0 - 220 + i * 6} 980 ${y0 + 120 - i * 8} 1640 ${y0 - 300 + i * 10}" fill="none" stroke="${i === 6 ? C.red : '#fff'}" stroke-opacity="${i === 6 ? 0.65 : n(0.03 + (i % 4) * 0.012)}" stroke-width="${i === 6 ? 2.5 : 1.5}"/>`;
  }
  s += ring(1360, 430, 170, 26);
  for (let i = 1; i <= 4; i++) s += `<circle cx="1360" cy="430" r="${170 + i * 64}" fill="none" stroke="#fff" stroke-opacity="${n(0.07 - i * 0.012)}" stroke-width="1.5"/>`;
  s += ring(1080, 600, 30, 8);
  s += grain(W, H, 0.06);
  return s;
} };

/* Hero split: a documentation page with components lifting off it. */
scenes['hero-split'] = { w: 1600, h: 900, view: [110, 32, 1428.57, 803.57], out: [DOCS, CATALOG], draw: () => {
  let s = glow(1290, 260, 220, 'Red', 0.7);
  s += page(330, 110, 1000, 660, { theme: 'light', seed: 2 });
  s += sheet(170, 470, 400, 300, 'dark', code(206, 520, 330, [0, 1, 2, 2, 1, 0, 1, 0], { seed: 9 }) + dots(206, 500, [C.red, C.steel, C.steel], 5));
  s += card(1110, 470, 360, 150, 'dark');
  s += check(1170, 545, 30) + lines(1222, 520, 210, [0.85, 0.55], { h: 11, gap: 16, color: C.iron, accent: C.fog, accentAt: 0 });
  s += card(1180, 650, 290, 96, 'light') + toggle(1206, 678, 84, 40) + lines(1312, 684, 130, [0.9, 0.6], { h: 9, gap: 12, color: C.fog });
  s += ring(1405, 175, 72, 20);
  return s;
} };

/* Hero side: three fanned pages with the ring above them. */
scenes['hero-side'] = { w: 1600, h: 900, out: [SITE, CATALOG], draw: () => {
  let s = glow(800, 260, 300, 'Red', 0.7);
  s += `<g transform="rotate(-9 560 560)">${page(240, 250, 600, 520, { theme: 'dark', seed: 4, hero: false })}</g>`;
  s += `<g transform="rotate(8 1040 560)">${page(760, 250, 600, 520, { theme: 'dark', seed: 6, codeBlock: false })}</g>`;
  s += page(500, 200, 600, 560, { theme: 'light', seed: 8, sidebar: false });
  s += ring(800, 128, 70, 18);
  return s;
} };

/* Figure: a framed product scene with a chart and a floating panel. */
scenes['figure-scene'] = { w: 1600, h: 900, out: [DOCS, CATALOG], draw: () => {
  let s = glow(1240, 640, 300, 'Red', 0.5);
  const x = 220;
  const y = 110;
  const w = 1080;
  const h = 660;
  s += card(x, y, w, h, 'darker', { r: 24 });
  s += dots(x + 40, y + 40, ['#FF5F57', C.steel, C.steel], 6);
  s += ring(x + 120, y + 40, 12, 5, { glowing: false }) + bar(x + 144, y + 35, 90, 10, C.iron);
  s += `<line x1="${x}" y1="${y + 80}" x2="${x + w}" y2="${y + 80}" stroke="rgba(255,255,255,.07)" stroke-width="2"/>`;
  s += lines(x + 40, y + 120, 180, [0.85, 0.65, 0.75, 0.55, 0.7, 0.6], { h: 10, gap: 30, color: C.steel, accent: C.red, accentAt: 1 });
  const gx = x + 270;
  const gy = y + 120;
  s += bar(gx, gy, 300, 22, C.iron) + lines(gx, gy + 46, 520, [0.9], { h: 10, gap: 12, color: C.steel });
  s += [0, 1, 2].map((i) => `<rect x="${gx + i * 260}" y="${gy + 100}" width="240" height="84" rx="14" fill="${C.graphite}"/>${bar(gx + i * 260 + 24, gy + 124, 70, 12, i === 0 ? C.red : C.iron)}${bar(gx + i * 260 + 24, gy + 152, 150, 9, C.steel)}`).join('');
  const chartY = gy + 210;
  const cw = 760;
  const ch = 270;
  s += `<rect x="${gx}" y="${chartY}" width="${cw}" height="${ch}" rx="16" fill="${C.graphite}"/>`;
  for (let i = 1; i <= 3; i++) s += `<line x1="${gx + 30}" y1="${chartY + i * 62}" x2="${gx + cw - 30}" y2="${chartY + i * 62}" stroke="rgba(255,255,255,.06)" stroke-width="2"/>`;
  const values = [0.18, 0.42, 0.3, 0.62, 0.5, 0.78, 0.64, 0.86, 0.74, 0.96];
  const pts = values.map((v, i) => [gx + 40 + (i * (cw - 80)) / (values.length - 1), chartY + ch - 34 - v * (ch - 80)]);
  let path = `M ${n(pts[0][0])} ${n(pts[0][1])}`;
  for (let i = 1; i < pts.length; i++) {
    const [x0, y0] = pts[i - 1];
    const [x1, y1] = pts[i];
    const mx = (x0 + x1) / 2;
    path += ` C ${n(mx)} ${n(y0)} ${n(mx)} ${n(y1)} ${n(x1)} ${n(y1)}`;
  }
  s += `<linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${C.red}" stop-opacity=".38"/><stop offset="1" stop-color="${C.red}" stop-opacity="0"/></linearGradient>`;
  s += `<path d="${path} L ${n(pts[pts.length - 1][0])} ${chartY + ch - 20} L ${n(pts[0][0])} ${chartY + ch - 20} Z" fill="url(#areaFill)"/>`;
  s += `<path d="${path}" fill="none" stroke="${C.red}" stroke-width="5" stroke-linecap="round"/>`;
  const last = pts[pts.length - 1];
  s += `<circle cx="${n(last[0])}" cy="${n(last[1])}" r="11" fill="${C.graphite}" stroke="${C.red}" stroke-width="5"/>`;
  s += card(1150, 560, 290, 170, 'light', { r: 20 });
  s += ring(1214, 645, 36, 11, { glowing: false }) + lines(1274, 618, 130, [0.9, 0.6, 0.75], { h: 10, gap: 14, color: C.fog, accent: C.mist, accentAt: 0 });
  return s;
} };

/* Figure, tall subject: a phone with a mobile page (shows contain vs cover). */
scenes['figure-tall'] = { w: 900, h: 1200, out: [DOCS], draw: () => {
  let s = '';
  s += card(240, 90, 420, 1020, 'darker', { r: 64 });
  s += `<rect x="262" y="112" width="376" height="976" rx="46" fill="${C.paper}"/>`;
  s += `<rect x="400" y="128" width="100" height="22" rx="11" fill="${C.ink}"/>`;
  s += ring(306, 196, 14, 6, { glowing: false }) + bar(330, 191, 80, 10, C.mist) + bar(570, 188, 36, 16, C.fog);
  s += picture(292, 236, 316, 230, { tone: 'light', seed: 31 });
  s += bar(292, 496, 230, 20, C.iron) + lines(292, 534, 316, [0.95, 0.85, 0.6], { h: 9, gap: 12, color: C.fog });
  s += `<rect x="292" y="624" width="150" height="44" rx="22" fill="url(#red)"/>`;
  for (let i = 0; i < 3; i++) {
    const yy = 700 + i * 120;
    s += `<rect x="292" y="${yy}" width="316" height="100" rx="16" fill="${C.cloud}"/>`;
    s += `<rect x="310" y="${yy + 20}" width="60" height="60" rx="12" fill="${i === 1 ? C.red : C.fog}"/>` + lines(390, yy + 30, 190, [0.8, 0.55], { h: 9, gap: 14, color: C.fog, accent: C.mist, accentAt: 0 });
  }
  s += `<rect x="390" y="1062" width="120" height="8" rx="4" fill="${C.ink}" opacity=".7"/>`;
  return s;
} };

/* Figure 4:3 and the build scheme: source, build, checked output. */
scenes['figure-flow'] = { w: 1600, h: 1200, out: [DOCS, CATALOG], draw: () => {
  let s = '';
  s += sheet(120, 300, 400, 520, 'light', lines(170, 360, 300, [0.55, 0.95, 0.85, 0.9, 0.6], { h: 12, gap: 20, color: C.fog, accent: C.mist, accentAt: 0 }) + picture(170, 560, 300, 180, { tone: 'light', seed: 41 }), { stack: 2 });
  s += connector([[540, 560], [680, 600]], { color: C.mist });
  s += ring(800, 600, 110, 28);
  s += connector([[920, 600], [1060, 560]], { color: C.red, opacity: 1 });
  s += card(1130, 260, 360, 240, 'dark') + lines(1170, 300, 280, [0.6, 0.9, 0.75], { h: 11, gap: 18, color: C.steel, accent: C.iron, accentAt: 0 }) + `<rect x="1170" y="410" width="280" height="56" rx="10" fill="${C.slate}"/>`;
  s += card(1100, 540, 390, 380, 'light') + picture(1130, 570, 330, 160, { tone: 'light', seed: 42 }) + lines(1130, 760, 330, [0.7, 0.95, 0.8], { h: 11, gap: 18, color: C.fog, accent: C.mist, accentAt: 0 });
  s += check(1470, 540, 44);
  return s;
} };

/* Media, right: the Docara pipeline (sources, components, page). */
scenes['media-flow'] = { w: 1600, h: 900, out: [DOCS, CATALOG], draw: () => {
  let s = '';
  s += sheet(90, 90, 250, 190, 'dark', code(118, 124, 200, [0, 1, 1, 2, 1], { seed: 12 }), { stack: 1 });
  s += sheet(90, 355, 250, 190, 'light', lines(118, 385, 194, [0.5, 0.95, 0.85, 0.7], { h: 10, gap: 18, color: C.fog, accent: C.mist, accentAt: 0 }), { stack: 1 });
  s += sheet(90, 620, 250, 190, 'dark', code(118, 654, 200, [0, 1, 2, 1, 0], { seed: 13 }), { stack: 1 });
  s += connector([[360, 185], [460, 430]]) + connector([[360, 450], [450, 450]]) + connector([[360, 715], [460, 470]]);
  s += ring(530, 450, 62, 18);
  const chips = [[650, 120, 'dark'], [650, 290, 'light'], [650, 460, 'dark'], [650, 630, 'light']];
  chips.forEach(([x, y, t], i) => {
    s += connector([[600, 450], [x - 8, y + 55]], { color: i === 2 ? C.red : C.mist, opacity: i === 2 ? 1 : 0.55, end: false });
    s += card(x, y, 250, 110, t);
    s += `<rect x="${x + 22}" y="${y + 27}" width="56" height="56" rx="12" fill="${i === 2 ? C.red : (t === 'dark' ? C.steel : C.fog)}"/>`;
    s += lines(x + 96, y + 36, 130, [0.9, 0.6], { h: 10, gap: 16, color: t === 'dark' ? C.iron : C.fog });
    s += connector([[x + 258, y + 55], [1000, 260 + i * 120]], { color: i === 2 ? C.red : C.mist, opacity: i === 2 ? 1 : 0.55, end: false });
  });
  s += page(1000, 90, 520, 720, { theme: 'light', seed: 14, sidebar: false });
  return s;
} };

/* Media, left: page components arranged around the ring. */
scenes['media-components'] = { w: 1600, h: 900, out: [DOCS, CATALOG], draw: () => {
  let s = glow(800, 450, 220, 'Red', 0.5);
  const hub = [800, 450];
  const spots = [[420, 210], [1180, 210], [360, 470], [1240, 470], [480, 720], [1120, 720]];
  spots.forEach(([x, y], i) => { s += connector([hub, [x, y]], { color: i % 2 ? C.red : C.mist, opacity: i % 2 ? 0.9 : 0.6, end: false }); });
  s += card(230, 120, 380, 160, 'light') + `<circle cx="300" cy="200" r="34" fill="url(#red)"/><rect x="296" y="178" width="8" height="28" rx="4" fill="#fff"/><circle cx="300" cy="218" r="5" fill="#fff"/>` + lines(356, 172, 220, [0.9, 0.6], { h: 12, gap: 20, color: C.fog, accent: C.mist, accentAt: 0 }) + `<rect x="238" y="266" width="364" height="8" rx="4" fill="${C.red}"/>`;
  s += sheet(990, 90, 400, 260, 'dark', dots(1024, 124, ['#FF5F57', C.steel, C.steel], 6) + code(1024, 160, 340, [0, 1, 1, 2, 1, 0], { seed: 16 }));
  s += card(200, 420, 330, 110, 'light', { r: 55 }) + `<circle cx="475" cy="475" r="38" fill="url(#red)"/><path d="M 466 459 L 484 475 L 466 491" fill="none" stroke="#fff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>` + bar(250, 468, 150, 14, C.fog);
  s += card(1070, 400, 340, 210, 'light') + `<path d="M 1070 440 Q 1070 400 1110 400 L 1170 400 L 1170 446 L 1070 446 Z" fill="${C.red}"/>` + `<rect x="1180" y="408" width="96" height="38" rx="8" fill="${C.cloud}"/><rect x="1286" y="408" width="96" height="38" rx="8" fill="${C.cloud}"/>` + lines(1098, 480, 190, [0.95, 0.75, 0.85], { h: 10, gap: 16, color: C.fog }) + `<rect x="1306" y="476" width="76" height="76" rx="12" fill="${C.cloud}"/>`;
  s += card(300, 620, 340, 220, 'light') + picture(324, 644, 150, 110, { tone: 'light', seed: 17 }) + lines(492, 656, 128, [0.9, 0.7, 0.8], { h: 9, gap: 14, color: C.fog }) + `<rect x="492" y="728" width="90" height="22" rx="11" fill="${C.red}"/>` + [0, 1, 2].map((i) => `<rect x="${324 + i * 102}" y="${774}" width="92" height="46" rx="10" fill="${C.cloud}"/>`).join('');
  s += card(980, 650, 320, 200, 'light');
  for (let r = 0; r < 2; r++) for (let c = 0; c < 3; c++) {
    const fx = 1004 + c * 96;
    const fy = 672 + r * 82;
    s += (r === 1 && c === 1) ? `<rect x="${fx}" y="${fy}" width="80" height="70" rx="10" fill="url(#red)"/>` : ((r + c) % 2 ? `<rect x="${fx + 1}" y="${fy + 1}" width="78" height="68" rx="10" fill="none" stroke="${C.fog}" stroke-width="2" stroke-dasharray="6 6"/>` : `<rect x="${fx}" y="${fy}" width="80" height="70" rx="10" fill="${C.fog}"/>`);
  }
  s += `<circle cx="800" cy="450" r="96" fill="${C.graphite}" filter="url(#shadow)"/>` + ring(800, 450, 62, 20);
  return s;
} };

/* Media, 4:3: the same page on a wide and a narrow screen. */
scenes['media-devices'] = { w: 1600, h: 1200, out: [DOCS, CATALOG], draw: () => {
  let s = glow(1180, 760, 300, 'Red', 0.75);
  s += page(120, 200, 1150, 760, { theme: 'dark', seed: 18, toc: true });
  s += card(1110, 420, 360, 700, 'darker', { r: 48 });
  s += `<rect x="1128" y="438" width="324" height="664" rx="34" fill="${C.paper}"/>`;
  s += ring(1166, 490, 12, 5, { glowing: false }) + bar(1188, 485, 70, 10, C.mist) + bar(1404, 482, 30, 16, C.fog);
  s += picture(1150, 530, 280, 170, { tone: 'light', seed: 19 });
  s += bar(1150, 724, 200, 18, C.iron) + lines(1150, 760, 280, [0.95, 0.8, 0.6], { h: 9, gap: 12, color: C.fog });
  s += `<rect x="1150" y="842" width="130" height="40" rx="20" fill="url(#red)"/>`;
  s += `<rect x="1150" y="904" width="280" height="80" rx="14" fill="${C.cloud}"/><rect x="1150" y="998" width="280" height="80" rx="14" fill="${C.cloud}"/>`;
  return s;
} };

/* Showcase: the product in both themes, the light window in front. */
scenes['showcase'] = { w: 1600, h: 900, out: [DOCS, CATALOG], draw: () => {
  let s = glow(1260, 200, 300, 'Red', 0.8);
  s += page(520, 60, 1000, 640, { theme: 'dark', seed: 22, toc: true });
  s += page(80, 220, 940, 620, { theme: 'light', seed: 23 });
  s += card(880, 600, 300, 120, 'dark') + check(940, 660, 30) + lines(990, 638, 160, [0.85, 0.55], { h: 10, gap: 16, color: C.iron, accent: C.fog, accentAt: 0 });
  return s;
} };

/* Promo: a banner; pages leave the ring and grow, the last one checked. */
scenes['promo'] = { w: 1600, h: 900, out: [DOCS, CATALOG], draw: () => {
  let s = '';
  for (let i = 1; i <= 3; i++) s += `<circle cx="330" cy="450" r="${150 + i * 48}" fill="none" stroke="${C.red}" stroke-opacity="${n(0.3 - i * 0.08)}" stroke-width="2"/>`;
  s += ring(330, 450, 150, 40);
  const sizes = [[600, 300, 230, 300], [890, 250, 270, 400], [1220, 170, 320, 560]];
  sizes.forEach(([x, y, w, h], i) => {
    s += connector([[i === 0 ? 510 : sizes[i - 1][0] + sizes[i - 1][2] + 8, 450], [x - 12, 450]], { color: C.red, opacity: 0.9 });
    s += card(x, y, w, h, i === 2 ? 'light' : 'dark');
    s += picture(x + w * 0.1, y + w * 0.1, w * 0.8, h * 0.4, { tone: i === 2 ? 'light' : 'dark', seed: 50 + i });
    s += lines(x + w * 0.1, y + h * 0.58, w * 0.8, [0.6, 0.95, 0.8, 0.7], { h: Math.max(9, h * 0.026), gap: h * 0.045, color: i === 2 ? C.fog : C.steel, accent: i === 2 ? C.mist : C.iron, accentAt: 0 });
  });
  s += check(1520, 190, 42);
  return s;
} };

/* Feature icons: content, layout and build, 256 px. */
scenes['feature-content'] = { w: 256, h: 256, out: [DOCS, CATALOG], draw: () => {
  let s = glow(128, 140, 130, 'Red', 0.5);
  s += `<g transform="rotate(-8 128 128)">${card(52, 40, 130, 170, 'soft', { r: 14 })}</g>`;
  s += card(74, 34, 130, 176, 'light', { r: 14 }) + bar(94, 62, 50, 12, C.red) + lines(94, 92, 90, [1, 0.85, 0.95, 0.6], { h: 9, gap: 14, color: C.mist });
  return s;
} };
scenes['feature-layout'] = { w: 256, h: 256, out: [DOCS, CATALOG], draw: () => {
  let s = glow(128, 140, 130, 'Red', 0.5);
  s += card(36, 44, 184, 168, 'dark', { r: 18 });
  s += `<rect x="54" y="62" width="56" height="132" rx="10" fill="${C.steel}"/><rect x="120" y="62" width="82" height="58" rx="10" fill="url(#red)"/><rect x="120" y="130" width="38" height="64" rx="10" fill="${C.slate}"/><rect x="164" y="130" width="38" height="64" rx="10" fill="${C.iron}"/>`;
  return s;
} };
scenes['feature-build'] = { w: 256, h: 256, out: [DOCS, CATALOG], draw: () => {
  let s = '';
  s += card(40, 120, 176, 96, 'dark', { r: 18 }) + `<rect x="60" y="140" width="136" height="12" rx="6" fill="${C.steel}"/><rect x="60" y="166" width="96" height="12" rx="6" fill="${C.iron}"/><rect x="60" y="190" width="60" height="8" rx="4" fill="${C.red}"/>`;
  s += ring(128, 82, 44, 15);
  return s;
} };

/* Grid cards of the catalogue: wide scenes for the same three ideas. */
scenes['grid-content'] = { w: 1600, h: 900, out: [CATALOG], draw: () => {
  let s = glow(1080, 300, 260, 'Red', 0.7);
  s += sheet(300, 140, 560, 640, 'dark', lines(350, 200, 460, [0.5, 0.95, 0.9, 0.7, 0.85, 0.95, 0.6, 0.8, 0.9, 0.5], { h: 13, gap: 30, color: C.steel, accent: C.red, accentAt: 0 }));
  s += sheet(760, 220, 560, 600, 'light', bar(810, 270, 260, 24, C.iron) + lines(810, 320, 460, [0.95, 0.85, 0.7], { h: 11, gap: 16, color: C.fog }) + picture(810, 420, 460, 230, { tone: 'light', seed: 61 }) + lines(810, 690, 460, [0.9, 0.75], { h: 11, gap: 16, color: C.fog }));
  s += ring(1240, 230, 54, 16);
  return s;
} };
scenes['grid-layout'] = { w: 1600, h: 900, out: [CATALOG], draw: () => {
  let s = glow(800, 450, 240, 'Red', 0.6);
  s += card(260, 110, 1080, 680, 'darker', { r: 26 });
  s += `<rect x="300" y="150" width="1000" height="70" rx="14" fill="${C.slate}"/>` + ring(345, 185, 16, 6, { glowing: false }) + bar(372, 180, 110, 10, C.iron);
  s += `<rect x="300" y="244" width="220" height="506" rx="14" fill="${C.graphite}"/>` + lines(326, 276, 168, [0.9, 0.7, 0.8, 0.6, 0.75], { h: 10, gap: 26, color: C.steel, accent: C.red, accentAt: 1 });
  s += `<rect x="544" y="244" width="756" height="230" rx="14" fill="url(#red)" opacity=".92"/>` + `<rect x="544" y="244" width="756" height="230" rx="14" fill="url(#sheen)"/>`;
  s += `<rect x="544" y="498" width="368" height="252" rx="14" fill="${C.slate}"/><rect x="932" y="498" width="368" height="252" rx="14" fill="${C.steel}"/>`;
  s += [544, 932].map((x) => lines(x + 28, 530, 300, [0.6, 0.9, 0.75], { h: 11, gap: 18, color: C.iron })).join('');
  return s;
} };
scenes['grid-build'] = { w: 1600, h: 900, out: [CATALOG], draw: () => {
  let s = '';
  s += card(160, 260, 300, 380, 'dark') + code(196, 300, 230, [0, 1, 2, 2, 1, 0, 1, 2, 1, 0, 0], { seed: 71 });
  s += connector([[470, 450], [560, 450]], { color: C.mist, end: false });
  s += ring(640, 450, 80, 24);
  s += connector([[730, 450], [830, 450]], { color: C.red, opacity: 1 });
  for (let i = 2; i >= 0; i--) s += card(880 + i * 40, 190 + i * 40, 420, 440, i ? 'soft' : 'light');
  s += picture(910, 220, 360, 170, { tone: 'light', seed: 72 }) + lines(910, 420, 360, [0.7, 0.95, 0.85, 0.6], { h: 11, gap: 18, color: C.fog, accent: C.mist, accentAt: 0 });
  s += check(1380, 230, 46);
  return s;
} };

/* Markdown image example: one simple scene. */
scenes['markdown-scene'] = { w: 1600, h: 900, out: [DOCS], draw: () => {
  let s = '';
  s += sheet(420, 150, 760, 600, 'light', picture(470, 200, 660, 330, { tone: 'light', seed: 81 }) + bar(470, 570, 300, 20, C.iron) + lines(470, 614, 660, [0.95, 0.8], { h: 11, gap: 16, color: C.fog }), { stack: 2 });
  return s;
} };

/* ------------------------------------------------------------------ */
/* Output                                                              */
/* ------------------------------------------------------------------ */

function sources() {
  const out = {};
  for (const [name, scene] of Object.entries(scenes)) {
    canvas = { w: scene.w, h: scene.h };
    hazeId = 0;
    out[name] = svg(scene.w, scene.h, scene.draw(scene.w, scene.h), scene.view);
  }
  return out;
}

async function chrome() {
  const bin = process.env.CHROME || ['/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', '/usr/bin/google-chrome', '/usr/bin/chromium'].find((p) => existsSync(p));
  if (!bin) throw new Error('Google Chrome was not found; set CHROME.');
  const profile = mkdtempSync(join(tmpdir(), 'docara-art-'));
  const port = 9400 + Math.floor(Math.random() * 400);
  const proc = spawn(bin, ['--headless=new', `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`, '--no-first-run', '--hide-scrollbars', '--force-color-profile=srgb', 'about:blank'], { stdio: 'ignore' });
  let info;
  for (let i = 0; i < 150 && !info; i++) {
    try { info = await (await fetch(`http://127.0.0.1:${port}/json/version`)).json(); } catch { await new Promise((r) => setTimeout(r, 100)); }
  }
  const ws = new WebSocket(info.webSocketDebuggerUrl);
  await new Promise((r) => ws.addEventListener('open', r, { once: true }));
  let id = 0;
  const pending = new Map();
  const events = [];
  ws.addEventListener('message', (e) => {
    const m = JSON.parse(e.data);
    if (m.id && pending.has(m.id)) { const p = pending.get(m.id); pending.delete(m.id); m.error ? p.rej(new Error(JSON.stringify(m.error))) : p.res(m.result); } else events.push(m);
  });
  const send = (method, params = {}, sessionId) => new Promise((res, rej) => { const i = ++id; pending.set(i, { res, rej }); ws.send(JSON.stringify({ id: i, method, params, sessionId })); });
  const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
  const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
  const s = (m, p) => send(m, p, sessionId);
  await s('Page.enable');
  await s('Emulation.setDefaultBackgroundColorOverride', { color: { r: 0, g: 0, b: 0, a: 0 } });
  return {
    async render(markup, w, h) {
      await s('Emulation.setDeviceMetricsOverride', { width: w, height: h, deviceScaleFactor: 1, mobile: false });
      const html = `<!doctype html><html><head><style>html,body{margin:0;background:transparent}svg{display:block}</style></head><body>${markup}</body></html>`;
      const { frameId } = await s('Page.navigate', { url: 'about:blank' });
      await s('Page.setDocumentContent', { frameId, html });
      await new Promise((r) => setTimeout(r, 250));
      const shot = await s('Page.captureScreenshot', { format: 'png', clip: { x: 0, y: 0, width: w, height: h, scale: 1 } });
      return Buffer.from(shot.data, 'base64');
    },
    async close() {
      const exited = new Promise((r) => proc.once('exit', r));
      try { await send('Browser.close'); } catch { /* closed */ }
      ws.close();
      proc.kill();
      await exited;
      rmSync(profile, { recursive: true, force: true, maxRetries: 5, retryDelay: 200 });
    },
  };
}

const check_ = process.argv.includes('--check');
const generated = sources();

if (check_) {
  const stale = Object.entries(generated).filter(([name, text]) => {
    const file = join(SOURCE_DIR, `${name}.svg`);
    return !existsSync(file) || readFileSync(file, 'utf8') !== text;
  }).map(([name]) => name);
  if (stale.length) {
    console.error(`Example art sources are out of date: ${stale.join(', ')}. Run node scripts/build-example-art.mjs.`);
    process.exit(1);
  }
  console.log(`Example art sources are current (${Object.keys(generated).length} images).`);
  process.exit(0);
}

const only = process.argv.slice(2).filter((a) => !a.startsWith('--'));
mkdirSync(SOURCE_DIR, { recursive: true });
const cwebp = process.env.CWEBP || 'cwebp';
const browser = await chrome();
const work = mkdtempSync(join(tmpdir(), 'docara-art-png-'));
try {
  for (const [name, text] of Object.entries(generated)) {
    writeFileSync(join(SOURCE_DIR, `${name}.svg`), text);
    if (only.length && !only.includes(name)) continue;
    const scene = scenes[name];
    const png = join(work, `${name}.png`);
    writeFileSync(png, await browser.render(text, scene.w, scene.h));
    const webp = join(work, `${name}.webp`);
    const args = scene.opaque ? ['-q', '80', '-m', '6', '-sharp_yuv'] : ['-q', '88', '-alpha_q', '85', '-m', '6', '-sharp_yuv'];
    execFileSync(cwebp, [...args, '-metadata', 'none', '-quiet', png, '-o', webp]);
    for (const dir of scene.out) copyFileSync(webp, join(ROOT, dir, `${name}.webp`));
    console.log(`${name}.webp  ${scene.w}x${scene.h}  ${(readFileSync(webp).length / 1024).toFixed(1)} KB  -> ${scene.out.join(', ')}`);
  }
} finally {
  await browser.close();
  rmSync(work, { recursive: true, force: true });
}
