# Goal 1 current result

Status: `G1.0 baseline checkpoint in progress`

The accepted extensible LEGO roadmap is now executing as a separate bounded
Goal 1 workflow. Input revision is
`313afa17e21df2299a6276d246cb4508c7ec00b5`; the accepted rc.3 and its test-site
evidence remain historical baselines, not deployable identities for the new
product source.

G1.0 records the current six built-in Smart artifacts, every central
component-ID/namespace branch, the false acceptance row, source-pinned committed
SF5 candidate revision and a disposable 103-route public baseline. Runtime code
has not changed yet. Evidence:
`source/workflow/evidence/2026-08-02-docara-goal1-portable-smart-runtime/G1.0-BASELINE-AND-PATH-MAP.md`.

# M0 result

Status: mapping preserved; contract contradictions resolved; checkpoint verified

## Candidate

- revision: `2928d68b81665dd4873cebeb87a6192343c28805`;
- exact parent: `a3ba9a4d04429f1f2046b8415764fe7bc89962c7`;
- branch: `codex/docara-unified-architecture`;
- initial worktree state: clean;
- product runtime/config/content/assets/templates: unchanged.

## Inventory

- commands and exact results:
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/M0-EVIDENCE.md`;
- complete route/source/output map:
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/m0-route-inventory.json`;
- findings: 59 physical Markdown routes plus 44 generated projections produce
  103 logical public pages;
- language packs still contain component prose records
  (`ru=42`, `en=42`, `ar=8`, `fr-CA=8`, `zh-Hans=8`);
- all six implementation mappings now contain exact code, test, current
  behavior, gap and deletion-gate references;
- contradictions:
  - the global source-ownership gate blocks M2 until all generated prose is
    gone, while the accepted roadmap schedules bulk migration for M3;
  - the repository has no Composer lock;
  - root README links to absent `docs/site/content/ru/components.md`;
  - historical workflow status claimed M0 inventory before mappings existed.

Contract resolution:

- `DOC-ADR-010..016` record the five mandatory architecture clarifications,
  scoped/global gate order and Composer lock ownership;
- `docara.gate.badge_source_ready` now gates M2, while global
  `source_ownership` remains open through M3;
- the two stale hard-coded `58` assertions now use source/contract-derived
  coverage instead of a duplicated inventory constant;
- the broken root README component link points to the existing physical
  `components/syntax.md`; the missing `/components/` index remains an M3
  Markdown migration item rather than being manufactured during M0;
- the package library intentionally does not own `composer.lock`; exact release
  evidence records its resolved tuple and consumer applications own their lock.

## Baseline verification

- formatter: FAIL on 12 inherited files; no formatting changes applied;
- tests: 343 total, 341 pass, 2 fail on stale expected Markdown count
  `58` versus actual `59`;
- deterministic build: PASS, two 103-page builds have the same 321-file
  manifest SHA-256
  `aea212c5b39f44411356b54841bdf89bde6c797f4c397cb25d629ea1be562b52`;
- static/link verification: PASS, 206 HTML documents, 18,866 local references,
  0 broken;
- badge page: full and isolated builds are byte-identical, HTML SHA-256
  `faeb6c6a8e075bff9ad5602bcea4b1e019c700aeae74f696c0289e32fbb83f79`;
- badge divergence: the physical page is authoritative, but 16 badge previews
  are rendered by hard-coded `InlineComponentRenderer::badge`; the
  declarative receipt contains zero normalized component calls.

## Mapping updates

- completed mappings:
  - `content-first.json`;
  - `composition-and-locales.json`;
  - `typed-ir.json`;
  - `smart-gateway.json`;
  - `pagebuilder.json`;
  - `update-and-locks.json`;
- unresolved mappings: none for current-code ownership; future target modules
  remain intentionally absent until M1/M2 implementation.

## Next bounded batch

- assignment:
  `source/workflow/2026-08-01-docara-m1-m2-bounded-plan.md`;
- acceptance: first resolve the M1/M2 gate-order divergence, then implement a
  typed physical-source locator and zero-growth source boundaries before the
  badge-only target pipeline;
- rollback: every proposed batch has a one-slice revert boundary and retains
  the M0 route, build-manifest and badge hashes.

## Gate status

`docara.gate.m0_baseline` is accepted from the preserved M0 evidence plus the
green contract checkpoint. `STATUS.yaml` now points to ready M1A work. The
scoped badge, global source ownership, vertical-slice, full architecture,
release and production gates remain unclaimed.

## Nonclaims

- no implementation readiness claimed;
- no release or production readiness claimed;
- no default-branch, tag, release or deployment action performed.

# M1A result

Status: typed source locator implemented; exact build parity passed

- authored Markdown discovery now returns typed `PageSource` objects through
  one fail-closed locator and route mapper;
- ambiguous physical routes, unknown locales, outside-root/traversal paths,
  symlinks and non-`.md` public page sources fail before rendering;
- the builder integration changes discovery only; renderer, generated routes,
  content, templates and assets remain unchanged;
- exact base-code versus candidate full builds match across all 321 files;
- full build contains 103 pages; single badge build selects one page;
- badge remains byte-identical at SHA-256
  `faeb6c6a8e075bff9ad5602bcea4b1e019c700aeae74f696c0289e32fbb83f79`;
- PHPUnit passes with 347 tests and 7206 assertions on PHP 8.4.20;
- evidence:
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/M1A-EVIDENCE.md`.

At the M1A checkpoint, M1B remained next and all later gates were still open.

# M1B result

Status: target source guards passed; badge-source gate accepted

- target config and component manifests reject prose, Markdown, HTML and CSS;
- `docara.lang.v1` is the versioned sole target contract for shared public UI
  strings, and `content/ru/lang.json` now carries 66 shared labels without
  page/component/catalog/example prose;
- `site.json`, public `resources/i18n`, legacy language packs and package-owned
  system messages are rejected as target PageBuilder inputs;
- 44 generated routes and current language-pack component counts form an
  explicit, removable-but-non-growing legacy allowlist;
- removing `components.docara.badge` from the active Russian pack leaves the
  authored badge output byte-identical, proving no badge-prose dependency;
- full/single build parity and the exact M0 badge hash remain green;
- PHPUnit passes with 355 tests and 7248 assertions;
- evidence:
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/M1B-EVIDENCE.md`.

`docara.gate.badge_source_ready` is accepted and M2 is ready. Global source
ownership, vertical-slice, release and production gates remain open.

# M2 result

Status: bounded Badge target pipeline accepted; exact output parity passed

- `content/ru/components/badge.md` now compiles to a 35-node typed in-memory
  IR with file/line/column locations;
- all 16 badge calls resolve through the alias registry, renderer registry and
  the content mode of the existing Smart gateway;
- the hard-coded `InlineComponentRenderer::badge` method is removed;
- one `PageBuilder` serves authored pages in full and isolated builds; the
  badge uses the target pipeline while other routes retain the rollback adapter;
- the complete 103-page/321-file build is byte-identical to M1B, static
  verification reports 0 broken references and badge HTML keeps SHA-256
  `faeb6c6a8e075bff9ad5602bcea4b1e019c700aeae74f696c0289e32fbb83f79`;
- no mandatory IR JSON/JSONL is emitted; the only serialization is the
  test-only IR snapshot;
- PHPUnit passes with 359 tests and 7350 assertions;
- desktop/mobile and light/dark Chromium captures pass with 0 console errors
  and 0 warnings;
- evidence:
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/M2-EVIDENCE.md`.

`docara.gate.vertical_slice` is accepted only for Badge. Global source
ownership, full migration, release and production remain unclaimed. M3 is now
eligible for a new bounded plan; it was not implemented in this assignment.

# M3-A Alert plan checkpoint result

Status: one-route production/test plan completed; independent review required

- scope is exactly `/ru/components/alert/`; no migration was implemented;
- the current path is traced from typed definition, Russian language-pack
  prose and `resources/component-catalog/examples/docara.alert.ru.md` through
  the catalog projector and legacy Alert renderer to
  `ru/components/alert/index.html`;
- the sole proposed owner is
  `docs/site/content/ru/components/alert.md`;
- a disposable build at M2 HEAD reproduced 103 pages, 321 files, 206 static
  HTML documents, 18,866 local references and zero broken references;
- full and isolated trees are byte-identical with manifest SHA-256
  `aad7bbd5a3684e5eb21a43953cf737f1679959d98ae41121d248a683c0f0171d`;
- Alert HTML SHA-256 is
  `a12740106a16dc42a4916c53e5f33f65fb4e18f527eaec857825f712f559dec9`;
- Chromium confirms all five Alert examples at desktop and mobile widths with
  zero console errors and warnings;
- the plan requires isolated route selection before compilation and
  catalog/example projection, using the same `PageBuilder` as the full build;
- the exact allowlist and Russian language-pack reductions are deferred until
  successful implementation parity;
- evidence:
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3a-alert-plan/`.

M3-A implementation, global source ownership, migration coverage, release and
production readiness remain unclaimed. The plan was subsequently accepted by
an independent audit at commit
`b14fe4e1e70a5465fe382bd5ced1de26cb65a315`.

# M3.1 Russian components execution-contract result

Status: PASS; overall M3 goal remains in progress

- one durable goal now covers all 32 public Russian component routes and its
  recovery contract defines 30 bounded batches without treating each batch as
  a completed goal;
- the exact inventory contains 2 physical Markdown owners and 30 generated
  projections at the accepted parent revision;
- two clean full builds produce 103 pages and 321 files with byte-identical
  trees; all 32 full/isolated component-route comparisons pass;
- static verification covers 206 HTML documents and 18,866 local references
  with zero broken references;
- the browser baseline covers the index and five representative component
  families in desktop/mobile and light/dark modes with zero console errors or
  warnings;
- M3.1 changes only workflow, evidence, graph and handoff; it implements no
  runtime/content/resource/lock change;
- evidence:
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/`.

The next checkpoint is M3.2: early isolated-route selection, the shared
typed-IR/registry/gateway runtime contract and the accepted Alert vertical
slice. M3 completion, global ownership, legacy retirement, release and
production readiness remain unclaimed.

# M3.2 shared runtime and Alert result

Status: PASS; overall M3 goal remains in progress

- isolated physical routes are selected before unrelated page compilation,
  component-catalog projection and declarative-example projection;
- generic `component_block` IR, its renderer-registry entry and the existing
  Smart gateway now render Alert without a component-specific pipeline;
- `/ru/components/alert/` is owned by
  `docs/site/content/ru/components/alert.md` and keeps five variants plus the
  tabbed example/code/copy experience;
- Russian Alert page prose is removed from the language pack, its route is
  removed from the allowlist, and the zero-reference Russian example
  projection is retired with a Git rollback boundary;
- full and isolated trees are identical: 103 pages, 321 files, Alert SHA-256
  `51897e5fc51ba73f2118e895065bf8d35c634c103206794d6e3e03d24a3c1e75`;
- static verification covers 206 HTML documents and 18,872 references with
  zero broken; PHPUnit passes 368 tests and 7,347 assertions;
- desktop/mobile light/dark browser checks pass tabs, copy, focus, roles,
  responsive tables and zero console/page errors;
- evidence:
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/M3.2-RUNTIME-ALERT.md`.

M3.3 is next. Three of 32 component routes are physical; global ownership,
derived-view convergence, full legacy retirement, release and production
readiness remain unclaimed.

## M3.3 batch 07 result

Status: PASS; M3.3 continues

- `/ru/components/headings-and-text/` and
  `/ru/components/lists-and-quotes/` now have physical Markdown owners;
- all physical component pages enter the target PageBuilder by one route
  pattern, not a per-component whitelist;
- typed native IR now includes generic list and blockquote nodes with physical
  source ranges;
- full/isolated trees are exact, static verification reports 0 broken, and
  desktop/mobile browser smoke passes tabs, copy, focus and responsive layout;
- the two Russian pack/allowlist records and zero-reference localized examples
  are retired with commit rollback;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-07-native-text-lists.md`.

Five of 32 component routes are physical; batch 08 (links/images and table) is
next. Overall M3 and release/production readiness remain unclaimed.

## M3.3 batch 08 result

Status: PASS; M3.3 continues

- `/ru/components/links-and-images/` and `/ru/components/table/` now have one
  physical Markdown owner each;
- typed native IR adds one generic image node and reuses the shared native
  table/code/example renderers;
- browser acceptance found and fixed two shared example-markup defects that
  could discard content after a table preview; regression tests cover valid,
  balanced markup and retained following sections;
- full/isolated trees are exact; static verification reports 206 HTML pages,
  18,884 local references and zero broken;
- desktop/mobile light/dark checks pass images, tabs/copy, responsive tables,
  post-example content and zero console warnings/errors;
- Russian pack/allowlist records and two zero-reference localized examples are
  retired with commit rollback;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-08-native-links-table.md`.

Seven of 32 component routes are physical; batch 09 (code and
footnotes/sources) is next. Overall M3 and release/production readiness remain
unclaimed.

## M3.3 batch 09 result

Status: PASS; M3.3 continues

- `/ru/components/code/` and `/ru/components/footnotes-and-sources/` now have
  one physical Markdown owner each;
- one shared example contract accepts safe nested CommonMark fences; source
  tabs no longer create an empty code block;
- footnote/noteref/backlink roles and targets work inside the generic example
  SourceNode without a Footnotes-specific pipeline;
- full/isolated trees are exact; static reports 206 HTML, 18,890 references and
  zero broken; desktop/mobile light/dark browser checks pass copy, code,
  anchors and zero console warnings/errors;
- exact Russian pack/allowlist records and two zero-reference localized
  examples are retired with commit rollback;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-09-native-code-footnotes.md`.

Nine of 32 component routes are physical; batch 10 (details and backlinks) is
next. Overall M3 and release/production readiness remain unclaimed.

## M3.3 batch 10 result

Status: PASS; M3.3 continues

- `/ru/components/details/` and `/ru/components/backlinks/` now have one
  physical Markdown owner each and useful user-facing documentation;
- one generic `typed_directive` IR node covers both block shapes with exact
  source ranges and fail-closed unknown aliases;
- layout composition consumes the hash-checked PageBuilder main result instead
  of reparsing page content, so hydrated Backlinks is no longer overwritten;
- a disposable hash-bound backlink projection preserves exact isolated rebuild
  parity without compiling other routes;
- Russian Backlinks UI copy is resolved from `content/ru/lang.json`, while the
  two pack prose records and localized examples are retired;
- full/isolated trees are exact; static reports 206 HTML, 18,917 references and
  zero broken; keyboard and desktop/mobile light/dark browser gates pass with
  zero console warnings/errors;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-10-details-backlinks.md`.

Eleven of 32 component routes are physical; batch 11 (Banner and Download) is
next. Overall M3 and release/production readiness remain unclaimed.

## M3.3 batch 11 result

Status: PASS; M3.3 continues

- Banner and Download now have physical Russian Markdown owners and matching
  portable starters;
- both reuse the generic typed-directive IR and one PageBuilder/renderer path;
- Banner covers all semantic variants, while Download uses a real existing
  content asset with native download/open behavior and accurate checksum text;
- full/isolated trees are exact; static reports 206 HTML, 18,916 references and
  zero broken; desktop/mobile light/dark browser gates pass with zero console
  warnings/errors;
- exact pack/allowlist records and localized examples are retired with commit
  rollback evidence;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-11-banner-download.md`.

Thirteen of 32 component routes are physical; batch 12 (Button and Icon/Kbd)
is next. Overall M3 and release/production readiness remain unclaimed.

## M3.3 batch 12 result

Status: PASS; M3.3 continues

- Button, Icon and Kbd now have physical Russian Markdown owners and matching
  portable starters;
- one manifest registry supplies Badge/Button/Icon/Kbd aliases, prop/slot
  contracts and templates to the existing typed ComponentNode and Smart
  gateway; former hard-coded Button/Icon/Kbd renderer branches are removed;
- full/isolated trees are exact; static reports 206 HTML, 18,914 references and
  zero broken; desktop-light and mobile-dark browser gates prove responsive
  actions, accessible icons, keyboard notation, tabs/copy/focus and zero
  console warnings/errors;
- exact pack/allowlist records and zero-reference localized examples are
  retired with commit rollback evidence;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-12-button-icon-kbd.md`.

Sixteen of 32 component routes are physical; batch 13 (Card and Hero) is next.
Overall M3 and release/production readiness remain unclaimed.

## M3.3 batch 13 result

Status: PASS; M3.3 continues

- Card and Hero now have physical Russian Markdown owners and portable starters;
- both reuse the generic typed-directive IR and one PageBuilder/renderer path;
- full/isolated trees are exact; static reports 206 HTML, 18,922 references and
  zero broken; desktop-light Card and mobile-dark Hero checks cover every
  layout variant, actions, accessible media and responsive overflow;
- exact pack/allowlist records and zero-reference localized examples are
  retired with commit rollback evidence;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-13-card-hero.md`.

Eighteen of 32 component routes are physical; batch 14 (Grid and Figure) is
next. Overall M3 and release/production readiness remain unclaimed.

## M3.3 batch 14 result

Status: PASS; M3.3 continues

- Grid and Figure now have physical Russian Markdown owners and portable starters;
- both reuse generic typed-directive IR and one PageBuilder/renderer path;
- full/isolated trees are exact; static reports 206 HTML, 18,925 references and
  zero broken; browser verifies responsive Grid and loaded accessible Figure
  images at desktop/mobile and light/dark;
- exact pack/allowlist records and zero-reference localized examples retired;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-14-grid-figure.md`.

Twenty of 32 component routes are physical; batch 15 (Media and Logos) is next.
Overall M3 and release/production readiness remain unclaimed.

## M3.3 batch 15 result

Status: PASS; M3.3 continues

- Media and Logos now have physical Russian Markdown owners and starters;
- both reuse generic typed-directive IR and one PageBuilder/renderer path;
- full/isolated exact; static reports 206 HTML, 18,937 references, zero broken;
  desktop/mobile light/dark browser checks prove loaded accessible assets,
  responsive sides, links and tones with zero console issues;
- pack/allowlist records and zero-reference localized examples retired;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-15-media-logos.md`.

Twenty-two of 32 routes are physical; batch 16 (Diagram and Math) is next.
Overall M3 and release/production readiness remain unclaimed.

## M3.3 batch 16 result

Status: PASS; M3.3 continues

- Diagram and Math have physical Russian Markdown owners and starters;
- generic typed-directive/PageBuilder path preserves accessible Mermaid source
  and inline/block TeX without adding client runtime dependencies;
- full/isolated exact; static 206 HTML, 18,939 references, zero broken; browser
  desktop/mobile light/dark has zero overflow and console issues;
- exact pack/allowlist/example legacy retired with rollback;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-16-diagram-math.md`.

Twenty-four of 32 routes are physical; batch 17 is Code-from-file and HTML.
Overall M3 remains unclaimed.

## M3.3 batch 17 result

Status: PASS; M3.3 continues

- Code-from-file and HTML now have physical Russian Markdown owners and
  portable starters;
- both reuse generic typed-directive IR and one PageBuilder/renderer path; the
  shared Markdown example renderer now retains authored source context for an
  external-code preview;
- full/isolated trees are exact; static reports 206 HTML, 18,942 references and
  zero broken; keyboard copy plus desktop/mobile light/dark browser gates pass
  with zero overflow and console issues;
- exact pack/allowlist/example legacy retired with rollback;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-17-code-html.md`.

Twenty-six of 32 component routes are physical; batch 18 is Embed and Example.
Overall M3 remains unclaimed.

## M3.3 batch 18 result

Status: PASS; M3.3 continues

- Embed and Example now have physical Russian Markdown owners and starters;
- both reuse generic typed-directive/example IR and one PageBuilder path;
- browser acceptance fixed the common consent-template activation and now uses
  Markdown-owned loading labels instead of hard-coded English public copy;
- full/isolated exact; static reports 206 HTML, 18,942 references and zero
  broken; offline consent activation and sandboxed interactive examples pass
  at desktop/mobile light/dark with zero overflow or console issues;
- exact pack/allowlist/example legacy retired with rollback;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-18-embed-example.md`.

Twenty-eight of 32 component routes are physical; batch 19 is Steps and Tree.
Overall M3 remains unclaimed.

## M3.3 batch 19 result

Status: PASS; M3.3 continues

- Steps and Tree now have physical Russian Markdown owners and starters;
- both reuse generic typed-directive IR and one PageBuilder/registry/gateway;
- shared Tree keyboard behavior exposes correct expanded state, retains focus
  and truly hides/restores branches without controls in static mode;
- full/isolated exact; static reports 206 HTML, 18,944 references and zero
  broken; desktop/mobile light/dark browser checks have zero overflow or
  console issues;
- exact pack/allowlist/example legacy retired with rollback;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-19-steps-tree.md`.

Thirty of 32 component routes are physical; batch 20 is Tabs. Overall M3 and
release/production readiness remain unclaimed.

## M3.3 batch 20 result

Status: PASS; M3.3 detail migration is complete, M3 continues

- Tabs now has a physical Russian Markdown owner and starter;
- it reuses generic typed-directive IR, the shared keyboard runtime and one
  PageBuilder/registry/gateway;
- full/isolated exact; static reports 206 HTML, 18,949 references and zero
  broken; desktop/mobile light/dark browser checks prove ArrowLeft/ArrowRight,
  Home/End, focus, ARIA panel selection and zero overflow/console issues;
- the generated component detail receipt is now empty and valid; only the
  generated component index remains;
- exact pack/allowlist/example legacy retired with rollback;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-20-tabs.md`.

Thirty-one of 32 routes are physical; batch 21 owns the component index and
converges derived views. Overall M3 and release/production readiness remain
unclaimed.

## M3.4 batch 21 result

Status: PASS; M3.3 and M3.4 complete, M3 continues

- all 32 Russian component routes now have physical Markdown owners;
- `/ru/components/` derives its 31-entry list from PageBuilder metadata through
  one generic typed-directive placeholder and hash-bound hydrator;
- generated Russian component catalog pages are zero; navigation, breadcrumbs,
  outline, previous/next and search remain on the same page-result topology;
- full plus isolated index/Badge HTML are byte-identical; static reports 206
  HTML, 18,942 references and zero broken; browser desktop/mobile light/dark
  has zero overflow or console issues;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-21-component-index.md`.

Batch 25 now retires the remaining Russian language-pack component prose and
proves the zero-projection build no longer reads it. Overall M3 and
release/production readiness remain unclaimed.

## M3.5 batch 25 result

Status: PASS; M3.5 continues

- the package RU language pack has zero public messages and no component prose;
- public declarative-example labels moved to `content/ru/lang.json`;
- all eight remaining localized catalog examples and the Russian component
  legacy allowlist entry were removed after zero-reference proof;
- the starter physically owns its Russian index, Badge page and UI labels;
- the Batch 25 full output is byte-identical to Batch 21, isolated index/Badge
  hashes are exact, and static verification reports 18,942 references with
  zero broken;
- exact old hashes, replacements and rollback are recorded in
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/old-to-new-map.json`;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-25-language-pack-retirement.md`.

Batch 26 was assigned to suppress zero-page component-catalog assets only when
no locale projection uses them; the empty hash-bound receipt remains diagnostic
evidence. Overall M3 and release/production readiness remain unclaimed.

## M3.5 batch 26 result

Status: PASS; M3.5 complete, M3 continues

- Russian builds no longer publish the seven unreferenced packaged
  component-catalog assets because their trusted projection has zero pages;
- asset publication remains enabled and fail-closed for generated English and
  other non-migrated locale projections;
- full/index-single/Badge-single hashes remain exact and static verification
  reports 18,942 references with zero broken;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-26-zero-page-assets.md`.

M3.6 integrated deterministic, all-route single, static, browser and reverse
acceptance is next. Overall M3 and release/production readiness remain
unclaimed.

## M3.6 batch 27 correction

Status: PASS; integrated acceptance continues

- the real browser gate found English `Copy` text injected by Framework on
  Russian code blocks;
- the shared shell now takes `code.copy`/`code.copied` from the same public
  `content/ru/lang.json` runtime-copy contour and localizes injected controls;
- focused/full/single/static/browser checks pass with zero console issues;
- evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/batch-27-code-copy-localization.md`.

# M3 Russian components final result

Status: PASS; the bounded M3 Goal is complete

- all 32 public routes under `/ru/components/` have exactly one physical
  Markdown owner: `docs/site/content/ru/components.md` plus 31 component files;
- Russian package language data contains no page prose or public UI messages;
  reusable public labels are owned by `docs/site/content/ru/lang.json`;
- generated Russian component routes, their broad allowlist and localized
  catalog examples are zero;
- the runtime contains one `PageBuilder`, one `DocumentRendererRegistry`, one
  `SmartComponentGateway` and generic typed component contracts; isolated
  selection occurs before unrelated compilation/projection;
- two disposable full builds selected 103 pages, produced 309 files and are
  byte-identical with normalized tree SHA-256
  `4aa179bde88d4391cd6b4a3ddeb112d0ef5ff6db2d04b6ec725d897fe0a29426`;
- all 32 isolated component route HTML results exactly match the full build;
- both static verifiers checked 206 HTML documents and 18,942 local references
  with zero broken links/assets;
- full PHPUnit on runtime commit `59427fd` passes 377 tests and 6,540
  assertions; PHP lint passes all 226 source/test/script files;
- browser smoke passes 32/32 routes with no overflow or console/page errors;
  representative desktop/mobile light/dark, keyboard/focus, copy, tabs,
  example/code, table and Smart-component checks pass;
- final evidence is indexed at
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/INDEX.md`.

Scope is deliberately exact: this closes Russian `/ru/components/` M3 only.
Other locales, remaining project-wide M4 legacy retirement, release and
production readiness are not claimed. No merge, push, tag, release or deploy
was performed.

## M4.1 recovery and governance checkpoint

Status: PASS; M4 implementation continues

- exact accepted M3 input is `230ce7504e72162dfb85db4687ba851b49353335`;
- the current Russian build selects 103 pages: 89 physical Markdown owners and
  exactly 14 generated `/ru/examples/` owners;
- all 14 generated routes reproduce byte-identical HTML in full and isolated
  builds;
- the baseline has 309 files; static verification checks 206 HTML and 18,942
  local references with zero broken;
- representative index/detail browser baselines cover desktop/mobile and
  light/dark with zero console errors;
- M4 graph lifecycle is active at contract readiness, not implementation PASS;
- recovery and evidence:
  `source/workflow/2026-08-02-docara-m4-public-ru-unification-goal.md` and
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/m4-public-ru-unification/INDEX.md`.

No runtime or content migration and no legacy deletion is claimed by M4.1.

## M4.2 physical examples checkpoint

Status: PASS; M4 implementation continues

- `/ru/examples/` and all thirteen detail routes now have canonical physical
  Markdown owners under `docs/site/content/ru/examples*`;
- URLs, source presentation, internal preview behavior, navigation, search,
  outline, breadcrumbs and previous/next are preserved;
- all fourteen full/isolated HTML results match exactly and static verification
  reports zero broken links/assets;
- evidence:
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/m4-public-ru-unification/M4.2-PHYSICAL-EXAMPLES.md`.

## M4.3 generated public path retirement

Status: PASS; M4 typed-contour convergence continues

- all 103 selected Russian routes are physical Markdown owners and generated
  public owners are zero;
- both obsolete public page projectors, legacy example descriptors/schema,
  demonstrator page templates/view models and both generated-page receipts are
  removed after parity and zero-reference proof;
- a disposable 103-page full build passes static verification at 206 HTML,
  21,436 local references and zero broken; all fourteen example routes remain
  exact in isolated builds;
- package-owned sources for other locales and the effective machine component
  catalog remain intentionally intact;
- evidence:
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/m4-public-ru-unification/M4.3-PROJECTOR-RETIREMENT.md`.

M4.4 must still remove the trusted-main/generated pipeline bypass and prove all
103 routes use the typed PageBuilder contour. Full M4, M5, release and
production remain unclaimed.

## M4.4 typed public contour

Status: PASS; integrated M4 acceptance continues

- every one of the 103 selected pages now compiles through one PageBuilder to
  typed in-memory Document IR;
- the layout shell consumes an explicit PageBuilder RenderArtifact;
  `buildGenerated()`, `buildRendered()` and all trusted-main/generated bypass
  identifiers are absent from runtime and tests;
- two 103-page/305-file disposable builds are byte-identical; each static
  verifier checks 206 HTML and 21,436 local references with zero broken;
- representative landing, reference, example and component isolated builds
  exactly match full HTML;
- focused tests pass 64 tests and 1,670 assertions;
- evidence:
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/m4-public-ru-unification/M4.4-TYPED-CONTOUR.md`.

All-route isolated and complete browser acceptance remain M4.5 work. Other
locales, package compatibility API removal, M5, release and production remain
unclaimed.

## M4.5 integrated acceptance

Status: PASS; final reverse audit and handoff remain

- all 103 current Russian public routes have one physical Markdown owner and
  zero generated public owners;
- 103/103 isolated builds produce exact full-build HTML;
- two disposable 103-page builds contain 305 files each and are byte-identical
  with normalized tree SHA-256
  `e76f488fd24638eb630ae32b345f60505fd9ecc9e6d2b94a2368e5b9366bb245`;
- both static verifiers check 206 HTML documents and 21,436 local references
  with zero broken;
- full PHPUnit passes 359 tests and 5,935 assertions; Pint, PHP lint, JSON,
  YAML, Composer, graph and diff checks pass;
- browser acceptance passes 24 representative viewport/theme cases and smoke
  103/103 routes with zero page overflow or console/page errors; keyboard tabs,
  tree focus, copy, responsive tables and media pass;
- evidence:
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/m4-public-ru-unification/M4.5-INTEGRATED-ACCEPTANCE.md`.

M5 exact-archive acceptance, other locales, release and production remain
unclaimed. No merge, push, tag, release or deploy was performed.

# M4 final result

Status: PASS_WITH_NONCLAIMS; M4 is complete

- the full current Russian public site has 103 physical Markdown owners and no
  generated public owner;
- one PageBuilder, typed in-memory Document IR, renderer registry and Smart
  gateway serve full and isolated builds;
- retired public projectors, generated allowlist/receipts and trusted-main
  bypass have zero runtime/test references and commit-addressable rollback;
- 103/103 full/single HTML parity, deterministic full builds, zero broken
  links/assets, full tests and browser acceptance pass;
- reverse audit:
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/m4-public-ru-unification/M4.6-REVERSE-OUTCOME-AUDIT.md`.

M5 exact-archive acceptance is now ready but not executed. Other locales,
release and production remain unclaimed. No merge, push, tag, release or deploy
was performed.

# M5 product stabilization result

Status: IMPLEMENTATION PASS; independent exact-archive acceptance pending

- portable `init` creates a clean project without Node.js and records explicit
  engine/project/generated ownership plus immutable package, dependency and
  Framework provenance;
- `update` has machine-readable verify/dry-run/diff, explicit atomic apply and
  validated rollback; dirty, unknown, conflicting, stale, symlinked or corrupt
  states fail before mutation and project-owned files are never update targets;
- an exported Composer ZIP installs as dist into a clean consumer whose lock
  resolves the exact candidate revision; the installed CLI works from the
  separately initialized project directory;
- full and isolated builds share one PageBuilder, typed in-memory IR, renderer
  registry and Smart gateway; failed isolated compilation preserves all
  accepted output and receipts;
- 103/103 public routes reproduce their full-build HTML, two 305-file builds
  are byte-identical, and static verification reports 21,440 references with
  zero broken;
- full PHPUnit passes 376 tests and 6,061 assertions; Pint, PHP lint, JSON,
  YAML, Composer, graph and diff checks pass;
- minimal EN LTR and AR RTL fixtures, security policy, browser/a11y matrix and
  the one-Markdown author workflow pass without claiming full translations;
- final evidence:
  `source/workflow/evidence/2026-08-01-docara-unified-architecture/m5-product-stabilization/INDEX.md`.

`docara.batch.m5.stabilize` is complete. `docara.batch.m5.accept` is now ready
for a separate tester-owned read-only run. The global architecture acceptance
gate remains open; merge, push, tag, release and deploy remain unclaimed and
unauthorized.

Exact archive candidate: `48751b8ca221f7185a72ce19188b1441aea93d2e`,
ZIP SHA-256
`d12169b3c5080f219dada00cc976a758263cbc38ef845da11176ed7e34e8334a`.

# Independent M5 acceptance result

Status: PASS_WITH_NOTES; architecture/product candidate accepted

- the independent audit tested exact revision `48751b8` and archive SHA-256
  `d12169b3c5080f219dada00cc976a758263cbc38ef845da11176ed7e34e8334a`;
- the exact-clone result is 376 tests and 6,062 assertions; the earlier 6,061
  executor count is retained as an environment/reporting discrepancy, not
  rewritten into the immutable candidate;
- fresh dist install, lifecycle, 103-route deterministic/full-single/static
  matrices and source/architecture boundaries pass;
- the broad browser matrix is UI-equivalent inherited evidence, while the
  exact candidate has direct representative desktop/mobile smoke;
- evidence:
  `source/workflow/evidence/2026-08-02-docara-r1-release-readiness/R1.1-M5-INDEPENDENT-ACCEPTANCE.md`.

Release, merge, push, tag, publication and production remain unclaimed. R1
release-readiness implementation is active.

# R1 local release-readiness result

Status: PASS_WITH_EXPLICIT_GAPS; separate release approval still required

- exact source `8c0d14566837b6e6f4552d14c656ea14b202cd18` produces a
  655-file ZIP with SHA-256
  `83afd355436284a0040390c88e1d125f3e5648932a23ff324ba9afa9af5eb561`;
- two independent clean clones produce byte-identical ZIP, manifest and
  checksums; two fresh dist consumers have the same lock and pass init, build
  and static verification;
- the packaged public site has 103 routes, 305 files, 103/103 isolated parity,
  21,442 checked references and zero broken;
- a real predecessor/current engine change passes verify, dry-run, apply and
  exact rollback while project-owned files remain preserved;
- 378 tests and 6,076 assertions pass in exhaustive bounded partitions; Pint,
  Composer, lint, JSON/YAML/graph and archive/security scans pass;
- PHP 8.2 and 8.4 are proved locally; PHP 8.3 and the complete Linux matrix are
  honest unexecuted cells;
- exact browser assertions pass RU desktop/mobile and AR RTL without errors or
  overflow; exact screenshots are not claimed because external-font waiting
  timed out, while accepted M5 screenshots are explicitly UI-equivalent only;
- evidence:
  `source/workflow/evidence/2026-08-02-docara-r1-release-readiness/INDEX.md`.

No merge, push, tag, release, publication or deploy was performed. The only
next action is a separate user-approved exact-artifact release review/action.

# R1 independent reverse-audit correction

Status: `CORRECTION_REQUIRED`; previous local readiness withdrawn

The independent audit reproduced deterministic packaging but found broken
local README links inside the exact ZIP and an obsolete public language-pack
contract across schema, starter, public docs, tests and packaged resources.
Source `8c0d14566837b6e6f4552d14c656ea14b202cd18` and ZIP `83afd355…`
remain immutable historical evidence with status `superseded_after_audit`.

R1-C is active at
`source/workflow/2026-08-02-docara-r1c-semantic-correction-goal.md`. No current
corrected candidate or local release-readiness claim existed at that audit
boundary.

# R1-C semantic correction candidate

Status: `IMPLEMENTATION_PASS`; independent exact-artifact retest pending

- public `language_pack` config/model/schema/data/runtime is removed; public
  pages use physical Markdown and shared UI labels use only locale `lang.json`;
- front matter and `locales.missing_page_policy` are executable, diagnosed and
  covered by positive/negative tests;
- public docs, specification, actual classes/error codes and semantic tests now
  agree; repository and exact-ZIP documentation links fail closed;
- exact source `56a2abf8bad05923f689141afc0bb045aa4d6734` produces a
  reproducible 650-file ZIP with SHA-256
  `04c18c95f2599905b1908fae3e326a9cf1ba47f29327ddd88465c4b4b792f753`;
- two fresh dist consumers, 103-route public deterministic/full-single/static
  matrices and a real old-R1-to-R1-C update/rollback pass;
- fresh RU LTR/AR RTL light/dark screenshots at 1920/1440/390 bind to that
  artifact; overflow and console errors are zero;
- evidence:
  `source/workflow/evidence/2026-08-02-docara-r1c-semantic-correction/INDEX.md`.

The old `83afd355…` artifact remains immutable and superseded. The new
`04c18c95…` artifact is the only current independent-retest target. The
tester-owned local release-readiness gate is not passed here; merge, push, tag,
release, publication and deploy were not performed.

# R1-C independent acceptance and R2 opening

Status: `PASS_WITH_NOTES`; local release candidate accepted

The independent reverse-outcome audit reproduced exact source
`56a2abf8bad05923f689141afc0bb045aa4d6734`, ZIP
`04c18c95f2599905b1908fae3e326a9cf1ba47f29327ddd88465c4b4b792f753`
and manifest `d709d27…`. The verifier passed 650 packaged files; PHPUnit passed
390 tests and 6,024 assertions; the 103-route/305-file public build and 21,437
static references had zero broken links. The retired public language-pack
surface is absent from the artifact.

Local release-readiness is therefore accepted. Planned version
`2.0.0-rc.2`/tag `v2.0.0-rc.2` remain unpublished parameters. The only valid
future tag target for this artifact is its source `56a2abf8…`; subsequent
governance HEADs are dossier revisions, not alternate artifact sources.

R2 is active at
`source/workflow/2026-08-02-docara-r2-production-readiness.md`. It prepares a
production-like exact-package matrix, current/candidate delta and disposable
atomic cutover/rollback proof. `/Users/rim/Sites/docara.test` remains read-only.
Release, tag, publication, live cutover and production are not authorized or
claimed.

# R2 production-readiness dossier result

Status: `PASS_DISPOSABLE`; explicit live approval still required

- exact source `56a2abf8bad05923f689141afc0bb045aa4d6734`, ZIP
  `04c18c95f2599905b1908fae3e326a9cf1ba47f29327ddd88465c4b4b792f753`
  and manifest `d709d27…` remain the only accepted candidate identity;
- fresh dist consumers pass macOS/PHP 8.4.20, macOS/PHP 8.3.31 and Linux
  Debian/PHP 8.3.33; package `.git` is absent;
- the exact public candidate has 103 selected routes, 305 files, 206 HTML,
  21,437 checked references and zero broken; two same-lock clean consumers
  produce byte-identical tree `457790d4…`;
- HTTP smoke passes 103/103 canonical routes; fresh 1920/1440/390,
  light/dark, RU LTR and AR RTL browser evidence has zero console/page errors
  and zero page overflow;
- archive/package/security checks pass with zero advisories, unsafe archive
  paths, private absolute paths or sampled secret signatures;
- the read-only served baseline is 322 files/206 HTML with tree
  `b98ea2f…`; exact SHA inventory classifies 112 changed, 19 removed and 2
  added paths without losing a route or required asset;
- the repository-owned fail-closed cutover helper passed current -> candidate
  -> 103-route smoke -> rollback in a same-filesystem disposable mirror and
  restored the exact current digest;
- Caddy root/TLS health, retention proposal, release window, stop thresholds,
  smoke and rollback commands are recorded in the deployment dossier;
- evidence:
  `source/workflow/evidence/2026-08-02-docara-r2-production-readiness/INDEX.md`.

No file under `/Users/rim/Sites/docara.test`, no Caddy/service state and no
existing backup/staging directory was changed. Merge, push, tag, publication,
release and deploy were not performed. The only next action is the user's
explicit choice whether to deploy this exact candidate to `docara.test`.

# R2 independent determinism correction

Status: `CORRECTION_REQUIRED`

An independent retest disproved the former two-consumer tree-equality claim:
outside Git, `page-metadata.json` used Composer extraction-time `filemtime` as
public `updated_at`. Source `56a2abf8…`, ZIP `04c18c95…` and tree
`457790d4…` are therefore `superseded_after_determinism_audit`, and the prior
R2 PASS is historical only. No valid deploy candidate currently exists.

Active recovery source:
`source/workflow/2026-08-02-docara-r2-determinism-correction.md`. The planned
replacement is unpublished `2.0.0-rc.3`, subject to full independent package,
consumer, build, browser, security and disposable rollback retest. Live
`docara.test` remains unchanged; release and production gates are closed.

# R2 deterministic correction result

Status: `PASS_DISPOSABLE_CORRECTED`

The public metadata contract no longer derives `updated_at` from filesystem
times outside Git. Exact source
`be0ba2db5254e468c7c014016ade02e8b4f3f16c` produces the unpublished rc.3 ZIP
`630d971e94a1222624304a3a5c2a7791586c0b7866ede5b8f3506c93bdebadc0`.
Two clean clones reproduced the package and two fresh dist consumers with
different content mtimes produced the same complete 305-file tree
`425da363fc51d33d2c5b42577980f4ca4603b83814440dbfb06fe419b4cade46`,
including identical page metadata.

The complete macOS PHP 8.4/8.3 and Linux PHP 8.3, static, security,
full/single, 103-route HTTP, browser, old-rc.2 update/rollback and disposable
atomic cutover/rollback matrices pass. The live tree remains unchanged at
`b98ea2f66b733c5146360af68c1fe15b55aa099b33957fe52813772d93ce836f`.
Evidence:
`source/workflow/evidence/2026-08-02-docara-r2-determinism-correction/INDEX.md`.

No merge, push, tag, release, publication, Caddy reload or live deployment was
performed. Production remains closed until the user explicitly approves this
exact candidate and the live preflight confirms unchanged inputs.

# Goal 1 Portable Smart Runtime candidate

Status: `IMPLEMENTATION_COMPLETE_AUDIT_PENDING`

Tracked SF5 Smart artifact v1 is source-pinned and consumed through a bounded
adapter. Deterministic project/package/Docara/Framework providers compile one
SmartRegistry. The existing SmartComponentGateway dispatches by provider
ownership, while SmartRenderer uses registered context/strategy IDs without a
component-ID list. Manual contribution classes, ViewModelFactory and semantic
component switches were removed after exact public parity.

The initialized portable project includes one `project.notice` artifact under
the fixed `smart/` root. It compiles into typed in-memory Document IR, renders
and publishes its CSS through the same registry/Gateway/PageBuilder, without a
component-specific engine source edit. Full integrated, cross-host, security,
determinism, static and browser evidence is indexed at
`source/workflow/evidence/2026-08-02-docara-goal1-portable-smart-runtime/INDEX.md`.

Goal 2/3, release identity and both live sites remain unchanged and unclaimed.

# Exact rc.3 validation deployment

Status: `PASS` at `https://docara-new.test`

The accepted exact rc.3 public tree was deployed to the new empty local test
site by same-filesystem atomic rename, without Caddy reload. The active tree is
`425da363fc51d33d2c5b42577980f4ca4603b83814440dbfb06fe419b4cade46`:
305 files, 206 HTML, 103/103 routes over HTTPS and static broken=0. Search,
settings, tabs, copy and the 390px mobile layout passed browser smoke with zero
page/console errors and zero horizontal overflow.

Immediate rollback is retained at
`/Users/rim/Sites/.docara-new.test-backup-before-rc3-be0ba2d`. The original
target was empty, so the backup contains zero files and exact empty-tree digest
`01ba4719…`. `docara.test`, Caddy configuration and the Caddy process were not
changed. Deployment evidence:
`source/workflow/2026-08-02-docara-new-test-deployment.md`.
