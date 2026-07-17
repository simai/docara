---
extends: _core._layouts.documentation
section: content
title: Generating Your Site
description: Generating Your Site
---

# Generating Your Site

## Build For Production

To generate your static site for deployment, run:

```bash
npm run prod
```

You will see output similar to this:

```text
vite v7.x building client environment for production...
built in 600ms

Building production site
Loading collections...
Building files from source...
Writing files to destination...
Site built successfully!
```

Your complete static site will be generated in the `/build_production` directory, ready for deployment.

Vite compiles, minifies, and versions your assets. The bundled asset paths are referenced in generated HTML through `vite()`:

```html
<link rel="stylesheet" href="/assets/build/css/styles.hash.css">
<script type="module" src="/assets/build/js/main.hash.js"></script>
```

Assets that do not require compilation, such as core and project images, are copied to `/assets/build/img`.

## Environments

Often you might want to use different site variables in your development and production environments. For example, in production you might want to render your analytics tracking snippet, but not include it in development.

Docara makes this simple by allowing you to create additional config files for different environments.

Say your base `config.php` file looks like this:

```php
<?php

return [
    'debug' => true,
    'company' => 'Simai',
];
```

You can override the `debug` variable in production by creating `config.production.php`:

```php
<?php

return [
    'debug' => false,
];
```

This file is merged on top of `config.php`, so you only need to specify variables that change.

## Build For A Specific Environment

To build files for a specific environment, set the Vite mode when running the build command:

```bash
npx vite build --mode staging
```

The Docara Vite plugin maps `production` mode to `docara build production`; other modes use `docara build local` by default. If you need a custom environment build without compiling assets, run Docara directly:

```bash
vendor/bin/docara build staging
```
