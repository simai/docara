---
extends: _core._layouts.documentation
section: content
title: Local Development
description: Local Development
---

# Local Development

## Set Your Local URL

To preview your site locally, you'll first need to create a .env file and specify your local URL in the APP_URL variable:

```bash
APP_URL=http://your_url_here
```

## Using Docara

Docara includes a serve command that makes your site available at http://localhost:8000. You can use this URL directly in your .env file:

```bash
APP_URL=http://localhost:8000
```

Once configured, start the local server by running `vendor/bin/jigsaw serve`.

## Using Valet

Alternatively, you can use Laravel Valet to run your site locally with a .test domain. From your project root, execute this command:

```bash
valet link my-site
```

This will host your site at http://my-site.test, which you can then use in your .env file:

```bash
APP_URL=http://my-site.test
```

## Preview Your Site

To preview your site after setting up your URL, run the dev command from your project root (default scaffold uses Laravel Mix + BrowserSync):

```bash
npm run dev
```

This builds to `/build_local`, serves it at `http://localhost:3000`, and reloads the page when the build output changes. Static HTML lands in `/build_local`; edits to Markdown/Blade trigger a rebuild, and BrowserSync reloads after the build finishes. Assets are compiled from:

`source/_core/_assets/js/main.js`

`source/_core/_assets/css/main.scss`

If you prefer Vite with HMR, follow the Vite setup in **Compiling Assets**; the scaffold ships with Mix by default, so `npm run dev` runs webpack (Mix) until you switch.

If you do not compile assets at all, you can preview via Jigsaw's `serve` command or Valet; in that case you'll need to run `vendor/bin/jigsaw build local` yourself after edits because there's no watcher.

## BrowserSync (webpack.mix.js)

To avoid early reloads, watch only the built output (not source files) in `webpack.mix.js`:

```js
mix.docara()
    .js("source/_core/_assets/js/main.js", "js")
    .js("source/_core/_assets/js/turbo.js", "js")
    .options({ processCssUrls: false })
    .browserSync({
        server: "build_local",
        files: ["build_local/**/*"], // watch only built files
        open: false,
    })
    .version();
```

Key idea: keep `files` scoped to `build_local/**/*` so BrowserSync reloads only after the build is written. Add `reloadDelay` or switch to `proxy`/`port` if needed, but avoid adding `source/**` back into `files`, or you'll bring back early reloads.
