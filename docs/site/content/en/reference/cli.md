---
description: Reference for the docara commands: build, check, preview, update and the SDK. Commands run from the site folder through php vendor/bin/docara.
---

# CLI commands

Commands run from the site folder that holds `docara.json` and
`simai-framework.lock.json`, through the project-local
`php vendor/bin/docara`. `docara` without arguments and `docara list` without
a kind print the list of commands; `docara --version` prints the installed
version. The common Symfony Console options (`--help`, `--quiet`,
`--verbose` and others) are not listed on this page.

The `--json` option of the Developer/AI SDK commands returns a stable
machine-readable result of the operation and an exit code.

## `init`

```bash
php vendor/bin/docara init [path] [--template=minimal|showcase] [--lang=en|ru]
```

Creates a project in an empty folder, or in a folder that holds only the
checked project-local `composer.json`, `composer.lock` and `vendor/` with
`simai/docara`. The path may be absolute or relative; the default is the
current folder.

- `--template` (`-t`) — the starter: `minimal` (the default; a home page, a
  page about writing pages and a components page) or `showcase` (a component
  catalogue, a landing page, nested guides and project Smart examples).
- `--lang` (`-l`) — the language of the starter content: `en` (the default)
  or `ru`. `showcase` is available only with `--lang=ru`; combined with
  `--lang=en` it stops with an error and writes nothing.
- `--update` (`-u`) — a guard flag: the command stops with an error, creates
  nothing and points to the explicit order `update --verify`, then
  `--dry-run`, then `--apply`.

## `build`

```text
php vendor/bin/docara build [environment] [--page=/public/url/]
```

The default environment is `local`; the result folder is
`build_<environment>`. For a result to publish, use `build production`. The
build is atomic: on an error the previous result stays.

`--page` quickly rebuilds one existing page after the first full build:

```bash
php vendor/bin/docara build production --page=/ru/components/badge/
```

Docara checks the accepted full-build receipts, selects the route before
compiling the other pages and produces the selected HTML with the same
PageBuilder. Navigation and search stay from the accepted full build. After
you add, delete or rename pages, or change the menu, `docara.json` or the
Framework lock, run an ordinary full build without `--page`.

If `docara.json` sets `site_url`, the build also writes `sitemap.xml`,
`robots.txt` and `llms.txt`, absolute `canonical`/`hreflang` and Open Graph
tags; see [Site address and search engines](/en/settings/site-address/).

## `verify-static`

```text
php vendor/bin/docara verify-static [directory] [--receipts=DIR]
```

Without an argument it checks `build_production`. Build receipts are read
from the neighbouring folder `<directory>.docara/`; `--receipts` names their
folder explicitly, for example for a copy already placed on hosting. The
check does not run project PHP code: it reads the static folder, the
receipts and the manifest, and prints JSON with `html_pages`,
`local_references_checked` and a `broken` list. Details are in
[Checking the build](/build/verify/).

For a site with `site_url` the check also reads `sitemap.xml`, `robots.txt`
and `llms.txt`: the files are valid, every address lies under `site_url`,
every sitemap address leads to a built indexable page, and every such page is
in the sitemap. Errors are printed with `reference: "@discovery-files"`.

## `portal-artifact`

```text
php vendor/bin/docara portal-artifact [environment] --release=TAG --repository=OWNER/NAME --commit=SHA [--product-version=VERSION] [--output=DIR]
```

Packs a portal build (`docara.json` with a `portal` section) from
`build_<environment>` into the archive `<product>-<version>-portal.tar.gz`,
its `.sha256` and the `simai.io.portal-artifact` receipt. Without an argument
it reads `build_production`; without `--output` the files go to
`build_<environment>.portal/`. Before writing, the command checks every page:
the language, the title, the description, a `canonical` under `site_url`, a
link to the portal home page and one Framework pair. Details are in
[Building for a portal](/build/portal/).

## `serve`

```text
php vendor/bin/docara serve [environment] [--host=localhost] [--port=8000] [--no-build]
```

Builds the chosen environment and starts the built-in PHP server. `--host`
sets the address (the default is `localhost`), `--port` (`-p`) the port (the
default is `8000`). With `--no-build` the server opens the folder that is
already built and checked, without rebuilding. The server runs until
`Ctrl+C`.

## `capabilities`

```bash
php vendor/bin/docara capabilities [--json]
```

Returns the exact version and revision of the installed Docara, the version
of `docara.ai_contract`, the actual commands and options, schemas, SDK types,
receipts, tracking and the update/rollback features. The data comes from the
real Application and the schema registry; there is no separate command list
for AI. `--json` prints the result as JSON.

## `doctor`

```bash
php vendor/bin/docara doctor [--json]
```

Checks the project's active contracts: the configuration, the registries of
Smart components, design artifacts and bindings, and the schema catalogue,
and reports their fingerprints. `--json` prints the result as JSON.

## `upgrade`

```bash
php vendor/bin/docara upgrade [path]
php vendor/bin/docara upgrade --check --json
php vendor/bin/docara upgrade --to=3.0.1 --dry-run --json
php vendor/bin/docara upgrade --apply=<plan-sha256> --json
php vendor/bin/docara upgrade --rollback=<id|latest> --json
```

An explicitly started `upgrade` selects and checks a compatible stable
patch/minor version within the current major and the Composer constraint.
Before apply, the candidate passes doctor, validation, engine sync, a
production build and `verify-static`. An error triggers a compensating
restore of the original lock, dependencies, engine and verified build.

- `--check` — check whether a compatible update is available;
- `--to` — an exact stable target version `X.Y.Z`;
- `--dry-run` — prepare and save an immutable checked plan;
- `--apply` — apply the plan by its SHA-256 if the inputs have not changed;
- `--rollback` — restore an applied update by id or `latest` without the
  network;
- `--json` — the result as JSON.

A major, a moving reference and a stale plan are rejected; a request for
another major returns `MAJOR_UPGRADE_REQUIRED`. Moving between majors is
described in [Moving from 2.x to 3.0](/en/build/update/#moving-from-2-x-to-3-0).

## `update`

```bash
php vendor/bin/docara update [path] --verify
php vendor/bin/docara update [path] --dry-run
php vendor/bin/docara update [path] --apply
php vendor/bin/docara update [path] --rollback=<id|latest>
```

Syncs the package-owned `.docara/engine` with the installed version. Without
an action flag it runs `--verify`; you can give only one action at a time.

- `--verify` checks ownership and reports whether there are changes;
- `--dry-run` creates a hash-bound plan and shows the exact operations;
  `--diff` is its alias;
- `--apply` applies only an unchanged plan by atomically replacing the
  engine-owned state, and saves a rollback;
- `--rollback` first checks the manifest and hashes, then restores the
  previous engine state;
- `--adopt` is allowed only together with dry-run, to explicitly adopt a
  project without an ownership manifest;
- `--json` gives a stable machine-readable answer and exit code.

Update never overwrites `content/**`, `assets/**`, `docara.json`,
`section.json`, `.page.json`, locale files or the consumer-owned
`composer.lock`.

## `list`

```bash
php vendor/bin/docara list [kind] [--json]
```

Without a kind it prints the list of commands. With a kind it lists the
registered SDK objects: `page`, `source`, `smart`, `binding`, `layout`,
`view`, `section`, `block`, `provider`, `fixture`, `state` or `schema`. The
`page` kind shows the physical Markdown pages. `--json` prints the result as
JSON.

```bash
php vendor/bin/docara list page --json
```

## `inspect`

```bash
php vendor/bin/docara inspect <kind> <id> [--json]
```

Shows one registered object with its ownership and provenance. The kind is
`page`, `source`, `smart`, `binding`, `layout`, `view`, `section`, `block`,
`provider`, `schema`, `fixture` or `state`. `--json` prints the result as
JSON.

```bash
php vendor/bin/docara inspect page /ru/start/ --json
php vendor/bin/docara inspect source simai-framework:component.buttons --json
```

## `schema`

```bash
php vendor/bin/docara schema <kind> [--json]
```

Shows the active JSON Schema of an SDK surface: `smart`, `layout`, `view`,
`section`, `block`, `binding`, `authoring`, `documentation-source`,
`documentation-tracking`, `capabilities`, `upgrade-plan`, `upgrade-result`,
`upgrade-journal` or a `*.schema.json` file name. `--json` prints the result
as JSON.

## `validate`

```bash
php vendor/bin/docara validate <kind> [id] [--json]
```

Checks a project, a page, a documentation source, a Smart component or a
design artifact with the same production services as the build. The kind is
`project`, `page`, `source`, `smart`, `layout`, `view`, `section` or `block`.
`--json` prints the result as JSON.

```bash
php vendor/bin/docara validate project --json
php vendor/bin/docara validate page /ru/start/ --json
```

The optional `docara.authoring.json` assigns pages one of the built-in
profiles: `landing`, `article`, `tutorial`, `how_to`, `reference` or
`explanation`. Without it the profile check does not apply. The check
reports measurable structural gaps and leaves a meaning checklist with the
status `review_required`; it does not call AI and does not change the
Markdown. The file format and the profiles are described in
[Page profiles](/authoring/page-profiles/).

## `scaffold`

```bash
php vendor/bin/docara scaffold page [<route>] --type=how_to --title="..." [--locale=ru] [--json]
php vendor/bin/docara scaffold <kind> <id> [--locale=ru] [--title="..."] \
  [--profile=how_to] [--source=<id>] [--entity=<key>] --dry-run [--json]
php vendor/bin/docara scaffold --apply=<plan-sha256> [--json]
```

Creates a hash-bound starter plan and applies it only explicitly. The kind
is `smart`, `design` or `page`; `id` is the artifact id or the
locale-relative route of the page. Without `--dry-run` the command creates a
page at once, from the template of its type. Without `<route>` the address
comes from the type and the title, for example `how-to/rotate-the-keys`. See
[Page profiles](/en/authoring/page-profiles/#a-new-page-of-a-type).

- `--locale` — a configured locale for the page; the default is the main
  locale of the site;
- `--title` — the page title;
- `--type` — the page type, the same as `--profile`;
- `--profile` — a built-in page profile;
- `--template` — a template from `docara.authoring.json`: the page gets its
  sections in order, see [Page templates](/en/authoring/page-templates/);
- `--source` — the id of a configured documentation source;
- `--entity` — the stable key of a source entity;
- `--dry-run` — create a deterministic plan to review;
- `--apply` — apply the exact plan by SHA-256 after checking the inputs
  again;
- `--json` — the result as JSON.

```bash
php vendor/bin/docara scaffold page guides/new-page \
  --locale=ru --title="Новая страница" --profile=how_to --dry-run --json
php vendor/bin/docara scaffold --apply=<plan-sha256> --json
```

Scaffold works only for Markdown that does not exist yet. You edit an
existing page directly, then run `validate`, build and `verify-static`. A
source-aware scaffold takes the title and the measurable structure from the
contract, but does not invent an unknown API or example:

```bash
php vendor/bin/docara scaffold page /ru/components/new-component/ \
  --source=simai-framework --entity=component.new-component \
  --locale=ru --profile=reference --dry-run --json
```

## `documentation`

```bash
php vendor/bin/docara documentation status [--source=<id>] [--kind=<kind>] [--status=<status>] [--json]
php vendor/bin/docara documentation accept --source=<id> --key=<key> \
  --route=<route> --example=<case>=<example-id> \
  --review=ai_verified|human_reviewed --dry-run [--json]
php vendor/bin/docara documentation accept --apply=<plan-sha256> [--json]
```

The action is `status` or `accept`. The optional `documentation_tracking`
matches the Markdown and shared examples with the public contract of the
source project.

- `--source` — the id of a configured documentation source;
- `--kind` — a filter by entity kind;
- `--status` — a filter of the report by status;
- `--key` — the stable key of a source entity;
- `--route` — the public route of the page in the source locale;
- `--example` — maps a required case to an example, `case=example/id`; the
  option can repeat;
- `--review` — `ai_verified` (the default) or `human_reviewed`;
- `--exclude-reason` — deliberately exclude an entity with a meaningful
  reason;
- `--dry-run` — create a hash-bound acceptance plan;
- `--apply` — apply the exact plan by SHA-256;
- `--json` — the result as JSON.

`status`, `validate` and build only read the inputs; the build creates the
receipt `build_<environment>.docara/documentation-status.json` but does not
stop because of editorial statuses. You can change `documentation.lock.json`
only with an unchanged hash-bound plan.

```bash
php vendor/bin/docara documentation status --source=simai-framework --kind=component --json
php vendor/bin/docara documentation accept \
  --source=simai-framework --key=component.buttons \
  --route=/ru/components/buttons/ \
  --example=default=components/buttons/basic \
  --review=ai_verified --dry-run --json
```

## `translations`

```text
php vendor/bin/docara translations status [--locale=en] [--status=stale] [--kind=page|lang] [--json]
php vendor/bin/docara translations accept --locale=en --key=<key> --review=ai_verified --dry-run --json
php vendor/bin/docara translations accept --locale=en [--key=<key> ...] [--prefix=<prefix>] [--status=<status>] [--kind=page|lang] --dry-run --json
php vendor/bin/docara translations accept --apply=<plan-sha256> --json
```

The action is `status` or `accept`.

- `--locale` — the target locale;
- `--status` — a filter of the report by status; for `accept`, accept
  everything that such a filtered report shows;
- `--kind` — `page`, or `lang` for `lang.json` keys; `status` without
  `--kind` shows both kinds, `accept` without `--kind` works with pages
  (`page`);
- `--key` — a translation key; repeat it to accept a set of keys;
- `--prefix` — accept all `unverified` and `stale` keys that start with the
  prefix, for example the `guide/` section;
- `--review` — `ai_verified` (the default) or `human_reviewed`;
- `--exclude-reason` — deliberately exclude a locale/key pair; it always
  needs a meaningful reason;
- `--dry-run` — create a checkable acceptance plan;
- `--apply` — atomically apply the exact plan by SHA-256;
- `--json` — the result as JSON.

One `--key` without `--prefix` and `--status` creates the former plan for a
single key (`--exclude-reason` works with it). Several keys, a prefix or a
status create a plan for a set: `accepted` holds the `unverified` and
`stale` items being accepted, and `skipped` holds the selected items in other
statuses, which do not change. An empty selection is the error
`TRANSLATION_ACCEPT_SELECTION_EMPTY`.

With `translation_tracking.mode: "publish-current"` the build publishes only
current translations and prints a list of held-back pages and strings; see
[Multilingual site](/authoring/multilingual-site/).

`status` only reads the sources and the lock file. With
`translation_tracking` turned on, an ordinary build also creates the receipt
`build_<environment>.docara/translation-status.json`; problems it finds are
printed as a warning and do not fail the build. `--apply` changes only the
named lock file, and only if all input hashes match the plan.

## `atlas`

```bash
php vendor/bin/docara atlas [--json]
```

Shows the deterministic Design Atlas built from the admitted registries:
design artifacts, Smart components with their presets, and bindings with
their views. `--json` prints the result as JSON.

## `preview`

```bash
php vendor/bin/docara preview <target> [--page=/route/] [--selector=<id>] [--json]
php vendor/bin/docara preview <target> --watch [--interval=250] [--max-cycles=0]
```

Renders a bounded preview along the production path. `target` is `smart`,
`region`, `layout` or `page`.

- `--page` — the public route that sets the production context;
- `--selector` — the id of a Smart component or the name of a region for a
  narrow target;
- `--json` — the result as JSON;
- `--watch` — watch the dependencies of the target with PHP only;
- `--interval` — the polling interval in milliseconds (the default is
  `250`);
- `--max-cycles` — the limit of watch cycles; `0` means until interrupted.

## `test`

```bash
php vendor/bin/docara test <kind> <id> [--page=/route/] [--json]
```

Checks and renders one declared fixture of a Smart component or layout
through PreviewKernel. `kind` is `smart` or `layout`. `--page` sets the
public route for the production context; `--json` prints the result as JSON.

## `qa`

```bash
php vendor/bin/docara qa report [--env=local] [--top=10] [--html] [--min-score=N] [--json]
php vendor/bin/docara qa review-packet [route] [--env=local] [--json]
php vendor/bin/docara qa <smart|region|layout> <id> --page=/route/ --dry-run [--json]
php vendor/bin/docara qa --finalize-reference=<draft> [--json]
php vendor/bin/docara qa --verify=<plan-sha256> [--json]
```

`qa report` checks the built site `build_<env>/` and gives the documentation
a score from 0 to 100. It prints the score, the group scores and a "fix
first" list, and writes `build_<env>.docara/quality-report.json`. Build the
site beforehand with the same `build <env>` command.

- `--env` — which build to check; the default is `local`;
- `--top` — how many items to show in the "Fix first" list; the default is
  10;
- `--html` — also write `build_<env>.docara/quality-report.html`; this file
  is not published;
- `--min-score` — strict mode: exit code 1 and `QUALITY_SCORE_BELOW_MINIMUM`
  if the score is below the number. Without the option, `quality.min_score`
  from `docara.authoring.json` applies.

`qa review-packet` writes questions for a meaning review of one page (with an
address) or the whole site (without an address) to
`build_<env>.docara/review-packets/`, together with the page text, the
profile and the sources. Docara does not answer them: a person or an AI
assistant does. Details are in
[Documentation quality](/en/authoring/documentation-quality/).

```bash
php vendor/bin/docara build production
php vendor/bin/docara qa report --env=production --min-score=80
php vendor/bin/docara qa review-packet /ru/start/quick-start/ --env=production
```

The `smart`, `region` and `layout` kinds plan or check an optional browser,
a11y and visual check on an isolated preview along the production path.

- `--page` — the public route of the page to check; required together with
  `--dry-run`;
- `--dry-run` — publish the preview and create a deterministic plan of
  scenarios. Without `--dry-run` and a non-empty `--page` (and without
  `--verify` or `--finalize-reference`) the command returns
  `QA_DRY_RUN_REQUIRED`;
- `--finalize-reference` — check a recorded reference draft and bind its
  manifest to a new immutable plan;
- `--verify` — check an external QA report by the plan's SHA-256;
- `--json` — the result as JSON.

## A first checked run

```bash
mkdir /path/to/site
cd /path/to/site
composer require simai/docara:^3.0
php vendor/bin/docara init . --lang=ru
php vendor/bin/docara capabilities --json
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```
