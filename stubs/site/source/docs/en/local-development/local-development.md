---
extends: _core._layouts.documentation
section: content
title: Local Development
description: Local Development
---

# Local Development

## Set Your Local URL

To preview your site locally, create a `.env` file and specify your local URL in `APP_URL`:

```bash
APP_URL=http://localhost:3000
```

## Using Vite

Docara's default scaffold uses Vite for local development:

```bash
npm run dev
```

This starts Vite at `http://localhost:3000`, writes `source/hot`, and rebuilds Docara into `/build_local` when Markdown, Blade, or config files change. Assets are compiled from:

`source/_core/_assets/js/main.js`

`source/_core/_assets/js/turbo.js`

`source/_core/_assets/css/main.scss`

The Vite plugin serves generated files from `build_local` after internal Vite assets are handled.

## Watch Build

For a production-like asset build in watch mode:

```bash
npm run watch
```

## Direct Docara Serve

Docara also includes a serve command that makes your built site available at `http://localhost:8000`:

```bash
vendor/bin/docara serve
```

If you do not compile assets at all, you can preview through Docara's serve command or Valet. In that case you need to run `vendor/bin/docara build local` yourself after edits because there is no asset watcher.

## Using Valet

Alternatively, you can use Laravel Valet to run your site locally with a `.test` domain. From your project root, execute:

```bash
valet link my-site
```

This hosts your site at `http://my-site.test`, which you can then use in `.env`:

```bash
APP_URL=http://my-site.test
```
