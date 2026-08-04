# B5 integrated verification and external gate

Date: 2026-08-04
Status: `historical_partial_pass_before_accepted_form_wave`
Exact product candidate: `ccb076a89535954022ca89eb70b84d6c81d80de3`
Accepted Goal A parent: `8c04160ab50549b060fb933cf80f86193cd92113`

## Result

This matrix remains the exact proof for the unchanged B0-B3 product candidate.
B0-B3 provide a
registry-derived Atlas, registered publisher chrome, safe presentation presets
and useful project-owned content/shell demos. Goal B is **not** ready for
independent acceptance. The later accepted form wave is recorded separately in
`B4-ACCEPTED-FORM-WAVE-LIST-ITEM-GATE.md`; its dropdown requires an unaccepted
`ui.list-item`, so the product/runtime candidate was intentionally not changed.

## Product and test matrix

- full PHPUnit on exact candidate: 460 tests / 8,544 assertions, PASS;
- focused Atlas, binding, project-design, project-demo and preview suites: PASS;
- Pint `--test`: PASS;
- Composer `validate --strict`: PASS; PHP 8.4 output contains only
  Composer-owned deprecation notices;
- Composer dependency audit: no security vulnerability advisories; the fresh
  consumer lock is the reproducible dependency input;
- PHP lint: 380 tracked files; JSON: 510 tracked files; YAML: 36 tracked files,
  PASS;
- candidate-range `git diff --check
  8c04160ab50549b060fb933cf80f86193cd92113..ccb076a89535954022ca89eb70b84d6c81d80de3`:
  PASS;
- exact SF5 cross-host compatibility remains byte-identical with no
  warnings/stderr.

## Full, selected and static builds

Two independent clean project roots built the exact candidate:

- routes: 104;
- files: 307;
- HTML: 208;
- complete-tree digest for both roots:
  `417ebc6247f2a70a385c2d255c250ca768fc74f78f12837c19dd4ed863a34cad`;
- `diff -rq`: empty;
- selected `/ru/components/alert/` rebuild preserves the same complete ledger;
- each static verification: 208 HTML, 21,844 local references, broken=0.

The digest algorithm is
`sha256(sorted("<file-sha256>  <relative-path>\n"))` over the complete output
tree.

## Exact package and fresh consumer

Two independent no-local clean clones produced byte-identical unpublished
packages:

- ZIP SHA-256:
  `1b66bd9674faadb0bcb4849c7767a4186b7b78cdf2fdf3f3637d3ae4f229e61c`;
- release manifest SHA-256:
  `45b482360842fe8661d9dad7fe6a65a8a957374836eb6053e238f39587376b18`;
- checksum file SHA-256:
  `117d326ae538fcc6b40d7e913f62378d8fa330938db1d79287c09a6249ec37b2`;
- package files: 780;
- repository package verifier: PASS for both artifacts.

A fresh Composer dist consumer contains no package `.git` and no
`node_modules`. Init copied 78 starter files. Doctor reports 13 Smart, 18
Design, 3 Binding and 36 schema entries at exact engine revision. Full build is
39 routes / 180 output files; static verification is 78 HTML / 3,931 local
references / broken=0. Full then selected `/ru/project-demos/` output is
byte-identical at complete-tree digest
`53fd4f2ecf50a36e8fc7d11f7fbbaadbb32538fb9e429cc3fdba188545b04b63`.

## Browser and accessibility smoke

The exact documentation output was served locally and checked with Playwright:

- search and settings use the registered shell path;
- settings modal exposes light/dark, blur and radius controls; dark mode is
  applied, Esc works, focus remains in the dialog while open;
- desktop and 390 px mobile, LTR and injected RTL have horizontal overflow 0;
- console errors=0 and warnings=0; requested local resources return 200.

The fresh consumer `/ru/project-demos/` page was checked separately:

- install mode changes the inert command to
  `composer require simai/docara:^2.0 --dev` without executing it;
- two configurator options update the static total to `4 500 ₽`;
- copy feedback works; desktop/mobile and LTR/RTL overflow is 0;
- console errors=0 and warnings=0; no backend/order/payment/command request is
  emitted.

This is representative exact-candidate smoke. A complete Goal B Framework and
browser acceptance cannot be claimed until B4 has accepted owner artifacts.

## Security, boundaries and rollback

- Atlas is derived from admitted Design, Smart and Binding registries; it is
  not editable runtime truth;
- container child/slot/count/order/depth and malformed/unclosed fence negatives
  fail before render;
- project demos are data/artifact-only and pass traversal, symlink, duplicate,
  namespace and unsafe-path gates;
- legacy trusted search/breadcrumb/pager leaves have zero runtime references;
- one Gateway, renderer registry, LayoutComposer and PageBuilder remain;
- rollback is ordinary `git revert` of Goal B commits in reverse order; builds,
  packages, preview and consumer roots are disposable and no live site changed.

## External stop condition

The original gate is documented in [B4-FRAMEWORK-WAVE.md](B4-FRAMEWORK-WAVE.md).
It was narrowed after owner acceptance by
[B4-ACCEPTED-FORM-WAVE-LIST-ITEM-GATE.md](B4-ACCEPTED-FORM-WAVE-LIST-ITEM-GATE.md).
The only safe next action is to obtain an independently accepted exact-pinned
`ui.list-item` owner artifact, then resume B4 and repeat the affected B5
matrix. Goal C remains unauthorized.

## Governance validation

- generated project context: byte-identical to canonical inputs, issues=[];
- canonical inventory: 1 goal / 13 stages / 16 batches / 8 mappings / 4
  metrics; every tracked graph JSON decodes successfully;
- focused Atlas/binding/project/preview/documentation/context/cross-host suite:
  43 tests / 2,185 assertions, PASS;
- full PHPUnit after governance synchronization: 460 tests / 8,544
  assertions, PASS;
- candidate-range and worktree `git diff --check`: PASS.
