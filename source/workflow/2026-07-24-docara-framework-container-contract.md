# Workflow: Docara Framework container contract

Date: 2026-07-24
Status: completed
Project mode: productization
Project size: medium, multi-repository
Primary owner: `docara`
Companions: `sf5`, `teamlead`, `docs`, `tester`
Track: `docara-consolidation`
quality_controls: human_centered_simplicity
simplicity_review: .hcs-audit/docara-framework-container-review.json
simplicity_repository_refs: repo://docara-consolidation
simplicity_repository_baselines: repo://docara-consolidation@ecfc8b72f34a020b1f7374e11eb5b33c0838aabe

## Current Goal

Завершить переход Docara на единый контейнер SIMAI Framework: оставить
системные size tokens единственным контрактом ширины, обновить конфигурацию,
схемы, рендеринг и документацию, пересобрать и проверить `docara.test`.

## Done When

- единственная настройка ширины — `layout.container.max` со значениями 1–8;
- значение отображается в `max-container-*`, а ширина приходит из size tokens;
- старые поля, режимы и жёстко заданные ширины отсутствуют;
- точные Framework revisions зафиксированы;
- тесты, воспроизводимая сборка, статическая и браузерная приёмка проходят;
- проверенный результат опубликован на `https://docara.test/`.

## Stages

- [x] Исправить и воспроизводимо собрать Framework-контракт контейнера.
- [x] Перевести Docara на новую конфигурацию без compatibility layer.
- [x] Обновить документацию, тесты и локальный сайт.
- [x] Провести статическую, адаптивную и браузерную приёмку.

## Batches

- [x] Framework source/build/distribution/docs/demo.
- [x] Docara schema/compiler/templates/CSS/tests/docs.
- [x] Exact lock integration and production build.
- [x] `docara.test` publication, browser verification and rollback evidence.

## Evidence Plan

- `source/workflow/evidence/2026-07-24-docara-framework-container-contract/acceptance.md`
- `source/workflow/evidence/2026-07-24-docara-framework-container-contract/technology-evidence.json`
- exact Framework commits, reproducibility hashes, PHPUnit/Pint/static checks;
- desktop/mobile, LTR/RTL, light/dark and full-bleed browser measurements.

## Track Linkage

Track: `docara-consolidation`. Это завершённый локальный productization batch.
Публичный release, tags и перенос default branches остаются отдельной
контролируемой операцией.

## Goal

Move Docara to one standard page-width contract based entirely on SIMAI
Framework `container` and `max-container-*`, correct the Framework defect that
prevents values 7 and 8 from reaching their declared widths, and completely
remove Docara's experimental `layout.max_width` model and hard-coded container
widths.

## Final Configuration

```json
{
  "layout": {
    "key": "docara.docs",
    "container": {
      "max": 7
    }
  }
}
```

Meaning:

- `layout.container.max` is the only page-width setting;
- the value is an integer from `1` through `8`;
- Docara validates the value and maps it to
  `max-container-<value>`;
- the default is `7`, which resolves through
  `--sf-container-7--size-max` to the size-system token `--sf-i3`;
- no arbitrary pixels or CSS class strings are accepted from configuration.

There is no separate text-width setting. Paragraph line length remains a
typography concern of SIMAI Framework. Grid columns, tables, code, media and
individual landing components use the available container space according to
their own contracts.

## No Compatibility Layer

Docara is still under development and has no external installations that need
this experimental contract. Therefore:

- delete `layout.max_width` from every schema;
- delete `maxWidth` and `max_width` from compiler, plans, publishers and view
  models;
- delete `data-width` output and its CSS selectors;
- delete `--docara-landing-max-width`;
- delete all examples, tests and documentation for
  `compact|normal|wide|full`;
- do not add aliases, migrations, deprecation warnings, fallbacks or automatic
  conversion;
- old configuration containing `layout.max_width` must fail schema validation
  as an unknown property.

## Intended Rendering

The outer background may span the viewport. The content inside it uses the
same Framework container as the rest of the page:

```html
<body class="max-container-7">
  <header>
    <div class="container m-inline-auto">...</div>
  </header>

  <div class="container m-inline-auto">
    <aside>...</aside>
    <main>...</main>
    <aside>...</aside>
  </div>

  <section data-docara-width="full">
    <div class="container m-inline-auto">...</div>
  </section>
</body>
```

The aligned inner surfaces are:

- header;
- documentation grid with sidebar, main content and outline;
- landing section content;
- footer content where present.

Full-bleed Hero, Promo and other landing surfaces retain viewport-wide
backgrounds. Only their inner content is constrained.

## Current Evidence

### Framework

- `.container` provides responsive width and gutters;
- `max-container-1..8` uses the container tokens
  `--sf-container-1--size-max` through
  `--sf-container-8--size-max`;
- those container tokens resolve to the canonical size-system tokens
  `--sf-h5`, `--sf-h6`, `--sf-h8`, `--sf-i0`, `--sf-i1`, `--sf-i2`,
  `--sf-i3` and `--sf-i4`;
- at a wide viewport the current runtime resolves values 1..6 correctly;
- values 7 and 8 both stop at `--sf-i2` because `.container` keeps
  `width: var(--sf-breakpoint-xxl)` while `max-container-*` changes only
  `max-width`.

### Docara

Current experimental code contains:

- `layout.max_width` in schemas and project JSON;
- `max_width`/`maxWidth` in compiler, projector, publisher and view model;
- `data-width` in the page template;
- hard-coded `45/60/80/104rem` content widths instead of size-system tokens;
- hard-coded `104rem` header, documentation grid and landing container widths
  instead of the `--sf-container-7--size-max` / `--sf-i3` token chain;
- tests and documentation for the obsolete model.

## Repository And Ownership Map

| Repository | Role | Allowed change in this workflow |
|---|---|---|
| `ui-control` | Framework coordination and exact compatibility chain | record baseline, assignments, evidence and acceptance |
| `ui-loader` | editable Framework utility source | correct `container`/`max-container` source |
| `ui-builder` | canonical deterministic builder | build the exact source candidate; change only if the source cannot be built correctly without a builder fix |
| `ui` | generated Framework distribution | generated output only; never hand-edit |
| `ui-doc` | Framework documentation | correct container reference and demonstration |
| `ui-play` | executable Framework conformance surface | wide-container browser fixture when required |
| Docara worktree | Docara source, starter, docs and tests | replace the width model and consume a pinned Framework candidate |

Legacy `sf5.webpack` is retired provenance only and must not be used.

## Dependency Chain

```text
accepted Framework baseline
  -> ui-loader source correction
  -> ui-builder deterministic candidate build
  -> generated ui candidate
  -> Framework tests + ui-play/browser acceptance
  -> ui-doc correction
  -> immutable Framework revision
  -> Docara lock update
  -> Docara schema/compiler/template cleanup
  -> Docara tests/build/static verification
  -> docara.test browser acceptance
```

Docara must not implement local CSS that compensates for a broken Framework
candidate.

## Work Plan

### Stage 0. Freeze the Framework baseline

Owner: `teamlead` through `ui-control`.

Actions:

1. Refresh the exact revisions and worktree state of `ui-loader`,
   `ui-builder`, `ui`, `ui-doc` and `ui-play`.
2. Resolve the currently pending Framework candidate chain before creating a
   new generated pair.
3. Create isolated worktrees for this container change.
4. Record source, builder, generated distribution and consumer revisions.
5. Confirm that no active worker owns the same generated files.

Done when:

- one exact accepted baseline is recorded;
- source and generated ownership are unambiguous;
- no overlapping writer exists;
- rollback revisions are known.

Stop conditions:

- current Framework source/builder/generated pair is not accepted;
- generated output cannot be reproduced from the recorded source and builder;
- another active workflow owns the same utility or distribution paths.

### Stage 1. Correct the Framework contract at the source

Owner: `sf5`; source repository: `ui-loader`.

Affected layer: utility.

Actions:

1. Define `max-container-N` as a maximum width expressed only through the
   Framework size-system and container tokens.
2. Correct the canonical SCSS in
   `src/utility/max-container/default/`.
3. Preserve responsive gutters and centering from `.container`.
4. Prevent horizontal overflow when the viewport is narrower than the chosen
   maximum.
5. Keep LTR and RTL behavior identical.
6. Add the narrowest regression fixture proving all eight token chains.

Acceptance:

At a sufficiently wide viewport, nested `.container` elements must resolve
through the complete token chain:

| class | container token | size-system token |
|---|---|---|
| `max-container-1` | `--sf-container-1--size-max` | `--sf-h5` |
| `max-container-2` | `--sf-container-2--size-max` | `--sf-h6` |
| `max-container-3` | `--sf-container-3--size-max` | `--sf-h8` |
| `max-container-4` | `--sf-container-4--size-max` | `--sf-i0` |
| `max-container-5` | `--sf-container-5--size-max` | `--sf-i1` |
| `max-container-6` | `--sf-container-6--size-max` | `--sf-i2` |
| `max-container-7` | `--sf-container-7--size-max` | `--sf-i3` |
| `max-container-8` | `--sf-container-8--size-max` | `--sf-i4` |

The browser assertion compares the measured container width with the resolved
value of `--sf-container-N--size-max`. It must not duplicate the current token
values as hard-coded pixels in tests.

Also verify 390, 520, 720, 960, 1140, 1320, 1664, 1792 and 2560px:

- container never exceeds the viewport;
- Framework gutters remain correct;
- centering is correct;
- no horizontal overflow;
- LTR and RTL are equivalent.

### Stage 2. Build and accept the Framework candidate

Owners: `sf5`, `ui-builder`; gatekeeper: `tester`.

Actions:

1. Build from the exact `ui-loader` source through the canonical
   `ui-builder`.
2. Generate `ui` distribution files; do not edit them manually.
3. Confirm reproducibility from a clean archive/worktree.
4. Verify source-to-generated mapping for:
   - utility CSS;
   - minified CSS;
   - `utility.full.css`;
   - relevant loader/rule metadata.
5. Run the utility fixture and browser matrix from exact candidate bytes.
6. Run the repository-wide Framework checks selected by the SF5 change gate.
7. Obtain an independent acceptance verdict.

Done when:

- the exact generated candidate is reproducible;
- the width matrix passes;
- existing values 1..6 have no regression;
- candidate SHA values and evidence are recorded in `ui-control`.

Release or tag publication remains a separate gated action. Docara may use an
exact accepted commit candidate for local integration, but a public Docara
release must use an immutable published Framework revision.

### Stage 3. Correct Framework documentation and demo

Owner: `docs` with `sf5` technical verification.

Actions:

1. Update the `container` and `max-container` reference pages.
2. Explain parent modifier plus nested container markup.
3. Show all eight values and responsive behavior.
4. Add or update a live Playground example.
5. Remove claims that are not proven by the accepted candidate.
6. Build `ui-doc` and verify links and the rendered example.

Done when:

- documentation matches the exact accepted implementation;
- the demo visually proves values 7 and 8 on a wide viewport;
- no legacy `sf5.webpack` instructions are introduced.

### Stage 4. Replace the Docara configuration contract

Owner: `docara`.

Actions:

1. Replace `layout.max_width` with:

   ```json
   "container": {
     "max": 7
   }
   ```

   inside `layout`.
2. Define `max` as a required integer with minimum 1 and maximum 8.
3. Add the default to Docara starter and Docara's own documentation site.
4. Preserve normal site -> section -> page inheritance for the whole
   `layout.container` object.
5. Map only validated integers to a whitelisted
   `max-container-1..8` class.
6. Reject obsolete `max_width` and arbitrary strings.
7. Keep size values in Framework CSS tokens; Docara must not copy their current
   `rem` or pixel equivalents into PHP, JSON or product CSS.

Acceptance:

- values 1..8 compile to the exact expected class;
- 0, 9, strings, arbitrary CSS and obsolete `max_width` fail validation;
- site, section, page and `$reset` inheritance are covered by tests.

### Stage 5. Simplify the Docara renderer and CSS

Owner: `docara`; Framework conformance reviewed by `sf5`.

Actions:

1. Remove `maxWidth` from `PortablePageViewModel`.
2. Remove `max_width` from plans, projectors, publishers and examples.
3. Remove `data-width` from templates.
4. Apply `max-container-N` at the stable shell ancestor.
5. Apply `container m-inline-auto` to header, documentation grid, landing
   inner containers and footer.
6. Remove:
   - `.docara-content[data-width=...]`;
   - `--docara-landing-max-width`;
   - all legacy `45/60/80/104rem` width rules;
   - hard-coded `104rem` shell widths, replacing their intended behavior with
     the Framework `max-container-7` token chain.
7. Retain only product-specific CSS for composition or behavior that a
   Framework utility does not provide.
8. Keep readable paragraph length under Framework typography tokens rather
   than a Docara layout setting.

Done when:

- one container system controls every preset;
- documentation and landing pages share alignment;
- full-bleed backgrounds remain full width;
- no obsolete width code remains.

### Stage 6. Clean projects, examples, tests and documentation

Owners: `docara`, `docs`.

Actions:

1. Remove `max_width` from:
   - `docs/site`;
   - portable starter;
   - section and page examples;
   - component catalog examples;
   - tests;
   - user and developer documentation.
2. Replace examples with `layout.container.max`.
3. Explain that text line length belongs to Framework typography.
4. Document site default and optional section/page override.
5. Add an example of:
   - documentation preset;
   - landing preset;
   - full-bleed Hero with aligned inner container.
6. Run a zero-reference search for all legacy terms.

Required zero-reference search:

```text
layout.max_width
max_width
maxWidth
data-width
docara-landing-max-width
compact|normal|wide|full as Docara width values
```

The generic words `compact`, `normal`, `wide` and `full` may remain only where
they belong to unrelated component contracts; each surviving hit must be
reviewed.

### Stage 7. Pin Framework and accept Docara

Owners: `docara`; gatekeeper: `tester`; local publication gate: `ops`.

Actions:

1. Update `simai-framework.lock.json` to the exact accepted Framework pair.
2. Run:

   ```bash
   vendor/bin/phpunit
   vendor/bin/pint --test
   php vendor/bin/docara build production
   php vendor/bin/docara verify-static build_production
   ```

3. Verify exact generated bytes and no stale output.
4. Publish reversibly to local `docara.test`.
5. Browser-test:
   - documentation and landing presets;
   - 390px mobile and wide desktop up to 2560px;
   - light and dark themes;
   - LTR and RTL;
   - header/main/footer alignment;
   - sidebar and outline behavior;
   - Hero full-bleed background with aligned content;
   - no horizontal overflow or console errors.
6. Obtain an independent Docara acceptance verdict.

Done when:

- Framework and Docara candidates are connected by exact immutable revisions;
- all automated and browser checks pass;
- `https://docara.test/` serves the verified build;
- rollback is recorded;
- no release or production-readiness claim is made without its separate gate.

## Owner And Gate Matrix

| Stage | Owner | Reviewer | Gatekeeper |
|---|---|---|---|
| baseline | `teamlead` / `ui-control` | `sf5` | compatibility evidence |
| Framework source | `sf5` / `ui-loader` | `docs` for public naming | `tester` |
| Framework build | `ui-builder` | `sf5` | reproducibility + `tester` |
| Framework docs | `docs` / `ui-doc` | `sf5` | docs build/browser |
| Docara contract | `docara` | `sf5` | `tester` |
| Docara docs | `docs` | `docara` | docs build |
| local publication | `docara` | `tester` | `ops` rollback/smoke |

## Risks And Controls

### Active Framework work

`ui-control` currently records an active source/builder/generated candidate.
Do not generate another distribution from a mixed or unaccepted chain.

Control: Stage 0 must select an accepted baseline and isolated worktrees.

### Generated repository edits

`ui` is a generated distribution.

Control: all edits originate in `ui-loader`; `ui-builder` produces `ui`.

### Incorrect fluid behavior

A naïve `width: 100%` fix may change intermediate breakpoint behavior or
duplicate size-system values outside their canonical tokens.

Control: decide the exact CSS semantics against the complete viewport matrix
before accepting the source patch.

### Partial Docara cleanup

Removing only schema fields would leave stale renderer/CSS behavior.

Control: zero-reference search plus source, tests, documentation, starter and
browser acceptance in the same Docara goal.

### Text readability

Removing the Docara width mode must not create unreadably long paragraphs.

Control: verify Framework typography measure on article paragraphs while
allowing tables, code and media to use the central column.

## Out Of Scope

- a reader-facing control that dynamically changes the whole site container;
- arbitrary pixel widths;
- a second Docara width scale;
- public Framework or Docara release without separate release approval;
- unrelated Framework utilities or Docara redesign.

A dynamic reader control may be considered later as a separate UX goal after
the static authoring contract is accepted.

## Focus Gate

Done When:

- Framework values 1..8 work and are independently accepted;
- Docara has exactly one container setting;
- the obsolete model is completely absent;
- the local site is built and visually accepted.

Current batch:

- completed implementation, local publication and acceptance.

Backlog policy:

- dynamic reader control is parked;
- unrelated utility improvements remain in their owner workflows.

Stop conditions:

- no accepted Framework baseline;
- irreproducible generated candidate;
- conflicting writer on source/generated files;
- failing acceptance matrix;
- publication/release boundary without its gate.

## Final Result

Verdict: `PASS`.

Framework:

- source candidate:
  `ui-loader@922c1745a1f09f291e81fdf4b9cd08274807d45f`;
- canonical builder:
  `ui-builder@894cfd4a323ee99381f21bf97467436c72e8a204`;
- generated Core candidate:
  `ui@f0b41eb526a8f1daf24a34484143bdfabf7802a4`;
- Framework documentation:
  `ui-doc@39a2312e7395e5f0aa3e2aa4f4c5a730cb2fb1db`;
- executable container demo:
  `ui-play@4225ec17b6ff79a6d957d765430ede7e8f73a90e`;
- generated distribution raw Git archive SHA-256:
  `040485a1dc7cba671e05240e3a9a8b93b5982cb5b264b5a044e213ac26dc93a9`;
- generated distribution file count: `5873`;
- Smart candidate remains
  `ui-smart@ab896dc7cd33f151377e3992ffb286769beee7f7`;
- compatibility pair:
  `sf-v5.3.2-f0b41eb5-ab896dc7`;
- two independent product-build waves are byte-identical for Core,
  Component, Utility and Smart;
- the container fixture covers all eight token chains, nine viewport widths,
  LTR and RTL;
- the runtime correction also preserves the released Smart style-readiness
  API and makes Highlight resolve lazy chunks from its component root.

Docara:

- `layout.container.max` is the only page-width setting;
- the accepted default is `7`, resolving through
  `--sf-container-7--size-max` to `--sf-i3`;
- schema, compiler, inheritance, renderer, starter, examples and documentation
  use the same integer `1..8` contract;
- the obsolete `layout.max_width`, `max_width`, `maxWidth`, `data-width`,
  `--docara-landing-max-width` and hard-coded container widths have zero
  remaining references in product, starter, tests and documentation;
- header, documentation grid, landing content and footer share
  `container m-inline-auto`;
- full-bleed landing surfaces span the viewport while their inner content
  remains aligned to the common container.

Acceptance:

- PHPUnit: `320 tests, 4957 assertions`, PASS;
- Pint: PASS;
- JSON validation: PASS;
- production build: `90` source pages, PASS;
- static verification: `198` HTML pages, `10718` local references,
  `broken: []`;
- fresh browser console: no errors or warnings;
- browser widths `390`, `1440`, `2560`: landing and documentation presets
  have no horizontal overflow;
- at the wide viewport, header, article, documentation grid and full-bleed
  inner content are all `1664px` and centered; the full-bleed surface itself
  remains viewport-wide;
- light and dark themes both pass with no overflow;
- Highlight lazy chunks load and code blocks receive `hljs` markup;
- RTL is covered by the arbitrary-BCP47 portable-site build test and by the
  exact Framework LTR/RTL container matrix.

Local publication:

- URL: `https://docara.test/`;
- ServBay document root:
  `/Users/rim/Sites/docara.test/build_production`;
- rollback backup:
  `/Users/rim/Sites/docara.test/.docara-backups/framework-container-20260724-145338`;
- rollback command:
  `rsync -a --delete --exclude=.docara-backups/ /Users/rim/Sites/docara.test/.docara-backups/framework-container-20260724-145338/ /Users/rim/Sites/docara.test/`.

Evidence:

- `source/workflow/evidence/2026-07-24-docara-framework-container-contract/acceptance.md`;
- `/tmp/docara-container-fw-repro-922c174/report.json`;
- action gate:
  `source/output/action-gates/action-gate-report-20260724115255.json`.

## Readiness

`LOCAL_INTEGRATION_ACCEPTED`.

This verdict accepts the local Docara integration and the exact candidate
revisions above. It does not claim a public Framework release, Docara release,
production readiness, default-branch integration or readiness of every
Framework component.

## Next

Keep the exact candidate locks until a separate release workflow publishes an
immutable Framework release and deliberately updates Docara. Dynamic
reader-controlled container width remains a separate optional UX goal.
