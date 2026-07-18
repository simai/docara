---
extends: _core._layouts.documentation
section: content
title: Compiling Assets
description: Compiling Assets
---

# Compiling Assets

Docara ships with Vite by default. Vite compiles the core JavaScript and SCSS entries, writes a manifest to `source/assets/build/manifest.json`, and then runs the Docara static build.

## Setup

Use Node.js `^20.19.0 || >=22.12.0` and Yarn Classic exactly `1.22.22`, then
install the exact dependency set from the committed lockfile:

```bash
YARN_IGNORE_PATH=1 npx --yes yarn@1.22.22 --no-default-rc install --frozen-lockfile --production=false --non-interactive
```

`yarn --version` must print exactly `1.22.22` before `docara init`. Docara owns
the legacy theme's package manager, exact `engines.node`, dependency graph,
standard scripts and lockfile. A missing engine is added; a different or
extended `engines` contract is rejected. It refuses npm locks, extra dependencies, lifecycle/install metadata,
project `.yarnrc*`, `.yarn/`, `.yarnclean` and `.npmrc` configuration, and
unsafe filesystem links before changing the project. Project identity,
descriptive fields, `config`, and additional explicit scripts are preserved.
`DOCARA_SKIP_FRONTEND_INSTALL=true` skips only the Yarn process; it does not
skip package/lock merge or scaffold refresh and does not prove frontend
readiness. A controlled CI caller must immediately run
`YARN_IGNORE_PATH=1 npx --yes yarn@1.22.22 --no-default-rc install --frozen-lockfile --production=false --non-interactive`
before a build or deploy.

Key scripts from `package.json`:

- `YARN_IGNORE_PATH=1 npx --yes yarn@1.22.22 --no-default-rc dev` - start Vite dev server and rebuild Docara on content changes
- `YARN_IGNORE_PATH=1 npx --yes yarn@1.22.22 --no-default-rc watch` - run Vite build in watch mode
- `YARN_IGNORE_PATH=1 npx --yes yarn@1.22.22 --no-default-rc prod` - production asset build followed by `docara build production`

Vite invokes the local `vendor/bin/docara` through `php`. If the first PHP in
your shell is not the one used for Composer, set the exact executable before
running Vite:

```bash
DOCARA_PHP_BINARY=/absolute/path/to/php yarn prod
```

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
