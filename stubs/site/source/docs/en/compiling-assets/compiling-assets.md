---
extends: _core._layouts.documentation
section: content
title: Compiling Assets
description: Compiling Assets
---

# Compiling Assets

Docara ships with Laravel Mix (webpack) by default. If you've used Mix in a Laravel project, the workflow is the same here. Vite is supported, but optional—see **Using Vite** below if you want HMR.

## Setup (Mix)

Make sure you have Node.js and npm installed, then install deps:

```bash
npm install
```

Key scripts (from package.json):

-   `npm run dev` — build + watch with BrowserSync
-   `npm run watch` — watch only
-   `npm run build` — production build (mix --production)

## Mix config

Mix is configured in `source/_core/webpack.mix.js` with Docara's mix plugin. Defaults:

-   Public path: `source/assets/build`
-   Entries: `source/_core/_assets/js/main.js`, `source/_core/_assets/js/turbo.js`, `source/_core/_assets/css/main.scss`
-   Copies: core fonts/img into `source/assets/build`
-   BrowserSync: serves `build_local`, watches `build_local/**/*`, `open: false`

Typical snippet:

```js
mix.docara()
    .js("source/_core/_assets/js/main.js", "js")
    .js("source/_core/_assets/js/turbo.js", "js")
    .sass("source/_core/_assets/css/main.scss", "css")
    .options({ processCssUrls: false })
    .browserSync({
        server: "build_local",
        files: ["build_local/**/*"],
        open: false,
    })
    .version();
```

## Referencing assets

In layouts, use the versioned paths Mix writes to `assets/build`:

```html
<link rel="stylesheet" href="/assets/build/css/main.css?id=xyz" />
<script defer src="/assets/build/js/main.js?id=abc"></script>
```

Images/fonts are copied as-is, so paths like `/assets/img/1.png` continue to work.

## Changing asset locations

We recommend keeping entries under `/source/_core/_assets`. If you move them (e.g., to `source/_assets`), update `webpack.mix.js` entry points and your layout paths accordingly. If you place assets under `source/`, prefix the directory with an underscore (e.g., `_assets`) so Docara doesn't copy them directly.
