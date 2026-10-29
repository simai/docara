# Building and publishing

An ordinary author needs no frontend toolchain. A PHP command reads the
Markdown and the JSON, checks the Framework lock and creates a finished static
folder.

## The safe path

:::steps
1. Build the `production` output.
2. Check it with `verify-static`.
3. Open that same output over HTTP through `serve --no-build`.
4. Before publishing, move the checked folder to staging.
5. Compare the digest, run the smoke checks, and only then switch the traffic.
6. On an error, bring back the previous folder you kept.

:::

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

The preview holds the terminal until `Ctrl+C`. Do not use `file://`.

## Guides

- [Building the site](/en/build/php-only/) — the commands, the finished folder and reproducibility
- [Local preview](/en/build/local-preview/)
- [Checking the build](/en/build/verify/)
- [Building in CI](/en/build/ci/) — commands for any CI and a ready GitHub Actions process with GitHub Pages
- [Hosting the site](/en/build/hosting/)
- [Publishing and rolling back](/en/build/publish/)
- [Building for a portal](/en/build/portal/)
- [Updating Docara](/en/build/update/) — including the move from 2.x to 3.0

Vite is for a maintainer who develops the theme's source assets. It is not
part of the portable author and build path.

A successful local check is no automatic permission for a public release or a
production deployment.
