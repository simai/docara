# Docara

Docara builds static documentation, reference sites and small landing pages
from Markdown and validated JSON. Simai Framework supplies the interface;
authors do not need Node.js or a frontend toolchain.

## Quick start

Until Docara 2 is published, run these commands from an exact source checkout:

```bash
git rev-parse HEAD
composer install
php docara init /path/to/my-docara
cd /path/to/my-docara
php /path/to/docara/docara build production
php /path/to/docara/docara verify-static build_production
php /path/to/docara/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Open `http://127.0.0.1:8000`. Do not use `file://`: routes, search and assets
must be checked through HTTP.

The starter contains one product model:

```text
docara.json                 site, locales, preset and Framework lock reference
redirects.json              explicit redirects
simai-framework.lock.json   immutable Framework revisions
assets/                     project-owned public assets
content/<locale>/           Markdown and inherited JSON settings
```

Settings resolve deterministically:

```text
built-in defaults
→ docara.json
→ section.json from the locale root to the page
→ <page>.page.json
→ Markdown content
```

One build publishes every locale declared in `docara.json`. A documentation
version is a separate site variant and output with its own `base_url`.

## Commands

```bash
php vendor/bin/docara init [--update] [path]
php vendor/bin/docara build [environment]
php vendor/bin/docara serve [environment] [--no-build]
php vendor/bin/docara verify-static [build-directory]
```

`init --update` updates engine-owned starter files and preserves documented
project-owned content and settings. Generated `build_*` and `.docara` files
must not be edited manually.

## Documentation

- [Quick start](docs/site/content/ru/start.md)
- [Project files and configuration](docs/site/content/ru/authoring/project-files.md)
- [Layouts, regions and navigation](docs/site/content/ru/authoring/layout-and-navigation.md)
- [Components](docs/site/content/ru/components.md)
- [Build and verification](docs/site/content/ru/build.md)
- [Portable format contract](docs/portable-format.md)

Every build contains a generated component catalogue at
`/components/catalog/`. It is derived from the exact Framework lock and is the
source of truth for components available in that build.

## Repository checks

```bash
php vendor/bin/pint --test
php vendor/bin/phpunit
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
```

This branch is a Docara 2 candidate. It does not itself claim a public release
or production readiness.

## License

MIT
