# Docara

Docara builds static documentation, reference sites and small landing pages
from Markdown and validated JSON. SIMAI Framework supplies the interface;
authors do not need Node.js or a frontend toolchain.

## Quick start

Create one project-local Composer runtime, then initialize Docara in the same
directory:

```bash
mkdir /path/to/my-docara
cd /path/to/my-docara
composer require simai/docara:^2.0
php vendor/bin/docara init .
php vendor/bin/docara doctor --json
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Open `http://127.0.0.1:8000`. Do not use `file://`: routes, search and assets
must be checked through HTTP.

The starter contains one product model:

```text
docara.json                 site, locales, preset and Framework lock reference
composer.json               compatible Docara dependency constraint
composer.lock               exact project-local Docara and dependency graph
vendor/                     project-local executable runtime
redirects.json              explicit redirects
simai-framework.lock.json   immutable Framework revisions
assets/                     project-owned public assets
examples/                   reusable HTML/CSS/JS demonstrations for every locale
content/<locale>/           Markdown and inherited JSON settings
translations.lock.json      accepted translation hashes and review levels (optional)
.docara/engine/             package-owned engine snapshot and ownership manifest
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
php vendor/bin/docara init [path]
php vendor/bin/docara capabilities --json
php vendor/bin/docara upgrade [--check] [--to=2.x.y] [--dry-run] [--json]
php vendor/bin/docara upgrade --apply=<plan-sha256> [--json]
php vendor/bin/docara upgrade --rollback=<id|latest> [--json]
php vendor/bin/docara update [path] --verify
php vendor/bin/docara update [path] --dry-run
php vendor/bin/docara update [path] --apply
php vendor/bin/docara update [path] --rollback=latest
php vendor/bin/docara build [environment] [--page=/public/url/]
php vendor/bin/docara translations status [--locale=en] [--status=stale] [--json]
php vendor/bin/docara translations accept --locale=en --key=<key> --review=ai_verified --dry-run --json
php vendor/bin/docara translations accept --apply=<plan-sha256> --json
php vendor/bin/docara serve [environment] [--no-build]
php vendor/bin/docara verify-static [build-directory]
php vendor/bin/docara doctor [--json]
php vendor/bin/docara list smart|layout|view|section|block|provider|fixture|state|schema [--json]
php vendor/bin/docara inspect smart|layout|view|section|block|provider|fixture|state|schema <id> [--json]
php vendor/bin/docara schema smart|layout|view|section|block [--json]
php vendor/bin/docara scaffold smart|design <project.id> --dry-run [--json]
php vendor/bin/docara scaffold --apply=<exact-plan-sha256> [--json]
php vendor/bin/docara validate project|smart|layout|view|section|block [id] [--json]
php vendor/bin/docara test smart|layout <id> --page=/public/route/ [--json]
php vendor/bin/docara qa smart|region|layout <id> --page=/public/route/ --dry-run [--json]
php vendor/bin/docara qa --finalize-reference=<exact-draft-plan-sha256> [--json]
php vendor/bin/docara qa --verify=<exact-finalized-plan-sha256> [--json]
```

`schema smart` возвращает neutral
`sf.smart_artifact_abi` v1 manifest schema, которой напрямую проверяется
результат `scaffold smart`; package compatibility adapters не являются вторым
публичным Smart-форматом.

For layout test/QA, the selected page must actually resolve that layout; a
context mismatch fails closed instead of testing a different production page.
Each QA run uses an explicit immutable chain. `qa ... --dry-run` creates a
content-addressed draft plan. Optional browser tooling records the planned
reference screenshots under that draft. PHP then validates every screenshot
and `qa --finalize-reference` creates a new finalized plan whose
`reference_id` covers the complete ordered reference manifest: target, page and
artifact hashes, scenario IDs, paths and screenshot hashes. Only that finalized
plan may produce a report or pass `qa --verify`. Verification recalculates the
full reference identity and manifest seal, re-hashes the preview and PNG bytes,
and never trusts a reported zero pixel count on its own.

`init` accepts an empty target or a target containing only a verified
project-local Composer runtime for `simai/docara`. `upgrade` is the normal
high-level update: after the user starts it, Docara resolves only a stable
patch/minor release inside the current major and project constraint, verifies
an isolated candidate, then promotes dependencies, engine and a verified build
as one compensating transaction. Any failure restores the previous local
runtime without changing authored files. Major upgrades require a separate
migration. The lower-level `update` command remains available when only
package-owned `.docara/engine` must be synchronized from an already selected
exact package. Generated `build_*` files and package-owned `.docara` state must
not be edited manually.

The optional `path` may be absolute or relative to the current directory. If it
is omitted, `init` and `update` use the current directory. `init --update` is a
disabled compatibility guard and prints the explicit update workflow.

After one complete build, changing one Markdown owner and using `--page`
atomically rebuilds only that existing route through the same PageBuilder.
Adding, renaming or deleting a route requires a complete build so navigation,
search, redirects and receipts change together. Run a complete build after
other structural, global configuration, Framework lock or reusable example
changes as well. Reusable examples live in `examples/<id>/` and are referenced
with `:::example {id="<id>"}`; inline examples remain supported.

Optional `translation_tracking` compares every locale with a configured source
locale and writes a non-blocking `.docara/translation-status.json` report.
Docara never translates or edits content. Accepting a reviewed translation is a
separate hash-bound dry-run/apply transaction that changes only the configured
lock file.

The developer SDK uses one operation result for human and `--json` output.
`capabilities --json` derives the exact commands, options, schemas, receipts
and lifecycle support from the installed package; AI tools must read it instead
of assuming the newest Docara surface.
Scaffolding is never a one-step write: review the deterministic dry-run diff,
then apply its exact SHA-256 plan. Only project-owned `smart/` and `design/`
sources can be created. Validation, test and QA delegate the production
registries and PreviewKernel. The optional PHP stdio MCP adapter is
`tools/mcp-docara/server.php`; it is read-only unless started explicitly with
`--allow-writes`, and even then apply requires the unchanged dry-run plan.

## Documentation

- [How the engine is organized](docs/site/content/ru/development/architecture.md)
- [Developer and AI SDK](docs/site/content/ru/development/developer-sdk.md)
- [Quick start](docs/site/content/ru/start.md)
- [Project files and configuration](docs/site/content/ru/authoring/project-files.md)
- [Reusable examples](docs/site/content/ru/components/example.md)
- [Translation tracking](docs/site/content/ru/authoring/multilingual-site.md)
- [Layouts, regions and navigation](docs/site/content/ru/authoring/layout-and-navigation.md)
- [Components](docs/site/content/ru/components.md)
- [Build and verification](docs/site/content/ru/build.md)
- [Portable project format](docs/site/content/ru/authoring/project-files.md)
- [Release notes](https://github.com/simai/docara/releases)

The component index, menu, search, outline and previous/next links are derived
from the same physical Markdown route set; there is no separate public page
catalogue.

## Repository checks

```bash
php vendor/bin/pint --test
php vendor/bin/phpunit
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
```

The stable release identity is recorded in `VERSION`, `CHANGELOG.md`, the
matching `vX.Y.Z` tag, and [release notes](https://github.com/simai/docara/releases).

## Local release-readiness check

Release packaging is an exact-revision, non-publishing operation. Run it only
from a clean checkout and pass a planned version/tag as parameters:

```bash
php vendor/bin/docara capabilities --json > current-capabilities.json
php scripts/verify-ai-contract-release.php \
  --previous=/path/to/previous-capabilities.json \
  --current=current-capabilities.json \
  --skill-dna=/path/to/ai-codex-skill-docara/graph/dna/skill-dna.json \
  --federation-lock=/path/to/ai-codex/federation/releases/stable.json \
  --skill-revision=<exact-compatible-skill-commit>

REVISION=$(git rev-parse HEAD)
PLANNED_VERSION="replace-with-approved-version"
PLANNED_TAG="replace-with-approved-tag"
php scripts/build-release-package.php \
  --revision="$REVISION" \
  --version="$PLANNED_VERSION" \
  --tag="$PLANNED_TAG" \
  --output=build_release
php scripts/verify-release-package.php \
  "build_release/docara-$PLANNED_VERSION.release-manifest.json"
```

The AI gate blocks a changed public capability surface without an incremented
`docara.ai_contract`, a compatible canonical skill range, and the exact skill
revision in the Federation stable release lock.

The command reads only committed blobs from that exact revision. It writes a
deterministic ZIP, a paired manifest containing the archive checksum and full
file ledger, a checksum file and an in-archive CycloneDX dependency inventory.
The package excludes VCS, CI, tests, graph, workflow evidence, caches and
credentials. `published=false` and the tag is only a planned parameter: no tag,
GitHub release or package publication is created.

Before an approved release, build twice in independent clean clones, install
both ZIPs into fresh Composer consumers, verify their consumer-owned locks,
run init/update/build/static/browser checks, and retain the exact rollback and
smoke plan. See [publishing and rollback](docs/site/content/ru/build/publish.md).

## License

MIT
