---
extends: _core._layouts.documentation
section: content
title: Deploying Your Site
description: Deploying Your Site
---

# Deploying Your Site

Since Docara sites are just static HTML and Javascript, they are simple and cheap to deploy and host.

## Using GitHub Pages

GitHub Pages is a free service for hosting static sites offered by GitHub. To get started, first read through the
documentation to get an understanding of how the service works.

The approach we use at Tighten for deploying Docara sites to GitHub pages looks like this:

1. Build your site for production
    ```bash 
    npm run build
    ```
2. Commit the `build_production` folder to your repository
    ```bash 
    git add build_production && git commit -m "Build for deploy"
    ```
3. Use `git subtree push` to push just the `build_production` folder to your `gh-pages` branch
   ```bash 
   git subtree push --prefix build_production origin gh-pages
   ```

## Using Netlify
Netlify is a service that provides free static site hosting and continuous deployment that can be configured to play
nice with any static site generator.

To deploy a site to Netlify, first create a `netlify.toml` file with the following configuration:
```toml 
[build]
command = "npm run build"
publish = "build_production"
```
Push this file to your repository.

Then connect your repository to the Netlify app to setup continuous deployment.

You can skip the build and deploy settings because Netlify will read the `netlify.toml` file we created earlier.

## Using Amazon S3
Amazon S3 is a very affordable service for hosting static assets. You might have used it in the past for hosting things
like images, user uploads, or PDFs in your web applications.

What you might not have known is that Amazon S3 also has first class support for hosting static sites, like your Docara
projects.

To deploy a site to S3, first build your site for production:
```bash 
npm run build
```
Then simply follow the steps in Amazon’s static site documentation to deploy your `build_production` folder to your S3
bucket.

## Using Kinsta Application Hosting
Kinsta Application Hosting is an affordable, developer-centric cloud host that lets you deploy and host your website.

Before deploying your site, you must make some minor changes—install `serve` by running `npm install serve`, and then update
your `package.json` file:
```json
"scripts": {
"build": "composer install && npm run build",
"start": "serve ./build_production"
},
```
This will build your site and start the server automatically.

Finally, add both the PHP and NodeJS buildpacks in the Processes section of your Kinsta dashboard.

## Manually
If you have an existing server that you’d like to use to host your Docara site, all you need to do is get the contents
`build_production` into a public folder that’s pointed at by the URL you’d like to use.

## Changing the Source and Destination Directories
Docara will look for your source files in a `source` directory, and will output your files to a directory named `build_`
followed by the environment you specified in the `build` command (`build_local` by default, or `build_staging`,
`build_production`, etc.). You can, however, customize these locations by adding a `build` key to the array in `config.php`,
and specifying your own source and/or destination paths.

> config.php
```php
<?php

return [
    'build' => [
        'source' => 'src',
        'destination' => 'my_destination',
    ],
    // ...
];
 ```
Source and destination paths are relative to your project root, i.e where your `config.php` file is located.

> config.php
```php
<?php

return [
    'build' => [
        'destination' => '../build-one-level-up',
    ],
    // ...
];
```
To include the environment name in your destination path, use the `{env}` token in your path name. `{env}` will be replaced by the environment specified when running the `build` command, and defaults to `local`.

> config.php
```php
<?php

return [
    'build' => [
        'destination' => 'dist/{env}/public',
    ],
    // ...
];
```

In this example, running `NODE_ENV=staging npm run build` would output your built files to `dist/staging/public`. Docara will create any directories that do not already exist.

You can also assign different source and build paths for different environments by using multiple environment-specific config files.

Source and destination paths in `config.production.php`, for example, will get merged with any build paths that have been defined in `config.php` when running `npm run build`.
