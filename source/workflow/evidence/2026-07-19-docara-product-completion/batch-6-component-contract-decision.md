# Batch 6 — effective component catalogue contract

Date: 2026-07-19
Base revision: `8c241f5b088ed92934274cad28659060a892a514`
Status: `IMPLEMENTED_PENDING_ACCEPTANCE`

## Product decision

Docara will expose one deterministic
`docara.effective_component_catalog.v1` projection. It is not a second Simai
Framework registry and cannot admit a component by itself.

The projection has exactly three executable owner sources:

1. the native Markdown profile actually configured by Docara;
2. Docara-owned typed composition definitions used by the real parser and
   renderer;
3. exact Smart manifests admitted by the immutable Docara Framework lock.

Explicit future requirements may also appear in the projection, but only with
`admission_pending`, `framework_gap` or `deferred` lifecycle. Runtime bytes,
CSS classes, an upstream example or an old documentation page never imply
`supported`.

## Lifecycle

- `supported`: the exact current product accepts and renders the authoring
  contract with tests and documentation;
- `admission_pending`: a bounded upstream or Docara contract exists, but
  admission assets, schema, tests, documentation or browser evidence are not
  complete;
- `framework_gap`: no sufficiently accepted upstream authoring,
  accessibility or asset contract exists;
- `deferred`: a distinct author job is intentionally outside the current
  product vertical.

Limitations are explicit structured facts and do not become a vague `partial`
state. Verification readiness remains separate from runtime lifecycle.

## One source per decision

### Native Markdown

One `PortableMarkdownProfile` owns both the configured CommonMark extensions
and the corresponding effective capability IDs. Descriptive resources may add
titles, examples and documentation references, but cannot widen the profile.

### Docara typed composition

One strict typed-definition repository owns the executable directive names:

- `docara.card` through `:::card`;
- `docara.steps` through `:::steps`;
- `docara.cta` through `:::cta`;
- `docara.features` through `:::features`.

The parser, inspector and renderer consume this repository. Their current
parallel `card|steps|cta|features` lists must be removed. Renderer IDs remain a
small executable code allowlist; a resource cannot introduce executable PHP.

`docara.card` also fulfils the neutral panel author job. Docara will not add an
aesthetic `:::panel` synonym.

The Goal-required Batch 7 executable delta is therefore `docara.columns` plus
the universal catalogue UI for every already supported entry. Tabs, badge and
public icon authoring remain explicit optional requirements with honest
fallbacks; they do not make the current Goal impossible to finish. A
requirement record is never promoted to `supported` in place: once owner work
is accepted, it is retired or replaced by the corresponding native profile,
typed definition or lock-admitted Smart source.

### Simai Framework Smart components

The Framework lock, not a PHP array or catalogue metadata file, owns Smart
admission. Every lock record supplies an exact provider revision and SHA-256;
the safe bundled manifest path is derived by a validated convention, and
Docara's consumer policy may only narrow that admitted manifest. Manifest
discovery, runtime diagnostics and component-call admission are derived from
the immutable lock records. The existing v1 lock schema remains in force; this
batch does not introduce a second admission format.

The component-call schema validates the stable `ui.*` grammar; the lock decides
whether a particular ID is admitted. Asset dependencies are traversed from the
exact runtime dependency graph instead of a component-name special case.

## Effective entries after Batch 6

Supported:

- native headings/text, emphasis/strikethrough, links/images, lists/quotes,
  semantic code and Markdown tables;
- `docara.card`, `docara.steps`, `docara.cta`, `docara.features`;
- `ui.alert`, with `closable=true` explicitly unavailable in the current
  bounded runtime;
- `ui.button`, render-only without an effect/data-binding claim.

Not falsely supported:

- `docara.columns`: `admission_pending`; strict syntax and renderer are a
  Batch 7 job;
- `ui.badge`: `admission_pending`; exact upstream manifest exists, but Docara
  has not admitted it;
- `content.icon`: `framework_gap`; runtime bytes exist, but no canonical
  authoring manifest exists;
- `ui.tabs`: `framework_gap`; the pinned Core/Smart pair lacks the complete
  keyboard, focus and ARIA contract and Docara has no admitted manifest;
- `native.code.enhanced`: `framework_gap`; highlighting/copy bytes do not equal
  a pinned authoring/accessibility contract;
- `ui.dataview`: `deferred`; structured data/settings is a different author job
  from Markdown tables.

Every non-supported entry must expose its owner, reason, safe fallback and
exact admission condition. No page or schema may claim all Framework
components, the historical 331 entities, production readiness or public
release readiness.

## Generated output

Every portable build publishes canonical
`_docara/component-catalog.json` inside its output tree (the public URL is
prefixed by the configured `base_url`), sorted by entry ID and containing only safe
package-relative provenance. Two equal inputs must produce byte-identical
output.

Batch 7 will render one universal catalogue UI from this same projection and
the same example fixtures. It will not maintain handwritten status or
parameter tables.

## Required negative gates

Implementation must fail closed for:

- duplicate IDs across native, typed, Smart and requirement sources;
- a typed parser name absent from the definition repository;
- an unknown executable renderer ID;
- a supported entry without renderer/test/docs evidence;
- a manifest resource, provider revision or SHA-256 mismatch;
- a runtime element not admitted by a manifest record;
- a consumer policy that adds or widens a manifest prop/state;
- author input for a managed property;
- an unexplained non-supported lifecycle;
- an unsafe docs/example/source path;
- an absolute filesystem path, moving reference or readiness overclaim;
- nondeterministic entry order;
- automatic admission of `ui.tabs` or `content.icon` because bytes exist.

## Acceptance boundary

Batch 6 changes the contract and generated JSON, not the visual catalogue. It
requires:

- red-first unit/schema/negative tests;
- full PHP regression and formatting;
- two byte-identical projections and two deterministic static builds;
- zero broken static references;
- exact Framework revisions unchanged and no moving references;
- bounded existing-page browser regression if build output changes;
- complete-diff Human-Centered Simplicity/source/security review;
- independent exact-archive tester `PASS`.

Full responsive/theme/keyboard catalogue acceptance belongs to Batch 7.
Public release, default-branch migration and repository retirement remain
excluded.
