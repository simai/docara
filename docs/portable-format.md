# Portable Docara format

## Decision

The portable format lives in the main `simai/docara` repository as an explicit
mode. A third long-lived Docara implementation would create another runtime,
another release line and another migration problem. Isolation is achieved by a
format marker (`docara.json`) and a separate builder, not by another product.

The existing Blade/Jigsaw path remains available and is not implicitly
migrated. `docara init --portable` refuses to rewrite a legacy project.

## Product boundary

Portable Docara is intentionally smaller than Larena:

- files are the source of truth;
- Markdown is the content store;
- JSON controls presentation and inherited section defaults;
- output is a deterministic static site;
- there is no database, administration panel, workflow, roles or runtime CRUD.

Larena can import the same contract and add those application capabilities. It
must not make the standalone format depend on Larena internals.

## Files and inheritance

`docara.json` defines the site-wide defaults and the immutable Framework lock.
Each `_section.json` applies to its directory and all descendants. A page may
have a sidecar named `<page>.page.json`.

For `content/guides/install.md`, the exact order is:

1. built-in defaults;
2. `docara.json`;
3. optional root `_section.json`;
4. `content/_section.json`;
5. `content/guides/_section.json`;
6. optional `content/guides/install.page.json`;
7. `content/guides/install.md` as content.

Objects merge recursively and scalar values override inherited values. A known
presentation object beginning with `{"$reset": true}` clears that inherited
branch before applying its sibling keys. Every winning value records its source
in `provenance`.

Version 1 deliberately exposes only settings that both presets actually use:

- `layout.max_width`: `compact`, `normal`, `wide`, or `full`;
- `settings.theme`: `system`, `light`, or `dark`;
- `navigation.hidden`: a boolean that removes the page from generated
  navigation.

These nested objects are strict. Unknown or incorrectly typed keys fail schema
validation instead of becoming silent no-ops. Smart-components are authored
only inside Markdown; JSON descriptors do not provide a second component list.

## Presets

Version 1 contains two presentation presets:

- `docs` — header, navigation and readable content column;
- `landing` — a focused page without documentation navigation.

Presets are render recipes, not different content types. A page changes preset
through its sidecar and keeps the same Markdown and component-call syntax.

## Markdown extensions

Raw HTML is stripped. Rich elements use fenced, JSON-valued component calls:

```markdown
:::ui.alert
{"type":"warning","title":"Check the lock","supporting-text":"Use an immutable revision."}
:::
```

Calls are allowed only when all of the following are true:

1. the component is present in the versioned component-call contract;
2. a bundled real Larena manifest describes its props and host renderer;
3. the manifest provider revision and bytes match the Framework lock;
4. every prop passes the manifest schema and constraints;
5. the runtime asset plan contains only immutable revisions.

The first bounded projection supports `ui.alert` and `ui.button`. Future
Retype-like extensions should be added through the same manifest path, not as
arbitrary HTML or an unrelated shortcode engine.

The accepted pair does not provide the `sf-icon-button` dependency used by a
closable alert. Therefore `ui.alert` with `closable: true` fails closed in this
prototype instead of silently rendering a partially working component.

## Simai Framework runtime

`simai-framework.lock.json` wraps the exact Larena frontend runtime lock, the
hashes of the component manifests used by Docara and an `asset_projection`
record. CSS, utilities, core runtime, Smart base and component dependencies are
emitted in deterministic boot order.

The initial projection uses two deliberately different publication paths:

- Simai Framework Core and the Material Symbols font load from jsDelivr at the
  exact `simai/ui` commit `7e836d8a9414d5da553fb1ab0404721e5b48769a`;
- the exact `alert`, `button` and `icon` Smart JavaScript files from
  `simai/ui-smart@dd786bbae98391fb21df9b4e1e6cd402ead0614c` are verified against
  SHA-256, copied into the reserved `_docara/framework` output namespace and
  addressed with a deterministic cache version derived from the accepted
  runtime pair plus the canonical asset-projection hash.

The bounded adapter uses that exact full Material Symbols font and marks
Framework icons ready only after the browser confirms that the font loaded.
Dynamic icon nodes are observed as well. This avoids the mutable icon-subset
service while keeping the Framework component markup and icon implementation.

There is no `ui-smart` CDN fallback: a missing or changed bundled byte fails the
build. Core remains an exact-revision network dependency, so fully offline
builds require a later, separately accepted Core projection.

The lock is a consumer-verified bounded bundle. It does not assert ecosystem,
production or all-components readiness. `main`, `master`, `latest` and other
moving references fail closed.

## Explainability and determinism

The build writes `.docara/resolved-page-plans.json`. For every page it records:

- the merged configuration;
- ordered input trace with SHA-256;
- value provenance;
- canonical plan hash;
- normalized Smart-component calls;
- exact asset plan;
- output file and public URL.

The builder emits no timestamps or absolute local paths. Identical inputs and
the same lock produce byte-identical output.

## Security boundary

- configuration paths must be relative and remain inside the site root;
- a direct root symlink, including a lexically disguised `root/` or `root/.`, is
  rejected; system/ancestor aliases are resolved once and every input path
  below that resolved root rejects symlink traversal;
- schemas reject unknown top-level fields;
- Markdown unsafe links and raw HTML are disabled;
- component output is rendered by the host adapter with escaped scalar props;
- page slugs and `base_url` use a portable path alphabet;
- `_docara` and `.docara` are reserved output namespaces;
- the builder only cleans a direct `build` or `build_*` directory inside the
  site root, never a symlink or a path overlapping content/lock inputs;
- generated-page and content-asset output collisions fail before cleaning an
  existing destination.

## Larena import boundary

Standalone Docara is the only interpreter of the content tree, `docara.json`,
section descriptors, page sidecars and Markdown extensions. It emits the
canonical `.docara/resolved-page-plans.json` artifact. Larena accepts that
artifact only with an external SHA-256 receipt, rechecks its canonical hashes
and exact Framework lock, re-renders component props through its own Smart
Registry, and then maps the verified plan to Larena contracts. Validation does
not mutate source files or the database.

The standalone fixture and its Larena adapter are acceptance artifacts. The
legacy `docara-template` and `docara-mix` repositories must not be archived
until their consumers are inventoried, migrated and independently accepted.

## Release boundary

This is a local compatibility prototype, not a public release candidate. The
inspected `ui-smart` source revision does not contain a license file. Public
push, package publication, tag or release containing its projected JavaScript
requires an explicit owner/legal redistribution decision first.
