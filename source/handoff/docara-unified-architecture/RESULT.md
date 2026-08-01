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
