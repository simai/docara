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

Batch 26 must suppress zero-page component-catalog assets/receipts only when no
locale projection uses them, then M3.6 performs integrated acceptance. Overall
M3 and release/production readiness remain unclaimed.
