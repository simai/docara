# Architecture contract checkpoint evidence

Date: 2026-08-01

Branch: `codex/docara-unified-architecture`

Base revision: `2928d68b81665dd4873cebeb87a6192343c28805`

Candidate binding: the commit containing this file; verify its parent is the
base revision above and record `git rev-parse HEAD HEAD^{tree}` after commit.
A commit cannot contain its own SHA or tree hash without changing itself.

## Contract synchronized

- one public page in one locale -> `content/<locale>/<route>.md`;
- sole shared public locale strings -> `content/<locale>/lang.json`;
- no target public `resources/i18n` or `site.json` compatibility;
- package-owned CLI/build localization stays outside public page inputs;
- `MarkdownCompiler` creates typed IR in memory; serialization is optional
  disposable cache, search, `--dump-ir` or test evidence;
- full and single-page modes use one PageBuilder pipeline and differ only by
  route selection;
- `badge_source_ready` gates M2 while global `source_ownership` remains open
  until M3;
- the library repo does not own `composer.lock`; release evidence records the
  resolved tuple and consumer applications own their lock.

## M0 discrepancies resolved

- stale `58` assertions replaced by source/contract-derived coverage;
- newly exposed projected-page expectation derives from physical authored
  component sources;
- root README links to existing `components/syntax.md`; the missing components
  index remains an M3 source migration item;
- M0 mapping/status history is corrected by current evidence rather than
  accepted as proof;
- Composer lock ownership is explicit in `DOC-ADR-016`.

## Verification

- graph JSON parse: PASS;
- official project graph validator: PASS, 1 goal, 6 stages, 7 batches,
  4 metrics, 6 implementation mappings, 0 warnings, 0 blockers;
- graph source refs and Markdown anchors: PASS;
- local Markdown links: PASS across 15 contract/handoff/workflow files;
- all 6 implementation mappings have code, test, evidence and deletion gates:
  PASS;
- `git diff --check`: PASS;
- changed PHP formatter check: PASS;
- focused PHPUnit: PASS, 12 tests, 1080 assertions;
- full PHPUnit: PASS, 343 tests, 7194 assertions, PHP 8.4.20;
- public build behavior: covered by the full suite and preserved M0 exact
  full/single manifest and badge hashes; no runtime/template/content change in
  this checkpoint.

## Dependency evidence

Tests ran in `/tmp/docara-contract-checkpoint.ffpiVM`, outside the worktree.
The temporary resolver produced 72 packages and lock SHA-256
`d868139fee40e0977952b6a3181a02cf50d635af0245f097c69bd68625a7c55e`.
The first PHP 8.3.31 run failed its generated platform check because the fresh
tuple requires PHP >= 8.4.1; PHP 8.4.20 passed. No `vendor` or `composer.lock`
was added to the repository.

## Nonclaims

- M1/M2 runtime implementation is not part of this checkpoint;
- `badge_source_ready`, vertical-slice, global source ownership, architecture,
  release and production gates are not passed here;
- no merge, tag, release or deploy was performed.
