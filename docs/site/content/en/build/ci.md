---
description: How to build and check a Docara site on every change: commands for any CI and a ready GitHub Actions workflow with GitHub Pages.
tags: [ci, github-actions, github-pages, automatic-build, check]
---

# Building in CI

An automated build catches an error before it reaches the site: every change is
built from scratch, checked and only then published. Docara stops with a
non-zero exit code on any error, so CI marks the failed check by itself.

## What the build server needs

- PHP 8.2 or newer with the `ctype`, `dom`, `intl`, `json`, `libxml`
  and `mbstring` extensions;
- Composer;
- Node.js 20 or newer.

The finished site itself is static files: the hosting needs neither PHP nor
Node.js.

## Commands for any CI

These steps suit any system: GitHub Actions, GitLab CI, Jenkins or a script
on your own server. Run them in the project root — where
`docara.json` and `composer.json` live.

```bash
composer install --no-interaction --prefer-dist
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara qa report --env=production --min-score=80
php vendor/simai/docara/scripts/verify-page-weight.php build_production
```

| Step | What it checks |
| --- | --- |
| `composer install` | Installs the same Docara version that is written in `composer.lock` |
| `build production` | Builds the site; stops on an error in the pages and the settings |
| `verify-static` | Checks the finished folder: links, files, search, service data |
| `qa report --min-score=80` | Stops the check if the documentation quality score is below 80 out of 100 |
| `verify-page-weight.php` | Stops the check if a page or an image is heavier than the threshold |

The last two steps are optional. The quality threshold is easier to keep in
`docara.authoring.json` in the `quality.min_score` field — described on the
[Documentation quality](/en/authoring/documentation-quality/) page. The weight
thresholds are set by the `--config=page-weight.json` file — see the section
[Page weight](/en/build/verify/#page-weight).

Publish only the `build_production` folder and only after all the steps have
passed.

## GitHub Actions and GitHub Pages

This workflow builds the site on every change to the `main` branch and
publishes it to GitHub Pages. The Docara documentation site itself is built the
same way.

Before the first run, turn publishing on: **Settings → Pages → Source:
GitHub Actions**. Then save the file `.github/workflows/docs.yml`:

```yaml {title=".github/workflows/docs.yml"}
name: Documentation

on:
  push:
    branches: [main]
  pull_request:
  workflow_dispatch:

permissions:
  contents: read

concurrency:
  group: pages
  cancel-in-progress: false

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@11d5960a326750d5838078e36cf38b85af677262
      - id: pages
        uses: actions/configure-pages@45bfe0192ca1faeb007ade9deae92b16b8254a0d # v6.0.0
      - uses: shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240
        with:
          php-version: "8.3"
          coverage: none
          tools: composer
      - run: composer install --no-interaction --prefer-dist
      - uses: actions/setup-node@49933ea5288caeca8642d1e84afbd3f7d6820020 # v4.4.0
        with:
          node-version: "24"
      - name: Set the site address
        env:
          BASE_PATH: ${{ steps.pages.outputs.base_path }}
          SITE_URL: ${{ steps.pages.outputs.base_url }}
        run: |
          jq --arg base "${BASE_PATH%/}/" --arg site "${SITE_URL%/}/" \
            '.base_url = $base | .site_url = $site' docara.json > docara.json.ci
          mv docara.json.ci docara.json
      - run: php vendor/bin/docara build production
      - run: php vendor/bin/docara verify-static build_production
      - uses: actions/upload-pages-artifact@fc324d3547104276b827a68afc52ff2a11cc49c9 # v5.0.0
        with:
          path: build_production

  deploy:
    if: github.event_name != 'pull_request'
    needs: build
    runs-on: ubuntu-latest
    permissions:
      pages: write
      id-token: write
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    steps:
      - id: deployment
        uses: actions/deploy-pages@368f82528645a54fb793d4d04e342629a3f51346 # v5.0.1
```

What matters here:

- **The site address.** A project site on GitHub Pages opens at
  `https://<owner>.github.io/<repository>/`, that is, in a subfolder. The
  "Set the site address" step takes the address from GitHub and writes it into
  `base_url` and `site_url`; `docara.json` in the repository keeps
  `"base_url": "/"` for local work. With your own domain GitHub returns an
  empty path itself, and the site is built from the root. More on the
  [Site address](/en/settings/site-address/) page.
- **Pull request checks.** On `pull_request` the site is built and
  checked but not published: the `if` condition on `deploy` takes care of that.
- **Pinned action versions.** The actions are given by an exact commit, not
  by a moving tag: that way the workflow does not change by itself when an
  action gets a new version. Update them deliberately.

## If a check stops

Docara prints the error code in square brackets. Find it in the
[error code reference](/en/reference/errors/): it gives the cause
and what to fix. To reproduce the error on your machine, run the same commands
locally, starting with `composer install`.

Common causes in CI:

| Symptom | Cause and what to do |
| --- | --- |
| The build stops but passes locally | A different Docara version is installed locally. **What to do:** Keep `composer.lock` in the repository and run `composer install`, not `composer update`. |
| The checks passed, but the published site has no styles and the links lead elsewhere | `base_url` does not match the address the site opens at: `verify-static` checks the build against its own `base_url`. **What to do:** Check the site address step. |
| `QUALITY_SCORE_BELOW_MINIMUM` | The quality score is below the threshold. **What to do:** Run `qa report` locally and fix the pages from the "fix first" list. |

## See also

- [Checking the build](/en/build/verify/)
- [Hosting](/en/build/hosting/)
- [Publishing and rollback](/en/build/publish/)
- [Site address](/en/settings/site-address/)
