---
extends: _core._layouts.documentation
section: content
title: Compiling Assets
description: Compiling Assets
---

# Compiling Assets

Docara ships with Vite by default. Vite compiles the core JavaScript and SCSS entries, writes a manifest to `source/assets/build/manifest.json`, and then runs the Docara static build.

## Setup

Make sure you have Node.js and npm installed, then install deps:

```bash
npm install
```

Key scripts from `package.json`:

- `npm run dev` - start Vite dev server and rebuild Docara on content changes
- `npm run watch` - run Vite build in watch mode
- `npm run prod` - production asset build followed by `docara build production`

## Vite Config

Vite is configured in `source/_core/vite.config.js`. Defaults:

- Output path: `source/assets/build`
- Entries: `source/_core/_assets/js/main.js`, `source/_core/_assets/js/turbo.js`, `source/_core/_assets/css/main.scss`
- Manifest: `source/assets/build/manifest.json`
- Static copy: core/project images into `source/assets/build/img`
- Docara build: `local` for dev/watch, `production` for prod

Typical snippet:

```js
export default defineConfig({
    build: {
        manifest: "manifest.json",
        outDir: "source/assets/build",
        rollupOptions: {
            input: {
                main: "source/_core/_assets/js/main.js",
                turbo: "source/_core/_assets/js/turbo.js",
                styles: "source/_core/_assets/css/main.scss",
            },
        },
    },
    plugins: [docara()],
});
```

## Referencing Assets

In layouts, use the versioned paths Vite writes to `assets/build`:

```blade
{!! vite_refresh() !!}
<link rel="stylesheet" href="{{ vite('source/_core/_assets/css/main.scss') }}">
<script type="module" src="{{ vite('source/_core/_assets/js/main.js') }}"></script>
```

Images copied from `source/_core/_assets/img` and `source/img` are available under `/assets/build/img`. Fonts imported from SCSS are emitted under `/assets/build/css/files`.

## Changing Asset Locations

We recommend keeping entries under `/source/_core/_assets`. If you move them, update `vite.config.js` entry points and your layout paths accordingly. If you place assets under `source/`, prefix the directory with an underscore, such as `_assets`, so Docara does not copy them directly.
